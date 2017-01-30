<?php namespace MUAUTH\Includes\Core;

// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);

/**
  * Redirect class
  */

class Redirect
{
    /** Class instance **/
    protected static $instance = null;

    /** Get Class instance **/
    public static function instance()
    {
        if ( null == self::$instance )
            self::$instance = new self;

        return self::$instance;
    }

    public static $redirect_to = null
                , $safe = null;

    public static function to( $uri, $safe=false )
    {
        self::$redirect_to = $uri;
        self::$safe = (bool) $safe;

        return self::instance();
    }

    public static function addError( $list, $cookie_key='muauth_errors' )
    {
        $list = apply_filters('muauth_add_error_session_pre', $list, $cookie_key);

        if ( !$list || !is_array($list) )
            return;

        if ( isset($list[0]) && !is_array($list[0]) ) {
            $list = array( $list );
        }

        if (!session_id()) {
            session_start();
        }

        if ( isset($_SESSION[$cookie_key]) && is_array($_SESSION[$cookie_key]) ) {
            $list = array_merge( $_SESSION[$cookie_key], $list );
        }

        $_SESSION[$cookie_key] = $list;
    }

    public static function withNotice( $list, $cookie_key='muauth_errors', $do=true )
    {
        self::addError($list, $cookie_key);

        if ( $do ) {
            return self::_do();
        }

        return self::instance();
    }

    public static function parseNotices($cookie_key='muauth_errors', $parser='')
    {
        if (!session_id()) {
            session_start();
        }

        if ( isset($_SESSION[$cookie_key]) ) {
            $errors = $_SESSION[$cookie_key];

            if ( is_array($errors) && $errors ) {
                foreach ( $errors as $error ) {
                    if ( is_callable( $parser ) ) {
                        call_user_func_array($parser, $error);
                    }
                }
            }
            
            unset( $_SESSION[$cookie_key] );
        }
    }

    public static function _do()
    {
        if ( !isset( self::$redirect_to ) || empty( self::$redirect_to ) )
            return;

        if ( isset(self::$safe) && self::$safe ) {
            wp_safe_redirect( self::$redirect_to );
        } else {
            wp_redirect( self::$redirect_to );
        }
        exit;
    }

    public function __destruct()
    {
        #$this->_do();
    }
}