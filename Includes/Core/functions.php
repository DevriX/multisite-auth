<?php

// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);

function muauth_get_auth_page( $blog_id=0, $return_id=null ) {
    if ( !$blog_id ) {
        global $muauth;
        $blog_id = $muauth->auth_blog_id;
    }

    if ( !$blog_id ) {
        $page = array();
    } else {
        switch_to_blog( $blog_id );
        $page_id = get_option( "muauth_auth_page", 0 );

        if ( $return_id ) {
            $page = (int) $page_id;
        } else if ( $page_id ) {
            $page = get_post( $page_id );
        } else {
            $page = null;
        }

        restore_current_blog();
    }

    return apply_filters( 'muauth_get_auth_page', $page, $blog_id, $return_id );
}

function muauth_auto_setup_auth_page( $blog_id ) {
    $page = muauth_get_auth_page( $blog_id );

    if ( empty($page->ID) || empty( $page->post_content ) ) {
        switch_to_blog( $blog_id );

        $page_id = wp_insert_post(array(
            'post_title' => __('Authentication', MUAUTH_DOMAIN),
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_content' => ''
        ));

        if ( $page_id ) {
            update_option( "muauth_auth_page", $page_id );
        } else {
            delete_option( "muauth_auth_page" );
        }

        restore_current_blog();
        // trigger hook
        do_action( 'muauth_post_update_auth_page', $page_id );
    }
}

function muauth_get_current_auth_site() {
    $var = get_query_var( 'muauth_site' );

    if ( !trim($var) ) {
        $site = null;
    } else if ( is_numeric($var) ) {
        $site = get_blog_details( (int) $var );
    } else {
        $id = get_id_from_blogname( $var );
        if ( $id ) {
            $site = get_blog_details( $id );
        } else {
            $site = null;
        }
    }

    return apply_filters( 'muauth_get_current_auth_site', $site );
}

function muauth_get_current_component($validate=false) {
    $var = get_query_var( 'muauth_component' );

    if ( $validate ) { // this is already handled by the rewrite but just making sure
        $valid = false;
        global $muauth_raw_components;
        if ( $muauth_raw_components ) {
            foreach ( $muauth_raw_components as $c=>$d ) {
                if ( $c === $var ) {
                    $valid = true;
                    break;
                }
            }
        }
        if ( !$valid ) {
            $var = null;
        }
    }

    return apply_filters( 'muauth_get_current_component', $var, $validate );
}

function muauth_is_component_active( $component ) {
    global $muauth;
    // check active components
    $is_active = !empty( $muauth->components ) && in_array($component, $muauth->components);
    // pluggable
    return apply_filters( 'muauth_is_component_active', $is_active, $component );
}

function muauth_trigger_404($nocache_headers=true) {
    global $wp_query;
    // trigger 404
    $wp_query->set_404();
    status_header( 404 );
    // no cache
    if ( $nocache_headers ) {
        nocache_headers();
    }
    // prevent redirect to identical object name
    remove_action( 'template_redirect', 'redirect_canonical' ); 
}

function muauth_get_dynamic_page_title( $title='', $post_id=0, $document_title=false ) {
    $raw_title = $title;

    if ( is_404() )
        return $title;

    global $auth_page;

    if ( empty( $auth_page->ID ) || !$post_id || $auth_page->ID !== $post_id )
        return $title;

    $component = muauth_get_current_component();

    if ( !$component ) {
        return $title;
    }

    global $muauth_raw_components;

    if ( !empty( $muauth_raw_components[$component] ) ) {
        $title = $muauth_raw_components[$component];
    }

    global $auth_site;

    if ( !empty( $auth_site->blogname ) ) {
        if ( !trim($title) ) {
            $title = __('Authentication', MUAUTH_DOMAIN);
        }
        $title = sprintf(_x('%1$s &rsaquo; %2$s', 'page title format', MUAUTH_DOMAIN), $auth_site->blogname, $title);
    }

    return apply_filters('muauth_get_dynamic_page_title', $title, $raw_title, $post_id, $document_title);
}

function muauth_pre_get_document_title( $title ) {
    return muauth_get_dynamic_page_title($title, get_the_ID(), true);
}

function muauth_get_dynamic_page_content( $content='' ) {
    if ( is_404() )
        return $content;

    $post_id = get_the_ID();

    global $auth_page;

    if ( empty( $auth_page->ID ) || !$post_id || $auth_page->ID !== $post_id )
        return $content;

    $component = muauth_get_current_component();

    if ( $component ) {
        ob_start();
        // trigger hook to parse content
        do_action( "muauth_parse_component_content", $component );
        do_action( "muauth_parse_{$component}_content" );

        $content = ob_get_clean();
    }

    return $content;
}

function muauth_get_disabled_sites_ids($validate=null) {
    if ( $validate ) {
        global $muauth_disabled_sites_ids_validated;
        if ( isset( $muauth_disabled_sites_ids_validated ) )
            return $muauth_disabled_sites_ids_validated;
    } else {
        global $muauth_disabled_sites_ids;
        if ( isset( $muauth_disabled_sites_ids ) )
            return $muauth_disabled_sites_ids;
    }

    $ids = get_site_option( 'muauth_disabled_sites', array() );

    if ( $ids && !is_array($ids) ) {
        $ids = preg_split('/\s+/', $ids  );
        $ids = array_map('trim', $ids);
        $ids = array_filter($ids, 'trim');
        $ids = array_map('intval', $ids);
        if ( $ids && $validate ) {
            $ids = array_filter($ids, 'muauth_get_disabled_sites_ids_validate');
        }
    }

    if ( $validate ) {
        $muauth_disabled_sites_ids_validated = $ids;
    } else{
        $muauth_disabled_sites_ids = $ids;
    }

    return apply_filters('muauth_get_disabled_sites_ids', $ids, $validate);  
}

function muauth_set_disabled_sites_ids( $ids_str_or_array, $skip_existing=false ) {
    if ( empty( $ids_str_or_array ) ) {
        if ( delete_site_option( 'muauth_disabled_sites' ) ) {
            unset( $GLOBALS['muauth_disabled_sites_ids'] );
            unset( $GLOBALS['muauth_disabled_sites_ids_validated'] );
            return;
        }
    }

    if ( !$skip_existing )
        $ids = muauth_get_disabled_sites_ids(1);

    if ( is_string( $ids_str_or_array ) ) {
        $new_ids = preg_split('/\s+/', $ids_str_or_array  );
        $new_ids = array_map('trim', $new_ids);
        $new_ids = array_filter($new_ids, 'trim');
        $new_ids = array_map('intval', $new_ids);
        if ( $new_ids ) {
            $new_ids = array_filter($new_ids, 'muauth_get_disabled_sites_ids_validate');
        }
    } else if ( is_array($ids_str_or_array) ) {
        $new_ids = array_map('trim', $ids_str_or_array);
        $new_ids = array_filter($new_ids, 'trim');
        $new_ids = array_map('intval', $new_ids);
        if ( $new_ids ) {
            $new_ids = array_filter($new_ids, 'muauth_get_disabled_sites_ids_validate');
        }
    }

    if ( $new_ids ) {
        if ( !$skip_existing )
            $ids = array_merge( $ids, $new_ids );
        else
            $ids = $new_ids;

        if ( update_site_option( 'muauth_disabled_sites', implode(' ', $ids) ) ) {
            unset( $GLOBALS['muauth_disabled_sites_ids'] );
            unset( $GLOBALS['muauth_disabled_sites_ids_validated'] );
            return $ids;
        }   
    }
}

function muauth_get_disabled_sites_ids_validate( $blog_id ) {
    if ( !intval( $blog_id ) )
        return false;

    return apply_filters('muauth_get_disabled_sites_ids_validate', !empty(get_blog_details( $blog_id, 0 )->blog_id), $blog_id);
}

function muauth_is_site_disabled( $blog_id=0 ) {
    if ( !$blog_id ) {
        global $muauth;
        $blog_id = $muauth->current_blog_id;
    }

    if ( !$blog_id )
        return;

    $ids = muauth_get_disabled_sites_ids();
    $disabled = $ids && in_array($blog_id, $ids);

    return apply_filters( 'muauth_is_site_disabled', $disabled, $blog_id );
}

function muauth_get_current_login_action($param='action') {
    /**
      * To add more than a value, convert the key (action) value
      * to an array, and merge your values to it
      */
    $actions = apply_filters('muauth_get_current_login_action_actions', array(
        'login' => null,
        'register' => 'register',
        'lost-password' => array('lostpassword', 'rp'),
        'activate' => 'activate',
        'logout' => 'logout'
    ));

    foreach ( $actions as $query=>$value ) {
        if ( is_array($value) ) {
            foreach ( $value as $val ) {
                if ( isset( $_REQUEST[$param] ) ) {
                    if ( $val === $_REQUEST[$param] ) {
                        $queryString = $query;
                        break 2;
                    }
                } else if ( is_null($val) ) {
                    $queryString = $query;
                    break 2;
                }

            }
            continue;
        }

        if ( isset( $_REQUEST[$param] ) ) {
            if ( $value === $_REQUEST[$param] ) {
                $queryString = $query;
                break;
            }
        } else if ( is_null($value) ) {
            $queryString = $query;
            break;
        }
    }

    return apply_filters( 'muauth_get_current_login_action', isset($queryString) ? trim($queryString) : '', $param, $actions );
}

function muauth_get_login_url( $redirect_to='', $blog_id=0 ) {
    if ( $redirect_to ) {
        $redirect_to = esc_url( $redirect_to );
    }

    global $muauth;
    $url = '';

    global $muauth_raw_components;
    $components = array_keys(apply_filters('muauth_raw_components_pre_rewrite', $muauth_raw_components));

    $key = array_search('login', $components);

    if ( isset( $components[$key] ) ) {
        $slug = apply_filters('muauth_component_slug', $components[$key]);
    }

    if ( !empty( $slug ) ) {

        if ( !$muauth->is_auth_blog )
            switch_to_blog( $muauth->auth_blog_id );
        $url_sample = home_url("/%s/{$slug}/");
        if ( !$muauth->is_auth_blog )
            restore_current_blog();

        global $auth_site;

        if ( !$blog_id ) {
            global $current_site;
            $_blog_id = isset($auth_site->blog_id) ? $auth_site->blog_id : (!$muauth->is_auth_blog ? $muauth->current_blog_id : $current_site->id);
        } else {
            $_blog_id = $blog_id;
        }

        if ( $_blog_id ) {
            $url = sprintf($url_sample, apply_filters('muauth_pre_uri_append_auth_id', $_blog_id));

            if ( $redirect_to ) {
                $url = add_query_arg(array(
                    'redirect_to' => urlencode($redirect_to)
                ), $url);
            }
        }
    }

    return apply_filters( 'muauth_get_login_url', $url, $redirect_to, $blog_id );
}

function muauth_get_register_url( $deprecated='', $blog_id=0 ) {
    global $muauth;
    $url = '';

    global $muauth_raw_components;
    $components = array_keys(apply_filters('muauth_raw_components_pre_rewrite', $muauth_raw_components));

    $key = array_search('register', $components);

    if ( isset( $components[$key] ) ) {
        $slug = apply_filters('muauth_component_slug', $components[$key]);
    }

    if ( !empty( $slug ) ) {

        if ( !$muauth->is_auth_blog )
            switch_to_blog( $muauth->auth_blog_id );
        $url_sample = home_url("/%s/{$slug}/");
        if ( !$muauth->is_auth_blog )
            restore_current_blog();

        global $auth_site;

        if ( !$blog_id ) {
            global $current_site;
            $_blog_id = isset($auth_site->blog_id) ? $auth_site->blog_id : (!$muauth->is_auth_blog ? $muauth->current_blog_id : $current_site->id);
        } else {
            $_blog_id = $blog_id;
        }

        if ( $_blog_id ) {
            $url = sprintf($url_sample, apply_filters('muauth_pre_uri_append_auth_id', $_blog_id));
        }
    }

    return apply_filters( 'muauth_get_register_url', $url, $deprecated, $blog_id );
}

function muauth_get_lostpassword_url( $deprecated='', $blog_id=0 ) {
    global $muauth;
    $url = '';

    global $muauth_raw_components;
    $components = array_keys(apply_filters('muauth_raw_components_pre_rewrite', $muauth_raw_components));

    $key = array_search('lost-password', $components);

    if ( isset( $components[$key] ) ) {
        $slug = apply_filters('muauth_component_slug', $components[$key]);
    }

    if ( !empty( $slug ) ) {

        if ( !$muauth->is_auth_blog )
            switch_to_blog( $muauth->auth_blog_id );
        $url_sample = home_url("/%s/{$slug}/");
        if ( !$muauth->is_auth_blog )
            restore_current_blog();

        global $auth_site;

        if ( !$blog_id ) {
            global $current_site;
            $_blog_id = isset($auth_site->blog_id) ? $auth_site->blog_id : (!$muauth->is_auth_blog ? $muauth->current_blog_id : $current_site->id);
        } else {
            $_blog_id = $blog_id;
        }

        if ( $_blog_id ) {
            $url = sprintf($url_sample, apply_filters('muauth_pre_uri_append_auth_id', $_blog_id));
        }
    }

    return apply_filters( 'muauth_get_lostpassword_url', $url, $deprecated, $blog_id );
}

function muauth_get_activation_url( $deprecated='', $blog_id=0 ) {
    global $muauth;
    $url = '';

    global $muauth_raw_components;
    $components = array_keys(apply_filters('muauth_raw_components_pre_rewrite', $muauth_raw_components));

    $key = array_search('activation', $components);

    if ( isset( $components[$key] ) ) {
        $slug = apply_filters('muauth_component_slug', $components[$key]);
    }

    if ( !empty( $slug ) ) {

        if ( !$muauth->is_auth_blog )
            switch_to_blog( $muauth->auth_blog_id );
        $url_sample = home_url("/%s/{$slug}/");
        if ( !$muauth->is_auth_blog )
            restore_current_blog();

        global $auth_site;

        if ( !$blog_id ) {
            global $current_site;
            $_blog_id = isset($auth_site->blog_id) ? $auth_site->blog_id : (!$muauth->is_auth_blog ? $muauth->current_blog_id : $current_site->id);
        } else {
            $_blog_id = $blog_id;
        }

        if ( $_blog_id ) {
            $url = sprintf($url_sample, apply_filters('muauth_pre_uri_append_auth_id', $_blog_id));
        }
    }

    return apply_filters( 'muauth_get_activation_url', $url, $deprecated, $blog_id );
}

function muauth_get_logout_url($redirect_to='', $blog_id=0, $nonce=null) {
    if ( $redirect_to ) {
        $redirect_to = esc_url( $redirect_to );
    }

    global $muauth;
    $url = '';

    global $muauth_raw_components;
    $components = array_keys(apply_filters('muauth_raw_components_pre_rewrite', $muauth_raw_components));

    $key = array_search('logout', $components);

    if ( isset( $components[$key] ) ) {
        $slug = apply_filters('muauth_component_slug', $components[$key]);
    }

    if ( !empty( $slug ) ) {

        if ( !$muauth->is_auth_blog )
            switch_to_blog( $muauth->auth_blog_id );
        $url_sample = home_url("/%s/{$slug}/");
        if ( !$muauth->is_auth_blog )
            restore_current_blog();

        global $auth_site;

        if ( !$blog_id ) {
            global $current_site;
            $_blog_id = isset($auth_site->blog_id) ? $auth_site->blog_id : (!$muauth->is_auth_blog ? $muauth->current_blog_id : $current_site->id);
        } else {
            $_blog_id = $blog_id;
        }

        if ( $_blog_id ) {
            $url = sprintf($url_sample, apply_filters('muauth_pre_uri_append_auth_id', $_blog_id));

            if ( $redirect_to ) {
                $url = add_query_arg(array(
                    'redirect_to' => urlencode($redirect_to)
                ), $url);
            }
        }
    }

    if ( $nonce ) {
        $url = add_query_arg('_wpnonce', wp_create_nonce('log-out'), $url);
    }

    return apply_filters( 'muauth_get_logout_url', $url, $redirect_to, $blog_id, $nonce );
}

function muauth_redirect($uri, $safe=false, $do=true) {
    // trigger hook
    do_action( 'muauth_redirect_pre', $uri, $safe );

    if ( $do ) {
        return \MUAUTH\Includes\Core\Redirect::to($uri, $safe)->_do();
    } else {
        return \MUAUTH\Includes\Core\Redirect::to($uri, $safe);
    }
}

function muauth_registration_on() {
    global $muauth;
    return apply_filters('muauth_registration_on', (bool) $muauth->registration_on);
}

function muauth_add_error_session($list, $cookie_key='muauth_errors') {
    return \MUAUTH\Includes\Core\Redirect::addError($list, $cookie_key);
}

function muauth_append_auth_domain_as_safe($h){
    global $muauth;
    if ( $muauth && !empty($muauth->auth_blog_id) ) {
        $home = get_home_url($muauth->auth_blog_id);
        $home = preg_replace("(^https?://)", "", $home );
        $h[] = $home;
    }
    return $h;
}

/**
  * a hack fix to emails not being sent due to the
  * mail headers set by the notification function
  * fix: strip headers
  *
  * @since 0.1
  */
function muauth_wpmu_signup_blog_notification( $domain, $path, $title, $user_login, $user_email, $key, $meta = array() ) {
    if ( apply_filters('muauth_wpmu_signup_blog_notification_rollback', false) ) {
        return call_user_func_array('wpmu_signup_blog_notification', func_get_args());
    }
    /**
     * Filters whether to bypass the new site email notification.
     *
     * @since MU
     *
     * @param string|bool $domain     Site domain.
     * @param string      $path       Site path.
     * @param string      $title      Site title.
     * @param string      $user_login User login name.
     * @param string      $user_email User email address.
     * @param string      $key        Activation key created in wpmu_signup_blog().
     * @param array       $meta       By default, contains the requested privacy setting and lang_id.
     */
    if ( ! apply_filters( 'wpmu_signup_blog_notification', $domain, $path, $title, $user_login, $user_email, $key, $meta ) ) {
        return false;
    }

    // Send email with activation link.
    if ( !is_subdomain_install() || get_current_network_id() != 1 )
        $activate_url = network_site_url("wp-activate.php?key=$key");
    else
        $activate_url = "http://{$domain}{$path}wp-activate.php?key=$key"; // @todo use *_url() API

    $activate_url = esc_url($activate_url);
    $admin_email = get_site_option( 'admin_email' );
    if ( $admin_email == '' )
        $admin_email = 'support@' . $_SERVER['SERVER_NAME'];
    $from_name = get_site_option( 'site_name' ) == '' ? 'WordPress' : esc_html( get_site_option( 'site_name' ) );
    $message_headers = "From: \"{$from_name}\" <{$admin_email}>\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";

    $user = get_user_by( 'login', $user_login );
    $switched_locale = switch_to_locale( get_user_locale( $user ) );

    $message = sprintf(
        /**
         * Filters the message content of the new blog notification email.
         *
         * Content should be formatted for transmission via wp_mail().
         *
         * @since MU
         *
         * @param string $content    Content of the notification email.
         * @param string $domain     Site domain.
         * @param string $path       Site path.
         * @param string $title      Site title.
         * @param string $user_login User login name.
         * @param string $user_email User email address.
         * @param string $key        Activation key created in wpmu_signup_blog().
         * @param array  $meta       By default, contains the requested privacy setting and lang_id.
         */
        apply_filters( 'wpmu_signup_blog_notification_email',
            __( "To activate your blog, please click the following link:\n\n%s\n\nAfter you activate, you will receive *another email* with your login.\n\nAfter you activate, you can visit your site here:\n\n%s" ),
            $domain, $path, $title, $user_login, $user_email, $key, $meta
        ),
        $activate_url,
        esc_url( "http://{$domain}{$path}" ),
        $key
    );
    // TODO: Don't hard code activation link.
    $subject = sprintf(
        /**
         * Filters the subject of the new blog notification email.
         *
         * @since MU
         *
         * @param string $subject    Subject of the notification email.
         * @param string $domain     Site domain.
         * @param string $path       Site path.
         * @param string $title      Site title.
         * @param string $user_login User login name.
         * @param string $user_email User email address.
         * @param string $key        Activation key created in wpmu_signup_blog().
         * @param array  $meta       By default, contains the requested privacy setting and lang_id.
         */
        apply_filters( 'wpmu_signup_blog_notification_subject',
            /* translators: New site notification email subject. 1: Network name, 2: New site URL */
            _x( '[%1$s] Activate %2$s', 'New site notification email subject' ),
            $domain, $path, $title, $user_login, $user_email, $key, $meta
        ),
        $from_name,
        esc_url( 'http://' . $domain . $path )
    );
    muauth_mail( $user_email, wp_specialchars_decode( $subject ), $message );

    if ( $switched_locale ) {
        restore_previous_locale();
    }

    return true;
}

/**
  * a hack fix to emails not being sent due to the
  * mail headers set by the notification function
  * fix: strip headers
  *
  * @since 0.1
  */
function muauth_wpmu_signup_user_notification( $user_login, $user_email, $key, $meta = array() ) {
    if ( apply_filters('muauth_wpmu_signup_user_notification_rollback', false) ) {
        return call_user_func_array('wpmu_signup_user_notification', func_get_args());
    }
    /**
     * Filters whether to bypass the email notification for new user sign-up.
     *
     * @since MU
     *
     * @param string $user_login User login name.
     * @param string $user_email User email address.
     * @param string $key        Activation key created in wpmu_signup_user().
     * @param array  $meta       Signup meta data.
     */
    if ( ! apply_filters( 'wpmu_signup_user_notification', $user_login, $user_email, $key, $meta ) )
        return false;

    $user = get_user_by( 'login', $user_login );
    $switched_locale = switch_to_locale( get_user_locale( $user ) );

    // Send email with activation link.
    $admin_email = get_site_option( 'admin_email' );
    if ( $admin_email == '' )
        $admin_email = 'support@' . $_SERVER['SERVER_NAME'];
    $from_name = get_site_option( 'site_name' ) == '' ? 'WordPress' : esc_html( get_site_option( 'site_name' ) );
    $message_headers = "From: \"{$from_name}\" <{$admin_email}>\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
    $message = sprintf(
        /**
         * Filters the content of the notification email for new user sign-up.
         *
         * Content should be formatted for transmission via wp_mail().
         *
         * @since MU
         *
         * @param string $content    Content of the notification email.
         * @param string $user_login User login name.
         * @param string $user_email User email address.
         * @param string $key        Activation key created in wpmu_signup_user().
         * @param array  $meta       Signup meta data.
         */
        apply_filters( 'wpmu_signup_user_notification_email',
            __( "To activate your user, please click the following link:\n\n%s\n\nAfter you activate, you will receive *another email* with your login." ),
            $user_login, $user_email, $key, $meta
        ),
        site_url( "wp-activate.php?key=$key" )
    );
    // TODO: Don't hard code activation link.
    $subject = sprintf(
        /**
         * Filters the subject of the notification email of new user signup.
         *
         * @since MU
         *
         * @param string $subject    Subject of the notification email.
         * @param string $user_login User login name.
         * @param string $user_email User email address.
         * @param string $key        Activation key created in wpmu_signup_user().
         * @param array  $meta       Signup meta data.
         */
        apply_filters( 'wpmu_signup_user_notification_subject',
            /* translators: New user notification email subject. 1: Network name, 2: New user login */
            _x( '[%1$s] Activate %2$s', 'New user notification email subject' ),
            $user_login, $user_email, $key, $meta
        ),
        $from_name,
        $user_login
    );
    muauth_mail( $user_email, wp_specialchars_decode( $subject ), $message );

    if ( $switched_locale ) {
        restore_previous_locale();
    }

    return true;
}

/**
  * a hack fix to emails not being sent due to the
  * mail headers set by the notification function
  * fix: strip headers
  *
  * @since 0.1
  */
function muauth_wpmu_welcome_user_notification( $user_id, $password, $meta = array() ) {
    if ( apply_filters('muauth_wpmu_welcome_user_notification_rollback', false) ) {
        return call_user_func_array('muauth_wpmu_welcome_user_notification_rollback', func_get_args());
    }
    $current_network = get_network();

    /**
     * Filters whether to bypass the welcome email after user activation.
     *
     * Returning false disables the welcome email.
     *
     * @since MU
     *
     * @param int    $user_id  User ID.
     * @param string $password User password.
     * @param array  $meta     Signup meta data.
     */
    if ( ! apply_filters( 'wpmu_welcome_user_notification', $user_id, $password, $meta ) )
        return false;

    $welcome_email = get_site_option( 'welcome_user_email' );

    $user = get_userdata( $user_id );

    $switched_locale = switch_to_locale( get_user_locale( $user ) );

    /**
     * Filters the content of the welcome email after user activation.
     *
     * Content should be formatted for transmission via wp_mail().
     *
     * @since MU
     *
     * @param string $welcome_email The message body of the account activation success email.
     * @param int    $user_id       User ID.
     * @param string $password      User password.
     * @param array  $meta          Signup meta data.
     */
    $welcome_email = apply_filters( 'update_welcome_user_email', $welcome_email, $user_id, $password, $meta );
    $welcome_email = str_replace( 'SITE_NAME', $current_network->site_name, $welcome_email );
    $welcome_email = str_replace( 'USERNAME', $user->user_login, $welcome_email );
    $welcome_email = str_replace( 'PASSWORD', $password, $welcome_email );
    $welcome_email = str_replace( 'LOGINLINK', wp_login_url(), $welcome_email );

    $admin_email = get_site_option( 'admin_email' );

    if ( $admin_email == '' )
        $admin_email = 'support@' . $_SERVER['SERVER_NAME'];

    $from_name = get_site_option( 'site_name' ) == '' ? 'WordPress' : esc_html( get_site_option( 'site_name' ) );
    $message_headers = "From: \"{$from_name}\" <{$admin_email}>\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
    $message = $welcome_email;

    if ( empty( $current_network->site_name ) )
        $current_network->site_name = 'WordPress';

    /* translators: New user notification email subject. 1: Network name, 2: New user login */
    $subject = __( 'New %1$s User: %2$s' );

    /**
     * Filters the subject of the welcome email after user activation.
     *
     * @since MU
     *
     * @param string $subject Subject of the email.
     */
    $subject = apply_filters( 'update_welcome_user_subject', sprintf( $subject, $current_network->site_name, $user->user_login) );
    muauth_mail( $user->user_email, wp_specialchars_decode( $subject ), $message );

    if ( $switched_locale ) {
        restore_previous_locale();
    }

    return true;
}

/**
  * a hack fix to emails not being sent due to the
  * mail headers set by the notification function
  * fix: strip headers
  *
  * @since 0.1
  */    
function muauth_wpmu_welcome_notification( $blog_id, $user_id, $password, $title, $meta = array() ) {
    if ( apply_filters('muauth_wpmu_welcome_notification_rollback', false) ) {
        return call_user_func_array('muauth_wpmu_welcome_notification_rollback', func_get_args());
    }
    $current_network = get_network();

    /**
     * Filters whether to bypass the welcome email after site activation.
     *
     * Returning false disables the welcome email.
     *
     * @since MU
     *
     * @param int|bool $blog_id  Blog ID.
     * @param int      $user_id  User ID.
     * @param string   $password User password.
     * @param string   $title    Site title.
     * @param array    $meta     Signup meta data.
     */
    if ( ! apply_filters( 'wpmu_welcome_notification', $blog_id, $user_id, $password, $title, $meta ) )
        return false;

    $user = get_userdata( $user_id );

    $switched_locale = switch_to_locale( get_user_locale( $user ) );

    $welcome_email = get_site_option( 'welcome_email' );
    if ( $welcome_email == false ) {
        /* translators: Do not translate USERNAME, SITE_NAME, BLOG_URL, PASSWORD: those are placeholders. */
        $welcome_email = __( 'Howdy USERNAME,

Your new SITE_NAME site has been successfully set up at:
BLOG_URL

You can log in to the administrator account with the following information:

Username: USERNAME
Password: PASSWORD
Log in here: BLOG_URLwp-login.php

We hope you enjoy your new site. Thanks!

--The Team @ SITE_NAME' );
    }

    $url = get_blogaddress_by_id($blog_id);

    $welcome_email = str_replace( 'SITE_NAME', $current_network->site_name, $welcome_email );
    $welcome_email = str_replace( 'BLOG_TITLE', $title, $welcome_email );
    $welcome_email = str_replace( 'BLOG_URL', $url, $welcome_email );
    $welcome_email = str_replace( 'USERNAME', $user->user_login, $welcome_email );
    $welcome_email = str_replace( 'PASSWORD', $password, $welcome_email );

    /**
     * Filters the content of the welcome email after site activation.
     *
     * Content should be formatted for transmission via wp_mail().
     *
     * @since MU
     *
     * @param string $welcome_email Message body of the email.
     * @param int    $blog_id       Blog ID.
     * @param int    $user_id       User ID.
     * @param string $password      User password.
     * @param string $title         Site title.
     * @param array  $meta          Signup meta data.
     */
    $welcome_email = apply_filters( 'update_welcome_email', $welcome_email, $blog_id, $user_id, $password, $title, $meta );
    $admin_email = get_site_option( 'admin_email' );

    if ( $admin_email == '' )
        $admin_email = 'support@' . $_SERVER['SERVER_NAME'];

    $from_name = get_site_option( 'site_name' ) == '' ? 'WordPress' : esc_html( get_site_option( 'site_name' ) );
    $message_headers = "From: \"{$from_name}\" <{$admin_email}>\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
    $message = $welcome_email;

    if ( empty( $current_network->site_name ) )
        $current_network->site_name = 'WordPress';

    /* translators: New site notification email subject. 1: Network name, 2: New site name */
    $subject = __( 'New %1$s Site: %2$s' );

    /**
     * Filters the subject of the welcome email after site activation.
     *
     * @since MU
     *
     * @param string $subject Subject of the email.
     */
    $subject = apply_filters( 'update_welcome_subject', sprintf( $subject, $current_network->site_name, wp_unslash( $title ) ) );
    muauth_mail( $user->user_email, wp_specialchars_decode( $subject ), $message );

    if ( $switched_locale ) {
        restore_previous_locale();
    }

    return true;
}

/**
  * aliasing wp_mail to muauth_mail
  * declare __muauth_mail function in order to send out emails
  * your own way
  */
function muauth_mail( $to, $subject, $message, $headers='' ) {
    if ( function_exists('__muauth_mail') ) {
        return call_user_func_array('__muauth_mail', func_get_args());
    }

    return wp_mail( $to, $subject, $message, $headers );
}

function muauth_get_current_auth_blog($default=0, $ignore='auth', $withinfo=true) {
    global $current_site, $muauth, $auth_site;

    $_ignore = $ignore;
    $_default = $default;

    if ( is_numeric( $ignore ) && $default ) {
        $default = (int) $ignore;
    } else {
        $default = $current_site->id;
    }

    if ( $ignore ) {
        if ( is_numeric($ignore) ) {
            $ignore = array( $ignore );
        } else if ( is_string( $ignore ) ) {
            switch ( $ignore ) {
                case 'auth':
                    if ( isset($muauth->auth_blog_id) && $muauth->auth_blog_id ) {
                        $ignore = array($muauth->auth_blog_id);
                    } else {
                        $ignore = array();
                    }
                    break;
            }
        } else if ( is_array( $ignore ) ) {
            $ignore = array_map('intval', $ignore);
            $ignore = array_filter('trim');
        }
    } else {
        $ignore = array();
    }

    $auth_blog = isset($auth_site->blog_id) ? (int) $auth_site->blog_id : 0;

    if ( !$auth_blog || in_array($auth_blog, $ignore) ) {
        $auth_blog = $default;
    }

    if ( $auth_blog && $withinfo ) {
        $auth_blog = get_blog_details($auth_blog);
    }

    return apply_filters('muauth_get_current_auth_blog', $auth_blog, $_default, $_ignore, $withinfo);
}