<?php
/** Posts Grid normalized configuration. @package CreceWebLumenLite */
namespace CreceWeb\LumenLite\PostsGrid;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Config {
	/** @return array<string,mixed> */
	public static function defaults(): array {
		return array(
			'title' => '', 'post_type' => 'post', 'source' => 'latest',
			'items_desktop' => 6, 'items_tablet' => 4, 'items_mobile' => 3,
			'orderby' => 'date', 'order' => 'DESC', 'taxonomy' => '', 'term_ids' => array(), 'manual_ids' => array(),
			'show_image' => true, 'show_taxonomy' => true, 'show_date' => true, 'show_excerpt' => true, 'show_read_more' => true,
			'read_more_text' => 'Read more', 'layout' => 'grid',
			'columns_desktop' => 3, 'columns_tablet' => 2, 'columns_mobile' => 1,
			'image_ratio_width' => 16.0, 'image_ratio_height' => 9.0, 'gap' => '', 'style' => 'default',
			'devices' => array( 'desktop' => true, 'tablet' => true, 'mobile' => true ),
		);
	}
	/** @param array<string,mixed> $raw @param array<string,mixed>|null $base @return array<string,mixed> */
	public static function normalize( array $raw, ?array $base = null ): array {
		$d = null === $base ? self::defaults() : array_merge( self::defaults(), $base );
		$v = array_merge( $d, $raw );
		$source = sanitize_key( (string) $v['source'] );
		if ( ! in_array( $source, array( 'latest', 'popular', 'manual' ), true ) ) { $source = 'latest'; }
		$orderby = sanitize_key( (string) $v['orderby'] );
		if ( ! in_array( $orderby, array( 'date', 'title', 'comment_count' ), true ) ) { $orderby = 'date'; }
		$order = strtoupper( sanitize_text_field( (string) $v['order'] ) );
		if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) { $order = 'DESC'; }
		$layout = sanitize_key( (string) $v['layout'] );
		if ( ! in_array( $layout, array( 'grid', 'list' ), true ) ) { $layout = 'grid'; }
		$style = sanitize_key( (string) $v['style'] );
		if ( ! in_array( $style, array( 'default', 'elevated', 'minimal' ), true ) ) { $style = 'default'; }
		$devices = isset( $v['devices'] ) && is_array( $v['devices'] ) ? $v['devices'] : array();
		$taxonomy = sanitize_key( (string) $v['taxonomy'] );
		$term_ids = '' === $taxonomy ? array() : self::ids( $v['term_ids'] ?? array() );
		$gap = '';
		if ( is_scalar( $v['gap'] ?? null ) && '' !== trim( (string) $v['gap'] ) && is_numeric( $v['gap'] ) && is_finite( (float) $v['gap'] ) ) { $gap = max( 0, (float) $v['gap'] ); }
		$ratio_w = is_numeric( $v['image_ratio_width'] ?? null ) && (float) $v['image_ratio_width'] > 0 ? (float) $v['image_ratio_width'] : 16.0;
		$ratio_h = is_numeric( $v['image_ratio_height'] ?? null ) && (float) $v['image_ratio_height'] > 0 ? (float) $v['image_ratio_height'] : 9.0;
		$normalized = array(
			'title' => sanitize_text_field( (string) $v['title'] ),
			'post_type' => sanitize_key( (string) $v['post_type'] ), 'source' => $source,
			'items_desktop' => max( 1, absint( $v['items_desktop'] ?? 6 ) ), 'items_tablet' => max( 1, absint( $v['items_tablet'] ?? 4 ) ), 'items_mobile' => max( 1, absint( $v['items_mobile'] ?? 3 ) ),
			'orderby' => $orderby, 'order' => $order, 'taxonomy' => $taxonomy,
			'term_ids' => $term_ids, 'manual_ids' => self::ids( $v['manual_ids'] ?? array() ),
			'show_image' => ! empty( $v['show_image'] ), 'show_taxonomy' => ! empty( $v['show_taxonomy'] ), 'show_date' => ! empty( $v['show_date'] ), 'show_excerpt' => ! empty( $v['show_excerpt'] ), 'show_read_more' => ! empty( $v['show_read_more'] ),
			'read_more_text' => sanitize_text_field( (string) $v['read_more_text'] ), 'layout' => $layout,
			'columns_desktop' => max( 1, absint( $v['columns_desktop'] ?? 3 ) ), 'columns_tablet' => max( 1, absint( $v['columns_tablet'] ?? 2 ) ), 'columns_mobile' => max( 1, absint( $v['columns_mobile'] ?? 1 ) ),
			'image_ratio_width' => $ratio_w, 'image_ratio_height' => $ratio_h, 'gap' => $gap, 'style' => $style,
			'devices' => array( 'desktop' => ! empty( $devices['desktop'] ), 'tablet' => ! empty( $devices['tablet'] ), 'mobile' => ! empty( $devices['mobile'] ) ),
		);
		$filtered = apply_filters( 'creceweb_lumen_posts_grid_config', $normalized, $raw, $base );
		return is_array( $filtered ) ? $filtered : $normalized;
	}
	/** @param array<string,mixed> $config */
	public static function max_items( array $config ): int {
		$devices = isset( $config['devices'] ) && is_array( $config['devices'] ) ? $config['devices'] : array( 'desktop' => true, 'tablet' => true, 'mobile' => true );
		$counts = array();
		foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
			if ( ! empty( $devices[ $device ] ) ) {
				$counts[] = max( 1, absint( $config[ 'items_' . $device ] ?? 1 ) );
			}
		}
		return empty( $counts ) ? 1 : max( $counts );
	}
	/** @param array<string,mixed> $config */
	public static function visible_devices( array $config ): bool { $d = $config['devices'] ?? array(); return is_array( $d ) && ( ! empty( $d['desktop'] ) || ! empty( $d['tablet'] ) || ! empty( $d['mobile'] ) ); }
	/** @return array<int,int> */
	private static function ids( mixed $value ): array { if ( is_string( $value ) ) { $value = preg_split( '/[^0-9]+/', $value ) ?: array(); } if ( ! is_array( $value ) ) { return array(); } return array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) ); }
}
