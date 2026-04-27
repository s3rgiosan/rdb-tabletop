<?php

namespace S3S\WP\RdbTabletop\QueryRunner;

use RemoteDataBlocks\Config\QueryRunner\QueryRunner;
use S3S\WP\RdbTabletop\Support\XmlFields;
use WP_Error;

/**
 * QueryRunner that deserializes BGG's XML responses into arrays.
 *
 * BGG's `/thing` and `/collection` endpoints return HTTP 202 with a "queued"
 * body while the server builds the response. This runner retries on 202
 * before handing off to the deserializer.
 */
class BggQueryRunner extends QueryRunner {

	/**
	 * Max retries for HTTP 202 queued responses.
	 */
	protected const MAX_QUEUE_RETRIES = 5;

	/**
	 * Delay (seconds) between retries.
	 */
	protected const QUEUE_RETRY_DELAY = 2;

	/**
	 * Fetch the raw response, retrying up to MAX_QUEUE_RETRIES times on HTTP 202.
	 *
	 * @param array<string,mixed> $request_details  Request config from the query.
	 * @param array<string,mixed> $input_variables  Resolved input variable values.
	 */
	protected function get_raw_response_data( array $request_details, array $input_variables ): array|WP_Error {

		$attempt = 0;
		do {
			$response = parent::get_raw_response_data( $request_details, $input_variables );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$status = (int) ( $response['metadata']['status_code'] ?? 0 );

			// BGG returns 202 while it queues the response. The body is a short
			// "Please try again later" message; retry a few times.
			if ( 202 !== $status ) {
				return $response;
			}

			++$attempt;
			if ( $attempt >= self::MAX_QUEUE_RETRIES ) {
				return new WP_Error(
					'rdb-tabletop-queued',
					__( 'BoardGameGeek is still preparing this response. Please try again in a moment.', 'rdb-tabletop' )
				);
			}

			sleep( self::QUEUE_RETRY_DELAY );
		} while ( true );
	}

	/**
	 * Parse a BGG XML string into an associative array for the output schema.
	 *
	 * @param string              $raw_response_data Raw XML body from BGG.
	 * @param array<string,mixed> $input_variables   Resolved input variable values.
	 */
	protected function deserialize_response( string $raw_response_data, array $input_variables ): mixed {
		if ( '' === trim( $raw_response_data ) ) {
			return [];
		}

		$previous = libxml_use_internal_errors( true );
		$xml      = simplexml_load_string( $raw_response_data, \SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NOBLANKS );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( false === $xml ) {
			return [];
		}

		return XmlFields::to_array( $xml );
	}
}
