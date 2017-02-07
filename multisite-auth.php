<?php namespace MUAUTH;
/*
Plugin Name: Multisite Auth
Plugin URI: https://github.com/elhardoum/multisite-auth
Description: A multisite authentication plugin for handling logins, signups, password resets, activation account auth components all in one site
Author: Samuel Elh
Version: 0.1.4
Author URI: https://samelh.com
Text Domain: muauth
*/

// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);

if ( !class_exists('VersionCompare') ) :

/**
  * Compares PHP versions
  * Making sure this blog is running enough required PHP software for
  * this plugin, currently requiring 5.3 at least for namespaces
  */

Class VersionCompare
{
    public $hasRequiredPHP;
    protected $min;

    public function __construct( $minVersion = '5.3' )
    {
        $this->min = $minVersion;

        if ( version_compare(PHP_VERSION, $this->min, '>=') ) {
            $this->hasRequiredPHP = true;
        } else {
            $tag = 'admin_notices';

            if ( is_multisite() && is_network_admin() ) {
                $tag = "network_{$tag}";
            }

            add_action( $tag, array( $this, "notice" ) );
        }
    }

    public function notice()
    {
        printf('<div class="error notice is-dismissible"><p>Multisite Auth plugin requires at least PHP %s.</p></div>',$this->min);
    }
}

$muauthVersionCompare = new VersionCompare;

if ( !isset($muauthVersionCompare->hasRequiredPHP) || !$muauthVersionCompare->hasRequiredPHP ) {
    return; // no min server requirements
}

endif;

use \MUAUTH\Includes\Core\Plugin
  , \MUAUTH\Includes\Admin\Admin;

class MUAUTH
{
    /** Class instance **/
    protected static $instance = null;

    /** Constants **/
    public $constants;

    /** Get Class instance **/
    public static function instance()
    {
        return null == self::$instance ? new self : self::$instance;
    }

    public function setup()
    {
        return $this
            ->setupConstants()
            ->setupGlobals()
            ->registerAutoload()
            ->loadHelpers();
    }

    /** define necessary constants **/
    public function setupConstants()
    {
        $this->constants = array(
            "MUAUTH_FILE" => __FILE__,
            "MUAUTH_DIR" => plugin_dir_path(__FILE__),
            "MUAUTH_URL" => plugin_dir_url(__FILE__),
            "MUAUTH_VER" => '0.1.2',
            "MUAUTH_NAME" => __('Multisite Auth', 'muauth'),
            "MUAUTH_BASE" => plugin_basename(__FILE__),
            "MUAUTH_DOMAIN" => 'muauth'
        );

        /**
          * Notice the use of defined() conditional, this means you can
          * pre-define a constant or more in your wp-config file
          */
        foreach ( $this->constants as $constant => $def ) {
            if ( !defined( $constant ) ) {
                define( $constant, $def );
            }
        }

        return $this;
    }

    public function setupGlobals()
    {
        global $muauth_raw_components
             , $muauth_errors;

        $muauth_raw_components = array(
            'login' => _x('Account Login', 'component name', 'muauth'),
            'register' => _x('Sign Up', 'muauth'),
            'lost-password' => _x('Password Reset', 'component name', 'muauth'),
            'activation' => _x('Account Activation', 'component name', 'muauth'),
            'logout' => _x('Log out', 'component name', 'muauth'),
        );

        $muauth_errors = (object) array(
            'default' => new \WP_Error
        );

        return $this;
    }

    public function registerAutoload()
    {
        spl_autoload_register(array($this, 'autoload'));

        return $this;
    }

    public function loadHelpers()
    {
        // include helpers
        require_once MUAUTH_DIR . sprintf (
            'Includes%1$sCore%1$sfunctions.php',
            DIRECTORY_SEPARATOR
        );

        return $this;
    }

    /** autoloader **/
    public function autoload($class) {
        $classFile = $class;
        // main parent namespace
        $parentNamespace = __NAMESPACE__;

        if ( "\{$parentNamespace}\\" === substr( $classFile, 0, (strlen($parentNamespace)+2) ) ) {
            $classFile = substr( $classFile, (strlen($parentNamespace)+2) );
        }
        else if ( "{$parentNamespace}\\" === substr( $classFile, 0, (strlen($parentNamespace)+1) ) ) {
            $classFile = substr( $classFile, (strlen($parentNamespace)+1) );
        }

        $classFile = MUAUTH_DIR."{$classFile}.php";
        $classFile = str_replace( '\\', DIRECTORY_SEPARATOR, $classFile );

        if ( !class_exists( $class ) && file_exists($classFile) ) {
            return require( $classFile );
        }
    }

    public static function verifyNonce($param='muauth_nonce', $action='muauth_nonce')
    {
        if ( !isset( $_REQUEST[$param] ) ) {
            return;
        }
        return wp_verify_nonce($_REQUEST[$param], $action);
    }

    public static function loadTemplate($file)
    {
        // pluggable
        $file = apply_filters("muauth_load_template_file", MUAUTH_DIR . "templates/{$file}", $file);
        // include
        if ( file_exists( $file ) ) {
            include( $file );
        }
    }
}

// init
$MUAUTH = new MUAUTH;
$MUAUTH->setup();

// init plugin
Plugin::init();

if ( is_admin() ) {
    // init admin
    Admin::init();
}

/*
TODO:
- widgets: login
- addons: reCaptcha, google authenticator, mailchimp
- uninstall hooks
*/