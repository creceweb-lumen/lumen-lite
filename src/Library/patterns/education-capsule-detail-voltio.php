<?php
/**
 * Education capsule detail blueprint · Voltio.
 *
 * @package CreceWebLumenLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$cw_lumen_lite_markup = <<<'HTML'
<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-hero cw-kit-catalog-hero cw-kit-detail-hero","align":"full","anchor":"capsule-detail"} -->
<div id="capsule-detail" class="wp-block-group alignfull cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-hero cw-kit-catalog-hero cw-kit-detail-hero"><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell"} --><div class="wp-block-group cw-kit-shell"><!-- wp:paragraph {"className":"cw-kit-sticker"} --><p class="cw-kit-sticker">{{sticker}}</p><!-- /wp:paragraph --><!-- wp:heading {"level":1,"className":"cw-kit-display cw-kit-detail-hero__title"} --><h1 class="wp-block-heading cw-kit-display cw-kit-detail-hero__title">{{title}}</h1><!-- /wp:heading --><!-- wp:paragraph {"className":"cw-kit-copy cw-kit-detail-hero__copy"} --><p class="cw-kit-copy cw-kit-detail-hero__copy">{{intro}}</p><!-- /wp:paragraph --><!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"},"className":"cw-kit-detail-meta"} --><div class="wp-block-group cw-kit-detail-meta"><!-- wp:paragraph {"className":"cw-kit-detail-meta__item"} --><p class="cw-kit-detail-meta__item">{{meta_1}}</p><!-- /wp:paragraph --><!-- wp:paragraph {"className":"cw-kit-detail-meta__item"} --><p class="cw-kit-detail-meta__item">{{meta_2}}</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:group --></div><!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-detail-section","align":"full"} --><div class="wp-block-group alignfull cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-detail-section"><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell"} --><div class="wp-block-group cw-kit-shell"><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-layout"} --><div class="wp-block-group cw-kit-detail-layout">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-main"} --><div class="wp-block-group cw-kit-detail-main">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-block"} --><div class="wp-block-group cw-kit-detail-block"><!-- wp:paragraph {"className":"cw-kit-detail-kicker"} --><p class="cw-kit-detail-kicker">{{explain_label}}</p><!-- /wp:paragraph --><!-- wp:heading {"level":2,"className":"cw-kit-detail-title"} --><h2 class="wp-block-heading cw-kit-detail-title">{{explain_title}}</h2><!-- /wp:heading --><!-- wp:paragraph {"className":"cw-kit-detail-copy"} --><p class="cw-kit-detail-copy">{{explain_copy}}</p><!-- /wp:paragraph --></div><!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-feature cw-kit-tone-1"} --><div class="wp-block-group cw-kit-detail-feature cw-kit-tone-1"><!-- wp:paragraph {"className":"cw-kit-detail-kicker"} --><p class="cw-kit-detail-kicker">{{idea_label}}</p><!-- /wp:paragraph --><!-- wp:heading {"level":2,"className":"cw-kit-detail-title"} --><h2 class="wp-block-heading cw-kit-detail-title">{{idea_title}}</h2><!-- /wp:heading --><!-- wp:paragraph --><p>{{idea_copy}}</p><!-- /wp:paragraph --></div><!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-block"} --><div class="wp-block-group cw-kit-detail-block"><!-- wp:paragraph {"className":"cw-kit-detail-kicker"} --><p class="cw-kit-detail-kicker">{{example_label}}</p><!-- /wp:paragraph --><!-- wp:heading {"level":2,"className":"cw-kit-detail-title"} --><h2 class="wp-block-heading cw-kit-detail-title">{{example_title}}</h2><!-- /wp:heading --><!-- wp:quote {"className":"cw-kit-detail-quote"} --><blockquote class="wp-block-quote cw-kit-detail-quote"><p>{{example_copy}}</p></blockquote><!-- /wp:quote --></div><!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-block","anchor":"exercise"} --><div id="exercise" class="wp-block-group cw-kit-detail-block"><!-- wp:paragraph {"className":"cw-kit-detail-kicker"} --><p class="cw-kit-detail-kicker">{{exercise_label}}</p><!-- /wp:paragraph --><!-- wp:heading {"level":2,"className":"cw-kit-detail-title"} --><h2 class="wp-block-heading cw-kit-detail-title">{{exercise_title}}</h2><!-- /wp:heading --><!-- wp:paragraph {"className":"cw-kit-detail-copy"} --><p class="cw-kit-detail-copy">{{exercise_copy}}</p><!-- /wp:paragraph --></div><!-- /wp:group -->
</div><!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-aside"} --><div class="wp-block-group cw-kit-detail-aside"><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-side-card"} --><div class="wp-block-group cw-kit-detail-side-card"><!-- wp:paragraph {"className":"cw-kit-detail-kicker"} --><p class="cw-kit-detail-kicker">{{aside_1_label}}</p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">{{aside_1_title}}</h3><!-- /wp:heading --><!-- wp:list --><ul class="wp-block-list"><li>{{aside_1_a}}</li><li>{{aside_1_b}}</li><li>{{aside_1_c}}</li></ul><!-- /wp:list --></div><!-- /wp:group --><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-detail-side-card"} --><div class="wp-block-group cw-kit-detail-side-card"><!-- wp:paragraph {"className":"cw-kit-detail-kicker"} --><p class="cw-kit-detail-kicker">{{aside_2_label}}</p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">{{aside_2_title}}</h3><!-- /wp:heading --><!-- wp:paragraph --><p>{{aside_2_copy}}</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:group -->
</div><!-- /wp:group --></div><!-- /wp:group --></div><!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-final cw-kit-detail-final","align":"full"} --><div class="wp-block-group alignfull cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-final cw-kit-detail-final"><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell"} --><div class="wp-block-group cw-kit-shell"><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-final__box"} --><div class="wp-block-group cw-kit-final__box"><!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-final__copy-wrap"} --><div class="wp-block-group cw-kit-final__copy-wrap"><!-- wp:paragraph {"className":"cw-kit-eyebrow cw-kit-eyebrow--on-color"} --><p class="cw-kit-eyebrow cw-kit-eyebrow--on-color">{{cta_label}}</p><!-- /wp:paragraph --><!-- wp:heading {"className":"cw-kit-final__title"} --><h2 class="wp-block-heading cw-kit-final__title">{{cta_title}}</h2><!-- /wp:heading --><!-- wp:paragraph {"className":"cw-kit-final__copy"} --><p class="cw-kit-final__copy">{{cta_copy}}</p><!-- /wp:paragraph --><!-- wp:buttons {"className":"cw-kit-btns","layout":{"type":"flex","flexWrap":"wrap"}} --><div class="wp-block-buttons cw-kit-btns"><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#exercise">{{cta_button}}</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group --><!-- wp:paragraph {"className":"cw-kit-final__bolt"} --><p class="cw-kit-final__bolt">✦</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:group --></div><!-- /wp:group -->
HTML;

$cw_lumen_lite_markup = strtr( $cw_lumen_lite_markup, array(
	'{{sticker}}' => esc_html__( 'Quick lesson', 'creceweb-lumen-lite' ),
	'{{title}}' => esc_html__( 'Prompts with context', 'creceweb-lumen-lite' ),
	'{{intro}}' => esc_html__( 'A short lesson for getting more useful answers by giving a model the context that actually changes the decision.', 'creceweb-lumen-lite' ),
	'{{meta_1}}' => esc_html__( 'Applied AI', 'creceweb-lumen-lite' ),
	'{{meta_2}}' => esc_html__( '18 minutes', 'creceweb-lumen-lite' ),
	'{{explain_label}}' => esc_html__( 'Why it matters', 'creceweb-lumen-lite' ),
	'{{explain_title}}' => esc_html__( 'A prompt is not just a question.', 'creceweb-lumen-lite' ),
	'{{explain_copy}}' => esc_html__( 'Useful context explains the goal, the constraints, the audience, and what a good answer must help you decide next.', 'creceweb-lumen-lite' ),
	'{{idea_label}}' => esc_html__( 'Key idea', 'creceweb-lumen-lite' ),
	'{{idea_title}}' => esc_html__( 'Add only context that changes the answer.', 'creceweb-lumen-lite' ),
	'{{idea_copy}}' => esc_html__( 'More context is not automatically better. Keep the details that change priorities, trade-offs, tone, evidence, or the next action.', 'creceweb-lumen-lite' ),
	'{{example_label}}' => esc_html__( 'Example', 'creceweb-lumen-lite' ),
	'{{example_title}}' => esc_html__( 'Move from generic to actionable.', 'creceweb-lumen-lite' ),
	'{{example_copy}}' => esc_html__( 'Instead of asking for ideas for a landing page, explain who it serves, what action matters, what proof exists, and which constraint cannot move.', 'creceweb-lumen-lite' ),
	'{{exercise_label}}' => esc_html__( 'Try it now', 'creceweb-lumen-lite' ),
	'{{exercise_title}}' => esc_html__( 'Rewrite one prompt you already use.', 'creceweb-lumen-lite' ),
	'{{exercise_copy}}' => esc_html__( 'Add a goal, one real constraint, the audience, and the decision the answer should support. Then compare the result with your original prompt.', 'creceweb-lumen-lite' ),
	'{{aside_1_label}}' => esc_html__( 'Lesson at a glance', 'creceweb-lumen-lite' ),
	'{{aside_1_title}}' => esc_html__( 'One idea, one exercise', 'creceweb-lumen-lite' ),
	'{{aside_1_a}}' => esc_html__( 'Duration: about 18 minutes', 'creceweb-lumen-lite' ),
	'{{aside_1_b}}' => esc_html__( 'Format: explanation + example', 'creceweb-lumen-lite' ),
	'{{aside_1_c}}' => esc_html__( 'Finish with one practical rewrite', 'creceweb-lumen-lite' ),
	'{{aside_2_label}}' => esc_html__( 'Keep it useful', 'creceweb-lumen-lite' ),
	'{{aside_2_title}}' => esc_html__( 'Stop when the decision is clearer.', 'creceweb-lumen-lite' ),
	'{{aside_2_copy}}' => esc_html__( 'The goal is not a perfect prompt. It is enough context to produce a result you can evaluate and use.', 'creceweb-lumen-lite' ),
	'{{cta_label}}' => esc_html__( 'Next move', 'creceweb-lumen-lite' ),
	'{{cta_title}}' => esc_html__( 'Use the idea before you collect another one.', 'creceweb-lumen-lite' ),
	'{{cta_copy}}' => esc_html__( 'Put the idea to work now, then decide what deserves a place in your next lesson.', 'creceweb-lumen-lite' ),
	'{{cta_button}}' => esc_html__( 'Try the exercise →', 'creceweb-lumen-lite' ),
) );

return array(
	'slug' => 'education-capsule-detail-voltio',
	'family' => 'content',
	'title' => __( 'Education capsule detail · Voltio', 'creceweb-lumen-lite' ),
	'description' => __( 'Quick-lesson detail with explanation, key idea, example, exercise, and next action.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'capsule detail', 'creceweb-lumen-lite' ), __( 'quick lesson', 'creceweb-lumen-lite' ), __( 'template', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-capsule-detail-voltio.webp',
	'content' => $cw_lumen_lite_markup,
);
