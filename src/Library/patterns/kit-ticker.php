<?php
/**
 * Kit ticker.
 *
 * @package CreceWebLumenLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_markup = <<<'HTML'
<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-marquee","align":"full"} -->
<div class="wp-block-group alignfull cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-marquee">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-marquee__track"} -->
<div class="wp-block-group cw-kit-marquee__track">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-marquee__set"} -->
<div class="wp-block-group cw-kit-marquee__set">
<!-- wp:paragraph {"className":"cw-kit-marquee__item"} -->
<p class="cw-kit-marquee__item">{{cw_lumen_text_1}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-marquee__item"} -->
<p class="cw-kit-marquee__item">{{cw_lumen_text_2}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-marquee__item"} -->
<p class="cw-kit-marquee__item">{{cw_lumen_text_3}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-marquee__item"} -->
<p class="cw-kit-marquee__item">{{cw_lumen_text_1}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-marquee__item"} -->
<p class="cw-kit-marquee__item">{{cw_lumen_text_2}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-marquee__item"} -->
<p class="cw-kit-marquee__item">{{cw_lumen_text_3}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-marquee__item"} -->
<p class="cw-kit-marquee__item">{{cw_lumen_text_1}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-marquee__item"} -->
<p class="cw-kit-marquee__item">{{cw_lumen_text_2}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-marquee__item"} -->
<p class="cw-kit-marquee__item">{{cw_lumen_text_3}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-marquee__set"} -->
<div class="wp-block-group cw-kit-marquee__set">
<!-- wp:paragraph {"className":"cw-kit-marquee__item"} -->
<p class="cw-kit-marquee__item">{{cw_lumen_text_1}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-marquee__item"} -->
<p class="cw-kit-marquee__item">{{cw_lumen_text_2}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-marquee__item"} -->
<p class="cw-kit-marquee__item">{{cw_lumen_text_3}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-marquee__item"} -->
<p class="cw-kit-marquee__item">{{cw_lumen_text_1}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-marquee__item"} -->
<p class="cw-kit-marquee__item">{{cw_lumen_text_2}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-marquee__item"} -->
<p class="cw-kit-marquee__item">{{cw_lumen_text_3}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-marquee__item"} -->
<p class="cw-kit-marquee__item">{{cw_lumen_text_1}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-marquee__item"} -->
<p class="cw-kit-marquee__item">{{cw_lumen_text_2}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-marquee__item"} -->
<p class="cw-kit-marquee__item">{{cw_lumen_text_3}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
HTML;
$cw_lumen_lite_markup = strtr(
	$cw_lumen_lite_markup,
	array(
		'{{cw_lumen_text_1}}' => esc_html__( '✦  New lab · AI for real work', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_2}}' => esc_html__( '✦  Short lessons', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_3}}' => esc_html__( '✦  Projects that get published', 'creceweb-lumen-lite' ),
	)
);
return array(
	'slug' => 'kit-ticker',
	'family' => 'content',
	'title' => __( 'Kit ticker', 'creceweb-lumen-lite' ),
	'description' => __( 'Horizontal announcement ticker for launches, lessons, resources, and project updates.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'ticker', 'creceweb-lumen-lite' ), __( 'marquee', 'creceweb-lumen-lite' ), __( 'announcement', 'creceweb-lumen-lite' ) ),
	'preview' => 'kit-ticker.webp',
	'content' => $cw_lumen_lite_markup,
);
