<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug' => 'business-case-detail-prisma',
	'title' => __( 'Prisma Case Detail', 'creceweb-lumen-lite' ),
	'description' => __( 'Business case detail with project context, decisions, process, gallery, and contact CTA.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'business', 'creceweb-lumen-lite' ), __( 'Prisma', 'creceweb-lumen-lite' ) ),
	'preview' => 'business-case-detail-prisma.webp',
	'template' => 'page-templates/full-width.php',
	'content' => $cw_lumen_lite_compose( array( 'business-case-detail-prisma' ) ),
);
