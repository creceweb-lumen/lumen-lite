<?php
/**
 * Education method · Voltio.
 *
 * @package CreceWebLumenLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_markup = <<<'HTML'
<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-section--method","align":"full"} -->
<div class="wp-block-group alignfull cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-section--method">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell cw-kit-method__layout"} -->
<div class="wp-block-group cw-kit-shell cw-kit-method__layout">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-method-art"} -->
<div class="wp-block-group cw-kit-method-art">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-method-art__circle"} -->
<div class="wp-block-group cw-kit-method-art__circle">
<!-- wp:paragraph {"className":"cw-kit-method-art__copy"} -->
<p class="cw-kit-method-art__copy">{{cw_lumen_text_1}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-method-art__square"} -->
<div class="wp-block-group cw-kit-method-art__square">
<!-- wp:paragraph {"className":"cw-kit-method-art__symbol"} -->
<p class="cw-kit-method-art__symbol">{{cw_lumen_text_2}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-method__copy"} -->
<div class="wp-block-group cw-kit-method__copy">
<!-- wp:paragraph {"className":"cw-kit-eyebrow"} -->
<p class="cw-kit-eyebrow">{{cw_lumen_text_3}}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"cw-kit-section-title cw-kit-section-title--left"} -->
<h2 class="wp-block-heading cw-kit-section-title cw-kit-section-title--left">{{cw_lumen_text_4}}</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"cw-kit-copy"} -->
<p class="cw-kit-copy">{{cw_lumen_text_5}}</p>
<!-- /wp:paragraph -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-method-steps"} -->
<div class="wp-block-group cw-kit-method-steps">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-method-step"} -->
<div class="wp-block-group cw-kit-method-step">
<!-- wp:paragraph {"className":"cw-kit-method-step__n"} -->
<p class="cw-kit-method-step__n">{{cw_lumen_text_6}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-method-step__label"} -->
<p class="cw-kit-method-step__label">{{cw_lumen_text_7}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-method-step"} -->
<div class="wp-block-group cw-kit-method-step">
<!-- wp:paragraph {"className":"cw-kit-method-step__n"} -->
<p class="cw-kit-method-step__n">{{cw_lumen_text_8}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-method-step__label"} -->
<p class="cw-kit-method-step__label">{{cw_lumen_text_9}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-method-step"} -->
<div class="wp-block-group cw-kit-method-step">
<!-- wp:paragraph {"className":"cw-kit-method-step__n"} -->
<p class="cw-kit-method-step__n">{{cw_lumen_text_10}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-method-step__label"} -->
<p class="cw-kit-method-step__label">{{cw_lumen_text_11}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:buttons {"className":"cw-kit-btns","layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-buttons cw-kit-btns">
<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">{{cw_lumen_text_12}}</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
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
		'{{cw_lumen_text_1}}' => esc_html__( 'That aha moment!', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_2}}' => esc_html__( '✦', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_3}}' => esc_html__( 'How we learn', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_4}}' => esc_html__( 'Less watching. More testing, breaking, and understanding.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_5}}' => esc_html__( 'Each module introduces a decision, a practice task, and a concrete piece of evidence that shows progress.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_6}}' => esc_html__( '01', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_7}}' => esc_html__( 'Understand', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_8}}' => esc_html__( '02', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_9}}' => esc_html__( 'Build', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_10}}' => esc_html__( '03', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_11}}' => esc_html__( 'Explain it', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_12}}' => esc_html__( 'Explore the method →', 'creceweb-lumen-lite' ),
	)
);
return array(
	'slug' => 'education-method-voltio',
	'family' => 'content',
	'title' => __( 'Education method · Voltio', 'creceweb-lumen-lite' ),
	'description' => __( 'Learning-method section with four principles, a filter list, and a closing action.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'method', 'creceweb-lumen-lite' ), __( 'process', 'creceweb-lumen-lite' ), __( 'education', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-method-voltio.webp',
	'content' => $cw_lumen_lite_markup,
);
