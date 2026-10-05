<?php
/**
 * Education course grid · Voltio.
 *
 * @package CreceWebLumenLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_markup = <<<'HTML'
<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-section--courses","align":"full","anchor":"courses"} -->
<div id="courses" class="wp-block-group alignfull cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-section--courses">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell"} -->
<div class="wp-block-group cw-kit-shell">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-section-head"} -->
<div class="wp-block-group cw-kit-section-head">
<!-- wp:paragraph {"className":"cw-kit-sticker"} -->
<p class="cw-kit-sticker">{{cw_lumen_text_1}}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"cw-kit-section-title"} -->
<h2 class="wp-block-heading cw-kit-section-title">{{cw_lumen_text_2}}</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"cw-kit-copy"} -->
<p class="cw-kit-copy">{{cw_lumen_text_3}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-grid cw-kit-grid--3 cw-kit-course-grid"} -->
<div class="wp-block-group cw-kit-grid cw-kit-grid--3 cw-kit-course-grid">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-color-card cw-kit-tone-1"} -->
<div class="wp-block-group cw-kit-color-card cw-kit-tone-1">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-color-card__screen"} -->
<div class="wp-block-group cw-kit-color-card__screen">
<!-- wp:paragraph {"className":"cw-kit-color-card__icon"} -->
<p class="cw-kit-color-card__icon">{{cw_lumen_text_4}}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"cw-kit-color-card__title"} -->
<h3 class="wp-block-heading cw-kit-color-card__title">{{cw_lumen_text_5}}</h3>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->
<!-- wp:paragraph {"className":"cw-kit-color-card__meta"} -->
<p class="cw-kit-color-card__meta">{{cw_lumen_text_6}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-color-card__text"} -->
<p class="cw-kit-color-card__text">{{cw_lumen_text_7}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-color-card cw-kit-tone-2"} -->
<div class="wp-block-group cw-kit-color-card cw-kit-tone-2">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-color-card__screen"} -->
<div class="wp-block-group cw-kit-color-card__screen">
<!-- wp:paragraph {"className":"cw-kit-color-card__icon"} -->
<p class="cw-kit-color-card__icon">{{cw_lumen_text_8}}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"cw-kit-color-card__title"} -->
<h3 class="wp-block-heading cw-kit-color-card__title">{{cw_lumen_text_9}}</h3>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->
<!-- wp:paragraph {"className":"cw-kit-color-card__meta"} -->
<p class="cw-kit-color-card__meta">{{cw_lumen_text_10}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-color-card__text"} -->
<p class="cw-kit-color-card__text">{{cw_lumen_text_11}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-color-card cw-kit-tone-3"} -->
<div class="wp-block-group cw-kit-color-card cw-kit-tone-3">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-color-card__screen"} -->
<div class="wp-block-group cw-kit-color-card__screen">
<!-- wp:paragraph {"className":"cw-kit-color-card__icon"} -->
<p class="cw-kit-color-card__icon">{{cw_lumen_text_12}}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"cw-kit-color-card__title"} -->
<h3 class="wp-block-heading cw-kit-color-card__title">{{cw_lumen_text_13}}</h3>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->
<!-- wp:paragraph {"className":"cw-kit-color-card__meta"} -->
<p class="cw-kit-color-card__meta">{{cw_lumen_text_14}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-color-card__text"} -->
<p class="cw-kit-color-card__text">{{cw_lumen_text_15}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-color-card cw-kit-tone-4"} -->
<div class="wp-block-group cw-kit-color-card cw-kit-tone-4">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-color-card__screen"} -->
<div class="wp-block-group cw-kit-color-card__screen">
<!-- wp:paragraph {"className":"cw-kit-color-card__icon"} -->
<p class="cw-kit-color-card__icon">{{cw_lumen_text_16}}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"cw-kit-color-card__title"} -->
<h3 class="wp-block-heading cw-kit-color-card__title">{{cw_lumen_text_17}}</h3>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->
<!-- wp:paragraph {"className":"cw-kit-color-card__meta"} -->
<p class="cw-kit-color-card__meta">{{cw_lumen_text_18}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-color-card__text"} -->
<p class="cw-kit-color-card__text">{{cw_lumen_text_19}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-color-card cw-kit-tone-5"} -->
<div class="wp-block-group cw-kit-color-card cw-kit-tone-5">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-color-card__screen"} -->
<div class="wp-block-group cw-kit-color-card__screen">
<!-- wp:paragraph {"className":"cw-kit-color-card__icon"} -->
<p class="cw-kit-color-card__icon">{{cw_lumen_text_20}}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"cw-kit-color-card__title"} -->
<h3 class="wp-block-heading cw-kit-color-card__title">{{cw_lumen_text_21}}</h3>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->
<!-- wp:paragraph {"className":"cw-kit-color-card__meta"} -->
<p class="cw-kit-color-card__meta">{{cw_lumen_text_22}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-color-card__text"} -->
<p class="cw-kit-color-card__text">{{cw_lumen_text_23}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-color-card cw-kit-tone-6"} -->
<div class="wp-block-group cw-kit-color-card cw-kit-tone-6">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-color-card__screen"} -->
<div class="wp-block-group cw-kit-color-card__screen">
<!-- wp:paragraph {"className":"cw-kit-color-card__icon"} -->
<p class="cw-kit-color-card__icon">{{cw_lumen_text_8}}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"cw-kit-color-card__title"} -->
<h3 class="wp-block-heading cw-kit-color-card__title">{{cw_lumen_text_24}}</h3>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->
<!-- wp:paragraph {"className":"cw-kit-color-card__meta"} -->
<p class="cw-kit-color-card__meta">{{cw_lumen_text_25}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-color-card__text"} -->
<p class="cw-kit-color-card__text">{{cw_lumen_text_26}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:buttons {"className":"cw-kit-btns cw-kit-btns--center","layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-buttons cw-kit-btns cw-kit-btns--center">
<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#">{{cw_lumen_text_27}}</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
HTML;
$cw_lumen_lite_markup = strtr(
	$cw_lumen_lite_markup,
	array(
		'{{cw_lumen_text_1}}' => esc_html__( 'Courses', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_2}}' => esc_html__( 'Learning works when it ends in something concrete.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_3}}' => esc_html__( 'Each route combines short explanations, real decisions, and a project you can show, use, or keep improving.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_4}}' => esc_html__( '⚡', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_5}}' => esc_html__( 'AI without autopilot', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_6}}' => esc_html__( 'AI · Beginner', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_7}}' => esc_html__( 'Use generative models to research, write, and decide without delegating judgement.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_8}}' => esc_html__( '⚡', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_9}}' => esc_html__( 'Web from zero to deploy', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_10}}' => esc_html__( 'Web · Beginner', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_11}}' => esc_html__( 'Understand the path from an idea to a real site that is live on the internet.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_12}}' => esc_html__( '⚡', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_13}}' => esc_html__( 'Automate the repetitive', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_14}}' => esc_html__( 'Automation · Intermediate', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_15}}' => esc_html__( 'Turn stable manual tasks into simple systems that are easy to observe and maintain.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_16}}' => esc_html__( '⚡', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_17}}' => esc_html__( 'UX writing that guides', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_18}}' => esc_html__( 'Content · Beginner', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_19}}' => esc_html__( 'Write interfaces that anticipate, orient, and help people recover from errors.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_20}}' => esc_html__( '⚡', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_21}}' => esc_html__( 'Visual systems that live', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_22}}' => esc_html__( 'Design · Intermediate', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_23}}' => esc_html__( 'Create reusable visual rules that stay coherent while the product changes.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_24}}' => esc_html__( 'Stories with numbers', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_25}}' => esc_html__( 'Data · Beginner', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_26}}' => esc_html__( 'Use evidence to explain what changed, why it matters, and what to test next.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_27}}' => esc_html__( 'View all courses →', 'creceweb-lumen-lite' ),
	)
);
return array(
	'slug' => 'education-courses-voltio',
	'family' => 'content',
	'title' => __( 'Education course grid · Voltio', 'creceweb-lumen-lite' ),
	'description' => __( 'Six high-contrast course cards with category, level, duration, and action.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'courses', 'creceweb-lumen-lite' ), __( 'cards', 'creceweb-lumen-lite' ), __( 'education', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-courses-voltio.webp',
	'content' => $cw_lumen_lite_markup,
);
