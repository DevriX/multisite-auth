<?php global $_auth_site;
// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);
?>

<?php do_action( 'muauth_login_before_content' ); ?>

<div class="muauth-wrap">

    <?php do_action( 'muauth_template_errors' ); ?>

    <form method="post" action="<?php echo muauth_current_url(array('remove'=>array('redirect_to','reauth','loggedout','login','submit','already_active'))); ?>">

        <div class="muauth-form">

            <?php do_action( 'muauth_login_before_login' ); ?>

            <p class="form-section<?php echo muauth_has_errors('login') ? ' has-errors' : ''; ?>">
                <label for="login"><?php _e( 'Username or Email Address:', MUAUTH_DOMAIN ); ?></label>
                <input type="text" name="login" id="login" value="<?php muauth_old( 'login' ); ?>" tabindex="<?php muauth_tabindex(); ?>" />

                <?php if ( muauth_has_errors('login') ) : ?>
                    <?php muauth_print_error( 'login' ); ?>
                <?php endif; ?>
            </p>

            <?php do_action( 'muauth_login_before_password' ); ?>

            <p class="form-section<?php echo muauth_has_errors('password') ? ' has-errors' : ''; ?>">
                <label for="password" style="display:flex">
                    <?php _e( 'Password:', MUAUTH_DOMAIN ); ?>
                    <a href="<?php echo muauth_get_lostpassword_url(null,$_auth_site->blog_id); ?>" style="margin-left:auto">Forgot?</a>
                </label>
                <input type="password" name="password" id="password" tabindex="<?php muauth_tabindex(); ?>" />

                <?php if ( muauth_has_errors('password') ) : ?>
                    <?php muauth_print_error( 'password' ); ?>
                <?php endif; ?>
            </p>

            <?php do_action( 'muauth_login_before_remember' ); ?>

            <p class="form-section<?php echo muauth_has_errors('remember') ? ' has-errors' : ''; ?>">
                <label for="remember">
                    <input type="checkbox" name="remember" id="remember" <?php checked(muauth_old('remember', 1), 'on'); ?> tabindex="<?php muauth_tabindex(); ?>" />
                    <span><?php _e( 'Remember Me', MUAUTH_DOMAIN ); ?></span>
                </label>
            </p>

            <?php do_action( 'muauth_login_before_submit' ); ?>

            <p class="form-section">
                <?php do_action( 'muauth_login_form_data' ); ?>
                <input type="submit" name="submit" value="<?php _e('Submit', MUAUTH_DOMAIN); ?>" tabindex="<?php muauth_tabindex(); ?>" />
            </p>

            <?php do_action( 'muauth_login_before_links' ); ?>

            <li><a href="<?php echo muauth_get_lostpassword_url('', $_auth_site->blog_id); ?>"><?php _e('Lost Password?', MUAUTH_DOMAIN); ?></a></li>

            <?php if ( muauth_is_component_active('activation') ) : ?>
                <li><a href="<?php echo muauth_get_activation_url('', $_auth_site->blog_id); ?>"><?php _e('Activate my Account', MUAUTH_DOMAIN); ?></a></li>
            <?php endif; ?>
            
            <?php if ( muauth_registration_on() ) : ?>
                <li><a href="<?php echo muauth_get_register_url(null,$_auth_site->blog_id); ?>"><?php printf(__('Sign up for a new account on "%s"', MUAUTH_DOMAIN), $_auth_site->blogname); ?></a></li>
            <?php endif; ?>

            <?php do_action( 'muauth_login_after_fields' ); ?>
            
        </div>

    </form>

    <?php do_action( 'muauth_login_after_form' ); ?>

</div>

<?php do_action( 'muauth_login_after_content' ); ?>