<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug'        => 'contact',
	'title'       => __( 'Contact page', 'creceweb-lumen-lite' ),
	'description' => __( 'Complete contact page with clear contact details and common questions.', 'creceweb-lumen-lite' ),
	'keywords'    => array( __( 'contact', 'creceweb-lumen-lite' ), __( 'email', 'creceweb-lumen-lite' ), __( 'phone', 'creceweb-lumen-lite' ) ),
	'preview'     => 'contact-info-simple.avif',
	'content'     => $cw_lumen_lite_compose( array( 'hero-base', 'contact-info-simple', 'faq-simple' ) ),
);
