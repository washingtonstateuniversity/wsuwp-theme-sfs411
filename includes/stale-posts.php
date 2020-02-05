<?php
/**
 * Handling for the Stale Posts dashboard.
 *
 * @package sfs411
 */

namespace SFS411\Dashboard\Stale_Content;

add_action( 'admin_menu', __NAMESPACE__ . '\add_stale_posts_page' );
add_filter( 'submenu_file', __NAMESPACE__ . '\stale_posts_submenu_file' );
add_action( 'add_meta_boxes_knowledge_base', __NAMESPACE__ . '\add_meta_boxes' );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_meta_box_script' );
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

/**
 * Adds a meta box for managing post staleness.
 *
 * @since 0.3.0
 *
 * @param string $post_type
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
 * @since 0.3.0
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
function enqueue_meta_box_script( $hook_suffix ) {
	if ( 'post.php' === $hook_suffix && 'knowledge_base' === get_current_screen()->id ) {
		wp_enqueue_script(
			'sfs411-stale-content',
			get_stylesheet_directory_uri() . '/includes/js/stale-content-meta-box.js',
			array(),
			spine_get_child_version(),
			true
		);
	}
}

/**
 * Saves the meta for tracking the staleness of a post.
 *
 * @since 0.3.0
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

	if ( isset( $_POST['_sfs411_stale_in'] ) && in_array( $_POST['_sfs411_stale_in'], array_values( get_stale_in_fields() ), true ) ) {

		// Get the current date and modify it by the `_sfs411_stale_in` value.
		$date = new \DateTime();
		$stale_by = $date->modify( '+' . $_POST['_sfs411_stale_in'] )->format( 'Y-m-d' );

		update_post_meta( $post_id, '_sfs411_stale_by', $stale_by );
		update_post_meta( $post_id, '_sfs411_stale_in', $_POST['_sfs411_stale_in'] );
	}
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

	if ( ! isset( $_GET['stale'] ) ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
		return;
	}

	$query->set(
		'meta_query',
		array(
			array(
				'key'     => '_sfs411_stale_by',
				'value'   => date( 'Y-m-d' ),
				'compare' => '<=',
				'type'    => 'DATE',
			),
		)
	);
}
