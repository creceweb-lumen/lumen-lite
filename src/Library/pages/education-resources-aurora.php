<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug' => 'education-resources-aurora-page',
	'title' => __( 'Aurora resources page', 'creceweb-lumen-lite' ),
	'description' => __( 'Editorial resource library with guides, metadata, summaries, and reading actions.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'education', 'creceweb-lumen-lite' ), __( 'Aurora', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-resource-catalog-aurora.webp',
	'template' => 'page-templates/full-width.php',
	'content' => $cw_lumen_lite_compose( array( 'education-resource-catalog-aurora' ) ),
);
