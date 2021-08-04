<?php
/**
 * Handling for the Knowledge Base post type.
 *
 * @package wsuwp-theme-sfs411
 */

namespace WSU\Theme\SFS411\Post_Type\Knowledge_Base;

add_action( 'pre_get_posts', __NAMESPACE__ . '\filter_archive_query' );
add_action( 'init', __NAMESPACE__ . '\rewrite_rules' );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_scripts' );
add_action( 'init', __NAMESPACE__ . '\register_post_type' );
add_action( 'init', __NAMESPACE__ . '\add_university_taxonomies', 11 );
add_action( 'init', __NAMESPACE__ . '\add_content_visibility_support', 11 );
add_filter( 'wsuwp_taxonomy_metabox_post_types', __NAMESPACE__ . '\taxonomy_meta_box' );

/**
 * Filter the query used to power knowledge base archive views.
 *
 * - On the post type archive page, sort by title ascending and
 *   display up to 500 items.
 * - The same applies to knowledge-base/category/{category-name}
 *
 * @param \WP_Query $query
 */
function filter_archive_query( $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_post_type_archive( 'knowledge_base' ) ) {
		$query->set( 'posts_per_page', 500 );
		$query->set( 'orderby', 'title' );
		$query->set( 'order', 'ASC' );
		$query->set( 'post_status', array('publish','private') );
	}
}


function add_content_visibility_support() {
	add_post_type_support( 'knowledge_base', 'wsuwp-content-visibility' );	
}

/**
 * Filter rewrite rules to account for category and tag specific
 * knowledge base archive views.
 */
function rewrite_rules() {
	add_rewrite_rule( 'knowledge-base/category/(.+?)/?$', 'index.php?post_type=knowledge_base&category_name=$matches[1]', 'top' );
	add_rewrite_rule( 'knowledge-base/tag/(.+?)/?$', 'index.php?post_type=knowledge_base&tag=$matches[1]', 'top' );
}

/**
 * Enqueue the script used to filter knowledge base items on
 * archive views.
 */
function enqueue_scripts() {
	if ( is_post_type_archive( 'knowledge_base' ) ) {
		wp_enqueue_script(
			'sfs411-kb-filter',
			get_stylesheet_directory_uri() . '/includes/js/knowledge-base-filter.js',
			array(),
			spine_get_child_version(),
			true
		);
	}
}

/**
 * Register the Knowledge Base post type.
 */
function register_post_type() {

	$args = array(
		'label'                  => __( 'Knowledge Base', 'sfs411' ),
		'labels'                 => array(
			'name'               => _x( 'Knowledge Base', 'Post Type General Name', 'sfs411' ),
			'singular_name'      => _x( 'Knowledge Base', 'Post Type Singular Name', 'pfmc-feature-set' ),
			'all_items'          => __( 'All Posts', 'sfs411' ),
		),
		'description'           => '',
		'public'                => true,
		'publicly_queryable'    => true,
		'show_ui'               => true,
		'delete_with_user'      => false,
		'has_archive'           => true,
		'show_in_menu'          => true,
		'show_in_nav_menus'     => false,
		'exclude_from_search'   => false,
		'capability_type'       => 'post',
		'map_meta_cap'          => true,
		'hierarchical'          => false,
		'query_var'             => true,
		'menu_position'         => 5,
		'menu_icon'             => 'dashicons-clipboard',
		'show_in_rest'          => true,
		'rewrite'               => array(
			'slug'       => __( 'knowledge-base', 'sfs411' ),
			'with_front' => false,
		),
		'supports'              => array(
			'title',
			'editor',
			'author',
			'comments',
			'revisions',
		),
		'taxonomies'            => array(
			'category',
			'post_tag',
		),
	);

	\register_post_type( 'knowledge_base', $args );
}

/**
 * Add support for the University Location taxonomy.
 */
function add_university_taxonomies() {
	register_taxonomy_for_object_type( 'wsuwp_university_location', 'knowledge_base' );
}

/**
 * Displays a meta box with the Select2 interface provided by the University Taxonomy plugin.
 *
 * @param array $post_types Post types and their associated taxonomies.
 */
function taxonomy_meta_box( $post_types ) {
	$post_types['knowledge_base'] = array( 'wsuwp_university_location' );

	return $post_types;
}
