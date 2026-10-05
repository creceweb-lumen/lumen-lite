<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug' => 'education-method-aurora-page',
	'title' => __( 'Aurora method page', 'creceweb-lumen-lite' ),
	'description' => __( 'Editorial learning-method page using indexed process rows and criteria.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'education', 'creceweb-lumen-lite' ), __( 'Aurora', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-method-page-aurora.webp',
	'template' => 'page-templates/full-width.php',
	'content' => $cw_lumen_lite_compose( array( 'education-method-page-aurora' ) ),
);
