<?php
/**
 * Lightweight social sharing for Lumen Lite.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Sharing;

use CreceWeb\LumenLite\Data\Settings;
use CreceWeb\LumenLite\Support\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds local share actions to selected public singular content.
 */
final class Controller {
	public function __construct( private Compatibility $compatibility ) {}

	/** @return void */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 39 );
		add_filter( 'the_content', array( $this, 'filter_content' ), 1001 );
	}

	/** @return array<string,mixed> */
	public function settings(): array {
		$settings = Settings::get();
		$sharing  = isset( $settings['sharing'] ) && is_array( $settings['sharing'] ) ? $settings['sharing'] : array();

		return wp_parse_args(
			$sharing,
			array(
				'enabled'                  => false,
				'position'                 => 'after',
				'show_labels'              => true,
				'minimal_style'             => false,
				'post_types'               => array(),
				'button_size'              => 40,
				'icon_size'                => 20,
				'border_width'             => 1,
				'border_radius'            => 8,
				'text_color'               => '',
				'text_hover_color'         => '',
				'background_color'         => '',
				'background_hover_color'   => '',
				'border_color'             => '',
				'border_hover_color'       => '',
				'actions'                  => array(
					'copy'     => true,
					'whatsapp' => true,
					'linkedin' => true,
					'facebook' => true,
					'email'    => true,
				),
			)
		);
	}

	/**
	 * Returns every viewable public post type currently registered.
	 *
	 * @return array<string,string>
	 */
	public function available_post_types(): array {
		$objects = get_post_types( array( 'public' => true ), 'objects' );
		$objects = is_array( $objects ) ? $objects : array();
		$types   = array();

		foreach ( $objects as $key => $object ) {
			if ( ! is_object( $object ) ) {
				continue;
			}
			$name = sanitize_key( (string) ( $object->name ?? $key ) );
			if ( '' === $name || empty( $object->public ) || 'attachment' === $name ) {
				continue;
			}
			if ( function_exists( 'is_post_type_viewable' ) && ! is_post_type_viewable( $object ) ) {
				continue;
			}
			$label = isset( $object->labels->name ) ? sanitize_text_field( (string) $object->labels->name ) : $name;
			$types[ $name ] = '' !== $label ? $label : $name;
		}

		/**
		 * Filters public post types offered by the Sharing tool.
		 *
		 * @param array<string,string> $types Post-type labels keyed by slug.
		 */
		$filtered = apply_filters( 'creceweb_lumen_lite_sharing_post_types', $types );
		return is_array( $filtered ) ? $filtered : $types;
	}

	/** @return bool */
	public function should_render(): bool {
		$this->compatibility->validate();
		$settings = $this->settings();
		if ( ! $this->compatibility->is_compatible() || empty( $settings['enabled'] ) || ! is_singular() || is_admin() ) {
			return false;
		}

		$post_id = get_queried_object_id();
		if ( $post_id < 1 || post_password_required( $post_id ) ) {
			return false;
		}

		$post_type = sanitize_key( (string) get_post_type( $post_id ) );
		$selected  = isset( $settings['post_types'] ) && is_array( $settings['post_types'] )
			? array_values( array_unique( array_filter( array_map( 'sanitize_key', $settings['post_types'] ) ) ) )
			: array();
		$available = $this->available_post_types();
		$actions   = $this->enabled_actions( $settings );
		$enabled   = '' !== $post_type && isset( $available[ $post_type ] ) && in_array( $post_type, $selected, true ) && ! empty( $actions );

		/**
		 * Filters whether Sharing can render on the current singular view.
		 *
		 * @param bool                $enabled Current decision.
		 * @param int                 $post_id Current post ID.
		 * @param string              $post_type Current public post type.
		 * @param array<string,mixed> $settings Sharing settings.
		 */
		return (bool) apply_filters( 'creceweb_lumen_lite_sharing_should_render', $enabled, $post_id, $post_type, $settings );
	}

	/** @return void */
	public function enqueue_assets(): void {
		if ( ! $this->should_render() ) {
			return;
		}

		wp_enqueue_style(
			'creceweb-lumen-lite-sharing',
			CRECEWEB_LUMEN_LITE_URL . 'assets/css/sharing.css',
			array(),
			CRECEWEB_LUMEN_LITE_ASSET_VERSION
		);

		$actions = $this->enabled_actions( $this->settings() );
		if ( in_array( 'copy', $actions, true ) ) {
			wp_enqueue_script(
				'creceweb-lumen-lite-sharing',
				CRECEWEB_LUMEN_LITE_URL . 'assets/js/sharing.js',
				array(),
				CRECEWEB_LUMEN_LITE_ASSET_VERSION,
				true
			);
		}
	}

	/** @return int */
	public function style_size_bytes(): int {
		$path = CRECEWEB_LUMEN_LITE_DIR . 'assets/css/sharing.css';
		$size = is_readable( $path ) ? filesize( $path ) : false;
		return false === $size ? 0 : max( 0, (int) $size );
	}

	/** @return int */
	public function script_size_bytes(): int {
		$path = CRECEWEB_LUMEN_LITE_DIR . 'assets/js/sharing.js';
		$size = is_readable( $path ) ? filesize( $path ) : false;
		return false === $size ? 0 : max( 0, (int) $size );
	}

	/**
	 * Adds the Sharing group before or after rendered singular content.
	 *
	 * @param string $content Rendered content.
	 * @return string
	 */
	public function filter_content( string $content ): string {
		if ( '' === trim( $content ) || str_contains( $content, 'data-cw-lumen-share' ) || ! $this->should_render() ) {
			return $content;
		}

		$post_id = get_queried_object_id();
		if ( $post_id < 1 || ( function_exists( 'get_the_ID' ) && get_the_ID() > 0 && get_the_ID() !== $post_id ) ) {
			return $content;
		}

		$markup = $this->markup( $post_id );
		if ( '' === $markup ) {
			return $content;
		}

		$position = sanitize_key( (string) ( $this->settings()['position'] ?? 'after' ) );
		return 'before' === $position ? $markup . $content : $content . $markup;
	}

	/**
	 * @param array<string,mixed> $settings Sharing settings.
	 * @return array<int,string>
	 */
	private function enabled_actions( array $settings ): array {
		$raw = isset( $settings['actions'] ) && is_array( $settings['actions'] ) ? $settings['actions'] : array();
		$actions = array();
		foreach ( array( 'copy', 'whatsapp', 'linkedin', 'facebook', 'email' ) as $action ) {
			if ( ! empty( $raw[ $action ] ) ) {
				$actions[] = $action;
			}
		}
		return $actions;
	}

	/**
	 * @param int $post_id Current post ID.
	 * @return string
	 */
	private function share_url( int $post_id ): string {
		$url = function_exists( 'wp_get_canonical_url' ) ? wp_get_canonical_url( $post_id ) : false;
		if ( ! is_string( $url ) || '' === $url ) {
			$url = get_permalink( $post_id );
		}
		return is_string( $url ) ? esc_url_raw( $url ) : '';
	}

	/**
	 * Reuses the same SVG services rendered by WordPress Core's Social Icons block.
	 *
	 * @param string $action Sharing action.
	 * @return string Sanitized SVG markup.
	 */
	private function icon_markup( string $action ): string {
		$services = array(
			'copy'     => 'chain',
			'whatsapp' => 'whatsapp',
			'linkedin' => 'linkedin',
			'facebook' => 'facebook',
			'email'    => 'mail',
		);
		$service = $services[ $action ] ?? 'share';
		if ( ! function_exists( 'block_core_social_link_get_icon' ) ) {
			return '';
		}

		$icon = block_core_social_link_get_icon( $service );
		if ( ! is_string( $icon ) || '' === $icon ) {
			return '';
		}

		return wp_kses(
			$icon,
			array(
				'svg'  => array(
					'width'       => true,
					'height'      => true,
					'viewbox'     => true,
					'viewBox'     => true,
					'version'     => true,
					'xmlns'       => true,
					'aria-hidden' => true,
					'focusable'   => true,
				),
				'path' => array(
					'd'         => true,
					'fill-rule' => true,
					'clip-rule' => true,
				),
			)
		);
	}

	/**
	 * Builds local CSS custom properties for Sharing appearance.
	 *
	 * @param array<string,mixed> $settings Sharing settings.
	 * @return string
	 */
	private function appearance_style( array $settings ): string {
		$properties = array(
			'--cw-lumen-sharing-button-size'  => max( 0, (float) ( $settings['button_size'] ?? 40 ) ) . 'px',
			'--cw-lumen-sharing-icon-size'    => max( 0, (float) ( $settings['icon_size'] ?? 20 ) ) . 'px',
			'--cw-lumen-sharing-border-width' => max( 0, (float) ( $settings['border_width'] ?? 1 ) ) . 'px',
			'--cw-lumen-sharing-border-radius' => max( 0, (float) ( $settings['border_radius'] ?? 8 ) ) . 'px',
		);
		$colors = array(
			'--cw-lumen-sharing-color'              => 'text_color',
			'--cw-lumen-sharing-color-hover'        => 'text_hover_color',
			'--cw-lumen-sharing-background'         => 'background_color',
			'--cw-lumen-sharing-background-hover'   => 'background_hover_color',
			'--cw-lumen-sharing-border-color'       => 'border_color',
			'--cw-lumen-sharing-border-color-hover' => 'border_hover_color',
		);
		foreach ( $colors as $property => $setting_key ) {
			$value = sanitize_hex_color( (string) ( $settings[ $setting_key ] ?? '' ) ) ?: '';
			if ( '' !== $value ) {
				$properties[ $property ] = $value;
			}
		}

		$style = '';
		foreach ( $properties as $property => $value ) {
			$style .= $property . ':' . $value . ';';
		}
		return $style;
	}

	/**
	 * @param int $post_id Current post ID.
	 * @return string
	 */
	private function markup( int $post_id ): string {
		$url = $this->share_url( $post_id );
		if ( '' === $url ) {
			return '';
		}

		$title    = sanitize_text_field( (string) get_the_title( $post_id ) );
		$settings = $this->settings();
		$actions  = $this->enabled_actions( $settings );
		if ( empty( $actions ) ) {
			return '';
		}

		$show_labels = ! empty( $settings['show_labels'] );
		$classes     = array( 'cw-lumen-lite-sharing' );
		if ( ! $show_labels ) {
			$classes[] = 'cw-lumen-lite-sharing--icons-only';
		}
		if ( ! empty( $settings['minimal_style'] ) ) {
			$classes[] = 'cw-lumen-lite-sharing--minimal';
		}

		$labels = array(
			'copy'     => __( 'Copy link', 'creceweb-lumen-lite' ),
			'whatsapp' => __( 'WhatsApp', 'creceweb-lumen-lite' ),
			'linkedin' => __( 'LinkedIn', 'creceweb-lumen-lite' ),
			'facebook' => __( 'Facebook', 'creceweb-lumen-lite' ),
			'email'    => __( 'Email', 'creceweb-lumen-lite' ),
		);
		$items = '';
		foreach ( $actions as $action ) {
			$label = $labels[ $action ];
			$icon  = $this->icon_markup( $action );
			$text  = '<span class="cw-lumen-lite-sharing__icon" aria-hidden="true">' . $icon . '</span>';
			if ( $show_labels ) {
				$text .= '<span class="cw-lumen-lite-sharing__label" data-cw-lumen-share-label>' . esc_html( $label ) . '</span>';
			}

			if ( 'copy' === $action ) {
				$items .= '<button type="button" class="cw-lumen-lite-sharing__action" data-cw-lumen-share-action="copy" data-cw-lumen-share-url="' . esc_attr( $url ) . '" aria-label="' . esc_attr( $label ) . '">' . $text . '</button>';
				continue;
			}

			$share_url = '';
			$target    = ' target="_blank" rel="noopener noreferrer"';
			if ( 'whatsapp' === $action ) {
				$share_url = 'https://wa.me/?text=' . rawurlencode( trim( $title . ' ' . $url ) );
			} elseif ( 'linkedin' === $action ) {
				$share_url = 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $url );
			} elseif ( 'facebook' === $action ) {
				$share_url = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url );
			} elseif ( 'email' === $action ) {
				$subject   = '' !== $title ? $title : __( 'Shared link', 'creceweb-lumen-lite' );
				$share_url = 'mailto:?subject=' . rawurlencode( $subject ) . '&body=' . rawurlencode( $url );
				$target    = '';
			}

			if ( '' !== $share_url ) {
				$items .= '<a class="cw-lumen-lite-sharing__action" href="' . esc_url( $share_url ) . '" data-cw-lumen-share-action="' . esc_attr( $action ) . '" aria-label="' . esc_attr( $label ) . '"' . $target . '>' . $text . '</a>';
			}
		}

		if ( '' === $items ) {
			return '';
		}

		$html = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" style="' . esc_attr( $this->appearance_style( $settings ) ) . '" data-cw-lumen-share data-cw-lumen-share-copied="' . esc_attr__( 'Link copied', 'creceweb-lumen-lite' ) . '" data-cw-lumen-share-copy-error="' . esc_attr__( 'Could not copy the link', 'creceweb-lumen-lite' ) . '" role="group" aria-label="' . esc_attr__( 'Share this content', 'creceweb-lumen-lite' ) . '"><span class="cw-lumen-lite-sharing__title">' . esc_html__( 'Share', 'creceweb-lumen-lite' ) . '</span><div class="cw-lumen-lite-sharing__actions">' . $items . '</div><span class="cw-lumen-lite-sharing__status" data-cw-lumen-share-status aria-live="polite" aria-atomic="true"></span></div>';

		/**
		 * Filters the final Sharing markup.
		 *
		 * @param string              $html Final markup.
		 * @param int                 $post_id Current post ID.
		 * @param array<string,mixed> $settings Sharing settings.
		 */
		$filtered = apply_filters( 'creceweb_lumen_lite_sharing_markup', $html, $post_id, $settings );
		return is_scalar( $filtered ) ? (string) $filtered : $html;
	}
}
