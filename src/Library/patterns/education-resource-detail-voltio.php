<?php
/**
 * Education resource detail blueprint · Voltio.
 *
 * @package CreceWebLumenLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$cw_lumen_lite_markup = <<<'HTML'
<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-hero cw-kit-catalog-hero cw-kit-detail-hero","align":"full","anchor":"resource-detail"} -->
<div id="resource-detail" class="wp-block-group alignfull cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-hero cw-kit-catalog-hero cw-kit-detail-hero"><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell"} --><div class="wp-block-group cw-kit-shell"><!-- wp:paragraph {"className":"cw-kit-sticker"} --><p class="cw-kit-sticker">{{sticker}}</p><!-- /wp:paragraph --><!-- wp:heading {"level":1,"className":"cw-kit-display cw-kit-detail-hero__title"} --><h1 class="wp-block-heading cw-kit-display cw-kit-detail-hero__title">{{title}}</h1><!-- /wp:heading --><!-- wp:paragraph {"className":"cw-kit-copy cw-kit-detail-hero__copy"} --><p class="cw-kit-copy cw-kit-detail-hero__copy">{{intro}}</p><!-- /wp:paragraph --><!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"},"className":"cw-kit-detail-meta"} --><div class="wp-block-group cw-kit-detail-meta"><!-- wp:paragraph {"className":"cw-kit-detail-meta__item"} --><p class="cw-kit-detail-meta__item">{{meta_1}}</p><!-- /wp:paragraph --><!-- wp:paragraph {"className":"cw-kit-detail-meta__item"} --><p class="cw-kit-detail-meta__item">{{meta_2}}</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:group --></div><!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-detail-section","align":"full"} --><div class="wp-block-group alignfull cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-detail-section"><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell"} --><div class="wp-block-group cw-kit-shell"><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-layout"} --><div class="wp-block-group cw-kit-detail-layout">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-main"} --><div class="wp-block-group cw-kit-detail-main">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-block"} --><div class="wp-block-group cw-kit-detail-block"><!-- wp:paragraph {"className":"cw-kit-detail-kicker"} --><p class="cw-kit-detail-kicker">{{start_label}}</p><!-- /wp:paragraph --><!-- wp:heading {"level":2,"className":"cw-kit-detail-title"} --><h2 class="wp-block-heading cw-kit-detail-title">{{start_title}}</h2><!-- /wp:heading --><!-- wp:paragraph {"className":"cw-kit-detail-copy"} --><p class="cw-kit-detail-copy">{{start_copy}}</p><!-- /wp:paragraph --></div><!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-block"} --><div class="wp-block-group cw-kit-detail-block"><!-- wp:paragraph {"className":"cw-kit-detail-kicker"} --><p class="cw-kit-detail-kicker">{{guide_label}}</p><!-- /wp:paragraph --><!-- wp:heading {"level":2,"className":"cw-kit-detail-title"} --><h2 class="wp-block-heading cw-kit-detail-title">{{guide_title}}</h2><!-- /wp:heading --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">{{guide_h3_1}}</h3><!-- /wp:heading --><!-- wp:paragraph {"className":"cw-kit-detail-copy"} --><p class="cw-kit-detail-copy">{{guide_p_1}}</p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">{{guide_h3_2}}</h3><!-- /wp:heading --><!-- wp:paragraph {"className":"cw-kit-detail-copy"} --><p class="cw-kit-detail-copy">{{guide_p_2}}</p><!-- /wp:paragraph --></div><!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-block","anchor":"key-points"} --><div id="key-points" class="wp-block-group cw-kit-detail-block"><!-- wp:paragraph {"className":"cw-kit-detail-kicker"} --><p class="cw-kit-detail-kicker">{{points_label}}</p><!-- /wp:paragraph --><!-- wp:heading {"level":2,"className":"cw-kit-detail-title"} --><h2 class="wp-block-heading cw-kit-detail-title">{{points_title}}</h2><!-- /wp:heading --><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-grid"} --><div class="wp-block-group cw-kit-detail-grid"><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-card cw-kit-tone-1"} --><div class="wp-block-group cw-kit-detail-card cw-kit-tone-1"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">{{point_1_title}}</h3><!-- /wp:heading --><!-- wp:paragraph --><p>{{point_1_copy}}</p><!-- /wp:paragraph --></div><!-- /wp:group --><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-card cw-kit-tone-3"} --><div class="wp-block-group cw-kit-detail-card cw-kit-tone-3"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">{{point_2_title}}</h3><!-- /wp:heading --><!-- wp:paragraph --><p>{{point_2_copy}}</p><!-- /wp:paragraph --></div><!-- /wp:group --><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-card cw-kit-tone-5"} --><div class="wp-block-group cw-kit-detail-card cw-kit-tone-5"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">{{point_3_title}}</h3><!-- /wp:heading --><!-- wp:paragraph --><p>{{point_3_copy}}</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:group --></div><!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-feature cw-kit-tone-4"} --><div class="wp-block-group cw-kit-detail-feature cw-kit-tone-4"><!-- wp:paragraph {"className":"cw-kit-detail-kicker"} --><p class="cw-kit-detail-kicker">{{apply_label}}</p><!-- /wp:paragraph --><!-- wp:heading {"level":2,"className":"cw-kit-detail-title"} --><h2 class="wp-block-heading cw-kit-detail-title">{{apply_title}}</h2><!-- /wp:heading --><!-- wp:paragraph --><p>{{apply_copy}}</p><!-- /wp:paragraph --></div><!-- /wp:group -->
</div><!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-aside"} --><div class="wp-block-group cw-kit-detail-aside"><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-side-card"} --><div class="wp-block-group cw-kit-detail-side-card"><!-- wp:paragraph {"className":"cw-kit-detail-kicker"} --><p class="cw-kit-detail-kicker">{{aside_1_label}}</p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">{{aside_1_title}}</h3><!-- /wp:heading --><!-- wp:list --><ul class="wp-block-list"><li>{{aside_1_a}}</li><li>{{aside_1_b}}</li><li>{{aside_1_c}}</li></ul><!-- /wp:list --></div><!-- /wp:group --><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-side-card"} --><div class="wp-block-group cw-kit-detail-side-card"><!-- wp:paragraph {"className":"cw-kit-detail-kicker"} --><p class="cw-kit-detail-kicker">{{aside_2_label}}</p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">{{aside_2_title}}</h3><!-- /wp:heading --><!-- wp:paragraph --><p>{{aside_2_a}}</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>{{aside_2_b}}</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:group -->
</div><!-- /wp:group --></div><!-- /wp:group --></div><!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-final cw-kit-detail-final","align":"full"} --><div class="wp-block-group alignfull cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-final cw-kit-detail-final"><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell"} --><div class="wp-block-group cw-kit-shell"><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-final__box"} --><div class="wp-block-group cw-kit-final__box"><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-final__copy-wrap"} --><div class="wp-block-group cw-kit-final__copy-wrap"><!-- wp:paragraph {"className":"cw-kit-eyebrow cw-kit-eyebrow--on-color"} --><p class="cw-kit-eyebrow cw-kit-eyebrow--on-color">{{cta_label}}</p><!-- /wp:paragraph --><!-- wp:heading {"className":"cw-kit-final__title"} --><h2 class="wp-block-heading cw-kit-final__title">{{cta_title}}</h2><!-- /wp:heading --><!-- wp:paragraph {"className":"cw-kit-final__copy"} --><p class="cw-kit-final__copy">{{cta_copy}}</p><!-- /wp:paragraph --><!-- wp:buttons {"className":"cw-kit-btns","layout":{"type":"flex","flexWrap":"wrap"}} --><div class="wp-block-buttons cw-kit-btns"><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#key-points">{{cta_button}}</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group --><!-- wp:paragraph {"className":"cw-kit-final__bolt"} --><p class="cw-kit-final__bolt">↗</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:group --></div><!-- /wp:group -->
HTML;

$cw_lumen_lite_markup = strtr( $cw_lumen_lite_markup, array(
	'{{sticker}}' => esc_html__( 'Guide', 'creceweb-lumen-lite' ),
	'{{title}}' => esc_html__( 'A 30-day plan for using AI with judgement', 'creceweb-lumen-lite' ),
	'{{intro}}' => esc_html__( 'A practical guide for moving from curiosity to a small, verifiable workflow without handing over the decisions that matter.', 'creceweb-lumen-lite' ),
	'{{meta_1}}' => esc_html__( 'Applied AI', 'creceweb-lumen-lite' ),
	'{{meta_2}}' => esc_html__( '8 minute read', 'creceweb-lumen-lite' ),
	'{{start_label}}' => esc_html__( 'Start here', 'creceweb-lumen-lite' ),
	'{{start_title}}' => esc_html__( 'Use the month to build evidence, not habits for their own sake.', 'creceweb-lumen-lite' ),
	'{{start_copy}}' => esc_html__( 'Choose one recurring task and improve it in small steps. Keep notes on what changed, what failed, and which decisions still need you.', 'creceweb-lumen-lite' ),
	'{{guide_label}}' => esc_html__( 'The guide', 'creceweb-lumen-lite' ),
	'{{guide_title}}' => esc_html__( 'Move from observation to a small system.', 'creceweb-lumen-lite' ),
	'{{guide_h3_1}}' => esc_html__( 'Days 1–10: map the work', 'creceweb-lumen-lite' ),
	'{{guide_p_1}}' => esc_html__( 'Document the inputs, the repeated decisions, and the places where errors become expensive. Do not automate anything yet.', 'creceweb-lumen-lite' ),
	'{{guide_h3_2}}' => esc_html__( 'Days 11–30: test one useful change', 'creceweb-lumen-lite' ),
	'{{guide_p_2}}' => esc_html__( 'Introduce AI only where you can compare before and after. Keep the check visible and decide what evidence would justify keeping the change.', 'creceweb-lumen-lite' ),
	'{{points_label}}' => esc_html__( 'Key points', 'creceweb-lumen-lite' ),
	'{{points_title}}' => esc_html__( 'Three rules that keep the experiment useful.', 'creceweb-lumen-lite' ),
	'{{point_1_title}}' => esc_html__( 'One task', 'creceweb-lumen-lite' ),
	'{{point_1_copy}}' => esc_html__( 'A narrow workflow is easier to observe, compare, and undo.', 'creceweb-lumen-lite' ),
	'{{point_2_title}}' => esc_html__( 'Visible checks', 'creceweb-lumen-lite' ),
	'{{point_2_copy}}' => esc_html__( 'Decide how you will catch bad output before the system reaches real work.', 'creceweb-lumen-lite' ),
	'{{point_3_title}}' => esc_html__( 'Evidence first', 'creceweb-lumen-lite' ),
	'{{point_3_copy}}' => esc_html__( 'Keep a change because it improves the work, not because the tool feels impressive.', 'creceweb-lumen-lite' ),
	'{{apply_label}}' => esc_html__( 'Practical application', 'creceweb-lumen-lite' ),
	'{{apply_title}}' => esc_html__( 'Write a one-page before-and-after note.', 'creceweb-lumen-lite' ),
	'{{apply_copy}}' => esc_html__( 'Record the original workflow, the change you tested, one measurable difference, one risk, and the condition that would make you roll it back.', 'creceweb-lumen-lite' ),
	'{{aside_1_label}}' => esc_html__( 'Guide details', 'creceweb-lumen-lite' ),
	'{{aside_1_title}}' => esc_html__( 'Designed to be used', 'creceweb-lumen-lite' ),
	'{{aside_1_a}}' => esc_html__( 'Type: practical guide', 'creceweb-lumen-lite' ),
	'{{aside_1_b}}' => esc_html__( 'Reading time: about 8 minutes', 'creceweb-lumen-lite' ),
	'{{aside_1_c}}' => esc_html__( 'Application: 30-day experiment', 'creceweb-lumen-lite' ),
	'{{aside_2_label}}' => esc_html__( 'Related next', 'creceweb-lumen-lite' ),
	'{{aside_2_title}}' => esc_html__( 'Keep the thread going.', 'creceweb-lumen-lite' ),
	'{{aside_2_a}}' => esc_html__( 'Quick lesson: Prompts with context', 'creceweb-lumen-lite' ),
	'{{aside_2_b}}' => esc_html__( 'Course: AI without autopilot', 'creceweb-lumen-lite' ),
	'{{cta_label}}' => esc_html__( 'Use the guide', 'creceweb-lumen-lite' ),
	'{{cta_title}}' => esc_html__( 'Turn one useful idea into visible practice.', 'creceweb-lumen-lite' ),
	'{{cta_copy}}' => esc_html__( 'Use the guide on one real workflow, keep the key points visible, and finish with a practical next action.', 'creceweb-lumen-lite' ),
	'{{cta_button}}' => esc_html__( 'Review the key points →', 'creceweb-lumen-lite' ),
) );

return array(
	'slug' => 'education-resource-detail-voltio',
	'family' => 'content',
	'title' => __( 'Education resource detail · Voltio', 'creceweb-lumen-lite' ),
	'description' => __( 'Resource detail with guide content, key points, practical application, related items, and a closing action.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'resource detail', 'creceweb-lumen-lite' ), __( 'guide', 'creceweb-lumen-lite' ), __( 'template', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-resource-detail-voltio.webp',
	'content' => $cw_lumen_lite_markup,
);
