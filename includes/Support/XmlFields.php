<?php

namespace S3S\WP\RdbTabletop\Support;

use SimpleXMLElement;

/**
 * XML → array utilities + BGG-specific field extractors.
 *
 * `to_array()` produces a deterministic shape where:
 *  - attributes become plain string keys on the parent,
 *  - every child element becomes a list (always an array), even when there
 *    is a single child of a given name,
 *  - text content is stored under `_text`.
 *
 * This makes JSONPath / `generate` callables predictable regardless of how
 * many siblings a tag has in the source XML.
 */
class XmlFields {

	/**
	 * Recursively convert a SimpleXMLElement to an associative array.
	 *
	 * @param  SimpleXMLElement $element Element to convert.
	 * @return array<string,mixed>
	 */
	public static function to_array( SimpleXMLElement $element ): array {
		$result = [];

		foreach ( $element->attributes() as $name => $value ) {
			$result[ (string) $name ] = (string) $value;
		}

		$text = trim( (string) $element );
		if ( '' !== $text ) {
			$result['_text'] = $text;
		}

		foreach ( $element->children() as $child ) {
			$result[ $child->getName() ][] = self::to_array( $child );
		}

		return $result;
	}

	/**
	 * Pick the `<name type="primary">` value from a `/thing` item.
	 *
	 * @param array<string,mixed> $item Deserialized `<item>` node.
	 */
	public static function primary_name( array $item ): string {
		foreach ( $item['name'] ?? [] as $name ) {
			if ( 'primary' === ( $name['type'] ?? '' ) ) {
				return (string) ( $name['value'] ?? '' );
			}
		}
		return (string) ( $item['name'][0]['value'] ?? '' );
	}

	/**
	 * Return the `value` attribute of the first occurrence of `$tag` under `$item`.
	 *
	 * @param array<string,mixed> $item     Deserialized `<item>` node.
	 * @param string              $tag      Child tag name.
	 * @param string              $fallback Value returned when the tag is absent.
	 */
	public static function first_value( array $item, string $tag, string $fallback = '' ): string {
		$nodes = $item[ $tag ] ?? [];
		return (string) ( $nodes[0]['value'] ?? $fallback );
	}

	/**
	 * Return a comma-joined list of `<link value="...">` values filtered by type.
	 *
	 * @param array<string,mixed> $item Deserialized `<item>` node.
	 * @param string              $type Value of the `type` attribute to filter on.
	 */
	public static function links_by_type( array $item, string $type ): string {
		$names = [];
		foreach ( $item['link'] ?? [] as $link ) {
			if ( ( $link['type'] ?? '' ) === $type ) {
				$names[] = (string) ( $link['value'] ?? '' );
			}
		}
		return implode( ', ', array_filter( $names ) );
	}

	/**
	 * Extract the overall BGG rank from a `/thing` item with `stats=1`.
	 *
	 * @param array<string,mixed> $item Deserialized `<item>` node.
	 */
	public static function overall_rank( array $item ): ?int {
		$ranks = $item['statistics'][0]['ratings'][0]['ranks'][0]['rank'] ?? [];
		foreach ( $ranks as $rank ) {
			if ( 'boardgame' === ( $rank['name'] ?? '' ) && 'subtype' === ( $rank['type'] ?? '' ) ) {
				$value = $rank['value'] ?? '';
				return is_numeric( $value ) ? (int) $value : null;
			}
		}
		return null;
	}

	/**
	 * Return a ratings stat value from the statistics block of a `/thing` item.
	 *
	 * @param array<string,mixed> $item Deserialized `<item>` node.
	 * @param string              $key  Stat key, e.g. `average`, `usersrated`, `averageweight`.
	 */
	public static function stat( array $item, string $key ): string {
		$ratings = $item['statistics'][0]['ratings'][0] ?? [];
		return (string) ( $ratings[ $key ][0]['value'] ?? '' );
	}

	/**
	 * Decode HTML entities commonly returned in BGG descriptions.
	 *
	 * @param string $raw Raw description string from the XML response.
	 */
	public static function clean_description( string $raw ): string {
		$decoded = html_entity_decode( $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return trim( $decoded );
	}
}
