<?php

namespace S3S\WP\RdbTabletop;

/**
 * Registers and enqueues plugin assets.
 */
class Assets extends BaseModule {
	use GetAssetInfo;

	/**
	 * {@inheritDoc}
	 */
	public function setup(): void {
		$this->setup_asset_vars(
			dist_path: S3S_RDB_TABLETOP_PATH . 'build/',
			fallback_version: S3S_RDB_TABLETOP_VERSION
		);

		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
	}

	/**
	 * Enqueue block editor stylesheet.
	 */
	public function enqueue_block_editor_assets(): void {
		wp_enqueue_style(
			'rdb-tabletop-block-editor',
			S3S_RDB_TABLETOP_URL . 'build/block-editor.css',
			[],
			$this->get_asset_info( 'block-editor', 'version' )
		);
	}
}
