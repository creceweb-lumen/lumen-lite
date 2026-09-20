<?php
/**
 * Lightweight editorial utilities supplied by Lumen Lite.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Content;

use CreceWeb\LumenLite\Data\Settings;
use CreceWeb\LumenLite\Support\AdminRedirect;
use CreceWeb\LumenLite\Support\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds opt-in reading time and excerpt length without taking ownership of Theme templates.
 */
final class Controller {
	public function __construct( private Compatibility $compatibility ) {}

	/** @return void */
	public function register(): void {
		add_action( 'admin_post_cw_lumen_lite_save_content', array( $this, 'handle_save' ) );
		add_action( 'creceweb_lumen_single_after_meta', array( $this, 'render_single_reading_time' ), 20, 2 );
		add_action( 'creceweb_lumen_loop_after_meta', array( $this, 'render_loop_reading_time' ), 20, 2 );
		add_filter( 'excerpt_length', array( $this, 'filter_excerpt_length' ), 50 );
		add_filter( 'get_the_excerpt', array( $this, 'filter_displayed_excerpt' ), 999, 2 );
	}

	/** @return array<string,mixed> */
	public function settings(): array {
		$settings = Settings::get();
		$content  = isset( $settings['content'] ) && is_array( $settings['content'] ) ? $settings['content'] : array();

		return wp_parse_args(
			$content,
			array(
				'reading_time_enabled'   => false,
				'excerpt_length_enabled' => false,
				'excerpt_length'         => 30,
			)
		);
	}

	/** @return bool */
	public function needs_assets(): bool {
		$settings = $this->settings();
		if ( ! $this->compatibility->is_compatible() || empty( $settings['reading_time_enabled'] ) ) {
			return false;
		}

		/*
		 * Lumen Theme prints reading time from single.php and from its loop cards.
		 * Static pages use page.php and do not expose either reading-time hook, so
		 * loading content.css there would be unnecessary.
		 */
		if ( is_singular() ) {
			if ( is_page() ) {
				return false;
			}

			$post_id = get_queried_object_id();
			if ( $post_id < 1 ) {
				return false;
			}

			return in_array( get_post_type( $post_id ), $this->supported_post_types(), true );
		}

		return is_home() || is_search() || is_archive();
	}

	/**
	 * @param int    $post_id Post ID.
	 * @param string $meta_visibility Theme metadata visibility.
	 * @return void
	 */
	public function render_single_reading_time( int $post_id, string $meta_visibility = '' ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Theme hook contract.
		$this->render_reading_time( $post_id, 'single' );
	}

	/**
	 * @param int    $post_id Post ID.
	 * @param string $context Theme list context.
	 * @return void
	 */
	public function render_loop_reading_time( int $post_id, string $context = 'loop' ): void {
		$this->render_reading_time( $post_id, $context );
	}

	/**
	 * Renders the shared reading-time markup for one post and context.
	 *
	 * Lite enables reading time across the native single and post-list contexts
	 * exposed by Lumen Theme. The public filter remains available for sites or
	 * extensions that need to customize the location list.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $context Location identifier.
	 * @return void
	 */
	public function render_reading_time( int $post_id, string $context = 'single' ): void {
		$context = sanitize_key( $context );
		if ( ! $this->should_render( $post_id, $context ) ) {
			return;
		}

		$minutes = $this->reading_minutes( $post_id );
		if ( $minutes < 1 ) {
			return;
		}

		$label = $this->reading_label( $minutes, $post_id, $context );
		$class = sanitize_html_class( str_replace( '_', '-', $context ) );
		$html  = sprintf(
			'<div class="cw-lumen-lite-reading-time cw-lumen-lite-reading-time--%1$s" data-reading-context="%2$s">%3$s</div>',
			esc_attr( $class ),
			esc_attr( $context ),
			esc_html( $label )
		);

		/**
		 * Filters the final reading-time markup.
		 *
		 * @param string $html Final HTML.
		 * @param int    $minutes Estimated minutes.
		 * @param int    $post_id Post ID.
		 * @param string $context Location identifier.
		 */
		$html = apply_filters( 'creceweb_lumen_lite_reading_time_markup', $html, $minutes, $post_id, $context );
		if ( is_scalar( $html ) ) {
			echo wp_kses_post( (string) $html );
		}
	}

	/**
	 * @param int    $post_id Post ID.
	 * @param string $context Location identifier.
	 * @return bool
	 */
	public function should_render( int $post_id, string $context ): bool {
		$settings = $this->settings();
		$enabled  = ! empty( $settings['reading_time_enabled'] )
			&& $this->compatibility->is_compatible()
			&& ! post_password_required( $post_id )
			&& in_array( get_post_type( $post_id ), $this->supported_post_types(), true )
			&& in_array( $context, $this->reading_time_locations(), true );

		/**
		 * Filters whether reading time should appear for one post and location.
		 *
		 * @param bool   $enabled Current decision.
		 * @param int    $post_id Post ID.
		 * @param string $context Location identifier.
		 */
		return (bool) apply_filters( 'creceweb_lumen_lite_reading_time_should_render', $enabled, $post_id, $context );
	}

	/**
	 * Returns enabled reading-time locations.
	 *
	 * @return array<int,string>
	 */
	public function reading_time_locations(): array {
		$default_locations = array(
			'single',
			'blog_home',
			'category',
			'tag',
			'author_archive',
			'date_archive',
			'post_type_archive',
			'search',
			'archive',
			'loop',
		);

		/**
		 * Filters the locations where the shared reading time can render.
		 *
		 * All native Lumen Theme single/list contexts are enabled by default.
		 * The filter is a customization API rather than an availability gate.
		 *
		 * @param array<int,string> $locations Location identifiers.
		 */
		$locations = apply_filters( 'creceweb_lumen_lite_reading_time_locations', $default_locations );
		$locations = is_array( $locations ) ? $locations : $default_locations;

		return array_values( array_unique( array_filter( array_map( 'sanitize_key', $locations ) ) ) );
	}

	/**
	 * @return array<int,string>
	 */
	public function supported_post_types(): array {
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		$post_types = is_array( $post_types ) ? $post_types : array( 'post' );
		$post_types = array_values(
			array_filter(
				$post_types,
				static function ( string $post_type ): bool {
					return 'attachment' !== $post_type && post_type_supports( $post_type, 'editor' );
				}
			)
		);

		if ( empty( $post_types ) ) {
			$post_types = array( 'post' );
		}

		/**
		 * Filters post types supported by the shared reading-time service.
		 *
		 * Public post types with editor/content support are enabled by default.
		 * The filter can customize that list for a specific site.
		 *
		 * @param array<int,string> $post_types Post type names.
		 */
		$post_types = apply_filters( 'creceweb_lumen_lite_reading_time_post_types', $post_types );
		$post_types = is_array( $post_types ) ? $post_types : array( 'post' );

		return array_values( array_unique( array_filter( array_map( 'sanitize_key', $post_types ) ) ) );
	}

	/**
	 * @param int $post_id Post ID.
	 * @return int
	 */
	public function reading_minutes( int $post_id ): int {
		$content = (string) get_post_field( 'post_content', $post_id );
		$content = wp_strip_all_tags( strip_shortcodes( $content ) );
		$words   = preg_split( '/\\s+/u', trim( $content ), -1, PREG_SPLIT_NO_EMPTY );
		$count   = is_array( $words ) ? count( $words ) : 0;

		/**
		 * Filters the reading speed used by Lumen Lite.
		 *
		 * @param int $words_per_minute Reading speed.
		 * @param int $post_id Post ID.
		 */
		$speed = absint( apply_filters( 'creceweb_lumen_lite_reading_speed', 200, $post_id ) );
		$speed = max( 1, $speed );

		return $count > 0 ? max( 1, (int) ceil( $count / $speed ) ) : 0;
	}

	/**
	 * @param int    $minutes Estimated minutes.
	 * @param int    $post_id Post ID.
	 * @param string $context Location identifier.
	 * @return string
	 */
	public function reading_label( int $minutes, int $post_id, string $context = 'single' ): string {
		/* translators: %s: Estimated number of reading minutes. */
		$label = sprintf( _n( '%s min read', '%s min read', $minutes, 'creceweb-lumen-lite' ), number_format_i18n( $minutes ) );

		/**
		 * Filters the visible reading-time label.
		 *
		 * @param string $label Visible label.
		 * @param int    $minutes Estimated minutes.
		 * @param int    $post_id Post ID.
		 * @param string $context Location identifier.
		 */
		$label = apply_filters( 'creceweb_lumen_lite_reading_time_label', $label, $minutes, $post_id, $context );

		return is_scalar( $label ) ? sanitize_text_field( (string) $label ) : '';
	}

	/**
	 * @param int $length Existing excerpt length.
	 * @return int
	 */
	public function filter_excerpt_length( int $length ): int {
		$settings = $this->settings();
		if ( is_admin() || ! $this->compatibility->is_compatible() || empty( $settings['excerpt_length_enabled'] ) ) {
			return $length;
		}

		$custom = absint( $settings['excerpt_length'] ?? 30 );
		return $custom > 0 ? $custom : $length;
	}



	/**
	 * Limits the final excerpt that the Theme will display.
	 *
	 * The filter runs after WordPress and other components have prepared the
	 * excerpt, so it affects the text that is actually rendered. A manual
	 * excerpt is never changed in the database; only its visible length is
	 * limited when the Lite option is enabled.
	 *
	 * @param string   $excerpt Final excerpt prepared by WordPress.
	 * @param \WP_Post $post Post object.
	 * @return string
	 */
	public function filter_displayed_excerpt( string $excerpt, \WP_Post $post ): string {
		$settings = $this->settings();
		if ( is_admin() || ! $this->compatibility->is_compatible() || empty( $settings['excerpt_length_enabled'] ) ) {
			return $excerpt;
		}

		$post_type = get_post_type_object( $post->post_type );
		if ( ! $post_type instanceof \WP_Post_Type || ! $post_type->public || 'attachment' === $post->post_type ) {
			return $excerpt;
		}

		$length = absint( $settings['excerpt_length'] ?? 30 );
		if ( $length < 1 ) {
			return $excerpt;
		}

		$text = trim( wp_strip_all_tags( strip_shortcodes( $excerpt ), true ) );
		if ( '' === $text ) {
			$text = strip_shortcodes( (string) $post->post_content );
			if ( function_exists( 'excerpt_remove_blocks' ) ) {
				$text = excerpt_remove_blocks( $text );
			}
			$text = trim( wp_strip_all_tags( $text, true ) );
		}

		if ( '' === $text ) {
			return $excerpt;
		}

		$text = wp_specialchars_decode( $text, ENT_QUOTES );
		$text = preg_replace( '/(?:\s*(?:\[\s*(?:…|\.\.\.)\s*\]|…|\.\.\.))+\s*$/u', '', $text );
		$text = is_string( $text ) ? trim( $text ) : '';

		/**
		 * Filters the ending appended to a shortened Lumen Lite excerpt.
		 *
		 * @param string   $more Excerpt ending.
		 * @param \WP_Post $post Post object.
		 * @param int      $length Selected word limit.
		 */
		$more = apply_filters( 'creceweb_lumen_lite_excerpt_more', '[…]', $post, $length );
		$more = is_scalar( $more ) ? sanitize_text_field( (string) $more ) : '[…]';
		$more = '' !== $more ? ' ' . ltrim( $more ) : '';

		$trimmed = wp_trim_words( $text, $length, $more );

		/**
		 * Filters the final visible excerpt after applying the Lite limit.
		 *
		 * @param string   $trimmed Final visible excerpt.
		 * @param string   $excerpt Original excerpt received by Lite.
		 * @param \WP_Post $post Post object.
		 * @param int      $length Selected word limit.
		 */
		$trimmed = apply_filters( 'creceweb_lumen_lite_excerpt_text', $trimmed, $excerpt, $post, $length );

		return is_scalar( $trimmed ) ? (string) $trimmed : $excerpt;
	}

	/**
	 * Resolves an image-ratio shortcut without restricting custom ratios.
	 *
	 * @param array<string,mixed> $related_content Submitted Related Content values.
	 * @return array{0:float,1:float}
	 */
	private static function resolve_related_image_ratio( array $related_content ): array {
		$preset = is_scalar( $related_content['image_ratio_preset'] ?? null ) ? sanitize_text_field( (string) $related_content['image_ratio_preset'] ) : 'custom';
		$presets = array(
			'16:9' => array( 16.0, 9.0 ),
			'4:3'  => array( 4.0, 3.0 ),
			'3:2'  => array( 3.0, 2.0 ),
			'1:1'  => array( 1.0, 1.0 ),
			'9:16' => array( 9.0, 16.0 ),
		);

		if ( isset( $presets[ $preset ] ) ) {
			return $presets[ $preset ];
		}

		$width = isset( $related_content['image_ratio_width'] ) && is_numeric( $related_content['image_ratio_width'] ) && is_finite( (float) $related_content['image_ratio_width'] ) && (float) $related_content['image_ratio_width'] > 0 ? (float) $related_content['image_ratio_width'] : 16.0;
		$height = isset( $related_content['image_ratio_height'] ) && is_numeric( $related_content['image_ratio_height'] ) && is_finite( (float) $related_content['image_ratio_height'] ) && (float) $related_content['image_ratio_height'] > 0 ? (float) $related_content['image_ratio_height'] : 9.0;

		return array( $width, $height );
	}

	/** @return void */
	public function handle_save(): void {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'Your user account does not have permission to save these changes.', 'creceweb-lumen-lite' ) );
		}

		check_admin_referer( 'cw_lumen_lite_save_content' );
		$return_url = isset( $_POST[ AdminRedirect::FIELD ] )
			? esc_url_raw( wp_unslash( (string) $_POST[ AdminRedirect::FIELD ] ) )
			: ( isset( $_POST['_wp_http_referer'] ) ? esc_url_raw( wp_unslash( (string) $_POST['_wp_http_referer'] ) ) : '' );

		$excerpt_length = isset( $_POST['excerpt_length'] ) ? absint( wp_unslash( $_POST['excerpt_length'] ) ) : 30;
		$excerpt_length = max( 1, $excerpt_length );

		Settings::update_content(
			array(
				'reading_time_enabled'   => isset( $_POST['reading_time_enabled'] ),
				'excerpt_length_enabled' => isset( $_POST['excerpt_length_enabled'] ),
				'excerpt_length'         => $excerpt_length,
			)
		);

		$reading_progress = isset( $_POST['reading_progress'] ) && is_array( $_POST['reading_progress'] )
			? wp_unslash( $_POST['reading_progress'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each nested value is sanitized/validated below before Settings persists it.
			: array();
		$post_types = isset( $reading_progress['post_types'] ) && is_array( $reading_progress['post_types'] )
			? array_values( array_unique( array_filter( array_map( 'sanitize_key', $reading_progress['post_types'] ) ) ) )
			: array();
		$devices = isset( $reading_progress['devices'] ) && is_array( $reading_progress['devices'] ) ? $reading_progress['devices'] : array();
		$thickness = isset( $reading_progress['thickness'] ) && is_numeric( $reading_progress['thickness'] )
			? max( 0, (float) $reading_progress['thickness'] )
			: 4.0;
		Settings::update_reading_progress(
			array(
				'enabled'    => ! empty( $reading_progress['enabled'] ),
				'position'   => sanitize_key( (string) ( $reading_progress['position'] ?? 'top' ) ),
				'thickness'  => $thickness,
				'color'       => sanitize_hex_color( (string) ( $reading_progress['color'] ?? '#10b981' ) ) ?: '#10b981',
				'track_color' => sanitize_hex_color( (string) ( $reading_progress['track_color'] ?? '#e2e8f0' ) ) ?: '#e2e8f0',
				'post_types' => $post_types,
				'devices'    => array(
					'desktop' => ! empty( $devices['desktop'] ),
					'tablet'  => ! empty( $devices['tablet'] ),
					'mobile'  => ! empty( $devices['mobile'] ),
				),
			)
		);

		$table_of_contents = isset( $_POST['table_of_contents'] ) && is_array( $_POST['table_of_contents'] )
			? wp_unslash( $_POST['table_of_contents'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each nested value is sanitized/validated below before Settings persists it.
			: array();
		$toc_levels = isset( $table_of_contents['heading_levels'] ) && is_array( $table_of_contents['heading_levels'] )
			? array_values( array_unique( array_intersect( array( 'h2', 'h3', 'h4', 'h5', 'h6' ), array_map( 'sanitize_key', $table_of_contents['heading_levels'] ) ) ) )
			: array();
		$toc_post_types = isset( $table_of_contents['post_types'] ) && is_array( $table_of_contents['post_types'] )
			? array_values( array_unique( array_filter( array_map( 'sanitize_key', $table_of_contents['post_types'] ) ) ) )
			: array();
		Settings::update_table_of_contents(
			array(
				'enabled'          => ! empty( $table_of_contents['enabled'] ),
				'title'            => sanitize_text_field( (string) ( $table_of_contents['title'] ?? '' ) ),
				'position'         => sanitize_key( (string) ( $table_of_contents['position'] ?? 'before_first_heading' ) ),
				'style'            => sanitize_key( (string) ( $table_of_contents['style'] ?? 'boxed' ) ),
				'minimum_headings' => max( 1, absint( $table_of_contents['minimum_headings'] ?? 3 ) ),
				'heading_levels'   => $toc_levels,
				'post_types'       => $toc_post_types,
				'text_color'       => sanitize_hex_color( (string) ( $table_of_contents['text_color'] ?? '' ) ) ?: '',
				'link_color'       => sanitize_hex_color( (string) ( $table_of_contents['link_color'] ?? '' ) ) ?: '',
				'link_hover_color' => sanitize_hex_color( (string) ( $table_of_contents['link_hover_color'] ?? '' ) ) ?: '',
				'background_color' => sanitize_hex_color( (string) ( $table_of_contents['background_color'] ?? '' ) ) ?: '',
				'border_color'     => sanitize_hex_color( (string) ( $table_of_contents['border_color'] ?? '' ) ) ?: '',
			)
		);

		$sharing = isset( $_POST['sharing'] ) && is_array( $_POST['sharing'] )
			? wp_unslash( $_POST['sharing'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each nested value is sanitized/validated below before Settings persists it.
			: array();
		$sharing_post_types = isset( $sharing['post_types'] ) && is_array( $sharing['post_types'] )
			? array_values( array_unique( array_filter( array_map( 'sanitize_key', $sharing['post_types'] ) ) ) )
			: array();
		$sharing_actions = isset( $sharing['actions'] ) && is_array( $sharing['actions'] ) ? $sharing['actions'] : array();
		Settings::update_sharing(
			array(
				'enabled'     => ! empty( $sharing['enabled'] ),
				'position'    => sanitize_key( (string) ( $sharing['position'] ?? 'after' ) ),
				'show_labels'              => ! empty( $sharing['show_labels'] ),
				'minimal_style'            => ! empty( $sharing['minimal_style'] ),
				'post_types'               => $sharing_post_types,
				'button_size'              => isset( $sharing['button_size'] ) && is_numeric( $sharing['button_size'] ) ? max( 0, (float) $sharing['button_size'] ) : 40,
				'icon_size'                => isset( $sharing['icon_size'] ) && is_numeric( $sharing['icon_size'] ) ? max( 0, (float) $sharing['icon_size'] ) : 20,
				'border_width'             => isset( $sharing['border_width'] ) && is_numeric( $sharing['border_width'] ) ? max( 0, (float) $sharing['border_width'] ) : 1,
				'border_radius'            => isset( $sharing['border_radius'] ) && is_numeric( $sharing['border_radius'] ) ? max( 0, (float) $sharing['border_radius'] ) : 8,
				'text_color'               => sanitize_hex_color( (string) ( $sharing['text_color'] ?? '' ) ) ?: '',
				'text_hover_color'         => sanitize_hex_color( (string) ( $sharing['text_hover_color'] ?? '' ) ) ?: '',
				'background_color'         => sanitize_hex_color( (string) ( $sharing['background_color'] ?? '' ) ) ?: '',
				'background_hover_color'   => sanitize_hex_color( (string) ( $sharing['background_hover_color'] ?? '' ) ) ?: '',
				'border_color'             => sanitize_hex_color( (string) ( $sharing['border_color'] ?? '' ) ) ?: '',
				'border_hover_color'       => sanitize_hex_color( (string) ( $sharing['border_hover_color'] ?? '' ) ) ?: '',
				'actions'                  => array(
					'copy'     => ! empty( $sharing_actions['copy'] ),
					'whatsapp' => ! empty( $sharing_actions['whatsapp'] ),
					'linkedin' => ! empty( $sharing_actions['linkedin'] ),
					'facebook' => ! empty( $sharing_actions['facebook'] ),
					'email'    => ! empty( $sharing_actions['email'] ),
				),
			)
		);

		$related_content = isset( $_POST['related_content'] ) && is_array( $_POST['related_content'] )
			? wp_unslash( $_POST['related_content'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each nested value is sanitized/validated below before Settings persists it.
			: array();
		$related_post_types = isset( $related_content['post_types'] ) && is_array( $related_content['post_types'] )
			? array_values( array_unique( array_filter( array_map( 'sanitize_key', $related_content['post_types'] ) ) ) )
			: array();
		$related_card_style = sanitize_key( is_scalar( $related_content['card_style'] ?? null ) ? (string) $related_content['card_style'] : 'default' );
		if ( ! in_array( $related_card_style, array( 'default', 'elevated', 'minimal' ), true ) ) {
			$related_card_style = 'default';
		}
		$related_card_gap = '';
		if ( isset( $related_content['card_gap'] ) && is_scalar( $related_content['card_gap'] ) && '' !== trim( (string) $related_content['card_gap'] ) && is_numeric( $related_content['card_gap'] ) && is_finite( (float) $related_content['card_gap'] ) ) {
			$related_card_gap = max( 0, (float) $related_content['card_gap'] );
		}
		$related_image_ratio = self::resolve_related_image_ratio( $related_content );

		Settings::update_related_content(
			array(
				'enabled'            => ! empty( $related_content['enabled'] ),
				'title'              => sanitize_text_field( (string) ( $related_content['title'] ?? '' ) ),
				'count'              => max( 1, absint( $related_content['count'] ?? 3 ) ),
				'columns_desktop'    => max( 1, absint( $related_content['columns_desktop'] ?? 3 ) ),
				'columns_tablet'     => max( 1, absint( $related_content['columns_tablet'] ?? 2 ) ),
				'columns_mobile'     => max( 1, absint( $related_content['columns_mobile'] ?? 1 ) ),
				'image_ratio_width'  => $related_image_ratio[0],
				'image_ratio_height' => $related_image_ratio[1],
				'card_gap'           => $related_card_gap,
				'card_style'         => $related_card_style,
				'show_image'         => ! empty( $related_content['show_image'] ),
				'show_excerpt'       => ! empty( $related_content['show_excerpt'] ),
				'show_date'          => ! empty( $related_content['show_date'] ),
				'post_types'         => $related_post_types,
			)
		);

		$content_view = isset( $_POST['cw_lumen_lite_content_view'] ) ? sanitize_key( wp_unslash( (string) $_POST['cw_lumen_lite_content_view'] ) ) : '';
		if ( in_array( $content_view, array( 'general', 'reading-progress', 'table-of-contents', 'sharing', 'related-content' ), true ) ) {
			$return_url = add_query_arg( 'content_view', $content_view, $return_url );
		}

		$url = AdminRedirect::target( $return_url, admin_url( 'themes.php' ), 'content_saved' );
		wp_safe_redirect( esc_url_raw( $url ) );
		exit;
	}
}
