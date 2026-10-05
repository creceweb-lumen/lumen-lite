<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug' => 'business-service-detail-prisma',
	'title' => __( 'Prisma Service Detail', 'creceweb-lumen-lite' ),
	'description' => __( 'Business service detail for end-to-end project management.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'business', 'creceweb-lumen-lite' ), __( 'Prisma', 'creceweb-lumen-lite' ) ),
	'preview' => 'business-service-detail-prisma.webp',
	'template' => 'page-templates/full-width.php',
	'content' => $cw_lumen_lite_compose( array( 'business-service-detail-prisma' ) ),
);
