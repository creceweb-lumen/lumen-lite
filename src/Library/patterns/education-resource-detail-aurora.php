<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_aurora_definition = require __DIR__ . '/education-resource-detail-voltio.php';
if ( ! is_array( $cw_lumen_aurora_definition ) ) { return array(); }
$cw_lumen_aurora_detail_hero = require __DIR__ . '/../support/aurora-detail-hero.php';
if ( ! is_callable( $cw_lumen_aurora_detail_hero ) ) { return array(); }
$cw_lumen_aurora_definition['slug'] = 'education-resource-detail-aurora';
$cw_lumen_aurora_definition['title'] = __( 'Education resource detail · Aurora', 'creceweb-lumen-lite' );
$cw_lumen_aurora_definition['description'] = __( 'Resource detail with guide content, key points, practical application, related items, and a closing action.', 'creceweb-lumen-lite' );
$cw_lumen_aurora_definition['keywords'] = array( __( 'resource detail', 'creceweb-lumen-lite' ), __( 'education', 'creceweb-lumen-lite' ), __( 'Aurora', 'creceweb-lumen-lite' ) );
$cw_lumen_aurora_definition['preview'] = 'education-resource-detail-aurora.webp';
$cw_lumen_aurora_content = str_replace( array( 'cw-lumen-kit-profile--voltio', '⚡' ), array( 'cw-lumen-kit-profile--aurora', '↗' ), (string) $cw_lumen_aurora_definition['content'] );
$cw_lumen_aurora_definition['content'] = $cw_lumen_aurora_detail_hero( $cw_lumen_aurora_content, 'resource-detail', '03' );
return $cw_lumen_aurora_definition;
