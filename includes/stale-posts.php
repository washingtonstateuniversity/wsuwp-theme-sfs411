<?php
/**
 * Handling for the Stale Posts dashboard.
 *
 * @package sfs411
 */

namespace SFS411\Dashboard\Stale_Content;

add_action( 'admin_menu', __NAMESPACE__ . '\add_stale_posts_page' );
add_filter( 'submenu_file', __NAMESPACE__ . '\stale_posts_submenu_file' );
add_filter( 'views_edit-knowledge_base', __NAMESPACE__ . '\stale_post_views' );
add_filter( 'bulk_actions-edit-knowledge_base', __NAMESPACE__ . '\filter_bulk_actions' );
add_filter( 'post_row_actions', __NAMESPACE__ . '\post_row_actions', 10, 2 );
add_action( 'add_meta_boxes_knowledge_base', __NAMESPACE__ . '\add_meta_boxes' );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_meta_box_assets' );
add_action( 'save_post_knowledge_base', __NAMESPACE__ . '\save_post', 10, 2 );
add_filter( 'pre_get_posts', __NAMESPACE__ . '\filter_by_stale_posts' );

/**
 * Adds the Stale Posts page the the Knowledge Base menu.
 */
function add_stale_posts_page() {
	add_submenu_page(
		'edit.php?post_type=knowledge_base',
		'Stale Posts',
		'Stale Posts',
		'edit_posts',
		'edit.php?post_type=knowledge_base&stale&all_posts=1',
		'',
		2
	);
}

/**
 * Determines if the current page is the Stale Posts page.
 */
function is_stale_posts_page() {
	if ( ! isset( $_GET['stale'] ) ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
		return false;
	}

	return true;
}

/**
 * Filters the file of the Stale Posts menu item.
 *
 * @param string $submenu_file The submenu file.
 * @return string The submenu file.
 */
function stale_posts_submenu_file( $submenu_file ) {
	if ( 'edit-knowledge_base' === get_current_screen()->id && is_stale_posts_page() ) {
		$submenu_file = 'edit.php?post_type=knowledge_base&stale&all_posts=1';
	}

	return $submenu_file;
}

/**
 * Customizes the views for the Stale Post page.
 *
 * @param array $views Fully-formed view links.
 * @return array Modified array of views.
 */
function stale_post_views( $views ) {

	// Return unmodified views early if this isn't the Stale Posts page.
	if ( ! is_stale_posts_page() ) {
		return $views;
	}

	// The Stale Posts page should only display published posts.
	unset( $views['publish'] );
	unset( $views['trash'] );

	// Add the `stale` parameter to all links.
	foreach ( $views as $view => $link ) {
		$views[ $view ] = str_replace(
			'post_type=knowledge_base',
			'post_type=knowledge_base&stale',
			$views[ $view ]
		);
	}

	// Set up query args for stale posts.
	$common_args = array(
		'post_type'      => 'knowledge_base',
		'posts_per_page' => -1,
		'meta_query'     => array(
			array(
				'key'     => '_sfs411_stale_by',
				'value'   => date( 'Y-m-d' ),
				'compare' => '<=',
				'type'    => 'DATE',
			),
		),
	);

	// Get the number of all stale posts.
	$all_stale_posts_count = ( new \WP_Query( $common_args ) )->found_posts;

	// Replace the default count with the stale posts count.
	$views['all'] = preg_replace(
		'/\(\d+\)/',
		'(' . $all_stale_posts_count . ')',
		$views['all']
	);

	// Add the author argument to get an accurate count for the "Mine" link.
	$common_args['author']   = get_current_user_id();
	$users_stale_posts_count = ( new \WP_Query( $common_args ) )->found_posts;

	// Replace the default count with the stale posts by the current user.
	$views['mine'] = preg_replace(
		'/\(\d+\)/',
		'(' . $users_stale_posts_count . ')',
		$views['mine']
	);

	return $views;
}

/**
 * Filters the bulk actions available on the Stale Posts page.
 *
 * @param array $actions Array of default bulk actions.
 * @return array Modified array of bulk actions.
 */
function filter_bulk_actions( $actions ) {
	if ( is_stale_posts_page() ) {
		unset( $actions['trash'] );
	}

	return $actions;
}

/**
 * Removes row actions that are not relevant to the Stale Posts dashboard.
 *
 * @param array   $actions Array of row action links.
 * @param WP_Post $post    The post object.
 */
function post_row_actions( $actions, $post ) {
	if ( 'knowledge_base' === $post->post_type && is_stale_posts_page() ) {
		unset( $actions['inline hide-if-no-js'] );
		unset( $actions['trash'] );
	}

	return $actions;
}

/**
 * Adds a meta box for managing post staleness.
 */
function add_meta_boxes() {
	add_meta_box(
		'sfs411-staleness-management',
		'Staleness Management',
		__NAMESPACE__ . '\display_staleness_management_meta_box',
		'knowledge_base',
		'side',
		'high'
	);
}

/**
 * Returns an array of field values keyed by id.
 *
 * @return array Field values keyed by id.
 */
function get_stale_in_fields() {
	return array(
		'quarterly'  => '3 months',
		'biannually' => '6 months',
		'annually'   => '1 year',
	);
}

/**
 * Displays a meta box used to manage post staleness.
 *
 * @param \WP_Post $post
 */
function display_staleness_management_meta_box( $post ) {
	$stale_in = get_post_meta( $post->ID, '_sfs411_stale_in', true );
	$stale_by = get_post_meta( $post->ID, '_sfs411_stale_by', true );
	$flagged  = $stale_in && $stale_by;

	wp_nonce_field( 'sfs411_check_staleness_nonce', 'sfs411-staleness-nonce' );

	if ( $flagged ) :
		$message = ( date( 'Y-m-d' ) > $stale_by )
			? 'This post has been marked as stale since '
			: 'This post is set to be marked as stale on ';
		?>
		<p id="sfs411-staleness-management_message"><?php echo esc_html( $message . date( 'F j, Y', strtotime( $stale_by ) ) ); ?>.</p>
		<?php
	endif;
	?>

	<div
		id="sfs411-staleness-management_options"
		<?php if ( $flagged ) : ?>class="hidden"<?php endif; ?>
	>

		<p>Mark this post as stale:</p>

		<?php foreach ( get_stale_in_fields() as $id => $value ) : ?>
		<p>
			<input
				type="radio"
				id="sfs411-staleness-management_options-<?php echo esc_attr( $id ); ?>"
				name="_sfs411_stale_in"
				value="<?php echo esc_attr( $value ); ?>"
				<?php
				checked( $stale_in, $value );
				disabled( $flagged );
				?>
			>
			<label for="sfs411-staleness-management_options-<?php echo esc_attr( $id ); ?>">in <?php echo esc_html( $value ); ?></label>
		</p>
		<?php endforeach; ?>

		<?php if ( empty( get_current_screen()->action ) ) : ?>
			<p><label for="sfs411-staleness-management_options-note">Leave a brief note explaining why this post is no longer stale (optional):</label></p>
			<textarea
				id="sfs411-staleness-management_options-note"
				name="_sfs411_reset_note"
				<?php disabled( $flagged ); ?>
			></textarea>
		<?php endif; ?>

	</div>

	<?php
	if ( $flagged ) :
		?>

		<button id="sfs411-staleness-management_reset" class="components-button is-link">Reset</button>

		<?php
	endif;
}

/**
 * Enqueue JavaScript for stale post management metabox functionality.
 *
 * @param string $hook_suffix The current admin page
 */
function enqueue_meta_box_assets( $hook_suffix ) {
	if ( 'post.php' !== $hook_suffix || 'knowledge_base' !== get_current_screen()->id ) {
		return;
	}

	wp_enqueue_script(
		'sfs411-stale-content',
		get_stylesheet_directory_uri() . '/includes/js/stale-content-meta-box.js',
		array(),
		spine_get_child_version(),
		true
	);

	wp_enqueue_style(
		'sfs411-stale-content',
		get_stylesheet_directory_uri() . '/includes/css/stale-content-meta-box.css',
		array(),
		spine_get_child_version()
	);
}

/**
 * Saves the meta for tracking the staleness of a post.
 *
 * @param int      $post_id
 * @param \WP_Post $post
 */
function save_post( $post_id, $post ) {
	if ( ! isset( $_POST['sfs411-staleness-nonce'] ) || ! wp_verify_nonce( $_POST['sfs411-staleness-nonce'], 'sfs411_check_staleness_nonce' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( 'auto-draft' === $post->post_status ) {
		return;
	}

	if ( ! isset( $_POST['_sfs411_stale_in'] ) || ! in_array( $_POST['_sfs411_stale_in'], array_values( get_stale_in_fields() ), true ) ) {
		return;
	}

	update_post_meta( $post_id, '_sfs411_stale_in', $_POST['_sfs411_stale_in'] );

	// Get the current date and modify it by the `_sfs411_stale_in` value.
	$date = new \DateTime();
	$stale_by = $date->modify( '+' . $_POST['_sfs411_stale_in'] )->format( 'Y-m-d' );

	update_post_meta( $post_id, '_sfs411_stale_by', $stale_by );

	// Get existing reset log meta.
	$reset_log = get_post_meta( $post_id, '_sfs411_reset_log', true );
	$reset_log = ( $reset_log ) ? $reset_log : array();

	// Set up user and date of the current reset.
	$this_reset = array(
		'user' => wp_get_current_user()->user_login,
		'date' => date( 'm-d-Y' ),
	);

	// Add the note for the current reset if there is one.
	if ( isset( $_POST['_sfs411_reset_note'] ) ) {
		$this_reset['note'] = sanitize_text_field( $_POST['_sfs411_reset_note'] );
	}

	// Append the current reset to the log.
	$reset_log[] = $this_reset;

	update_post_meta( $post_id, '_sfs411_reset_log', $reset_log );
}

/**
 * Adjusts the query for filtered views of the Knowledge Base post list table.
 *
 * @param WP_Query $query The WP_Query instance.
 */
function filter_by_stale_posts( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || 'edit-knowledge_base' !== get_current_screen()->id ) {
		return;
	}

	if ( ! is_stale_posts_page() ) {
		return;
	}

	$query->set( 'post_status', 'publish' );

	$query->set( 'meta_query', array(
		array(
			'key'     => '_sfs411_stale_by',
			'value'   => date( 'Y-m-d' ),
			'compare' => '<=',
			'type'    => 'DATE',
		),
	) );
}
