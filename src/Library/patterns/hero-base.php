<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
return array(
	'slug' => 'hero-base', 'family' => 'heroes',
	'title' => __( 'Main presentation', 'creceweb-lumen-lite' ),
	'description' => __( 'Centered header with a title, introduction, and two buttons.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'hero', 'creceweb-lumen-lite' ), __( 'landing', 'creceweb-lumen-lite' ), __( 'introduction', 'creceweb-lumen-lite' ) ),
	'content' => implode( "\n", array(
		'<!-- wp:group {"align":"full","className":"cw-lumen-section cw-lumen-lite-pattern cw-lumen-lite-pattern--hero-base cw-lumen-hero","layout":{"type":"default"}} -->',
		'<div class="wp-block-group alignfull cw-lumen-section cw-lumen-lite-pattern cw-lumen-lite-pattern--hero-base cw-lumen-hero">',
		'<!-- wp:group {"className":"cw-lumen-section__inner cw-lumen-lite-pattern__inner cw-lumen-lite-hero__inner","layout":{"type":"default"}} -->', '<div class="wp-block-group cw-lumen-section__inner cw-lumen-lite-pattern__inner cw-lumen-lite-hero__inner">',
		'<!-- wp:paragraph {"className":"cw-lumen-lite-pattern__eyebrow"} -->', '<p class="cw-lumen-lite-pattern__eyebrow">' . esc_html__( 'A clear starting point', 'creceweb-lumen-lite' ) . '</p>', '<!-- /wp:paragraph -->',
		'<!-- wp:heading {"level":1,"textAlign":"center","textColor":"inverse","fontSize":"h1","className":"cw-lumen-lite-hero__title"} -->', '<h1 class="wp-block-heading has-text-align-center has-inverse-color has-text-color has-h-1-font-size cw-lumen-lite-hero__title">' . esc_html__( 'A website that explains your value.', 'creceweb-lumen-lite' ) . '</h1>', '<!-- /wp:heading -->',
		'<!-- wp:paragraph {"align":"center","className":"cw-lumen-lite-pattern__lead"} -->', '<p class="has-text-align-center cw-lumen-lite-pattern__lead">' . esc_html__( 'Present your value proposition with a concise message and a clear next step.', 'creceweb-lumen-lite' ) . '</p>', '<!-- /wp:paragraph -->',
		'<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"className":"cw-lumen-lite-pattern__buttons"} -->', '<div class="wp-block-buttons cw-lumen-lite-pattern__buttons is-content-justification-center"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#contact">' . esc_html__( 'Start a conversation', 'creceweb-lumen-lite' ) . '</a></div><!-- /wp:button --><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#services">' . esc_html__( 'View services', 'creceweb-lumen-lite' ) . '</a></div><!-- /wp:button --></div>', '<!-- /wp:buttons -->',
		'</div>', '<!-- /wp:group -->', '</div>', '<!-- /wp:group -->'
	) ),
);
