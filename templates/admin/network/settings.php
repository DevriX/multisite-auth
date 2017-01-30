<?php global $muauth, $muauth_raw_components;
// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);
?>

<form method="post" action="settings.php?page=mu-auth">

    <div class="section">

        <h3><?php _e('Components', MUAUTH_DOMAIN); ?></h3>

        <p><?php printf(__('Choose which components to process by %s plugin:', MUAUTH_DOMAIN), MUAUTH_NAME); ?></p>

        <?php if ( $muauth_raw_components && is_array($muauth_raw_components) ) : ?>

            <?php foreach ( $muauth_raw_components as $component=>$about ) : ?>

                <label>
                    <input type="checkbox" name="components[]" value="<?php echo esc_attr($component); ?>" <?php checked(muauth_is_component_active($component)); ?> />
                    <?php echo esc_attr($about); ?>
                </label><br/>

            <?php endforeach; ?>

        <?php endif; ?>

        <p><em><?php _e('Unchecked components will not be redirected, instead will be processed by WordPress.', MUAUTH_DOMAIN); ?></em></p>

    </div>

    <p></p>

    <div class="section">
        
        <h3><?php _e('Auth handler site', MUAUTH_DOMAIN); ?></h3>

        <p><?php _e('Which blog shall we use to handle all the network authentication?', MUAUTH_DOMAIN); ?></p>

        <select name="auth_blog">
        
            <option value="0" <?php selected(!$muauth->auth_blog_id); ?>><?php _e( '&mdash; Select &mdash;', MUAUTH_DOMAIN ); ?></option>
            
            <?php foreach ( get_sites() as $blog ) : ?>
                
                <option value="<?php echo $blog->blog_id; ?>" <?php selected($blog->blog_id, $muauth->auth_blog_id); ?>>
                    <?php echo get_home_url($blog->blog_id); ?>
                </option>

            <?php endforeach; ?>

        </select>

        <p><?php _e('Can\'t find it? Click <a href="site-new.php">here</a> to create it!', MUAUTH_DOMAIN); ?></p>

    </div>

    <p></p>

    <div class="section">
        
        <h3><?php _e('Custom Disable Auth', MUAUTH_DOMAIN); ?></h3>

        <p><?php _e('This setting allows you to disable mu-auth plugin for custom sites, which means it will not force redirect to the main auth portal to perform a login, nor process components for this site.', MUAUTH_DOMAIN); ?></p>

        <p><?php _e('Enter your desired site IDs separated by spaces:', MUAUTH_DOMAIN); ?></p>

        <input type="text" name="custom_disable" size="50" value="<?php echo implode(' ', muauth_get_disabled_sites_ids(1)); ?>">

    </div>

    <?php do_action('muauth_network_settings'); ?>

    <?php wp_nonce_field( 'muauth_nonce', 'muauth_nonce' ); ?>
    <?php submit_button(); ?>

</form>

<?php if ( $muauth->auth_blog_id && !apply_filters( 'muauth_hide_auth_page_settings', true ) ) :
switch_to_blog( $muauth->auth_blog_id ); ?>
    <p><a href="<?php echo admin_url('options-general.php?page=mu-auth'); ?>"><?php _e('Edit auth site settings', MUAUTH_DOMAIN); ?></a></p>
    <?php restore_current_blog(); ?>
<?php endif; ?>