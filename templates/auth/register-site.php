<?php global $muauth, $muauth_signupfor, $muauth_index, $current_site, $_auth_site;
// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);
?>

<?php do_action( 'muauth_register_site_before_content' ); ?>

<?php do_action( 'muauth_register_before_sitename' ); ?>

<p class="form-section site-name<?php echo muauth_has_errors('sitename') ? ' has-errors' : ''; ?>">
    <label for="sitename">
        <?php is_subdomain_install() ? _e( 'Site Domain:', MUAUTH_DOMAIN ) : _e( 'Site Name:', MUAUTH_DOMAIN ); ?>
    </label>

    <?php if ( is_subdomain_install() ) : ?>
        <?php do_action( 'muauth_register_domain_protocol' ); ?>
        <input type="text" class="site-name" name="sitename" id="sitename" value="<?php muauth_old( 'sitename' ); ?>" tabindex="<?php muauth_tabindex(); ?>" autocapitalize="none" autocorrect="off" maxlength="60" />.
        <?php echo preg_replace('|^www\.|', '', $current_site->domain); ?>
    <?php else : ?>
        <?php do_action( 'muauth_register_domain_protocol' ); ?>
        <span class="blogprefix"><?php echo $current_site->domain . $current_site->path; ?></span>
        <input type="text" class="site-name" name="sitename" id="sitename" value="<?php muauth_old( 'sitename' ); ?>" tabindex="<?php muauth_tabindex(); ?>" autocapitalize="none" autocorrect="off" maxlength="60" />
    <?php endif; ?>

    <?php if ( muauth_has_errors('sitename') ) : ?>
        <?php muauth_print_error( 'sitename' ); ?>
    <?php endif; ?>
</p>

<?php do_action( 'muauth_register_before_sitetitle' ); ?>

<p class="form-section<?php echo muauth_has_errors('sitetitle') ? ' has-errors' : ''; ?>">
    <label for="sitetitle"><?php _e( 'Site Title:', MUAUTH_DOMAIN ); ?></label>
    <input type="text" name="sitetitle" id="sitetitle" value="<?php muauth_old( 'sitetitle' ); ?>" tabindex="<?php muauth_tabindex(); ?>" maxlength="200" />

    <?php if ( muauth_has_errors('sitetitle') ) : ?>
        <?php muauth_print_error( 'sitetitle' ); ?>
    <?php endif; ?>
</p>

<?php do_action( 'muauth_register_before_privacy' ); ?>

<p class="form-section">
    <label for=""><?php _e( 'Privacy:', MUAUTH_DOMAIN ); ?></label>

    <label class="allow-index">
        <input type="checkbox" name="index" <?php checked((bool) $muauth_index); ?> tabindex="<?php muauth_tabindex(); ?>" />
        <?php _e( 'Allow search engines to index this site.', MUAUTH_DOMAIN ); ?>
    </label>
</p>

<?php do_action( 'muauth_register_before_submit' ); ?>

<p class="form-section">
    <?php do_action( 'muauth_register_form_data' ); ?>
    <input type="submit" name="submit" value="<?php _e('Signup', MUAUTH_DOMAIN); ?>" tabindex="<?php muauth_tabindex(); ?>" />
</p>

<?php do_action( 'muauth_register_before_links' ); ?>

<li><a href="<?php echo muauth_get_lostpassword_url(null,$_auth_site->blog_id); ?>"><?php _e('Lost Password?', MUAUTH_DOMAIN); ?></a></li>
<li><a href="<?php echo muauth_get_login_url(null,$_auth_site->blog_id); ?>"><?php _e('Login', MUAUTH_DOMAIN); ?></a></li>

<?php if ( muauth_is_component_active('activation') ) : ?>
    <li><a href="<?php echo muauth_get_activation_url(null,$_auth_site->blog_id); ?>"><?php _e('Activate my Account', MUAUTH_DOMAIN); ?></a></li>
<?php endif; ?>

<?php do_action( 'muauth_register_after_links' ); ?>
