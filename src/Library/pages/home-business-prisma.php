<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug' => 'home-business-prisma',
	'title' => __( 'Prisma Business home', 'creceweb-lumen-lite' ),
	'description' => __( 'Complete business home with services, case studies, process, testimonials, FAQ, and contact paths.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'business', 'creceweb-lumen-lite' ), __( 'Prisma', 'creceweb-lumen-lite' ) ),
	'preview' => 'business-home-prisma.webp',
	'template' => 'page-templates/full-width.php',
	'content' => $cw_lumen_lite_compose( array( 'business-home-prisma' ) ),
);
