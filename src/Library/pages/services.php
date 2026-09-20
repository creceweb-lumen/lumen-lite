<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug'        => 'services',
	'title'       => __( 'Services page', 'creceweb-lumen-lite' ),
	'description' => __( 'Complete services page with offerings, process, answers, and a call to action.', 'creceweb-lumen-lite' ),
	'keywords'    => array( __( 'services', 'creceweb-lumen-lite' ), __( 'process', 'creceweb-lumen-lite' ), __( 'faq', 'creceweb-lumen-lite' ) ),
	'preview'     => 'services-three-columns.avif',
	'content'     => $cw_lumen_lite_compose( array( 'hero-base', 'services-three-columns', 'process-three-steps', 'faq-simple', 'cta-centered' ) ),
);
