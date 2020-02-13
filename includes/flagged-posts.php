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
add_action( 'pre_get_comments', __NAMESPACE__ . '\filter_comments_query' );
add_filter( 'wp_count_comments', __NAMESPACE__ . '\filter_comment_counts', 10, 2 );
add_filter( 'comment_status_links', __NAMESPACE__ . '\flagged_posts_status_links' );
add_filter( 'bulk_actions-edit-comments', __NAMESPACE__ . '\filter_bulk_actions' );
add_filter( 'notify_post_author', __NAMESPACE__ . '\disable_default_notification', 10, 2 );
add_filter( 'notify_moderator', __NAMESPACE__ . '\disable_default_notification', 10, 2 );
add_action( 'comment_post', __NAMESPACE__ . '\comment_post', 10, 3 );

/**
 * Adds the Flagged Posts page the the Knowledge Base menu.
 */
function add_flagged_posts_page() {
	add_submenu_page(
		'edit.php?post_type=knowledge_base',
		'Flagged Posts',
		'Flagged Posts',
		'moderate_comments',
		'edit-comments.php?comment_type=flagged_content',
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
		$submenu_file = 'edit-comments.php?comment_type=flagged_content';
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
		return $actions;
	}

	// Return early if the comment is not of the `flagged_content` type.
	if ( 'flagged_content' !== $comment->comment_type ) {
		return $actions;
	}

	// Unset `unapprove`, `approve`, and `spam` actions.
	unset( $actions['unapprove'] );
	unset( $actions['approve'] );
	unset( $actions['spam'] );

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

		function removeFilterActions() {
			const filterActions = document.getElementById( 'filter-by-comment-type' ).parentNode;

			filterActions.parentNode.removeChild( filterActions );
		}

		addResolveCheckbox();
		removeFilterActions();
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

/**
 * Filters the comments query.
 *
 * For the Flagged Posts dashboard page, this ensures that the only comments
 * shown are those for `knowledge_base` posts. (And the `comment_type` URL
 * parameter ensures only comments of the `flagged_content` type are shown.)
 *
 * For the default Comments dashboard page, this ensures that the only comments
 * show are NOT of the `flagged_content` or `resolved` type.
 *
 * @param WP_Comment_Query The WP_Comment_Query instance.
 */
function filter_comments_query( $query ) {
	if ( ! is_admin() || ! get_current_screen() || 'edit-comments' !== get_current_screen()->base ) {
		return;
	}

	if ( is_flagged_posts_page() ) {
		$query->query_vars['post_type'] = 'knowledge_base';

		if ( isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
			$query->query_vars['comment__in'] = array( absint( $_GET['id'] ) ); // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
		}
	} else {
		$query->query_vars['type__not_in'] = array( 'flagged_content', 'resolved' );
	}
}

/**
 * Filters the comment counts for the status links on the Flagged Posts page.
 *
 * @param array|stdClass $count   An empty array or an object containing comment counts.
 * @param int            $post_id The post ID.
 * @return stdClass Object containing comment counts.
 */
function filter_comment_counts( $count, $post_id ) {

	// Return early if this is not the Flagged Posts page.
	if ( ! is_flagged_posts_page() ) {
		return;
	}

	// Return early if the current view is for a specific post.
	if ( 0 !== $post_id ) {
		return $count;
	}

	$comments_query = new \WP_Comment_Query();

	// Find `flagged_content` comment types on `knowledge_base` posts.
	$args = array(
		'type'      => 'flagged_content',
		'post_type' => 'knowledge_base',
		'count'     => true,
	);

	// Add the argument for trashed comments.
	$trashed_comments_args = array_merge(
		$args,
		array(
			'status' => 'trash',
		),
	);

	// Provide accurate counts for `flagged_content` comment types.
	$count = (object) array(
		'all'            => get_comments( $args ),
		'moderated'      => 0,
		'approved'       => 0,
		'post-trashed'   => 0,
		'trash'          => get_comments( $trashed_comments_args ),
	);

	return $count;
}

/**
 * Modifies the status links for the Flagged Posts page.
 *
 * This removes the "Pending", "Approved", and "Spam" links,
 * and fixes the count for the "Mine" link, since that doesn't
 * seem possible to do through the `wp_count_comments` filter.
 *
 * @param array $status_links Fully-formed comment status links.
 * @return array Modified comment status links.
 */
function flagged_posts_status_links( $status_links ) {

	// Return early if this is not the Flagged Posts page.
	if ( ! is_flagged_posts_page() ) {
		return $status_links;
	}

	unset( $status_links['moderated'] );
	unset( $status_links['approved'] );
	unset( $status_links['spam'] );

	$comments_query = new \WP_Comment_Query();

	$args = array(
		'count'     => true,
		'user_id'   => get_current_user_id(),
	);

	$mine_args = array_merge(
		$args,
		array(
			'type'      => 'flagged_content',
			'post_type' => 'knowledge_base',
		)
	);

	// Replace the inaccurate count for the "Mine" link.
	$status_links['mine'] = str_replace(
		get_comments( $args ),
		get_comments( $mine_args ),
		$status_links['mine']
	);

	return $status_links;
}

/**
 * Filters the bulk actions available on the Flagged Posts page.
 *
 * @param array $actions Array of default bulk actions.
 * @return array Modified array of bulk actions.
 */
function filter_bulk_actions( $actions ) {

	// Return early if this is not the Flagged Posts page.
	if ( ! is_flagged_posts_page() ) {
		return $actions;
	}

	// Remove the `unapprove`, `approve`, and `spam` bulk actions.
	unset( $actions['unapprove'] );
	unset( $actions['approve'] );
	unset( $actions['spam'] );

	return $actions;
}

/**
 * Disable default comment notifications for comments flagging bad content
 * or resolving bad content flags.
 *
 * @param bool $maybe_notify Whether to notify blog moderator/post author.
 * @param int  $comment_id   The ID of the comment for the notification.
 */
function disable_default_notification( $maybe_notify, $comment_id ) {
	$comment_type = get_comment( $comment_id )->comment_type;

	if ( in_array( $comment_type, array( 'flagged_content', 'resolved' ), true ) ) {
		$maybe_notify = false;
	}

	return $maybe_notify;
}

/**
 * Send an email when a comment flagging bad content is submitted.
 *
 * @param int        $comment_id       The comment ID.
 * @param int|string $comment_approved 1 if the comment is approved, 0 if not, 'spam' if spam.
 * @param array      $comment_data     Comment data.
 */
function comment_post( $comment_id, $comment_approved, $comment_data ) {

	// Return early if the comment is not of the `flagged-content` type.
	if ( 'flagged_content' !== $comment_data->comment_type ) {
		return;
	}

	// Return early if the comment is not approved.
	if ( 1 !== $comment_approved ) {
		return;
	}

	$post = get_post( $comment_data->comment_post_ID );

	$author_id = $post->post_author;

	$to = get_the_author_meta( 'user_email', $author_id );

	$subject = 'Your SFS 411 Knowledge Base post was flagged for content';

	$dashboard_url   = add_query_arg( 'comment_type', 'flagged_content', get_admin_url( 'edit-comments.php' ) );
	$flag_dashboard  = esc_url( add_query_arg( 'id', absint( $comment_id ), $dashboard_url ) );
	$post_flags_dash = esc_url( add_query_arg( 'p', absint( $post->ID ), $dashboard_url ) );

	$body  = '<p>The Knowledge Base post "' . get_the_title() . '" has been flagged for old, missing, or inaccurate content.</p>';
	$body .= '<p>Please review the concern at <a href="' . $flag_dashboard . '">' . $flag_dashboard . '</a>. It can be resolved by clicking the "Resolve" action beneath the comment.</p>';
	$body .= '<p>View all flags on "' . get_the_title() . '": <a href="' . $post_flags_dash . '">' . $post_flags_dash . '</a>';

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );

	wp_mail( $to, $subject, $body, $headers );
}
