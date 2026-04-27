<?php

namespace S3S\WP\RdbTabletop\Blocks;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\Query\HttpQuery;
use S3S\WP\RdbTabletop\DataSource\BggDataSource;
use S3S\WP\RdbTabletop\QueryRunner\BggQueryRunner;
use S3S\WP\RdbTabletop\Support\XmlFields;

use function register_remote_data_block;

/**
 * "Board Game" block: search BGG by name, render full detail for the picked game.
 */
class BoardGame {

	use LoadsPatterns;

	/**
	 * Register the search and detail queries and the Board Game block.
	 */
	public static function register(): void {
		$data_source = BggDataSource::create();

		$search_query = HttpQuery::from_array(
			[
				'display_name'  => __( 'Search BoardGameGeek', 'rdb-tabletop' ),
				'data_source'   => $data_source,
				'endpoint'      => function ( array $input ) use ( $data_source ): string {
					$term = isset( $input['search'] ) ? (string) $input['search'] : '';
					return sprintf(
						'%s/search?type=boardgame,boardgameaccessory,boardgameexpansion,boardgamedesigner&query=%s',
						$data_source->get_endpoint(),
						rawurlencode( $term )
					);
				},
				'input_schema'  => [
					'search' => [
						'name'          => __( 'Search term', 'rdb-tabletop' ),
						'type'          => 'ui:search_input',
						'default_value' => '',
					],
				],
				'output_schema' => [
					'is_collection' => true,
					'path'          => '$.item[*]',
					'type'          => [
						'game_id' => [
							'name' => __( 'Game ID', 'rdb-tabletop' ),
							'path' => '$.id',
							'type' => 'id',
						],
						'title'   => [
							'name'     => __( 'Title', 'rdb-tabletop' ),
							'type'     => 'title',
							'generate' => static function ( array $item ): string {
								return (string) ( $item['name'][0]['value'] ?? '' );
							},
						],
						'type'    => [
							'name'     => __( 'Type', 'rdb-tabletop' ),
							'type'     => 'string',
							'generate' => static function ( array $item ): string {
								$labels = [
									'boardgame'          => __( 'Game', 'rdb-tabletop' ),
									'boardgameexpansion' => __( 'Expansion', 'rdb-tabletop' ),
									'boardgameaccessory' => __( 'Accessory', 'rdb-tabletop' ),
									'boardgamedesigner'  => __( 'Designer', 'rdb-tabletop' ),
								];
								$raw    = (string) ( $item['type'] ?? '' );
								return $labels[ $raw ] ?? $raw;
							},
						],
						'year'    => [
							'name'     => __( 'Year', 'rdb-tabletop' ),
							'type'     => 'string',
							'generate' => static function ( array $item ): string {
								return (string) ( $item['yearpublished'][0]['value'] ?? '' );
							},
						],
					],
				],
				'query_runner'  => new BggQueryRunner(),
			]
		);

		$detail_query = self::detail_query( $data_source );

		/**
		 * Filters the patterns registered for the Board Game block.
		 *
		 * @param array<int, array{title: string, html: string, role: string}> $patterns Pattern definitions.
		 */
		$patterns = apply_filters(
			'rdb_tabletop_board_game_patterns',
			[
				[
					'title' => __( 'Board Game', 'rdb-tabletop' ),
					'html'  => self::load_pattern( 'board-game.html' ),
					'role'  => 'inner_blocks',
				],
			]
		);

		register_remote_data_block(
			[
				'title'             => __( 'Board Game', 'rdb-tabletop' ),
				'icon'              => 'games',
				'render_query'      => [
					'query' => $detail_query,
				],
				'selection_queries' => [
					[
						'query'        => $search_query,
						'type'         => 'search',
						'display_name' => __( 'Search BoardGameGeek', 'rdb-tabletop' ),
					],
				],
				'patterns'          => $patterns,
			]
		);
	}

	/**
	 * Build the detail-by-ID HttpQuery.
	 *
	 * @param HttpDataSource $data_source Shared BGG data source instance.
	 */
	private static function detail_query( HttpDataSource $data_source ): HttpQuery {
		return HttpQuery::from_array(
			[
				'display_name'  => __( 'Get BoardGameGeek game', 'rdb-tabletop' ),
				'data_source'   => $data_source,
				'endpoint'      => function ( array $input ) use ( $data_source ): string {
					$id = isset( $input['game_id'] ) ? (string) $input['game_id'] : '';
					return sprintf(
						'%s/thing?id=%s&stats=1',
						$data_source->get_endpoint(),
						rawurlencode( $id )
					);
				},
				'input_schema'  => [
					'game_id' => [
						'name' => __( 'Game ID', 'rdb-tabletop' ),
						'type' => 'id',
					],
				],
				'output_schema' => self::detail_output_schema(),
				'query_runner'  => new BggQueryRunner(),
			]
		);
	}

	/**
	 * Output schema for the detail-by-ID query.
	 *
	 * Deserialized shape (from BggQueryRunner):
	 *   { item: [ { id, type, name: [...], yearpublished: [...], link: [...], statistics: [...], ... } ] }
	 *
	 * All extraction is done via `generate` callables over `item[0]` so repeated
	 * tags and attribute-heavy nodes stay predictable.
	 */
	private static function detail_output_schema(): array {
		$first = static fn( array $data ): array => (array) ( $data['item'][0] ?? [] );

		return [
			'type' => [
				'game_id'        => [
					'name'     => __( 'Game ID', 'rdb-tabletop' ),
					'type'     => 'id',
					'generate' => static function ( array $data ) use ( $first ): string {
						return (string) ( $first( $data )['id'] ?? '' );
					},
				],
				'title'          => [
					'name'     => __( 'Title', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static fn( array $data ): string => XmlFields::primary_name( $first( $data ) ),
				],
				'year'           => [
					'name'     => __( 'Year published', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static fn( array $data ): string => XmlFields::first_value( $first( $data ), 'yearpublished' ),
				],
				'description'    => [
					'name'     => __( 'Description', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static function ( array $data ) use ( $first ): string {
						return XmlFields::clean_description( (string) ( $first( $data )['description'][0]['_text'] ?? '' ) );
					},
				],
				'image'          => [
					'name'     => __( 'Image URL', 'rdb-tabletop' ),
					'type'     => 'image_url',
					'generate' => static function ( array $data ) use ( $first ): string {
						return (string) ( $first( $data )['image'][0]['_text'] ?? '' );
					},
				],
				'thumbnail'      => [
					'name'     => __( 'Thumbnail URL', 'rdb-tabletop' ),
					'type'     => 'image_url',
					'generate' => static function ( array $data ) use ( $first ): string {
						return (string) ( $first( $data )['thumbnail'][0]['_text'] ?? '' );
					},
				],
				'min_players'    => [
					'name'     => __( 'Min players', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static fn( array $data ): string => XmlFields::first_value( $first( $data ), 'minplayers' ),
				],
				'max_players'    => [
					'name'     => __( 'Max players', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static fn( array $data ): string => XmlFields::first_value( $first( $data ), 'maxplayers' ),
				],
				'playing_time'   => [
					'name'     => __( 'Playing time (minutes)', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static fn( array $data ): string => XmlFields::first_value( $first( $data ), 'playingtime' ),
				],
				'min_play_time'  => [
					'name'     => __( 'Min play time (minutes)', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static fn( array $data ): string => XmlFields::first_value( $first( $data ), 'minplaytime' ),
				],
				'max_play_time'  => [
					'name'     => __( 'Max play time (minutes)', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static fn( array $data ): string => XmlFields::first_value( $first( $data ), 'maxplaytime' ),
				],
				'min_age'        => [
					'name'     => __( 'Min age', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static fn( array $data ): string => XmlFields::first_value( $first( $data ), 'minage' ),
				],
				'average_rating' => [
					'name'     => __( 'Average rating', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static fn( array $data ): string => XmlFields::stat( $first( $data ), 'average' ),
				],
				'rating_count'   => [
					'name'     => __( 'Number of ratings', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static fn( array $data ): string => XmlFields::stat( $first( $data ), 'usersrated' ),
				],
				'weight'         => [
					'name'     => __( 'Complexity weight (1–5)', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static fn( array $data ): string => XmlFields::stat( $first( $data ), 'averageweight' ),
				],
				'overall_rank'   => [
					'name'     => __( 'BGG rank', 'rdb-tabletop' ),
					'type'     => 'number',
					'generate' => static fn( array $data ): ?int => XmlFields::overall_rank( $first( $data ) ),
				],
				'designers'      => [
					'name'     => __( 'Designers', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static fn( array $data ): string => XmlFields::links_by_type( $first( $data ), 'boardgamedesigner' ),
				],
				'artists'        => [
					'name'     => __( 'Artists', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static fn( array $data ): string => XmlFields::links_by_type( $first( $data ), 'boardgameartist' ),
				],
				'publishers'     => [
					'name'     => __( 'Publishers', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static fn( array $data ): string => XmlFields::links_by_type( $first( $data ), 'boardgamepublisher' ),
				],
				'categories'     => [
					'name'     => __( 'Categories', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static fn( array $data ): string => XmlFields::links_by_type( $first( $data ), 'boardgamecategory' ),
				],
				'mechanics'      => [
					'name'     => __( 'Mechanics', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static fn( array $data ): string => XmlFields::links_by_type( $first( $data ), 'boardgamemechanic' ),
				],
				'families'       => [
					'name'     => __( 'Families', 'rdb-tabletop' ),
					'type'     => 'string',
					'generate' => static fn( array $data ): string => XmlFields::links_by_type( $first( $data ), 'boardgamefamily' ),
				],
				'bgg_url'        => [
					'name'     => __( 'BGG URL', 'rdb-tabletop' ),
					'type'     => 'button_url',
					'generate' => static function ( array $data ) use ( $first ): string {
						$id = (string) ( $first( $data )['id'] ?? '' );
						return '' === $id ? '' : sprintf( 'https://boardgamegeek.com/boardgame/%s', rawurlencode( $id ) );
					},
				],
			],
		];
	}
}
