<?php
/** Elementor Free Lumen Posts widget. @package CreceWebLumenLite */
namespace CreceWeb\LumenLite\PostsGrid\Elementor;
use CreceWeb\LumenLite\Data\Settings;
use CreceWeb\LumenLite\Plugin;
use CreceWeb\LumenLite\PostsGrid\Config;
use CreceWeb\LumenLite\PostsGrid\Renderer;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Widget extends \Elementor\Widget_Base {
	public function get_name(): string { return 'lumen_posts'; }
	public function get_title(): string { return __( 'Lumen Posts', 'creceweb-lumen-lite' ); }
	public function get_icon(): string { return 'eicon-posts-grid'; }
	public function get_categories(): array { return array( 'creceweb-lumen' ); }
	public function get_style_depends(): array { return array( 'creceweb-lumen-lite-posts-grid' ); }
	protected function register_controls(): void {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'creceweb-lumen-lite' ) ) );
		$this->add_control( 'use_global_defaults', array( 'label'=>__( 'Use global defaults', 'creceweb-lumen-lite' ), 'type'=>\Elementor\Controls_Manager::SWITCHER, 'default'=>'yes' ) );
		$this->add_control( 'title', array( 'label'=>__( 'Section title', 'creceweb-lumen-lite' ), 'type'=>\Elementor\Controls_Manager::TEXT, 'condition'=>array('use_global_defaults'=>'') ) );
		$this->add_control( 'post_type', array( 'label'=>__( 'Post type', 'creceweb-lumen-lite' ), 'type'=>\Elementor\Controls_Manager::TEXT, 'default'=>'post', 'condition'=>array('use_global_defaults'=>'') ) );
		$this->add_control( 'source', array( 'label'=>__( 'Source', 'creceweb-lumen-lite' ), 'type'=>\Elementor\Controls_Manager::SELECT, 'options'=>array('latest'=>__( 'Latest', 'creceweb-lumen-lite' ),'popular'=>__( 'Popular', 'creceweb-lumen-lite' ),'manual'=>__( 'Manual', 'creceweb-lumen-lite' )), 'default'=>'latest', 'condition'=>array('use_global_defaults'=>'') ) );
		$this->add_control( 'orderby', array( 'label'=>__( 'Order by', 'creceweb-lumen-lite' ), 'type'=>\Elementor\Controls_Manager::SELECT, 'options'=>array('date'=>__( 'Date', 'creceweb-lumen-lite' ),'title'=>__( 'Title', 'creceweb-lumen-lite' ),'comment_count'=>__( 'Comment count', 'creceweb-lumen-lite' )), 'default'=>'date', 'condition'=>array('use_global_defaults'=>'','source!'=>'manual') ) );
		$this->add_control( 'order', array( 'label'=>__( 'Order', 'creceweb-lumen-lite' ), 'type'=>\Elementor\Controls_Manager::SELECT, 'options'=>array('DESC'=>'DESC','ASC'=>'ASC'), 'default'=>'DESC', 'condition'=>array('use_global_defaults'=>'','source!'=>'manual') ) );
		$item_labels = array( 'desktop' => __( 'Desktop items', 'creceweb-lumen-lite' ), 'tablet' => __( 'Tablet items', 'creceweb-lumen-lite' ), 'mobile' => __( 'Mobile items', 'creceweb-lumen-lite' ) ); foreach ( array('desktop'=>6,'tablet'=>4,'mobile'=>3) as $device=>$default ) { $this->add_control( 'items_'.$device, array( 'label'=>$item_labels[$device], 'type'=>\Elementor\Controls_Manager::NUMBER, 'min'=>1, 'default'=>$default, 'condition'=>array('use_global_defaults'=>'') ) ); }
		$this->add_control( 'manual_ids', array( 'label'=>__( 'Manual IDs', 'creceweb-lumen-lite' ), 'type'=>\Elementor\Controls_Manager::TEXT, 'condition'=>array('use_global_defaults'=>'','source'=>'manual') ) );
		$this->add_control( 'taxonomy', array( 'label'=>__( 'Taxonomy slug', 'creceweb-lumen-lite' ), 'type'=>\Elementor\Controls_Manager::TEXT, 'condition'=>array('use_global_defaults'=>'') ) );
		$this->add_control( 'term_ids', array( 'label'=>__( 'Term IDs', 'creceweb-lumen-lite' ), 'type'=>\Elementor\Controls_Manager::TEXT, 'condition'=>array('use_global_defaults'=>'') ) );
		$this->end_controls_section();
		$this->start_controls_section( 'appearance', array( 'label'=>__( 'Layout and appearance', 'creceweb-lumen-lite' ) ) );
		$this->add_control( 'layout', array( 'label'=>__( 'Layout', 'creceweb-lumen-lite' ), 'type'=>\Elementor\Controls_Manager::SELECT, 'options'=>array('grid'=>__( 'Grid', 'creceweb-lumen-lite' ),'list'=>__( 'List', 'creceweb-lumen-lite' )), 'default'=>'grid','condition'=>array('use_global_defaults'=>'') ) );
		$column_labels = array( 'desktop' => __( 'Desktop columns', 'creceweb-lumen-lite' ), 'tablet' => __( 'Tablet columns', 'creceweb-lumen-lite' ), 'mobile' => __( 'Mobile columns', 'creceweb-lumen-lite' ) ); foreach ( array('desktop'=>3,'tablet'=>2,'mobile'=>1) as $device=>$default ) { $this->add_control( 'columns_'.$device, array( 'label'=>$column_labels[$device], 'type'=>\Elementor\Controls_Manager::NUMBER, 'min'=>1, 'default'=>$default,'condition'=>array('use_global_defaults'=>'') ) ); }
		$this->add_control( 'style', array( 'label'=>__( 'Card style', 'creceweb-lumen-lite' ), 'type'=>\Elementor\Controls_Manager::SELECT, 'options'=>array('default'=>__( 'Theme', 'creceweb-lumen-lite' ),'elevated'=>__( 'Elevated', 'creceweb-lumen-lite' ),'minimal'=>__( 'Minimal', 'creceweb-lumen-lite' )), 'default'=>'default','condition'=>array('use_global_defaults'=>'') ) );
		$this->add_control( 'image_ratio_width', array( 'label'=>__( 'Image ratio width', 'creceweb-lumen-lite' ), 'type'=>\Elementor\Controls_Manager::NUMBER, 'min'=>0.1, 'step'=>0.1, 'default'=>16, 'condition'=>array('use_global_defaults'=>'') ) );
		$this->add_control( 'image_ratio_height', array( 'label'=>__( 'Image ratio height', 'creceweb-lumen-lite' ), 'type'=>\Elementor\Controls_Manager::NUMBER, 'min'=>0.1, 'step'=>0.1, 'default'=>9, 'condition'=>array('use_global_defaults'=>'') ) );
		$this->add_control( 'gap', array( 'label'=>__( 'Gap (px)', 'creceweb-lumen-lite' ), 'type'=>\Elementor\Controls_Manager::NUMBER, 'min'=>0, 'step'=>0.1, 'condition'=>array('use_global_defaults'=>'') ) );
		foreach ( array('show_image'=>__( 'Featured image', 'creceweb-lumen-lite' ),'show_taxonomy'=>__( 'Taxonomy', 'creceweb-lumen-lite' ),'show_date'=>__( 'Date', 'creceweb-lumen-lite' ),'show_excerpt'=>__( 'Excerpt', 'creceweb-lumen-lite' ),'show_read_more'=>__( 'Read more', 'creceweb-lumen-lite' )) as $key=>$label ) { $this->add_control( $key, array('label'=>$label,'type'=>\Elementor\Controls_Manager::SWITCHER,'default'=>'yes','condition'=>array('use_global_defaults'=>'')) ); }
		$this->add_control( 'read_more_text', array( 'label'=>__( 'Read more text', 'creceweb-lumen-lite' ), 'type'=>\Elementor\Controls_Manager::TEXT, 'default'=>__( 'Read more', 'creceweb-lumen-lite' ), 'condition'=>array('use_global_defaults'=>'','show_read_more'=>'yes') ) );
		$this->end_controls_section();
	}
	protected function render(): void {
		$plugin = Plugin::instance();
		$renderer = $plugin ? $plugin->posts_grid()->renderer() : null;
		if ( ! $renderer instanceof Renderer ) { return; }
		$s = $this->get_settings_for_display(); $all=Settings::get(); $base=Config::normalize(isset($all['posts_grid'])&&is_array($all['posts_grid'])?$all['posts_grid']:array());
		$elementor_devices = array( 'desktop' => true, 'tablet' => true, 'mobile' => true );
		if ( ! empty( $s['use_global_defaults'] ) ) { $base['devices'] = $elementor_devices; echo $renderer->render( $base, 'elementor' ); return; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes all output.
		$raw=$s; $raw['devices']=$elementor_devices;
		foreach(array('show_image','show_taxonomy','show_date','show_excerpt','show_read_more') as $key){$raw[$key]=!empty($s[$key]);}
		echo $renderer->render( Config::normalize( $raw, $base ), 'elementor' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes all output.
	}
}
