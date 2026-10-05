<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug' => 'business-cases-prisma',
	'title' => __( 'Prisma Cases', 'creceweb-lumen-lite' ),
	'description' => __( 'Business cases hub with three alternating example project stories.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'business', 'creceweb-lumen-lite' ), __( 'Prisma', 'creceweb-lumen-lite' ) ),
	'preview' => 'business-cases-prisma.webp',
	'template' => 'page-templates/full-width.php',
	'content' => $cw_lumen_lite_compose( array( 'business-cases-prisma' ) ),
);
