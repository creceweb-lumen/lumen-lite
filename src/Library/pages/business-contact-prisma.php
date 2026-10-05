<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_compose = require __DIR__ . '/_compose.php';
return array(
	'slug' => 'business-contact-prisma',
	'title' => __( 'Prisma Contact', 'creceweb-lumen-lite' ),
	'description' => __( 'Business contact page with a contextual checklist and a ready-to-connect form area.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'business', 'creceweb-lumen-lite' ), __( 'Prisma', 'creceweb-lumen-lite' ) ),
	'preview' => 'business-contact-prisma.webp',
	'template' => 'page-templates/full-width.php',
	'content' => $cw_lumen_lite_compose( array( 'business-contact-prisma' ) ),
);
