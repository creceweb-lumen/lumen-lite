<?php
/**
 * Safe return URLs for Lumen Lite settings forms.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps settings forms on the same Lumen screen after saving.
 */
final class AdminRedirect {
	public const FIELD = 'cw_lumen_lite_return_url';

	/**
	 * @param string $requested_url URL submitted by the settings form.
	 * @param string $fallback Safe fallback URL.
	 * @param string $notice Notice identifier.
	 * @return string
	 */
	public static function target( string $requested_url, string $fallback, string $notice ): string {
		$url = '' !== $requested_url ? wp_validate_redirect( $requested_url, '' ) : '';
		if ( '' === $url ) {
			$url = $fallback;
		}

		$url = remove_query_arg(
			array(
				'cw_lumen_lite_notice',
				'_wpnonce',
				'_wp_http_referer',
			),
			$url
		);

		return add_query_arg( 'cw_lumen_lite_notice', sanitize_key( $notice ), $url );
	}
}
