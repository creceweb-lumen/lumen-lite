<?php
/** Dynamic Gutenberg block adapter. @package CreceWebLumenLite */
namespace CreceWeb\LumenLite\PostsGrid;
use CreceWeb\LumenLite\Data\Settings;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Block {
	public function __construct( private Renderer $renderer ) {}
	public function register(): void {
		if ( ! function_exists( 'register_block_type' ) ) { return; }
		register_block_type( CRECEWEB_LUMEN_LITE_DIR . 'blocks/posts-grid', array( 'render_callback' => array( $this, 'render' ) ) );
	}

	/** @param array<int,array<string,mixed>> $categories @return array<int,array<string,mixed>> */
	public function register_category( array $categories, mixed $editor_context = null ): array {
		foreach ( $categories as $category ) {
			if ( isset( $category['slug'] ) && 'creceweb-lumen' === $category['slug'] ) { return $categories; }
		}
		array_unshift( $categories, array( 'slug' => 'creceweb-lumen', 'title' => __( 'Lumen', 'creceweb-lumen-lite' ) ) );
		return $categories;
	}
	/** @param array<string,mixed> $attributes */
	public function render( array $attributes ): string {
		$settings = Settings::get();
		$base = Config::normalize( isset( $settings['posts_grid'] ) && is_array( $settings['posts_grid'] ) ? $settings['posts_grid'] : array() );
		$use_global = ! array_key_exists( 'useGlobalDefaults', $attributes ) || ! empty( $attributes['useGlobalDefaults'] );
		if ( $use_global ) { return $this->renderer->render( $base, 'block' ); }
		$map = array(
			'title'=>'title','postType'=>'post_type','source'=>'source','itemsDesktop'=>'items_desktop','itemsTablet'=>'items_tablet','itemsMobile'=>'items_mobile','orderby'=>'orderby','order'=>'order','taxonomy'=>'taxonomy','termIds'=>'term_ids','manualIds'=>'manual_ids',
			'showImage'=>'show_image','showTaxonomy'=>'show_taxonomy','showDate'=>'show_date','showExcerpt'=>'show_excerpt','showReadMore'=>'show_read_more','readMoreText'=>'read_more_text','layout'=>'layout','columnsDesktop'=>'columns_desktop','columnsTablet'=>'columns_tablet','columnsMobile'=>'columns_mobile','imageRatioWidth'=>'image_ratio_width','imageRatioHeight'=>'image_ratio_height','gap'=>'gap','style'=>'style',
		);
		$raw = array(); foreach ( $map as $from=>$to ) { if ( array_key_exists( $from, $attributes ) ) { $raw[ $to ] = $attributes[ $from ]; } }
		if ( isset( $attributes['extensions'] ) && is_array( $attributes['extensions'] ) ) { $raw['extensions'] = $attributes['extensions']; }
		$raw['devices'] = array( 'desktop'=>! empty( $attributes['desktop'] ), 'tablet'=>! empty( $attributes['tablet'] ), 'mobile'=>! empty( $attributes['mobile'] ) );
		return $this->renderer->render( Config::normalize( $raw, $base ), 'block' );
	}
}
