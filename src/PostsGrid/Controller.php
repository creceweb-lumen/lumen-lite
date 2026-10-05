<?php
/** Lumen Posts Grid coordinator. @package CreceWebLumenLite */
namespace CreceWeb\LumenLite\PostsGrid;
use CreceWeb\LumenLite\ContentCollection\Catalog;
use CreceWeb\LumenLite\PostsGrid\Elementor\Integration;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Controller {
	private Catalog $catalog; private Query $query; private Renderer $renderer; private Shortcode $shortcode; private Block $block; private Integration $elementor;
	public function __construct() { $this->catalog=new Catalog(); $this->query=new Query($this->catalog); $this->renderer=new Renderer($this->query); $this->shortcode=new Shortcode($this->renderer); $this->block=new Block($this->renderer); $this->elementor=new Integration(); }
	public function register(): void {
		add_action( 'init', array( $this, 'register_assets' ), 5 );
		add_action( 'init', array( $this->block, 'register' ), 10 );
		add_action( 'init', array( $this->shortcode, 'register' ), 10 );
		add_filter( 'block_categories_all', array( $this->block, 'register_category' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'detect_content_assets' ), 20 );
		$this->elementor->register();
	}
	public function register_assets(): void {
		wp_register_style( 'creceweb-lumen-lite-posts-grid', CRECEWEB_LUMEN_LITE_URL.'assets/css/posts-grid.css', array(), CRECEWEB_LUMEN_LITE_ASSET_VERSION );
		wp_register_style( 'creceweb-lumen-lite-posts-grid-editor', CRECEWEB_LUMEN_LITE_URL.'assets/css/posts-grid-editor.css', array('wp-edit-blocks'), CRECEWEB_LUMEN_LITE_ASSET_VERSION );
		wp_register_script( 'creceweb-lumen-lite-posts-grid-block', CRECEWEB_LUMEN_LITE_URL.'assets/js/posts-grid-block.js', array('wp-blocks','wp-element','wp-components','wp-block-editor','wp-i18n','wp-server-side-render','wp-hooks'), CRECEWEB_LUMEN_LITE_ASSET_VERSION, true );
		if ( function_exists( 'wp_set_script_translations' ) ) { wp_set_script_translations( 'creceweb-lumen-lite-posts-grid-block', 'creceweb-lumen-lite' ); }
	}
	public function detect_content_assets(): void {
		if ( is_admin() || ! function_exists( 'get_queried_object' ) ) { return; }
		$post = get_queried_object();
		$content = is_object( $post ) ? (string) ( $post->post_content ?? '' ) : '';
		if ( '' === $content ) { return; }
		$has_shortcode = function_exists( 'has_shortcode' ) && has_shortcode( $content, 'lumen_posts' );
		$has_block = function_exists( 'has_block' ) && has_block( 'creceweb-lumen/posts-grid', $content );
		if ( $has_shortcode || $has_block ) { wp_enqueue_style( 'creceweb-lumen-lite-posts-grid' ); }
	}
	public function catalog(): Catalog { return $this->catalog; }
	public function query(): Query { return $this->query; }
	public function renderer(): Renderer { return $this->renderer; }
	public function is_configured_for_request(): bool {
		if ( $this->renderer->was_used() ) { return true; }
		if ( is_admin() || ! function_exists( 'get_queried_object' ) ) { return false; }
		$post = get_queried_object(); $content = is_object( $post ) ? (string) ( $post->post_content ?? '' ) : '';
		if ( '' === $content ) { return false; }
		if ( function_exists( 'has_shortcode' ) && has_shortcode( $content, 'lumen_posts' ) ) { return true; }
		return function_exists( 'has_block' ) && has_block( 'creceweb-lumen/posts-grid', $content );
	}
	public function was_used(): bool { return $this->renderer->was_used(); }
	public function style_size_bytes(): int { $path = CRECEWEB_LUMEN_LITE_DIR . 'assets/css/posts-grid.css'; return is_file( $path ) ? (int) filesize( $path ) : 0; }
}
