<?php
/**
 * Shared Core Icon block factory.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Library;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds an editable Core Icon block with a dependency-free fallback.
 */
final class IconFactory {
	/**
	 * @param string              $preferred_icon Preferred Core icon name.
	 * @param array<int,string>   $context_classes Context-specific classes.
	 * @param string              $modifier Visual modifier.
	 * @param array<string,mixed> $block_attributes Optional block attributes.
	 * @return string
	 */
	public static function block( string $preferred_icon, array $context_classes = array(), string $modifier = 'info', array $block_attributes = array() ): string {
		$context_classes = array_values(
			array_filter(
				array_map( 'sanitize_html_class', $context_classes )
			)
		);
		$modifier_class = 'cw-lumen-icon-slot--' . sanitize_html_class( $modifier );
		$base_classes   = array_merge( array( 'cw-lumen-icon-slot', $modifier_class ), $context_classes );

		if ( self::native_icon_available() ) {
			$attributes              = $block_attributes;
			$attributes['icon']      = self::resolve_icon_name( $preferred_icon );
			$attributes['className'] = implode( ' ', array_merge( $base_classes, array( 'cw-lumen-icon-slot--native' ) ) );

			return '<!-- wp:icon ' . wp_json_encode( $attributes, JSON_UNESCAPED_SLASHES ) . ' /-->';
		}

		$classes = implode( ' ', array_merge( $base_classes, array( 'cw-lumen-icon-slot--legacy' ) ) );

		return implode(
			"\n",
			array(
				'<!-- wp:group {"className":"' . esc_attr( $classes ) . '","layout":{"type":"constrained"}} -->',
				'<div class="wp-block-group ' . esc_attr( $classes ) . '"></div>',
				'<!-- /wp:group -->',
			)
		);
	}

	/** @return bool */
	public static function native_icon_available(): bool {
		return class_exists( '\\WP_Block_Type_Registry' )
			&& \WP_Block_Type_Registry::get_instance()->is_registered( 'core/icon' )
			&& class_exists( '\\WP_Icons_Registry' );
	}

	/**
	 * @param string $preferred_icon Preferred icon.
	 * @return string
	 */
	private static function resolve_icon_name( string $preferred_icon ): string {
		$registry = \WP_Icons_Registry::get_instance();

		if ( $registry->is_registered( $preferred_icon ) ) {
			return $preferred_icon;
		}

		return $registry->is_registered( 'core/info' ) ? 'core/info' : $preferred_icon;
	}
}
