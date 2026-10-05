<?php
/**
 * Education course detail blueprint · Voltio.
 *
 * @package CreceWebLumenLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$cw_lumen_lite_markup = <<<'HTML'
<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-hero cw-kit-catalog-hero cw-kit-detail-hero","align":"full","anchor":"course-detail"} -->
<div id="course-detail" class="wp-block-group alignfull cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-hero cw-kit-catalog-hero cw-kit-detail-hero">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell"} -->
<div class="wp-block-group cw-kit-shell">
<!-- wp:paragraph {"className":"cw-kit-sticker"} --><p class="cw-kit-sticker">{{sticker}}</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"cw-kit-display cw-kit-detail-hero__title"} --><h1 class="wp-block-heading cw-kit-display cw-kit-detail-hero__title">{{title}}</h1><!-- /wp:heading -->
<!-- wp:paragraph {"className":"cw-kit-copy cw-kit-detail-hero__copy"} --><p class="cw-kit-copy cw-kit-detail-hero__copy">{{intro}}</p><!-- /wp:paragraph -->
<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"},"className":"cw-kit-detail-meta"} --><div class="wp-block-group cw-kit-detail-meta"><!-- wp:paragraph {"className":"cw-kit-detail-meta__item"} --><p class="cw-kit-detail-meta__item">{{meta_1}}</p><!-- /wp:paragraph --><!-- wp:paragraph {"className":"cw-kit-detail-meta__item"} --><p class="cw-kit-detail-meta__item">{{meta_2}}</p><!-- /wp:paragraph --></div><!-- /wp:group -->
</div><!-- /wp:group -->
</div><!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-detail-section","align":"full"} -->
<div class="wp-block-group alignfull cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-detail-section">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell"} --><div class="wp-block-group cw-kit-shell">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-layout"} --><div class="wp-block-group cw-kit-detail-layout">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-main"} --><div class="wp-block-group cw-kit-detail-main">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-block"} --><div class="wp-block-group cw-kit-detail-block">
<!-- wp:paragraph {"className":"cw-kit-detail-kicker"} --><p class="cw-kit-detail-kicker">{{learn_label}}</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"className":"cw-kit-detail-title"} --><h2 class="wp-block-heading cw-kit-detail-title">{{learn_title}}</h2><!-- /wp:heading -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-grid"} --><div class="wp-block-group cw-kit-detail-grid">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-card cw-kit-tone-1"} --><div class="wp-block-group cw-kit-detail-card cw-kit-tone-1"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">{{learn_1_title}}</h3><!-- /wp:heading --><!-- wp:paragraph --><p>{{learn_1_copy}}</p><!-- /wp:paragraph --></div><!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-card cw-kit-tone-3"} --><div class="wp-block-group cw-kit-detail-card cw-kit-tone-3"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">{{learn_2_title}}</h3><!-- /wp:heading --><!-- wp:paragraph --><p>{{learn_2_copy}}</p><!-- /wp:paragraph --></div><!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-card cw-kit-tone-5"} --><div class="wp-block-group cw-kit-detail-card cw-kit-tone-5"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">{{learn_3_title}}</h3><!-- /wp:heading --><!-- wp:paragraph --><p>{{learn_3_copy}}</p><!-- /wp:paragraph --></div><!-- /wp:group -->
</div><!-- /wp:group -->
</div><!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-block","anchor":"modules"} --><div id="modules" class="wp-block-group cw-kit-detail-block">
<!-- wp:paragraph {"className":"cw-kit-detail-kicker"} --><p class="cw-kit-detail-kicker">{{modules_label}}</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"className":"cw-kit-detail-title"} --><h2 class="wp-block-heading cw-kit-detail-title">{{modules_title}}</h2><!-- /wp:heading -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-checklist"} --><div class="wp-block-group cw-kit-checklist">
{{modules}}
</div><!-- /wp:group -->
</div><!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-feature cw-kit-tone-2"} --><div class="wp-block-group cw-kit-detail-feature cw-kit-tone-2">
<!-- wp:paragraph {"className":"cw-kit-detail-kicker"} --><p class="cw-kit-detail-kicker">{{project_label}}</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"className":"cw-kit-detail-title"} --><h2 class="wp-block-heading cw-kit-detail-title">{{project_title}}</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>{{project_copy}}</p><!-- /wp:paragraph -->
</div><!-- /wp:group -->
</div><!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-aside"} --><div class="wp-block-group cw-kit-detail-aside">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-side-card"} --><div class="wp-block-group cw-kit-detail-side-card"><!-- wp:paragraph {"className":"cw-kit-detail-kicker"} --><p class="cw-kit-detail-kicker">{{aside_1_label}}</p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">{{aside_1_title}}</h3><!-- /wp:heading --><!-- wp:list --><ul class="wp-block-list"><li>{{aside_1_a}}</li><li>{{aside_1_b}}</li><li>{{aside_1_c}}</li></ul><!-- /wp:list --></div><!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-side-card"} --><div class="wp-block-group cw-kit-detail-side-card"><!-- wp:paragraph {"className":"cw-kit-detail-kicker"} --><p class="cw-kit-detail-kicker">{{aside_2_label}}</p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">{{aside_2_title}}</h3><!-- /wp:heading --><!-- wp:paragraph --><p>{{aside_2_copy}}</p><!-- /wp:paragraph --></div><!-- /wp:group -->
</div><!-- /wp:group -->
</div><!-- /wp:group -->
</div><!-- /wp:group -->
</div><!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-final cw-kit-detail-final","align":"full"} --><div class="wp-block-group alignfull cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-final cw-kit-detail-final"><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell"} --><div class="wp-block-group cw-kit-shell"><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-final__box"} --><div class="wp-block-group cw-kit-final__box"><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-final__copy-wrap"} --><div class="wp-block-group cw-kit-final__copy-wrap"><!-- wp:paragraph {"className":"cw-kit-eyebrow cw-kit-eyebrow--on-color"} --><p class="cw-kit-eyebrow cw-kit-eyebrow--on-color">{{cta_label}}</p><!-- /wp:paragraph --><!-- wp:heading {"className":"cw-kit-final__title"} --><h2 class="wp-block-heading cw-kit-final__title">{{cta_title}}</h2><!-- /wp:heading --><!-- wp:paragraph {"className":"cw-kit-final__copy"} --><p class="cw-kit-final__copy">{{cta_copy}}</p><!-- /wp:paragraph --><!-- wp:buttons {"className":"cw-kit-btns","layout":{"type":"flex","flexWrap":"wrap"}} --><div class="wp-block-buttons cw-kit-btns"><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#modules">{{cta_button}}</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group --><!-- wp:paragraph {"className":"cw-kit-final__bolt"} --><p class="cw-kit-final__bolt">⚡</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:group --></div><!-- /wp:group -->
HTML;

$cw_lumen_lite_modules = '';
$cw_lumen_lite_module_rows = array(
	array( '01', __( 'Frame the problem', 'creceweb-lumen-lite' ), __( 'Turn a vague request into a decision with scope, constraints, and evidence.', 'creceweb-lumen-lite' ) ),
	array( '02', __( 'Work with the model', 'creceweb-lumen-lite' ), __( 'Build prompts that preserve context and make assumptions visible.', 'creceweb-lumen-lite' ) ),
	array( '03', __( 'Check the result', 'creceweb-lumen-lite' ), __( 'Evaluate claims, compare alternatives, and keep human judgement in the loop.', 'creceweb-lumen-lite' ) ),
	array( '04', __( 'Ship a small system', 'creceweb-lumen-lite' ), __( 'Turn the workflow into something repeatable without hiding important decisions.', 'creceweb-lumen-lite' ) ),
);
foreach ( $cw_lumen_lite_module_rows as $cw_lumen_lite_module ) {
	$cw_lumen_lite_modules .= sprintf(
		'<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-checklist__item"} --><div class="wp-block-group cw-kit-checklist__item"><!-- wp:paragraph {"className":"cw-kit-checklist__number"} --><p class="cw-kit-checklist__number">%1$s</p><!-- /wp:paragraph --><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-checklist__body"} --><div class="wp-block-group cw-kit-checklist__body"><!-- wp:heading {"level":3,"className":"cw-kit-checklist__title"} --><h3 class="wp-block-heading cw-kit-checklist__title">%2$s</h3><!-- /wp:heading --><!-- wp:paragraph {"className":"cw-kit-checklist__copy"} --><p class="cw-kit-checklist__copy">%3$s</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:group -->',
		esc_html( $cw_lumen_lite_module[0] ), esc_html( $cw_lumen_lite_module[1] ), esc_html( $cw_lumen_lite_module[2] )
	);
}

$cw_lumen_lite_markup = strtr( $cw_lumen_lite_markup, array(
	'{{sticker}}' => esc_html__( 'Course', 'creceweb-lumen-lite' ),
	'{{title}}' => esc_html__( 'AI without autopilot', 'creceweb-lumen-lite' ),
	'{{intro}}' => esc_html__( 'A practical route for using generative AI while keeping criteria, evidence, and responsibility in your hands.', 'creceweb-lumen-lite' ),
	'{{meta_1}}' => esc_html__( 'Beginner', 'creceweb-lumen-lite' ),
	'{{meta_2}}' => esc_html__( '3 hours', 'creceweb-lumen-lite' ),
	'{{learn_label}}' => esc_html__( 'What you will learn', 'creceweb-lumen-lite' ),
	'{{learn_title}}' => esc_html__( 'Build judgement into the workflow.', 'creceweb-lumen-lite' ),
	'{{learn_1_title}}' => esc_html__( 'Ask with context', 'creceweb-lumen-lite' ),
	'{{learn_1_copy}}' => esc_html__( 'Give the model enough information to help without turning the prompt into a hidden specification.', 'creceweb-lumen-lite' ),
	'{{learn_2_title}}' => esc_html__( 'Verify before using', 'creceweb-lumen-lite' ),
	'{{learn_2_copy}}' => esc_html__( 'Separate useful output from confident noise before it reaches a real decision.', 'creceweb-lumen-lite' ),
	'{{learn_3_title}}' => esc_html__( 'Keep ownership', 'creceweb-lumen-lite' ),
	'{{learn_3_copy}}' => esc_html__( 'Know which decisions can be accelerated and which ones still need your judgement.', 'creceweb-lumen-lite' ),
	'{{modules_label}}' => esc_html__( 'Modules', 'creceweb-lumen-lite' ),
	'{{modules_title}}' => esc_html__( 'Four steps from prompt to practice.', 'creceweb-lumen-lite' ),
	'{{modules}}' => $cw_lumen_lite_modules,
	'{{project_label}}' => esc_html__( 'Final project', 'creceweb-lumen-lite' ),
	'{{project_title}}' => esc_html__( 'Build one AI-assisted workflow you can explain.', 'creceweb-lumen-lite' ),
	'{{project_copy}}' => esc_html__( 'Choose a recurring task, define the decisions that stay human, document the checks, and leave a result another person could understand.', 'creceweb-lumen-lite' ),
	'{{aside_1_label}}' => esc_html__( 'Route at a glance', 'creceweb-lumen-lite' ),
	'{{aside_1_title}}' => esc_html__( 'Designed for practice', 'creceweb-lumen-lite' ),
	'{{aside_1_a}}' => esc_html__( 'Level: beginner', 'creceweb-lumen-lite' ),
	'{{aside_1_b}}' => esc_html__( 'Duration: about 3 hours', 'creceweb-lumen-lite' ),
	'{{aside_1_c}}' => esc_html__( 'Format: guided practice + project', 'creceweb-lumen-lite' ),
	'{{aside_2_label}}' => esc_html__( 'Before you start', 'creceweb-lumen-lite' ),
	'{{aside_2_title}}' => esc_html__( 'Bring a real task.', 'creceweb-lumen-lite' ),
	'{{aside_2_copy}}' => esc_html__( 'Tie each exercise to something you need to decide, improve, or produce.', 'creceweb-lumen-lite' ),
	'{{cta_label}}' => esc_html__( 'Ready to practice', 'creceweb-lumen-lite' ),
	'{{cta_title}}' => esc_html__( 'Build the route around a real problem.', 'creceweb-lumen-lite' ),
	'{{cta_copy}}' => esc_html__( 'Use the route on a problem that matters now, then keep the checks visible as the work changes.', 'creceweb-lumen-lite' ),
	'{{cta_button}}' => esc_html__( 'Review the modules →', 'creceweb-lumen-lite' ),
) );

return array(
	'slug' => 'education-course-detail-voltio',
	'family' => 'content',
	'title' => __( 'Education course detail · Voltio', 'creceweb-lumen-lite' ),
	'description' => __( 'Course detail with outcomes, modules, a final project, and a closing action.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'course detail', 'creceweb-lumen-lite' ), __( 'education', 'creceweb-lumen-lite' ), __( 'template', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-course-detail-voltio.webp',
	'content' => $cw_lumen_lite_markup,
);
