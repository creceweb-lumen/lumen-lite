<?php
/**
 * Internal helper for composing Lite page definitions from validated sections.
 *
 * @package CreceWebLumenLite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( array $slugs ): string {
	$parts = array();
	$base  = CRECEWEB_LUMEN_LITE_DIR . 'src/Library/patterns/';

	foreach ( $slugs as $slug ) {
		$slug = sanitize_key( (string) $slug );
		$file = $base . $slug . '.php';
		if ( '' === $slug || ! is_readable( $file ) ) {
			continue;
		}

		$definition = require $file;
		if ( is_array( $definition ) && isset( $definition['content'] ) && is_string( $definition['content'] ) ) {
			$parts[] = $definition['content'];
		}
	}

	return implode( "\n\n", $parts );
};
