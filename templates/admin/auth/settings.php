<?php global $auth_page;
// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);
?>


<div class="wrap">
    
    <h2><?php _e( sprintf( 'Auth Settings', MUAUTH_NAME ), MUAUTH_DOMAIN ); ?></h2>
    <p></p>

    <form method="post">

        <div class="section">

            <h3><?php _e('Auth page', MUAUTH_DOMAIN); ?></h3>

            <p><label for="auth_page"><?php _e('Select a page for the authentication:', MUAUTH_DOMAIN); ?></label></p>

            <select name="auth_page" id="auth_page">
                <option value="0" <?php selected(empty($auth_page->ID)); ?>><?php _e( '&mdash; Select &mdash;', MUAUTH_DOMAIN ); ?></option>

                <?php $pages = get_posts( 'post_type=page&posts_per_page=-1&post_status=publish' ); if ( $pages ) : ?>

                    <?php foreach ( $pages as $page ) : ?>

                        <option value="<?php echo $page->ID; ?>" <?php selected(!empty($auth_page->ID) && $page->ID==$auth_page->ID); ?>>
                            <?php echo esc_attr( $page->post_title ); ?>
                        </option>

                    <?php endforeach; ?>

                <?php endif; ?>

            </select>

            <p><?php _e('Or click <a href="post-new.php?post_type=page" target="_blank">here</a> to add a new page.', MUAUTH_DOMAIN); ?></p>

        </div>

        <?php wp_nonce_field( 'muauth_nonce', 'muauth_nonce' ); ?>
        <?php submit_button(); ?>

    </form>

</div>