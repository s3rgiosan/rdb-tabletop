<?php

namespace S3S\WP\RdbTabletop;

use S3S\WP\RdbTabletop\Admin\Settings;
use S3S\WP\RdbTabletop\Blocks\BoardGame;
use S3S\WP\RdbTabletop\Blocks\Collection;

/**
 * Plugin bootstrap: registers hooks, admin notices, and the settings page.
 */
class Plugin {

	/**
	 * Plugin singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance(): static {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Setup hooks.
	 */
	public function setup(): void {
		add_action( 'init', [ $this, 'register_blocks' ], 20 );
		add_filter( 'render_block', [ $this, 'append_powered_by' ], 10, 2 );
		add_filter( 'plugin_action_links_' . S3S_RDB_TABLETOP_BASENAME, [ $this, 'plugin_action_links' ] );

		( new Assets() )->setup();
		( new Settings() )->setup();
	}

	/**
	 * Append the rendered `rdb-tabletop/powered-by` block after each RDB Tabletop block.
	 *
	 * @param string              $block_content Rendered block HTML.
	 * @param array<string,mixed> $block         Parsed block array.
	 */
	public function append_powered_by( string $block_content, array $block ): string {
		$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
		if ( ! in_array( $name, $this->powered_by_anchors(), true ) ) {
			return $block_content;
		}

		/**
		 * Filters whether to append the "Powered by BoardGameGeek" block after an RDB Tabletop block.
		 *
		 * Return false to suppress the attribution for a given anchor block.
		 *
		 * @param bool                $append Whether to append the powered-by block. Default true.
		 * @param string              $name   Anchor block name (e.g. `remote-data-blocks/board-game`).
		 * @param array<string,mixed> $block  Parsed anchor block array.
		 */
		$append = (bool) apply_filters( 'rdb_tabletop_append_powered_by', true, $name, $block );
		if ( ! $append ) {
			return $block_content;
		}

		return $block_content . render_block(
			[
				'blockName'    => 'rdb-tabletop/powered-by',
				'attrs'        => [],
				'innerBlocks'  => [],
				'innerHTML'    => '',
				'innerContent' => [],
			]
		);
	}

	/**
	 * Prepend a Settings link to the plugin's action links on the Plugins screen.
	 *
	 * @param array<int|string,string> $links Existing action links.
	 * @return array<int|string,string>
	 */
	public function plugin_action_links( array $links ): array {
		return array_merge(
			[
				sprintf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'options-general.php?page=' . Settings::MENU_SLUG ) ),
					esc_html__( 'Settings', 'rdb-tabletop' )
				),
			],
			$links
		);
	}

	/**
	 * Register the Remote Data Blocks integrations.
	 */
	public function register_blocks(): void {
		if ( ! function_exists( 'register_remote_data_block' ) ) {
			return;
		}

		BoardGame::register();
		Collection::register();

		register_block_type( S3S_RDB_TABLETOP_PATH . 'blocks/powered-by' );
	}

	/**
	 * Anchor blocks that receive the powered-by attribution appended after them.
	 *
	 * @return array<int,string>
	 */
	private function powered_by_anchors(): array {
		return [
			'remote-data-blocks/board-game',
			'remote-data-blocks/board-game-collection',
			'remote-data-blocks/board-game-wishlist',
			'remote-data-blocks/expansion-collection',
			'remote-data-blocks/expansion-wishlist',
			'remote-data-blocks/accessory-collection',
			'remote-data-blocks/accessory-wishlist',
		];
	}
}
