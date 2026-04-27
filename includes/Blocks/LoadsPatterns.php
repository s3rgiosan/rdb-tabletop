<?php

namespace S3S\WP\RdbTabletop\Blocks;

/**
 * Loads block pattern HTML files from the plugin's patterns/ directory.
 */
trait LoadsPatterns {

	/**
	 * Load a pattern HTML file and swap the plugin URL placeholder.
	 *
	 * @param string $filename Filename relative to patterns/.
	 */
	private static function load_pattern( string $filename ): string {
		$path = S3S_RDB_TABLETOP_PATH . 'patterns/' . $filename;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$html = is_readable( $path ) ? (string) file_get_contents( $path ) : '';
		return str_replace( '{{PLUGIN_URL}}', S3S_RDB_TABLETOP_URL, $html );
	}
}
