<?php
/**
 * Popular Content for Lumen Lite.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\PopularContent;

use CreceWeb\LumenLite\Data\Settings;
use CreceWeb\LumenLite\ContentCollection\Catalog;
use CreceWeb\LumenLite\ContentCollection\ExcerptBuilder;
use CreceWeb\LumenLite\Support\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a small, local list of popular published content.
 */
final class Controller {
	private const CACHE_GENERATION_OPTION = 'cw_lumen_lite_popular_content_cache_generation';
	private const CACHE_PREFIX = 'cw_lumen_lite_popular_';
	private const CACHE_SCHEMA = 2;

	/** @var array<string,array<int,int>> */
	private array $request_cache = array();

	public function __construct( private Compatibility $compatibility, private ?Catalog $content_catalog = null ) {
		$this->content_catalog = $this->content_catalog ?? new Catalog();
	}

	/** @return void */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 41 );
		add_filter( 'the_content', array( $this, 'filter_content' ), 1003 );
		add_action( 'creceweb_lumen_lite_popular_content_settings_updated', array( $this, 'invalidate_cache' ), 10, 0 );
		add_action( 'save_post', array( $this, 'invalidate_cache' ), 20, 0 );
		add_action( 'deleted_post', array( $this, 'invalidate_cache' ), 20, 0 );
		add_action( 'transition_post_status', array( $this, 'invalidate_cache' ), 20, 0 );
		add_action( 'transition_comment_status', array( $this, 'invalidate_cache' ), 20, 0 );
		add_action( 'comment_post', array( $this, 'invalidate_cache' ), 20, 0 );
		add_action( 'deleted_comment', array( $this, 'invalidate_cache' ), 20, 0 );
		add_action( 'set_object_terms', array( $this, 'invalidate_cache' ), 20, 0 );
	}

	/** @return array<string,mixed> */
	public function settings(): array {
		$settings = Settings::get();
		$popular  = isset( $settings['popular_content'] ) && is_array( $settings['popular_content'] ) ? $settings['popular_content'] : array();

		return array_merge(
			array(
				'enabled'            => false,
				'title'              => __( 'Popular content', 'creceweb-lumen-lite' ),
				'selection_mode'     => 'automatic',
				'items'              => 4,
				'period'             => 'all',
				'content_types'      => array(),
				'manual_ids'         => array(),
				'taxonomy'           => '',
				'term_ids'           => array(),
				'singular_position'  => 'after',
				'show_on_singular'   => true,
				'devices'            => array(
					'desktop' => true,
					'tablet'  => true,
					'mobile'  => true,
				),
				'show_image'         => true,
				'show_taxonomy'      => true,
				'show_date'          => false,
				'show_excerpt'       => false,
				'columns_desktop'    => 4,
				'columns_tablet'     => 2,
				'columns_mobile'     => 1,
				'image_ratio_width'  => 16.0,
				'image_ratio_height' => 9.0,
				'card_gap'           => '',
				'card_style'         => 'default',
			),
			$popular
		);
	}

	/** @return bool */
	public function is_configured(): bool {
		$settings = $this->settings();
		if ( empty( $settings['enabled'] ) || empty( $settings['show_on_singular'] ) || ! $this->has_visible_device( $settings ) ) {
			return false;
		}
		$content_types = $this->selected_content_types( $settings );
		if ( empty( $content_types ) || absint( $settings['items'] ?? 0 ) < 1 ) {
			return false;
		}
		if ( 'manual' === (string) ( $settings['selection_mode'] ?? 'automatic' ) ) {
			return ! empty( $this->sanitize_ids( $settings['manual_ids'] ?? array() ) );
		}
		return true;
	}

	/**
	 * Returns normalized frontend device visibility flags.
	 *
	 * Missing device settings are treated as visible on every device so upgrades
	 * preserve the pre-1.1.0 behavior. Explicit false values remain false.
	 *
	 * @param array<string,mixed> $settings Popular Content settings.
	 * @return array{desktop:bool,tablet:bool,mobile:bool}
	 */
	private function device_flags( array $settings ): array {
		$devices = isset( $settings['devices'] ) && is_array( $settings['devices'] ) ? $settings['devices'] : null;
		if ( null === $devices ) {
			return array( 'desktop' => true, 'tablet' => true, 'mobile' => true );
		}

		return array(
			'desktop' => ! empty( $devices['desktop'] ),
			'tablet'  => ! empty( $devices['tablet'] ),
			'mobile'  => ! empty( $devices['mobile'] ),
		);
	}

	/**
	 * @param array<string,mixed> $settings Popular Content settings.
	 * @return bool
	 */
	private function has_visible_device( array $settings ): bool {
		return in_array( true, $this->device_flags( $settings ), true );
	}

	/**
	 * Returns every viewable public post type supported by Popular Content.
	 *
	 * @return array<string,string>
	 */
	public function available_post_types(): array {
		$types = $this->content_catalog->post_types();
		$filtered = apply_filters( 'creceweb_lumen_lite_popular_content_post_types', $types );
		return is_array( $filtered ) ? $filtered : $types;
	}

	/** @param array<int,string>|null $post_types @return array<string,string> */
	public function available_taxonomies( ?array $post_types = null ): array {
		$post_types = null === $post_types ? array_keys( $this->available_post_types() ) : $post_types;
		return $this->content_catalog->taxonomies( $post_types );
	}

	/** @return array<int,string> */
	public function available_terms( string $taxonomy ): array {
		$taxonomy = sanitize_key( $taxonomy );
		if ( '' === $taxonomy || ! isset( $this->available_taxonomies()[ $taxonomy ] ) ) {
			return array();
		}
		return $this->content_catalog->terms( $taxonomy );
	}

	/** @return bool */
	public function is_eligible(): bool {
		$this->compatibility->validate();
		$settings = $this->settings();
		if ( ! $this->compatibility->is_compatible() || ! $this->is_configured() || is_admin() || ! is_singular() ) {
			return false;
		}

		$post_id = get_queried_object_id();
		if ( $post_id < 1 || post_password_required( $post_id ) ) {
			return false;
		}
		$post_type = sanitize_key( (string) get_post_type( $post_id ) );
		$selected  = $this->selected_content_types( $settings );
		$available = $this->available_post_types();
		$eligible  = '' !== $post_type && isset( $available[ $post_type ] ) && in_array( $post_type, $selected, true );
		if ( ! $eligible ) {
			return false;
		}

		/**
		 * Filters whether Popular Content may render on the current singular view.
		 *
		 * Returning false prevents the result query from running.
		 *
		 * @param bool                $eligible Current decision.
		 * @param int                 $post_id Current post ID.
		 * @param string              $post_type Current public post type.
		 * @param array<string,mixed> $settings Popular Content settings.
		 */
		return (bool) apply_filters( 'creceweb_lumen_lite_popular_content_should_render', true, $post_id, $post_type, $settings );
	}

	/** @return bool */
	public function should_render(): bool {
		if ( ! $this->is_eligible() ) {
			return false;
		}
		$post_id = get_queried_object_id();
		return $post_id > 0 && ! empty( $this->popular_post_ids( $post_id ) );
	}

	/** @return bool */
	public function needs_assets(): bool {
		return $this->should_render();
	}

	/** @return void */
	public function enqueue_assets(): void {
		if ( ! $this->needs_assets() ) {
			return;
		}
		wp_enqueue_style(
			'creceweb-lumen-lite-popular-content',
			CRECEWEB_LUMEN_LITE_URL . 'assets/css/popular-content.css',
			array(),
			CRECEWEB_LUMEN_LITE_ASSET_VERSION
		);
	}

	/** @return int */
	public function style_size_bytes(): int {
		$path = CRECEWEB_LUMEN_LITE_DIR . 'assets/css/popular-content.css';
		$size = is_readable( $path ) ? filesize( $path ) : false;
		return false === $size ? 0 : max( 0, (int) $size );
	}

	/**
	 * Returns final Popular Content IDs for the current singular context.
	 *
	 * @param int $post_id Current singular post ID.
	 * @return array<int,int>
	 */
	public function popular_post_ids( int $post_id ): array {
		if ( $post_id < 1 ) {
			return array();
		}
		$settings  = $this->settings();
		$post_type = sanitize_key( (string) get_post_type( $post_id ) );
		if ( '' === $post_type || ! in_array( $post_type, $this->selected_content_types( $settings ), true ) || ! isset( $this->available_post_types()[ $post_type ] ) ) {
			return array();
		}

		$cache_key = $this->cache_key( $post_id, $post_type, $settings );
		if ( isset( $this->request_cache[ $cache_key ] ) ) {
			return $this->request_cache[ $cache_key ];
		}

		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) && isset( $cached['ids'] ) && is_array( $cached['ids'] ) ) {
			$ids = $this->sanitize_ids( $cached['ids'] );
			$this->request_cache[ $cache_key ] = $ids;
			return $ids;
		}

		$mode = sanitize_key( (string) ( $settings['selection_mode'] ?? 'automatic' ) );
		$ids  = 'manual' === $mode
			? $this->manual_ids( $post_id, $post_type, $settings )
			: $this->automatic_ids( $post_id, $post_type, $settings );

		/**
		 * Filters Popular Content IDs after the selection mode resolves.
		 *
		 * @param array<int,int>      $ids Selected post IDs.
		 * @param int                 $post_id Current singular post ID.
		 * @param string              $post_type Current post type.
		 * @param array<string,mixed> $settings Popular Content settings.
		 */
		$filtered = apply_filters( 'creceweb_lumen_lite_popular_content_items', $ids, $post_id, $post_type, $settings );
		if ( is_array( $filtered ) ) {
			$ids = $this->sanitize_ids( $filtered );
		}
		$ids = array_values( array_diff( $ids, array( $post_id ) ) );
		$ids = array_slice( $ids, 0, max( 1, absint( $settings['items'] ?? 4 ) ) );

		$this->request_cache[ $cache_key ] = $ids;
		$ttl = (int) apply_filters( 'creceweb_lumen_lite_popular_content_cache_ttl', 10 * MINUTE_IN_SECONDS, $settings );
		if ( $ttl > 0 ) {
			set_transient( $cache_key, array( 'ids' => $ids ), $ttl );
		}
		return $ids;
	}

	/** @return void */
	public function invalidate_cache(): void {
		$current = absint( get_option( self::CACHE_GENERATION_OPTION, 1 ) );
		update_option( self::CACHE_GENERATION_OPTION, max( 1, $current + 1 ), false );
		$this->request_cache = array();
	}

	/** @return string */
	public static function cache_generation_option(): string {
		return self::CACHE_GENERATION_OPTION;
	}

	/**
	 * Adds Popular Content before or after rendered singular content.
	 *
	 * @param string $content Rendered content.
	 * @return string
	 */
	public function filter_content( string $content ): string {
		if ( '' === trim( $content ) || str_contains( $content, 'data-cw-lumen-popular-content' ) || ! $this->should_render() ) {
			return $content;
		}
		$post_id = get_queried_object_id();
		if ( $post_id < 1 || ( function_exists( 'get_the_ID' ) && get_the_ID() > 0 && get_the_ID() !== $post_id ) ) {
			return $content;
		}
		$ids = $this->popular_post_ids( $post_id );
		if ( empty( $ids ) ) {
			return $content;
		}
		$markup = $this->markup( $post_id, $ids );
		if ( '' === $markup ) {
			return $content;
		}
		$position = sanitize_key( (string) ( $this->settings()['singular_position'] ?? 'after' ) );
		return 'before' === $position ? $markup . $content : $content . $markup;
	}

	/**
	 * @param int                 $post_id Current singular post ID.
	 * @param string              $post_type Current post type.
	 * @param array<string,mixed> $settings Popular Content settings.
	 * @return array<int,int>
	 */
	private function automatic_ids( int $post_id, string $post_type, array $settings ): array {
		$query_args = array(
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			'posts_per_page'         => max( 1, absint( $settings['items'] ?? 4 ) ),
			'fields'                 => 'ids',
			'post__not_in'           => array( $post_id ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Excluding the current singular post is required for this bounded IDs-only recommendation query.
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => ! empty( $settings['show_taxonomy'] ),
			'orderby'                => array( 'comment_count' => 'DESC', 'date' => 'DESC', 'ID' => 'DESC' ),
		);

		$date_query = $this->date_query( (string) ( $settings['period'] ?? 'all' ) );
		if ( ! empty( $date_query ) ) {
			$query_args['date_query'] = $date_query;
		}

		$configured_taxonomy = sanitize_key( (string) ( $settings['taxonomy'] ?? '' ) );
		$term_ids            = $this->sanitize_ids( $settings['term_ids'] ?? array() );
		if ( '' !== $configured_taxonomy && ! empty( $term_ids ) ) {
			$taxonomy = $this->valid_taxonomy_for_post_type( $configured_taxonomy, $post_type );
			if ( '' === $taxonomy ) {
				return array();
			}
			$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Optional user-selected public taxonomy filtering is a core Popular Content feature on a bounded IDs-only query.
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $term_ids,
				),
			);
		}

		/**
		 * Filters the WP_Query arguments used by automatic Popular Content.
		 *
		 * @param array<string,mixed> $query_args Query arguments.
		 * @param int                 $post_id Current singular post ID.
		 * @param string              $post_type Current post type.
		 * @param array<string,mixed> $settings Popular Content settings.
		 */
		$filtered = apply_filters( 'creceweb_lumen_lite_popular_content_query_args', $query_args, $post_id, $post_type, $settings );
		if ( is_array( $filtered ) ) {
			$query_args = $filtered;
		}
		$query = new \WP_Query( $query_args );
		$ids = isset( $query->posts ) && is_array( $query->posts ) ? $this->sanitize_ids( $query->posts ) : array();
		return array_values( array_diff( $ids, array( $post_id ) ) );
	}

	/**
	 * @param int                 $post_id Current singular post ID.
	 * @param string              $post_type Current post type.
	 * @param array<string,mixed> $settings Popular Content settings.
	 * @return array<int,int>
	 */
	private function manual_ids( int $post_id, string $post_type, array $settings ): array {
		$result = array();
		foreach ( $this->sanitize_ids( $settings['manual_ids'] ?? array() ) as $candidate_id ) {
			if ( $candidate_id === $post_id || 'publish' !== (string) get_post_status( $candidate_id ) || $post_type !== sanitize_key( (string) get_post_type( $candidate_id ) ) ) {
				continue;
			}
			if ( ! $this->matches_taxonomy_filter( $candidate_id, $post_type, $settings ) ) {
				continue;
			}
			$result[] = $candidate_id;
		}
		return $result;
	}

	/**
	 * Applies the optional taxonomy/term filter to one candidate while preserving
	 * Manual selection order. No taxonomy or no selected terms means no filter.
	 *
	 * @param int                 $candidate_id Candidate post ID.
	 * @param string              $post_type Candidate post type.
	 * @param array<string,mixed> $settings Popular Content settings.
	 * @return bool
	 */
	private function matches_taxonomy_filter( int $candidate_id, string $post_type, array $settings ): bool {
		$configured_taxonomy = sanitize_key( (string) ( $settings['taxonomy'] ?? '' ) );
		$term_ids            = $this->sanitize_ids( $settings['term_ids'] ?? array() );

		// No selected taxonomy/terms means the optional filter is disabled.
		if ( '' === $configured_taxonomy || empty( $term_ids ) ) {
			return true;
		}

		// A configured filter must never degrade silently to "no filter" on a
		// post type that does not expose that taxonomy. Fail closed instead.
		$taxonomy = $this->valid_taxonomy_for_post_type( $configured_taxonomy, $post_type );
		if ( '' === $taxonomy ) {
			return false;
		}

		return has_term( $term_ids, $taxonomy, $candidate_id );
	}

	/**
	 * @param string $period Period key.
	 * @return array<int,array<string,mixed>>
	 */
	private function date_query( string $period ): array {
		$period = sanitize_key( $period );
		$after = array(
			'24h' => '24 hours ago',
			'7d'  => '7 days ago',
			'30d' => '30 days ago',
		)[ $period ] ?? '';
		return '' === $after ? array() : array( array( 'after' => $after, 'inclusive' => true ) );
	}

	/**
	 * @param int                 $post_id Current singular post ID.
	 * @param string              $post_type Current post type.
	 * @param array<string,mixed> $settings Popular Content settings.
	 * @return string
	 */
	private function cache_key( int $post_id, string $post_type, array $settings ): string {
		$generation = max( 1, absint( get_option( self::CACHE_GENERATION_OPTION, 1 ) ) );
		$payload = array(
			'schema'           => self::CACHE_SCHEMA,
			'generation'       => $generation,
			'post_type'        => $post_type,
			'mode'             => sanitize_key( (string) ( $settings['selection_mode'] ?? 'automatic' ) ),
			'items'            => max( 1, absint( $settings['items'] ?? 4 ) ),
			'period'           => sanitize_key( (string) ( $settings['period'] ?? 'all' ) ),
			'taxonomy'         => sanitize_key( (string) ( $settings['taxonomy'] ?? '' ) ),
			'term_ids'         => $this->sanitize_ids( $settings['term_ids'] ?? array() ),
			'manual_ids'       => $this->sanitize_ids( $settings['manual_ids'] ?? array() ),
			'current_post_id'  => $post_id,
		);
		$json = function_exists( 'wp_json_encode' ) ? wp_json_encode( $payload ) : json_encode( $payload );
		return self::CACHE_PREFIX . md5( is_string( $json ) ? $json : serialize( $payload ) );
	}

	/**
	 * @param array<string,mixed> $settings Settings.
	 * @return array<int,string>
	 */
	private function selected_content_types( array $settings ): array {
		$types = isset( $settings['content_types'] ) && is_array( $settings['content_types'] ) ? $settings['content_types'] : array();
		return array_values( array_unique( array_filter( array_map( 'sanitize_key', $types ) ) ) );
	}

	/**
	 * @param mixed $ids IDs.
	 * @return array<int,int>
	 */
	private function sanitize_ids( mixed $ids ): array {
		if ( ! is_array( $ids ) ) {
			return array();
		}
		$clean = array();
		foreach ( $ids as $id ) {
			$id = absint( $id );
			if ( $id > 0 && ! in_array( $id, $clean, true ) ) {
				$clean[] = $id;
			}
		}
		return $clean;
	}

	/**
	 * @param string $taxonomy Taxonomy slug.
	 * @param string $post_type Post type slug.
	 * @return string
	 */
	private function valid_taxonomy_for_post_type( string $taxonomy, string $post_type ): string {
		$taxonomy = sanitize_key( $taxonomy );
		if ( '' === $taxonomy ) {
			return '';
		}
		$objects = get_object_taxonomies( $post_type, 'objects' );
		$objects = is_array( $objects ) ? $objects : array();
		foreach ( $objects as $key => $object ) {
			if ( ! is_object( $object ) || empty( $object->public ) ) {
				continue;
			}
			$name = sanitize_key( (string) ( $object->name ?? $key ) );
			if ( $taxonomy === $name ) {
				return $taxonomy;
			}
		}
		return '';
	}

	/**
	 * @param int            $post_id Current singular post ID.
	 * @param array<int,int> $ids Popular post IDs.
	 * @return string
	 */
	private function markup( int $post_id, array $ids ): string {
		$settings        = $this->settings();
		$title           = sanitize_text_field( (string) ( $settings['title'] ?? '' ) );
		$columns_desktop = max( 1, absint( $settings['columns_desktop'] ?? 4 ) );
		$columns_tablet  = max( 1, absint( $settings['columns_tablet'] ?? 2 ) );
		$columns_mobile  = max( 1, absint( $settings['columns_mobile'] ?? 1 ) );
		$ratio_width     = isset( $settings['image_ratio_width'] ) && is_numeric( $settings['image_ratio_width'] ) && is_finite( (float) $settings['image_ratio_width'] ) && (float) $settings['image_ratio_width'] > 0 ? (float) $settings['image_ratio_width'] : 16.0;
		$ratio_height    = isset( $settings['image_ratio_height'] ) && is_numeric( $settings['image_ratio_height'] ) && is_finite( (float) $settings['image_ratio_height'] ) && (float) $settings['image_ratio_height'] > 0 ? (float) $settings['image_ratio_height'] : 9.0;
		$card_gap        = isset( $settings['card_gap'] ) && is_scalar( $settings['card_gap'] ) && '' !== trim( (string) $settings['card_gap'] ) && is_numeric( $settings['card_gap'] ) && is_finite( (float) $settings['card_gap'] ) ? max( 0, (float) $settings['card_gap'] ) : '';
		$card_style      = sanitize_key( is_scalar( $settings['card_style'] ?? null ) ? (string) $settings['card_style'] : 'default' );
		if ( ! in_array( $card_style, array( 'default', 'elevated', 'minimal' ), true ) ) {
			$card_style = 'default';
		}
		$title_id = 'cw-lumen-popular-content-title-' . $post_id;
		$items = '';

		foreach ( $ids as $item_id ) {
			$item_id = absint( $item_id );
			if ( $item_id < 1 || $item_id === $post_id ) {
				continue;
			}
			$url        = get_permalink( $item_id );
			$item_title = sanitize_text_field( (string) get_the_title( $item_id ) );
			if ( ! is_string( $url ) || '' === $url || '' === $item_title ) {
				continue;
			}

			$media = '';
			if ( ! empty( $settings['show_image'] ) && has_post_thumbnail( $item_id ) ) {
				$image = get_the_post_thumbnail( $item_id, 'medium_large', array( 'class' => 'cw-lumen-lite-popular-content__image', 'loading' => 'lazy', 'decoding' => 'async' ) );
				if ( is_string( $image ) && '' !== $image ) {
					$media = '<a class="cw-lumen-lite-popular-content__media" href="' . esc_url( $url ) . '" tabindex="-1" aria-hidden="true">' . wp_kses_post( $image ) . '</a>';
				}
			}

			$meta = '';
			if ( ! empty( $settings['show_taxonomy'] ) ) {
				$meta .= $this->taxonomy_markup( $item_id, sanitize_key( (string) get_post_type( $item_id ) ), (string) ( $settings['taxonomy'] ?? '' ) );
			}
			if ( ! empty( $settings['show_date'] ) ) {
				$date_display = get_the_date( '', $item_id );
				$date_iso     = get_post_time( DATE_W3C, true, $item_id );
				if ( is_string( $date_display ) && '' !== $date_display ) {
					$meta .= '<time class="cw-lumen-lite-popular-content__date" datetime="' . esc_attr( (string) $date_iso ) . '">' . esc_html( $date_display ) . '</time>';
				}
			}
			$meta = '' !== $meta ? '<div class="cw-lumen-lite-popular-content__meta">' . $meta . '</div>' : '';

			$excerpt = '';
			if ( ! empty( $settings['show_excerpt'] ) ) {
				$excerpt_text = $this->card_excerpt( $item_id );
				if ( '' !== $excerpt_text ) {
					$excerpt = '<p class="cw-lumen-lite-popular-content__excerpt">' . esc_html( $excerpt_text ) . '</p>';
				}
			}

			$items .= '<li class="cw-lumen-lite-popular-content__item"><article class="cw-lumen-lite-popular-content__card">' . $media . '<div class="cw-lumen-lite-popular-content__body">' . $meta . '<h3 class="cw-lumen-lite-popular-content__item-title"><a href="' . esc_url( $url ) . '">' . esc_html( $item_title ) . '</a></h3>' . $excerpt . '</div></article></li>';
		}
		if ( '' === $items ) {
			return '';
		}

		$heading = '' !== $title ? '<h2 id="' . esc_attr( $title_id ) . '" class="cw-lumen-lite-popular-content__title">' . esc_html( $title ) . '</h2>' : '';
		$label   = '' !== $title ? ' aria-labelledby="' . esc_attr( $title_id ) . '"' : ' aria-label="' . esc_attr__( 'Popular content', 'creceweb-lumen-lite' ) . '"';
		$style   = '--cw-lumen-popular-columns-desktop:' . $columns_desktop . ';--cw-lumen-popular-columns-tablet:' . $columns_tablet . ';--cw-lumen-popular-columns-mobile:' . $columns_mobile . ';';
		$style  .= '--cw-lumen-popular-image-ratio-width:' . (string) $ratio_width . ';--cw-lumen-popular-image-ratio-height:' . (string) $ratio_height . ';';
		if ( '' !== $card_gap ) {
			$style .= '--cw-lumen-popular-card-gap:' . (string) $card_gap . 'px;';
		}
		$classes = array( 'cw-lumen-lite-popular-content', 'cw-lumen-lite-popular-content--style-' . $card_style );
		foreach ( $this->device_flags( $settings ) as $device => $visible ) {
			if ( ! $visible ) {
				$classes[] = 'cw-lumen-lite-popular-content--hide-' . $device;
			}
		}

		return '<section class="' . esc_attr( implode( ' ', $classes ) ) . '" data-cw-lumen-popular-content data-card-style="' . esc_attr( $card_style ) . '" style="' . esc_attr( $style ) . '"' . $label . '>' . $heading . '<ul class="cw-lumen-lite-popular-content__grid">' . $items . '</ul></section>';
	}


	/**
	 * Returns a card excerpt without re-entering the public get_the_excerpt filter
	 * while Popular Content itself is executing inside the_content.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function card_excerpt( int $post_id ): string {
		return ExcerptBuilder::for_post( $post_id );
	}


	/**
	 * @param int    $post_id Post ID.
	 * @param string $post_type Post type.
	 * @param string $configured_taxonomy Configured taxonomy.
	 * @return string
	 */
	private function taxonomy_markup( int $post_id, string $post_type, string $configured_taxonomy ): string {
		$taxonomy = $this->valid_taxonomy_for_post_type( $configured_taxonomy, $post_type );
		if ( '' === $taxonomy ) {
			$objects = get_object_taxonomies( $post_type, 'objects' );
			$objects = is_array( $objects ) ? $objects : array();
			foreach ( $objects as $key => $object ) {
				if ( is_object( $object ) && ! empty( $object->public ) ) {
					$taxonomy = sanitize_key( (string) ( $object->name ?? $key ) );
					if ( '' !== $taxonomy ) {
						break;
					}
				}
			}
		}
		if ( '' === $taxonomy ) {
			return '';
		}
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( is_wp_error( $terms ) || ! is_array( $terms ) || empty( $terms ) ) {
			return '';
		}
		$term = reset( $terms );
		if ( ! is_object( $term ) ) {
			return '';
		}
		$name = sanitize_text_field( (string) ( $term->name ?? '' ) );
		$url  = get_term_link( $term );
		if ( '' === $name || is_wp_error( $url ) || ! is_string( $url ) || '' === $url ) {
			return '';
		}
		return '<a class="cw-lumen-lite-popular-content__taxonomy" href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
	}
}
