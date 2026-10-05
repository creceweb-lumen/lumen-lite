<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug' => 'education-courses-voltio-page',
	'title' => __( 'Voltio courses page', 'creceweb-lumen-lite' ),
	'description' => __( 'High-contrast course catalog with routes, levels, durations, and clear actions.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'education', 'creceweb-lumen-lite' ), __( 'courses', 'creceweb-lumen-lite' ), __( 'Voltio', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-course-catalog-voltio.webp',
	'template' => 'page-templates/full-width.php',
	'content' => $cw_lumen_lite_compose( array( 'education-course-catalog-voltio' ) ),
);
