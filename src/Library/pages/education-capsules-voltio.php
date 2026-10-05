<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug' => 'education-capsules-voltio-page',
	'title' => __( 'Voltio quick lessons page', 'creceweb-lumen-lite' ),
	'description' => __( 'Quick lessons catalog for focused, practical microlearning.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'education', 'creceweb-lumen-lite' ), __( 'quick lessons', 'creceweb-lumen-lite' ), __( 'Voltio', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-capsule-catalog-voltio.webp',
	'template' => 'page-templates/full-width.php',
	'content' => $cw_lumen_lite_compose( array( 'education-capsule-catalog-voltio' ) ),
);
