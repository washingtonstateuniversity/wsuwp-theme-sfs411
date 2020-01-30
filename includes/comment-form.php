<?php
/**
 * Handling for customizations to the comment form.
 *
 * @package sfs411
 */

namespace SFS411\Comment\Form;

add_action( 'after_setup_theme', __NAMESPACE__ . '\register_html5_support' );

/**
 * Registers HTML5 support for the comment form and comments output.
 */
function register_html5_support() {
	add_theme_support( 'html5', array(
		'comment-form',
		'comment-list',
	) );
}
