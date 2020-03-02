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
add_action( 'admin_head', __NAMESPACE__ . '\admin_head' );
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
 *
 * @return bool Whther the current page is the Flagged Posts page.
 */
function is_flagged_posts_page() {
	return isset( $_GET['comment_type'] ) && 'flagged_content' === $_GET['comment_type']; // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
}

/**
 * Determines if the current page is the My Flagged Posts page.
 *
 * @return bool Whether the current page is the My Flagged Posts page.
 */
function is_my_flagged_posts_page() {
	return isset( $_GET['comment_status'] ) && 'my_posts' === $_GET['comment_status']; // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
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
 * Hides the comment filters for the Flagged Posts page.
 */
function admin_head() {

	// Return early if this is not the Flagged Posts page.
	if ( ! is_flagged_posts_page() ) {
		return;
	}
	?>
	<style type="text/css">
		#filter-by-comment-type,
		#post-query-submit {
			display: none;
		}
	</style>
	<?php
}

/**
 * Adds a checkbox to the comment list table reply form for resolving flags.
 */
function admin_footer() {

	// Return early if this is not the Flagged Posts page.
	if ( ! is_flagged_posts_page() ) {
		return;
	}
	?>
	<script type="text/javascript">
		// Add a checkbox for marking a reply as a resolution of the content flag.
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

		// Remove the filter actions, except for the "Empty Trash" button.
		function removeFilterActions() {
			const filterActions = document.getElementById( 'filter-by-comment-type' ).parentNode;

			let child = filterActions.firstElementChild;

			while ( child ) {
				if ( 'delete_all' === child.id ) {
					break;
				}

				filterActions.removeChild( child );
				child = filterActions.firstElementChild;
			}
		}

		addResolveCheckbox();
		removeFilterActions();
	</script>
	<?php
}

/**
 * If a comment reply is marked as a resolution for a bad content flag,
 * update the parent comment type to `resolved`.
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

		// Use the `id` URL parameter to display flags on a specific post.
		if ( isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
			$query->query_vars['comment__in'] = array( absint( $_GET['id'] ) ); // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
		}

		// Display flags on posts authored by the current user.
		// Whereas setting other query variables doesn't seem to impact status link counts,
		// `post_author` evidently does, hence the check that it's not already set to `0`.
		// Also, attempting to add this by setting it as a URL parameter didn't seem to work.
		if ( is_my_flagged_posts_page() && ( ! isset( $query->query_vars['post_author'] ) || 0 !== $query->query_vars['post_author'] ) ) {
			$query->query_vars['post_author'] = get_current_user_id();
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

	// Return early if the current view is for a specific post.
	if ( 0 !== $post_id ) {
		return $count;
	}

	// Find count of comments not of the `flagged_content` or `resolved` type.
	$base_args = array(
		'type__not_in' => array( 'flagged_content', 'resolved' ),
		'count'        => true,
	);

	// Overwrite base arguments if this is the flagged posts page.
	if ( is_flagged_posts_page() ) {

		// Find count of `flagged_content` comment types on `knowledge_base` posts.
		$base_args = array(
			'type'        => 'flagged_content',
			'post_type'   => 'knowledge_base',
			'count'       => true,
			'post_author' => 0,
		);
	}

	// Provide accurate counts for the "All" and "Trash" links.
	// (The `moderated`, `approved`, and `spam` links
	// are removed from the flagged posts page.)
	$count = array(
		'all'          => get_comments( $base_args ),
		'moderated'    => 0,
		'approved'     => 0,
		'post-trashed' => get_comments( array_merge( $base_args, array( 'post_status' => 'trash' ) ) ),
		'spam'         => 0,
		'trash'        => get_comments( array_merge( $base_args, array( 'status' => 'trash' ) ) ),
	);

	// Add accurate `moderated`, `approved`, and `spam`
	// comment counts for the default Comments page.
	if ( ! is_flagged_posts_page() ) {
		$count['moderated']    = get_comments( array_merge( $base_args, array( 'status' => 'hold' ) ) );
		$count['approved']     = get_comments( array_merge( $base_args, array( 'status' => 'approve' ) ) );
		$count['spam']         = get_comments( array_merge( $base_args, array( 'status' => 'spam' ) ) );
	}

	return (object) $count;
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

	// Remove irrelevant status links.
	unset( $status_links['moderated'] );
	unset( $status_links['approved'] );
	unset( $status_links['spam'] );

	// Set up arguments for retrieving the number of `flagged_content`
	// comments on `knowledge_base` posts for all authors.
	$default_args = array(
		'count'       => true,
		'type'        => 'flagged_content',
		'post_type'   => 'knowledge_base',
		'post_author' => 0,
	);

	// Supplement the arguments to retrieve the count for the current user.
	$mine_count = get_comments( array_merge( $default_args, array( 'user_id' => get_current_user_id() ) ) );

	// Replace the "Mine" link label with "My Comments",
	// and the default count with the accurate count.
	$status_links['mine'] = preg_replace(
		array( '/Mine/', '/\([^)]+\)/' ),
		array( 'My flags', '(' . $mine_count . ')' ),
		$status_links['mine']
	);

	// Create a URL with parameters for displaying a
	// list of comments on the current user's posts.
	$my_posts_link = esc_url( add_query_arg( array(
		'comment_type'   => 'flagged_content',
		'comment_status' => 'my_posts',
	), get_admin_url( null, 'edit-comments.php' ) ) );

	// Start out with a blank string for the current link attributes.
	$current_link_attributes = '';

	if ( is_my_flagged_posts_page() ) {

		// Overwite the string to output for current link attributes.
		$current_link_attributes = ' class="current" aria-current="page"';

		// Remove the current link attributes from the "All" link.
		$status_links['all'] = str_replace(
			$current_link_attributes,
			'',
			$status_links['all']
		);
	}

	// Get a count of `flagged_content` comments on the current user's posts.
	$my_posts_count = get_comments( array_merge( $default_args, array( 'post_author' => get_current_user_id() ) ) );

	// Set up the label for a "Flags on my posts" status link.
	/* translators: %s: Number of comments. */
	$label = _nx_noop(
		'Flags on my posts <span class="count">(%s)</span>',
		'Flags on my posts <span class="count">(%s)</span>',
		'comments'
	);

	// Add the "My Posts" link to the status links array.
	$status_links['my_posts'] = "<a href='$my_posts_link'$current_link_attributes>" . sprintf(
		translate_nooped_plural( $label, $my_posts_count ),
		sprintf(
			'<span class="my-posts-count">%s</span>',
			number_format_i18n( $my_posts_count )
		)
	) . '</a>';

	// Sort the status links alphabetically.
	ksort( $status_links );

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
 * Disables default comment notifications for comments flagging bad content
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
 * Sends an email when a comment flagging bad content is submitted.
 *
 * @param int        $comment_id       The comment ID.
 * @param int|string $comment_approved 1 if the comment is approved, 0 if not, 'spam' if spam.
 * @param array      $comment_data     Comment data.
 */
function comment_post( $comment_id, $comment_approved, $comment_data ) {

	// Return early if the comment is not of the `flagged-content` type.
	if ( 'flagged_content' !== $comment_data['comment_type'] ) {
		return;
	}

	// Return early if the comment is not approved.
	if ( 1 !== $comment_approved ) {
		return;
	}

	$post = get_post( $comment_data['comment_post_ID'] );

	$author_id = $post->post_author;

	$to = get_the_author_meta( 'user_email', $author_id );

	$subject = 'Your SFS 411 Knowledge Base post was flagged for content';

	$dashboard_url   = add_query_arg( 'comment_type', 'flagged_content', get_admin_url( null, 'edit-comments.php' ) );
	$flag_dashboard  = esc_url( add_query_arg( 'id', absint( $comment_id ), $dashboard_url ) );
	$post_flags_dash = esc_url( add_query_arg( 'p', absint( $post->ID ), $dashboard_url ) );
	$all_posts_flags = esc_url( add_query_arg( 'comment_status', 'my_posts', $dashboard_url ) );

	$body .= '<p>The Knowledge Base post "' . get_the_title( $post ) . '" has been flagged for old, missing, or inaccurate content.</p>';
	$body .= '<p>Please review the concern at <a href="' . $flag_dashboard . '">' . $flag_dashboard . '</a>. It can be resolved by clicking the "Resolve" action beneath the comment.</p>';
	$body .= '<p>View all flags on "' . get_the_title( $post ) . '" at <a href="' . $post_flags_dash . '">' . $post_flags_dash . '</a>.</p>';
	$body .= '<p>View flags for all your posts at <a href="' . $all_posts_flags . '">' . $all_posts_flags . '</a>.</p>';

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );

	wp_mail( $to, $subject, $body, $headers );
}
