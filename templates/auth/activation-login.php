<?php global $_auth_site;
// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);
?>

<?php if ( !isset( $_GET['sent'] ) ) : ?>

    <?php do_action( 'muauth_activation_before_help_text' ); ?>

    <p><?php _e('Enter your username or email address below and we will send you an activation link.', MUAUTH_DOMAIN); ?></p>

    <?php do_action( 'muauth_activation_before_login' ); ?>

    <p class="form-section<?php echo muauth_has_errors('login') ? ' has-errors' : ''; ?>">
        <label for="login"><?php _e( 'Username or Email Address:', MUAUTH_DOMAIN ); ?></label>
        <input type="text" name="login" id="login" value="<?php muauth_old('login'); ?>" tabindex="<?php muauth_tabindex(); ?>" />

        <?php if ( muauth_has_errors('login') ) : ?>
            <?php muauth_print_error( 'login' ); ?>
        <?php endif; ?>
    </p>

    <?php do_action( 'muauth_activation_before_submit' ); ?>

    <p class="form-section">
        <?php do_action( 'muauth_activation_form_data' ); ?>
        <input type="submit" name="submit" value="<?php _e('Submit', MUAUTH_DOMAIN); ?>" tabindex="<?php muauth_tabindex(); ?>" />
    </p>

    <?php do_action( 'muauth_activation_before_links' ); ?>

    <li><a href="<?php echo add_query_arg(array('stage'=>2), muauth_get_activation_url(null,$_auth_site->blog_id)); ?>"><?php _e('I have an activation code', MUAUTH_DOMAIN); ?></a></li>
    <li><a href="<?php echo muauth_get_login_url(null,$_auth_site->blog_id); ?>"><?php _e('Login', MUAUTH_DOMAIN); ?></a></li>

    <?php do_action( 'muauth_activation_after_fields' ); ?>

<?php else : ?>

    <li><a href="<?php echo add_query_arg(array('stage'=>2), muauth_get_activation_url(null,$_auth_site->blog_id)); ?>"><?php _e('Enter activation code', MUAUTH_DOMAIN); ?></a></li>
    <li><a href="<?php echo muauth_get_login_url(null,$_auth_site->blog_id); ?>"><?php _e('Login', MUAUTH_DOMAIN); ?></a></li>

<?php endif; ?>