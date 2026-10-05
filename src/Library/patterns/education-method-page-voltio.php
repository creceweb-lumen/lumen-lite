<?php
/**
 * Education method page · Voltio.
 *
 * @package CreceWebLumenLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$cw_lumen_lite_principles = array(
	array(
		'tone'        => '1',
		'number'      => '01',
		'label'       => __( 'Context', 'creceweb-lumen-lite' ),
		'title'       => __( 'Understand first so it is useful.', 'creceweb-lumen-lite' ),
		'description' => __( 'A tool without a problem is only a collection of buttons. Start with one real situation, one constraint, and one decision.', 'creceweb-lumen-lite' ),
		'symbol'      => '◎',
	),
	array(
		'tone'        => '2',
		'number'      => '02',
		'label'       => __( 'Practice', 'creceweb-lumen-lite' ),
		'title'       => __( 'Hands start before theory gets cold.', 'creceweb-lumen-lite' ),
		'description' => __( 'Test each idea right away. Not to repeat a recipe, but to discover what changes when the conditions change.', 'creceweb-lumen-lite' ),
		'symbol'      => '✦',
	),
	array(
		'tone'        => '3',
		'number'      => '03',
		'label'       => __( 'Project', 'creceweb-lumen-lite' ),
		'title'       => __( 'Build something that survives the course.', 'creceweb-lumen-lite' ),
		'description' => __( 'The result should be usable, demonstrable, or worth continuing. If it ends in a forgotten folder, it is not finished.', 'creceweb-lumen-lite' ),
		'symbol'      => '↗',
	),
	array(
		'tone'        => '5',
		'number'      => '04',
		'label'       => __( 'Criterion', 'creceweb-lumen-lite' ),
		'title'       => __( 'Explaining a decision is also a skill.', 'creceweb-lumen-lite' ),
		'description' => __( 'Close by documenting choices, limits, and next steps. Knowing why something worked gives you a better starting point next time.', 'creceweb-lumen-lite' ),
		'symbol'      => '▤',
	),
);

$cw_lumen_lite_principle_template = <<<'HTML'
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-catalog-card cw-kit-method-principle cw-kit-tone-%1$s"} -->
<div class="wp-block-group cw-kit-catalog-card cw-kit-method-principle cw-kit-tone-%1$s">
<!-- wp:paragraph {"className":"cw-kit-catalog-card__category cw-kit-method-principle__meta"} -->
<p class="cw-kit-catalog-card__category cw-kit-method-principle__meta">%2$s · %3$s</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"cw-kit-catalog-card__title cw-kit-method-principle__title"} -->
<h3 class="wp-block-heading cw-kit-catalog-card__title cw-kit-method-principle__title">%4$s</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"cw-kit-catalog-card__copy cw-kit-method-principle__copy"} -->
<p class="cw-kit-catalog-card__copy cw-kit-method-principle__copy">%5$s</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-method-principle__symbol has-text-color"} -->
<p class="cw-kit-method-principle__symbol has-text-color" aria-hidden="true">%6$s</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
HTML;

$cw_lumen_lite_principle_cards = '';
foreach ( $cw_lumen_lite_principles as $cw_lumen_lite_principle ) {
	$cw_lumen_lite_principle_cards .= sprintf(
		$cw_lumen_lite_principle_template,
		esc_attr( $cw_lumen_lite_principle['tone'] ),
		esc_html( $cw_lumen_lite_principle['number'] ),
		esc_html( $cw_lumen_lite_principle['label'] ),
		esc_html( $cw_lumen_lite_principle['title'] ),
		esc_html( $cw_lumen_lite_principle['description'] ),
		esc_html( $cw_lumen_lite_principle['symbol'] )
	);
}

$cw_lumen_lite_filters = array(
	array( '01', __( 'No filler', 'creceweb-lumen-lite' ), __( 'A lesson exists because it changes a decision, not because hours need to be filled.', 'creceweb-lumen-lite' ) ),
	array( '02', __( 'No tool worship', 'creceweb-lumen-lite' ), __( 'Mental models are taught so they survive when the software changes.', 'creceweb-lumen-lite' ) ),
	array( '03', __( 'No magic results', 'creceweb-lumen-lite' ), __( 'Limits, errors, and awkward parts that make something trustworthy stay visible.', 'creceweb-lumen-lite' ) ),
	array( '04', __( 'No toy practice', 'creceweb-lumen-lite' ), __( 'Projects keep context, constraints, and a concrete recipient.', 'creceweb-lumen-lite' ) ),
);

$cw_lumen_lite_filter_template = <<<'HTML'
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-checklist__item"} -->
<div class="wp-block-group cw-kit-checklist__item">
<!-- wp:paragraph {"className":"cw-kit-checklist__number has-text-color"} -->
<p class="cw-kit-checklist__number has-text-color">%1$s</p>
<!-- /wp:paragraph -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-checklist__body"} -->
<div class="wp-block-group cw-kit-checklist__body">
<!-- wp:heading {"level":3,"className":"cw-kit-checklist__title"} -->
<h3 class="wp-block-heading cw-kit-checklist__title">%2$s</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"cw-kit-checklist__copy"} -->
<p class="cw-kit-checklist__copy">%3$s</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
HTML;

$cw_lumen_lite_filter_items = '';
foreach ( $cw_lumen_lite_filters as $cw_lumen_lite_filter ) {
	$cw_lumen_lite_filter_items .= sprintf(
		$cw_lumen_lite_filter_template,
		esc_html( $cw_lumen_lite_filter[0] ),
		esc_html( $cw_lumen_lite_filter[1] ),
		esc_html( $cw_lumen_lite_filter[2] )
	);
}

$cw_lumen_lite_markup = <<<'HTML'
<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-hero cw-kit-catalog-hero","align":"full","anchor":"method"} -->
<div id="method" class="wp-block-group alignfull cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-hero cw-kit-catalog-hero">
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

<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section","align":"full"} -->
<div class="wp-block-group alignfull cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell"} -->
<div class="wp-block-group cw-kit-shell">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-catalog-grid cw-kit-method-principles"} -->
<div class="wp-block-group cw-kit-catalog-grid cw-kit-method-principles">{{cw_lumen_principles}}</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-section--panel","align":"full"} -->
<div class="wp-block-group alignfull cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-section--panel">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell cw-kit-method-filter"} -->
<div class="wp-block-group cw-kit-shell cw-kit-method-filter">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-method-filter__intro"} -->
<div class="wp-block-group cw-kit-method-filter__intro">
<!-- wp:paragraph {"className":"cw-kit-sticker cw-kit-sticker--secondary"} -->
<p class="cw-kit-sticker cw-kit-sticker--secondary">{{cw_lumen_text_5}}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"className":"cw-kit-section-title cw-kit-section-title--left"} -->
<h2 class="wp-block-heading cw-kit-section-title cw-kit-section-title--left">{{cw_lumen_text_6}}</h2>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-checklist"} -->
<div class="wp-block-group cw-kit-checklist">{{cw_lumen_filters}}</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-method-statement-section","align":"full"} -->
<div class="wp-block-group alignfull cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-method-statement-section">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell"} -->
<div class="wp-block-group cw-kit-shell">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-statement"} -->
<div class="wp-block-group cw-kit-statement">
<!-- wp:paragraph {"className":"cw-kit-statement__icon has-text-color"} -->
<p class="cw-kit-statement__icon has-text-color" aria-hidden="true">⚡</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"className":"cw-kit-statement__title"} -->
<h2 class="wp-block-heading cw-kit-statement__title">{{cw_lumen_text_7}}</h2>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-method-action-section","align":"full"} -->
<div class="wp-block-group alignfull cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-method-action-section">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell"} -->
<div class="wp-block-group cw-kit-shell">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-final__box cw-kit-action-panel"} -->
<div class="wp-block-group cw-kit-final__box cw-kit-action-panel">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-action-panel__copy"} -->
<div class="wp-block-group cw-kit-action-panel__copy">
<!-- wp:paragraph {"className":"cw-kit-action-panel__eyebrow"} -->
<p class="cw-kit-action-panel__eyebrow">{{cw_lumen_text_8}}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"className":"cw-kit-final__title cw-kit-action-panel__title"} -->
<h2 class="wp-block-heading cw-kit-final__title cw-kit-action-panel__title">{{cw_lumen_text_9}}</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"cw-kit-final__copy cw-kit-action-panel__text"} -->
<p class="cw-kit-final__copy cw-kit-action-panel__text">{{cw_lumen_text_10}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:buttons {"className":"cw-kit-action-panel__buttons","layout":{"type":"flex","justifyContent":"right"}} -->
<div class="wp-block-buttons cw-kit-action-panel__buttons">
<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">{{cw_lumen_text_11}}</a></div>
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
		'{{cw_lumen_text_1}}'    => esc_html__( 'Method', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_2}}'    => esc_html__( 'Learning is not watching.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_3}}'    => esc_html__( 'It is changing what you can do.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_4}}'    => esc_html__( 'Each experience is built around an observable transformation: one decision you now understand, one tool you can use, or one project you could not build before.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_5}}'    => esc_html__( 'Our filter', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_6}}'    => esc_html__( 'What stays out also defines the experience.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_7}}'    => esc_html__( 'The goal is not to finish a class. It is to start working differently.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_8}}'    => esc_html__( 'Next step', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_9}}'    => esc_html__( 'Start with one skill you already need.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_10}}'   => esc_html__( 'Motivation lasts longer when learning solves something happening today.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_11}}'   => esc_html__( 'I want to start →', 'creceweb-lumen-lite' ),
		'{{cw_lumen_principles}}' => $cw_lumen_lite_principle_cards,
		'{{cw_lumen_filters}}'   => $cw_lumen_lite_filter_items,
	)
);

return array(
	'slug' => 'education-method-page-voltio',
	'family' => 'content',
	'title' => __( 'Education method page · Voltio', 'creceweb-lumen-lite' ),
	'description' => __( 'Full editable learning-method page with principles, quality filter, statement, and final action.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'method', 'creceweb-lumen-lite' ), __( 'principles', 'creceweb-lumen-lite' ), __( 'education', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-method-page-voltio.webp',
	'content' => $cw_lumen_lite_markup,
);
