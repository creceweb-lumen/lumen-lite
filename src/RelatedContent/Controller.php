<?php
/**
 * Automatic related content for Lumen Lite.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\RelatedContent;

use CreceWeb\LumenLite\Data\Settings;
use CreceWeb\LumenLite\Support\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Finds related singular content through shared public taxonomy terms.
 */
final class Controller {
	/** @var array<int,array<int,int>> */
	private array $related_cache = array();

	public function __construct( private Compatibility $compatibility ) {}

	/** @return void */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 40 );
		add_filter( 'the_content', array( $this, 'filter_content' ), 1002 );
	}

	/** @return array<string,mixed> */
	public function settings(): array {
		$settings = Settings::get();
		$related  = isset( $settings['related_content'] ) && is_array( $settings['related_content'] ) ? $settings['related_content'] : array();

		return array_merge(
			array(
				'enabled'            => false,
				'title'              => __( 'Related content', 'creceweb-lumen-lite' ),
				'count'              => 3,
				'columns_desktop'    => 3,
				'columns_tablet'     => 2,
				'columns_mobile'     => 1,
				'image_ratio_width'  => 16.0,
				'image_ratio_height' => 9.0,
				'card_gap'           => '',
				'card_style'         => 'default',
				'show_image'         => true,
				'show_excerpt'       => true,
				'show_date'          => false,
				'post_types'         => array(),
			),
			$related
		);
	}

	/**
	 * Returns every viewable public post type supported by Related Content.
	 *
	 * Availability follows WordPress registration and viewability. The generic
	 * matching engine does not reserve otherwise supported public CPTs for a
	 * separate product tier or add-on.
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
		 * Filters public post types offered by Related Content.
		 *
		 * @param array<string,string> $types Post-type labels keyed by slug.
		 */
		$filtered = apply_filters( 'creceweb_lumen_lite_related_content_post_types', $types );
		if ( ! is_array( $filtered ) ) {
			return $types;
		}

		return $filtered;
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
		$enabled   = '' !== $post_type && isset( $available[ $post_type ] ) && in_array( $post_type, $selected, true );

		/**
		 * Filters whether Related Content can render on the current singular view.
		 *
		 * @param bool                $enabled Current decision.
		 * @param int                 $post_id Current post ID.
		 * @param string              $post_type Current public post type.
		 * @param array<string,mixed> $settings Related Content settings.
		 */
		return (bool) apply_filters( 'creceweb_lumen_lite_related_content_should_render', $enabled, $post_id, $post_type, $settings );
	}

	/** @return bool */
	public function needs_assets(): bool {
		if ( ! $this->should_render() ) {
			return false;
		}

		$post_id = get_queried_object_id();
		return $post_id > 0 && ! empty( $this->related_post_ids( $post_id ) );
	}

	/** @return void */
	public function enqueue_assets(): void {
		if ( ! $this->needs_assets() ) {
			return;
		}

		wp_enqueue_style(
			'creceweb-lumen-lite-related-content',
			CRECEWEB_LUMEN_LITE_URL . 'assets/css/related-content.css',
			array(),
			CRECEWEB_LUMEN_LITE_ASSET_VERSION
		);
	}

	/** @return int */
	public function style_size_bytes(): int {
		$path = CRECEWEB_LUMEN_LITE_DIR . 'assets/css/related-content.css';
		$size = is_readable( $path ) ? filesize( $path ) : false;
		return false === $size ? 0 : max( 0, (int) $size );
	}

	/**
	 * Returns ranked related post IDs for a singular content item.
	 *
	 * Candidates use the same post type and must share at least one term from a
	 * public taxonomy. More shared terms rank first; the candidate query's
	 * date/ID order provides deterministic tie breaking.
	 *
	 * @param int $post_id Current post ID.
	 * @return array<int,int>
	 */
	public function related_post_ids( int $post_id ): array {
		if ( $post_id < 1 ) {
			return array();
		}
		if ( isset( $this->related_cache[ $post_id ] ) ) {
			return $this->related_cache[ $post_id ];
		}

		$settings  = $this->settings();
		$post_type = sanitize_key( (string) get_post_type( $post_id ) );
		if ( '' === $post_type || ! isset( $this->available_post_types()[ $post_type ] ) ) {
			$this->related_cache[ $post_id ] = array();
			return array();
		}

		$taxonomies = $this->public_taxonomies( $post_type );
		if ( empty( $taxonomies ) ) {
			$this->related_cache[ $post_id ] = array();
			return array();
		}

		$current_terms = wp_get_object_terms( array( $post_id ), $taxonomies, array( 'fields' => 'all_with_object_id' ) );
		if ( ! is_array( $current_terms ) || empty( $current_terms ) ) {
			$this->related_cache[ $post_id ] = array();
			return array();
		}

		$current_keys = array();
		$terms_by_taxonomy = array();
		foreach ( $current_terms as $term ) {
			if ( ! is_object( $term ) ) {
				continue;
			}
			$taxonomy = sanitize_key( (string) ( $term->taxonomy ?? '' ) );
			$term_id  = absint( $term->term_id ?? 0 );
			if ( '' === $taxonomy || $term_id < 1 || ! in_array( $taxonomy, $taxonomies, true ) ) {
				continue;
			}
			$key = $taxonomy . ':' . $term_id;
			$current_keys[ $key ] = true;
			$terms_by_taxonomy[ $taxonomy ][ $term_id ] = $term_id;
		}
		if ( empty( $current_keys ) ) {
			$this->related_cache[ $post_id ] = array();
			return array();
		}

		$candidate_ids = array();
		foreach ( $terms_by_taxonomy as $taxonomy => $term_ids ) {
			$object_ids = get_objects_in_term( array_values( $term_ids ), $taxonomy );
			if ( ! is_array( $object_ids ) ) {
				continue;
			}
			foreach ( $object_ids as $object_id ) {
				$candidate_id = absint( $object_id );
				if ( $candidate_id > 0 && $candidate_id !== $post_id ) {
					$candidate_ids[ $candidate_id ] = $candidate_id;
				}
			}
		}
		$candidate_ids = array_values( $candidate_ids );
		if ( empty( $candidate_ids ) ) {
			$this->related_cache[ $post_id ] = array();
			return array();
		}

		$query_args = array(
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'post__in'               => $candidate_ids,
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'orderby'                => array( 'date' => 'DESC', 'ID' => 'DESC' ),
		);

		/**
		 * Filters the candidate query used by Related Content.
		 *
		 * @param array<string,mixed> $query_args Candidate WP_Query arguments.
		 * @param int                 $post_id Current post ID.
		 * @param array<int,string>   $taxonomies Public taxonomies used for matching.
		 */
		$filtered_args = apply_filters( 'creceweb_lumen_lite_related_content_query_args', $query_args, $post_id, $taxonomies );
		if ( is_array( $filtered_args ) ) {
			$query_args = $filtered_args;
		}

		$query = new \WP_Query( $query_args );
		$candidate_ids = isset( $query->posts ) && is_array( $query->posts )
			? array_values( array_unique( array_filter( array_map( 'absint', $query->posts ) ) ) )
			: array();
		$candidate_ids = array_values( array_diff( $candidate_ids, array( $post_id ) ) );
		if ( empty( $candidate_ids ) ) {
			$this->related_cache[ $post_id ] = array();
			return array();
		}

		$candidate_terms = wp_get_object_terms( $candidate_ids, $taxonomies, array( 'fields' => 'all_with_object_id' ) );
		if ( ! is_array( $candidate_terms ) ) {
			$candidate_terms = array();
		}

		$scores = array_fill_keys( $candidate_ids, 0 );
		$seen   = array();
		foreach ( $candidate_terms as $term ) {
			if ( ! is_object( $term ) ) {
				continue;
			}
			$candidate_id = absint( $term->object_id ?? 0 );
			$taxonomy     = sanitize_key( (string) ( $term->taxonomy ?? '' ) );
			$term_id      = absint( $term->term_id ?? 0 );
			$key          = $taxonomy . ':' . $term_id;
			$pair_key     = $candidate_id . '|' . $key;
			if ( isset( $scores[ $candidate_id ] ) && isset( $current_keys[ $key ] ) && ! isset( $seen[ $pair_key ] ) ) {
				++$scores[ $candidate_id ];
				$seen[ $pair_key ] = true;
			}
		}

		$order = array_flip( $candidate_ids );
		$ranked = array_values( array_filter( $candidate_ids, static fn( int $candidate_id ): bool => ( $scores[ $candidate_id ] ?? 0 ) > 0 ) );
		usort(
			$ranked,
			static function ( int $left, int $right ) use ( $scores, $order ): int {
				$score_comparison = ( $scores[ $right ] ?? 0 ) <=> ( $scores[ $left ] ?? 0 );
				return 0 !== $score_comparison ? $score_comparison : ( ( $order[ $left ] ?? PHP_INT_MAX ) <=> ( $order[ $right ] ?? PHP_INT_MAX ) );
			}
		);

		/**
		 * Filters ranked Related Content IDs before the configured count is applied.
		 *
		 * @param array<int,int>      $ranked Ranked candidate post IDs.
		 * @param int                 $post_id Current post ID.
		 * @param array<int,int>      $scores Shared-term scores keyed by candidate ID.
		 * @param array<string,mixed> $settings Related Content settings.
		 */
		$filtered_ids = apply_filters( 'creceweb_lumen_lite_related_content_post_ids', $ranked, $post_id, $scores, $settings );
		if ( is_array( $filtered_ids ) ) {
			$ranked = array_values( array_unique( array_filter( array_map( 'absint', $filtered_ids ) ) ) );
			$ranked = array_values( array_diff( $ranked, array( $post_id ) ) );
		}

		$count = max( 1, absint( $settings['count'] ?? 3 ) );
		$this->related_cache[ $post_id ] = array_slice( $ranked, 0, $count );
		return $this->related_cache[ $post_id ];
	}

	/**
	 * Adds Related Content after the rendered singular content.
	 *
	 * @param string $content Rendered content.
	 * @return string
	 */
	public function filter_content( string $content ): string {
		if ( '' === trim( $content ) || str_contains( $content, 'data-cw-lumen-related-content' ) || ! $this->should_render() ) {
			return $content;
		}

		$post_id = get_queried_object_id();
		if ( $post_id < 1 || ( function_exists( 'get_the_ID' ) && get_the_ID() > 0 && get_the_ID() !== $post_id ) ) {
			return $content;
		}

		$related_ids = $this->related_post_ids( $post_id );
		if ( empty( $related_ids ) ) {
			return $content;
		}

		$markup = $this->markup( $post_id, $related_ids );
		return '' === $markup ? $content : $content . $markup;
	}

	/**
	 * @param string $post_type Public post type.
	 * @return array<int,string>
	 */
	private function public_taxonomies( string $post_type ): array {
		$objects = get_object_taxonomies( $post_type, 'objects' );
		$objects = is_array( $objects ) ? $objects : array();
		$taxonomies = array();
		foreach ( $objects as $key => $object ) {
			if ( ! is_object( $object ) || empty( $object->public ) ) {
				continue;
			}
			$name = sanitize_key( (string) ( $object->name ?? $key ) );
			if ( '' !== $name ) {
				$taxonomies[] = $name;
			}
		}
		$taxonomies = array_values( array_unique( $taxonomies ) );

		/**
		 * Filters public taxonomies used for automatic relation matching.
		 *
		 * @param array<int,string> $taxonomies Public taxonomy slugs.
		 * @param string            $post_type Current post type.
		 */
		$filtered = apply_filters( 'creceweb_lumen_lite_related_content_taxonomies', $taxonomies, $post_type );
		return is_array( $filtered ) ? array_values( array_unique( array_filter( array_map( 'sanitize_key', $filtered ) ) ) ) : $taxonomies;
	}

	/**
	 * @param int            $post_id Current post ID.
	 * @param array<int,int> $related_ids Related post IDs.
	 * @return string
	 */
	private function markup( int $post_id, array $related_ids ): string {
		$settings        = $this->settings();
		$title           = sanitize_text_field( (string) ( $settings['title'] ?? '' ) );
		$columns_desktop = max( 1, absint( $settings['columns_desktop'] ?? $settings['columns'] ?? 3 ) );
		$columns_tablet  = max( 1, absint( $settings['columns_tablet'] ?? 2 ) );
		$columns_mobile  = max( 1, absint( $settings['columns_mobile'] ?? 1 ) );
		$ratio_width     = isset( $settings['image_ratio_width'] ) && is_numeric( $settings['image_ratio_width'] ) && is_finite( (float) $settings['image_ratio_width'] ) && (float) $settings['image_ratio_width'] > 0 ? (float) $settings['image_ratio_width'] : 16.0;
		$ratio_height    = isset( $settings['image_ratio_height'] ) && is_numeric( $settings['image_ratio_height'] ) && is_finite( (float) $settings['image_ratio_height'] ) && (float) $settings['image_ratio_height'] > 0 ? (float) $settings['image_ratio_height'] : 9.0;
		$card_gap        = isset( $settings['card_gap'] ) && is_scalar( $settings['card_gap'] ) && '' !== trim( (string) $settings['card_gap'] ) && is_numeric( $settings['card_gap'] ) && is_finite( (float) $settings['card_gap'] ) ? max( 0, (float) $settings['card_gap'] ) : '';
		$card_style      = sanitize_key( is_scalar( $settings['card_style'] ?? null ) ? (string) $settings['card_style'] : 'default' );
		if ( ! in_array( $card_style, array( 'default', 'elevated', 'minimal' ), true ) ) {
			$card_style = 'default';
		}
		$title_id = 'cw-lumen-related-content-title-' . $post_id;

		$items = '';
		foreach ( $related_ids as $related_id ) {
			$related_id = absint( $related_id );
			if ( $related_id < 1 || $related_id === $post_id ) {
				continue;
			}
			$url        = get_permalink( $related_id );
			$item_title = sanitize_text_field( (string) get_the_title( $related_id ) );
			if ( ! is_string( $url ) || '' === $url || '' === $item_title ) {
				continue;
			}

			$media = '';
			if ( ! empty( $settings['show_image'] ) && has_post_thumbnail( $related_id ) ) {
				$image = get_the_post_thumbnail(
					$related_id,
					'medium_large',
					array(
						'class'    => 'cw-lumen-lite-related-content__image',
						'loading'  => 'lazy',
						'decoding' => 'async',
					)
				);
				if ( is_string( $image ) && '' !== $image ) {
					$media = '<a class="cw-lumen-lite-related-content__media" href="' . esc_url( $url ) . '" tabindex="-1" aria-hidden="true">' . wp_kses_post( $image ) . '</a>';
				}
			}

			$meta = '';
			if ( ! empty( $settings['show_date'] ) ) {
				$date_display = get_the_date( '', $related_id );
				$date_iso     = get_post_time( DATE_W3C, true, $related_id );
				if ( is_string( $date_display ) && '' !== $date_display ) {
					$meta = '<time class="cw-lumen-lite-related-content__date" datetime="' . esc_attr( (string) $date_iso ) . '">' . esc_html( $date_display ) . '</time>';
				}
			}

			$excerpt = '';
			if ( ! empty( $settings['show_excerpt'] ) ) {
				$excerpt_text = trim( wp_strip_all_tags( (string) get_the_excerpt( $related_id ), true ) );
				if ( '' !== $excerpt_text ) {
					$excerpt = '<p class="cw-lumen-lite-related-content__excerpt">' . esc_html( $excerpt_text ) . '</p>';
				}
			}

			$items .= '<li class="cw-lumen-lite-related-content__item"><article class="cw-lumen-lite-related-content__card">' . $media . '<div class="cw-lumen-lite-related-content__body">' . $meta . '<h3 class="cw-lumen-lite-related-content__item-title"><a href="' . esc_url( $url ) . '">' . esc_html( $item_title ) . '</a></h3>' . $excerpt . '</div></article></li>';
		}

		if ( '' === $items ) {
			return '';
		}

		$heading = '' !== $title ? '<h2 id="' . esc_attr( $title_id ) . '" class="cw-lumen-lite-related-content__title">' . esc_html( $title ) . '</h2>' : '';
		$label   = '' !== $title ? ' aria-labelledby="' . esc_attr( $title_id ) . '"' : ' aria-label="' . esc_attr__( 'Related content', 'creceweb-lumen-lite' ) . '"';
		$style   = '--cw-lumen-related-columns-desktop:' . $columns_desktop . ';--cw-lumen-related-columns-tablet:' . $columns_tablet . ';--cw-lumen-related-columns-mobile:' . $columns_mobile . ';';
		$style  .= '--cw-lumen-related-image-ratio-width:' . (string) $ratio_width . ';--cw-lumen-related-image-ratio-height:' . (string) $ratio_height . ';';
		if ( '' !== $card_gap ) {
			$style .= '--cw-lumen-related-card-gap:' . (string) $card_gap . 'px;';
		}
		$classes = 'cw-lumen-lite-related-content cw-lumen-lite-related-content--style-' . $card_style;

		return '<section class="' . esc_attr( $classes ) . '" data-cw-lumen-related-content data-card-style="' . esc_attr( $card_style ) . '" data-columns-desktop="' . esc_attr( (string) $columns_desktop ) . '" data-columns-tablet="' . esc_attr( (string) $columns_tablet ) . '" data-columns-mobile="' . esc_attr( (string) $columns_mobile ) . '" style="' . esc_attr( $style ) . '"' . $label . '>' . $heading . '<ul class="cw-lumen-lite-related-content__grid">' . $items . '</ul></section>';
	}
}
