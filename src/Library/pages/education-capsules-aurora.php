<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug' => 'education-capsules-aurora-page',
	'title' => __( 'Aurora quick lessons page', 'creceweb-lumen-lite' ),
	'description' => __( 'Editorial quick lessons catalog with compact notes, topics, durations, and actions.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'education', 'creceweb-lumen-lite' ), __( 'Aurora', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-capsule-catalog-aurora.webp',
	'template' => 'page-templates/full-width.php',
	'content' => $cw_lumen_lite_compose( array( 'education-capsule-catalog-aurora' ) ),
);
