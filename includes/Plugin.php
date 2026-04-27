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
		add_filter( 'plugin_action_links_' . S3S_RDB_TABLETOP_BASENAME, [ $this, 'plugin_action_links' ] );

		( new Assets() )->setup();
		( new Settings() )->setup();
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
	}
}
