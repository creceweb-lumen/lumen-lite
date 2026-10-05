<?php
/**
 * Data-only Kit catalog for the shared Lumen Library.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Library;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalizes curated Kit definitions without installing runtime code.
 *
 * A Kit is only a grouping of existing Library items. It cannot register PHP,
 * scripts, styles, post types, taxonomies, or background services.
 */
final class KitCatalog {
	/** @var array<string,array<string,mixed>>|null */
	private ?array $definitions = null;

	/** Locale used to build the cached translated definitions. */
	private ?string $definitions_locale = null;

	/**
	 * @return array<string,array<string,mixed>>
	 */
	public function items(): array {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();

		if ( null !== $this->definitions && $locale === $this->definitions_locale ) {
			return $this->definitions;
		}

		$definitions = $this->included_definitions();

		/**
		 * Filters the data-only Kit catalog.
		 *
		 * Compatible extensions may contribute curated groupings of existing
		 * Library item IDs. Executable Kit callbacks are intentionally unsupported.
		 *
		 * @param array<string,array<string,mixed>> $definitions Kit definitions.
		 */
		$filtered = apply_filters( 'creceweb_lumen_lite_library_kits', $definitions );
		$filtered = is_array( $filtered ) ? $filtered : array();

		$normalized = array();
		foreach ( $filtered as $id => $definition ) {
			$id = sanitize_key( (string) $id );
			if ( '' === $id || ! is_array( $definition ) ) {
				continue;
			}

			$title = sanitize_text_field( (string) ( $definition['title'] ?? '' ) );
			$items = isset( $definition['items'] ) && is_array( $definition['items'] )
				? array_values( array_unique( array_filter( array_map( 'sanitize_key', $definition['items'] ) ) ) )
				: array();

			if ( '' === $title || empty( $items ) ) {
				continue;
			}

			$raw_source = sanitize_key( (string) ( $definition['source'] ?? '' ) );
			$source     = 'included' === $raw_source ? 'included' : 'extension';
			$keywords   = isset( $definition['keywords'] ) && is_array( $definition['keywords'] )
				? array_values( array_filter( array_map( 'sanitize_text_field', $definition['keywords'] ) ) )
				: array();

			$normalized[ $id ] = array(
				'id'            => $id,
				'title'         => $title,
				'description'   => sanitize_text_field( (string) ( $definition['description'] ?? '' ) ),
				'use'           => sanitize_text_field( (string) ( $definition['use'] ?? '' ) ),
				'preview_url'   => esc_url_raw( (string) ( $definition['preview_url'] ?? '' ) ),
				'items'         => $items,
				'keywords'      => $keywords,
				'config_preset' => sanitize_file_name( (string) ( $definition['config_preset'] ?? '' ) ),
				'site_setup'    => $this->normalize_site_setup( $definition['site_setup'] ?? array(), $items, $title ),
				'source'        => $source,
				'source_label'  => sanitize_text_field( (string) ( $definition['source_label'] ?? '' ) ),
			);
		}

		ksort( $normalized );
		$this->definitions        = $normalized;
		$this->definitions_locale = $locale;

		return $this->definitions;
	}

	/**
	 * Normalize the optional complete-site setup manifest without accepting
	 * executable callbacks or content outside this Kit's existing page items.
	 *
	 * @param mixed             $raw Raw manifest.
	 * @param array<int,string> $items Normalized Kit item IDs.
	 * @param string            $kit_title Kit title used as a menu-name fallback.
	 * @return array<string,mixed>
	 */
	private function normalize_site_setup( mixed $raw, array $items, string $kit_title ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$available = array_fill_keys( $items, true );
		$pages     = array();
		foreach ( (array) ( $raw['pages'] ?? array() ) as $page ) {
			if ( ! is_array( $page ) ) {
				continue;
			}
			$key  = sanitize_key( (string) ( $page['key'] ?? '' ) );
			$item = sanitize_key( (string) ( $page['item'] ?? '' ) );
			if ( '' === $key || '' === $item || ! isset( $available[ $item ] ) || isset( $pages[ $key ] ) ) {
				continue;
			}
			$pages[ $key ] = array(
				'key'   => $key,
				'item'  => $item,
				'title' => sanitize_text_field( (string) ( $page['title'] ?? $key ) ),
			);
		}

		if ( empty( $pages ) ) {
			return array();
		}

		$navigation = array();
		foreach ( (array) ( $raw['navigation'] ?? array() ) as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$indicator = sanitize_text_field( (string) ( $entry['indicator'] ?? '' ) );
			$indicator_devices = array_values(
				array_intersect(
					array( 'desktop', 'tablet', 'mobile' ),
					array_map( 'sanitize_key', is_array( $entry['indicator_devices'] ?? null ) ? $entry['indicator_devices'] : array() )
				)
			);
			if ( '' !== $indicator && empty( $indicator_devices ) ) {
				$indicator_devices = array( 'mobile' );
			}

			$page_key = sanitize_key( (string) ( $entry['page'] ?? '' ) );
			if ( '' !== $page_key && isset( $pages[ $page_key ] ) ) {
				$parent = sanitize_key( (string) ( $entry['parent'] ?? '' ) );
				$navigation[] = array(
					'type'              => 'page',
					'page'              => $page_key,
					'parent'            => isset( $pages[ $parent ] ) ? $parent : '',
					'highlight'         => ! empty( $entry['highlight'] ),
					'indicator'         => $indicator,
					'indicator_devices' => $indicator_devices,
				);
				continue;
			}

			$label       = sanitize_text_field( (string) ( $entry['label'] ?? '' ) );
			$url         = esc_url_raw( (string) ( $entry['url'] ?? '' ) );
			$target_page = sanitize_key( (string) ( $entry['target_page'] ?? '' ) );
			$target_page = isset( $pages[ $target_page ] ) ? $target_page : '';
			if ( '' === $label || ( '' === $url && '' === $target_page ) ) {
				continue;
			}
			$navigation[] = array(
				'type'              => 'custom',
				'label'             => $label,
				'url'               => $url,
				'target_page'       => $target_page,
				'highlight'         => ! empty( $entry['highlight'] ),
				'indicator'         => $indicator,
				'indicator_devices' => $indicator_devices,
			);
		}

		$front_page = sanitize_key( (string) ( $raw['front_page'] ?? '' ) );
		if ( ! isset( $pages[ $front_page ] ) ) {
			$front_page = '';
		}

		return array(
			'pages'         => array_values( $pages ),
			'front_page'    => $front_page,
			'menu_name'     => sanitize_text_field( (string) ( $raw['menu_name'] ?? $kit_title ) ),
			'menu_location' => sanitize_key( (string) ( $raw['menu_location'] ?? 'primary' ) ),
			'navigation'    => $navigation,
		);
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	private function included_definitions(): array {
		$definitions = array();
		$directory   = CRECEWEB_LUMEN_LITE_DIR . 'src/Library/kits/';

		foreach ( glob( $directory . '*.php' ) ?: array() as $file ) {
			$definition = require $file;
			if ( ! is_array( $definition ) ) {
				continue;
			}

			$id = sanitize_key( (string) ( $definition['id'] ?? '' ) );
			if ( '' === $id ) {
				continue;
			}

			$definition['source'] = 'included';
			if ( empty( $definition['source_label'] ) ) {
				$definition['source_label'] = __( 'Built-in', 'creceweb-lumen-lite' );
			}
			$definitions[ $id ] = $definition;
		}

		return $definitions;
	}
}
