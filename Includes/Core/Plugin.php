<?php namespace MUAUTH\Includes\Core;

use \MUAUTH\MUAUTH;

// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);

/**
  * plugin class
  */

class Plugin
{
    /** Class instance **/
    protected static $instance = null;

    /** Get Class instance **/
    public static function instance()
    {
        return null == self::$instance ? new self : self::$instance;
    }

    public static function init()
    {
        // class instane
        $ins = self::instance();
        // globals
        add_action( "plugins_loaded", array( $ins, "loader" ) );
    }

    public static function loader()
    {
        return self::instance()
            ->loadTextdomain() /* i18n */
            ->setupGlobals() /* global variables */
            ->_init(); /* init plugin */
    }

    public static function setupGlobals()
    {
        global $muauth, $muauth_raw_components;

        $current_blog_id = (int) (function_exists('get_current_blog_id') ? get_current_blog_id() : 0);
        $auth_blog_id = (int) get_site_option( 'muauth_auth_blog_id', 0);
        $registration = get_site_option( 'registration', 'none' );

        $muauth = (object) array(
            'current_blog_id'   => (int) (function_exists('get_current_blog_id') ? $current_blog_id : 0),
            'is_main_site'      => (bool) (function_exists('is_main_site') && is_main_site($current_blog_id)),
            'components'        => get_site_option( 'muauth_components', array_keys($muauth_raw_components)),
            'auth_blog_id'      => $auth_blog_id,
            'is_auth_blog'      => $auth_blog_id === $current_blog_id,
            'registration'      => $registration,
            'registration_on'   => $registration && in_array($registration, array('user','blog','all')),
            'tabindex'          => 1
        );

        return self::instance();
    }

    /** setup **/
    public static function _init()
    {
        // class instance
        $ins = self::instance();

        // load filters
        require_once MUAUTH_DIR . (
            sprintf('Includes%1$sCore%1$sfilters.php', DIRECTORY_SEPARATOR)
        );

        // template functions
        require_once MUAUTH_DIR . (
            sprintf('Includes%1$sCore%1$stemplate-functions.php', DIRECTORY_SEPARATOR)
        );

        if ( method_exists('\MUAUTH\Includes\Core\Shortcodes', 'shortcodesInit') ) {
            call_user_func(array('\MUAUTH\Includes\Core\Shortcodes', 'shortcodesInit'));
        }

        // global settings
        global $muauth;

        // run only when in auth blog
        if ( $muauth->is_auth_blog ) {
            // init auth blog
            add_action( "init", array( $ins, "authBlogInit" ) );
        }

        return $ins;
    }

    public static function authBlogInit()
    {
        // class instance
        $ins = self::instance();
        // setup globals
        self::authSetupGlobals();
        // rewrite rules
        $ins::authAddRewriteRule();
        // append custom query variables
        add_filter( 'query_vars', array( $ins, 'authAppendQueryVars' ) );
        // validate auth site and component
        add_action( 'parse_query', array( $ins, 'authValidateRequest' ) );
        // enqueue scripts
        add_action( 'wp_enqueue_scripts', array( $ins, 'authEnqueueScripts' ) );
        // trigger hook
        do_action( 'init_auth_blog', $ins );
    }

    /** i18n **/
    public static function loadTextdomain()
    {
        load_plugin_textdomain(MUAUTH_DOMAIN, FALSE, dirname(MUAUTH_BASE).'/languages');

        return self::instance();
    }

    public static function loadTemplate($file)
    {
        // core path
        $TEMPALTES_BASE = MUAUTH_DIR . 'templates';
        // child path
        $THEME_BASE = get_stylesheet_directory() . '/' . dirname(MUAUTH_BASE) . '/templates';

        if ( file_exists( "{$THEME_BASE}/{$file}" ) ) {
            require( "{$THEME_BASE}/{$file}" );
        } else if ( file_exists( "{$TEMPALTES_BASE}/{$file}" ) ) {
            require( "{$TEMPALTES_BASE}/{$file}" );
        }

        do_action('muauth_catch_inexisting_template_file', $file);
    }

    public static function authSetupGlobals()
    {
        global $auth_page;
        
        $auth_page = muauth_get_auth_page();
    }

    public static function authAddRewriteRule()
    {
        global $auth_page;

        if ( empty( $auth_page->post_name ) )
            return;

        global $muauth_raw_components;

        $components = apply_filters('muauth_raw_components_pre_rewrite', $muauth_raw_components);

        if ( !$components )
            return;
        
        foreach ( array_keys($components) as $slug ) {
            add_rewrite_rule(
                '([^/]+)/' . apply_filters('muauth_component_slug', $slug) . '/?$',
                'index.php?pagename='.$auth_page->post_name.'&muauth_site=$matches[1]&muauth_component='.$slug,
                'top'
            );
        }
    }

    public static function authAppendQueryVars( $vars )
    {
        $vars[] = 'muauth_site';
        $vars[] = 'muauth_component';
        return $vars;
    }

    public static function authValidateRequest()
    {
        global $auth_page, $auth_site, $_auth_site;

        if ( empty( $auth_page->ID ) )
            return;
        
        if ( !is_page( $auth_page->ID ) )
            return;

        /** auth-to site **/
        
        // get site
        $auth_site = muauth_get_current_auth_site();

        // filtered: default to main site, ignore auth site to get user target site
        $_auth_site = muauth_get_current_auth_blog(null,'auth');

        // verify
        if ( empty( $auth_site->blog_id ) ) {
            /* site not there, bail */
            return muauth_trigger_404();
        }

        if ( muauth_is_site_disabled($auth_site->blog_id) ) {
            /** site is disabled in network settings, bail **/
            return muauth_trigger_404();
        }

        /** auth component **/

        $component = muauth_get_current_component();

        if ( !muauth_is_component_active( $component ) ) {
            /* component not active in network settings, quit */
            return muauth_trigger_404();
        }

        // trigger hook
        do_action( 'muauth_parse_query_handle_component_' . $component, $auth_site );
        // trigger hook
        do_action( 'muauth_parse_query_handle_component', $component, $auth_site );
    }

    public static function authEnqueueScripts()
    {
        /* stylesheet */
        wp_enqueue_style( "mu-auth", apply_filters( 'muauth_stylesheet', MUAUTH_URL . 'assets/css/style.css' ), MUAUTH_VER );
    }

}