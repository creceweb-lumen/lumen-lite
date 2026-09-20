<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/*
 * Reuse the previously validated editable component: each benefit is a
 * horizontal Group with a replaceable core/icon and a Paragraph. Legacy CSS
 * for .cw-lumen-lite-checklist remains for content inserted by older versions,
 * while new insertions no longer depend on a fixed pseudo-element.
 */
$cw_lumen_lite_benefit = static function ( string $text ): string {
	return implode(
		"\n",
		array(
			'<!-- wp:group {"className":"cw-lumen-lite-benefits__feature","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->',
			'<div class="wp-block-group cw-lumen-lite-benefits__feature">',
			\CreceWeb\LumenLite\Library\IconFactory::block(
				'core/check',
				array( 'cw-lumen-lite-benefits__feature-icon' ),
				'benefit-check',
				array(
					'style' => array(
						'color' => array(
							'background' => '#059669',
							'text'       => '#ffffff',
						),
						'border' => array( 'radius' => '999px' ),
						'spacing' => array(
							'padding' => array(
								'top'    => '0.28rem',
								'right'  => '0.28rem',
								'bottom' => '0.28rem',
								'left'   => '0.28rem',
							),
						),
						'dimensions' => array( 'width' => '1.55rem' ),
					),
				)
			),
			'<!-- wp:paragraph {"className":"cw-lumen-lite-benefits__feature-text"} -->',
			'<p class="cw-lumen-lite-benefits__feature-text">' . esc_html( $text ) . '</p>',
			'<!-- /wp:paragraph -->',
			'</div>',
			'<!-- /wp:group -->',
		)
	);
};

return array(
	'slug' => 'benefits-checklist', 'family' => 'services',
	'title' => __( 'Featured benefits', 'creceweb-lumen-lite' ),
	'description' => __( 'Two-column section connecting a value proposition with concrete benefits.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'benefits', 'creceweb-lumen-lite' ), __( 'checklist', 'creceweb-lumen-lite' ), __( 'value', 'creceweb-lumen-lite' ) ),
	'content' => implode( "\n", array(
		'<!-- wp:group {"align":"full","className":"cw-lumen-section cw-lumen-lite-pattern cw-lumen-lite-pattern--benefits","layout":{"type":"default"}} -->',
		'<div class="wp-block-group alignfull cw-lumen-section cw-lumen-lite-pattern cw-lumen-lite-pattern--benefits">',
		'<!-- wp:group {"className":"cw-lumen-section__inner cw-lumen-lite-pattern__inner","layout":{"type":"default"}} -->',
		'<div class="wp-block-group cw-lumen-section__inner cw-lumen-lite-pattern__inner">',
		'<!-- wp:columns {"verticalAlignment":"center","className":"cw-lumen-section-layout"} -->',
		'<div class="wp-block-columns are-vertically-aligned-center cw-lumen-section-layout">',
		'<!-- wp:column {"verticalAlignment":"center"} -->',
		'<div class="wp-block-column is-vertically-aligned-center">',
		'<!-- wp:group {"className":"cw-lumen-section-heading cw-lumen-lite-section-heading","layout":{"type":"default"}} -->',
		'<div class="wp-block-group cw-lumen-section-heading cw-lumen-lite-section-heading">',
		'<!-- wp:paragraph {"className":"cw-lumen-lite-pattern__eyebrow"} -->',
		'<p class="cw-lumen-lite-pattern__eyebrow">' . esc_html__( 'Why it works', 'creceweb-lumen-lite' ) . '</p>',
		'<!-- /wp:paragraph -->',
		'<!-- wp:heading -->',
		'<h2 class="wp-block-heading">' . esc_html__( 'A clearer experience for you and your clients.', 'creceweb-lumen-lite' ) . '</h2>',
		'<!-- /wp:heading -->',
		'<!-- wp:paragraph {"className":"cw-lumen-lite-pattern__lead"} -->',
		'<p class="cw-lumen-lite-pattern__lead">' . esc_html__( 'Connect your value proposition with practical improvements that are easy to understand.', 'creceweb-lumen-lite' ) . '</p>',
		'<!-- /wp:paragraph -->',
		'</div>',
		'<!-- /wp:group -->',
		'</div>',
		'<!-- /wp:column -->',
		'<!-- wp:column {"verticalAlignment":"center"} -->',
		'<div class="wp-block-column is-vertically-aligned-center">',
		'<!-- wp:group {"className":"cw-lumen-lite-benefits__features","layout":{"type":"default"}} -->',
		'<div class="wp-block-group cw-lumen-lite-benefits__features">',
		$cw_lumen_lite_benefit( __( 'A focused message from the very first screen.', 'creceweb-lumen-lite' ) ),
		$cw_lumen_lite_benefit( __( 'Reusable sections that remain easy to edit.', 'creceweb-lumen-lite' ) ),
		$cw_lumen_lite_benefit( __( 'Layouts that adapt to phones, tablets, and computers.', 'creceweb-lumen-lite' ) ),
		$cw_lumen_lite_benefit( __( 'A consistent foundation for growth.', 'creceweb-lumen-lite' ) ),
		'</div>',
		'<!-- /wp:group -->',
		'</div>',
		'<!-- /wp:column -->',
		'</div>',
		'<!-- /wp:columns -->',
		'</div>',
		'<!-- /wp:group -->',
		'</div>',
		'<!-- /wp:group -->',
	) ),
);
