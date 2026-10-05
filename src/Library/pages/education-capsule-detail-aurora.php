<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug' => 'education-capsule-detail-aurora-page',
	'title' => __( 'Aurora Capsule Detail', 'creceweb-lumen-lite' ),
	'description' => __( 'Quick lesson detail page with explanation, key idea, example, exercise, and next action.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'education', 'creceweb-lumen-lite' ), __( 'Aurora', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-capsule-detail-aurora.webp',
	'template' => 'page-templates/full-width.php',
	'content' => $cw_lumen_lite_compose( array( 'education-capsule-detail-aurora' ) ),
);
