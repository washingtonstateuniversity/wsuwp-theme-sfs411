<?php
/**
 * Handling for the Flagged Posts dashboard.
 *
 * @package sfs411
 */

namespace SFS411\Dashboard\Flagged_Content;

add_action( 'admin_menu', __NAMESPACE__ . '\add_flagged_posts_page' );
add_filter( 'parent_file', __NAMESPACE__ . '\flagged_posts_parent_file' );
add_filter( 'submenu_file', __NAMESPACE__ . '\flagged_posts_submenu_file' );
add_action( 'adminmenu', __NAMESPACE__ . '\adminmenu' );

/**
 * Adds the Flagged Posts page the the Knowledge Base menu.
 */
function add_flagged_posts_page() {
	add_submenu_page(
		'edit.php?post_type=knowledge_base',
		'Flagged Posts',
		'Flagged Posts',
		'manage_options',
		'edit-comments.php?comment_type=flagged_content&post_type=knowledge_base',
		'',
		1
	);
}

/**
 * Determines if the current page is the Flagged Posts page.
 */
function is_flagged_posts_page() {
	if ( ! isset( $_GET['comment_type'] ) || 'flagged_content' !== $_GET['comment_type'] ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
		return false;
	}

	return true;
}

/**
 * Filters the parent file of the Flagged Posts menu item.
 *
 * @param string $parent_file The parent file.
 * @return string The parent file.
 */
function flagged_posts_parent_file( $parent_file ) {
	if ( is_flagged_posts_page() ) {
		$parent_file = 'edit.php?post_type=knowledge_base';
	}

	return $parent_file;
}

/**
 * Filters the file of the Flagged Posts menu item.
 *
 * @param string $submenu_file The submenu file.
 * @return string The submenu file.
 */
function flagged_posts_submenu_file( $submenu_file ) {
	if ( is_flagged_posts_page() ) {
		$submenu_file = 'edit-comments.php?comment_type=flagged_content&post_type=knowledge_base';
	}

	return $submenu_file;
}

/**
 * Removes the `current` classes and aria attributes from the Comments menu
 * item when Flagged Posts is the current page.
 *
 * Filtering the parent and submenu file doesn't take care of this,
 * because the Flagged Posts page technically is the Comments page,
 * presumably.
 */
function adminmenu() {
	if ( ! is_flagged_posts_page() ) {
		return;
	}
	?>
	<script type="text/javascript">
		function commentsMenuItem() {
			const item = document.getElementById( 'menu-comments' );
			const link = item.querySelector( 'a' );

			item.classList.remove( 'current' );
			link.classList.remove( 'current' );
			link.removeAttribute( 'aria-current' );
		};

		commentsMenuItem();
	</script>
	<?php
}
