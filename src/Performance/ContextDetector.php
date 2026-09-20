<?php
/**
 * Resolves the request context and whether enqueue collection is ready.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Performance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ContextDetector {
	/**
	 * @param array<string,mixed> $args Optional explicit context.
	 * @return array{name:string,screen_id:string,hook_suffix:string,ready:bool}
	 */
	public static function detect( array $args = array() ): array {
		$name        = isset( $args['context'] ) ? sanitize_key( (string) $args['context'] ) : '';
		$screen_id   = isset( $args['screen_id'] ) ? sanitize_key( (string) $args['screen_id'] ) : '';
		$hook_suffix = isset( $args['hook_suffix'] ) ? sanitize_key( (string) $args['hook_suffix'] ) : '';

		if ( '' === $name ) {
			if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
				$name = 'customizer';
			} elseif ( function_exists( 'is_admin' ) && is_admin() ) {
				$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
				if ( is_object( $screen ) ) {
					if ( '' === $screen_id && isset( $screen->id ) ) {
						$screen_id = sanitize_key( (string) $screen->id );
					}
					if ( method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() ) {
						$name = 'block_editor';
					}
				}

				if ( '' === $name ) {
					$name = str_contains( $screen_id, 'creceweb-lumen' ) ? 'admin_lumen' : 'admin_other';
				}
			} else {
				$name = 'frontend';
			}
		}

		$ready = self::is_ready( $name );

		if ( array_key_exists( 'ready', $args ) ) {
			$ready = (bool) $args['ready'];
		}

		return array(
			'name'        => $name,
			'screen_id'   => $screen_id,
			'hook_suffix' => $hook_suffix,
			'ready'       => $ready,
		);
	}

	/** @return bool */
	private static function is_ready( string $context ): bool {
		if ( ! function_exists( 'did_action' ) ) {
			return false;
		}

		if ( 'frontend' === $context || 'customizer' === $context ) {
			return did_action( 'wp_enqueue_scripts' ) > 0;
		}

		if ( 'block_editor' === $context ) {
			return did_action( 'enqueue_block_assets' ) > 0
				&& did_action( 'enqueue_block_editor_assets' ) > 0;
		}

		if ( 'admin_lumen' === $context || 'admin_other' === $context ) {
			return did_action( 'admin_enqueue_scripts' ) > 0;
		}

		return true;
	}
}
