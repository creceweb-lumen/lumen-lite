<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug' => 'business-about-prisma',
	'title' => __( 'Prisma About', 'creceweb-lumen-lite' ),
	'description' => __( 'Business about page with story, process, and roles.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'business', 'creceweb-lumen-lite' ), __( 'Prisma', 'creceweb-lumen-lite' ) ),
	'preview' => 'business-about-prisma.webp',
	'template' => 'page-templates/full-width.php',
	'content' => $cw_lumen_lite_compose( array( 'business-about-prisma' ) ),
);
