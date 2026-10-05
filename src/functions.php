<?php
/**
 * Public helper functions for Lumen extensions.
 *
 * @package CreceWebLumenLite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! function_exists( 'cw_lumen_lite_api_version' ) ) {
	/** Returns the stable Lite extension API version. */
	function cw_lumen_lite_api_version(): string {
		return defined( 'CRECEWEB_LUMEN_LITE_API_VERSION' ) ? (string) CRECEWEB_LUMEN_LITE_API_VERSION : '';
	}
}

if ( ! function_exists( 'cw_lumen_lite_version' ) ) {
	/** Returns the active Lite plugin version. */
	function cw_lumen_lite_version(): string {
		return defined( 'CRECEWEB_LUMEN_LITE_VERSION' ) ? (string) CRECEWEB_LUMEN_LITE_VERSION : '';
	}
}

if ( ! function_exists( 'cw_lumen_lite' ) ) {
	/**
	 * Returns the shared Lite service owner.
	 *
	 * @return \CreceWeb\LumenLite\Plugin|null
	 */
	function cw_lumen_lite(): ?\CreceWeb\LumenLite\Plugin {
		return \CreceWeb\LumenLite\Plugin::instance();
	}
}


if ( ! function_exists( 'cw_lumen_lite_library_url' ) ) {
	/** Returns the Gutenberg editor URL that opens the shared Lumen Library. */
	function cw_lumen_lite_library_url(): string {
		return add_query_arg( 'cw_lumen_library', '1', admin_url( 'post-new.php?post_type=page' ) );
	}
}

if ( ! function_exists( 'cw_lumen_lite_library_catalog' ) ) {
	/**
	 * Returns the filtered shared pattern catalog.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	function cw_lumen_lite_library_catalog(): array {
		$plugin = cw_lumen_lite();
		return null !== $plugin ? $plugin->catalog()->items() : array();
	}
}

if ( ! function_exists( 'cw_lumen_lite_library_families' ) ) {
	/**
	 * Returns the filtered pattern families.
	 *
	 * @return array<string,array<string,string>>
	 */
	function cw_lumen_lite_library_families(): array {
		$plugin = cw_lumen_lite();
		return null !== $plugin ? $plugin->catalog()->families() : array();
	}
}

if ( ! function_exists( 'cw_lumen_lite_library_kits' ) ) {
	/**
	 * Returns data-only Kit groupings registered in the shared Library.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	function cw_lumen_lite_library_kits(): array {
		$plugin = cw_lumen_lite();
		return null !== $plugin ? $plugin->kit_catalog()->items() : array();
	}
}


if ( ! function_exists( 'cw_lumen_lite_is_theme_compatible' ) ) {
	/**
	 * Returns whether the local Theme and bridge are compatible.
	 *
	 * @return bool
	 */
	function cw_lumen_lite_is_theme_compatible(): bool {
		$plugin = cw_lumen_lite();
		return null !== $plugin && $plugin->compatibility()->validate();
	}
}

if ( ! function_exists( 'cw_lumen_lite_footer_credit' ) ) {
	/**
	 * Returns the expanded Lite footer credit.
	 *
	 * @return string
	 */
	function cw_lumen_lite_footer_credit(): string {
		$plugin = cw_lumen_lite();
		return null !== $plugin ? $plugin->footer()->expanded_text() : '';
	}
}


if ( ! function_exists( 'cw_lumen_lite_reading_minutes' ) ) {
	/**
	 * Returns the shared estimated reading minutes for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	function cw_lumen_lite_reading_minutes( int $post_id ): int {
		$plugin = cw_lumen_lite();
		return null !== $plugin ? $plugin->content()->reading_minutes( $post_id ) : 0;
	}
}

if ( ! function_exists( 'cw_lumen_lite_reading_time_locations' ) ) {
	/**
	 * Returns the filtered reading-time locations available to extensions.
	 *
	 * @return array<int,string>
	 */
	function cw_lumen_lite_reading_time_locations(): array {
		$plugin = cw_lumen_lite();
		return null !== $plugin ? $plugin->content()->reading_time_locations() : array();
	}
}


if ( ! function_exists( 'cw_lumen_lite_floating_action_config' ) ) {
	/**
	 * Returns the global advanced floating-action configuration owned by Lite.
	 *
	 * @return array<string,mixed>
	 */
	function cw_lumen_lite_floating_action_config(): array {
		$plugin = cw_lumen_lite();
		return null !== $plugin ? $plugin->floating_action()->config() : array();
	}
}

if ( ! function_exists( 'cw_lumen_lite_messaging_config' ) ) {
	/**
	 * Returns the normalized global Messaging configuration owned by Lumen Lite.
	 *
	 * @return array<string,mixed>
	 */
	function cw_lumen_lite_messaging_config(): array {
		$plugin = cw_lumen_lite();
		return null !== $plugin ? $plugin->messaging()->config() : array();
	}
}

if ( ! function_exists( 'cw_lumen_lite_messaging_style_presets' ) ) {
	/** Returns provider-aware Messaging color presets.
	 * @return array<string,array{background_color:string,icon_color:string}>
	 */
	function cw_lumen_lite_messaging_style_presets(): array {
		$plugin = cw_lumen_lite();
		return null !== $plugin ? $plugin->messaging_style_resolver()->all() : array();
	}
}

if ( ! function_exists( 'cw_lumen_lite_resolve_messaging_message' ) ) {
	/**
	 * Resolves Messaging variables for WhatsApp or Telegram.
	 *
	 * @param string              $template Message template.
	 * @param array<string,mixed> $context  Optional site/title/description/url overrides.
	 * @param string              $provider Provider slug.
	 * @return string
	 */
	function cw_lumen_lite_resolve_messaging_message( string $template, array $context = array(), string $provider = '' ): string {
		$plugin = cw_lumen_lite();
		return null !== $plugin ? $plugin->messaging_message_resolver()->resolve( $template, $context, $provider ) : $template;
	}
}

if ( ! function_exists( 'cw_lumen_lite_whatsapp_config' ) ) {
	/**
	 * Returns the historical WhatsApp projection for compatibility.
	 *
	 * @return array<string,mixed>
	 */
	function cw_lumen_lite_whatsapp_config(): array {
		$plugin = cw_lumen_lite();
		return null !== $plugin ? $plugin->messaging()->legacy_whatsapp_config( array() ) : array();
	}
}

if ( ! function_exists( 'cw_lumen_lite_resolve_whatsapp_message' ) ) {
	/**
	 * Resolves historical WhatsApp message variables for compatibility.
	 *
	 * @param string              $template Message template.
	 * @param array<string,mixed> $context  Optional site/title/description/url overrides.
	 * @return string
	 */
	function cw_lumen_lite_resolve_whatsapp_message( string $template, array $context = array() ): string {
		$plugin = cw_lumen_lite();
		return null !== $plugin ? $plugin->messaging_message_resolver()->resolve( $template, $context, 'whatsapp' ) : $template;
	}
}

