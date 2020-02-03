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
add_filter( 'comment_row_actions', __NAMESPACE__ . '\comment_row_actions', 10, 2 );
add_action( 'admin_footer', __NAMESPACE__ . '\admin_footer' );
add_filter( 'preprocess_comment', __NAMESPACE__ . '\preprocess_comment_data' );

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

	// Return early if this is not the Flagged Posts page.
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

/**
 * Removes the `unapprove` and `approve` row actions and repurposes `reply`
 * for use as part of the resolution workflow.
 *
 * @param array      $actions Array of comment actions.
 * @param WP_Comment $comment The comment object.
 */
function comment_row_actions( $actions, $comment ) {

	// Return early if this is not the Flagged Posts page.
	if ( ! is_flagged_posts_page() ) {
		return;
	}

	// Return early if the comment is not of the `flagged_content` type.
	if ( 'flagged_content' !== $comment->comment_type ) {
		return;
	}

	// Unset `unapprove` and`approve` actions, not applicable in this case.
	unset( $actions['unapprove'] );
	unset( $actions['approve'] );

	// Modify the default `reply` action to use as a "Resolve" button.
	$actions['reply'] = sprintf(
		'<button type="button" onclick="window.commentReply && commentReply.open(\'%s\',\'%s\');" class="vim-r button-link hide-if-no-js" aria-label="%s">%s</button>',
		$comment->comment_ID,
		$comment->comment_post_ID,
		esc_attr__( 'Resolve this flagged content' ),
		__( 'Resolve' )
	);

	return $actions;
}

/**
 * Add a checkbox to the comment list table reply form for resolving flags.
 */
function admin_footer() {

	// Return early if this is not the Flagged Posts page.
	if ( ! is_flagged_posts_page() ) {
		return;
	}
	?>
	<script type="text/javascript">
		function addResolveCheckbox() {
			const checkbox = document.createElement( 'input' );
			const label = document.createElement( 'label' );
			const replyContainer = document.getElementById( 'replycontainer' );

			checkbox.type    = 'checkbox';
			checkbox.name    = 'resolve_flag';
			checkbox.id      = 'resolve-flag';
			checkbox.checked = true;

			label.htmlFor = 'resolve-flag';
			label.appendChild( document.createTextNode( 'This resolves the concern' ) );

			replyContainer.appendChild( checkbox );
			replyContainer.appendChild( label );
		};

		addResolveCheckbox();
	</script>
	<?php
}

/**
 * If the comment is about bad content, save it as a `flagged_content` type.
 *
 * @param array $commentdata Submitted comment data.
 * @return array $commentdata Modified comment data.
 */
function preprocess_comment_data( $commentdata ) {

	// Nonce verification is not necessary for the comment form.
	if ( is_user_logged_in() && isset( $_POST['resolve_flag'] ) && isset( $commentdata['comment_parent'] ) ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
		wp_update_comment( array(
			'comment_ID'   => $commentdata['comment_parent'],
			'comment_type' => 'resolved',
		) );
	}

	return $commentdata;
}
