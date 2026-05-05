<?php

namespace S3S\WP\RdbTabletop\Blocks;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\Query\HttpQuery;
use S3S\WP\RdbTabletop\DataSource\BggDataSource;
use S3S\WP\RdbTabletop\QueryRunner\BggQueryRunner;

use function register_remote_data_block;

/**
 * Registers Remote Data Blocks per BGG collection subtype × kind.
 *
 * Two kinds are registered for each subtype: "collection" (own=1) and "wishlist"
 * (wishlist=1). Each block accepts only a BGG username and renders matching
 * items as a repeating card list.
 *
 * `/collection?username=…&{status}=1&stats=1&version=1&subtype=…` shape (after XML → array):
 *   { item: [ { objectid, subtype, name: [{ _text }], yearpublished: [{ _text }],
 *              image: [{ _text }], thumbnail: [{ _text }],
 *              version: [{ item: [{ name: [{ value }], yearpublished: [{ value }],
 *                link: [{ type, value }] }] }],
 *              stats: [{ minplayers, maxplayers, minplaytime, maxplaytime, playingtime,
 *                rating: [{ value, bayesaverage: [{ value }] }] }],
 *              status: [{ own, prevowned, fortrade, want, wanttoplay, wanttobuy,
 *                wishlist, preordered }],
 *              numplays: [{ _text }], comment: [{ _text }] } ] }
 */
class Collection {

	use LoadsPatterns;

	/**
	 * Placeholder rendered when a display string field has no value.
	 */
	private const PLACEHOLDER = '—';

	/**
	 * Register one block for each configured subtype × kind.
	 */
	public static function register(): void {
		$data_source   = BggDataSource::create();
		$query_runner  = new BggQueryRunner();
		$output_schema = self::output_schema();

		foreach ( self::subtypes() as $subtype => $subtype_config ) {
			foreach ( self::kinds() as $kind => $kind_config ) {
				self::register_subtype(
					$data_source,
					$query_runner,
					$output_schema,
					$subtype,
					$kind,
					sprintf( '%s %s', $subtype_config['noun'], $kind_config['noun'] ),
					sprintf( $kind_config['display_template'], $subtype_config['display_noun'] ),
					$kind_config['status']
				);
			}
		}
	}

	/**
	 * BGG subtype variants to register, keyed by BGG API subtype value.
	 *
	 * Returns translated strings — must be called at runtime, not stored as a constant.
	 *
	 * @return array<string, array{noun: string, display_noun: string}>
	 */
	private static function subtypes(): array {
		return [
			'boardgame'          => [
				'noun'         => __( 'Board Game', 'rdb-tabletop' ),
				'display_noun' => __( 'board game', 'rdb-tabletop' ),
			],
			'boardgameexpansion' => [
				'noun'         => __( 'Expansion', 'rdb-tabletop' ),
				'display_noun' => __( 'expansion', 'rdb-tabletop' ),
			],
			'boardgameaccessory' => [
				'noun'         => __( 'Accessory', 'rdb-tabletop' ),
				'display_noun' => __( 'accessory', 'rdb-tabletop' ),
			],
		];
	}

	/**
	 * Collection kinds (BGG status filter variants).
	 *
	 * @return array<string, array{noun: string, display_template: string, status: array<string,string>}>
	 */
	private static function kinds(): array {
		return [
			'collection' => [
				'noun'             => __( 'Collection', 'rdb-tabletop' ),
				/* translators: %s: subtype display noun, e.g. "board game". */
				'display_template' => __( 'User %s collection', 'rdb-tabletop' ),
				'status'           => [ 'own' => '1' ],
			],
			'wishlist'   => [
				'noun'             => __( 'Wishlist', 'rdb-tabletop' ),
				/* translators: %s: subtype display noun, e.g. "board game". */
				'display_template' => __( 'User %s wishlist', 'rdb-tabletop' ),
				'status'           => [ 'wishlist' => '1' ],
			],
		];
	}

	/**
	 * Register the Remote Data Block for a single BGG subtype × kind.
	 *
	 * @param HttpDataSource       $data_source   Shared BGG data source instance.
	 * @param BggQueryRunner       $query_runner  Shared query runner instance.
	 * @param array<string,mixed>  $output_schema Shared output schema array.
	 * @param string               $subtype       BGG API subtype value (e.g. boardgame).
	 * @param string               $kind          Collection kind (e.g. collection, wishlist).
	 * @param string               $title         Block title shown in the inserter (translated).
	 * @param string               $display_name  Query display name shown in the RDB UI (translated).
	 * @param array<string,string> $status        BGG status flag(s) for this kind (e.g. ['own' => '1']).
	 */
	private static function register_subtype(
		HttpDataSource $data_source,
		BggQueryRunner $query_runner,
		array $output_schema,
		string $subtype,
		string $kind,
		string $title,
		string $display_name,
		array $status
	): void {
		$collection_query = HttpQuery::from_array(
			[
				'display_name'  => $display_name,
				'data_source'   => $data_source,
				'endpoint'      => function ( array $input ) use ( $data_source, $subtype, $kind, $status ): string {
					$username = isset( $input['username'] ) ? (string) $input['username'] : '';

					$params = array_merge(
						[
							'username' => $username,
							'stats'    => '1',
							'version'  => '1',
							'subtype'  => $subtype,
						],
						$status
					);

					/**
					 * Filters the query parameters sent to the BGG collection endpoint
					 * for a specific subtype × kind.
					 *
					 * The dynamic portions `{$subtype}` and `{$kind}` match the BGG API subtype
					 * (`boardgame`, `boardgameexpansion`, `boardgameaccessory`) and the registered
					 * kind (`collection`, `wishlist`).
					 *
					 * @param array<string,string> $params Query parameters, including `username`, `stats`, `version`, `subtype`, and the kind's status flag(s).
					 * @param array<string,mixed>  $input  Resolved input variable values keyed by slug.
					 */
					$params = (array) apply_filters( "rdb_tabletop_{$subtype}_{$kind}_query_params", $params, $input );

					return sprintf(
						'%s/collection?%s',
						$data_source->get_endpoint(),
						http_build_query( $params )
					);
				},
				'input_schema'  => [
					'username' => [
						'name'          => __( 'Username', 'rdb-tabletop' ),
						'type'          => 'string',
						'default_value' => '',
					],
				],
				'output_schema' => $output_schema,
				'query_runner'  => $query_runner,
			]
		);

		/**
		 * Filters the patterns registered for a specific subtype × kind.
		 *
		 * The dynamic portions `{$subtype}` and `{$kind}` match the BGG API subtype
		 * (`boardgame`, `boardgameexpansion`, `boardgameaccessory`) and the registered
		 * kind (`collection`, `wishlist`).
		 *
		 * @param array<int, array{title: string, html: string, role: string}> $patterns Pattern definitions.
		 */
		$patterns = apply_filters(
			"rdb_tabletop_{$subtype}_{$kind}_patterns",
			[
				[
					'title' => $title,
					'html'  => self::load_pattern( 'collection.html' ),
					'role'  => 'inner_blocks',
				],
			]
		);

		register_remote_data_block(
			[
				'title'        => $title,
				'icon'         => 'screenoptions',
				'render_query' => [
					'query' => $collection_query,
				],
				'patterns'     => $patterns,
			]
		);
	}

	/**
	 * Shared output schema for all collection subtypes.
	 *
	 * @return array<string,mixed>
	 */
	private static function output_schema(): array {
		return [
			'is_collection' => true,
			'path'          => '$.item[*]',
			'type'          => [
				'game_id'     => [
					'name' => __( 'Game ID', 'rdb-tabletop' ),
					'path' => '$.objectid',
					'type' => 'id',
				],
				'title'       => [
					'name'     => __( 'Title', 'rdb-tabletop' ),
					'type'     => 'title',
					'generate' => static function ( array $item ): string {
						return (string) ( $item['name'][0]['_text'] ?? '' );
					},
				],
				'year'        => [
					'name'     => __( 'Year', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static function ( array $item ): string {
						return self::or_dash( (string) ( $item['yearpublished'][0]['_text'] ?? '' ) );
					},
				],
				'image'       => [
					'name'     => __( 'Image URL', 'rdb-tabletop' ),
					'type'     => 'image_url',
					'generate' => static function ( array $item ): string {
						return (string) ( $item['image'][0]['_text'] ?? '' );
					},
				],
				'thumbnail'        => [
					'name'     => __( 'Thumbnail URL', 'rdb-tabletop' ),
					'type'     => 'image_url',
					'generate' => static function ( array $item ): string {
						return (string) ( $item['thumbnail'][0]['_text'] ?? '' );
					},
				],
				'version'          => [
					'name'     => __( 'Version', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static function ( array $item ): string {
						return (string) ( $item['version'][0]['item'][0]['name'][0]['value'] ?? '' );
					},
				],
				'version_year'     => [
					'name'     => __( 'Version year', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static function ( array $item ): string {
						$version_year = (string) ( $item['version'][0]['item'][0]['yearpublished'][0]['value'] ?? '' );
						if ( '' !== $version_year ) {
							return $version_year;
						}
						return self::or_dash( (string) ( $item['yearpublished'][0]['_text'] ?? '' ) );
					},
				],
				'version_language' => [
					'name'     => __( 'Version language', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static function ( array $item ): string {
						foreach ( $item['version'][0]['item'][0]['link'] ?? [] as $link ) {
							if ( 'language' === ( $link['type'] ?? '' ) ) {
								$value = (string) ( $link['value'] ?? '' );
								if ( '' !== $value ) {
									return $value;
								}
							}
						}
						return self::PLACEHOLDER;
					},
				],
				'version_publisher' => [
					'name'     => __( 'Version publisher', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static function ( array $item ): string {
						foreach ( $item['version'][0]['item'][0]['link'] ?? [] as $link ) {
							if ( 'boardgamepublisher' === ( $link['type'] ?? '' ) ) {
								$value = (string) ( $link['value'] ?? '' );
								if ( '' !== $value ) {
									return $value;
								}
							}
						}
						return self::PLACEHOLDER;
					},
				],
				'subtype'       => [
					'name'     => __( 'Subtype', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static function ( array $item ): string {
						return (string) ( $item['subtype'] ?? '' );
					},
				],
				'min_players'   => [
					'name'     => __( 'Min players', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static function ( array $item ): string {
						return self::or_dash( (string) ( $item['stats'][0]['minplayers'] ?? '' ) );
					},
				],
				'max_players'   => [
					'name'     => __( 'Max players', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static function ( array $item ): string {
						return self::or_dash( (string) ( $item['stats'][0]['maxplayers'] ?? '' ) );
					},
				],
				'playing_time'  => [
					'name'     => __( 'Playing time (minutes)', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static function ( array $item ): string {
						return self::or_dash( (string) ( $item['stats'][0]['playingtime'] ?? '' ) );
					},
				],
				'min_play_time' => [
					'name'     => __( 'Min play time (minutes)', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static function ( array $item ): string {
						return self::or_dash( (string) ( $item['stats'][0]['minplaytime'] ?? '' ) );
					},
				],
				'max_play_time' => [
					'name'     => __( 'Max play time (minutes)', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static function ( array $item ): string {
						return self::or_dash( (string) ( $item['stats'][0]['maxplaytime'] ?? '' ) );
					},
				],
				'num_plays'     => [
					'name'     => __( 'Number of plays', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static function ( array $item ): string {
						return self::or_dash( (string) ( $item['numplays'][0]['_text'] ?? '' ) );
					},
				],
				'comment'       => [
					'name'     => __( 'Comment', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static function ( array $item ): string {
						return self::or_dash( (string) ( $item['comment'][0]['_text'] ?? '' ) );
					},
				],
				'user_rating'  => [
					'name'     => __( 'User rating', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static function ( array $item ): string {
						$value = (string) ( $item['stats'][0]['rating'][0]['value'] ?? '' );
						return 'N/A' === $value ? self::PLACEHOLDER : self::or_dash( $value );
					},
				],
				'geek_rating'  => [
					'name'     => __( 'Geek rating', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static function ( array $item ): string {
						$value = (string) ( $item['stats'][0]['rating'][0]['bayesaverage'][0]['value'] ?? '' );
						return '0' === $value ? self::PLACEHOLDER : self::or_dash( $value );
					},
				],
				'status'       => [
					'name'     => __( 'Status', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static function ( array $item ): string {
						$flags = [
							'own'        => __( 'Owned', 'rdb-tabletop' ),
							'prevowned'  => __( 'Previously owned', 'rdb-tabletop' ),
							'fortrade'   => __( 'For trade', 'rdb-tabletop' ),
							'want'       => __( 'Want', 'rdb-tabletop' ),
							'wanttoplay' => __( 'Want to play', 'rdb-tabletop' ),
							'wanttobuy'  => __( 'Want to buy', 'rdb-tabletop' ),
							'wishlist'   => __( 'Wishlist', 'rdb-tabletop' ),
							'preordered' => __( 'Pre-ordered', 'rdb-tabletop' ),
						];
						$status = $item['status'][0] ?? [];
						$active = [];
						foreach ( $flags as $key => $label ) {
							if ( '1' === ( $status[ $key ] ?? '0' ) ) {
								$active[] = $label;
							}
						}
						return implode( ', ', $active );
					},
				],
			],
		];
	}

	/**
	 * Return the value if non-empty, otherwise the placeholder.
	 */
	private static function or_dash( string $value ): string {
		return '' === $value ? self::PLACEHOLDER : $value;
	}
}
