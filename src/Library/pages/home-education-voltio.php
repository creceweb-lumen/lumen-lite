<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug' => 'home-education-voltio',
	'title' => __( 'Voltio Education home', 'creceweb-lumen-lite' ),
	'description' => __( 'Complete high-energy education home with courses, quick lessons, resources, method, and contact paths.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'education', 'creceweb-lumen-lite' ), __( 'home', 'creceweb-lumen-lite' ), __( 'courses', 'creceweb-lumen-lite' ), __( 'Voltio', 'creceweb-lumen-lite' ) ),
	'preview' => 'home-education-voltio.webp',
	'template' => 'page-templates/full-width.php',
	'content' => $cw_lumen_lite_compose( array( 'kit-ticker', 'education-lab-hero-voltio', 'education-skills-signal-voltio', 'education-courses-voltio', 'education-capsules-voltio', 'education-method-voltio', 'education-resources-voltio', 'education-final-cta-voltio' ) ),
);
