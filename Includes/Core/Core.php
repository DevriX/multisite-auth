<?php namespace MUAUTH\Includes\Core;

use \MUAUTH\MUAUTH;

// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);

/**
  * Muauth Core class
  */

class Core
{
    /** Class instance **/
    protected static $instance = null;

    /** Get Class instance **/
    public static function instance()
    {
        return null == self::$instance ? new self : self::$instance;
    }

    public static function startBuffer()
    {
        return ob_start();
    }

    public static function initUserHooks()
    {
        global $current_user;

        if ( $current_user->ID ) {
            do_action( 'muauth_init_logged_in', $current_user );
        } else {
            do_action( 'muauth_init_logged_out' );
        }
    }

    public static function wpUserHooks()
    {
        global $current_user;

        if ( $current_user->ID ) {
            do_action( 'muauth_wp_logged_in', $current_user );
        } else {
            do_action( 'muauth_wp_logged_out' );
        }
    }

    public static function dispatchAuthActionRedirect()
    {
        global $pagenow;

        if ( 'wp-login.php' === $pagenow ) {
            global $muauth;

            if ( muauth_is_site_disabled( $muauth->current_blog_id ) )
                return; // site disabled

            $action = muauth_get_current_login_action();

            do_action( 'muauth_pre_disptch_auth_action_redirect', $action );
            do_action( "muauth_pre_disptch_auth_action_{$action}_redirect" );

            switch ( $action ) {
                case 'login':
                    return self::dispatchLoginRedirect();
                    break;

                case 'register':
                    return self::dispatchRegisterRedirect();
                    break;

                case 'lost-password':
                    return self::dispatchLostPasswordRedirect();
                    break;

                case 'activate':
                    return self::dispatchActivationRedirect();
                    break;

                case 'logout':
                    return self::dispatchLogoutRedirect();
                    break;

                default:
                    /**
                      * And we fall back to this couple lines of code
                      * when no action PARAM has matched out targeted
                      * ones (see muauth_get_current_login_action())
                      * e.g /wp-login.php?action=test or /wp-login.php?action=
                      * WP will threat all null|invalid actions as logins and
                      * will serve a login screen, but upon submit, POST action
                      * will not be there (unless a client edits the source to
                      * add it and this is why we use hook this method 
                      * redirectLoginUnknownAction)
                      *
                      * @see muauth_get_current_login_action filter to filter
                      * the action value
                      * @see muauth_get_current_login_action_actions filter
                      * to add more actions to watch
                      *
                      * @param string $action action value if set
                      */
                    do_action( 'muauth_disptch_auth_action_redirect', $action );
                    do_action( "muauth_disptch_auth_action_{$action}_redirect" );
                    break;
            }
        }

        else if ( 'wp-signup.php' === $pagenow ) {
            return self::dispatchRegisterRedirect();
        }

        else if ( 'wp-activate.php' === $pagenow ) {
            return self::dispatchActivationRedirect();
        }
    }

    public static function dispatchLoginRedirect()
    {
        if ( !muauth_is_component_active('login') ) {
            do_action('muauth_pre_dispatch_inactive_component_redirect', 'login');
            do_action('muauth_pre_dispatch_inactive_login_component_redirect');
            return;
        }

        $login_url = muauth_get_login_url();

        if ( !empty( $_REQUEST ) ) {
            $args = array_map('urlencode', $_REQUEST);
        } else {
            $args = array();
        }

        /* pluggable: filter to include or exclude queries to attach to URL */
        $args = apply_filters( 'muauth_pre_dispatch_login_redirect_request', $args, $login_url );

        if ( $args ) {
            $login_url = add_query_arg($args, $login_url);
        }

        $login_url = apply_filters( 'muauth_pre_dispatch_login_redirect_url', $login_url );
        
        // trigger hook
        do_action('muauth_pre_redirect_to_auth', 'login', $login_url);
        do_action('muauth_pre_redirect_login_to_auth', $login_url);

        return muauth_redirect( $login_url, true );
    }

    public static function dispatchRegisterRedirect()
    {
        if ( !muauth_is_component_active('register') ) {
            do_action('muauth_pre_dispatch_inactive_component_redirect', 'register');
            do_action('muauth_pre_dispatch_inactive_register_component_redirect');
            return;
        }

        $register_url = muauth_get_register_url();

        if ( !empty( $_REQUEST ) ) {
            $args = array_map('urlencode', $_REQUEST);

            if ( isset($args['action']) ) {
                unset( $args['action'] );
            }
        } else {
            $args = array();
        }

        /* pluggable: filter to include or exclude queries to attach to URL */
        $args = apply_filters( 'muauth_pre_dispatch_register_redirect_request', $args, $register_url );

        if ( $args ) {
            $register_url = add_query_arg($args, $register_url);
        }

        $register_url = apply_filters( 'muauth_pre_dispatch_register_redirect_url', $register_url );
        // trigger hook
        do_action('muauth_pre_redirect_to_auth', 'register', $register_url);
        do_action('muauth_pre_redirect_register_to_auth', $register_url);

        return muauth_redirect( $register_url, true );
    }

    public static function dispatchLostPasswordRedirect()
    {
        if ( !muauth_is_component_active('lost-password') ) {
            do_action('muauth_pre_dispatch_inactive_component_redirect', 'lost-password');
            do_action('muauth_pre_dispatch_inactive_lost-password_component_redirect');
            return;
        }

        $lostpassword_url = muauth_get_lostpassword_url();

        if ( !empty( $_REQUEST ) ) {
            $args = array_map('urlencode', $_REQUEST);

            if ( isset($args['key']) ) {
                // little bit of rename
                $args['code'] = $args['key'];
                unset( $args['key'] );
            }

            else if ( isset($args['action']) ) {
                unset( $args['action'] );
            }
        } else {
            $args = array();
        }

        /* pluggable: filter to include or exclude queries to attach to URL */
        $args = apply_filters( 'muauth_pre_dispatch_lostpassword_redirect_request', $args, $lostpassword_url );

        if ( $args ) {
            $lostpassword_url = add_query_arg($args, $lostpassword_url);
        }

        $lostpassword_url = apply_filters( 'muauth_pre_dispatch_lostpassword_redirect_url', $lostpassword_url );
        // trigger hook
        do_action('muauth_pre_redirect_to_auth', 'lostpassword', $lostpassword_url);
        do_action('muauth_pre_redirect_lostpassword_to_auth', $lostpassword_url);

        return muauth_redirect( $lostpassword_url, true );
    }

    public static function dispatchActivationRedirect()
    {
        if ( !muauth_is_component_active('activation') ) {
            do_action('muauth_pre_dispatch_inactive_component_redirect', 'activation');
            do_action('muauth_pre_dispatch_inactive_activation_component_redirect');
            return;
        }

        $activation_url = muauth_get_activation_url();

        if ( !empty( $_REQUEST ) ) {
            $args = array_map('urlencode', $_REQUEST);

            if ( isset($args['key']) ) {
                // little bit of rename
                $args['code'] = $args['key'];
                unset( $args['key'] );
            }
        } else {
            $args = array();
        }

        $args['action'] = 'activate';

        /* pluggable: filter to include or exclude queries to attach to URL */
        $args = apply_filters( 'muauth_pre_dispatch_activation_redirect_request', $args, $activation_url );

        if ( $args ) {
            $activation_url = add_query_arg($args, $activation_url);
        }

        $activation_url = apply_filters( 'muauth_pre_dispatch_activation_redirect_url', $activation_url );
        // trigger hook
        do_action('muauth_pre_redirect_to_auth', 'activation', $activation_url);
        do_action('muauth_pre_redirect_activation_to_auth', $activation_url);

        return muauth_redirect( $activation_url, true );
    }

    public static function dispatchLogoutRedirect()
    {
        if ( !muauth_is_component_active('logout') ) {
            do_action('muauth_pre_dispatch_inactive_component_redirect', 'logout');
            do_action('muauth_pre_dispatch_inactive_logout_component_redirect');
            return;
        }

        $logout_url = muauth_get_logout_url();

        if ( !empty( $_REQUEST ) ) {
            $args = array_map('urlencode', $_REQUEST);
        } else {
            $args = array();
        }

        unset($args['action']);

        /* pluggable: filter to include or exclude queries to attach to URL */
        $args = apply_filters( 'muauth_pre_dispatch_logout_redirect_request', $args, $logout_url );

        if ( $args ) {
            $logout_url = add_query_arg($args, $logout_url);
        }

        $logout_url = apply_filters( 'muauth_pre_dispatch_logout_redirect_url', $logout_url );
        
        // trigger hook
        do_action('muauth_pre_redirect_to_auth', 'logout', $logout_url);
        do_action('muauth_pre_redirect_logout_to_auth', $logout_url);

        return muauth_redirect( $logout_url, true );
    }

    public static function removeAuthBlogFromDisabledSites($ids)
    {
        global $muauth;

        if ( !empty( $muauth->auth_blog_id ) ) {
            $i = array_search($muauth->auth_blog_id, $ids);
            if ( false !== $i ) {
                unset( $ids[$i] );
            }
        }

        return $ids;
    }

    public static function redirectLoginUnknownAction($action)
    {
        $login_url = muauth_get_login_url();

        if ( trim($action) ) {
            $login_url = add_query_arg('action', $action, $login_url);
        }

        return muauth_redirect($login_url);
    }

    public static function contentFilters()
    {
        /* filter title in modern themes that omit the use of wp_title */
        add_filter( 'pre_get_document_title', 'muauth_pre_get_document_title');
        /* filter title in themes that use wp_title */
        add_filter( 'wp_title', 'muauth_pre_get_document_title');
        /* filter auth page title */
        add_filter( 'the_title', 'muauth_get_dynamic_page_title', 10, 2);
        /* filter auth page title */
        add_filter( 'the_content', 'muauth_get_dynamic_page_content');
        /* parse redirect notices */
        self::parseRediectNotices();
    }

    public static function parseLoginTemplate()
    {
        return Plugin::loadTemplate( 'auth/login.php' );
    }

    public static function parseTempalteErrors()
    {
        // exclude errors by codes to parse errors below form fields
        $exclude_codes = array();
        // get current component name
        $component = muauth_get_current_component();

        switch ( $component ) {
            case 'login':
                $exclude_codes = array('login', 'password');
                // logged out notice (from redirect)
                if ( isset( $_GET['loggedout'] ) ) {
                    muauth_add_error( 'loggedout', __('You are logged out successfully!', MUAUTH_DOMAIN), 'success' );
                }
                break;

            case 'lost-password':
                $exclude_codes = array('login','code','password1','password2');
                break;

            case 'activation':
                $exclude_codes = array('login','code');
                break;

            case 'register':
                $exclude_codes = array('username','email','sitename','sitetitle');
                break;
        }

        // pluggable
        $exclude_codes = apply_filters( 'muauth_parse_template_errors_exclude_codes', $exclude_codes, $component );

        // print'em
        muauth_template_errors( $exclude_codes );
    }

    public static function parseLoginRequestQuery()
    {
        if ( isset( $_REQUEST['redirect_to'] ) ) {
            printf(
                '<input type="hidden" name="redirect_to" value="%s" />',
                esc_url( $_REQUEST['redirect_to'] )
            );
        }

        global $auth_site;

        printf(
            '<input type="hidden" name="auth_site" value="%d" />',
            $auth_site->blog_id
        );

        // ...and a little bit of nonce
        wp_nonce_field('muauth_nonce', 'muauth_nonce');
    }

    public static function validatePost()
    {
        if ( isset($_POST['current_component']) && !empty( $_POST['current_component'] ) ) {
            $component = esc_attr( $_POST['current_component'] );
            if ( muauth_is_component_active($component) ) {
                switch ( $component ) {
                    case 'login':
                        return self::validateLogin();
                        break;

                    case 'lost-password':
                        return self::validateLostPassword();
                        break;

                    case 'register':
                        /* see parseQueryHandleRegister method */
                        break;

                    case 'activation':
                        return self::validateActivation();
                        break;
                }
            } else {
                do_action( 'muauth_validate_post_component_inactive', $component );
            }
        } else {
            do_action( 'muauth_validate_post_no_post_component' );
        }

        do_action('muauth_validate_post');
    }

    public static function validateLogin()
    {
        if ( !isset($_REQUEST['submit']) )
            return;

        // trigger hook
        do_action('muauth_pre_validate_login');

        /** Use filter to verify data at first and add custom errors */
        if ( apply_filters('muauth_validate_login_early_bail', false) )
            return;

        if ( empty( $_POST ) ) {
            return muauth_add_error( 'request_method', __('Error: Only POST requests are honored!', MUAUTH_DOMAIN), 'error' );
        }

        if ( !MUAUTH::verifyNonce() ) {
            return muauth_add_error( 'bad_request', __('Error: Bad authentication!', MUAUTH_DOMAIN), 'error' );
        }

        if ( !isset( $_POST['auth_site'] ) || !intval( $_POST['auth_site'] ) ) {
            return muauth_add_error( 'bad_request', __('Error: Something went wrong, we couldn\'t find the authentication site.', MUAUTH_DOMAIN), 'error' );
        }

        ###

        $auth_site_id = (int) $_POST['auth_site'];

        if ( muauth_is_site_disabled( $auth_site_id ) ) {
            return muauth_add_error( 'bad_request', __('Error: authentication is disabled for this site.', MUAUTH_DOMAIN), 'error' );
        }

        if ( !isset( $_POST['login'] ) || !( trim($_POST['login']) ) ) {
            muauth_add_error( 'login', __('Error: You must enter a username or email in order to login!', MUAUTH_DOMAIN), 'error' );
        } else {
            $login = sanitize_text_field($_POST['login']);
        }

        if ( !isset( $_POST['password'] ) || !( trim($_POST['password']) ) ) {
            muauth_add_error( 'password', __('Error: You must enter a password in order to login!', MUAUTH_DOMAIN), 'error' );
        } else {
            $password = ($_POST['password']);
        }

        do_action('muauth_pre_validate_login_auth');

        if ( !isset( $login ) || !isset( $password ) || muauth_has_errors() )
            return;

        $is_email = (bool) strpos($login, '@');

        if ( $is_email ) {
            $user = get_user_by( 'email', $login );
        } else {
            $user = get_user_by( 'slug', $login );
        }

        if ( !$user || !intval( $user->ID ) || empty( $user->data->user_pass ) ) {
            return muauth_add_error( 'login', __('Error: We couldn\'t find a user with this login!', MUAUTH_DOMAIN), 'error' );
        }

        if ( !wp_check_password( $password, $user->data->user_pass, $user->ID) ) {
            return muauth_add_error( 'password', sprintf(
                __('Error: Incorrect password, please try again or <a href=%s>reset</a> your password.', MUAUTH_DOMAIN),
                add_query_arg(array('login' => $login), muauth_get_lostpassword_url())
            ), 'error' );
        }

        $remember = isset( $_POST['remember'] );
        $redirect_to = isset( $_POST['redirect_to'] ) ? esc_url($_POST['redirect_to']) : null;

        /**
          * Hook into login validation
          * Use to validate custom data and add/remove custom errors
          * example use, validating your custom captcha field
          */
        do_action( 'muauth_validate_login', $user, $auth_site_id, $remember, $redirect_to );

        if ( !muauth_has_errors() ) {
            // trigger login

            if ( User::loginUser( $user->ID, $remember ) ) {
                /* trigger hook */
                do_action( 'muauth_login_success', $user, $auth_site_id );

                if ( !$redirect_to ) {
                    switch_to_blog($auth_site_id);
                    $redirect_to = get_bloginfo('url');
                    restore_current_blog();
                }

                // redirect
                return muauth_redirect( $redirect_to, true );
            } else {
                muauth_add_error( 'login_fail', __('Error: Something went, login was not successfull.', MUAUTH_DOMAIN), 'error' );
                // hook
                do_action( 'muauth_validate_login_fail', $user, $auth_site_id, $remember, $redirect_to );
            }
        }

        /* triggers when custom errors are added */
        do_action('muauth_post_validate_login');
    }

    public static function parseSimpleLoginTemplate($redirect_to='', $blog_id=0, $unique_id='login_form')
    {
        if ( !$blog_id ) {
            global $muauth;
            $blog_id = $muauth->auth_blog_id;
        }

        global $muauth_login_form;
        
        $muauth_login_form = apply_filters( 'muauth_simple_login_form_args', array(
            'redirect_to' => $redirect_to,
            'unique_id' => $unique_id,
            'blog_id' => $blog_id
        ));

        return Plugin::loadTemplate( 'auth/login-form.php' );
    }

    public static function parseSimpleLoginRequestQuery()
    {
        global $muauth_login_form;

        if ( isset( $muauth_login_form['redirect_to'] ) ) {
            printf(
                '<input type="hidden" name="redirect_to" value="%s" />',
                esc_url( $muauth_login_form['redirect_to'] )
            );
        }

        if ( isset( $muauth_login_form['blog_id'] ) ) {
            printf(
                '<input type="hidden" name="auth_site" value="%d" />',
                $muauth_login_form['blog_id']
            );
        }

        // ...and a little bit of nonce
        wp_nonce_field('muauth_nonce', 'muauth_nonce');
    }

    public static function parseCurrentComponentField()
    {
        printf(
            '<input type="hidden" name="current_component" value="%s" />',
            muauth_get_current_component()
        );

        print(
            '<input type="hidden" name="_doing_muauth" value="1" />'
        );
    }

    public static function parseSimpleLoginCurrentComponentField()
    {
        print(
            '<input type="hidden" name="current_component" value="login" />'
        );

        print(
            '<input type="hidden" name="_doing_muauth" value="1" />'
        );
    }

    public static function parseLostPasswordTemplate()
    {
        return Plugin::loadTemplate( 'auth/lost-password.php' );
    }

    public static function validateLostPassword()
    {
        // trigger hook
        do_action('muauth_pre_validate_lostpassword');

        /** Use filter to verify data at first and add custom errors */
        if ( apply_filters('muauth_validate_lostpassword_early_bail', false) )
            return;

        global $muauth_stage;
        $muauth_stage = 1; // defaults to 1

        if ( empty( $_POST ) ) {
            return muauth_add_error( 'request_method', __('Error: Only POST requests are honored!', MUAUTH_DOMAIN), 'error' );
        }

        if ( !MUAUTH::verifyNonce() ) {
            return muauth_add_error( 'bad_request', __('Error: Bad authentication!', MUAUTH_DOMAIN), 'error' );
        }

        if ( !isset( $_POST['auth_site'] ) || !intval( $_POST['auth_site'] ) ) {
            return muauth_add_error( 'bad_request', __('Error: Something went wrong, we couldn\'t find the authentication site.', MUAUTH_DOMAIN), 'error' );
        }

        $auth_site_id = (int) $_POST['auth_site'];

        if ( muauth_is_site_disabled( $auth_site_id ) ) {
            return muauth_add_error( 'bad_request', __('Error: authentication is disabled for this site.', MUAUTH_DOMAIN), 'error' );
        }

        if ( isset( $_POST['stage'] ) && intval( $_POST['stage'] ) ) {
            $muauth_stage = (int) $_POST['stage'];
        } else if ( isset( $_GET['stage'] ) && intval( $_GET['stage'] ) ) {
            $muauth_stage = (int) $_GET['stage'];
        }

        do_action( 'muauth_pre_validate_lostpassword_auth' );

        $muauth_stage = apply_filters('muauth_lost_password_stage', $muauth_stage, $auth_site_id);

        if ( 1 === $muauth_stage ) {

            if ( !isset( $_POST['login'] ) || !( trim($_POST['login']) ) ) {
                return muauth_add_error( 'login', __('Error: You must enter a username or email in order to rest your password!', MUAUTH_DOMAIN), 'error' );
            }

            $login = sanitize_user($_POST['login']);
            $is_email = (bool) strpos($login, '@');

            if ( $is_email ) {
                $user = get_user_by( 'email', $login );
            } else {
                $user = get_user_by( 'slug', $login );
            }

            if ( !$user || !intval( $user->ID ) || empty( $user->data->user_pass ) ) {
                return muauth_add_error( 'login', __('Error: We couldn\'t find a user with this login!', MUAUTH_DOMAIN), 'error' );
            }

            // trigger hook
            do_action( 'muauth_validate_lostpassword_pre_sendmail', $user, $login, $auth_site_id );

            if ( !muauth_has_errors() ) {

                // send out an email
                User::emailPasswordResetCode($user, $auth_site_id);

                $redirect_to = add_query_arg(array(
                    'sent' => 1,
                    'stage' => 2,
                    'login' => $login
                ), muauth_get_lostpassword_url(null, $auth_site_id));

                return muauth_redirect($redirect_to, true, 0)->withNotice(array(
                    'email_sent',
                    __('We have just sent you an email with a password-reset code to reset your password! Please check your inbox, and follow the instructions provided within the email.', MUAUTH_DOMAIN),
                    'success'
                ));
            }

        } else if ( 2 === $muauth_stage ) {

            if ( !isset( $_POST['login'] ) || !( trim($_POST['login']) ) ) {
                muauth_add_error( 'login', __('Error: You must enter a username or email in order to rest your password!', MUAUTH_DOMAIN), 'error' );
            } else {
                $login = sanitize_user($_POST['login']);
            }

            if ( !isset( $_POST['code'] ) || !( trim($_POST['code']) ) ) {
                muauth_add_error( 'code', __('Error: You must enter your password reset code!', MUAUTH_DOMAIN), 'error' );
            } else {
                $code = sanitize_text_field($_POST['code']);
            }

            if ( !isset( $login ) || !isset( $code ) )
                return;

            /** trigger hook - use to validate data and register custom errors with muauth_add_error **/
            do_action( 'muauth_validate_lostpassword_before_get_redirect_stage_2', $login, $code );

            // check for any errors
            if ( muauth_has_errors() )
                return;

            /**
              * simple redirect to next step with GET login and code
              * next step will verify if user exists, code is valid
              * otherwise it will redirect back to step 1 with a
              * simple notice on the screen
              */

            return muauth_redirect(add_query_arg(array(
                'action' => 'rp',
                'code' => $code,
                'login' => $login
            ), muauth_get_lostpassword_url('', $auth_site_id)));

        } else if ( 3 === $muauth_stage ) {

            // validate user and key
            if ( isset( $_POST['login'] ) && !empty( $_POST['login'] ) ) {
                $login = sanitize_user( $_POST['login'] );
            }

            if ( isset( $_POST['code'] ) && !empty( $_POST['code'] ) ) {
                $reset_key = sanitize_text_field( $_POST['code'] );
            }

            if ( (!isset($login) || empty($login)) || (!isset($reset_key) || empty($reset_key)) ) {
                return muauth_redirect(muauth_get_lostpassword_url('', $auth_site_id), true, 0)->withNotice(array(
                    'wrong_creds',
                    __('Error: Missing or wrong password-reset credentials. Please try again.', MUAUTH_DOMAIN),
                    'error'
                ));
            }
            
            $user = get_user_by( strpos($login, '@') ? 'email' : 'slug', $login );

            /** making sure users reset passwords for their own **/
            if ( !User::verifyUserResetKey( $reset_key, $user ) ) {
                return muauth_redirect(muauth_get_lostpassword_url('', $auth_site_id), true, 0)->withNotice(array(
                    'wrong_creds',
                    __('Error: Missing or wrong password-reset credentials. Please try again.', MUAUTH_DOMAIN),
                    'error'
                ));
            }

            if ( !isset( $_POST['password1'] ) || !( trim($_POST['password1']) ) ) {
                muauth_add_error( 'password1', __('Error: Please enter a new password!', MUAUTH_DOMAIN), 'error' );
            } else {
                $password1 = sanitize_text_field($_POST['password1']);
            }

            if ( !isset( $_POST['password2'] ) || !( trim($_POST['password2']) ) ) {
                muauth_add_error( 'password2', __('Error: You must confirm your new password!', MUAUTH_DOMAIN), 'error' );
            } else {
                $password2 = sanitize_text_field($_POST['password2']);
            }

            if ( !isset( $password1 ) || !isset( $password2 ) )
                return;

            if ( !($password1 === $password2) ) {
                // add empty errors to highlight password fields as alert
                muauth_add_error( 'password1', '', 'error' );
                muauth_add_error( 'password2', '', 'error' );
                // return password-mismatch notice
                return muauth_add_error( 'password_mismatch', __('Error: Password Mismatch!', MUAUTH_DOMAIN), 'error' );                
            }

            /** trigger custom hook to validate passwords and/or add custom errors **/
            do_action( 'muauth_validate_lostpassword_pre_store', $password2, $user );

            if ( muauth_has_errors() )
                return;

            if ( User::updatePassword( $user, $password2 ) ) {
                return muauth_redirect(add_query_arg(array(
                    'login' => $login
                ), muauth_get_login_url('', $auth_site)), true, 0)->withNotice(array(
                    'password_updated',
                    __('Your password was updated successfully! Please login below.', MUAUTH_DOMAIN),
                    'success'
                ));
            } else { /* something went wrong, password reset failed */
                // trigge hook
                do_action( 'muauth_validate_password_reset_fail', $password2, $user );
                // break it to them gently
                return muauth_add_error( 'failed_pwd_reset', __('Error: Something went wrong and we could not update your password. Please try again or later, or reach out to the site administrators to address the issue.', MUAUTH_DOMAIN), 'error' );                
            }

        } else {
            do_action('muauth_validate_lostpassword_custom_stage', $muauth_stage, $auth_site_id);
            do_action("muauth_validate_lostpassword_custom_stage_{$muauth_stage}", $auth_site_id);
        }

    }

    public static function parseLostPasswordRequestQuery()
    {
        global $auth_site, $muauth_stage, $muauth_login, $muauth_reset_key;

        if ( isset($auth_site->blog_id) && intval( $auth_site->blog_id ) ) {
            printf(
                '<input type="hidden" name="auth_site" value="%d" />',
                $auth_site->blog_id
            );
        }

        if ( isset($muauth_stage) ) {
            printf(
                '<input type="hidden" name="stage" value="%d" />',
                $muauth_stage
            );

            if ( 3 === $muauth_stage ) {
                if ( isset( $muauth_login ) ) {
                    printf(
                        '<input type="hidden" name="login" value="%s" />',
                        $muauth_login
                    );
                } else if ( isset( $_POST['login'] ) ) {
                    printf(
                        '<input type="hidden" name="login" value="%s" />',
                        sanitize_text_field($_POST['login'])
                    );
                }

                if ( isset( $muauth_reset_key ) ) {
                    printf(
                        '<input type="hidden" name="code" value="%s" />',
                        $muauth_reset_key
                    );
                } else if ( isset( $_POST['code'] ) ) {
                    printf(
                        '<input type="hidden" name="code" value="%s" />',
                        sanitize_text_field($_POST['code'])
                    );
                } else if ( isset( $_POST['key'] ) ) {
                    printf(
                        '<input type="hidden" name="code" value="%s" />',
                        sanitize_text_field($_POST['key'])
                    );
                }

                print(
                    '<input type="hidden" name="action" value="rp" />'
                );
            }

        }

        // ...and a little bit of nonce
        wp_nonce_field('muauth_nonce', 'muauth_nonce');
    }

    public static function lpCatchValidatePostNoPostComponent()
    {
        $action = isset($_GET['action']) ? esc_attr($_GET['action']) : null;

        switch ( strtolower($action) ) :

            case 'rp':

                if ( muauth_is_component_active('lost-password') ) :
                $key = isset( $_GET['code'] ) ? trim($_GET['code']) : (
                    isset($_GET['key']) ? trim($_GET['key']) : null
                );
                if ( $key && (isset( $_GET['login'] ) && trim($_GET['login'])) ) {
                    $login = sanitize_text_field($_GET['login']);
                    $is_email = (bool) strpos($login, '@');

                    if ( User::verifyUserResetKey( $key, get_user_by( $is_email ? 'email' : 'slug', $login ) ) ) {
                        if ( !muauth_has_errors() ) {
                            global $muauth_stage, $muauth_reset_key, $muauth_login;            
                            $muauth_stage = 3;
                            $muauth_login = $login;
                            $muauth_reset_key = sanitize_text_field($key);
                            # TODO: email upon pwd change success
                            return muauth_add_error(
                                'new_password',
                                __('Enter and confirm your new password below', MUAUTH_DOMAIN),
                                'success'
                            );
                        }
                    } else {
                        return muauth_redirect(muauth_current_url(0,1), true, 0)->withNotice(array(
                            'invalid_creds',
                            __('Sorry, we couldn\'t verify your credentials, please ensure the link/key is not expired and try again.', MUAUTH_DOMAIN),
                            'error'
                        ));
                    }
                }
                endif;

                break;

            case 'activate':

                if ( muauth_is_component_active('activation') ) :
                if ( isset( $_GET['code'] ) && trim($_GET['code']) ) {

                    $code = sanitize_text_field($_GET['code']);

                    do_action( 'muauth_validate_activation_GET_pre_activate', $code );

                    if ( !muauth_has_errors() ) {

                        $activation = wpmu_activate_signup( $code );

                        if ( is_wp_error( $activation ) ) {

                            do_action( 'muauth_validate_activation_GET_error', $activation );

                            $error = array(
                                (bool) ( $activation->get_error_code() ) ? $activation->get_error_code() : 'activation_error',
                                (bool) ( $activation->get_error_message() ) ? $activation->get_error_message() : __(
                                    '',
                                MUAUTH_DOMAIN),
                                'error'
                            );

                            return muauth_redirect( muauth_current_url(0,1), true, 0 )->withNotice( $error );

                        } else if ( is_array($activation) && isset( $activation['user_id'] ) ) {
                            
                            do_action( 'muauth_validate_activation_GET_success', $activation );

                            global $muauth;

                            $meta = isset($activation['meta']) ? $activation['meta'] : array();

                            if ( isset( $activation['blog_id'] ) && $activation['blog_id'] ) {
                                $blog_id = (int) $activation['blog_id'];
                                $newblog = true;
                            } else if ( isset($meta['add_to_blog']) && $meta['add_to_blog'] ) {
                                $blog_id = (int) $meta['add_to_blog'];
                            } else if ( !isset($muauth->is_auth_blog) || !$muauth->is_auth_blog ) {
                                $blog_id = $muauth->current_blog_id;
                            } else {
                                global $current_site;
                                $blog_id = $current_site->id;
                            }

                            switch_to_blog( $blog_id );
                            $blogname = get_bloginfo('name');
                            restore_current_blog();

                            $user_id = (int) $activation['user_id'];
                            $password = isset( $activation['password'] ) ? esc_attr($activation['password']) : null;
                            $user = get_userdata( $user_id );
                            $redirect_to = muauth_get_login_url(null, $blog_id);
                            
                            if ( isset( $user->user_login ) ) {
                                $redirect_to = add_query_arg( array( 'login' => $user->user_login ), $redirect_to );
                            }

                            /**
                              * Trigger hook
                              *
                              * used in core to email users their newly generated password
                              * @see activationWelcome methods
                              * @param int $user_id user ID
                              * @param str $password user raw password
                              * @param int $blog_id blog ID to authenticate to
                              * @param bool $newblog is new blog signed up with user
                              * @param str $redirect_to redirect-to URL
                              */

                            do_action( 'muauth_validate_activation_GET_success_pre_redirect', $user_id, $password, $blog_id, isset($newblog), $redirect_to );

                            $notice = sprintf(__('Your account at "%1$s" is now activated! Please login below with your provided credentials: <br/> <strong>Username:</strong> %2$s <br/> <strong>Password:</strong> %3$s', MUAUTH_DOMAIN), $blogname, isset($user->user_login) ? esc_attr($user->user_login) : null, $password);

                            if ( !muauth_has_errors() ) {
                                return muauth_redirect($redirect_to, 1, 0)->withNotice(array('activated', $notice, 'success'));
                            }
                        }

                    }

                }
                endif;
            
                break;

        endswitch;

        return add_action( "wp", array( self::instance(), "wpAuthParseVariablesFromGet" ), 11 );
    }

    public static function wpAuthParseVariablesFromGet()
    {
        switch (muauth_get_current_component()) {
            case 'lost-password':
            case 'activation':
                global $muauth_stage;
                if ( isset($_GET['stage']) && intval( $_GET['stage'] ) ) {
                    $muauth_stage = (int) $_GET['stage'];
                } else {
                    $muauth_stage = 1;                    
                }
                break;
            case 'register':
                global $muauth_signupfor, $muauth, $muauth_index;
                if ( !isset( $muauth_signupfor ) ) {
                    if ( is_user_logged_in() ) {
                        $muauth_signupfor = 'blog';
                    } else if ( 'all' === $muauth->registration ) {
                        $muauth_signupfor = 'blog';
                    } else {
                        $muauth_signupfor = 'user';
                    }
                }
                $muauth_index = true;
                break;
            case 'login':
                if ( isset($_GET['reauth']) ) {
                    wp_clear_auth_cookie();
                }
                break;
        }
    }

    public static function parseRediectNotices()
    {
        // parse templates notices from redirects
        Redirect::parseNotices( 'muauth_errors', 'muauth_add_error' );
    }

    public static function handleLoggedInComponent()
    {
        global $auth_site;
        switch ( muauth_get_current_component() ) {
            case 'login':
                if ( isset( $_REQUEST['redirect_to'] ) ) {
                    $redirect_to = esc_url( $_REQUEST['redirect_to'] );
                } else {
                    switch_to_blog( $auth_site->blog_id );
                    $redirect_to = home_url();
                    restore_current_blog();
                }
                return muauth_redirect($redirect_to); // safe redirect won't work for subdomain install
                break;

            case 'activation':
                switch_to_blog( $auth_site->blog_id );
                $redirect_to = site_url();
                restore_current_blog();
                return muauth_redirect($redirect_to, true);
                break;
        }
    }

    public static function parseActivationTemplate()
    {
        return Plugin::loadTemplate( 'auth/activation.php' );
    }

    public static function validateActivation()
    {
        // trigger hook 
        do_action('muauth_pre_validate_activation');

        /** Use filter to verify data at first and add custom errors */
        if ( apply_filters('muauth_validate_activation_early_bail', false) )
            return;

        global $muauth_stage;
        $muauth_stage = 1; // defaults to 1

        if ( empty( $_POST ) ) {
            return muauth_add_error( 'request_method', __('Error: Only POST requests are honored!', MUAUTH_DOMAIN), 'error' );
        }

        if ( !MUAUTH::verifyNonce() ) {
            return muauth_add_error( 'bad_request', __('Error: Bad authentication!', MUAUTH_DOMAIN), 'error' );
        }

        if ( !isset( $_POST['auth_site'] ) || !intval( $_POST['auth_site'] ) ) {
            return muauth_add_error( 'bad_request', __('Error: Something went wrong, we couldn\'t find the authentication site.', MUAUTH_DOMAIN), 'error' );
        }

        $auth_site_id = (int) $_POST['auth_site'];

        if ( muauth_is_site_disabled( $auth_site_id ) ) {
            return muauth_add_error( 'bad_request', __('Error: authentication is disabled for this site.', MUAUTH_DOMAIN), 'error' );
        }

        if ( isset( $_POST['stage'] ) && intval( $_POST['stage'] ) ) {
            $muauth_stage = (int) $_POST['stage'];
        } else if ( isset( $_GET['stage'] ) && intval( $_GET['stage'] ) ) {
            $muauth_stage = (int) $_GET['stage'];
        }

        do_action( 'muauth_pre_validate_activation_auth' );

        $muauth_stage = apply_filters('muauth_activation_stage', $muauth_stage, $auth_site_id);

        if ( 1 === $muauth_stage ) {
            if ( !isset( $_POST['login'] ) || !( trim($_POST['login']) ) ) {
                muauth_add_error( 'login', __('Error: You must enter a username or email in order to request an activation link!', MUAUTH_DOMAIN), 'error' );
            } else {
                $login = sanitize_text_field($_POST['login']);
            }

            if ( !isset( $login ) )
                return;

            do_action( 'muauth_validate_activation_1_before_check_redirect', $login );

            if ( muauth_has_errors() )
                return;

            $user = get_user_by(( strpos($login, '@') ? 'email' : 'slug' ), $login);

            if ( $user && $user->ID ) {

                // trigger hook
                do_action( 'muauth_validate_activation_1_before_redirect_active', $login );

                if ( muauth_has_errors() )
                    return;
                
                return muauth_redirect(add_query_arg(array('login'=>$login), muauth_get_login_url('',$auth_site_id)), 1, 0)->withNotice(array(
                    'already_active',
                    __('This user is already active, please login below or reset your password.', MUAUTH_DOMAIN),
                    'notice'
                ));
            }

            do_action( 'muauth_validate_activation_1_pre_check_is_pending', $login );

            if ( muauth_has_errors() )
                return;

            if ( !User::isPending( $login ) ) {
                do_action( 'muauth_validate_activation_1_404_pending', $login );

                return muauth_add_error(
                    '404_user',
                    __('Error: We couldn\'t find a user with the provided credentials!', MUAUTH_DOMAIN),
                    'error'
                );

                #troubleshooting
                #muauth_redirect(muauth_get_activation_url('',$auth_site_id))
            }

            // now that the user is there, let's send them their key:
            if ( User::resendActivation($login) ) {
                return muauth_redirect(muauth_get_activation_url('',$auth_site_id), 1, 0)->withNotice(array(
                    'resend_success',
                    __('We have just sent you an activation email! Please check your inbox, and follow the instructions provided within to activate your account.', MUAUTH_DOMAIN),
                    'success'
                ));
            } else {
                return muauth_redirect(muauth_get_activation_url('',$auth_site_id), 1, 0)->withNotice(array(
                    '404_user',
                    __('Error occured while sending you the activation email. Please try again or later.', MUAUTH_DOMAIN),
                    'error'
                ));                
            }

        } else if ( 2 === $muauth_stage ) {

            if ( !isset( $_POST['code'] ) || !( trim($_POST['code']) ) ) {
                muauth_add_error( 'code', __('Error: You must enter your activation code!', MUAUTH_DOMAIN), 'error' );
            } else {
                $code = sanitize_text_field($_POST['code']);
            }

            if ( !isset( $code ) )
                return;

            /** trigger hook **/
            do_action( 'muauth_validate_activation_1_before_redirect_key', $code );

            if ( muauth_has_errors() )
                return;

            return muauth_redirect(add_query_arg(array(
                'action' => 'activate',
                'code' => $code
            )), muauth_get_activation_url('',$auth_site_id));

        }
    }

    public static function parseActivationRequestQuery()
    {
        global $auth_site, $muauth_stage;

        if ( isset($auth_site->blog_id) && intval( $auth_site->blog_id ) ) {
            printf(
                '<input type="hidden" name="auth_site" value="%d" />',
                $auth_site->blog_id
            );
        }

        if ( isset($muauth_stage) ) {
            printf(
                '<input type="hidden" name="stage" value="%d" />',
                $muauth_stage
            );
        }

        // ...and a little bit of nonce
        wp_nonce_field('muauth_nonce', 'muauth_nonce');
    }

    public static function activationMeta($activation)
    {
        $blog_id = isset($activation['blog_id']) ? $activation['blog_id'] : 0;
        $user_id = isset($activation['user_id']) ? $activation['user_id'] : 0;

        if ( $user_id && isset($activation['meta']) && $activation['meta'] ) {
            if ( $blog_id ) {
                $usermeta = isset($activation['meta']['muauth_umeta']) ? $activation['meta']['muauth_umeta'] : array();
            } else {
                $usermeta = $activation['meta'];
            }

            // trigger hook
            do_action('muauth_activation_user_meta', $usermeta, $user_id, $blog_id);

            if ( $usermeta ) {
                foreach ( $usermeta as $key=>$value ) {
                    if ( !apply_filters( "muauth_activation_meta_handled_{$key}", false, $value, $user_id ) ) {
                        update_user_meta( $user_id, $key, $value );
                    }
                }
            }
        }
    }

    public static function activationWelcome($user_id, $password, $blog_id, $newblog)
    {
        $user = get_userdata( $user_id );

        if ( empty( $user->ID ) )
            return;

        if ( $newblog ) {
            $blog = get_blog_details($blog_id);
            return muauth_wpmu_welcome_notification($blog_id, $user_id, $password, $blog->blogname);
        } else {
            return muauth_wpmu_welcome_user_notification($user_id, $password);
        }
    }

    public static function parseRegisterTemplate()
    {
        // signup button toggle JS
        add_action('wp_footer', array(self::instance(), 'registerSignupForButtonTextToggler'));

        if ( is_user_logged_in() ) {
            global $muauth_stage;
            $muauth_stage = 2;
        }

        // load template
        return Plugin::loadTemplate( 'auth/register.php' );
    }

    public static function registerSignupForButtonTextToggler()
    {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($){
                var btn = $('.muauth-form input[type="submit"]').first()
                  , ipt = $('input[name="signupfor"]').first()
                  , tgl = $(document).on('change', 'input[name="signupfor"]', function(e){
                        if ( 'blog' === $(this).val() ) {
                            btn.attr('data-val', function(){
                                return btn.val();
                            }).val('<?php esc_attr_e( 'Next', MUAUTH_DOMAIN ); ?>');
                        } else {
                            btn.val(function(){
                                return btn.attr('data-val') || btn.val();
                            });
                        }
                        e.preventDefault();
                    });
                'hidden' !== ipt.prop('type').toLowerCase() && ipt.change();
            });
        </script>
        <?php
    }

    public static function validateRegister()
    {
        if ( !isset( $_REQUEST['submit'] ) )
            return;

        // trigger hook
        do_action('muauth_pre_validate_registration');

        /** Use filter to verify data at first and add custom errors */
        if ( apply_filters('muauth_validate_registration_early_bail', false) )
            return;

        global $muauth_stage;
        $muauth_stage = 1; // defaults to 1

        if ( empty( $_POST ) ) {
            return muauth_add_error( 'request_method', __('Error: Only POST requests are honored!', MUAUTH_DOMAIN), 'error' );
        }

        if ( !MUAUTH::verifyNonce() ) {
            return muauth_add_error( 'bad_request', __('Error: Bad authentication!', MUAUTH_DOMAIN), 'error' );
        }

        if ( !isset( $_POST['auth_site'] ) || !intval( $_POST['auth_site'] ) ) {
            return muauth_add_error( 'bad_request', __('Error: Something went wrong, we couldn\'t find the authentication site.', MUAUTH_DOMAIN), 'error' );
        }

        $auth_site_id = (int) $_POST['auth_site'];

        if ( muauth_is_site_disabled( $auth_site_id ) ) {
            return muauth_add_error( 'bad_request', __('Error: authentication is disabled for this site.', MUAUTH_DOMAIN), 'error' );
        }

        if ( isset( $_POST['stage'] ) && intval( $_POST['stage'] ) ) {
            $muauth_stage = (int) $_POST['stage'];
        }

        $muauth_stage = apply_filters('muauth_register_stage', $muauth_stage, $auth_site_id);

        global $muauth, $muauth_signupfor;

        if ( 'user' === $muauth->registration ) {
            $muauth_signupfor = 'user';
        } else if ( 'blog' === $muauth->registration ) {
            $muauth_signupfor = 'blog';
        } else if ( $muauth->registration_on ) {
            if ( isset( $_POST['signupfor'] ) && in_array($_POST['signupfor'], array('user','blog')) ) {
                $muauth_signupfor = esc_attr( $_POST['signupfor'] );
            } else {
                $muauth_signupfor = 'blog';
            }
        }

        $muauth_signupfor = apply_filters( 'muauth_register_signupfor', $muauth_signupfor );
        
        if ( !is_user_logged_in() ) :

        if ( !isset( $_POST['username'] ) || !( trim($_POST['username']) ) ) {
            muauth_add_error( 'username', __('Error: You must enter a username!', MUAUTH_DOMAIN), 'error' );
        } else {
            $username = esc_attr($_POST['username']);
        }

        if ( !isset( $_POST['email'] ) || !( trim($_POST['email']) ) ) {
            muauth_add_error( 'email', __('Error: You must enter a valid email address!', MUAUTH_DOMAIN), 'error' );
        } else {
            $email = esc_attr($_POST['email']);
        }

        if ( !isset( $username ) || !isset( $email ) ) {
            $muauth_stage = 1;
            return do_action("muauth_validate_register_{$muauth_stage}_error_returned");
        }

        do_action( 'muauth_validate_registration_email_username_early', $email, $username );

        if ( muauth_has_errors() )
            return do_action("muauth_validate_register_{$muauth_stage}_error_returned");

        $user_validation = (object) wpmu_validate_user_signup($username, $email);
        $user_validation = apply_filters('muauth_validate_register_user_validation', $user_validation, $username, $email);
        $errors = $user_validation->errors;

        if ( !is_wp_error( $errors ) ) {
            do_action("muauth_validate_register_{$muauth_stage}_error_returned");
            $muauth_stage = 1;
            return muauth_add_error('error', __('Error occured, could not validate your credentials.'), 'error');
        }

        if ( $errors->get_error_codes() ) {
            foreach ( $errors->get_error_codes() as $code ) {
                if ( $errors->get_error_messages( $code ) ) {
                    $muauthCode = str_replace(array(
                        'user_name', 'user_email'
                    ), array(
                        'username',
                        'email'
                    ), $code);

                    foreach ( $errors->get_error_messages( $code ) as $message ) {
                        muauth_add_error($muauthCode, esc_attr($message), 'error');
                    }
                }
            }
            $muauth_stage = 1;
            return do_action("muauth_validate_register_{$muauth_stage}_error_returned");;
        }

        endif;

        if ( 1 === $muauth_stage ) {
            /** stage 1: user registeration for logouts (and optional blogs if settings allow) **/

            if ( 'user' === $muauth_signupfor ) {
                /** register username **/

                global $auth_site;

                $usermeta = array();

                if ( !is_main_site($auth_site->blog_id) ) {
                    $usermeta['add_to_blog'] = (int) $auth_site->blog_id;
                }

                // append a default role for the user
                $usermeta['new_role'] = 'subscriber';

                // user meta
                $usermeta = apply_filters('muauth_validate_register_pre_signup_username_usermeta', $usermeta, $user_validation);

                /* trigger hook */
                do_action( 'muauth_validate_register_pre_signup_username', $user_validation, $usermeta );

                if ( muauth_has_errors() )
                    return do_action("muauth_validate_register_{$muauth_stage}_error_returned");
            
                wpmu_signup_user(
                    $user_validation->user_name,
                    $user_validation->user_email,
                    $usermeta
                );

                /** 
                  * trigger hook
                  * user might not be inserted correctly as wpmu_signup_user
                  * only inserts with no catching result
                  *
                  * @param object $user_validation wpmu_validate_user_signup return
                  */

                do_action( 'muauth_post_signup_user', $user_validation, $usermeta );

                if ( muauth_has_errors() )
                    return do_action("muauth_validate_register_{$muauth_stage}_error_returned");

                /**
                  * Trigger hook
                  * used in core to check if user inserted correctly
                  * to signups then notify them via email and add
                  * screen notices
                  *
                  * @since 0.1
                  * @see postSignupUser method
                  * @param object $user_validation wpmu_validate_user_signup return
                  * @param array $usermeta user meta
                  */

                do_action( 'muauth_signup_user', $user_validation, $usermeta );
            
            } else if ( !is_user_logged_in() ) {
                /** register blog and username **/

                // sign request to next step
                $muauth_stage = 2;

                // automatically set index to true
                global $muauth_index;
                $muauth_index = true;
            } else {
                /**
                  * Register blog only
                  * this code will not be execute because register blog stage is 2
                  * and we're in stage 1 bloc
                  */
            }

        }

        else if ( 2 === $muauth_stage ) {
            /** final stage: site registeration (and user for logouts) **/

            global $muauth_index;

            $muauth_index = (bool) isset($_POST['index']);

            if ( !isset( $_POST['sitename'] ) || !( trim($_POST['sitename']) ) ) {
                muauth_add_error( 'sitename',(
                    is_subdomain_install() ? __('Error: Please enter a blog domain!', MUAUTH_DOMAIN) : __('Error: Please enter a blog name!', MUAUTH_DOMAIN)
                ), 'error' );
            } else {
                $sitename = sanitize_text_field($_POST['sitename']);
            }

            if ( !isset( $_POST['sitetitle'] ) || !( trim($_POST['sitetitle']) ) ) {
                muauth_add_error( 'sitetitle', __('Error: Please enter a title for this blog!', MUAUTH_DOMAIN), 'error' );
            } else {
                $sitetitle = sanitize_text_field($_POST['sitetitle']);
            }

            if ( !isset( $sitename ) || !isset( $sitetitle ) )
                return do_action("muauth_validate_register_{$muauth_stage}_error_returned");

            do_action( 'muauth_validate_registration_site_early', $sitename, $sitetitle );

            if ( muauth_has_errors() )
                return do_action("muauth_validate_register_{$muauth_stage}_error_returned");

            if ( is_user_logged_in() ) {
                global $current_user;
                $user = $current_user;
            } else {
                $user = sanitize_user( $_POST['username'] );
            }

            $blog_validation = (object) wpmu_validate_blog_signup($sitename, $sitetitle, $user);
            $blog_validation = apply_filters('muauth_validate_register_blog_validation', $blog_validation, $sitename, $sitetitle, $user);
            $errors = $blog_validation->errors;

            if ( !is_wp_error( $errors ) ) {
                do_action("muauth_validate_register_{$muauth_stage}_error_returned");
                return muauth_add_error('error', __('Error occured, could not validate your credentials.'), 'error');
            }

            if ( $errors->get_error_codes() ) {
                foreach ( $errors->get_error_codes() as $code ) {
                    if ( $errors->get_error_messages( $code ) ) {
                        $muauthCode = str_replace(array(
                            'blogname', 'blog_title'
                        ), array(
                            'sitename',
                            'sitetitle'
                        ), $code);

                        foreach ( $errors->get_error_messages( $code ) as $message ) {
                            muauth_add_error($muauthCode, esc_attr($message), 'error');
                        }
                    }
                }
                return do_action("muauth_validate_register_{$muauth_stage}_error_returned");
            }

            $blogmeta = apply_filters(
                'muauth_validate_register_blogmeta_pre',
                array('lang_id' => 1, 'public' => (int) $muauth_index),
                $blog_validation,
                $user
            );

            if ( is_object($user) && !empty( $user->ID ) ) {
                // new blog for current user

                /* trigger hook */
                do_action( 'muauth_validate_register_pre_create_another_blog', $blog_validation, $user, $blogmeta );

                if ( muauth_has_errors() )
                    return do_action("muauth_validate_register_{$muauth_stage}_error_returned");

                global $current_site;

                $blog_id = wpmu_create_blog(
                    $blog_validation->domain,
                    $blog_validation->path,
                    $blog_validation->blog_title,
                    $user->ID,
                    $blogmeta,
                    $current_site->id
                );

                if ( is_wp_error( $blog_id ) ) {
                    // parse errors
                    if ( $blog_id->get_error_codes() ) {
                        foreach ( $blog_id->get_error_codes() as $code ) {
                            if ( $blog_id->get_error_messages( $code ) ) {
                                foreach ( $blog_id->get_error_messages( $code ) as $message ) {
                                    muauth_add_error($code, esc_attr($message), 'error');
                                }
                            }
                        }
                    }
                    // not successful, trigger hook and bail
                    do_action( 'muauth_create_blog_fail', $blog_validation, $blogmeta, $blog_id );
                    return do_action("muauth_validate_register_{$muauth_stage}_error_returned");
                }

                /** 
                  * trigger hook
                  *
                  * @param object $blog_id newly created blog ID
                  * @param object $blog_validation validated blog data returned from wpmu_validate_blog_signup
                  */

                do_action( 'muauth_post_create_blog', $blog_id, $blog_validation );

                if ( muauth_has_errors() )
                    return do_action("muauth_validate_register_{$muauth_stage}_error_returned");

                /**
                  * Trigger hook
                  * used in core to redirect user and add success notice
                  * of the new blog created
                  *
                  * @since 0.1
                  * @see postCreateBlog method
                  * @param object $blog_id newly created blog ID
                  * @param object $blog_validation validated blog data returned from wpmu_validate_blog_signup
                  */

                do_action( 'muauth_create_blog', $blog_id, $blog_validation );
            } else if ( isset($user_validation) && $user_validation ) {
                // new user and blog for $user|$email

                // user meta
                $usermeta = apply_filters('muauth_validate_register_pre_signup_blog_usermeta', array());

                /* trigger hook */
                do_action( 'muauth_validate_register_pre_signup_user_blog', $blog_validation, $user_validation, $blogmeta, $usermeta );

                if ( muauth_has_errors() )
                    return do_action("muauth_validate_register_{$muauth_stage}_error_returned");

                if ( $usermeta ) {
                    $_blogmeta = !empty($blogmeta) && is_array($blogmeta) ? $blogmeta : array();
                    $_blogmeta['muauth_umeta'] = $usermeta;
                } else {
                    $_blogmeta = $blogmeta;
                }

                wpmu_signup_blog(
                    $blog_validation->domain,
                    $blog_validation->path,
                    $blog_validation->blog_title,
                    $user_validation->user_name,
                    $user_validation->user_email,
                    $_blogmeta
                );

                /** 
                  * trigger hook
                  *
                  * @param object $blog_validation wpmu_validate_blog_signup return
                  * @param object $user_validation wpmu_validate_user_signup return
                  * @param array $blogmeta blog meta
                  * @param array $usermeta user meta
                  */

                do_action( 'muauth_post_signup_blog', $blog_validation, $user_validation, $blogmeta, $usermeta );

                if ( muauth_has_errors() )
                    return do_action("muauth_validate_register_{$muauth_stage}_error_returned");

                /**
                  * Trigger hook
                  * used in core to redirect user and add success notice
                  * of the new blog created
                  *
                  * @since 0.1
                  * @see postSignupBlog method
                  * @param object $blog_validation wpmu_validate_blog_signup return
                  * @param object $user_validation wpmu_validate_user_signup return
                  * @param array $blogmeta blog meta
                  * @param array $usermeta user meta
                  */

                do_action( 'muauth_signup_blog', $blog_validation, $user_validation, $blogmeta, $usermeta );
            }
        }
    }

    public static function parseRegisterRequestQuery()
    {
        global $muauth, $auth_site, $muauth_stage;

        if ( isset($auth_site->blog_id) && intval( $auth_site->blog_id ) ) {
            printf(
                '<input type="hidden" name="auth_site" value="%d" />',
                $auth_site->blog_id
            );
        }

        if ( is_user_logged_in() ) {
            print(
                '<input type="hidden" name="stage" value="2" />'
            );

            print(
                '<input type="hidden" name="signupfor" value="blog" />'
            );
        }

        else {
            if ( isset($muauth_stage) ) {
                printf(
                    '<input type="hidden" name="stage" value="%d" />',
                    $muauth_stage
                );

                if ( 2 === (int) $muauth_stage ) {
                    if ( isset( $_POST['username'] ) ) {
                        printf(
                            '<input type="hidden" name="username" value="%s" />',
                            esc_attr( $_POST['username'] )
                        );
                    }

                    if ( isset( $_POST['email'] ) ) {
                        printf(
                            '<input type="hidden" name="email" value="%s" />',
                            esc_attr( $_POST['email'] )
                        );
                    }
                }
            }

            if ( in_array($muauth->registration, array('user', 'blog')) ) {
                printf(
                    '<input type="hidden" name="signupfor" value="%s" />',
                    $muauth->registration
                );
            }
        }

        // ...and a little bit of nonce
        wp_nonce_field('muauth_nonce', 'muauth_nonce');
    }

    public static function parseQueryHandleRegister()
    {
        global $muauth, $auth_site;

        $logged_in = is_user_logged_in();

        if ( 'user' === $muauth->registration ) {
            // allow for logouts
            if ( $logged_in ) {
                if ( $muauth->current_blog_id !== (int) $auth_site->blog_id ) {
                    switch_to_blog( $auth_site->blog_id );
                }
                $home = site_url();
                if ( $muauth->current_blog_id !== (int) $auth_site->blog_id ) {
                    restore_current_blog();
                }
                return muauth_redirect($home, true);
            }
        } else if ( 'blog' === $muauth->registration ) {
            // force login
            if ( !$logged_in ) {
                return muauth_redirect(add_query_arg(array(
                    'redirect_to' => muauth_get_register_url()
                ), muauth_get_login_url('',$auth_site->blog_id)), 1, 0)->withNotice(array(
                    'must_login',
                    __( 'Sorry, registration is restricted to members only, please login first in order to signup for a new blog!', MUAUTH_DOMAIN ),
                    'notice'
                ));
            }
        } else if ( $muauth->registration_on ) {
            // pass to all
        } else {
            // 404
            return muauth_trigger_404();
        }

        return self::validateRegister();
    }

    public static function filterRegisterComponentActive( $active, $component )
    { 
        if ( !is_network_admin() ) {
            global $muauth;

            if ( !isset( $muauth->registration_on ) || !$muauth->registration_on )
                $active = false;
        }
        return $active;
    }

    public static function blogRegisterLoggedInHelpText()
    {
        return add_action( 'muauth_register_site_before_content', array( self::instance(), 'blogRegisterLoggedInParseHelpText' ));
    }

    public static function blogRegisterLoggedInParseHelpText()
    {
        global $current_user, $current_site;

        $blogs = get_blogs_of_user( $current_user->ID );

        ?>

        <h3><?php printf(__('Get another %s site in seconds', MUAUTH_DOMAIN), $current_site->site_name); ?></h3>

        <p><?php printf(__('Welcome back, %s. By filling out the form below, you can <strong>add another site to your account</strong>. There is no limit to the number of sites you can have, so create to your heart’s content, but write responsibly!', MUAUTH_DOMAIN), $current_user->display_name); ?></p>

        <p><?php _e('Sites you are already a member of:', MUAUTH_DOMAIN); ?></p>

        <ul>
        <?php foreach ( $blogs as $blog ) : $home = get_home_url($blog->userblog_id); ?>
            <li>
                <a href="<?php echo $home; ?>" title="<?php echo $blog->blogname; ?>"><?php echo $home; ?></a>
            </li>
        <?php endforeach; ?>
        </ul>

        <p><?php _e('If you’re not going to use a great site domain, leave it for a new user. Now have at it!', MUAUTH_DOMAIN); ?></p>
        <?php
    }

    public static function noRobots()
    {
        return add_action( 'wp_head', 'wp_no_robots' );
    }

    public static function postSignupUser($user_validation, $meta)
    {
        global $wpdb;

        $key = $wpdb->get_var($wpdb->prepare(
            "SELECT `activation_key` FROM $wpdb->signups WHERE `user_email` = %s AND `active` != 1",
            $user_validation->user_email
        ));

        $key = apply_filters('muauth_signup_user_key', $key, $user_validation);

        if ( !empty( $key ) ) {
            /** trigger hook **/
            do_action( 'muauth_signup_user_inserted', $user_validation->user_email, $user_validation->user_name, $key );

            /** notify the user of their account and activation credentials **/
            if ( apply_filters( 'muauth_signup_user_notify', true, $user_validation, $key ) ) {
                muauth_wpmu_signup_user_notification(
                    $user_validation->user_name,
                    $user_validation->user_email,
                    $key,
                    $meta
                );
            }

            /** localized by WordPress **/
            $notice = '<h2>' . sprintf( __( '%s is your new username' ), $user_validation->user_name) . '</h2>';
            $notice .= '<p>' . __( 'But, before you can start using your new username, <strong>you must activate it</strong>.' ) . '</p>';
            $notice .= '<p>' . sprintf( __( 'Check your inbox at %s and click the link given.' ), '<strong>' . $user_validation->user_email . '</strong>' ) . '</p>';
            $notice .= '<p>' . __( 'If you do not activate your username within two days, you will have to sign up again.' ) . '</p>';

            $notice = apply_filters( 'muauth_signup_user_success_notice', $notice );

            return muauth_redirect(add_query_arg(array(
                'success' => 1
            ), muauth_current_url(0,1)), 1, 0)->withNotice(array(
                'signup_success',
                $notice,
                'success'
            ));
        } else {

            // add screen error
            muauth_add_error('signup_fail', __('Error occured while creating your account. Please try again or later.', MUAUTH_DOMAIN), 'error');

            // catch
            do_action( 'muauth_signup_user_insert_fail', $user_validation->user_email, $user_validation->user_name );           
        }
    }

    public static function postCreateBlog($blog_id, $blog)
    {
        global $current_user;

        if ( $blog_id ) {
            switch_to_blog( $blog_id );
            $home_url  = home_url( '/' );
            restore_current_blog();
        } else {
            $home_url  = 'http://' . $blog->domain . $blog->path;
        }

        $login_url = muauth_get_login_url('', $blog_id);

        $site = sprintf( '<a href=%1$s>%2$s</a>',
            esc_url( $home_url ),
            $blog->blog_title
        );

        $notice = '<h2>' . sprintf(__( 'The site %s is yours.' ), $site) . '</h2>';
        $notice .= '<p>';
        $notice .= sprintf(
            __( '<a href=%1$s>%2$s</a> is your new site. <a href=%3$s>Log in</a> as &#8220;%4$s&#8221; using your existing password.', MUAUTH_DOMAIN ),
            esc_url( $home_url ),
            untrailingslashit( $blog->domain . $blog->path ),
            esc_url( $login_url ),
            $current_user->user_login
        );
        $notice .= '</p>';

        $notice = apply_filters( 'muauth_create_blog_success_notice', $notice );

        # TODO: maybe mail?

        return muauth_redirect(add_query_arg(array(
            'success' => 1
        ), muauth_current_url(0,1)), 1, 0)->withNotice(array(
            'signup_success',
            $notice,
            'success'
        ));
    }

    public static function postSignupBlog($blog_validation, $user_validation, $blogmeta, $usermeta)
    {
        global $wpdb;

        $key = $wpdb->get_var($wpdb->prepare(
            "SELECT `activation_key` FROM $wpdb->signups WHERE `user_email` = %s AND `domain` != '' AND `active` != 1",
            $user_validation->user_email
        ));

        $key = apply_filters('muauth_signup_blog_key', $key, $blog_validation, $user_validation);

        if ( !empty( $key ) || true ) {
            /** trigger hook **/
            do_action( 'muauth_signup_blog_inserted', $blog_validation, $user_validation, $key );

            /** notify the user of their account and activation credentials **/
            if ( apply_filters( 'muauth_signup_blog_notify', true, $blog_validation, $user_validation, $key ) ) {
                muauth_wpmu_signup_blog_notification(
                    $blog_validation->domain,
                    $blog_validation->path,
                    $blog_validation->blog_title,
                    $user_validation->user_name,
                    $user_validation->user_email,
                    $key,
                    $blogmeta
                );
            }

            /** localized by WordPress **/
            $notice = '<h2>' . sprintf( __( '%s is your new username' ), $user_validation->user_name) . '</h2>';
            $notice .= '<p>' . __( 'But, before you can start using your new username, <strong>you must activate it</strong>.' ) . '</p>';
            $notice .= '<p>' . sprintf( __( 'Check your inbox at %s and click the link given.' ), '<strong>' . $user_validation->user_email . '</strong>' ) . '</p>';
            $notice .= '<p>' . __( 'If you do not activate your username within two days, you will have to sign up again.' ) . '</p>';

            # TODO: maybe different notice when the user also registered a site?

            $notice = apply_filters( 'muauth_signup_blog_success_notice', $notice );

            return muauth_redirect(add_query_arg(array(
                'success' => 1
            ), muauth_current_url(0,1)), 1, 0)->withNotice(array(
                'signup_success',
                $notice,
                'success'
            ));
        } else {

            // add screen error
            muauth_add_error('signup_fail', __('Error occured while creating your account. Please try again or later.', MUAUTH_DOMAIN), 'error');

            // catch
            do_action( 'muauth_signup_user_insert_fail', $user_validation->user_email, $user_validation->user_name );           
        }
    }

    public static function removeAuthDomainAsHost($blog)
    {
        if ( isset($blog->domain) && $blog->domain ) {
            global $muauth;

            if ( isset($muauth->is_auth_blog) && $muauth->is_auth_blog ) {
                global $current_site, $domain;

                $blog->domain = str_replace(
                    $domain,
                    $current_site->domain,
                    $blog->domain
                );
            }
        }

        return $blog;
    }

    public static function registerUrl()
    {
        return muauth_get_register_url();
    }

    public static function loginUrl($login_url, $redirect, $force_reauth)
    {
        $login_url = muauth_get_login_url($redirect);

        if ( $force_reauth ) {
            $login_url = add_query_arg('reauth', '1', $login_url);
        }

        return $login_url;
    }

    public static function logoutUrl($logout_url, $redirect)
    {
        return muauth_get_logout_url($redirect, null, true);
    }

    public static function parseQueryHandleLogout()
    {
        if ( !is_user_logged_in() ) {
            if ( isset( $_REQUEST['redirect_to'] ) ) {
                $redirect_to = esc_url( $_REQUEST['redirect_to'] );
            } else if ( isset($auth_site->blog_id) && $auth_site->blog_id ) {
                $redirect_to = add_query_arg('loggedout', 'true', get_home_url($auth_site->blog_id));
            } else {
                $redirect_to = add_query_arg('loggedout', 'true', site_url());
            }

            // call redirect
            return muauth_redirect(apply_filters('muauth_pre_logout_redirect_to', $redirect_to));
        }

        if ( !MUAUTH::verifyNonce('_wpnonce', 'log-out') && apply_filters('muauth_force_nonce_on_logout', true) ) {
            return muauth_trigger_404();            
        }

        do_action('muauth_pre_logout');

        if ( muauth_has_errors() )
            return;

        global $auth_site;

        if ( isset( $_REQUEST['redirect_to'] ) ) {
            $redirect_to = esc_url( $_REQUEST['redirect_to'] );
        } else if ( isset($auth_site->blog_id) && $auth_site->blog_id ) {
            $redirect_to = add_query_arg('loggedout', 'true', get_home_url($auth_site->blog_id));
        } else {
            $redirect_to = add_query_arg('loggedout', 'true', site_url());
        }

        $redirect_to = apply_filters('muauth_pre_logout_redirect_to', $redirect_to);

        // logout user
        User::logout();

        // call redirect
        return muauth_redirect($redirect_to);
    }
}