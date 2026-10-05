<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug' => 'education-contact-voltio-page',
	'title' => __( 'Voltio contact page', 'creceweb-lumen-lite' ),
	'description' => __( 'Contact page with context prompts and a flexible form area.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'education', 'creceweb-lumen-lite' ), __( 'contact', 'creceweb-lumen-lite' ), __( 'Voltio', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-contact-voltio.webp',
	'template' => 'page-templates/full-width.php',
	'content' => $cw_lumen_lite_compose( array( 'education-contact-voltio' ) ),
);
