<?php
/**
 * Plugin Name: DMG Post Finder
 * Description: A WordPress plugin to find posts via WP-CLI or an editor block, and display them as a styled link.
 * Version: 1.0.0
 * Author: Dan Q
 *
 * @package DMG\PostFinder
 */

namespace DMG\PostFinder;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/* Initialise the new Gutenberg block */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-gutenbergblock.php';
$gutenberg_block = new GutenbergBlock();
$gutenberg_block->init();

/* Load CLI command if WP-CLI is available */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-cli.php';
}
