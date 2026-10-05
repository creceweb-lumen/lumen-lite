<?php
/**
 * Education course catalog · Voltio.
 *
 * @package CreceWebLumenLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$cw_lumen_lite_courses = array(
	array(
		'tone'        => '1',
		'category'    => __( 'Applied AI', 'creceweb-lumen-lite' ),
		'title'       => __( 'AI without autopilot', 'creceweb-lumen-lite' ),
		'description' => __( 'Use generative models to research, write, and decide without delegating judgement.', 'creceweb-lumen-lite' ),
		'meta'        => __( 'Beginner · 3h', 'creceweb-lumen-lite' ),
	),
	array(
		'tone'        => '2',
		'category'    => __( 'Development', 'creceweb-lumen-lite' ),
		'title'       => __( 'Web from zero to deploy', 'creceweb-lumen-lite' ),
		'description' => __( 'Understand the full route from an idea to a visible site on the internet.', 'creceweb-lumen-lite' ),
		'meta'        => __( 'Beginner · 4h', 'creceweb-lumen-lite' ),
	),
	array(
		'tone'        => '3',
		'category'    => __( 'Automation', 'creceweb-lumen-lite' ),
		'title'       => __( 'Automate the repetitive', 'creceweb-lumen-lite' ),
		'description' => __( 'Turn manual tasks into simple, observable systems that are easy to maintain.', 'creceweb-lumen-lite' ),
		'meta'        => __( 'Intermediate · 3h', 'creceweb-lumen-lite' ),
	),
	array(
		'tone'        => '4',
		'category'    => __( 'Content', 'creceweb-lumen-lite' ),
		'title'       => __( 'UX writing that guides', 'creceweb-lumen-lite' ),
		'description' => __( 'Write interfaces that anticipate, orient, and help people recover from errors.', 'creceweb-lumen-lite' ),
		'meta'        => __( 'Beginner · 2h', 'creceweb-lumen-lite' ),
	),
	array(
		'tone'        => '5',
		'category'    => __( 'Design', 'creceweb-lumen-lite' ),
		'title'       => __( 'Visual systems that live', 'creceweb-lumen-lite' ),
		'description' => __( 'Move from isolated screens to visual rules that speed up work as a team.', 'creceweb-lumen-lite' ),
		'meta'        => __( 'Intermediate · 4h', 'creceweb-lumen-lite' ),
	),
	array(
		'tone'        => '6',
		'category'    => __( 'Data', 'creceweb-lumen-lite' ),
		'title'       => __( 'Stories with numbers', 'creceweb-lumen-lite' ),
		'description' => __( 'Choose metrics, discard noise, and build a narrative that can be explained.', 'creceweb-lumen-lite' ),
		'meta'        => __( 'Beginner · 3h', 'creceweb-lumen-lite' ),
	),
	array(
		'tone'        => '1',
		'category'    => __( 'Applied AI', 'creceweb-lumen-lite' ),
		'title'       => __( 'Agents with clear limits', 'creceweb-lumen-lite' ),
		'description' => __( 'Design goals, memory, permissions, and evaluations for useful, controllable agents.', 'creceweb-lumen-lite' ),
		'meta'        => __( 'Intermediate · 4h', 'creceweb-lumen-lite' ),
	),
	array(
		'tone'        => '2',
		'category'    => __( 'Operations', 'creceweb-lumen-lite' ),
		'title'       => __( 'Personal work systems', 'creceweb-lumen-lite' ),
		'description' => __( 'Organize priorities, information, and follow-up without turning the system into more work.', 'creceweb-lumen-lite' ),
		'meta'        => __( 'Beginner · 3h', 'creceweb-lumen-lite' ),
	),
);

$cw_lumen_lite_card_template = <<<'HTML'
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-catalog-card cw-kit-tone-%1$s"} -->
<div class="wp-block-group cw-kit-catalog-card cw-kit-tone-%1$s">
<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"},"className":"cw-kit-catalog-card__head"} -->
<div class="wp-block-group cw-kit-catalog-card__head">
<!-- wp:paragraph {"className":"cw-kit-catalog-card__category"} -->
<p class="cw-kit-catalog-card__category">%2$s</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-catalog-card__icon"} -->
<p class="cw-kit-catalog-card__icon">&#9889;&#65038;</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:heading {"level":3,"className":"cw-kit-catalog-card__title"} -->
<h3 class="wp-block-heading cw-kit-catalog-card__title">%3$s</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"cw-kit-catalog-card__copy"} -->
<p class="cw-kit-catalog-card__copy">%4$s</p>
<!-- /wp:paragraph -->
<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"},"className":"cw-kit-catalog-card__footer"} -->
<div class="wp-block-group cw-kit-catalog-card__footer">
<!-- wp:paragraph {"className":"cw-kit-catalog-card__meta"} -->
<p class="cw-kit-catalog-card__meta">%5$s</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-catalog-card__cta"} -->
<p class="cw-kit-catalog-card__cta"><a href="#">%6$s</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
HTML;

$cw_lumen_lite_cards = '';
foreach ( $cw_lumen_lite_courses as $cw_lumen_lite_course ) {
	$cw_lumen_lite_cards .= sprintf(
		$cw_lumen_lite_card_template,
		esc_attr( $cw_lumen_lite_course['tone'] ),
		esc_html( $cw_lumen_lite_course['category'] ),
		esc_html( $cw_lumen_lite_course['title'] ),
		esc_html( $cw_lumen_lite_course['description'] ),
		esc_html( $cw_lumen_lite_course['meta'] ),
		esc_html__( 'I want this route →', 'creceweb-lumen-lite' )
	);
}

$cw_lumen_lite_markup = <<<'HTML'
<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-hero cw-kit-catalog-hero","align":"full","anchor":"courses"} -->
<div id="courses" class="wp-block-group alignfull cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-hero cw-kit-catalog-hero">
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
		'{{cw_lumen_text_1}}' => esc_html__( 'Courses', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_2}}' => esc_html__( "Don't collect videos.", 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_3}}' => esc_html__( 'Collect results.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_4}}' => esc_html__( 'Compact routes to understand a skill, practice it with context, and finish with a project that proves what you can do.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_cards}}'  => $cw_lumen_lite_cards,
	)
);

return array(
	'slug' => 'education-course-catalog-voltio',
	'family' => 'content',
	'title' => __( 'Education course catalog · Voltio', 'creceweb-lumen-lite' ),
	'description' => __( 'Eight large editable course cards with category, summary, level, duration, and call to action.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'courses', 'creceweb-lumen-lite' ), __( 'catalog', 'creceweb-lumen-lite' ), __( 'education', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-course-catalog-voltio.webp',
	'content' => $cw_lumen_lite_markup,
);
