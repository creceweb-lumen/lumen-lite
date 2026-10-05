<?php
/** Lumen Posts Library resource. @package CreceWebLumenLite */
if ( ! defined( 'ABSPATH' ) ) { exit; }
return array(
    'slug' => 'posts-grid-compact',
    'family' => 'lumen-posts',
    'title' => __( 'Lumen Posts · Compact list', 'creceweb-lumen-lite' ),
    'description' => __( 'Insert a compact latest-posts list ready to customize.', 'creceweb-lumen-lite' ),
    'keywords' => array( __( 'Posts', 'creceweb-lumen-lite' ), __( 'List', 'creceweb-lumen-lite' ), __( 'Compact', 'creceweb-lumen-lite' ) ),
    'content' => '<!-- wp:creceweb-lumen/posts-grid {"useGlobalDefaults":false,"source":"latest","layout":"list","itemsDesktop":5,"itemsTablet":4,"itemsMobile":3,"columnsDesktop":1,"columnsTablet":1,"columnsMobile":1,"desktop":true,"tablet":true,"mobile":true} /-->',
);
