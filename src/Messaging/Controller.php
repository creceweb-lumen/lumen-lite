<?php
/**
 * Global provider-neutral Messaging settings and front-end runtime.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Messaging;

use CreceWeb\LumenLite\Data\Settings;
use CreceWeb\LumenLite\Support\AdminRedirect;
use CreceWeb\LumenLite\Support\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Owns one complete global Messaging channel in Lumen Lite. */
final class Controller {
	private bool $rendered = false;

	public function __construct(
		private Compatibility $compatibility,
		private MessageResolver $message_resolver,
		private UrlResolver $url_resolver,
		private GlyphResolver $glyph_resolver,
		private StyleResolver $style_resolver
	) {}

	/** @return void */
	public function register(): void {
		add_action( 'after_setup_theme', array( $this, 'maybe_migrate_theme_settings' ), 120 );
		add_action( 'admin_post_cw_lumen_lite_save_messaging', array( $this, 'handle_save' ) );
		add_filter( 'creceweb_lumen_global_messaging_config', array( $this, 'filter_global_config' ), 20 );
		add_filter( 'creceweb_lumen_global_whatsapp_config', array( $this, 'legacy_whatsapp_config' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 35 );
		add_action( 'wp_body_open', array( $this, 'render' ), 20 );
		add_action( 'wp_footer', array( $this, 'render' ), 5 );
	}

	/** @return array<string,mixed> */
	public function settings(): array {
		$settings  = Settings::get();
		$messaging = isset( $settings['messaging'] ) && is_array( $settings['messaging'] ) ? $settings['messaging'] : array();

		return wp_parse_args(
			$messaging,
			array(
				'enabled'         => false,
				'provider'        => 'whatsapp',
				'appearance_mode' => 'provider',
				'providers' => array(
					'whatsapp' => array( 'number' => '', 'message' => '' ),
					'telegram' => array( 'username' => '', 'message' => '' ),
					'messenger' => array( 'username' => '' ),
					'signal' => array( 'url' => '' ),
				),
				'background_color' => '#0f172a',
				'icon_color'       => '#ffffff',
				'size'             => 46,
				'position'         => 'right',
				'devices'          => array( 'desktop' => true, 'tablet' => true, 'mobile' => true ),
			)
		);
	}

	/** @return array<string,mixed> */
	public function config(): array {
		$settings  = $this->settings();
		$provider  = sanitize_key( (string) ( $settings['provider'] ?? 'whatsapp' ) );
		$providers = isset( $settings['providers'] ) && is_array( $settings['providers'] ) ? $settings['providers'] : array();
		$active    = isset( $providers[ $provider ] ) && is_array( $providers[ $provider ] ) ? $providers[ $provider ] : array();
		$message   = in_array( $provider, array( 'whatsapp', 'telegram' ), true ) ? (string) ( $active['message'] ?? '' ) : '';
		$url       = $this->url_resolver->resolve( $provider, $active, $message );
		$destination = $this->destination( $provider, $active );
		$style = $this->style_resolver->resolve(
			$provider,
			(string) ( $settings['appearance_mode'] ?? 'provider' ),
			(string) ( $settings['background_color'] ?? '' ),
			(string) ( $settings['icon_color'] ?? '' )
		);

		$config = array(
			'enabled'          => ! empty( $settings['enabled'] ),
			'provider'         => $provider,
			'appearance_mode'  => $style['appearance_mode'],
			'url'              => $url,
			'destination'      => $destination,
			'message'          => $message,
			'background_color' => $style['background_color'],
			'icon_color'       => $style['icon_color'],
			'size'             => max( 1, absint( $settings['size'] ?? 46 ) ),
			'position'         => in_array( (string) ( $settings['position'] ?? 'right' ), array( 'left', 'right' ), true ) ? (string) $settings['position'] : 'right',
			'devices'          => isset( $settings['devices'] ) && is_array( $settings['devices'] ) ? $settings['devices'] : array( 'desktop' => true, 'tablet' => true, 'mobile' => true ),
			'owner'            => 'lumen-lite',
			'scope'            => 'global',
		);

		$filtered = apply_filters( 'creceweb_lumen_lite_messaging_config', $config );
		return is_array( $filtered ) ? $filtered : $config;
	}

	/**
	 * Supplies Lite's normalized configuration to Theme Bridge 1.6+.
	 *
	 * @param array<string,mixed> $config Existing global configuration.
	 * @return array<string,mixed>
	 */
	public function filter_global_config( array $config ): array {
		$this->compatibility->validate();
		if ( ! $this->compatibility->is_compatible() ) {
			return $config;
		}
		return $this->config();
	}

	/**
	 * Projects the active provider into the historical WhatsApp contract.
	 * Non-WhatsApp providers deliberately disable the legacy contract.
	 *
	 * @param array<string,mixed> $config Existing WhatsApp configuration.
	 * @return array<string,mixed>
	 */
	public function legacy_whatsapp_config( array $config ): array {
		$current = $this->config();
		$devices = isset( $current['devices'] ) && is_array( $current['devices'] ) ? $current['devices'] : array();
		if ( 'whatsapp' !== (string) ( $current['provider'] ?? '' ) ) {
			return array(
				'enabled'          => false,
				'number'           => '',
				'message'          => '',
				'background_color' => (string) ( $current['background_color'] ?? '#0f172a' ),
				'icon_color'       => (string) ( $current['icon_color'] ?? '#ffffff' ),
				'size'             => max( 1, absint( $current['size'] ?? 46 ) ),
				'position'         => (string) ( $current['position'] ?? 'right' ),
				'devices'          => $devices,
				'owner'            => 'lumen-lite',
			);
		}

		$number = preg_replace( '/\D+/', '', (string) ( $current['destination'] ?? '' ) );
		$number = is_string( $number ) ? substr( $number, 0, 20 ) : '';
		$legacy = array(
			'enabled'          => ! empty( $current['enabled'] ) && '' !== $number,
			'number'           => $number,
			'message'          => (string) ( $current['message'] ?? '' ),
			'background_color' => (string) ( $current['background_color'] ?? '#0f172a' ),
			'icon_color'       => (string) ( $current['icon_color'] ?? '#ffffff' ),
			'size'             => max( 1, absint( $current['size'] ?? 46 ) ),
			'position'         => (string) ( $current['position'] ?? 'right' ),
			'devices'          => $devices,
			'owner'            => 'lumen-lite',
		);

		$filtered = apply_filters( 'creceweb_lumen_lite_whatsapp_config', $legacy );
		return is_array( $filtered ) ? $filtered : $legacy;
	}

	/** Returns one plugin-owned Messaging glyph for admin/front-end parity. @return string */
	public function glyph_svg( string $provider ): string {
		return $this->glyph_resolver->svg( $provider );
	}

	/** @return array<string,string> */
	public function glyphs(): array {
		return $this->glyph_resolver->all();
	}

	/** @return array<string,array{background_color:string,icon_color:string}> */
	public function style_presets(): array {
		return $this->style_resolver->all();
	}

	/** @return bool */
	public function should_render(): bool {
		$this->compatibility->validate();
		if ( ! $this->compatibility->is_compatible() ) {
			return false;
		}

		$config = $this->config();
		$should = ! empty( $config['enabled'] ) && ! empty( $config['url'] );
		if ( function_exists( '\\CreceWeb\\Lumen\\should_render_global_messaging' ) ) {
			$should = \CreceWeb\Lumen\should_render_global_messaging( $config );
		} else {
			$should = (bool) apply_filters( 'creceweb_lumen_global_messaging_should_render', $should, $config );
		}

		// Pro 1.9.71 exposes contextual ownership through the historical WhatsApp
		// render gate. Treat that gate as a duplicate-prevention signal for the one
		// global Messaging action, regardless of which Lite provider is selected.
		// This does not restrict provider availability: all four providers remain
		// complete in Lite and render normally whenever Pro does not own/hide the
		// current context.
		if ( $should ) {
			$legacy = $this->legacy_whatsapp_config( array() );
			if ( 'whatsapp' === (string) ( $config['provider'] ?? '' ) && function_exists( '\CreceWeb\Lumen\should_render_global_whatsapp' ) ) {
				$should = \CreceWeb\Lumen\should_render_global_whatsapp( $legacy );
			} else {
				$should = (bool) apply_filters( 'creceweb_lumen_global_whatsapp_should_render', $should, $legacy );
			}
		}

		return $should;
	}

	/** @return void */
	public function enqueue_assets(): void {
		if ( ! $this->should_render() ) {
			return;
		}

		wp_register_style( 'creceweb-lumen-lite-messaging', false, array( 'creceweb-lumen' ), CRECEWEB_LUMEN_LITE_ASSET_VERSION );
		wp_enqueue_style( 'creceweb-lumen-lite-messaging' );
		wp_add_inline_style( 'creceweb-lumen-lite-messaging', $this->inline_css() );
	}

	/** @return int */
	public function style_size_bytes(): int {
		return strlen( $this->inline_css() );
	}

	/** @return string */
	private function inline_css(): string {
		return <<<'CSS'
.cw-floating-action.cw-lumen-lite-messaging{width:var(--cw-lumen-lite-messaging-size,var(--cw-floating-action-size,46px))!important;height:var(--cw-lumen-lite-messaging-size,var(--cw-floating-action-size,46px))!important;background-color:var(--cw-lumen-lite-messaging-background,var(--cw-floating-action-background,#0f172a))!important;color:var(--cw-lumen-lite-messaging-icon,var(--cw-floating-action-icon-color,#fff))!important;z-index:101}
.cw-floating-action.cw-lumen-lite-messaging svg{width:var(--cw-lumen-lite-messaging-icon-size,30px)!important;height:var(--cw-lumen-lite-messaging-icon-size,30px)!important;max-width:none;max-height:none}
.cw-floating-action.cw-lumen-lite-messaging:hover,.cw-floating-action.cw-lumen-lite-messaging:focus-visible{background-color:var(--cw-lumen-lite-messaging-background,var(--cw-floating-action-background,#0f172a))!important;color:var(--cw-lumen-lite-messaging-icon,var(--cw-floating-action-icon-color,#fff))!important;filter:brightness(.92)}
.cw-lumen-lite-messaging--left{inset-inline-start:max(1rem,env(safe-area-inset-left));inset-inline-end:auto}.cw-lumen-lite-messaging--right{inset-inline-start:auto;inset-inline-end:max(1rem,env(safe-area-inset-right))}
@media(min-width:1025px){.cw-lumen-lite-messaging--hide-desktop{display:none!important}}@media(min-width:782px) and (max-width:1024px){.cw-lumen-lite-messaging--hide-tablet{display:none!important}}@media(max-width:781px){.cw-lumen-lite-messaging--hide-mobile{display:none!important}}
CSS;
	}

	/** @return void */
	public function render(): void {
		if ( $this->rendered || ! $this->should_render() ) {
			return;
		}

		$config = $this->config();
		$url    = (string) ( $config['url'] ?? '' );
		$provider = sanitize_key( (string) ( $config['provider'] ?? '' ) );
		$svg = $this->glyph_resolver->svg( $provider );
		if ( '' === $url || '' === $svg ) {
			return;
		}
		$this->rendered = true;

		$position = in_array( (string) ( $config['position'] ?? 'right' ), array( 'left', 'right' ), true ) ? (string) $config['position'] : 'right';
		$devices  = isset( $config['devices'] ) && is_array( $config['devices'] ) ? $config['devices'] : array();
		$classes  = array( 'cw-floating-action', 'cw-lumen-lite-messaging', 'cw-lumen-lite-messaging--' . $position, 'cw-lumen-lite-messaging--' . $provider );
		if ( 'whatsapp' === $provider ) {
			$classes[] = 'cw-floating-action--whatsapp';
			$classes[] = 'cw-lumen-lite-whatsapp';
		}
		foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
			if ( empty( $devices[ $device ] ) ) {
				$classes[] = 'cw-lumen-lite-messaging--hide-' . $device;
			}
		}

		$background = sanitize_hex_color( (string) ( $config['background_color'] ?? '' ) ) ?: '#0f172a';
		$icon       = sanitize_hex_color( (string) ( $config['icon_color'] ?? '' ) ) ?: '#ffffff';
		$size       = max( 1, absint( $config['size'] ?? 46 ) );
		$icon_size  = round( $size * 0.65, 2 );
		$style      = sprintf(
			'--cw-floating-action-background:%1$s;--cw-floating-action-icon-color:%2$s;--cw-floating-action-size:%3$dpx;--cw-lumen-lite-messaging-background:%1$s;--cw-lumen-lite-messaging-icon:%2$s;--cw-lumen-lite-messaging-size:%3$dpx;--cw-lumen-lite-messaging-icon-size:%4$spx;background-color:%1$s;color:%2$s;width:%3$dpx;height:%3$dpx',
			$background,
			$icon,
			$size,
			(string) $icon_size
		);
		$labels = array(
			'whatsapp'  => __( 'WhatsApp', 'creceweb-lumen-lite' ),
			'telegram'  => __( 'Telegram', 'creceweb-lumen-lite' ),
			'messenger' => __( 'Messenger', 'creceweb-lumen-lite' ),
			'signal'    => __( 'Signal', 'creceweb-lumen-lite' ),
		);
		/* translators: %s: messaging provider name. */
		$label = sprintf( __( 'Contact via %s', 'creceweb-lumen-lite' ), $labels[ $provider ] ?? $provider );
		$legacy_data = 'whatsapp' === $provider ? ' data-cw-lumen-whatsapp-owner="lumen-lite"' : '';

		printf(
			'<a class="%1$s" href="%2$s" target="_blank" rel="noopener noreferrer" aria-label="%3$s" style="%4$s" data-cw-lumen-messaging-owner="lumen-lite" data-cw-lumen-messaging-provider="%5$s" data-cw-lumen-floating-side="%6$s"%8$s>%7$s<span class="screen-reader-text">%9$s</span></a>',
			esc_attr( implode( ' ', array_map( 'sanitize_html_class', $classes ) ) ),
			esc_url( $url ),
			esc_attr( $label ),
			esc_attr( $style ),
			esc_attr( $provider ),
			esc_attr( $position ),
			$svg, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed plugin-controlled SVG from GlyphResolver.
			$legacy_data, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed compatibility attribute.
			esc_html( $label )
		);
	}

	/** @return void */
	public function maybe_migrate_theme_settings(): void {
		$settings = Settings::get();
		$migration_source = (string) ( $settings['messaging_migration_source'] ?? '' );
		if ( ! in_array( $migration_source, array( '', 'fresh' ), true ) || ! empty( $settings['whatsapp_migration_complete'] ) ) {
			return;
		}
		$this->compatibility->validate();
		if ( ! $this->compatibility->is_compatible() || ! function_exists( '\\CreceWeb\\Lumen\\get_customizations' ) ) {
			return;
		}

		$theme   = \CreceWeb\Lumen\get_customizations();
		$theme   = is_array( $theme ) ? $theme : array();
		$number  = preg_replace( '/\D+/', '', (string) ( $theme['whatsapp_number'] ?? '' ) );
		$number  = is_string( $number ) ? substr( $number, 0, 20 ) : '';
		$message = sanitize_textarea_field( (string) ( $theme['whatsapp_message'] ?? '' ) );

		$has_theme_legacy = 'whatsapp' === (string) ( $theme['floating_action'] ?? '' ) || '' !== $number || '' !== trim( $message );
		if ( ! $has_theme_legacy ) {
			$settings['messaging_migration_complete'] = true;
			$settings['messaging_migration_source']   = 'fresh';
			$settings['whatsapp_migration_complete']  = true;
			$settings['whatsapp_migration_source']    = 'fresh';
			Settings::update( $settings );
			return;
		}

		$legacy = array(
			'enabled'          => 'whatsapp' === (string) ( $theme['floating_action'] ?? '' ) && '' !== $number,
			'number'           => $number,
			'message'          => $message,
			'background_color' => sanitize_hex_color( (string) ( $theme['floating_action_background_color'] ?? '' ) ) ?: '#0f172a',
			'icon_color'       => sanitize_hex_color( (string) ( $theme['floating_action_icon_color'] ?? '' ) ) ?: '#ffffff',
			'size'             => max( 1, absint( $theme['floating_action_size'] ?? 46 ) ),
			'position'         => 'right',
			'devices'          => array( 'desktop' => true, 'tablet' => true, 'mobile' => true ),
		);
		$messaging = isset( $settings['messaging'] ) && is_array( $settings['messaging'] ) ? $settings['messaging'] : array();
		$providers = isset( $messaging['providers'] ) && is_array( $messaging['providers'] ) ? $messaging['providers'] : array();
		$providers['whatsapp'] = array( 'number' => $legacy['number'], 'message' => $legacy['message'] );
		$messaging = array_merge(
			$messaging,
			array(
				'enabled'          => $legacy['enabled'],
				'provider'         => 'whatsapp',
				'appearance_mode'  => 'custom',
				'providers'        => $providers,
				'background_color' => $legacy['background_color'],
				'icon_color'       => $legacy['icon_color'],
				'size'             => $legacy['size'],
				'position'         => $legacy['position'],
				'devices'          => $legacy['devices'],
			)
		);
		$settings['messaging']                    = $messaging;
		$settings['messaging_migration_complete'] = true;
		$settings['messaging_migration_source']   = 'theme-legacy';
		$settings['whatsapp']                     = $legacy;
		$settings['whatsapp_migration_complete']  = true;
		$settings['whatsapp_migration_source']    = 'theme-legacy';
		Settings::update( $settings );
	}

	/** @return void */
	public function handle_save(): void {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'Your user account does not have permission to save these changes.', 'creceweb-lumen-lite' ) );
		}
		check_admin_referer( 'cw_lumen_lite_save_messaging' );
		$return_url = isset( $_POST[ AdminRedirect::FIELD ] )
			? esc_url_raw( wp_unslash( (string) $_POST[ AdminRedirect::FIELD ] ) )
			: ( isset( $_POST['_wp_http_referer'] ) ? esc_url_raw( wp_unslash( (string) $_POST['_wp_http_referer'] ) ) : '' );
		$posted = isset( $_POST['messaging'] ) && is_array( $_POST['messaging'] )
			? map_deep( wp_unslash( $_POST['messaging'] ), 'sanitize_textarea_field' )
			: array();
		Settings::update_messaging( $posted );
		$url = AdminRedirect::target( $return_url, admin_url( 'themes.php' ), 'messaging_saved' );
		wp_safe_redirect( esc_url_raw( $url ) );
		exit;
	}

	/** @return string */
	private function destination( string $provider, array $active ): string {
		return match ( $provider ) {
			'whatsapp' => preg_replace( '/\D+/', '', (string) ( $active['number'] ?? '' ) ) ?: '',
			'telegram', 'messenger' => (string) ( $active['username'] ?? '' ),
			'signal' => (string) ( $active['url'] ?? '' ),
			default => '',
		};
	}
}
