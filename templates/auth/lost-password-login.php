<?php global $_auth_site;
// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);
?>

<?php if ( !isset( $_GET['sent'] ) ) : ?>

    <?php do_action( 'muauth_lostpassword_before_help_text' ); ?>

    <p><?php _e('Enter your username or email address below and we will send you a password-reset link.', MUAUTH_DOMAIN); ?></p>

    <?php do_action( 'muauth_lostpassword_before_login' ); ?>

    <p class="form-section<?php echo muauth_has_errors('login') ? ' has-errors' : ''; ?>">
        <label for="login"><?php _e( 'Username or Email Address:', MUAUTH_DOMAIN ); ?></label>
        <input type="text" name="login" id="login" value="<?php muauth_old( 'login' ); ?>" tabindex="<?php muauth_tabindex(); ?>" />

        <?php if ( muauth_has_errors('login') ) : ?>
            <?php muauth_print_error( 'login' ); ?>
        <?php endif; ?>
    </p>

    <?php do_action( 'muauth_lostpassword_before_submit' ); ?>

    <p class="form-section">
        <?php do_action( 'muauth_lostpassword_form_data' ); ?>
        <input type="submit" name="submit" value="<?php _e('Submit', MUAUTH_DOMAIN); ?>" tabindex="<?php muauth_tabindex(); ?>" />
    </p>

    <?php do_action( 'muauth_lostpassword_before_links' ); ?>

    <li><a href="<?php echo add_query_arg(array('stage'=>2), muauth_get_lostpassword_url(null,$_auth_site->blog_id)); ?>"><?php _e('I have a password-reset code', MUAUTH_DOMAIN); ?></a></li>
    <li><a href="<?php echo muauth_get_login_url(null,$_auth_site->blog_id); ?>"><?php _e('Login', MUAUTH_DOMAIN); ?></a></li>

    <?php if ( muauth_is_component_active('activation') ) : ?>
        <li><a href="<?php echo muauth_get_activation_url(null,$_auth_site->blog_id); ?>"><?php _e('Activate my Account', MUAUTH_DOMAIN); ?></a></li>
    <?php endif; ?>

    <?php if ( muauth_registration_on() ) : ?>
        <li><a href="<?php echo muauth_get_register_url(null,$_auth_site->blog_id); ?>">
            <?php _e(sprintf('Sign up for a new account on "%s"',$_auth_site->blogname), MUAUTH_DOMAIN); ?>
        </a></li>
    <?php endif; ?>

    <?php do_action( 'muauth_lostpassword_after_fields' ); ?>

<?php else : ?>

    <li><a href="<?php echo add_query_arg(array('stage'=>2), muauth_get_lostpassword_url(null,$_auth_site->blog_id)); ?>"><?php _e('Enter password-reset code', MUAUTH_DOMAIN); ?></a></li>
    <li><a href="<?php echo muauth_get_login_url(null,$_auth_site->blog_id); ?>"><?php _e('Login', MUAUTH_DOMAIN); ?></a></li>

<?php endif; ?>