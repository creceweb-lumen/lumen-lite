<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug' => 'education-capsule-detail-voltio-page',
	'title' => __( 'Voltio Quick Lesson Detail', 'creceweb-lumen-lite' ),
	'description' => __( 'Quick lesson detail page with explanation, key idea, example, exercise, and next action.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'education', 'creceweb-lumen-lite' ), __( 'quick lesson detail', 'creceweb-lumen-lite' ), __( 'Voltio', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-capsule-detail-voltio.webp',
	'template' => 'page-templates/full-width.php',
	'content' => $cw_lumen_lite_compose( array( 'education-capsule-detail-voltio' ) ),
);
