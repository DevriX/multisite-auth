<?php global $_auth_site;
// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);
?>

<?php do_action( 'muauth_activation_before_help_text' ); ?>

<p><?php _e('To activate your account, please enter the activation code sent to your email, or simply follow the link from the email.', MUAUTH_DOMAIN); ?></p>

<?php do_action( 'muauth_activation_before_code' ); ?>

<p class="form-section<?php echo muauth_has_errors('code') ? ' has-errors' : ''; ?>">
    <label for="code"><?php _e( 'Activation Code:', MUAUTH_DOMAIN ); ?></label>
    <input type="password" name="code" id="code" tabindex="<?php muauth_tabindex(); ?>" />

    <?php if ( muauth_has_errors('code') ) : ?>
        <?php muauth_print_error( 'code' ); ?>
    <?php endif; ?>
</p>

<?php do_action( 'muauth_activation_before_submit' ); ?>

<p class="form-section">
    <?php do_action( 'muauth_activation_form_data' ); ?>
    <input type="submit" name="submit" value="<?php _e('Submit', MUAUTH_DOMAIN); ?>" tabindex="<?php muauth_tabindex(); ?>" />
</p>

<?php do_action( 'muauth_activation_before_links' ); ?>

<li><a href="<?php echo muauth_get_activation_url(null,$_auth_site->blog_id); ?>"><?php _e('Resend code', MUAUTH_DOMAIN); ?></a></li>
<li><a href="<?php echo muauth_get_login_url(null,$_auth_site->blog_id); ?>"><?php _e('Login', MUAUTH_DOMAIN); ?></a></li>

<?php if ( muauth_registration_on() ) : ?>

    <li><a href="<?php echo muauth_get_register_url(null,$_auth_site->blog_id); ?>">
        <?php _e(sprintf('Sign up for a new account on "%s"',$_auth_site->blogname), MUAUTH_DOMAIN); ?>    
    </a></li>

<?php endif; ?>

<?php do_action( 'muauth_activation_after_fields' ); ?>