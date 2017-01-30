<?php global $_auth_site;
// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);
?>

<?php do_action( 'muauth_lostpassword_before_lostpassword' ); ?>

<p class="form-section<?php echo muauth_has_errors('password1') ? ' has-errors' : ''; ?>">
    <label for="password1"><?php _e( 'Enter New Password:', MUAUTH_DOMAIN ); ?></label>
    <input type="password" name="password1" id="password1" tabindex="<?php muauth_tabindex(); ?>" />

    <?php if ( muauth_has_errors('password1') ) : ?>
        <?php muauth_print_error( 'password1' ); ?>
    <?php endif; ?>
</p>

<p class="form-section<?php echo muauth_has_errors('password2') ? ' has-errors' : ''; ?>">
    <label for="password2"><?php _e( 'Confirm New Password:', MUAUTH_DOMAIN ); ?></label>
    <input type="password" name="password2" id="password2" tabindex="<?php muauth_tabindex(); ?>" />

    <?php if ( muauth_has_errors('password2') ) : ?>
        <?php muauth_print_error( 'password2' ); ?>
    <?php endif; ?>
</p>

<?php do_action( 'muauth_lostpassword_before_submit' ); ?>

<p class="form-section">
    <?php do_action( 'muauth_lostpassword_form_data' ); ?>
    <input type="submit" name="submit" value="<?php _e('Submit', MUAUTH_DOMAIN); ?>" tabindex="<?php muauth_tabindex(); ?>" />
</p>

<?php do_action( 'muauth_lostpassword_before_links' ); ?>

<li><a href="<?php echo muauth_get_login_url(null,$_auth_site->blog_id); ?>"><?php _e('Login', MUAUTH_DOMAIN); ?></a></li>

<?php do_action( 'muauth_lostpassword_after_fields' ); ?>