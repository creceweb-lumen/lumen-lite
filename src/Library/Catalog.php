<?php
/**
 * Public Library catalog contract.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Library;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalizes the shared catalog that Lite owns and compatible extensions can extend.
 */
final class Catalog {
	/**
	 * @return array<string,array<string,string>>
	 */
	public function families(): array {
		$families = array();

		/**
		 * Filters visual Library families.
		 *
		 * @param array<string,array<string,string>> $families Family definitions.
		 */
		$families = apply_filters( 'creceweb_lumen_lite_library_families', $families );

		if ( ! is_array( $families ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $families as $id => $family ) {
			$id = sanitize_key( (string) $id );

			if ( '' === $id || ! is_array( $family ) ) {
				continue;
			}

			$label = isset( $family['label'] ) ? sanitize_text_field( (string) $family['label'] ) : '';

			if ( '' === $label ) {
				continue;
			}

			$normalized[ $id ] = array(
				'label'       => $label,
				'description' => isset( $family['description'] ) ? sanitize_text_field( (string) $family['description'] ) : '',
			);
		}

		return $normalized;
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	public function items(): array {
		$catalog = array();

		/**
		 * Filters the shared Library catalog.
		 *
		 * @param array<string,array<string,mixed>> $catalog Pattern catalog.
		 */
		$catalog = apply_filters( 'creceweb_lumen_lite_library_catalog', $catalog );

		if ( ! is_array( $catalog ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $catalog as $id => $item ) {
			$id = sanitize_key( (string) $id );

			if ( '' === $id || ! is_array( $item ) ) {
				continue;
			}

			$title        = isset( $item['title'] ) ? sanitize_text_field( (string) $item['title'] ) : '';
			$pattern_name = isset( $item['pattern_name'] ) ? sanitize_text_field( (string) $item['pattern_name'] ) : '';
			$type         = isset( $item['type'] ) ? sanitize_key( (string) $item['type'] ) : 'section';

			if ( ! in_array( $type, array( 'section', 'page' ), true ) ) {
				$type = 'section';
			}

			if ( '' === $title || '' === $pattern_name ) {
				continue;
			}

			$keywords = isset( $item['keywords'] ) && is_array( $item['keywords'] )
				? array_values( array_filter( array_map( 'sanitize_text_field', $item['keywords'] ) ) )
				: array();

			$content = isset( $item['content'] ) && is_string( $item['content'] ) ? $item['content'] : '';
			$use     = isset( $item['use'] ) ? sanitize_text_field( (string) $item['use'] ) : '';

			$raw_source = isset( $item['source'] ) ? sanitize_key( (string) $item['source'] ) : '';
			$source     = 'included' === $raw_source ? 'included' : 'extension';

			$normalized[ $id ] = array(
				'id'           => $id,
				'type'         => $type,
				'title'        => $title,
				'description'  => isset( $item['description'] ) ? sanitize_text_field( (string) $item['description'] ) : '',
				'use'          => $use,
				'content'      => $content,
				'family'       => isset( $item['family'] ) ? sanitize_key( (string) $item['family'] ) : '',
				'pattern_name' => $pattern_name,
				'preview_url'  => isset( $item['preview_url'] ) ? esc_url_raw( (string) $item['preview_url'] ) : '',
				'keywords'     => $keywords,
				'template'     => 'page' === $type && isset( $item['template'] ) ? sanitize_text_field( (string) $item['template'] ) : '',
				'source'       => $source,
				'source_label' => isset( $item['source_label'] ) ? sanitize_text_field( (string) $item['source_label'] ) : '',
			);
		}

		return $normalized;
	}
}
