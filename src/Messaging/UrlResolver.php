<?php
/**
 * Provider-neutral Messaging URL resolution.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Messaging;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Builds outbound provider URLs without network requests. */
final class UrlResolver {
	public function __construct( private MessageResolver $message_resolver ) {}

	/**
	 * @param string              $provider        Provider key.
	 * @param array<string,mixed> $provider_config Active provider values.
	 * @param string              $message_template Optional message template.
	 * @param array<string,mixed> $context         Optional message context.
	 * @return string
	 */
	public function resolve( string $provider, array $provider_config, string $message_template = '', array $context = array() ): string {
		$provider = sanitize_key( $provider );

		switch ( $provider ) {
			case 'whatsapp':
				$number = preg_replace( '/\D+/', '', (string) ( $provider_config['number'] ?? '' ) );
				$number = is_string( $number ) ? substr( $number, 0, 20 ) : '';
				if ( '' === $number ) {
					return '';
				}
				$url = 'https://wa.me/' . $number;
				$message = trim( $this->message_resolver->resolve( $message_template, $context, 'whatsapp' ) );
				return '' !== $message ? $url . '?text=' . rawurlencode( $message ) : $url;

			case 'telegram':
				$username = ltrim( trim( (string) ( $provider_config['username'] ?? '' ) ), '@' );
				$username = preg_replace( '/[^A-Za-z0-9_]/', '', $username );
				$username = is_string( $username ) ? substr( $username, 0, 64 ) : '';
				if ( '' === $username ) {
					return '';
				}
				$url = 'https://t.me/' . rawurlencode( $username );
				$message = trim( $this->message_resolver->resolve( $message_template, $context, 'telegram' ) );
				return '' !== $message ? $url . '?text=' . rawurlencode( $message ) : $url;

			case 'messenger':
				$username = ltrim( trim( (string) ( $provider_config['username'] ?? '' ) ), '@/ ' );
				$username = preg_replace( '/[^A-Za-z0-9._-]/', '', $username );
				if ( ! is_string( $username ) || '' === $username ) {
					return '';
				}
				return 'https://m.me/' . rawurlencode( $username );

			case 'signal':
				return $this->signal_url( (string) ( $provider_config['url'] ?? '' ) );
		}

		return '';
	}

	/** @return string */
	private function signal_url( string $url ): string {
		$url = trim( $url );
		if ( '' === $url ) {
			return '';
		}
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) ) {
			return '';
		}
		$scheme = strtolower( (string) ( $parts['scheme'] ?? '' ) );
		$host   = strtolower( (string) ( $parts['host'] ?? '' ) );
		if ( 'https' !== $scheme || ! in_array( $host, array( 'signal.me', 'signal.link' ), true ) ) {
			return '';
		}
		if ( isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return '';
		}
		return esc_url_raw( $url );
	}
}
