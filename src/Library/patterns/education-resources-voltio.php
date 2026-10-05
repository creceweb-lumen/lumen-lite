<?php
/**
 * Education resources · Voltio.
 *
 * @package CreceWebLumenLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_markup = <<<'HTML'
<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-section--resources","align":"full"} -->
<div class="wp-block-group alignfull cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-section--resources">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell"} -->
<div class="wp-block-group cw-kit-shell">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-section-head"} -->
<div class="wp-block-group cw-kit-section-head">
<!-- wp:paragraph {"className":"cw-kit-sticker cw-kit-sticker--green"} -->
<p class="cw-kit-sticker cw-kit-sticker--green">{{cw_lumen_text_1}}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"cw-kit-section-title"} -->
<h2 class="wp-block-heading cw-kit-section-title">{{cw_lumen_text_2}}</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"cw-kit-copy"} -->
<p class="cw-kit-copy">{{cw_lumen_text_3}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-resource-layout"} -->
<div class="wp-block-group cw-kit-resource-layout">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-feature-card"} -->
<div class="wp-block-group cw-kit-feature-card">
<!-- wp:paragraph {"className":"cw-kit-eyebrow"} -->
<p class="cw-kit-eyebrow">{{cw_lumen_text_4}}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"cw-kit-feature-card__title"} -->
<h3 class="wp-block-heading cw-kit-feature-card__title">{{cw_lumen_text_5}}</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"cw-kit-copy"} -->
<p class="cw-kit-copy">{{cw_lumen_text_6}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-feature-card__link"} -->
<p class="cw-kit-feature-card__link">{{cw_lumen_text_7}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-resource-side"} -->
<div class="wp-block-group cw-kit-resource-side">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-color-card cw-kit-tone-4"} -->
<div class="wp-block-group cw-kit-color-card cw-kit-tone-4">
<!-- wp:heading {"level":3,"className":"cw-kit-color-card__title"} -->
<h3 class="wp-block-heading cw-kit-color-card__title">{{cw_lumen_text_8}}</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"cw-kit-color-card__text"} -->
<p class="cw-kit-color-card__text">{{cw_lumen_text_9}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-color-card cw-kit-tone-5"} -->
<div class="wp-block-group cw-kit-color-card cw-kit-tone-5">
<!-- wp:heading {"level":3,"className":"cw-kit-color-card__title"} -->
<h3 class="wp-block-heading cw-kit-color-card__title">{{cw_lumen_text_10}}</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"cw-kit-color-card__text"} -->
<p class="cw-kit-color-card__text">{{cw_lumen_text_11}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
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
		'{{cw_lumen_text_1}}' => esc_html__( 'Want more?', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_2}}' => esc_html__( 'The library keeps going after class ends.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_3}}' => esc_html__( 'Guides, maps, and examples for the moment a real question appears in the middle of the work.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_4}}' => esc_html__( 'Featured guide', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_5}}' => esc_html__( 'A 30-day plan for using AI with judgement', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_6}}' => esc_html__( 'A practical route from curiosity to a workflow you can explain and verify.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_7}}' => esc_html__( 'Read now →', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_8}}' => esc_html__( 'A portfolio that shows judgement', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_9}}' => esc_html__( 'Explain decisions, constraints, and results without filling pages with screenshots.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_10}}' => esc_html__( 'Learn a tool without collecting tutorials', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_11}}' => esc_html__( 'Turn consumed content into practice, evidence, and one useful next step.', 'creceweb-lumen-lite' ),
	)
);
return array(
	'slug' => 'education-resources-voltio',
	'family' => 'content',
	'title' => __( 'Education resources · Voltio', 'creceweb-lumen-lite' ),
	'description' => __( 'Editorial resource layout with one large feature and two editable supporting cards.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'resources', 'creceweb-lumen-lite' ), __( 'guides', 'creceweb-lumen-lite' ), __( 'education', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-resources-voltio.webp',
	'content' => $cw_lumen_lite_markup,
);
