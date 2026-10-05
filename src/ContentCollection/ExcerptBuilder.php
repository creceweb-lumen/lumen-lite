<?php
/** Shared safe card excerpt builder. @package CreceWebLumenLite */
namespace CreceWeb\LumenLite\ContentCollection;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class ExcerptBuilder {
	public static function for_post( int $post_id ): string {
		$post = get_post( $post_id );
		if ( ! is_object( $post ) ) { return ''; }
		$manual = trim( wp_strip_all_tags( (string) ( $post->post_excerpt ?? '' ), true ) );
		if ( '' !== $manual ) { return $manual; }
		$content = (string) ( $post->post_content ?? '' );
		if ( function_exists( 'strip_shortcodes' ) ) { $content = strip_shortcodes( $content ); }
		$content = trim( wp_strip_all_tags( $content, true ) );
		if ( '' === $content ) { return ''; }
		$length = max( 1, (int) apply_filters( 'excerpt_length', 55 ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- excerpt_length is the WordPress core excerpt-length filter.
		return wp_trim_words( $content, $length, '…' );
	}
}
