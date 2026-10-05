<?php
/**
 * Education capsule catalog · Voltio.
 *
 * @package CreceWebLumenLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$cw_lumen_lite_capsules = array(
	array(
		'tone'        => '1',
		'category'    => __( 'AI', 'creceweb-lumen-lite' ),
		'duration'    => __( '18 min', 'creceweb-lumen-lite' ),
		'title'       => __( 'Prompts with context', 'creceweb-lumen-lite' ),
		'description' => __( 'Ask fewer times and get answers that are easier to verify.', 'creceweb-lumen-lite' ),
	),
	array(
		'tone'        => '2',
		'category'    => __( 'Code', 'creceweb-lumen-lite' ),
		'duration'    => __( '17 min', 'creceweb-lumen-lite' ),
		'title'       => __( 'Git without drama', 'creceweb-lumen-lite' ),
		'description' => __( 'Branch, commit, merge, and rollback with one simple mental model.', 'creceweb-lumen-lite' ),
	),
	array(
		'tone'        => '3',
		'category'    => __( 'Web', 'creceweb-lumen-lite' ),
		'duration'    => __( '15 min', 'creceweb-lumen-lite' ),
		'title'       => __( 'APIs in 15 minutes', 'creceweb-lumen-lite' ),
		'description' => __( 'Request, response, endpoint, and token in one concrete example.', 'creceweb-lumen-lite' ),
	),
	array(
		'tone'        => '4',
		'category'    => __( 'Design', 'creceweb-lumen-lite' ),
		'duration'    => __( '19 min', 'creceweb-lumen-lite' ),
		'title'       => __( 'Useful accessibility', 'creceweb-lumen-lite' ),
		'description' => __( 'Five decisions that improve navigation, reading, and focus.', 'creceweb-lumen-lite' ),
	),
	array(
		'tone'        => '5',
		'category'    => __( 'Content', 'creceweb-lumen-lite' ),
		'duration'    => __( '17 min', 'creceweb-lumen-lite' ),
		'title'       => __( 'SEO for people', 'creceweb-lumen-lite' ),
		'description' => __( 'Write for an intention before writing for a keyword.', 'creceweb-lumen-lite' ),
	),
	array(
		'tone'        => '6',
		'category'    => __( 'AI', 'creceweb-lumen-lite' ),
		'duration'    => __( '25 min', 'creceweb-lumen-lite' ),
		'title'       => __( 'Your first agent', 'creceweb-lumen-lite' ),
		'description' => __( 'Define the goal, tools, limits, and one test that matters.', 'creceweb-lumen-lite' ),
	),
	array(
		'tone'        => '1',
		'category'    => __( 'Code', 'creceweb-lumen-lite' ),
		'duration'    => __( '16 min', 'creceweb-lumen-lite' ),
		'title'       => __( 'Astro islands', 'creceweb-lumen-lite' ),
		'description' => __( 'Add interaction without shipping the whole page to JavaScript.', 'creceweb-lumen-lite' ),
	),
	array(
		'tone'        => '2',
		'category'    => __( 'Data', 'creceweb-lumen-lite' ),
		'duration'    => __( '20 min', 'creceweb-lumen-lite' ),
		'title'       => __( 'Readable dashboard', 'creceweb-lumen-lite' ),
		'description' => __( 'Use visual hierarchy to decide what deserves attention first.', 'creceweb-lumen-lite' ),
	),
	array(
		'tone'        => '3',
		'category'    => __( 'Design', 'creceweb-lumen-lite' ),
		'duration'    => __( '14 min', 'creceweb-lumen-lite' ),
		'title'       => __( 'Forms that flow', 'creceweb-lumen-lite' ),
		'description' => __( 'Use labels, help, and errors that reduce abandonment.', 'creceweb-lumen-lite' ),
	),
	array(
		'tone'        => '4',
		'category'    => __( 'Automation', 'creceweb-lumen-lite' ),
		'duration'    => __( '21 min', 'creceweb-lumen-lite' ),
		'title'       => __( 'Webhooks without mystery', 'creceweb-lumen-lite' ),
		'description' => __( 'Understand what fires, what travels, and what can fail.', 'creceweb-lumen-lite' ),
	),
	array(
		'tone'        => '5',
		'category'    => __( 'Data', 'creceweb-lumen-lite' ),
		'duration'    => __( '12 min', 'creceweb-lumen-lite' ),
		'title'       => __( 'Choose the chart', 'creceweb-lumen-lite' ),
		'description' => __( 'Use one quick question to avoid bars, pies, and lines without meaning.', 'creceweb-lumen-lite' ),
	),
	array(
		'tone'        => '6',
		'category'    => __( 'Work', 'creceweb-lumen-lite' ),
		'duration'    => __( '18 min', 'creceweb-lumen-lite' ),
		'title'       => __( 'Document decisions', 'creceweb-lumen-lite' ),
		'description' => __( 'Leave context for your future self and the rest of the team.', 'creceweb-lumen-lite' ),
	),
);

$cw_lumen_lite_card_template = <<<'HTML'
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-lesson-card cw-kit-tone-%1$s"} -->
<div class="wp-block-group cw-kit-lesson-card cw-kit-tone-%1$s">
<!-- wp:paragraph {"className":"cw-kit-lesson-card__icon has-text-color"} -->
<p class="cw-kit-lesson-card__icon has-text-color">&#10022;</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-lesson-card__meta"} -->
<p class="cw-kit-lesson-card__meta">%2$s · %3$s</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"cw-kit-lesson-card__title"} -->
<h3 class="wp-block-heading cw-kit-lesson-card__title">%4$s</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"cw-kit-lesson-card__copy"} -->
<p class="cw-kit-lesson-card__copy">%5$s</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-lesson-card__cta"} -->
<p class="cw-kit-lesson-card__cta"><a href="#">%6$s</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
HTML;

$cw_lumen_lite_cards = '';
foreach ( $cw_lumen_lite_capsules as $cw_lumen_lite_capsule ) {
	$cw_lumen_lite_cards .= sprintf(
		$cw_lumen_lite_card_template,
		esc_attr( $cw_lumen_lite_capsule['tone'] ),
		esc_html( $cw_lumen_lite_capsule['category'] ),
		esc_html( $cw_lumen_lite_capsule['duration'] ),
		esc_html( $cw_lumen_lite_capsule['title'] ),
		esc_html( $cw_lumen_lite_capsule['description'] ),
		esc_html__( 'Save for later →', 'creceweb-lumen-lite' )
	);
}

$cw_lumen_lite_markup = <<<'HTML'
<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-hero cw-kit-catalog-hero","align":"full","anchor":"capsules"} -->
<div id="capsules" class="wp-block-group alignfull cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-hero cw-kit-catalog-hero">
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
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-catalog-grid cw-kit-catalog-grid--3"} -->
<div class="wp-block-group cw-kit-catalog-grid cw-kit-catalog-grid--3">{{cw_lumen_cards}}</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
HTML;

$cw_lumen_lite_markup = strtr(
	$cw_lumen_lite_markup,
	array(
		'{{cw_lumen_text_1}}' => esc_html__( 'Capsules', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_2}}' => esc_html__( 'Learn one thing.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_3}}' => esc_html__( 'Use it today.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_4}}' => esc_html__( 'Compact lessons for moments when you do not need another full course; you need to unblock one decision.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_cards}}'  => $cw_lumen_lite_cards,
	)
);

return array(
	'slug' => 'education-capsule-catalog-voltio',
	'family' => 'content',
	'title' => __( 'Education capsule catalog · Voltio', 'creceweb-lumen-lite' ),
	'description' => __( 'Twelve compact editable microlearning cards with category, duration, summary, and call to action.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'capsules', 'creceweb-lumen-lite' ), __( 'microlearning', 'creceweb-lumen-lite' ), __( 'education', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-capsule-catalog-voltio.webp',
	'content' => $cw_lumen_lite_markup,
);
