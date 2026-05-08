<?php
/**
 * Plugin Name:       RDB Tabletop
 * Description:       BoardGameGeek data source for Remote Data Blocks.
 * Plugin URI:        https://github.com/s3rgiosan/rdb-tabletop
 * Requires at least: 6.7
 * Requires PHP:      8.2
 * Requires Plugins:  remote-data-blocks
 * Version:           1.0.1
 * Author:            Sérgio Santos
 * Author URI:        https://s3rgiosan.dev/?utm_source=wp-plugins&utm_medium=rdb-tabletop&utm_campaign=author-uri
 * License:           GPL-3.0-or-later
 * License URI:       https://spdx.org/licenses/GPL-3.0-or-later.html
 * Update URI:        https://s3rgiosan.dev/
 * GitHub Plugin URI: https://github.com/s3rgiosan/rdb-tabletop
 * Text Domain:       rdb-tabletop
 */

namespace S3S\WP\RdbTabletop;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'S3S_RDB_TABLETOP_VERSION', '1.0.0' );
define( 'S3S_RDB_TABLETOP_PATH', plugin_dir_path( __FILE__ ) );
define( 'S3S_RDB_TABLETOP_URL', plugin_dir_url( __FILE__ ) );
define( 'S3S_RDB_TABLETOP_BASENAME', plugin_basename( __FILE__ ) );

if ( file_exists( S3S_RDB_TABLETOP_PATH . 'vendor/autoload.php' ) ) {
	require_once S3S_RDB_TABLETOP_PATH . 'vendor/autoload.php';
}

PucFactory::buildUpdateChecker(
	'https://github.com/s3rgiosan/rdb-tabletop/',
	__FILE__,
	'rdb-tabletop'
);

/**
 * Load the plugin.
 */
add_action(
	'plugins_loaded',
	function () {
		$plugin = Plugin::get_instance();
		$plugin->setup();
	}
);
