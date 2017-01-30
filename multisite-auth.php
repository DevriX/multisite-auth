<?php namespace MUAUTH;
/*
Plugin Name: Multisite Auth
Plugin URI: https://samelh.com/
Description: All in one Multisite Auth handler
Author: Samuel Elh
Version: 0.1.2
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
            add_action( "admin_notices", array( $this, "notice" ) );
        }
    }

    public function notice()
    {
        printf('<div class="error notice is-dismissible"><p>Multisite Auth plugin requires at least PHP %s.</p></div>',$this->min);
    }
}

$muauthVersionCompare = new VersionCompare();

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

    public static function init()
    {
        // include helpers
        require_once MUAUTH_DIR . 'Includes/Core/functions.php';
        // activation
        register_activation_hook( __FILE__, array( self::instance(), "activation" ) );
        // deactivation
        register_deactivation_hook( __FILE__, array( self::instance(), "deactivation" ) );
    }

    public function setup()
    {
        return $this->setupConstants()->setupGlobals();
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
            'logout' => _x('Log out', 'component name', 'muauth')
        );

        $muauth_errors = (object) array(
            'default' => new \WP_Error
        );

        return $this;
    }

    /** autoloader **/
    public static function autoload( $class, $regular_file=null ) {
        $classFile = $class;
        // main parent namespace
        $parentNamespace = 'MUAUTH';

        if ( "\{$parentNamespace}\\" === substr( $classFile, 0, (strlen($parentNamespace)+2) ) ) {
            $classFile = substr( $classFile, (strlen($parentNamespace)+2) );
        }
        else if ( "{$parentNamespace}\\" === substr( $classFile, 0, (strlen($parentNamespace)+1) ) ) {
            $classFile = substr( $classFile, (strlen($parentNamespace)+1) );
        }

        $classFile = MUAUTH_DIR."{$classFile}.php";
        $classFile = str_replace( '\\', '/', $classFile );

        if ( (!class_exists( $class ) || $regular_file) && file_exists($classFile) ) {
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

    public static function activation()
    {

    }

    public static function deactivation()
    {

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
$MUAUTH->setup()->init();

// load plugin
$MUAUTH::autoload( 'MUAUTH\Includes\Core\Plugin' );

// init plugin
Plugin::init();

if ( is_admin() ) {
    // load admin
    $MUAUTH::autoload( 'MUAUTH\Includes\Admin\Admin' );
    // init admin
    Admin::init();
}

/*
TODO:
- widgets: login
- addons: reCaptcha, google authenticator, mailchimp
- uninstall hooks
*/