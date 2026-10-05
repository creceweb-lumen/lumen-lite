<?php
/** Posts Grid shortcode adapter. @package CreceWebLumenLite */
namespace CreceWeb\LumenLite\PostsGrid;
use CreceWeb\LumenLite\Data\Settings;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Shortcode {
	public function __construct( private Renderer $renderer ) {}
	public function register(): void { add_shortcode( 'lumen_posts', array( $this, 'render' ) ); }
	/** @param array<string,mixed>|string $attributes */
	public function render( array|string $attributes = array() ): string {
		$stored = Settings::get();
		$base = Config::normalize( isset( $stored['posts_grid'] ) && is_array( $stored['posts_grid'] ) ? $stored['posts_grid'] : array() );
		$raw = is_array( $attributes ) ? $attributes : array();
		$allowed = array( 'title','post_type','source','items_desktop','items_tablet','items_mobile','orderby','order','taxonomy','terms','manual_ids','show_image','show_taxonomy','show_date','show_excerpt','show_read_more','read_more_text','layout','columns_desktop','columns_tablet','columns_mobile','image_ratio_width','image_ratio_height','gap','style','desktop','tablet','mobile' );
		$clean = array();
		foreach ( $allowed as $key ) { if ( array_key_exists( $key, $raw ) ) { $clean[ $key ] = $raw[ $key ]; } }
		if ( isset( $clean['terms'] ) ) { $clean['term_ids'] = $clean['terms']; unset( $clean['terms'] ); }
		$bools = array( 'show_image','show_taxonomy','show_date','show_excerpt','show_read_more' );
		foreach ( $bools as $key ) { if ( isset( $clean[ $key ] ) ) { $clean[ $key ] = $this->bool_value( $clean[ $key ] ); } }
		$devices = $base['devices'];
		foreach ( array( 'desktop','tablet','mobile' ) as $device ) { if ( isset( $clean[ $device ] ) ) { $devices[ $device ] = $this->bool_value( $clean[ $device ] ); unset( $clean[ $device ] ); } }
		$clean['devices'] = $devices;
		return $this->renderer->render( Config::normalize( $clean, $base ), 'shortcode' );
	}
	private function bool_value( mixed $value ): bool { return in_array( strtolower( trim( (string) $value ) ), array( '1','true','yes','on' ), true ); }
}
