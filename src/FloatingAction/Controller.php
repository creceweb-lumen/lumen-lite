<?php
/**
 * Global advanced floating action for Lumen Lite.
 *
 * Lumen Lite owns the complete presentation and behavior of this optional
 * global action. Theme Back to top remains a separate Theme-owned control.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\FloatingAction;

use CreceWeb\LumenLite\Data\Settings;
use CreceWeb\LumenLite\Support\AdminRedirect;
use CreceWeb\LumenLite\Support\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Controller {
	public const ACTION_SAVE = 'cw_lumen_lite_save_floating_action';
	private bool $rendered = false;
	/** @var array<string,mixed>|null */
	private ?array $config_cache = null;

	public function __construct( private Compatibility $compatibility ) {}

	/** @return void */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION_SAVE, array( $this, 'handle_save' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 36 );
		add_action( 'wp_body_open', array( $this, 'render' ), 21 );
		add_action( 'wp_footer', array( $this, 'render' ), 5 );
	}

	/** @return array<string,mixed> */
	public function settings(): array {
		$settings = Settings::get();
		return isset( $settings['floating_action'] ) && is_array( $settings['floating_action'] )
			? $settings['floating_action']
			: array();
	}

	/** @return array<string,mixed> */
	public function config(): array {
		if ( null !== $this->config_cache ) {
			return $this->config_cache;
		}

		$config = $this->settings();
		$filtered = apply_filters( 'creceweb_lumen_lite_floating_action_config', $config );
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
	public function should_render(): bool {
		if ( ! $this->is_enabled() ) {
			return false;
		}

		$config      = $this->config();
		$destination = $this->destination( $config );
		if ( '' === $destination['href'] ) {
			return false;
		}

		return (bool) apply_filters( 'creceweb_lumen_lite_floating_action_should_render', true, $config, $destination );
	}

	/** @return void */
	public function enqueue_assets(): void {
		if ( ! $this->should_render() ) {
			return;
		}

		wp_register_style(
			'creceweb-lumen-lite-floating-action',
			false,
			array( 'creceweb-lumen' ),
			CRECEWEB_LUMEN_LITE_ASSET_VERSION
		);
		wp_enqueue_style( 'creceweb-lumen-lite-floating-action' );
		wp_add_inline_style( 'creceweb-lumen-lite-floating-action', $this->inline_css() );

		wp_enqueue_script(
			'creceweb-lumen-lite-floating-action',
			CRECEWEB_LUMEN_LITE_URL . 'assets/js/floating-action.js',
			array(),
			CRECEWEB_LUMEN_LITE_ASSET_VERSION,
			true
		);
	}

	/** @return int */
	public function style_size_bytes(): int {
		return strlen( $this->inline_css() );
	}

	/** @return int */
	public function script_size_bytes(): int {
		$path = CRECEWEB_LUMEN_LITE_DIR . 'assets/js/floating-action.js';
		$size = is_readable( $path ) ? filesize( $path ) : false;
		return false === $size ? 0 : max( 0, (int) $size );
	}

	/** @return string */
	private function inline_css(): string {
		return <<<'CSS'
.cw-floating-action.cw-lumen-lite-advanced-action {
	bottom: max(var(--cw-lumen-lite-action-bottom, 16px), env(safe-area-inset-bottom));
	min-width: var(--cw-lumen-lite-action-size, 46px);
	min-height: var(--cw-lumen-lite-action-size, 46px);
	height: var(--cw-lumen-lite-action-size, 46px) !important;
	background-color: var(--cw-lumen-lite-action-background, #0f172a) !important;
	opacity: 0;
	visibility: hidden;
	pointer-events: none;
	transform: translateY(8px);
	transition: opacity .18s ease, transform .18s ease, visibility 0s linear .18s, background-color .18s ease;
	z-index: 100;
}
.cw-floating-action.cw-lumen-lite-action--visible {
	opacity: var(--cw-lumen-lite-action-opacity, 1);
	visibility: visible;
	pointer-events: auto;
	transform: translateY(0);
	transition-delay: 0s;
}
.cw-floating-action.cw-lumen-lite-action--left {
	inset-inline-start: max(var(--cw-lumen-lite-action-side, 16px), env(safe-area-inset-left));
	inset-inline-end: auto;
}
.cw-floating-action.cw-lumen-lite-action--right {
	inset-inline-start: auto;
	inset-inline-end: max(var(--cw-lumen-lite-action-side, 16px), env(safe-area-inset-right));
}
.cw-floating-action.cw-lumen-lite-action--with-label {
	display: inline-flex;
	width: auto !important;
	max-width: min(32rem, calc(100vw - 2rem));
	gap: .55em;
	min-width: 0;
	padding-inline: calc(var(--cw-lumen-lite-action-size, 46px) * .34);
	white-space: nowrap;
}
.cw-floating-action.cw-lumen-lite-action--content-icon {
	width: var(--cw-lumen-lite-action-size, 46px) !important;
}
.cw-floating-action.cw-lumen-lite-action--shape-pill { border-radius: 999px; }
.cw-floating-action.cw-lumen-lite-action--shape-rounded { border-radius: .75rem; }
.cw-floating-action.cw-lumen-lite-action--shape-square { border-radius: 0; }
.cw-floating-action.cw-lumen-lite-advanced-action svg {
	flex: 0 0 auto;
	width: calc(var(--cw-lumen-lite-action-size, 46px) * .46) !important;
	height: calc(var(--cw-lumen-lite-action-size, 46px) * .46) !important;
	color: var(--cw-lumen-lite-action-icon, #fff);
	max-width: none;
	max-height: none;
}
.cw-lumen-lite-action__label {
	min-width: 0;
	overflow: hidden;
	font-size: max(.8125rem, calc(var(--cw-lumen-lite-action-size, 46px) * .3));
	font-weight: 700;
	line-height: 1.15;
	text-overflow: ellipsis;
	color: var(--cw-lumen-lite-action-text, #fff);
}
.cw-floating-action.cw-lumen-lite-advanced-action:hover,
.cw-floating-action.cw-lumen-lite-advanced-action:focus-visible {
	background-color: var(--cw-lumen-lite-action-background-hover, #1e293b) !important;
}
.cw-floating-action.cw-lumen-lite-advanced-action:hover svg,
.cw-floating-action.cw-lumen-lite-advanced-action:focus-visible svg {
	color: var(--cw-lumen-lite-action-icon-hover, #fff);
}
.cw-floating-action.cw-lumen-lite-advanced-action:hover .cw-lumen-lite-action__label,
.cw-floating-action.cw-lumen-lite-advanced-action:focus-visible .cw-lumen-lite-action__label {
	color: var(--cw-lumen-lite-action-text-hover, #fff);
}
@media (min-width: 1025px) {
	.cw-lumen-lite-action--hide-desktop { display: none !important; }
}
@media (min-width: 782px) and (max-width: 1024px) {
	.cw-lumen-lite-action--hide-tablet { display: none !important; }
}
@media (max-width: 781px) {
	.cw-lumen-lite-action--hide-mobile { display: none !important; }
	.cw-floating-action.cw-lumen-lite-action--with-label { max-width: calc(100vw - 2rem); }
}
@media (prefers-reduced-motion: reduce) {
	.cw-floating-action.cw-lumen-lite-advanced-action { transition: none; }
}
CSS;
	}
	/** @return void */
	public function render(): void {
		if ( $this->rendered || ! $this->should_render() ) {
			return;
		}

		$config      = $this->config();
		$destination = $this->destination( $config );
		$label       = trim( sanitize_text_field( (string) ( $config['label'] ?? '' ) ) );
		$content     = in_array( (string) ( $config['content'] ?? 'both' ), array( 'icon', 'text', 'both' ), true ) ? (string) $config['content'] : 'both';
		$shape       = in_array( (string) ( $config['shape'] ?? 'pill' ), array( 'pill', 'rounded', 'square' ), true ) ? (string) $config['shape'] : 'pill';
		$icon        = sanitize_key( (string) ( $config['icon'] ?? 'arrow-right' ) );
		$position    = in_array( (string) ( $config['position'] ?? 'right' ), array( 'left', 'right' ), true ) ? (string) $config['position'] : 'right';
		$devices     = isset( $config['devices'] ) && is_array( $config['devices'] ) ? $config['devices'] : array();
		$opacity     = max( 0, min( 100, absint( $config['opacity'] ?? 100 ) ) );
		$bottom      = max( 0, absint( $config['bottom_offset'] ?? 16 ) );
		$side        = max( 0, absint( $config['side_offset'] ?? 16 ) );
		$size        = max( 1, absint( $config['size'] ?? 46 ) );
		$background  = sanitize_hex_color( (string) ( $config['background_color'] ?? '#0f172a' ) ) ?: '#0f172a';
		$background_hover = sanitize_hex_color( (string) ( $config['background_hover'] ?? '#1e293b' ) ) ?: '#1e293b';
		$icon_color  = sanitize_hex_color( (string) ( $config['icon_color'] ?? '#ffffff' ) ) ?: '#ffffff';
		$icon_hover  = sanitize_hex_color( (string) ( $config['icon_hover'] ?? '#ffffff' ) ) ?: '#ffffff';
		$text_color  = sanitize_hex_color( (string) ( $config['text_color'] ?? '#ffffff' ) ) ?: '#ffffff';
		$text_hover  = sanitize_hex_color( (string) ( $config['text_hover'] ?? '#ffffff' ) ) ?: '#ffffff';
		$scroll      = max( 0, absint( $config['scroll_offset'] ?? 300 ) );
		$delay       = max( 0, absint( $config['auto_hide_delay'] ?? 4 ) );
		$smooth      = ! empty( $config['smooth_scroll'] );
		$auto_hide   = ! empty( $config['auto_hide'] );

		if ( 'text' === $content && '' === $label ) {
			$content = 'icon';
		} elseif ( 'both' === $content && '' === $label ) {
			$content = 'icon';
		}

		$classes = array(
			'cw-floating-action',
			'cw-lumen-lite-advanced-action',
			'cw-lumen-lite-action--' . $position,
			'cw-lumen-lite-action--shape-' . $shape,
			'cw-lumen-lite-action--content-' . $content,
		);
		if ( 'icon' !== $content ) {
			$classes[] = 'cw-lumen-lite-action--with-label';
		}
		foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
			if ( empty( $devices[ $device ] ) ) {
				$classes[] = 'cw-lumen-lite-action--hide-' . $device;
			}
		}

		$style = sprintf(
			'--cw-lumen-lite-action-opacity:%1$s;--cw-lumen-lite-action-bottom:%2$dpx;--cw-lumen-lite-action-side:%3$dpx;--cw-lumen-lite-action-size:%4$dpx;--cw-lumen-lite-action-background:%5$s;--cw-lumen-lite-action-background-hover:%6$s;--cw-lumen-lite-action-icon:%7$s;--cw-lumen-lite-action-icon-hover:%8$s;--cw-lumen-lite-action-text:%9$s;--cw-lumen-lite-action-text-hover:%10$s',
			(string) ( $opacity / 100 ),
			$bottom,
			$side,
			$size,
			$background,
			$background_hover,
			$icon_color,
			$icon_hover,
			$text_color,
			$text_hover
		);

		$accessible_label = $label;
		if ( '' === $accessible_label ) {
			$accessible_label = 'element' === $destination['action']
				? __( 'Go to page section', 'creceweb-lumen-lite' )
				: __( 'Open link', 'creceweb-lumen-lite' );
		}

		$scroll_target = (string) ( $destination['scroll_target'] ?? '' );
		$attributes = sprintf(
			'data-cw-lumen-advanced-action="1" data-cw-scroll-offset="%1$d" data-cw-auto-hide="%2$d" data-cw-auto-hide-delay="%3$d" data-cw-smooth="%4$d"',
			$scroll,
			$auto_hide ? 1 : 0,
			$delay,
			$smooth ? 1 : 0
		);
		if ( '' !== $scroll_target ) {
			$attributes .= ' data-cw-scroll-target="' . esc_attr( $scroll_target ) . '"';
		}

		$icon_html = 'text' !== $content ? $this->icon_svg( $icon ) : '';
		$text_html = 'icon' !== $content && '' !== $label
			? '<span class="cw-lumen-lite-action__label">' . esc_html( $label ) . '</span>'
			: '';
		$sr_html = 'icon' === $content
			? '<span class="screen-reader-text">' . esc_html( $accessible_label ) . '</span>'
			: '';

		$this->rendered = true;
		printf(
			'<a class="%1$s" href="%2$s" aria-label="%3$s" style="%4$s" %5$s>%6$s%7$s%8$s</a>',
			esc_attr( implode( ' ', array_map( 'sanitize_html_class', $classes ) ) ),
			esc_url( (string) $destination['href'] ),
			esc_attr( $accessible_label ),
			esc_attr( $style ),
			$attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built exclusively from escaped numeric/data attributes above.
			$icon_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static inline SVG owned by the plugin.
			$text_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
			$sr_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
		);
	}

	/**
	 * @param array<string,mixed> $config Configuration.
	 * @return array{action:string,href:string,scroll_target:string}
	 */
	private function destination( array $config ): array {
		$action = sanitize_key( (string) ( $config['action'] ?? 'element' ) );
		if ( ! in_array( $action, array( 'element', 'url' ), true ) ) {
			$action = 'element';
		}

		if ( 'element' === $action ) {
			$id = $this->sanitize_element_id( (string) ( $config['element_id'] ?? '' ) );
			if ( '' !== $id ) {
				return array( 'action' => 'element', 'href' => '#' . $id, 'scroll_target' => $id );
			}
		}

		if ( 'url' === $action ) {
			$url = esc_url_raw( (string) ( $config['url'] ?? '' ) );
			if ( '' !== $url ) {
				return array( 'action' => 'url', 'href' => $url, 'scroll_target' => '' );
			}
		}

		return array( 'action' => $action, 'href' => '', 'scroll_target' => '' );
	}

	/** @return string */
	private function sanitize_element_id( string $id ): string {
		$id = ltrim( trim( $id ), '#' );
		$id = preg_replace( '/[^A-Za-z0-9_:\-.]/', '', $id );
		return is_string( $id ) ? $id : '';
	}

	/** @return string */
	public function icon_svg( string $icon ): string {
		$icons = array(
			'arrow-right'   => '<path d="M5 11.25h11.13l-4.44-4.44 1.06-1.06L19 12l-6.25 6.25-1.06-1.06 4.44-4.44H5v-1.5Z" fill="currentColor"/>',
			'chevron-right' => '<path d="m9.25 5.5 6.5 6.5-6.5 6.5-1.06-1.06L13.63 12 8.19 6.56 9.25 5.5Z" fill="currentColor"/>',
			'arrow-down'    => '<path d="M11.25 5h1.5v11.13l4.44-4.44 1.06 1.06L12 19l-6.25-6.25 1.06-1.06 4.44 4.44V5Z" fill="currentColor"/>',
			'plus'          => '<path d="M11.25 5h1.5v6.25H19v1.5h-6.25V19h-1.5v-6.25H5v-1.5h6.25V5Z" fill="currentColor"/>',
			'info'          => '<path d="M12 3.75A8.25 8.25 0 1 0 12 20.25 8.25 8.25 0 0 0 12 3.75Zm0 1.5A6.75 6.75 0 1 1 12 18.75 6.75 6.75 0 0 1 12 5.25Zm-.75 3h1.5v1.5h-1.5v-1.5Zm0 3h1.5v5.25h-1.5v-5.25Z" fill="currentColor"/>',
			'mail'          => '<path d="M4.5 6h15A1.5 1.5 0 0 1 21 7.5v9A1.5 1.5 0 0 1 19.5 18h-15A1.5 1.5 0 0 1 3 16.5v-9A1.5 1.5 0 0 1 4.5 6Zm0 1.5v.36L12 12.7l7.5-4.84V7.5h-15Zm15 2.14-7.09 4.58a.75.75 0 0 1-.82 0L4.5 9.64v6.86h15V9.64Z" fill="currentColor"/>',
			'phone'         => '<path d="M7.1 3.75h2.22l1.16 4.06-1.72 1.72a13.2 13.2 0 0 0 5.71 5.71l1.72-1.72 4.06 1.16v2.22a3.35 3.35 0 0 1-3.7 3.33A14.36 14.36 0 0 1 3.77 7.45a3.35 3.35 0 0 1 3.33-3.7Zm1.09 1.5H7.1c-1 0-1.86.87-1.84 1.87a12.86 12.86 0 0 0 11.62 11.62 1.85 1.85 0 0 0 1.87-1.84v-1.09l-2.1-.6-1.83 1.83-.48-.2a14.76 14.76 0 0 1-7.18-7.18l-.2-.48 1.83-1.83-.6-2.1Z" fill="currentColor"/>',
			'calendar'      => '<path d="M7.5 3h1.5v2h6V3h1.5v2H18A2 2 0 0 1 20 7v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h1.5V3ZM6 6.5a.5.5 0 0 0-.5.5v2h13V7a.5.5 0 0 0-.5-.5h-1.5V8H15V6.5H9V8H7.5V6.5H6Zm12.5 4h-13V18a.5.5 0 0 0 .5.5h12a.5.5 0 0 0 .5-.5v-7.5Z" fill="currentColor"/>',
			'map-pin'       => '<path d="M12 2.75a6.25 6.25 0 0 1 6.25 6.25c0 4.2-4.72 9.68-5.67 10.74a.78.78 0 0 1-1.16 0C10.47 18.68 5.75 13.2 5.75 9A6.25 6.25 0 0 1 12 2.75Zm0 1.5A4.75 4.75 0 0 0 7.25 9c0 3 3.19 7.21 4.75 9.08 1.56-1.87 4.75-6.08 4.75-9.08A4.75 4.75 0 0 0 12 4.25Zm0 2.5A2.25 2.25 0 1 1 12 11.25 2.25 2.25 0 0 1 12 6.75Zm0 1.5a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5Z" fill="currentColor"/>',
			'chat'          => '<path d="M5.25 4.5h13.5A2.25 2.25 0 0 1 21 6.75v8.5a2.25 2.25 0 0 1-2.25 2.25h-7.42L7 20.37V17.5H5.25A2.25 2.25 0 0 1 3 15.25v-8.5A2.25 2.25 0 0 1 5.25 4.5Zm0 1.5a.75.75 0 0 0-.75.75v8.5c0 .41.34.75.75.75H8.5v1.57L10.88 16h7.87a.75.75 0 0 0 .75-.75v-8.5a.75.75 0 0 0-.75-.75H5.25Z" fill="currentColor"/>',
		);
		$path = $icons[ $icon ] ?? $icons['arrow-right'];
		return '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">' . $path . '</svg>';
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
		$posted = isset( $_POST['floating_action'] ) && is_array( $_POST['floating_action'] )
			? wp_unslash( $_POST['floating_action'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Settings::update_floating_action() normalizes and sanitizes every nested value before persistence.
			: array();
		$devices = isset( $posted['devices'] ) && is_array( $posted['devices'] ) ? $posted['devices'] : array();

		Settings::update_floating_action(
			array(
				'enabled'         => ! empty( $posted['enabled'] ),
				'action'          => (string) ( $posted['action'] ?? 'element' ),
				'content'         => (string) ( $posted['content'] ?? 'both' ),
				'shape'           => (string) ( $posted['shape'] ?? 'pill' ),
				'icon'            => (string) ( $posted['icon'] ?? 'arrow-right' ),
				'label'           => (string) ( $posted['label'] ?? '' ),
				'element_id'      => (string) ( $posted['element_id'] ?? '' ),
				'url'              => (string) ( $posted['url'] ?? '' ),
				'size'             => absint( $posted['size'] ?? 46 ),
				'background_color' => (string) ( $posted['background_color'] ?? '#0f172a' ),
				'background_hover' => (string) ( $posted['background_hover'] ?? '#1e293b' ),
				'icon_color'       => (string) ( $posted['icon_color'] ?? '#ffffff' ),
				'icon_hover'       => (string) ( $posted['icon_hover'] ?? '#ffffff' ),
				'text_color'       => (string) ( $posted['text_color'] ?? '#ffffff' ),
				'text_hover'       => (string) ( $posted['text_hover'] ?? '#ffffff' ),
				'position'         => (string) ( $posted['position'] ?? 'right' ),
				'scroll_offset'   => absint( $posted['scroll_offset'] ?? 300 ),
				'bottom_offset'   => absint( $posted['bottom_offset'] ?? 16 ),
				'side_offset'     => absint( $posted['side_offset'] ?? 16 ),
				'opacity'         => absint( $posted['opacity'] ?? 100 ),
				'auto_hide'       => ! empty( $posted['auto_hide'] ),
				'auto_hide_delay' => absint( $posted['auto_hide_delay'] ?? 4 ),
				'smooth_scroll'   => ! empty( $posted['smooth_scroll'] ),
				'devices'         => array(
					'desktop' => ! empty( $devices['desktop'] ),
					'tablet'  => ! empty( $devices['tablet'] ),
					'mobile'  => ! empty( $devices['mobile'] ),
				),
			)
		);

		$url = AdminRedirect::target( $return_url, admin_url( 'themes.php' ), 'floating_action_saved' );
		wp_safe_redirect( esc_url_raw( $url ) );
		exit;
	}
}
