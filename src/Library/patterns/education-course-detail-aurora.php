<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_aurora_definition = require __DIR__ . '/education-course-detail-voltio.php';
if ( ! is_array( $cw_lumen_aurora_definition ) ) { return array(); }
$cw_lumen_aurora_detail_hero = require __DIR__ . '/../support/aurora-detail-hero.php';
if ( ! is_callable( $cw_lumen_aurora_detail_hero ) ) { return array(); }
$cw_lumen_aurora_definition['slug'] = 'education-course-detail-aurora';
$cw_lumen_aurora_definition['title'] = __( 'Education course detail · Aurora', 'creceweb-lumen-lite' );
$cw_lumen_aurora_definition['description'] = __( 'Course detail with outcomes, modules, a final project, and a closing action.', 'creceweb-lumen-lite' );
$cw_lumen_aurora_definition['keywords'] = array( __( 'course detail', 'creceweb-lumen-lite' ), __( 'education', 'creceweb-lumen-lite' ), __( 'Aurora', 'creceweb-lumen-lite' ) );
$cw_lumen_aurora_definition['preview'] = 'education-course-detail-aurora.webp';
$cw_lumen_aurora_content = str_replace( array( 'cw-lumen-kit-profile--voltio', '⚡' ), array( 'cw-lumen-kit-profile--aurora', '↗' ), (string) $cw_lumen_aurora_definition['content'] );
$cw_lumen_aurora_definition['content'] = $cw_lumen_aurora_detail_hero( $cw_lumen_aurora_content, 'course-detail', '01' );
return $cw_lumen_aurora_definition;
