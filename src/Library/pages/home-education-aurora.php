<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug' => 'home-education-aurora',
	'title' => __( 'Aurora Education home', 'creceweb-lumen-lite' ),
	'description' => __( 'Complete editorial education home with courses, quick lessons, resources, method, and contact paths.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'education', 'creceweb-lumen-lite' ), __( 'Aurora', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-home-aurora.webp',
	'template' => 'page-templates/full-width.php',
	'content' => $cw_lumen_lite_compose( array( 'education-home-aurora' ) ),
);
