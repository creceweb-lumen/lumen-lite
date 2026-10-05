<?php
/**
 * Education resource catalog · Voltio.
 *
 * @package CreceWebLumenLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$cw_lumen_lite_resources = array(
	array(
		'tone'        => '1',
		'index'       => '01',
		'category'    => __( 'Applied AI', 'creceweb-lumen-lite' ),
		'duration'    => __( '8 min', 'creceweb-lumen-lite' ),
		'title'       => __( 'A 30-day plan for using AI with judgement', 'creceweb-lumen-lite' ),
		'description' => __( 'A practical guide to move from curiosity to a workflow you can verify.', 'creceweb-lumen-lite' ),
	),
	array(
		'tone'        => '2',
		'index'       => '02',
		'category'    => __( 'Career', 'creceweb-lumen-lite' ),
		'duration'    => __( '7 min', 'creceweb-lumen-lite' ),
		'title'       => __( 'A portfolio that shows judgement', 'creceweb-lumen-lite' ),
		'description' => __( 'Show decisions, constraints, and results without filling pages with screenshots.', 'creceweb-lumen-lite' ),
	),
	array(
		'tone'        => '3',
		'index'       => '03',
		'category'    => __( 'Method', 'creceweb-lumen-lite' ),
		'duration'    => __( '6 min', 'creceweb-lumen-lite' ),
		'title'       => __( 'Learn one tool without collecting pending tutorials', 'creceweb-lumen-lite' ),
		'description' => __( 'Turn consumed content into practice, evidence, and one useful next step.', 'creceweb-lumen-lite' ),
	),
	array(
		'tone'        => '4',
		'index'       => '04',
		'category'    => __( 'Automation', 'creceweb-lumen-lite' ),
		'duration'    => __( '7 min', 'creceweb-lumen-lite' ),
		'title'       => __( 'What to automate first without creating chaos faster', 'creceweb-lumen-lite' ),
		'description' => __( 'Use simple criteria to choose tasks that are stable, reversible, and observable.', 'creceweb-lumen-lite' ),
	),
);

$cw_lumen_lite_card_template = <<<'HTML'
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-resource-card cw-kit-tone-%1$s"} -->
<div class="wp-block-group cw-kit-resource-card cw-kit-tone-%1$s">
<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"},"className":"cw-kit-resource-card__head"} -->
<div class="wp-block-group cw-kit-resource-card__head">
<!-- wp:paragraph {"className":"cw-kit-resource-card__icon has-text-color"} -->
<p class="cw-kit-resource-card__icon has-text-color">&#8599;</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-resource-card__index"} -->
<p class="cw-kit-resource-card__index">%2$s</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:paragraph {"className":"cw-kit-resource-card__meta"} -->
<p class="cw-kit-resource-card__meta">%3$s <span aria-hidden="true">—</span> %4$s</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"cw-kit-resource-card__title"} -->
<h3 class="wp-block-heading cw-kit-resource-card__title">%5$s</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"cw-kit-resource-card__copy"} -->
<p class="cw-kit-resource-card__copy">%6$s</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-resource-card__cta"} -->
<p class="cw-kit-resource-card__cta"><a href="#">%7$s</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
HTML;

$cw_lumen_lite_cards = '';
foreach ( $cw_lumen_lite_resources as $cw_lumen_lite_resource ) {
	$cw_lumen_lite_cards .= sprintf(
		$cw_lumen_lite_card_template,
		esc_attr( $cw_lumen_lite_resource['tone'] ),
		esc_html( $cw_lumen_lite_resource['index'] ),
		esc_html( $cw_lumen_lite_resource['category'] ),
		esc_html( $cw_lumen_lite_resource['duration'] ),
		esc_html( $cw_lumen_lite_resource['title'] ),
		esc_html( $cw_lumen_lite_resource['description'] ),
		esc_html__( 'Read guide →', 'creceweb-lumen-lite' )
	);
}

$cw_lumen_lite_markup = <<<'HTML'
<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-hero cw-kit-catalog-hero","align":"full","anchor":"resources"} -->
<div id="resources" class="wp-block-group alignfull cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-hero cw-kit-catalog-hero">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell"} -->
<div class="wp-block-group cw-kit-shell">
<!-- wp:paragraph {"className":"cw-kit-sticker"} -->
<p class="cw-kit-sticker">{{cw_lumen_text_1}}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"cw-kit-display cw-kit-catalog-hero__title"} -->
<h1 class="wp-block-heading cw-kit-display cw-kit-catalog-hero__title">{{cw_lumen_text_2}}</h1>
<!-- /wp:heading -->
<!-- wp:heading {"level":2,"className":"cw-kit-display cw-kit-display--accent cw-kit-catalog-hero__accent-title"} -->
<h2 class="wp-block-heading cw-kit-display cw-kit-display--accent cw-kit-catalog-hero__accent-title">{{cw_lumen_text_3}}</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"cw-kit-copy"} -->
<p class="cw-kit-copy">{{cw_lumen_text_4}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-catalog-section","align":"full"} -->
<div class="wp-block-group alignfull cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-catalog-section">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell"} -->
<div class="wp-block-group cw-kit-shell">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-catalog-grid"} -->
<div class="wp-block-group cw-kit-catalog-grid">{{cw_lumen_cards}}</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
HTML;

$cw_lumen_lite_markup = strtr(
	$cw_lumen_lite_markup,
	array(
		'{{cw_lumen_text_1}}' => esc_html__( 'Resources', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_2}}' => esc_html__( 'Read less by inertia.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_3}}' => esc_html__( 'Find one useful idea.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_4}}' => esc_html__( 'Guides to think better about a tool, organize learning, and make decisions with more context.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_cards}}'  => $cw_lumen_lite_cards,
	)
);

return array(
	'slug' => 'education-resource-catalog-voltio',
	'family' => 'content',
	'title' => __( 'Education resource catalog · Voltio', 'creceweb-lumen-lite' ),
	'description' => __( 'Four large editable guide cards with category, reading time, index, summary, and call to action.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'resources', 'creceweb-lumen-lite' ), __( 'guides', 'creceweb-lumen-lite' ), __( 'education', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-resource-catalog-voltio.webp',
	'content' => $cw_lumen_lite_markup,
);
