<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug'        => 'home-agency',
	'title'       => __( 'Agency home', 'creceweb-lumen-lite' ),
	'description' => __( 'Complete agency home page assembled from Lumen Lite sections.', 'creceweb-lumen-lite' ),
	'keywords'    => array( __( 'agency', 'creceweb-lumen-lite' ), __( 'home', 'creceweb-lumen-lite' ), __( 'business', 'creceweb-lumen-lite' ) ),
	'preview'     => 'hero-split-simple.avif',
	'content'     => $cw_lumen_lite_compose( array( 'hero-split-simple', 'services-three-columns', 'benefits-checklist', 'metrics-simple', 'testimonials-simple', 'cta-centered', 'contact-info-simple' ) ),
);
