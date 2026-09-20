<?php
/**
 * Reads the current WordPress enqueue state without modifying it.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Performance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WordPressAssetObserver implements AssetObserverInterface {
	/** @inheritDoc */
	public function observe( string $type, string $handle ): array {
		$checker = 'style' === $type ? 'wp_style_is' : 'wp_script_is';

		if ( ! function_exists( $checker ) ) {
			return array(
				'observed' => false,
				'state'    => 'unavailable',
				'source'   => '',
			);
		}

		$source = $this->source( $type, $handle );

		if ( $checker( $handle, 'done' ) ) {
			return array(
				'observed' => true,
				'state'    => 'done',
				'source'   => $source,
			);
		}

		if ( $checker( $handle, 'enqueued' ) ) {
			return array(
				'observed' => true,
				'state'    => 'enqueued',
				'source'   => $source,
			);
		}

		if ( $checker( $handle, 'registered' ) ) {
			return array(
				'observed' => false,
				'state'    => 'registered',
				'source'   => $source,
			);
		}

		return array(
			'observed' => false,
			'state'    => 'not_registered',
			'source'   => '',
		);
	}

	/**
	 * Returns a privacy-safe asset source without query strings or fragments.
	 *
	 * @param string $type Asset type.
	 * @param string $handle Asset handle.
	 * @return string
	 */
	private function source( string $type, string $handle ): string {
		global $wp_styles, $wp_scripts;

		$registry = 'style' === $type ? $wp_styles : $wp_scripts;
		if ( ! is_object( $registry ) || ! isset( $registry->registered[ $handle ] ) ) {
			return '';
		}

		$registered = $registry->registered[ $handle ];
		$source     = isset( $registered->src ) && is_scalar( $registered->src )
			? (string) $registered->src
			: '';

		if ( '' === $source ) {
			return '';
		}

		$source = preg_replace( '/[?#].*$/', '', $source );
		return is_string( $source ) ? sanitize_text_field( $source ) : '';
	}
}
