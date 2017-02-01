<?php

// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);

// class instance
$core = MUAUTH\Includes\Core\Core::instance();

/** prepare query **/

// start buffer
add_action('init', array($core, 'startBuffer'), 0);        
// initialize current user hooks
add_action('init', array($core, 'initUserHooks'));
// initialize current user hooks wp
add_action('wp', array($core, 'wpUserHooks'));
// remove auth blog from the disabled sites
add_filter('muauth_get_disabled_sites_ids', array($core, 'removeAuthBlogFromDisabledSites'), 0);
// filter titles and page content for auth
add_action('muauth_parse_query_handle_component', array($core, 'contentFilters'), 11);
// dispatch auth action redirect
add_action('init', array($core, 'dispatchAuthActionRedirect'), 11);

/** templates =============**/

// parse notices
add_action('muauth_template_errors', array($core, 'parseTempalteErrors'));
// parse HTML in errors, e.g useful links
add_filter('muauth_error_display', 'wp_specialchars_decode');

/** login =================**/

// parse basic template
add_action('muauth_parse_login_content', array($core, 'parseLoginTemplate'));
// parse request query
add_action('muauth_login_form_data', array($core, 'parseLoginRequestQuery'));
// parse current component field
add_action('muauth_login_form_data', array($core, 'parseCurrentComponentField'));
// simple login form
add_action('muauth_login_form', array($core, 'parseSimpleLoginTemplate'), 10, 3);
// parse necessary data
add_action('muauth_simple_login_form_data', array($core, 'parseSimpleLoginRequestQuery'));
// parse current component field
add_action('muauth_simple_login_form_data', array($core, 'parseSimpleLoginCurrentComponentField'));

// handle login data
add_action('init_auth_blog', array($core, 'validatePost'), 11);

/** lost password =========**/

// parse template
add_action('muauth_parse_lost-password_content', array($core, 'parseLostPasswordTemplate'));
// parse necessary data
add_action('muauth_lostpassword_form_data', array($core, 'parseLostPasswordRequestQuery'));
// parse current component field
add_action('muauth_lostpassword_form_data', array($core, 'parseCurrentComponentField'));

/** activation ============**/

// load template
add_action('muauth_parse_activation_content', array($core, 'parseActivationTemplate'));
// parse necessary data
add_action('muauth_activation_form_data', array($core, 'parseActivationRequestQuery'));
// parse current component field
add_action('muauth_activation_form_data', array($core, 'parseCurrentComponentField'));
// email the user their newly generated password upon activation
add_action('muauth_validate_activation_GET_success_pre_redirect', array($core, 'activationWelcome'), 10, 4);
// handle user/site meta upon activation
add_action('muauth_validate_activation_GET_success', array($core, 'activationMeta'));

/** register ==============**/

// handle register view to play by registration rules
add_action('muauth_parse_query_handle_component_register', array($core, 'parseQueryHandleRegister'));
// load template
add_action('muauth_parse_register_content', array($core, 'parseRegisterTemplate'));
// parse necessary data
add_action('muauth_register_form_data', array($core, 'parseRegisterRequestQuery'));
// parse current component field
add_action('muauth_register_form_data', array($core, 'parseCurrentComponentField'));
// add help text to site-register when settings allow only logged-in blogs
add_action('muauth_init_logged_in', array($core, 'blogRegisterLoggedInHelpText'));
// confirm user signup
add_action('muauth_signup_user', array($core, 'postSignupUser'), 0, 2);
// confirm new blog created for current user
add_action('muauth_create_blog', array($core, 'postCreateBlog'), 0, 2);
// confirm user with blog signup
add_action('muauth_signup_blog', array($core, 'postSignupBlog'), 0, 4);

/** logout ================**/

// handle
add_action('muauth_parse_query_handle_component_logout', array($core, 'parseQueryHandleLogout'));

/** general auth **/
// validate request when no POST is set (i.e pre form submit)
add_action('muauth_validate_post_no_post_component', array($core, 'lpCatchValidatePostNoPostComponent'));
// add norobots meta
add_action('muauth_parse_query_handle_component', array($core, 'noRobots'));

/** handle logged-in user components **/
add_action('muauth_wp_logged_in', array($core, 'handleLoggedInComponent'), 12);

/** handle invalid-unregistered login action **/
add_action('muauth_disptch_auth_action__redirect', array($core, 'redirectLoginUnknownAction'), 0);

/** other filters =========**/

// unslash the field values
add_filter('muauth_old', 'wp_unslash');

if ( is_subdomain_install() ) :

/**
  * muauth_redirect uses wp_safe_redirect to redirect users safely to
  * auth areas (e.g admin, reset password). 
  * The auth subdomain will be treated as unsafe therefore the redirect
  * will faile and fall in a loop, so let's append the auth blog hostname
  * to the safe list
  *
  * @see https://core.trac.wordpress.org/ticket/30598
  */

add_filter('allowed_redirect_hosts', 'muauth_append_auth_domain_as_safe');

// fix returned blog domain when validating creating blog on the auth blog
add_filter('muauth_validate_register_blog_validation', array($core, 'removeAuthDomainAsHost'));

endif;

/** rewrite WordPress auth URLs **/

if ( muauth_is_component_active('register') ) {
    // filter register URL
    add_filter('register_url', array($core, 'registerUrl'));
}

if ( muauth_is_component_active('login') ) {
    // filter login URL
    add_filter('login_url', array($core, 'loginUrl'), 10, 3);
}

if ( muauth_is_component_active('logout') ) {
    // filter logout URL
    add_filter('logout_url', array($core, 'logoutUrl'), 10, 3);
}

/**
  * Facilitates extending the above filters
  */
do_action( 'muauth_filters_loaded', $core );