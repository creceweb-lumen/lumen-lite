<?php
/**
 * Education final CTA · Voltio.
 *
 * @package CreceWebLumenLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_markup = <<<'HTML'
<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-final","align":"full"} -->
<div class="wp-block-group alignfull cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-final">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell"} -->
<div class="wp-block-group cw-kit-shell">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-final__box"} -->
<div class="wp-block-group cw-kit-final__box">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-final__copy-wrap"} -->
<div class="wp-block-group cw-kit-final__copy-wrap">
<!-- wp:paragraph {"className":"cw-kit-eyebrow cw-kit-eyebrow--on-color"} -->
<p class="cw-kit-eyebrow cw-kit-eyebrow--on-color">{{cw_lumen_text_1}}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"cw-kit-final__title"} -->
<h2 class="wp-block-heading cw-kit-final__title">{{cw_lumen_text_2}}</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"cw-kit-final__copy"} -->
<p class="cw-kit-final__copy">{{cw_lumen_text_3}}</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"className":"cw-kit-btns","layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-buttons cw-kit-btns">
<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#contact">{{cw_lumen_text_4}}</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
<!-- wp:paragraph {"className":"cw-kit-final__bolt"} -->
<p class="cw-kit-final__bolt">{{cw_lumen_text_5}}</p>
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
		'{{cw_lumen_text_1}}' => esc_html__( 'New material every week', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_2}}' => esc_html__( 'A library that pushes you to produce, not postpone.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_3}}' => esc_html__( 'Receive new routes, quick lessons, and labs in an experience designed to support your own pace.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_4}}' => esc_html__( 'I want in →', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_5}}' => esc_html__( '⚡', 'creceweb-lumen-lite' ),
	)
);
return array(
	'slug' => 'education-final-cta-voltio',
	'family' => 'conversion',
	'title' => __( 'Education final CTA · Voltio', 'creceweb-lumen-lite' ),
	'description' => __( 'Large high-contrast closing call to action with message and button.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'cta', 'creceweb-lumen-lite' ), __( 'education', 'creceweb-lumen-lite' ), __( 'library', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-final-cta-voltio.webp',
	'content' => $cw_lumen_lite_markup,
);
