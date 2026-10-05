<?php
/** Posts Grid query service. @package CreceWebLumenLite */
namespace CreceWeb\LumenLite\PostsGrid;
use CreceWeb\LumenLite\ContentCollection\Catalog;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Query {
	public function __construct( private Catalog $catalog ) {}
	/** @param array<string,mixed> $config @return array<int,int> */
	public function ids( array $config ): array {
		$result = $this->result( $config );
		return $result['ids'];
	}
	/**
	 * Runs the shared Posts Grid query and returns reusable result metadata.
	 *
	 * @param array<string,mixed> $config Raw or normalized Posts Grid config.
	 * @return array{ids:array<int,int>,found_posts:int,max_num_pages:int,current_page:int,posts_per_page:int}
	 */
	public function result( array $config ): array {
		$config = Config::normalize( $config );
		$limit = Config::max_items( $config );
		if ( ! Config::visible_devices( $config ) ) { return $this->empty_result( $limit ); }
		$post_types = $this->catalog->post_types();
		$post_type = sanitize_key( (string) $config['post_type'] );
		if ( '' === $post_type || ! isset( $post_types[ $post_type ] ) ) { return $this->empty_result( $limit ); }
		$taxonomy = sanitize_key( (string) $config['taxonomy'] );
		$terms = $config['term_ids'];
		$tax_query = array();
		if ( '' !== $taxonomy ) {
			$available = $this->catalog->taxonomies( array( $post_type ) );
			if ( ! isset( $available[ $taxonomy ] ) ) { return $this->empty_result( $limit ); }
			if ( ! empty( $terms ) ) {
				$valid_terms = $this->catalog->terms( $taxonomy );
				$term_ids = array_values( array_filter( array_map( 'absint', $terms ), static fn( int $id ): bool => isset( $valid_terms[ $id ] ) ) );
				if ( empty( $term_ids ) ) { return $this->empty_result( $limit ); }
				$tax_query[] = array( 'taxonomy' => $taxonomy, 'field' => 'term_id', 'terms' => $term_ids, 'operator' => 'IN' );
			}
		} elseif ( ! empty( $terms ) ) {
			return $this->empty_result( $limit );
		}
		$args = array(
			'post_type' => $post_type, 'post_status' => 'publish', 'posts_per_page' => $limit,
			'fields' => 'ids', 'ignore_sticky_posts' => true, 'no_found_rows' => true,
		);
		if ( ! empty( $tax_query ) ) { $args['tax_query'] = $tax_query; } // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- User-selected public taxonomy is core feature.
		$source = (string) $config['source'];
		if ( 'manual' === $source ) {
			$manual = array_values( array_filter( array_map( 'absint', $config['manual_ids'] ) ) );
			if ( empty( $manual ) ) { return $this->empty_result( $limit ); }
			$args['post__in'] = $manual;
			$args['orderby'] = 'post__in';
		} elseif ( 'popular' === $source ) {
			$args['orderby'] = array( 'comment_count' => 'DESC', 'date' => 'DESC', 'ID' => 'DESC' );
		} else {
			$args['orderby'] = (string) $config['orderby'];
			$args['order'] = (string) $config['order'];
		}
		$filtered_args = apply_filters( 'creceweb_lumen_posts_grid_query_args', $args, $config );
		if ( is_array( $filtered_args ) ) { $args = $filtered_args; }
		$query = new \WP_Query( $args );
		$posts = is_array( $query->posts ?? null ) ? $query->posts : array();
		$ids = array();
		foreach ( $posts as $post ) {
			$id = is_object( $post ) ? absint( $post->ID ?? 0 ) : absint( $post );
			if ( $id > 0 ) { $ids[] = $id; }
		}
		$ids = array_values( array_slice( array_unique( $ids ), 0, $limit ) );
		$per_page = max( 1, absint( $args['posts_per_page'] ?? $limit ) );
		$current_page = max( 1, absint( $args['paged'] ?? 1 ) );
		$found_posts = isset( $query->found_posts ) ? absint( $query->found_posts ) : count( $ids );
		$max_num_pages = isset( $query->max_num_pages ) ? absint( $query->max_num_pages ) : 0;
		return array(
			'ids' => $ids,
			'found_posts' => $found_posts,
			'max_num_pages' => $max_num_pages,
			'current_page' => $current_page,
			'posts_per_page' => $per_page,
		);
	}
	/** @return array{ids:array<int,int>,found_posts:int,max_num_pages:int,current_page:int,posts_per_page:int} */
	private function empty_result( int $per_page ): array {
		return array( 'ids' => array(), 'found_posts' => 0, 'max_num_pages' => 0, 'current_page' => 1, 'posts_per_page' => max( 1, $per_page ) );
	}
}
