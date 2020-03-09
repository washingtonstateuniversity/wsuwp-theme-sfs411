<?php
/**
 * SFS 411 Spine child theme functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package sfs411
 */

add_filter( 'spine_child_theme_version', 'sfs411_theme_version' );

/**
 * Provides a theme version for use in cache busting.
 *
 * @since 0.0.1
 *
 * @return string
 */
function sfs411_theme_version() {
	return '0.0.1';
}

require_once 'includes/knowledge-base-post-type.php';
require_once 'includes/comment-form.php';
require_once 'includes/flagged-posts.php';
require_once 'includes/stale-posts.php';
require_once 'blocks/index.php';
