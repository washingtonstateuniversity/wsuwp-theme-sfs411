<?php
/**
 * Handling for the Stale Posts dashboard.
 *
 * @package sfs411
 */

namespace SFS411\Dashboard\Stale_Content;

add_action( 'admin_menu', __NAMESPACE__ . '\add_stale_posts_page' );
add_filter( 'submenu_file', __NAMESPACE__ . '\stale_posts_submenu_file' );

/**
 * Adds the Stale Posts page the the Knowledge Base menu.
 */
function add_stale_posts_page() {
	add_submenu_page(
		'edit.php?post_type=knowledge_base',
		'Stale Posts',
		'Stale Posts',
		'manage_options',
		'edit.php?post_type=knowledge_base&stale',
		'',
		2
	);
}

/**
 * Filters the file of the Stale Posts menu item.
 *
 * @param string $submenu_file The submenu file.
 * @return string The submenu file.
 */
function stale_posts_submenu_file( $submenu_file ) {
	if ( 'edit-knowledge_base' === get_current_screen()->id && isset( $_GET['stale'] ) ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
		$submenu_file = 'edit.php?post_type=knowledge_base&stale';
	}

	return $submenu_file;
}
