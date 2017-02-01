<?php namespace MUAUTH\Includes\Admin;

use MUAUTH\MUAUTH;

// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);

/**
  * admin class
  */

class Admin
{
	/** check if in plugin admin **/
	public $active = null, $page;

    /** Class instance **/
    protected static $instance = null;

    /** tabs **/
    static $tabs = null, $tab = null;

    /** Get Class instance **/
    public static function instance()
    {
        return null == self::$instance ? new self : self::$instance;
    }

    function __construct()
    {
    	if ( isset( $_GET['page'] ) ) {
    		$this->active = 'mu-auth' === substr( $_GET['page'], 0, strlen('mu-auth') );
    	}

        $this->page = isset( $_GET['page'] ) ? esc_attr( $_GET['page'] ) : null;
        global $pagenow;
        $this->pageNow = $pagenow;
    }

    public static function init()
    {
        return add_action( "plugins_loaded", array( self::instance(), "_init" ) );
    }

    /** setup admin **/
    public static function _init()
    {
        // instance
        $ins = self::instance();
        // settings
        global $muauth;
        // notices
        add_action( ($muauth->is_main_site ? 'network_' : '') . 'admin_notices', array( $ins, 'uiFeedback' ) );
        // deactivate if not in multisite
        add_action( 'admin_init', array( $ins, 'multisiteCheck' ) );

        if ( $muauth->is_main_site ) {
            // admin pages
            add_action( 'network_admin_menu', array( $ins, 'networkSetupPages' ) );
            // generate auth page
            add_action( 'network_admin_menu', array( $ins, 'genereatePage' ) );
            // manage settings
            if ( 'settings.php' === $ins->pageNow && 'mu-auth' === $ins->page ) {
                add_action( 'network_admin_menu', array( $ins, 'updateSettings' ) );
            }
            // plugin meta links
            add_filter( 'plugin_row_meta', array( $ins, 'pushRowLinks' ), 10, 2);
        } else if ( $muauth->is_auth_blog ) { // current blog is the auth site
            // admin pages
            add_action( 'admin_menu', array( $ins, 'setupAuthPages' ) );
            // manage settings
            add_action( 'admin_menu', array( $ins, 'updateAuthSettings' ) );
            // generate auth page
            add_action( 'admin_menu', array( $ins, 'genereatePage' ) );
        }
        // super admin alerts
        add_action( ($muauth->is_main_site ? 'network_' : '') . 'admin_menu', array( $ins, 'superAdminNotices' ) );
        // plugin links
        add_filter( 'network_admin_plugin_action_links_' . MUAUTH_BASE, array( $ins, 'pluginLinks' ));
        
        if ( method_exists('\MUAUTH\Includes\Core\Shortcodes', 'adminInit') ) {
            call_user_func(array('\MUAUTH\Includes\Core\Shortcodes', 'adminInit'));
        }

        // setup tabs
        self::setupTabs();
    }

    public static function multisiteCheck()
    {
        if ( !is_multisite() ) {
            // notice
            self::feedback(array(
                'success' => false,
                'message' => sprintf(__('Sorry, %s can work only on a multi-site install. Deactivating plugin..', MUAUTH_DOMAIN), MUAUTH_NAME)
            ));
            // deactivate
            deactivate_plugins( MUAUTH_BASE );
        } else if ( !is_plugin_active_for_network( MUAUTH_BASE ) ) {
            // require network-wide activation
            if ( is_super_admin( get_current_user_id() ) ) {
                self::feedback(array(
                    'success' => false,
                    'message' => sprintf(__('Please activate %s for the whole network!', MUAUTH_DOMAIN), MUAUTH_NAME)
                ));
            }
        }
    }

    /** netowrk setup pages **/
    public static function networkSetupPages()
    {  
        self::setCurrentTab();

        if ( isset(self::$tab) && isset(self::$tab['title']) && self::$tab['title'] )
            $tabTitle = self::$tab['title'];
        else
            $tabTitle = sprintf(__('Settings &lsaquo; %s', MUAUTH_DOMAIN), MUAUTH_NAME);

        return add_submenu_page(
            'settings.php',
            $tabTitle,
            MUAUTH_NAME,
            'manage_options',
            'mu-auth',
            array(self::instance(), "networkScreen")
        );
    }

    public static function setupTabs()
    {
        $ins = self::instance();

        self::$tabs = array(
            'settings' => array(
                'contentCallback' => array($ins, 'networkSettingsScreen'),
                'title' => __('Settings', MUAUTH_DOMAIN),
                'updateCallbak' => array($ins, 'updateNetworkSettings')
            )
        );

        self::$tabs = apply_filters('muauth_network_settings_tabs', self::$tabs);

        return $ins;
    }

    public static function setCurrentTab()
    {
        $ins = self::instance();

        if ( isset( $_GET['tab'] ) && trim($_GET['tab']) ) {
            self::$tab = esc_attr($_GET['tab']);
        } else {
            self::$tab = 'settings';
        }

        if ( !is_array(self::$tabs) || !isset(self::$tabs[self::$tab]) ) {
            self::$tab = 'settings';
        }

        foreach ( self::$tabs as $name=>$tab ) {
            if ( $name === self::$tab ) {
                self::$tab = $tab;
                self::$tab['name'] = $name;
            }
        }

        return $ins;
    }

    public static function loadTabContent()
    {
        $ins = self::instance();

        if ( isset(self::$tab) && is_array(self::$tab) ) {
            $callback = isset(self::$tab['contentCallback']) ? self::$tab['contentCallback'] : null;

            if ( !is_callable($callback) ) {
                self::feedback(array(
                    'success' => false,
                    'message' => __('Error: Could not load tab content', MUAUTH_DOMAIN)
                ));
            } else {
                call_user_func($callback);
            }
        } else {
            self::feedback(array(
                'success' => false,
                'message' => __('Error: Could not load tab content', MUAUTH_DOMAIN)
            ));
        }

        return $ins;
    }

    public static function updateTabSettings()
    {
        $ins = self::instance();

        if ( isset(self::$tab) && is_array(self::$tab) ) {
            $callback = isset(self::$tab['updateCallbak']) ? self::$tab['updateCallbak'] : null;

            if ( is_callable($callback) ) {
                call_user_func($callback);
            }
        }

        return $ins;
    }

    public static function networkScreen()
    {
        self::setCurrentTab();

        ?>

        <div class="wrap">

        <?php if ( self::$tab && !empty( self::$tab['title'] ) ) : ?>
            <h2><?php printf( __('%1$s &lsaquo; %2$s', MUAUTH_DOMAIN), self::$tab['title'], MUAUTH_NAME ); ?></h2>
        <?php endif; ?>

        <h2 class="nav-tab-wrapper">
            <?php foreach ( self::$tabs as $name=>$tab ) : $tab['name'] = $name; ?>
                <a 
                    class="nav-tab<?php echo $tab == self::$tab ?" nav-tab-active":"";?>"
                    href="settings.php?page=mu-auth<?php echo $name && 'settings' !== $name ? "&tab={$name}" : ''; ?>"
                >
                    <span><?php echo esc_attr($tab['title']); ?></span>
                </a>
            <?php endforeach; ?>
        </h2>

        <?php self::loadTabContent(); ?>

        </div>

        <?php

        return self::instance();
    }

    public static function networkSettingsScreen()
    {
        MUAUTH::loadTemplate('admin/network/settings.php');
    }

    public static function updateSettings()
    {
        // set current tab
        self::setCurrentTab();

        if ( isset(self::$tab) && isset(self::$tab['name']) ) {
            do_action('muauth_network_headers_' . self::$tab['name']);
        }

        do_action('muauth_network_headers', isset(self::$tab) ? self::$tab : new stdClass);

        global $muauth;

        if ( isset( $_POST['submit'] ) ) {
            if ( !MUAUTH::verifyNonce() ) {
                return self::feedback(array(
                    'success' => false,
                    'message' => __('Error: bad authentication', MUAUTH_DOMAIN)
                ));
            } else {
                // call and feedback
                self::updateTabSettings()->feedback(array(
                    'success' => true,
                    'message' => __('Settings updated successfully!', MUAUTH_DOMAIN)
                ));
            }
        }

        if ( !$muauth->registration_on && muauth_is_component_active( 'register' ) ) {
            self::feedback(array(
                'success' => false,
                'message' => __('The registration component is enabled, however, registration is disabled.', MUAUTH_DOMAIN)
            ));
        }
    }

    public static function updateNetworkSettings() {
        global $muauth;

        if ( isset( $_POST['components'] ) && is_array($_POST['components']) ) {

            $_POST['components'] = array_map('strval', $_POST['components']);
            $_POST['components'] = array_filter($_POST['components'], 'trim');

            if ( $_POST['components'] ) {
                update_site_option( 'muauth_components', (array) $_POST['components'] );
                // update global data
                $muauth->components = (array) $_POST['components'];
            } else {
                update_site_option('muauth_components', array());
                // update global data
                $muauth->components = array();
            }

        } else {
            update_site_option('muauth_components', array());
            // update global data
            $muauth->components = array();
        }

        if ( isset( $_POST['auth_blog'] ) && intval( $_POST['auth_blog'] ) ) {
            update_site_option( 'muauth_auth_blog_id', (int) $_POST['auth_blog'] );
            // update global data
            $muauth->auth_blog_id = (int) $_POST['auth_blog'];
        } else {
            delete_site_option( 'muauth_auth_blog_id' );
            // update global data
            $muauth->auth_blog_id = 0;
        }

        if ( isset($_POST['custom_disable']) )
            muauth_set_disabled_sites_ids( $_POST['custom_disable'], true );
    }

    /** setup pages **/
    public static function setupAuthPages()
    {
        // this is all about selecting a page, while later we decided to auto-generate it instead
        if ( apply_filters( 'muauth_hide_auth_page_settings', true ) )
            return;

        return add_submenu_page(
            'options-general.php',
            sprintf(__('Settings &lsaquo; %s', MUAUTH_DOMAIN), MUAUTH_NAME),
            MUAUTH_NAME,
            'manage_options',
            'mu-auth',
            array(self::instance(), "authSettingsScreen")
        );
    }

    public static function authSettingsScreen()
    {
        MUAUTH::loadTemplate('admin/auth/settings.php');             
    }

    public static function updateAuthSettings()
    {
        if ( isset( $_POST['submit'] ) ) {
            if ( !MUAUTH::verifyNonce() ) {
                return self::feedback(array(
                    'success' => false,
                    'message' => __('Error: bad authentication', MUAUTH_DOMAIN)
                ));
            } else {
                global $auth_page;

                $pre_auth_page = $auth_page;

                if ( isset( $_POST['auth_page'] ) && intval( $_POST['auth_page'] ) ) {
                    $page = get_post( $_POST['auth_page'] );
                    if ( !empty( $page->ID ) ) {
                        update_option( 'muauth_auth_page', $page->ID );
                        $auth_page = $page;
                    } else {
                        delete_option( 'muauth_auth_page' );
                        $auth_page = null;
                    }
                } else {
                    delete_option( 'muauth_auth_page' );
                    // update global data
                    $auth_page = null;
                }

                if ( !empty( $pre_auth_page->ID ) ) {
                    if ( empty( $auth_page->ID ) || $auth_page->ID !== $pre_auth_page->ID ) {
                        // flush rewrite rules
                        delete_option('rewrite_rules');
                    }
                } else if ( !empty( $auth_page->ID ) ) {
                    // flush rewrite rules
                    delete_option('rewrite_rules');
                }

                return self::feedback(array(
                    'success' => true,
                    'message' => __('Settings updated successfully!', MUAUTH_DOMAIN)
                ));
            }
        }

    }

    public static function feedback( $new_response )
    {
        if ( is_array($new_response) && isset( $new_response['success'] ) ) {
            global $dp_admin_feedback;
            if ( !is_array($dp_admin_feedback) ) {
                $dp_admin_feedback = array();
            }
            $dp_admin_feedback[] = $new_response;
        }

        return self::instance();
    }

    public static function uiFeedback()
    {
        global $dp_admin_feedback, $dp_admin_feedback_printed;
        if ( !isset( $dp_admin_feedback_printed ) || !is_array($dp_admin_feedback_printed) ) {
            $dp_admin_feedback_printed = array();
        }
        if ( $dp_admin_feedback && is_array($dp_admin_feedback) ) {
            foreach ( $dp_admin_feedback as $i => $res ) {
                if ( empty( $res['message'] ) ) continue;
                // duplicates check
                if ( isset($dp_admin_feedback_printed[$res['message']]) ) continue;
                $dp_admin_feedback_printed[$res['message']] = true;
                // print message
                printf(
                    '<div class="%s notice is-dismissible"><p>%s</p></div>',
                    !empty($res['success'])?'updated':'error',
                    $res['message']
                );
            }
        }

        return self::instance();
    }

    public static function superAdminNotices()
    {
        if ( !is_super_admin( get_current_user_id() ) )
            return;

        global $muauth;

        if ( $muauth->auth_blog_id ) {
            switch_to_blog( $muauth->auth_blog_id );
            $auth_admin_url = admin_url('options-general.php?page=mu-auth');
            restore_current_blog();
        } else {

            $network_admin_url = network_admin_url('settings.php?page=mu-auth');

            self::feedback(array(
                'success' => false,
                'message' => __(
                    sprintf(__('You must choose a site to handle all the authentication. Click <a href="%s">here</a> to select a site or create a new one.', MUAUTH_DOMAIN), $network_admin_url),
                    MUAUTH_DOMAIN
                )
            ));
        }

        do_action('muauth_super_admin_notices');
    }

    public static function genereatePage()
    {
        $page = muauth_get_auth_page();

        if ( !$page || empty( $page->ID ) || empty( $page->post_status ) || 'publish' !== $page->post_status ) {
            global $muauth;

            if ( isset($muauth->auth_blog_id) && $muauth->auth_blog_id ) {
                switch_to_blog($muauth->auth_blog_id);
                muauth_auto_setup_auth_page($muauth->auth_blog_id);
                delete_option('rewrite_rules');
                restore_current_blog();
            }
        }
    }

    public static function pluginLinks($links)
    {
        return array_merge(array(
            'Settings' => sprintf(
                '<a href="%s">' . __('Settings', MUAUTH_DOMAIN) . '</a>',
                network_admin_url('settings.php?page=mu-auth')
            )
        ), $links);
    }

    public static function pushRowLinks( $links, $file )
    {
        if ( $file == MUAUTH_BASE ) {
            $c = array(
                _x('Addons', 'plugin meta links', MUAUTH_DOMAIN) => 'https://git.io/vDmP4',
                _x('Shortcodes', 'shortcodes admin', MUAUTH_DOMAIN) => network_admin_url('settings.php?page=mu-auth&tab=shortcodes'),
                _x('Bug Report', 'plugin meta links', MUAUTH_DOMAIN) => 'https://git.io/vDmPO',
            );
            foreach ( $c as $t=>$l ) {
                $c[$t] = '<a href="' . $l . '">' . $t . '</a>';
            }
            $links += $c;
        }
        return $links;
    }
}