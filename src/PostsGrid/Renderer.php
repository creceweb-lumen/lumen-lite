<?php
/** Posts Grid shared HTML renderer. @package CreceWebLumenLite */
namespace CreceWeb\LumenLite\PostsGrid;
use CreceWeb\LumenLite\ContentCollection\ExcerptBuilder;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Renderer {
	private static int $instance = 0;
	private bool $used = false;
	public function __construct( private Query $query ) {}
	/** @param array<string,mixed> $raw_config */
	public function render( array $raw_config, string $context = 'shortcode' ): string {
		$config = Config::normalize( $raw_config );
		if ( ! Config::visible_devices( $config ) ) { return ''; }
		$result = $this->query->result( $config );
		$ids = $result['ids'];
		if ( empty( $ids ) ) { return ''; }
		$this->enqueue_style();
		$this->used = true;
		self::$instance++;
		$title = trim( (string) $config['title'] );
		$title_id = 'cw-lumen-posts-title-' . self::$instance;
		$items = $this->render_items( $ids, $config );
		if ( '' === $items ) { return ''; }
		$classes = array( 'cw-lumen-posts', 'cw-lumen-posts--layout-' . $config['layout'], 'cw-lumen-posts--style-' . $config['style'] );
		foreach ( $config['devices'] as $device => $visible ) { if ( ! $visible ) { $classes[] = 'cw-lumen-posts--hide-' . sanitize_key( (string) $device ); } }
		$style = '--cw-lumen-posts-columns-desktop:' . (int) $config['columns_desktop'] . ';--cw-lumen-posts-columns-tablet:' . (int) $config['columns_tablet'] . ';--cw-lumen-posts-columns-mobile:' . (int) $config['columns_mobile'] . ';';
		$style .= '--cw-lumen-posts-image-ratio-width:' . (float) $config['image_ratio_width'] . ';--cw-lumen-posts-image-ratio-height:' . (float) $config['image_ratio_height'] . ';';
		$style .= '--cw-lumen-posts-items-desktop:' . (int) $config['items_desktop'] . ';--cw-lumen-posts-items-tablet:' . (int) $config['items_tablet'] . ';--cw-lumen-posts-items-mobile:' . (int) $config['items_mobile'] . ';';
		if ( '' !== (string) $config['gap'] ) { $style .= '--cw-lumen-posts-gap:' . (float) $config['gap'] . 'px;'; }
		$heading = '' !== $title ? '<h2 id="' . esc_attr( $title_id ) . '" class="cw-lumen-posts__title">' . esc_html( $title ) . '</h2>' : '';
		$label = '' !== $title ? ' aria-labelledby="' . esc_attr( $title_id ) . '"' : ' aria-label="' . esc_attr__( 'Posts', 'creceweb-lumen-lite' ) . '"';
		$before = apply_filters( 'creceweb_lumen_posts_grid_before_grid', '', $config, $result, $context, self::$instance );
		$after = apply_filters( 'creceweb_lumen_posts_grid_after_grid', '', $config, $result, $context, self::$instance );
		$before = is_string( $before ) ? $before : '';
		$after = is_string( $after ) ? $after : '';
		return '<section class="' . esc_attr( implode( ' ', $classes ) ) . '" data-cw-lumen-posts data-context="' . esc_attr( sanitize_key( $context ) ) . '" style="' . esc_attr( $style ) . '"' . $label . '>' . $heading . $before . '<ul class="cw-lumen-posts__grid">' . $items . '</ul>' . $after . '</section>';
	}
	/** @param array<int,int> $ids @param array<string,mixed> $config */
	public function render_items( array $ids, array $config ): string {
		$config = Config::normalize( $config );
		$items = '';
		foreach ( array_values( $ids ) as $index => $id ) {
			$id = absint( $id );
			if ( $id < 1 ) { continue; }
			$number = $index + 1;
			$classes = array( 'cw-lumen-posts__item', 'cw-lumen-posts__item--' . $number );
			if ( $number > (int) $config['items_desktop'] ) { $classes[] = 'cw-lumen-posts__item--hide-desktop'; }
			if ( $number > (int) $config['items_tablet'] ) { $classes[] = 'cw-lumen-posts__item--hide-tablet'; }
			if ( $number > (int) $config['items_mobile'] ) { $classes[] = 'cw-lumen-posts__item--hide-mobile'; }
			$url = get_permalink( $id ); $post_title = get_the_title( $id );
			if ( ! is_string( $url ) || ! is_string( $post_title ) || '' === trim( $post_title ) ) { continue; }
			$media = '';
			if ( ! empty( $config['show_image'] ) && has_post_thumbnail( $id ) ) {
				$media = '<a class="cw-lumen-posts__media" href="' . esc_url( $url ) . '" tabindex="-1" aria-hidden="true">' . get_the_post_thumbnail( $id, 'large', array( 'loading' => 'lazy', 'decoding' => 'async' ) ) . '</a>';
			}
			$meta = '';
			if ( ! empty( $config['show_taxonomy'] ) ) {
				$term_name = $this->term_name( $id, (string) $config['post_type'], (string) $config['taxonomy'] );
				if ( '' !== $term_name ) { $meta .= '<span class="cw-lumen-posts__taxonomy">' . esc_html( $term_name ) . '</span>'; }
			}
			if ( ! empty( $config['show_date'] ) ) {
				$date = get_the_date( '', $id ); $iso = get_post_time( DATE_W3C, true, $id );
				if ( is_string( $date ) && '' !== $date ) { $meta .= '<time class="cw-lumen-posts__date" datetime="' . esc_attr( (string) $iso ) . '">' . esc_html( $date ) . '</time>'; }
			}
			$meta = '' !== $meta ? '<div class="cw-lumen-posts__meta">' . $meta . '</div>' : '';
			$excerpt = '';
			if ( ! empty( $config['show_excerpt'] ) ) { $x = ExcerptBuilder::for_post( $id ); if ( '' !== $x ) { $excerpt = '<p class="cw-lumen-posts__excerpt">' . esc_html( $x ) . '</p>'; } }
			$more = '';
			if ( ! empty( $config['show_read_more'] ) ) { $read_more = '' !== trim( (string) $config['read_more_text'] ) ? (string) $config['read_more_text'] : __( 'Read more', 'creceweb-lumen-lite' ); $more = '<a class="cw-lumen-posts__more" href="' . esc_url( $url ) . '">' . esc_html( $read_more ) . '<span aria-hidden="true"> →</span></a>'; }
			$items .= '<li class="' . esc_attr( implode( ' ', $classes ) ) . '"><article class="cw-lumen-posts__card">' . $media . '<div class="cw-lumen-posts__body">' . $meta . '<h3 class="cw-lumen-posts__item-title"><a href="' . esc_url( $url ) . '">' . esc_html( $post_title ) . '</a></h3>' . $excerpt . $more . '</div></article></li>';
		}
		return $items;
	}
	public function was_used(): bool { return $this->used; }
	private function enqueue_style(): void {
		if ( function_exists( 'wp_enqueue_style' ) && defined( 'CRECEWEB_LUMEN_LITE_URL' ) ) { wp_enqueue_style( 'creceweb-lumen-lite-posts-grid', CRECEWEB_LUMEN_LITE_URL . 'assets/css/posts-grid.css', array(), defined( 'CRECEWEB_LUMEN_LITE_ASSET_VERSION' ) ? CRECEWEB_LUMEN_LITE_ASSET_VERSION : null ); }
	}
	private function term_name( int $id, string $post_type, string $configured ): string {
		if ( ! function_exists( 'get_the_terms' ) ) { return ''; }
		$taxonomy = sanitize_key( $configured );
		if ( '' === $taxonomy && function_exists( 'get_object_taxonomies' ) ) {
			$objects = get_object_taxonomies( $post_type, 'objects' );
			foreach ( is_array( $objects ) ? $objects : array() as $key => $object ) { if ( is_object( $object ) && ! empty( $object->public ) ) { $taxonomy = sanitize_key( (string) ( $object->name ?? $key ) ); break; } }
		}
		if ( '' === $taxonomy ) { return ''; }
		$terms = get_the_terms( $id, $taxonomy );
		if ( is_wp_error( $terms ) || ! is_array( $terms ) || empty( $terms ) ) { return ''; }
		return sanitize_text_field( (string) ( $terms[0]->name ?? '' ) );
	}
}
