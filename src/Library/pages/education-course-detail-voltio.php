<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug' => 'education-course-detail-voltio-page',
	'title' => __( 'Voltio Course Detail', 'creceweb-lumen-lite' ),
	'description' => __( 'Course detail page with outcomes, modules, a final project, and a closing action.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'education', 'creceweb-lumen-lite' ), __( 'course detail', 'creceweb-lumen-lite' ), __( 'Voltio', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-course-detail-voltio.webp',
	'template' => 'page-templates/full-width.php',
	'content' => $cw_lumen_lite_compose( array( 'education-course-detail-voltio' ) ),
);
