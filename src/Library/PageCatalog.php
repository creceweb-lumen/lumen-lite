<?php
/**
 * Lumen Library full-page definitions.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Library;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads full-page compositions built entirely from Lite-owned patterns.
 */
final class PageCatalog {
	/** @var array<string,array<string,mixed>>|null */
	private ?array $definitions = null;

	/** Locale used to build the cached translated definitions. */
	private ?string $definitions_locale = null;

	/**
	 * @return array<string,array<string,mixed>>
	 */
	public function definitions(): array {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();

		if ( null !== $this->definitions && $locale === $this->definitions_locale ) {
			return $this->definitions;
		}

		$this->definitions        = array();
		$this->definitions_locale = $locale;
		$directory                = CRECEWEB_LUMEN_LITE_DIR . 'src/Library/pages/';

		foreach ( glob( $directory . '*.php' ) ?: array() as $file ) {
			if ( str_starts_with( basename( $file ), '_' ) ) {
				continue;
			}

			$definition = require $file;
			if ( ! is_array( $definition ) ) {
				continue;
			}

			$slug = sanitize_key( (string) ( $definition['slug'] ?? '' ) );
			if ( '' === $slug || empty( $definition['content'] ) ) {
				continue;
			}

			$definition['slug']        = $slug;
			$definition['title']       = sanitize_text_field( (string) ( $definition['title'] ?? $slug ) );
			$definition['description'] = sanitize_text_field( (string) ( $definition['description'] ?? '' ) );
			$definition['keywords']    = isset( $definition['keywords'] ) && is_array( $definition['keywords'] )
				? array_values( array_filter( array_map( 'sanitize_text_field', $definition['keywords'] ) ) )
				: array();
			$this->definitions[ $slug ] = $definition;
		}

		ksort( $this->definitions );
		return $this->definitions;
	}
}
