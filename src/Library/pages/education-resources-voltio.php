<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug' => 'education-resources-voltio-page',
	'title' => __( 'Voltio resources page', 'creceweb-lumen-lite' ),
	'description' => __( 'Resource library with guides, categories, reading times, and clear actions.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'education', 'creceweb-lumen-lite' ), __( 'resources', 'creceweb-lumen-lite' ), __( 'Voltio', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-resource-catalog-voltio.webp',
	'template' => 'page-templates/full-width.php',
	'content' => $cw_lumen_lite_compose( array( 'education-resource-catalog-voltio' ) ),
);
