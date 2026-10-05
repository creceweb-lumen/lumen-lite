<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug' => 'education-course-detail-aurora-page',
	'title' => __( 'Aurora Course Detail', 'creceweb-lumen-lite' ),
	'description' => __( 'Course detail page with outcomes, modules, route context, and a final project.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'education', 'creceweb-lumen-lite' ), __( 'Aurora', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-course-detail-aurora.webp',
	'template' => 'page-templates/full-width.php',
	'content' => $cw_lumen_lite_compose( array( 'education-course-detail-aurora' ) ),
);
