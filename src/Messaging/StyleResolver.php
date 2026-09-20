<?php
/**
 * Provider-aware Messaging appearance presets.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Messaging;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Resolves effective Messaging colors without adding frontend assets. */
final class StyleResolver {
	/** @var array<string,array{background_color:string,icon_color:string}> */
	private const PRESETS = array(
		'whatsapp'  => array( 'background_color' => '#128c7e', 'icon_color' => '#ffffff' ),
		'telegram'  => array( 'background_color' => '#229ed9', 'icon_color' => '#ffffff' ),
		'messenger' => array( 'background_color' => '#0866ff', 'icon_color' => '#ffffff' ),
		'signal'    => array( 'background_color' => '#3a76f0', 'icon_color' => '#ffffff' ),
	);

	/** @param mixed $mode Raw appearance mode. */
	public function sanitize_mode( $mode ): string {
		$mode = sanitize_key( (string) $mode );
		return in_array( $mode, array( 'provider', 'custom' ), true ) ? $mode : 'provider';
	}

	/** @param mixed $provider Raw provider. */
	public function sanitize_provider( $provider ): string {
		$provider = sanitize_key( (string) $provider );
		return isset( self::PRESETS[ $provider ] ) ? $provider : 'whatsapp';
	}

	/** @return array{background_color:string,icon_color:string} */
	public function preset( string $provider ): array {
		$provider = $this->sanitize_provider( $provider );
		return self::PRESETS[ $provider ];
	}

	/** @return array<string,array{background_color:string,icon_color:string}> */
	public function all(): array {
		return self::PRESETS;
	}

	/**
	 * @return array{appearance_mode:string,background_color:string,icon_color:string}
	 */
	public function resolve( string $provider, string $mode, string $background_color = '', string $icon_color = '' ): array {
		$mode = $this->sanitize_mode( $mode );
		if ( 'provider' === $mode ) {
			$preset = $this->preset( $provider );
			return array(
				'appearance_mode'  => 'provider',
				'background_color' => $preset['background_color'],
				'icon_color'       => $preset['icon_color'],
			);
		}

		return array(
			'appearance_mode'  => 'custom',
			'background_color' => sanitize_hex_color( $background_color ) ?: '#0f172a',
			'icon_color'       => sanitize_hex_color( $icon_color ) ?: '#ffffff',
		);
	}
}
