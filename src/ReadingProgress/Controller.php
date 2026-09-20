<?php
/**
 * Reading Progress for Lumen Lite.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\ReadingProgress;

use CreceWeb\LumenLite\Data\Settings;
use CreceWeb\LumenLite\Support\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a lightweight page-reading progress indicator on selected public post types.
 */
final class Controller {
	private bool $rendered = false;

	public function __construct( private Compatibility $compatibility ) {}

	/** @return void */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 37 );
		add_action( 'wp_body_open', array( $this, 'render' ), 20 );
		add_action( 'wp_footer', array( $this, 'render' ), 4 );
	}

	/** @return array<string,mixed> */
	public function settings(): array {
		$settings = Settings::get();
		$progress = isset( $settings['reading_progress'] ) && is_array( $settings['reading_progress'] ) ? $settings['reading_progress'] : array();

		return wp_parse_args(
			$progress,
			array(
				'enabled'    => false,
				'position'   => 'top',
				'thickness'   => 4,
				'color'       => '#10b981',
				'track_color' => '#e2e8f0',
				'post_types' => array(),
				'devices'    => array( 'desktop' => true, 'tablet' => true, 'mobile' => true ),
			)
		);
	}

	/**
	 * Returns all public post types currently available to the site.
	 *
	 * @return array<string,string> Map of post-type slug to human label.
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
			if ( '' === $name || empty( $object->public ) ) {
				continue;
			}
			if ( function_exists( 'is_post_type_viewable' ) && ! is_post_type_viewable( $object ) ) {
				continue;
			}
			$label = isset( $object->labels->name ) ? sanitize_text_field( (string) $object->labels->name ) : $name;
			$types[ $name ] = '' !== $label ? $label : $name;
		}

		/**
		 * Filters public post types offered by Reading Progress.
		 *
		 * The default is every viewable public post type registered on the site.
		 * The filter is a site-level customization API.
		 *
		 * @param array<string,string> $types Post-type labels keyed by slug.
		 */
		$filtered = apply_filters( 'creceweb_lumen_lite_reading_progress_post_types', $types );
		return is_array( $filtered ) ? $filtered : $types;
	}

	/** @return bool */
	public function should_render(): bool {
		$this->compatibility->validate();
		$settings = $this->settings();
		if ( ! $this->compatibility->is_compatible() || empty( $settings['enabled'] ) || ! is_singular() ) {
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
		$enabled   = '' !== $post_type && isset( $available[ $post_type ] ) && in_array( $post_type, $selected, true );

		/**
		 * Filters whether Reading Progress renders on the current singular view.
		 *
		 * @param bool   $enabled Current decision.
		 * @param int    $post_id Current post ID.
		 * @param string $post_type Current public post type.
		 * @param array<string,mixed> $settings Reading Progress settings.
		 */
		return (bool) apply_filters( 'creceweb_lumen_lite_reading_progress_should_render', $enabled, $post_id, $post_type, $settings );
	}

	/** @return void */
	public function enqueue_assets(): void {
		if ( ! $this->should_render() ) {
			return;
		}

		wp_enqueue_style(
			'creceweb-lumen-lite-reading-progress',
			CRECEWEB_LUMEN_LITE_URL . 'assets/css/reading-progress.css',
			array(),
			CRECEWEB_LUMEN_LITE_ASSET_VERSION
		);

		wp_add_inline_style( 'creceweb-lumen-lite-reading-progress', $this->inline_css() );

		wp_enqueue_script(
			'creceweb-lumen-lite-reading-progress',
			CRECEWEB_LUMEN_LITE_URL . 'assets/js/reading-progress.js',
			array(),
			CRECEWEB_LUMEN_LITE_ASSET_VERSION,
			true
		);
	}

	/** @return string */
	private function inline_css(): string {
		$settings  = $this->settings();
		$thickness   = isset( $settings['thickness'] ) && is_numeric( $settings['thickness'] ) ? max( 0, (float) $settings['thickness'] ) : 4.0;
		$color       = sanitize_hex_color( (string) ( $settings['color'] ?? '#10b981' ) ) ?: '#10b981';
		$track_color = sanitize_hex_color( (string) ( $settings['track_color'] ?? '#e2e8f0' ) ) ?: '#e2e8f0';

		return sprintf(
			'.cw-lumen-lite-reading-progress{--cw-lumen-lite-reading-progress-thickness:%1$spx;--cw-lumen-lite-reading-progress-color:%2$s;--cw-lumen-lite-reading-progress-track-color:%3$s;}',
			(string) $thickness,
			$color,
			$track_color
		);
	}

	/** @return int */
	public function style_size_bytes(): int {
		$path = CRECEWEB_LUMEN_LITE_DIR . 'assets/css/reading-progress.css';
		$size = is_readable( $path ) ? filesize( $path ) : false;
		$file_size = false === $size ? 0 : max( 0, (int) $size );
		return $file_size + strlen( $this->inline_css() );
	}

	/** @return int */
	public function script_size_bytes(): int {
		$path = CRECEWEB_LUMEN_LITE_DIR . 'assets/js/reading-progress.js';
		$size = is_readable( $path ) ? filesize( $path ) : false;
		return false === $size ? 0 : max( 0, (int) $size );
	}

	/** @return void */
	public function render(): void {
		if ( $this->rendered || ! $this->should_render() ) {
			return;
		}
		$this->rendered = true;

		$settings = $this->settings();
		$position = in_array( (string) ( $settings['position'] ?? 'top' ), array( 'top', 'bottom' ), true ) ? (string) $settings['position'] : 'top';
		$devices  = isset( $settings['devices'] ) && is_array( $settings['devices'] ) ? $settings['devices'] : array();
		$classes  = array( 'cw-lumen-lite-reading-progress' );
		foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
			if ( empty( $devices[ $device ] ) ) {
				$classes[] = 'cw-lumen-lite-reading-progress--hide-' . $device;
			}
		}

		$post_id = max( 0, (int) get_queried_object_id() );
		$target  = $post_id > 0 ? '#post-' . $post_id : '';

		printf(
			'<div class="%1$s" data-cw-lumen-reading-progress data-position="%2$s" data-cw-lumen-reading-progress-post-id="%3$s" data-cw-lumen-reading-progress-target="%4$s" aria-hidden="true"><span class="cw-lumen-lite-reading-progress__bar" data-cw-lumen-reading-progress-bar></span></div>',
			esc_attr( implode( ' ', array_map( 'sanitize_html_class', $classes ) ) ),
			esc_attr( $position ),
			esc_attr( (string) $post_id ),
			esc_attr( $target )
		);
	}
}
