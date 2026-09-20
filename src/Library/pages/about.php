<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug'        => 'about',
	'title'       => __( 'About page', 'creceweb-lumen-lite' ),
	'description' => __( 'Complete about page with value, metrics, testimonials, and a closing action.', 'creceweb-lumen-lite' ),
	'keywords'    => array( __( 'about', 'creceweb-lumen-lite' ), __( 'company', 'creceweb-lumen-lite' ), __( 'team', 'creceweb-lumen-lite' ) ),
	'preview'     => 'hero-base.avif',
	'content'     => $cw_lumen_lite_compose( array( 'hero-base', 'benefits-checklist', 'metrics-simple', 'testimonials-simple', 'cta-centered' ) ),
);
