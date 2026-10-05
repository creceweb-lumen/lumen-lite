<?php
/** Optional Elementor integration loader. @package CreceWebLumenLite */
namespace CreceWeb\LumenLite\PostsGrid\Elementor;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Integration {
	public function register(): void {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );
	}
	public function register_category( object $elements_manager ): void {
		if ( method_exists( $elements_manager, 'get_categories' ) ) {
			$categories = $elements_manager->get_categories();
			if ( is_array( $categories ) && isset( $categories['creceweb-lumen'] ) ) { return; }
		}
		if ( method_exists( $elements_manager, 'add_category' ) ) {
			$elements_manager->add_category( 'creceweb-lumen', array( 'title' => __( 'Lumen', 'creceweb-lumen-lite' ), 'icon' => 'eicon-apps' ) );
		}
	}
	public function register_widget( object $widgets_manager ): void {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) { return; }
		$path = CRECEWEB_LUMEN_LITE_DIR . 'src/PostsGrid/Elementor/Widget.php';
		if ( is_readable( $path ) ) { require_once $path; }
		if ( class_exists( __NAMESPACE__ . '\Widget' ) && method_exists( $widgets_manager, 'register' ) ) { $widgets_manager->register( new Widget() ); }
	}
}
