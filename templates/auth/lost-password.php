<?php global $muauth_stage;
// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);
?>

<?php do_action( 'muauth_lostpassword_before_content' ); ?>

<div class="muauth-wrap">

    <?php do_action( 'muauth_template_errors' ); ?>

    <form method="post" action="<?php echo muauth_current_url(array('remove'=>array('login','submit','stage','sent','code','key','action'))); ?>">

        <div class="muauth-form">

            <?php switch ( $muauth_stage ) :

                case 2: ?>
                    
                    <?php \MUAUTH\Includes\Core\Plugin::loadTemplate( 'auth/lost-password-code.php' ); ?>

                <?php break; ?>

                <?php case 3: ?>
                    
                    <?php \MUAUTH\Includes\Core\Plugin::loadTemplate( 'auth/lost-password-reset.php' ); ?>

                <?php break; ?>

                <?php default: ?>
                    
                    <?php \MUAUTH\Includes\Core\Plugin::loadTemplate( 'auth/lost-password-login.php' ); ?>

                <?php break; ?>


            <?php endswitch; ?>
            
        </div>

    </form>

    <?php do_action( 'muauth_lostpassword_after_form' ); ?>

</div>

<?php do_action( 'muauth_lostpassword_after_content' ); ?>