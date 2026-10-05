<?php
/**
 * Lumen palette-based color mode.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\ColorMode;

use CreceWeb\LumenLite\Data\Settings;
use CreceWeb\LumenLite\Support\AdminRedirect;
use CreceWeb\LumenLite\Support\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Applies a visitor-selectable dark palette to Lumen semantic tokens.
 */
final class Controller {
	public const ACTION_SAVE = 'cw_lumen_lite_save_color_mode';
	public const STORAGE_KEY = 'cw_lumen_color_mode';
	private bool $floating_rendered = false;
	private bool $switch_script_requested = false;
	/** @var array<string,mixed>|null */
	private ?array $config_cache = null;

	public function __construct( private Compatibility $compatibility ) {}

	/** @return void */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION_SAVE, array( $this, 'handle_save' ) );
		add_action( 'init', array( $this, 'register_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 110 );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_editor_style' ), 120 );
		add_action( 'wp_footer', array( $this, 'render_floating_switch' ), 4 );
		add_filter( 'wp_nav_menu_items', array( $this, 'filter_primary_menu_items' ), 20, 2 );
	}

	/** @return void */
	public function register_shortcode(): void {
		add_shortcode( 'lumen_color_mode_switch', array( $this, 'shortcode' ) );
	}

	/** @return array<string,mixed> */
	public function settings(): array {
		$settings = Settings::get();
		return isset( $settings['color_mode'] ) && is_array( $settings['color_mode'] )
			? $settings['color_mode']
			: array();
	}

	/** @return array<string,mixed> */
	public function config(): array {
		if ( null !== $this->config_cache ) {
			return $this->config_cache;
		}

		$config   = $this->settings();
		$filtered = apply_filters( 'creceweb_lumen_lite_color_mode_config', $config );
		$this->config_cache = is_array( $filtered ) ? $filtered : $config;
		return $this->config_cache;
	}

	/** @return bool */
	public function is_enabled(): bool {
		$this->compatibility->validate();
		$config = $this->config();
		return $this->compatibility->is_compatible() && ! empty( $config['enabled'] );
	}

	/** @return bool */
	public function should_render_floating_switch(): bool {
		if ( ! $this->is_enabled() ) {
			return false;
		}

		$config  = $this->config();
		$devices = isset( $config['devices'] ) && is_array( $config['devices'] ) ? $config['devices'] : array();
		return ! empty( $config['show_switch'] ) && in_array( true, array_map( 'boolval', $devices ), true );
	}

	/** @return bool */
	public function should_render_menu_switch(): bool {
		if ( ! $this->is_enabled() ) {
			return false;
		}

		$config = $this->config();
		return ! empty( $config['show_menu_switch'] );
	}

	/** @return void */
	public function enqueue_assets(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$style_dependencies = ( wp_style_is( 'creceweb-lumen-customization', 'registered' ) || wp_style_is( 'creceweb-lumen-customization', 'enqueued' ) )
			? array( 'creceweb-lumen-customization' )
			: array( 'creceweb-lumen' );

		wp_register_style(
			'creceweb-lumen-lite-color-mode',
			false,
			$style_dependencies,
			CRECEWEB_LUMEN_LITE_ASSET_VERSION
		);
		wp_enqueue_style( 'creceweb-lumen-lite-color-mode' );
		wp_add_inline_style( 'creceweb-lumen-lite-color-mode', $this->style_css() );

		if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
			$customizer_css = $this->customizer_style_css();
			if ( '' !== $customizer_css ) {
				wp_add_inline_style( 'creceweb-lumen-lite-color-mode', $customizer_css );
			}
		}

		wp_register_script(
			'creceweb-lumen-lite-color-mode-bootstrap',
			false,
			array(),
			CRECEWEB_LUMEN_LITE_ASSET_VERSION,
			false
		);
		wp_enqueue_script( 'creceweb-lumen-lite-color-mode-bootstrap' );
		wp_add_inline_script( 'creceweb-lumen-lite-color-mode-bootstrap', $this->bootstrap_script(), 'after' );

		if ( $this->should_render_floating_switch() || $this->should_render_menu_switch() ) {
			$this->enqueue_switch_script();
		}
	}

	/**
	 * Keeps the active dark palette authoritative inside the Theme Customizer preview.
	 *
	 * Lumen Theme live-preview controls write semantic tokens as normal inline
	 * declarations on the root element. Custom-property declarations marked
	 * important here win only while Dark mode is active; switching back to Light
	 * immediately reveals the Theme's current live-preview values again.
	 *
	 * @return string
	 */
	public function customizer_style_css(): string {
		if ( ! $this->is_enabled() ) {
			return '';
		}

		$frontend_css = $this->style_css();
		if ( 1 !== preg_match( '/html\[data-cw-color-mode="dark"\]\{color-scheme:dark;([^}]*)\}/', $frontend_css, $matches ) ) {
			return '';
		}

		$important = array();
		foreach ( explode( ';', $matches[1] ) as $declaration ) {
			$declaration = trim( $declaration );
			if ( '' !== $declaration ) {
				$important[] = $declaration . '!important';
			}
		}

		return empty( $important )
			? ''
			: 'html[data-cw-color-mode="dark"]{' . implode( ';', $important ) . '}';
	}

	/**
	 * Mirrors the configured default color mode inside Gutenberg's editor canvas.
	 * The Theme remains the source of the light/base palette; Lite only overlays
	 * its dark semantic tokens when Dark (or System-dark) is the default.
	 *
	 * @return void
	 */
	public function enqueue_editor_style(): void {
		if ( ! is_admin() || ! $this->is_enabled() ) {
			return;
		}

		$css = $this->editor_style_css();
		if ( '' === $css ) {
			return;
		}

		wp_add_inline_style( 'creceweb-lumen-lite-editor-content', $css );
	}

	/**
	 * Builds editor-only CSS from the exact dark declarations used on frontend.
	 *
	 * @return string
	 */
	public function editor_style_css(): string {
		if ( ! $this->is_enabled() ) {
			return '';
		}

		$config       = $this->config();
		$default_mode = in_array( (string) ( $config['default_mode'] ?? 'system' ), array( 'light', 'dark', 'system' ), true )
			? (string) $config['default_mode']
			: 'system';

		if ( 'light' === $default_mode ) {
			return '';
		}

		$frontend_css = $this->style_css();
		if ( 1 !== preg_match( '/html\[data-cw-color-mode="dark"\]\{color-scheme:dark;([^}]*)\}/', $frontend_css, $matches ) ) {
			return '';
		}

		$dark_rule = '.editor-styles-wrapper{color-scheme:dark;' . $matches[1]
			. ';background-color:var(--cw-color-background);color:var(--cw-color-text)}';

		return 'dark' === $default_mode
			? $dark_rule
			: '@media (prefers-color-scheme:dark){' . $dark_rule . '}';
	}

	/** @return void */
	private function enqueue_switch_script(): void {
		$this->switch_script_requested = true;
		wp_register_script(
			'creceweb-lumen-lite-color-mode-switch',
			CRECEWEB_LUMEN_LITE_URL . 'assets/js/color-mode.js',
			array(),
			CRECEWEB_LUMEN_LITE_ASSET_VERSION,
			true
		);
		wp_enqueue_script( 'creceweb-lumen-lite-color-mode-switch' );
	}

	/** @return string */
	public function bootstrap_script(): string {
		if ( ! $this->is_enabled() ) {
			return '';
		}
		$config = $this->config();
		$default_mode = in_array( (string) ( $config['default_mode'] ?? 'system' ), array( 'light', 'dark', 'system' ), true )
			? (string) $config['default_mode']
			: 'system';

		return "(function(){var k='" . self::STORAGE_KEY . "',d='" . $default_mode . "',m='';document.documentElement.setAttribute('data-cw-color-mode-default',d);try{m=localStorage.getItem(k)||'';}catch(e){}if(m!=='light'&&m!=='dark'){m=d==='dark'?'dark':(d==='light'?'light':((window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches)?'dark':'light'));}document.documentElement.setAttribute('data-cw-color-mode',m);}());";
	}

	/** @return string */
	public function style_css(): string {
		if ( ! $this->is_enabled() ) {
			return '';
		}
		$config  = $this->config();
		$palette = isset( $config['palette'] ) && is_array( $config['palette'] ) ? $config['palette'] : array();
		$defaults = array(
			'primary'    => '#334155',
			'accent'     => '#34d399',
			'background' => '#0f172a',
			'surface'    => '#111827',
			'text'       => '#e5e7eb',
			'heading'    => '#f8fafc',
			'link'       => '#6ee7b7',
			'border'     => '#334155',
		);
		foreach ( $defaults as $key => $fallback ) {
			$value = sanitize_hex_color( (string) ( $palette[ $key ] ?? $fallback ) );
			$palette[ $key ] = $value ? strtolower( $value ) : $fallback;
		}
		$button_text  = $this->best_contrast_text_color( $palette['accent'] );
		$primary_text = $this->best_contrast_text_color( $palette['primary'] );

		$vars = array(
			'--cw-color-primary:' . $palette['primary'],
			'--cw-color-accent:' . $palette['accent'],
			'--cw-color-accent-strong:color-mix(in srgb,var(--cw-color-accent) 82%,black)',
			'--cw-color-background:' . $palette['background'],
			'--cw-color-surface:' . $palette['surface'],
			'--cw-color-text:' . $palette['text'],
			'--cw-color-heading:' . $palette['heading'],
			'--cw-color-link:' . $palette['link'],
			'--cw-color-text-muted:color-mix(in srgb,var(--cw-color-text) 68%,var(--cw-color-background))',
			'--cw-color-muted:var(--cw-color-text-muted)',
			'--cw-color-border:' . $palette['border'],
			'--cw-color-focus:var(--cw-color-accent)',
			'--cw-navigation-color:var(--cw-color-text)',
			'--cw-navigation-hover-color:var(--cw-color-accent)',
			'--cw-navigation-active-color:var(--cw-color-accent)',
			'--cw-submenu-background:var(--cw-color-surface)',
			'--cw-button-background:var(--cw-color-accent)',
			'--cw-button-hover:var(--cw-color-accent-strong)',
			'--cw-button-text:' . $button_text,
			'--cw-footer-background:var(--cw-color-surface)',
			'--cw-footer-text:var(--cw-color-text)',
			'--cw-footer-link:var(--cw-color-link)',
			'--cw-copyright-background:color-mix(in srgb,var(--cw-color-background) 84%,black)',
			'--cw-copyright-text:var(--cw-color-text-muted)',
			'--cw-form-background:var(--cw-color-surface)',
			'--cw-form-border:var(--cw-color-border)',
			'--cw-form-focus:var(--cw-color-accent)',
			'--cw-topbar-background:var(--cw-color-surface)',
			'--cw-topbar-text:var(--cw-color-text)',
			'--cw-topbar-link:var(--cw-color-link)',
			'--cw-content-heading-color:var(--cw-color-heading)',
			'--cw-content-button-background:var(--cw-color-accent)',
			'--cw-content-button-hover:var(--cw-color-accent-strong)',
			'--cw-content-button-text:' . $button_text,
			'--cw-content-box-color:var(--cw-color-border)',
			'--cw-content-bullet-color:var(--cw-color-primary)',
			'--cw-blog-read-more-background:var(--cw-color-accent)',
			'--cw-blog-read-more-hover-background:var(--cw-color-accent-strong)',
			'--cw-blog-read-more-text:' . $button_text,
			'--cw-blog-read-more-hover-text:' . $button_text,
			'--cw-blog-read-more-border:var(--cw-color-accent)',
			'--cw-submenu-hover-text:var(--cw-color-accent)',
			'--cw-submenu-hover-background:var(--cw-color-accent)',
			'--cw-submenu-hover-background-opacity:14%',
			'--cw-social-header-color:var(--cw-color-primary)',
			'--cw-social-header-hover-color:var(--cw-color-accent)',
			'--cw-social-footer-color:var(--cw-color-primary)',
			'--cw-social-footer-hover-color:var(--cw-color-accent)',
			'--wp--preset--color--primary:' . $palette['primary'],
			'--wp--preset--color--accent:' . $palette['accent'],
			'--wp--preset--color--accent-strong:var(--cw-color-accent-strong)',
			'--wp--preset--color--background:' . $palette['background'],
			'--wp--preset--color--surface:' . $palette['surface'],
			'--wp--preset--color--lumen-text:' . $palette['text'],
			'--wp--preset--color--text:' . $palette['text'],
			'--wp--preset--color--text-muted:var(--cw-color-text-muted)',
			'--wp--preset--color--lumen-border:' . $palette['border'],
			'--wp--preset--color--border:' . $palette['border'],
			'--wp--preset--color--inverse:' . $primary_text,
		);

		$elementor = array(
			'--e-global-color-cwlumenprimary:' . $palette['primary'],
			'--e-global-color-cwlumenaccent:' . $palette['accent'],
			'--e-global-color-cwlumenaccentstrong:var(--cw-color-accent-strong)',
			'--e-global-color-cwlumenbackground:' . $palette['background'],
			'--e-global-color-cwlumensurface:' . $palette['surface'],
			'--e-global-color-cwlumentext:' . $palette['text'],
			'--e-global-color-cwlumenheadingbase:' . $palette['heading'],
			'--e-global-color-cwlumenlink:' . $palette['link'],
			'--e-global-color-cwlumenmuted:var(--cw-color-text-muted)',
			'--e-global-color-cwlumenborder:' . $palette['border'],
			'--e-global-color-cwlumenheading:' . $palette['heading'],
			'--e-global-color-cwlumenbutton:' . $palette['accent'],
			'--e-global-color-cwlumenbuttonhover:var(--cw-color-accent-strong)',
			'--e-global-color-cwlumenbuttontext:' . $button_text,
			'--e-global-color-cwlumenbullet:' . $palette['primary'],
			'--e-global-color-cwlumenbox:' . $palette['border'],
		);

		return 'html[data-cw-color-mode="dark"]{color-scheme:dark;' . implode( ';', $vars ) . '}'
			. 'html[data-cw-color-mode="light"]{color-scheme:light}'
			. 'html[data-cw-color-mode="dark"] body{' . implode( ';', $elementor ) . '}'
			. 'html[data-cw-color-mode="dark"] .cw-header-tone--primary .cw-site-header :is(a,.wp-block-site-title,.wp-block-site-tagline),html[data-cw-color-mode="dark"] .cw-footer-tone--primary :is(a,.wp-block-site-title,.wp-block-site-tagline,.widget-title,.cw-widget__title,.wp-block-heading){color:' . $primary_text . '}'
			. 'html[data-cw-color-mode="dark"] .cw-site-header .cw-classic-navigation__menu li.cw-lumen-lite-menu-item--button>a,html[data-cw-color-mode="dark"] .cw-site-header .cw-classic-navigation__menu li.cw-lumen-lite-menu-item--button>a:hover,html[data-cw-color-mode="dark"] .cw-site-header .cw-classic-navigation__menu li.cw-lumen-lite-menu-item--button>a:focus-visible{color:var(--cw-button-text)!important}'
			. '.cw-color-mode-switch{display:inline-grid;place-items:center;width:46px;height:46px;padding:0;border:1px solid var(--cw-color-border);border-radius:999px;background:var(--cw-color-surface);color:var(--cw-color-text);box-shadow:0 8px 22px rgb(15 23 42 / 18%);cursor:pointer;line-height:1;z-index:101}'
			. '.cw-color-mode-switch:hover,.cw-color-mode-switch:focus-visible{border-color:var(--cw-color-accent);color:var(--cw-color-accent)}'
			. '.cw-color-mode-switch svg{display:block;width:22px;height:22px}'
			. '.cw-color-mode-switch .cw-color-mode-switch__sun{display:none}.cw-color-mode-switch .cw-color-mode-switch__moon{display:block}html[data-cw-color-mode="dark"] .cw-color-mode-switch .cw-color-mode-switch__sun{display:block}html[data-cw-color-mode="dark"] .cw-color-mode-switch .cw-color-mode-switch__moon{display:none}'
			. '.cw-color-mode-switch--floating{position:fixed;bottom:max(16px,env(safe-area-inset-bottom))}'
			. '.cw-color-mode-switch--left{left:max(16px,env(safe-area-inset-left));right:auto}.cw-color-mode-switch--right{right:max(16px,env(safe-area-inset-right));left:auto}'
			. '.cw-color-mode-menu-item{display:flex;align-items:center;justify-content:center}.cw-color-mode-switch--menu{width:38px;height:38px;border-color:color-mix(in srgb,var(--cw-navigation-color) 24%,transparent);background:transparent;color:var(--cw-navigation-color);box-shadow:none}.cw-color-mode-switch--menu:hover,.cw-color-mode-switch--menu:focus-visible{border-color:var(--cw-navigation-hover-color);color:var(--cw-navigation-hover-color);background:color-mix(in srgb,var(--cw-color-surface) 72%,transparent)}.cw-color-mode-switch--menu svg{width:20px;height:20px}@media(max-width:1024px){.cw-color-mode-menu-item{justify-content:flex-start;min-height:3.125rem;padding:.45rem 0}.cw-color-mode-switch--menu{width:44px;height:44px}}'
			. '.cw-color-mode-shortcode{display:inline-flex;align-items:center;justify-content:center;width:auto;max-width:100%;line-height:0;vertical-align:middle}.cw-color-mode-shortcode .cw-color-mode-switch--shortcode{box-sizing:border-box!important;display:inline-grid!important;width:46px!important;min-width:46px!important;max-width:46px!important;inline-size:46px!important;min-inline-size:46px!important;max-inline-size:46px!important;height:46px!important;min-height:46px!important;max-height:46px!important;block-size:46px!important;min-block-size:46px!important;max-block-size:46px!important;margin:0!important;padding:0!important;flex:0 0 46px!important;border-radius:999px!important;line-height:1!important;appearance:none!important}'
			. '@media(min-width:1025px){.cw-color-mode-switch--hide-desktop{display:none!important}}'
			. '@media(min-width:782px) and (max-width:1024px){.cw-color-mode-switch--hide-tablet{display:none!important}}'
			. '@media(max-width:781px){.cw-color-mode-switch--hide-mobile{display:none!important}}'
			. '@media(prefers-reduced-motion:reduce){.cw-color-mode-switch{transition:none}}';
	}

	/**
	 * Chooses a stable high-contrast button text color from the accent only.
	 * Background changes must never alter button text.
	 *
	 * @param string $accent Accent hex color.
	 * @return string
	 */
	private function best_contrast_text_color( string $accent ): string {
		$dark  = '#0f172a';
		$light = '#ffffff';
		$accent_luminance = $this->relative_luminance( $accent );
		$dark_ratio  = ( max( $accent_luminance, $this->relative_luminance( $dark ) ) + 0.05 ) / ( min( $accent_luminance, $this->relative_luminance( $dark ) ) + 0.05 );
		$light_ratio = ( max( $accent_luminance, $this->relative_luminance( $light ) ) + 0.05 ) / ( min( $accent_luminance, $this->relative_luminance( $light ) ) + 0.05 );
		return $dark_ratio >= $light_ratio ? $dark : $light;
	}

	/**
	 * @param string $hex Six-digit hex color.
	 * @return float
	 */
	private function relative_luminance( string $hex ): float {
		$hex = ltrim( strtolower( $hex ), '#' );
		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			return 0.0;
		}
		$channels = array( hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) );
		$channels = array_map(
			static function ( int $channel ): float {
				$value = $channel / 255;
				return $value <= 0.04045 ? $value / 12.92 : ( ( $value + 0.055 ) / 1.055 ) ** 2.4;
			},
			$channels
		);
		return ( 0.2126 * $channels[0] ) + ( 0.7152 * $channels[1] ) + ( 0.0722 * $channels[2] );
	}

	/** @return bool */
	public function switch_script_requested(): bool {
		return $this->switch_script_requested;
	}

	/** @return int */
	public function style_size_bytes(): int {
		return strlen( $this->style_css() );
	}

	/** @return int */
	public function script_size_bytes(): int {
		$path = CRECEWEB_LUMEN_LITE_DIR . 'assets/js/color-mode.js';
		$size = is_readable( $path ) ? filesize( $path ) : false;
		return false === $size ? 0 : max( 0, (int) $size );
	}

	/** @return int */
	public function bootstrap_size_bytes(): int {
		return strlen( $this->bootstrap_script() );
	}

	/** @return string */
	public function shortcode(): string {
		if ( ! $this->is_enabled() ) {
			return '';
		}
		$this->enqueue_switch_script();
		return '<span class="cw-color-mode-shortcode">' . $this->switch_markup( 'shortcode' ) . '</span>';
	}

	/** @return void */
	public function render_floating_switch(): void {
		if ( $this->floating_rendered || ! $this->should_render_floating_switch() ) {
			return;
		}
		$this->floating_rendered = true;
		echo $this->switch_markup( 'floating' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is assembled from validated settings and escaped strings.
	}

	/**
	 * @param string $placement Switch placement: floating, menu, or shortcode.
	 * @return string
	 */
	private function switch_markup( string $placement ): string {
		$config       = $this->config();
		$default_mode = (string) ( $config['default_mode'] ?? 'system' );
		$classes      = array( 'cw-color-mode-switch' );
		if ( 'floating' === $placement ) {
			$position = in_array( (string) ( $config['position'] ?? 'left' ), array( 'left', 'right' ), true ) ? (string) $config['position'] : 'left';
			$classes[] = 'cw-color-mode-switch--floating';
			$classes[] = 'cw-color-mode-switch--' . $position;
			$devices = isset( $config['devices'] ) && is_array( $config['devices'] ) ? $config['devices'] : array();
			foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
				if ( empty( $devices[ $device ] ) ) {
					$classes[] = 'cw-color-mode-switch--hide-' . $device;
				}
			}
		} elseif ( 'menu' === $placement ) {
			$classes[] = 'cw-color-mode-switch--menu';
		} elseif ( 'shortcode' === $placement ) {
			$classes[] = 'cw-color-mode-switch--shortcode';
		}

		$moon = '<svg class="cw-color-mode-switch__moon" aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M19.2 15.8A7.5 7.5 0 0 1 8.2 4.8 8 8 0 1 0 19.2 15.8Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>';
		$sun  = '<svg class="cw-color-mode-switch__sun" aria-hidden="true" viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="3.5" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M12 2.5v2M12 19.5v2M2.5 12h2M19.5 12h2M5.3 5.3l1.4 1.4M17.3 17.3l1.4 1.4M18.7 5.3l-1.4 1.4M6.7 17.3l-1.4 1.4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>';

		return '<button type="button" class="' . esc_attr( implode( ' ', array_map( 'sanitize_html_class', $classes ) ) ) . '" data-cw-color-mode-switch data-cw-default-mode="' . esc_attr( $default_mode ) . '" data-cw-label-dark="' . esc_attr__( 'Switch to dark mode', 'creceweb-lumen-lite' ) . '" data-cw-label-light="' . esc_attr__( 'Switch to light mode', 'creceweb-lumen-lite' ) . '" aria-pressed="false" aria-label="' . esc_attr__( 'Toggle color mode', 'creceweb-lumen-lite' ) . '">' . $moon . $sun . '<span class="screen-reader-text">' . esc_html__( 'Toggle color mode', 'creceweb-lumen-lite' ) . '</span></button>';
	}


	/**
	 * Appends the color-mode switch to Lumen's primary navigation when enabled.
	 *
	 * @param string $items Existing menu item markup.
	 * @param mixed  $args  WordPress menu arguments.
	 * @return string
	 */
	public function filter_primary_menu_items( string $items, mixed $args ): string {
		$location = is_object( $args ) && isset( $args->theme_location ) ? (string) $args->theme_location : '';
		if ( 'primary' !== $location || ! $this->should_render_menu_switch() ) {
			return $items;
		}

		$this->enqueue_switch_script();
		return $items . '<li class="menu-item cw-color-mode-menu-item">' . $this->switch_markup( 'menu' ) . '</li>';
	}

	/** @return void */
	public function handle_save(): void {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'Your user account does not have permission to save these changes.', 'creceweb-lumen-lite' ) );
		}
		check_admin_referer( self::ACTION_SAVE );

		$return_url = isset( $_POST[ AdminRedirect::FIELD ] )
			? esc_url_raw( wp_unslash( (string) $_POST[ AdminRedirect::FIELD ] ) )
			: ( isset( $_POST['_wp_http_referer'] ) ? esc_url_raw( wp_unslash( (string) $_POST['_wp_http_referer'] ) ) : '' );
		$posted = isset( $_POST['color_mode'] ) && is_array( $_POST['color_mode'] )
			? wp_unslash( $_POST['color_mode'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Settings normalizes every nested value before persistence.
			: array();
		$devices = isset( $posted['devices'] ) && is_array( $posted['devices'] ) ? $posted['devices'] : array();
		$palette = isset( $posted['palette'] ) && is_array( $posted['palette'] ) ? $posted['palette'] : array();

		Settings::update_color_mode(
			array(
				'enabled'      => ! empty( $posted['enabled'] ),
				'default_mode' => (string) ( $posted['default_mode'] ?? 'system' ),
				'show_switch'      => ! empty( $posted['show_switch'] ),
				'show_menu_switch' => ! empty( $posted['show_menu_switch'] ),
				'position'     => (string) ( $posted['position'] ?? 'left' ),
				'devices'      => array(
					'desktop' => ! empty( $devices['desktop'] ),
					'tablet'  => ! empty( $devices['tablet'] ),
					'mobile'  => ! empty( $devices['mobile'] ),
				),
				'palette'      => $palette,
			)
		);

		$url = AdminRedirect::target( $return_url, admin_url( 'themes.php' ), 'color_mode_saved' );
		wp_safe_redirect( esc_url_raw( $url ) );
		exit;
	}
}
