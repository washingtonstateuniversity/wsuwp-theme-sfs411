<?php
/**
 * Registers custom blocks.
 *
 * @package wsuwp-theme-sfs411
 */

namespace WSU\Theme\SFS411\Blocks;

require_once 'latest-custom-posts/index.php'; // Include the latest custom posts block.

add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\enqueue_block_editor_assets', 10 );

/**
 * Enqueues assets for the editor.
 */
function enqueue_block_editor_assets() {
	wp_enqueue_script(
		'sfs411-blocks',
		get_stylesheet_directory_uri() . '/blocks/index.js',
		array(
			'wp-blocks',
			'wp-i18n',
			'wp-element',
		),
		sfs411_theme_version(),
		true
	);

	wp_enqueue_style(
		'sfs411-blocks-editor',
		get_stylesheet_directory_uri() . '/blocks/editor.css',
		array(),
		sfs411_theme_version()
	);
}
