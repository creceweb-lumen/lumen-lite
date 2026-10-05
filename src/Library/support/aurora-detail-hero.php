<?php
/**
 * Builds the Aurora-specific Detail hero while preserving the shared Education Detail body.
 *
 * @package CreceWebLumenLite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( string $content, string $anchor, string $code ): string {
	$sticker = '';
	$title   = '';
	$intro   = '';
	$meta    = array();

	if ( preg_match( '~<p class="cw-kit-sticker">(.*?)</p>~s', $content, $match ) ) {
		$sticker = $match[1];
	}
	if ( preg_match( '~<h1 class="wp-block-heading cw-kit-display cw-kit-detail-hero__title">(.*?)</h1>~s', $content, $match ) ) {
		$title = $match[1];
	}
	if ( preg_match( '~<p class="cw-kit-copy cw-kit-detail-hero__copy">(.*?)</p>~s', $content, $match ) ) {
		$intro = $match[1];
	}
	if ( preg_match_all( '~<p class="cw-kit-detail-meta__item">(.*?)</p>~s', $content, $matches ) ) {
		$meta = $matches[1];
	}

	$body_marker = '<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-kit-component cw-lumen-kit-profile--aurora cw-kit-section cw-kit-detail-section","align":"full"} -->';
	$body_pos    = strpos( $content, $body_marker );

	if ( '' === $sticker || '' === $title || '' === $intro || 2 > count( $meta ) || false === $body_pos ) {
		return $content;
	}

	$hero = '<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--aurora cw-kit-aurora-page-hero cw-kit-aurora-detail-hero","align":"full","anchor":"' . esc_attr( $anchor ) . '"} -->'
		. '<div id="' . esc_attr( $anchor ) . '" class="wp-block-group alignfull cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--aurora cw-kit-aurora-page-hero cw-kit-aurora-detail-hero">'
		. '<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell cw-kit-aurora-page-hero__grid"} --><div class="wp-block-group cw-kit-shell cw-kit-aurora-page-hero__grid">'
		. '<!-- wp:group {"layout":{"type":"default"}} --><div class="wp-block-group">'
		. '<!-- wp:paragraph {"className":"cw-kit-aurora-kicker"} --><p class="cw-kit-aurora-kicker">' . $sticker . '</p><!-- /wp:paragraph -->'
		. '<!-- wp:heading {"level":1,"className":"cw-kit-aurora-display cw-kit-aurora-page-title"} --><h1 class="wp-block-heading cw-kit-aurora-display cw-kit-aurora-page-title">' . $title . '</h1><!-- /wp:heading -->'
		. '</div><!-- /wp:group -->'
		. '<!-- wp:group {"layout":{"type":"default"}} --><div class="wp-block-group">'
		. '<!-- wp:paragraph {"className":"cw-kit-aurora-lede cw-kit-aurora-lede--small","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} --><p class="cw-kit-aurora-lede cw-kit-aurora-lede--small" style="margin-top:0;margin-bottom:0">' . $intro . '</p><!-- /wp:paragraph -->'
		. '<!-- wp:paragraph {"className":"cw-kit-aurora-route__meta","style":{"spacing":{"margin":{"top":"1rem","bottom":"0"}}}} --><p class="cw-kit-aurora-route__meta" style="margin-top:1rem;margin-bottom:0">' . $meta[0] . ' · ' . $meta[1] . '</p><!-- /wp:paragraph -->'
		. '</div><!-- /wp:group -->'
		. '<!-- wp:paragraph {"className":"cw-kit-aurora-page-code"} --><p class="cw-kit-aurora-page-code">' . esc_html( $code ) . '</p><!-- /wp:paragraph -->'
		. '</div><!-- /wp:group -->'
		. '</div><!-- /wp:group -->';

	return $hero . substr( $content, $body_pos );
};
