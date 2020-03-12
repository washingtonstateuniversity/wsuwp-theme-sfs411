<?php
/**
 * Handling for the Knowledge Base post type.
 *
 * @package sfs411
 */

namespace SFS411\Post_Type\Knowledge_Base;

add_action( 'init', __NAMESPACE__ . '\register_post_type' );
add_action( 'init', __NAMESPACE__ . '\add_university_taxonomies', 11 );
add_filter( 'wsuwp_taxonomy_metabox_post_types', __NAMESPACE__ . '\taxonomy_meta_box' );

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
