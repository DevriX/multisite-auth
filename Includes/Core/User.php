<?php namespace MUAUTH\Includes\Core;

// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);

/**
  * filters class
  */

class User
{
    /** Class instance **/
    protected static $instance = null;

    /** Get Class instance **/
    public static function instance()
    {
        return null == self::$instance ? new self : self::$instance;
    }

    /**
      * Login user by ID
      *
      * @since 0.1
      * @param $user_id int
      * @param $remember bool keep session for couple weeks
      * @return int logged in user ID if successful, otherwise 0
      */

    public static function loginUser( $user_id, $remember=false )
    {
        // trigger hook
        do_action('muauth_pre_login_user', $user_id);

        if ( is_user_logged_in() ) {
            // logout current user first
            self::logout();
        }

        // set current user
        wp_set_current_user( $user_id );
        // update auth cookie for current user
        wp_set_auth_cookie( $user_id, $remember );

        // trigger hook
        do_action('muauth_post_login_user', $user_id);

        return (int) get_current_user_id();
    }

    /**
      * Logout current user
      *
      * @since 0.1
      * @return void
      */

    public static function logout()
    {
        return wp_logout();        
    }

    /**
      * Email user with their password-reset link
      *
      * @since 0.1
      * @param $user WP_User|int target user
      * @return void
      */

    public static function emailPasswordResetCode($user, $blog_id=0)
    {
        if ( is_numeric( $user ) ) {
            $user = get_userdata( $user );
        }

        if ( empty( $user->ID ) )
            return;

        if ( empty( $blog_id ) ) {
            $network = get_network();
            $blogname = $network->site_name;
            $rp_url = muauth_get_lostpassword_url(null, $network->id);
            $home_url = network_home_url( '/' );
        } else {
            $rp_url = muauth_get_lostpassword_url(null, $blog_id);
            $_blog = get_blog_details($blog_id);
            $home_url = $_blog->home;
            $blogname = $_blog->blogname;
        }

        $key = get_password_reset_key( $user );
        $rp_url = add_query_arg(array(
            'action' => 'rp',
            'key' => $key,
            'login' => rawurlencode($user->user_login)
        ), $rp_url);

        /** localized by WordPress **/
        $message = __('Someone has requested a password reset for the following account:') . "\r\n\r\n";
        $message .= $home_url . "\r\n\r\n";
        $message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
        $message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
        $message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
        $message .= '<' . $rp_url . ">\r\n";

        /** localized by WordPress **/
        $title = sprintf( __('[%s] Password Reset'), $blogname );

        $message = apply_filters('muauth_password_reset_email', $message, $user, $key);
        $title = apply_filters('muauth_password_reset_email_title', $title, $user, $key);
        $headers = apply_filters('muauth_password_reset_email_headers', '', $user);

        if ( !trim($message) || !trim($title) )
            return;

        $catch = muauth_mail( $user->user_email, $title, $message, $headers );

        do_action('muauth_password_reset_wp_mail_catch', $catch);
    }

    /**
      * Sends the activation email to a user by login
      * 
      * If no user is found in the pending signups, it'll bail
      *
      * @since 0.1
      * @param $login string email/slug
      * @return bool
      */

    public static function resendActivation($login)
    {
        /* custom validation, return bool-type when you're hooking into this */
        $early_bool = apply_filters( 'muauth_user_resend_activation_early_return', null, $login );

        if ( isset( $early_bool ) )
            return $early_bool;

        global $wpdb;
        $is_email = strpos($login, '@');
        $pending = false;

        if ( $is_email ) {
            $signup = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wpdb->signups WHERE user_email = %s AND active != '1' LIMIT 1", $login) );
        } else {
            $signup = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wpdb->signups WHERE user_login = %s AND active != '1' LIMIT 1", $login) );
        }

        if ( !$signup || empty( $signup->activation_key ) ) {
            do_action( 'muauth_user_resend_activation_fail', $signup, $login );
            return;
        }

        return muauth_wpmu_signup_user_notification($signup->user_login, $signup->user_email, $signup->activation_key);
    }

    /**
      * Verify password reset key
      *
      * @since 0.1
      * @param $key string password reset key
      * @param $user WP_User|int user to match
      * @return bool
      */

    public static function verifyUserResetKey( $key, $user ) {   
        if ( is_numeric( $user ) ) {
            $user = get_userdata( $user );
        }

        if ( isset( $user->user_login ) && !empty( $user->user_login ) ) {
            $verification = !is_wp_error(check_password_reset_key( $key, $user->user_login ));
        } else {
            $verification = false;
        }

        return apply_filters( 'muauth_verify_user_reset_key', $verification, $key, $user );
    }

    /**
      * Change user password
      *
      * @since 0.1
      * @param $user WP_User|int user to update
      * @param $password string raw new password
      * @return bool
      */

    public static function updatePassword( $user, $password )
    {
        if ( !isset( $user->ID ) || !isset( $user->data->user_pass ) ) {
            // nothing? catch you on line:125
        } else if ( wp_check_password( $password, $user->data->user_pass, $user->ID) ) {
            /** The user has entered the same password as their old one **/
            muauth_add_error(
                'same_password',
                sprintf(__('This appears to be your old password, are you sure you want to update it? Use this password to <a href=%s>login</a>, or enter a different password to force an update.', MUAUTH_DOMAIN), muauth_get_login_url()),
                'notice'
            );
        } else if ( apply_filters( 'muauth_pass_user_update_password', true, $user, $password ) ) {
            // set new password
            wp_set_password( $password, $user->ID );
            // logout user
            self::logout();

            return true;
        }

        return apply_filters( 'muauth_user_update_password_fail', false, $user, $password );
    }

    /**
      * Verify if a user is pending activation or not
      *
      * @since 0.1
      * @param $login string email/slug
      * @return bool
      */

    public static function isPending( $login )
    {
        /* custom validation, return bool-type when you're hooking into this */
        $early_bool = apply_filters( 'muauth_user_is_user_pending_early_return', null, $login );

        if ( isset( $early_bool ) )
            return $early_bool;

        global $wpdb;
        $is_email = strpos($login, '@');
        $pending = false;

        if ( $is_email ) {
            $signup = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wpdb->signups WHERE user_email = %s", $login) );
        } else {
            $signup = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wpdb->signups WHERE user_login = %s", $login) );
        }

        if ( $signup != null && empty( $signup->active ) ) {
            $registered_at =  mysql2date('U', $signup->registered);
            $now = current_time( 'timestamp', true );
            $diff = $now - $registered_at;
            if ( $diff > apply_filters( 'muauth_signup_expiracy_interval', 2 * DAY_IN_SECONDS, $login ) ) {
                $wpdb->delete($wpdb->signups, array(
                    $is_email ? 'user_email' : 'user_login' => $login
                ));
            } else {
                $pending = true;
            }
        }

        return apply_filters( 'muauth_user_is_user_pending', $pending, $login );
    }

}