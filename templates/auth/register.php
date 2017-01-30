<?php global $muauth_stage, $_auth_site;
// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);
?>

<?php do_action( 'muauth_register_before_content' ); ?>

<div class="muauth-wrap">

    <?php do_action( 'muauth_template_errors' ); ?>

    <?php if ( isset( $_GET['success'] ) ) : ?>

        <?php do_action( 'muauth_register_success_content' ); ?> 

        <?php if ( !is_user_logged_in() ) : ?>

            <li><a href="<?php echo muauth_get_login_url(null,$_auth_site->blog_id); ?>"><?php _e('Login', MUAUTH_DOMAIN); ?></a></li>

            <?php if ( muauth_is_component_active('activation') ) : ?>
                <li><a href="<?php echo muauth_get_activation_url(null,$_auth_site->blog_id); ?>"><?php _e('Activate my Account', MUAUTH_DOMAIN); ?></a></li>
            <?php endif; ?>

        <?php endif; ?>   

    <?php else : ?>

        <?php do_action( 'muauth_register_before_form' ); ?>

        <form method="post" action="<?php echo muauth_current_url(array('remove'=>array('login','submit','stage','sent','code'))); ?>">

            <div class="muauth-form">

                <?php switch ( $muauth_stage ) :

                    case 2: ?>
                        
                        <?php \MUAUTH\Includes\Core\Plugin::loadTemplate( 'auth/register-site.php' ); ?>

                    <?php break; ?>

                    <?php default: ?>
                        
                        <?php \MUAUTH\Includes\Core\Plugin::loadTemplate( 'auth/register-user.php' ); ?>

                    <?php break; ?>

                <?php endswitch; ?>
                
            </div>

        </form>

        <?php do_action( 'muauth_register_after_form' ); ?>

    <?php endif; ?>

</div>

<?php do_action( 'muauth_register_after_content' ); ?>