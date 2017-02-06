<?php global $muauth_login_form, $_auth_site; $args = $muauth_login_form;
// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);
?>

<div class="muauth-wrap">

    <?php muauth_template_errors(null, $args['unique_id']); ?>

    <form method="post" action="<?php echo muauth_get_login_url( $args['redirect_to'], $args['blog_id'] ); ?>">

        <div class="muauth-form">

            <?php do_action( 'muauth_login_form_before_login', $args ); ?>

            <p class="form-section<?php echo muauth_has_errors('login', $args['unique_id']) ? ' has-errors' : ''; ?>">
                <label for="login"><?php _e( 'Username or Email Address:', MUAUTH_DOMAIN ); ?></label>
                <input type="text" name="login" id="login" value="<?php muauth_old( 'login' ); ?>" tabindex="<?php muauth_tabindex(); ?>" />

                <?php if ( muauth_has_errors('login', $args['unique_id']) ) : ?>
                    <?php muauth_print_error( 'login', $args['unique_id'] ); ?>
                <?php endif; ?>
            </p>

            <?php do_action( 'muauth_login_form_before_password', $args ); ?>

            <p class="form-section<?php echo muauth_has_errors('password', $args['unique_id']) ? ' has-errors' : ''; ?>">
                <label for="password" style="display:flex">
                    <?php _e( 'Password:', MUAUTH_DOMAIN ); ?>
                    <a href="<?php echo muauth_get_lostpassword_url(0,$args['blog_id']); ?>" style="margin-left:auto">Forgot?</a>
                </label>
                <input type="password" name="password" id="password" tabindex="<?php muauth_tabindex(); ?>" />

                <?php if ( muauth_has_errors('password', $args['unique_id']) ) : ?>
                    <?php muauth_print_error( 'password', $args['unique_id'] ); ?>
                <?php endif; ?>
            </p>

            <?php do_action( 'muauth_login_form_before_remember', $args ); ?>

            <p class="form-section">
                <label for="remember">
                    <input type="checkbox" name="remember" id="remember" <?php checked(muauth_old('remember', 1), 'on'); ?> tabindex="<?php muauth_tabindex(); ?>" />
                    <span><?php _e( 'Remember Me', MUAUTH_DOMAIN ); ?></span>
                </label>
            </p>

            <?php do_action( 'muauth_login_form_before_submit', $args ); ?>

            <p class="form-section">
                <?php do_action( 'muauth_simple_login_form_data' ); ?>
                <input type="submit" name="submit" value="<?php _e('Submit', MUAUTH_DOMAIN); ?>" tabindex="<?php muauth_tabindex(); ?>" />
            </p>            
            
        </div>

    </form>

</div>