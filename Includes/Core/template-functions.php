<?php

// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);

function muauth_errors( $group='default' ) {
    global $muauth_errors;

    if ( !trim($group) )
        $group = 'group';

    if ( !isset( $muauth_errors->$group ) ) {
        $muauth_errors->$group = new \WP_Error;
    }

    return $muauth_errors->$group;
}

function muauth_add_error( $code='', $message='', $data='error', $group='default' ) {
	$muauth_errors = muauth_errors($group);

	return $muauth_errors->add( $code, $message, $data );
}

function muauth_remove_error( $code='', $group='default' ) {
	$muauth_errors = muauth_errors($group);

	return $muauth_errors->remove( $code );
}

function muauth_has_errors( $code='', $group='default' ) {
	$muauth_errors = muauth_errors($group);

	$codes = $muauth_errors->get_error_codes();

	if ( $code ) {
		$has_errors = $codes && in_array($code, $codes);
	} else {
		$has_errors = (bool) $codes;
	}

	return apply_filters( 'muauth_has_errors', $has_errors, $code );
}

function muauth_template_errors($exclude=array(), $group='default') {
	if ( !muauth_has_errors() )
		return;

	$muauth_errors = muauth_errors($group);

	$codes = array_filter($muauth_errors->get_error_codes(), 'trim');

    if ( $exclude && is_array($exclude) ) :
    foreach ( $codes as $i=>$code ) {
        if ( in_array($code, $exclude) ) {
            unset( $codes[$i] );
        }
    }
    endif;

	?>

	<ul class="muauth-errors">
	<?php foreach ( $codes as $code ) : ?>
		<?php $errors = array_unique($muauth_errors->get_error_messages( $code ));?>
		<?php if ( !$errors ) continue; ?>
		<?php foreach ( $errors as $error ) : ?>
			<li class="<?php echo esc_attr($muauth_errors->get_error_data($code)); ?>">
				<span><?php echo apply_filters( 'muauth_error_display', esc_attr($error) ); ?></span>
			</li>
		<?php endforeach; ?>
	<?php endforeach; ?>
	</ul>

	<?php
}

function muauth_print_error( $code, $group='default' ) {
    if ( !muauth_has_errors($code) )
        return;

    $muauth_errors = muauth_errors($group);
    $errors = $muauth_errors->get_error_messages( $code );
    $errors = array_unique($errors);

    if ( $errors ) {
        foreach ( $errors as $error ) {
            printf(
                '<span class="inline-error %s">%s</span>',
                esc_attr($muauth_errors->get_error_data($code)),
                apply_filters( 'muauth_error_display', esc_attr($error) )
            );
        }
    }
}


if ( !function_exists('muauth_old') ) :
function muauth_old( $name, $return=null, $method='request' ) {

    switch ( strtolower($method) ) {
        case 'get':
            $data = $_GET;
            break;

        case 'post':
            $data = $_POST;
            break;

        default:
            $data = $_REQUEST;
            break;
    }

    $value = isset($data[$name]) ? $data[$name] : null;

    if ( is_string( $value ) ) {
        $value = esc_attr( $value );
    }

    if ( $return ) {
        return apply_filters( 'muauth_old', $value, $name, $method );
    } else {
        echo apply_filters( 'muauth_old', $value, $name, $method );
    }
}
endif;

function muauth_current_url($args=array(), $strip_all=0) {
    $query = $_SERVER['REQUEST_URI'];

    if ( $strip_all ) {
        if ( $_REQUEST ) {
            $query = remove_query_arg(array_keys($_REQUEST), $query);
        }
    } else if ( $args && is_array($args) ) {
        if ( !empty( $args['add'] ) ) {
            $query = add_query_arg( $args['add'], $query );
        }

        if ( !empty( $args['remove'] ) ) {
            $query = remove_query_arg( $args['remove'], $query );
        }
    }

    return apply_filters('muauth_current_url', $query, $args, $strip_all);
}

function muauth_login_form( $redirect_to='', $blog_id=0, $unique_id='login_form' ) {
    do_action( 'muauth_login_form', $redirect_to, $blog_id, $unique_id );
}

function muauth_tabindex( $ret=false ) {
    global $muauth;
    $muauth->tabindex++;
    $muauth->tabindex = apply_filters( 'muauth_tabindex', $muauth->tabindex );

    if ( $ret ) {
        return $muauth->tabindex;
    } else {
        echo $muauth->tabindex;
    }
}