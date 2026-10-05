<?php
/**
 * Education contact · Voltio.
 *
 * @package CreceWebLumenLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$cw_lumen_lite_contact_items = array(
	array(
		'symbol' => '◎',
		'title'  => __( 'Interest', 'creceweb-lumen-lite' ),
		'copy'   => __( 'Which skill, course, or proposal brought you here?', 'creceweb-lumen-lite' ),
	),
	array(
		'symbol' => '↺',
		'title'  => __( 'Context', 'creceweb-lumen-lite' ),
		'copy'   => __( 'Are you asking for yourself, for a team, or for an organization?', 'creceweb-lumen-lite' ),
	),
	array(
		'symbol' => '▣',
		'title'  => __( 'Timing', 'creceweb-lumen-lite' ),
		'copy'   => __( 'Is there a date or a concrete need we should know about?', 'creceweb-lumen-lite' ),
	),
);

$cw_lumen_lite_contact_item_template = <<<'HTML'
<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"},"className":"cw-kit-contact-info__item"} -->
<div class="wp-block-group cw-kit-contact-info__item">
<!-- wp:paragraph {"className":"cw-kit-contact-info__icon has-text-color"} -->
<p class="cw-kit-contact-info__icon has-text-color" aria-hidden="true">%1$s</p>
<!-- /wp:paragraph -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-contact-info__body"} -->
<div class="wp-block-group cw-kit-contact-info__body">
<!-- wp:heading {"level":3,"className":"cw-kit-contact-info__title"} -->
<h3 class="wp-block-heading cw-kit-contact-info__title">%2$s</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"cw-kit-contact-info__copy"} -->
<p class="cw-kit-contact-info__copy">%3$s</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
HTML;

$cw_lumen_lite_contact_items_markup = '';
foreach ( $cw_lumen_lite_contact_items as $cw_lumen_lite_contact_item ) {
	$cw_lumen_lite_contact_items_markup .= sprintf(
		$cw_lumen_lite_contact_item_template,
		esc_html( $cw_lumen_lite_contact_item['symbol'] ),
		esc_html( $cw_lumen_lite_contact_item['title'] ),
		esc_html( $cw_lumen_lite_contact_item['copy'] )
	);
}

$cw_lumen_lite_markup = <<<'HTML'
<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-hero cw-kit-catalog-hero","align":"full","anchor":"contact"} -->
<div id="contact" class="wp-block-group alignfull cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-hero cw-kit-catalog-hero">
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
<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-contact-section","align":"full"} -->
<div class="wp-block-group alignfull cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section cw-kit-contact-section">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell"} -->
<div class="wp-block-group cw-kit-shell">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-contact-grid"} -->
<div class="wp-block-group cw-kit-contact-grid">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-contact-info"} -->
<div class="wp-block-group cw-kit-contact-info">
<!-- wp:paragraph {"className":"cw-kit-contact-info__eyebrow has-text-color"} -->
<p class="cw-kit-contact-info__eyebrow has-text-color">{{cw_lumen_text_5}}</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"className":"cw-kit-contact-info__heading"} -->
<h2 class="wp-block-heading cw-kit-contact-info__heading">{{cw_lumen_text_6}}</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"cw-kit-contact-info__intro"} -->
<p class="cw-kit-contact-info__intro">{{cw_lumen_text_7}}</p>
<!-- /wp:paragraph -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-contact-info__list"} -->
<div class="wp-block-group cw-kit-contact-info__list">{{cw_lumen_contact_items}}</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-contact-response"} -->
<div class="wp-block-group cw-kit-contact-response">
<!-- wp:paragraph {"className":"cw-kit-contact-response__label has-text-color"} -->
<p class="cw-kit-contact-response__label has-text-color">{{cw_lumen_text_8}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-contact-response__copy"} -->
<p class="cw-kit-contact-response__copy">{{cw_lumen_text_9}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-contact-form-card"} -->
<div class="wp-block-group cw-kit-contact-form-card">
<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"},"className":"cw-kit-contact-form-card__head"} -->
<div class="wp-block-group cw-kit-contact-form-card__head">
<!-- wp:paragraph {"className":"cw-kit-contact-form-card__icon has-text-color"} -->
<p class="cw-kit-contact-form-card__icon has-text-color" aria-hidden="true">▣</p>
<!-- /wp:paragraph -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-contact-form-card__heading"} -->
<div class="wp-block-group cw-kit-contact-form-card__heading">
<!-- wp:heading {"level":2,"className":"cw-kit-contact-form-card__title"} -->
<h2 class="wp-block-heading cw-kit-contact-form-card__title">{{cw_lumen_text_10}}</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"cw-kit-contact-form-card__copy"} -->
<p class="cw-kit-contact-form-card__copy">{{cw_lumen_text_11}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-form-slot cw-kit-contact-form-slot"} -->
<div class="wp-block-group cw-kit-form-slot cw-kit-contact-form-slot">
<!-- wp:shortcode -->

<!-- /wp:shortcode -->
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
		'{{cw_lumen_text_1}}'       => esc_html__( 'Contact', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_2}}'       => esc_html__( 'Bring a question.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_3}}'       => esc_html__( 'We bring the energy.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_4}}'       => esc_html__( 'Questions about courses, ideas for teams, partnerships, or something that does not fit a category yet.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_5}}'       => esc_html__( 'To answer better', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_6}}'       => esc_html__( 'Tell us where you are starting.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_7}}'       => esc_html__( 'A little context helps us understand your question before reading the first line of the message.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_8}}'       => esc_html__( 'Human response', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_9}}'       => esc_html__( 'Every message is read by a person. Use the site messaging action if that channel is more convenient.', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_10}}'      => esc_html__( 'Your message', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_11}}'      => esc_html__( "Complete the form and we'll get back to you as soon as possible.", 'creceweb-lumen-lite' ),
		'{{cw_lumen_contact_items}}' => $cw_lumen_lite_contact_items_markup,
	)
);

return array(
	'slug' => 'education-contact-voltio',
	'family' => 'conversion',
	'title' => __( 'Education contact · Voltio', 'creceweb-lumen-lite' ),
	'description' => __( 'Editable contact landing section with an information panel and a neutral shortcode form slot.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'contact', 'creceweb-lumen-lite' ), __( 'education', 'creceweb-lumen-lite' ), __( 'inquiry', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-contact-voltio.webp',
	'content' => $cw_lumen_lite_markup,
);
