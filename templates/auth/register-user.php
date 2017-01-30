<?php global $muauth, $muauth_signupfor, $_auth_site;
// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);
?>

<?php do_action( 'muauth_register_before_username' ); ?>

<p class="form-section<?php echo muauth_has_errors('username') ? ' has-errors' : ''; ?>">
    <label for="username"><?php _e( 'Username:', MUAUTH_DOMAIN ); ?></label>
    <input type="text" name="username" id="username" value="<?php muauth_old( 'username' ); ?>" tabindex="<?php muauth_tabindex(); ?>" autocapitalize="none" autocorrect="off" maxlength="60" />

    <?php if ( muauth_has_errors('username') ) : ?>
        <?php muauth_print_error( 'username' ); ?>
    <?php endif; ?>

    <?php _e('(Must be at least 4 characters, letters and numbers only.)', MUAUTH_DOMAIN); ?>
</p>

<?php do_action( 'muauth_register_before_email' ); ?>

<p class="form-section<?php echo muauth_has_errors('email') ? ' has-errors' : ''; ?>">
    <label for="email"><?php _e( 'Email Address:', MUAUTH_DOMAIN ); ?></label>
    <input type="email" name="email" id="email" value="<?php muauth_old( 'email' ); ?>" tabindex="<?php muauth_tabindex(); ?>" maxlength="200" />

    <?php if ( muauth_has_errors('email') ) : ?>
        <?php muauth_print_error( 'email' ); ?>
    <?php endif; ?>

    <?php _e('We send your registration email to this address. (Double-check your email address before continuing.)', MUAUTH_DOMAIN) ?>
</p>

<?php if ( 'all' === $muauth->registration ) : ?>

    <?php do_action( 'muauth_register_before_signup_for' ); ?>

    <p class="form-section<?php echo muauth_has_errors('signupfor') ? ' has-errors' : ''; ?>">
        <input type="radio" name="signupfor" value="blog" id="signupfor_blog" <?php checked( $muauth_signupfor, 'blog' ); ?> tabindex="<?php muauth_tabindex(); ?>" />
        <label for="signupfor_blog" class="inline"><?php _e( 'Gimme a Site!', MUAUTH_DOMAIN ); ?></label>
        <br/>

        <input type="radio" name="signupfor" value="user" id="signupfor_user" <?php checked( $muauth_signupfor, 'user' ); ?> tabindex="<?php muauth_tabindex(); ?>"/>
        <label for="signupfor_user" class="inline"><?php _e( 'Just a username, please.', MUAUTH_DOMAIN ); ?></label>

        <?php if ( muauth_has_errors('signupfor') ) : ?>
            <?php muauth_print_error( 'signupfor' ); ?>
        <?php endif; ?>
    </p>

<?php endif; ?>

<?php do_action( 'muauth_register_before_submit' ); ?>

<p class="form-section">
    <?php do_action( 'muauth_register_form_data' ); ?>
    <input type="submit" name="submit" value="<?php _e('Signup', MUAUTH_DOMAIN); ?>" tabindex="<?php muauth_tabindex(); ?>" />
</p>

<?php do_action( 'muauth_register_before_links' ); ?>

<li><a href="<?php echo muauth_get_lostpassword_url(null,$_auth_site->blog_id); ?>"><?php _e('Lost Password?', MUAUTH_DOMAIN); ?></a></li>
<li><a href="<?php echo muauth_get_login_url(null,$_auth_site->blog_id); ?>"><?php _e('Login', MUAUTH_DOMAIN); ?></a></li>

<?php if ( muauth_is_component_active('activation') ) : ?>
    <li>
        <a href="<?php echo muauth_get_activation_url(null,$_auth_site->blog_id); ?>"><?php _e('Activate my Account', MUAUTH_DOMAIN); ?></a>
    </li>
<?php endif; ?>

<?php do_action( 'muauth_register_after_links' ); ?>