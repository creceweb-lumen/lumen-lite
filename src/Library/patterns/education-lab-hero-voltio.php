<?php
/**
 * Education Lab hero · Voltio.
 *
 * @package CreceWebLumenLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_markup = <<<'HTML'
<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-hero","align":"full"} -->
<div class="wp-block-group alignfull cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-hero">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell cw-kit-hero__layout"} -->
<div class="wp-block-group cw-kit-shell cw-kit-hero__layout">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-hero__copy"} -->
<div class="wp-block-group cw-kit-hero__copy">
<!-- wp:paragraph {"className":"cw-kit-eyebrow"} -->
<p class="cw-kit-eyebrow">{{cw_lumen_text_1}}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"cw-kit-display"} -->
<h1 class="wp-block-heading cw-kit-display">{{cw_lumen_text_2}}</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"cw-kit-display cw-kit-display--accent"} -->
<p class="cw-kit-display cw-kit-display--accent">{{cw_lumen_text_3}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-copy cw-kit-hero__lead"} -->
<p class="cw-kit-copy cw-kit-hero__lead">{{cw_lumen_text_4}}</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"className":"cw-kit-btns","layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-buttons cw-kit-btns">
<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#courses">{{cw_lumen_text_5}}</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#capsules">{{cw_lumen_text_6}}</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
<!-- wp:paragraph {"className":"cw-kit-hero__note"} -->
<p class="cw-kit-hero__note">{{cw_lumen_text_7}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-routes"} -->
<div class="wp-block-group cw-kit-routes">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-route cw-kit-route--1"} -->
<div class="wp-block-group cw-kit-route cw-kit-route--1">
<!-- wp:paragraph {"className":"cw-kit-route__label"} -->
<p class="cw-kit-route__label">{{cw_lumen_text_8}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-route__icon"} -->
<p class="cw-kit-route__icon">{{cw_lumen_text_9}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-route__title"} -->
<p class="cw-kit-route__title">{{cw_lumen_text_10}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-route cw-kit-route--2"} -->
<div class="wp-block-group cw-kit-route cw-kit-route--2">
<!-- wp:paragraph {"className":"cw-kit-route__label"} -->
<p class="cw-kit-route__label">{{cw_lumen_text_11}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-route__icon"} -->
<p class="cw-kit-route__icon">{{cw_lumen_text_12}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-route__title"} -->
<p class="cw-kit-route__title">{{cw_lumen_text_13}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-route cw-kit-route--3"} -->
<div class="wp-block-group cw-kit-route cw-kit-route--3">
<!-- wp:paragraph {"className":"cw-kit-route__label"} -->
<p class="cw-kit-route__label">{{cw_lumen_text_14}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-route__icon"} -->
<p class="cw-kit-route__icon">{{cw_lumen_text_15}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-route__title"} -->
<p class="cw-kit-route__title">{{cw_lumen_text_16}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
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
		'{{cw_lumen_text_1}}' => esc_html__( 'Microlearning to build better', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_2}}' => esc_html__( 'Learn something.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_3}}' => esc_html__( 'Make it happen.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_4}}' => esc_html__( 'Short courses, guided practice, and real projects for mastering AI, design, development, and automation without living inside tutorials.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_5}}' => esc_html__( 'Choose your first route →', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_6}}' => esc_html__( 'Try a quick lesson ✦', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_7}}' => esc_html__( 'Choose a route. Move at your pace. Build something real.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_8}}' => esc_html__( 'Route 01', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_9}}' => esc_html__( '✦', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_10}}' => esc_html__( 'AI with judgement', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_11}}' => esc_html__( 'Route 02', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_12}}' => esc_html__( '✎', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_13}}' => esc_html__( 'Design that works', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_14}}' => esc_html__( 'Route 03', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_15}}' => esc_html__( '↗', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_16}}' => esc_html__( 'Build and publish', 'creceweb-lumen-lite' ),
	)
);
return array(
	'slug' => 'education-lab-hero-voltio',
	'family' => 'heroes',
	'title' => __( 'Education Lab hero · Voltio', 'creceweb-lumen-lite' ),
	'description' => __( 'High-energy split hero with bold message, route cards, and two calls to action.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'education', 'creceweb-lumen-lite' ), __( 'hero', 'creceweb-lumen-lite' ), __( 'courses', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-lab-hero-voltio.webp',
	'content' => $cw_lumen_lite_markup,
);
