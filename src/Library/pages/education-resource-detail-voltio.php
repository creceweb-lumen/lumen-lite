<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug' => 'education-resource-detail-voltio-page',
	'title' => __( 'Voltio Resource Detail', 'creceweb-lumen-lite' ),
	'description' => __( 'Resource detail page with guide content, key points, practical application, related items, and a closing action.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'education', 'creceweb-lumen-lite' ), __( 'resource detail', 'creceweb-lumen-lite' ), __( 'Voltio', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-resource-detail-voltio.webp',
	'template' => 'page-templates/full-width.php',
	'content' => $cw_lumen_lite_compose( array( 'education-resource-detail-voltio' ) ),
);
