<?php
/**
 * Registers custom blocks.
 *
 * @package wsuwp-theme-sfs411
 */

namespace WSU\Theme\SFS411\Blocks;

/**
 * - wp dist-archive was used to create a distributable archive of
 *   the Latest Custom Posts plugin so that it could be embedded in
 *   this theme. This removes most unnecessary build and development
 *   files.
 * - The script enqueues in the Latest Custom Posts block file are
 *   modified to load from the child theme location rather than the
 *   standard plugins_url().
 * - The namespace in the main Block file is updated to match this
 *   theme in the case that the plugin is added to the wider ecosystem.
 * - The main Latest Custom Posts plugin file is removed and the
 *   block is included directly.
 */
require_once __DIR__ . '/latest-custom-posts/includes/block.php'; // Include the latest custom posts block.
