<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug' => 'education-resource-detail-aurora-page',
	'title' => __( 'Aurora Resource Detail', 'creceweb-lumen-lite' ),
	'description' => __( 'Long-form resource page with guide content, key points, practical application, and related items.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'education', 'creceweb-lumen-lite' ), __( 'Aurora', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-resource-detail-aurora.webp',
	'template' => 'page-templates/full-width.php',
	'content' => $cw_lumen_lite_compose( array( 'education-resource-detail-aurora' ) ),
);
