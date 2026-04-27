<?php

namespace S3S\WP\RdbTabletop\DataSource;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use S3S\WP\RdbTabletop\Admin\Settings;

/**
 * Factory for the shared BGG XML API2 HttpDataSource.
 */
class BggDataSource {

	/**
	 * Base URL for the BGG XML API2 (no trailing slash, no www).
	 */
	public const ENDPOINT = 'https://boardgamegeek.com/xmlapi2';

	/**
	 * Build a shared HttpDataSource instance for the BGG XML API2.
	 *
	 * The Authorization header is pulled from the plugin settings on every request
	 * (via a callable) so token rotations take effect without re-registering blocks.
	 */
	public static function create(): HttpDataSource {
		return HttpDataSource::from_array(
			[
				'display_name'    => 'BoardGameGeek',
				'endpoint'        => self::ENDPOINT,
				'image_url'       => 'https://cf.geekdo-static.com/images/logos/navbar-logo-bgg-b2.svg',
				'request_headers' => static function (): array {
					$headers = [
						'Accept'     => 'application/xml, text/xml',
						'User-Agent' => 'rdb-tabletop/1.0 (+https://github.com/s3rgiosan/rdb-tabletop)',
					];

					$token = Settings::get_token();
					if ( '' !== $token ) {
						$headers['Authorization'] = 'Bearer ' . $token;
					}

					return $headers;
				},
			]
		);
	}

}
