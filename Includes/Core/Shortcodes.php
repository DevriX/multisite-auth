<?php namespace MUAUTH\Includes\Core;

// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);

/**
  * Shortcodes class
  */

class Shortcodes
{
    /** Class instance **/
    protected static $instance = null;

    public $tags = null;

    /** Get Class instance **/
    public static function instance()
    {
        return null == self::$instance ? new self : self::$instance;
    }

    public function __construct()
    {
        $this->tags = array(
            'muauth_login_url',
            'muauth_lost_password_url',
            'muauth_register_url',
            'muauth_activation_url',
            'muauth_logout_url',
            'muauth_login_form'
        );
    }

    public static function shortcodes()
    {
        $ins = self::instance();
        $tags = $ins->tags;

        $tags = array_map(function($tag){
            if ( 'muauth_' === substr($tag, 0, strlen('muauth_')) ) {
                $tag = substr($tag, strlen('muauth_'));
            }
            return preg_replace_callback('/_.{1}/i', function($m){
                return strtoupper(str_replace('_','',array_shift($m)));
            }, $tag);
        }, $tags);

        $data = array();

        foreach ( $tags as $callback ) {
            $data[] = array(
                'callback' => array($ins, $callback)
            );
        }

        $data = array_combine($ins->tags, $data);

        foreach ( $data as $tag=>$d ) {
            switch ($tag) {
                case 'muauth_login_url':
                    $data[$tag]['params'] = array(
                        'redirect_to' => __('Optional redirect-to URL after successful login', MUAUTH_DOMAIN),
                        'blog_id' => __('Optional blog ID to login to, if not set, it will get current blog ID', MUAUTH_DOMAIN)
                    );
                    $data[$tag]['use'] = __('Returns the login URL', MUAUTH_DOMAIN);
                    break;

                case 'muauth_lost_password_url':
                    $data[$tag]['params'] = array(
                        'blog_id' => __('Optional blog ID to login to, if not set, it will get current blog ID', MUAUTH_DOMAIN)
                    );
                    $data[$tag]['use'] = __('Returns a password-reset URL', MUAUTH_DOMAIN);
                    break;

                case 'muauth_register_url':
                    $data[$tag]['params'] = array(
                        'blog_id' => __('Optional blog ID to login to, if not set, it will get current blog ID', MUAUTH_DOMAIN)
                    );
                    $data[$tag]['use'] = __('Returns the path to registeration page', MUAUTH_DOMAIN);
                    break;

                case 'muauth_activation_url':
                    $data[$tag]['params'] = array(
                        'blog_id' => __('Optional blog ID to login to, if not set, it will get current blog ID', MUAUTH_DOMAIN)
                    );
                    $data[$tag]['use'] = __('Returns the activation page URL', MUAUTH_DOMAIN);
                    break;

                case 'muauth_logout_url':
                    $data[$tag]['params'] = array(
                        'redirect_to' => __('Optional redirect-to URL after logout', MUAUTH_DOMAIN),
                        'blog_id' => __('Optional blog ID to logout from, if not set, it will get current blog ID', MUAUTH_DOMAIN)
                    );
                    $data[$tag]['use'] = __('Generates a logout URL', MUAUTH_DOMAIN);
                    break;

                case 'muauth_login_form':
                    $data[$tag]['params'] = array(
                        'redirect_to' => __('Optional redirect-to URL after successful login', MUAUTH_DOMAIN),
                        'blog_id' => __('Optional blog ID to login to, defaults to main site, used for redirect purposes ignore this if you are setting the redirect parameter.', MUAUTH_DOMAIN)
                    );
                    $data[$tag]['use'] = __('Prints a simple login form', MUAUTH_DOMAIN);
                    break;
            }
        }

        return $data;
    }

    public static function shortcodesInit()
    {
        $ins = self::instance();
        $list = $ins::shortcodes();

        foreach ( $list as $tag=>$data ) {
            add_shortcode($tag, $data['callback'], 10, 2);
        }
    }

    public static function loginUrl($atts)
    {
        $atts = shortcode_atts(array(
            'redirect_to' => null,
            'blog_id' => null,
        ), $atts);

        $output = call_user_func_array('muauth_get_login_url', $atts);

        return apply_filters('muauth_shortcodes_loginUrl', $output, $atts);
    }

    public static function lostPasswordUrl($atts)
    {
        $atts = shortcode_atts(array(
            'deprecated' => null,
            'blog_id' => null
        ), $atts);

        $output = call_user_func_array('muauth_get_lostpassword_url', $atts);

        return apply_filters('muauth_shortcodes_lostPasswordUrl', $output, $atts);
    }

    public static function registerUrl($atts)
    {
        $atts = shortcode_atts(array(
            'deprecated' => null,
            'blog_id' => null
        ), $atts);

        $output = call_user_func_array('muauth_get_register_url', $atts);

        return apply_filters('muauth_shortcodes_registerUrl', $output, $atts);
    }

    public static function activationUrl($atts)
    {
        $atts = shortcode_atts(array(
            'deprecated' => null,
            'blog_id' => null
        ), $atts);

        $output = call_user_func_array('muauth_get_activation_url', $atts);

        return apply_filters('muauth_shortcodes_activationUrl', $output, $atts);
    }

    public static function logoutUrl($atts)
    {
        $atts = shortcode_atts(array(
            'redirect_to' => null,
            'blog_id' => null,
            'nonce' => true
        ), $atts);

        $output = call_user_func_array('muauth_get_logout_url', $atts);

        return apply_filters('muauth_shortcodes_logout', $output, $atts);
    }

    public static function loginForm($atts)
    {
        global $current_site;

        $atts = shortcode_atts(array(
            'redirect_to' => null,
            'blog_id' => $current_site->id,
            'unique_id' => 'shortcode_login_form'
        ), $atts);

        $output = call_user_func_array('muauth_login_form', $atts);

        return apply_filters('muauth_shortcodes_loginForm', $output, $atts);
    }

    public static function adminInit()
    {
        add_filter('muauth_network_settings_tabs', array(self::instance(), 'networkSettingsTab'));
    }

    public static function networkSettingsTab($tabs)
    {
        return array_merge($tabs, array(
            'shortcodes' => array(
                'contentCallback' => array(self::instance(), 'adminShortcodes'),
                'title' => _x('Shortcodes', 'shortcodes admin', MUAUTH_DOMAIN)
            )
        ));
    }

    public static function adminShortcodes()
    {
        $shortcodes = self::shortcodes();
        ?>

        <table class="form-table widefat striped">
            <thead>
                <tr>
                    <th style="padding-left:10px"><?php _ex('Shortcode', 'shortcodes admin', MUAUTH_DOMAIN); ?></th>
                    <th style="padding-left:10px"><?php _ex('Use', 'shortcodes admin', MUAUTH_DOMAIN); ?></th>
                    <th style="padding-left:10px"><?php _ex('Attributes', 'shortcodes admin', MUAUTH_DOMAIN); ?></th>
                </tr>
            </thead>

            <?php foreach ( $shortcodes as $tag=>$data ) : ?>

                <tr>
                    <td>
                        <code><?php echo esc_attr("[$tag]"); ?></code>
                    </td>

                    <td>
                        <?php if ( isset($data['use']) && $data['use'] ) : ?>
                            <span><?php echo esc_attr($data['use']); ?></span>
                        <?php else : ?>
                            <?php _e('NULL', MUAUTH_DOMAIN); ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ( isset($data['params']) && $data['params'] ) : ?>
                            <?php foreach ( $data['params'] as $param=>$use ) : ?>
                                <li>
                                    <code><?php echo esc_attr($param); ?></code>:
                                    <span><?php echo esc_attr($use); ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <?php _e('NULL', MUAUTH_DOMAIN); ?>
                        <?php endif; ?>
                    </td>
                </tr>

            <?php endforeach; ?>
        </table>

        <p><?php _e('See <a href="https://codex.wordpress.org/Shortcode">Shortcode</a> documentation if you need help with shortcodes.', MUAUTH_DOMAIN); ?></p>

        <?php
    }
}

/*
Array
(
    [muauth_login_url] => loginUrl
    [muauth_lost_password_url] => lostPasswordUrl
    [muauth_register_url] => registerUrl
    [muauth_activation_url] => activationUrl
    [muauth_logout_url] => logoutUrl
    [muauth_login_form] => loginForm
)
*/