<?php

namespace S3S\WP\RdbTabletop\Admin;

use S3S\WP\RdbTabletop\BaseModule;

/**
 * Settings page with contextual help for BGG application setup.
 *
 * Renders under Settings → RDB Tabletop. Stores the BGG Bearer token
 * sent with every XML API2 request.
 */
class Settings extends BaseModule {

	/**
	 * The wp_options key that stores the settings array.
	 */
	public const OPTION_KEY = 's3s_rdb_tabletop_settings';

	/**
	 * Slug used for the admin menu page and settings group.
	 */
	public const MENU_SLUG = 'rdb-tabletop';

	/**
	 * WordPress capability required to view and save the settings page.
	 */
	public const CAPABILITY = 'manage_options';

	/**
	 * {@inheritDoc}
	 */
	public function setup(): void {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_menu', [ $this, 'register_page' ] );
	}

	/**
	 * Return the saved BGG application token, or empty string if not configured.
	 */
	public static function get_token(): string {
		$settings = get_option( self::OPTION_KEY, [] );
		return isset( $settings['token'] ) ? (string) $settings['token'] : '';
	}

	/**
	 * Register the settings page under Settings → RDB Tabletop.
	 */
	public function register_page(): void {
		$hook_suffix = add_options_page(
			__( 'RDB Tabletop', 'rdb-tabletop' ),
			__( 'RDB Tabletop', 'rdb-tabletop' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			[ $this, 'render_page' ]
		);

		if ( $hook_suffix ) {
			add_action( 'load-' . $hook_suffix, [ $this, 'add_help_tabs' ] );
		}
	}

	/**
	 * Register the settings group, section, and token field.
	 */
	public function register_settings(): void {
		register_setting(
			self::MENU_SLUG,
			self::OPTION_KEY,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize' ],
				'default'           => [ 'token' => '' ],
			]
		);

		add_settings_section(
			'rdb_bgg_auth',
			'',
			[ $this, 'render_section_intro' ],
			self::MENU_SLUG
		);

		add_settings_field(
			'rdb_bgg_token',
			__( 'Application token', 'rdb-tabletop' ),
			[ $this, 'render_token_field' ],
			self::MENU_SLUG,
			'rdb_bgg_auth'
		);
	}

	/**
	 * Sanitize the settings array before saving.
	 *
	 * @param mixed $input Raw POST input.
	 */
	public function sanitize( mixed $input ): array {
		$output = [ 'token' => '' ];

		if ( is_array( $input ) && isset( $input['token'] ) ) {
			$output['token'] = trim( sanitize_text_field( (string) $input['token'] ) );
		}

		return $output;
	}

	/**
	 * Attach contextual help tabs to the settings screen.
	 */
	public function add_help_tabs(): void {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$screen->add_help_tab(
			[
				'id'      => 'rdb-tabletop-requirements',
				'title'   => __( 'Requirements', 'rdb-tabletop' ),
				'content' => $this->get_requirements_help_content(),
			]
		);

		$screen->add_help_tab(
			[
				'id'      => 'rdb-tabletop-setup',
				'title'   => __( 'Setup Instructions', 'rdb-tabletop' ),
				'content' => $this->get_setup_help_content(),
			]
		);

		$screen->set_help_sidebar( $this->get_help_sidebar_content() );
	}

	/**
	 * Render the section intro, pointing users to the Help tab.
	 */
	public function render_section_intro(): void {
		?>
		<div class="notice notice-info inline">
			<p>
				<?php esc_html_e( 'For step-by-step instructions on creating a BGG application and getting your token, click the Help tab in the top-right corner of this page.', 'rdb-tabletop' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render the token input field.
	 */
	public function render_token_field(): void {
		$value = self::get_token();
		printf(
			'<input type="password" id="rdb_bgg_token" name="%1$s[token]" value="%2$s" class="regular-text code" autocomplete="off" spellcheck="false" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $value )
		);
	}

	/**
	 * Render the settings page wrapper.
	 */
	public function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'RDB Tabletop', 'rdb-tabletop' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::MENU_SLUG );
				do_settings_sections( self::MENU_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Return HTML for the Requirements help tab.
	 */
	private function get_requirements_help_content(): string {
		$items = [
			__( 'A BoardGameGeek account (free to create at boardgamegeek.com).', 'rdb-tabletop' ),
			__( 'The URL of the site where this plugin will run.', 'rdb-tabletop' ),
			__( 'An email address you actively monitor — BGG may reply there during review.', 'rdb-tabletop' ),
		];

		$list = implode(
			'',
			array_map(
				static fn( string $item ): string => sprintf( '<li>%s</li>', $item ),
				$items
			)
		);

		return sprintf(
			'<p><strong>%s</strong></p><ul>%s</ul>',
			__( 'Before you begin, you will need:', 'rdb-tabletop' ),
			$list
		);
	}

	/**
	 * Return HTML for the Setup Instructions help tab.
	 */
	private function get_setup_help_content(): string {
		$steps = [
			[
				'title' => __( 'Apply for API access', 'rdb-tabletop' ),
				'items' => [
					sprintf(
						wp_kses(
							/* translators: %s: URL to BGG applications page. */
							__( 'Log in at boardgamegeek.com, then go to <a href="%s" target="_blank" rel="noreferrer noopener">boardgamegeek.com/applications</a> and click <strong>Create an application</strong>.', 'rdb-tabletop' ),
							[
								'a'      => [
									'href'   => [],
									'target' => [],
									'rel'    => [],
								],
								'strong' => [],
							]
						),
						esc_url( 'https://boardgamegeek.com/applications' )
					),
					sprintf(
						wp_kses(
							/* translators: %1$s: Open strong, %2$s: Close strong. */
							__( 'Fill in the form. For <strong>Application name</strong>, use something like <em>RDB Tabletop – your-domain.com</em> (do not include "BGG" or "BoardGameGeek" — they are trademarks).', 'rdb-tabletop' ),
							[
								'strong' => [],
								'em'     => [],
							]
						)
					),
					__( 'Describe your site and how you plan to use the API. Mention that calls are server-side and responses are cached.', 'rdb-tabletop' ),
					sprintf(
						wp_kses(
							/* translators: %1$s: Open strong, %2$s: Close strong. */
							__( 'Accept the Terms of Use and click <strong>Submit for evaluation</strong>.', 'rdb-tabletop' ),
							[ 'strong' => [] ]
						)
					),
					__( 'BGG reviews every application manually. Approval usually takes a few days to a week. Watch your email for follow-up questions.', 'rdb-tabletop' ),
				],
			],
			[
				'title' => __( 'Create a token', 'rdb-tabletop' ),
				'items' => [
					sprintf(
						wp_kses(
							/* translators: %s: URL to BGG applications page. */
							__( 'Once approved, return to <a href="%s" target="_blank" rel="noreferrer noopener">boardgamegeek.com/applications</a>.', 'rdb-tabletop' ),
							[
								'a' => [
									'href'   => [],
									'target' => [],
									'rel'    => [],
								],
							]
						),
						esc_url( 'https://boardgamegeek.com/applications' )
					),
					sprintf(
						wp_kses(
							/* translators: %1$s: Open strong, %2$s: Close strong. */
							__( 'Click the <strong>Tokens</strong> button next to your application, then click <strong>Create a token</strong>.', 'rdb-tabletop' ),
							[ 'strong' => [] ]
						)
					),
					__( 'Copy the generated token. Treat it like a password — do not share it or commit it to a repository.', 'rdb-tabletop' ),
				],
			],
			[
				'title' => __( 'Configure the plugin', 'rdb-tabletop' ),
				'items' => [
					__( 'Paste the token into the Application token field on this page.', 'rdb-tabletop' ),
					sprintf(
						wp_kses(
							/* translators: %1$s: Open strong, %2$s: Close strong. */
							__( 'Click <strong>Save Changes</strong>.', 'rdb-tabletop' ),
							[ 'strong' => [] ]
						)
					),
				],
			],
		];

		$content     = '';
		$step_number = 1;

		foreach ( $steps as $step ) {
			$content .= sprintf(
				'<h3>%s</h3>',
				sprintf(
					/* translators: %1$s: Step number, %2$s: Step title. */
					__( 'Step %1$s: %2$s', 'rdb-tabletop' ),
					$step_number,
					$step['title']
				)
			);

			$content .= sprintf(
				'<ol>%s</ol>',
				implode(
					'',
					array_map(
						static fn( string $item ): string => sprintf( '<li>%s</li>', $item ),
						$step['items']
					)
				)
			);

			++$step_number;
		}

		return $content;
	}

	/**
	 * Return HTML for the help sidebar.
	 */
	private function get_help_sidebar_content(): string {
		$links = [
			sprintf(
				'<a href="%s" target="_blank" rel="noreferrer noopener">%s</a>',
				esc_url( 'https://boardgamegeek.com/applications' ),
				__( 'BGG Applications', 'rdb-tabletop' )
			),
			sprintf(
				'<a href="%s" target="_blank" rel="noreferrer noopener">%s</a>',
				esc_url( 'https://boardgamegeek.com/using_the_xml_api' ),
				__( 'BGG API Terms of Use', 'rdb-tabletop' )
			),
			sprintf(
				'<a href="%s" target="_blank" rel="noreferrer noopener">%s</a>',
				esc_url( 'https://github.com/s3rgiosan/rdb-tabletop' ),
				__( 'Plugin Documentation', 'rdb-tabletop' )
			),
		];

		return sprintf(
			'<p><strong>%s</strong></p><ul>%s</ul>',
			__( 'Useful links:', 'rdb-tabletop' ),
			implode(
				'',
				array_map(
					static fn( string $link ): string => sprintf( '<li>%s</li>', $link ),
					$links
				)
			)
		);
	}
}
