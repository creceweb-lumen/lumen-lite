<?php
/** Lumen Posts Library resource. @package CreceWebLumenLite */
if ( ! defined( 'ABSPATH' ) ) { exit; }
return array(
    'slug' => 'posts-grid-popular',
    'family' => 'lumen-posts',
    'title' => __( 'Lumen Posts · Popular posts', 'creceweb-lumen-lite' ),
    'description' => __( 'Insert a popular-posts grid ready to customize.', 'creceweb-lumen-lite' ),
    'keywords' => array( __( 'Posts', 'creceweb-lumen-lite' ), __( 'Popular', 'creceweb-lumen-lite' ), __( 'Grid', 'creceweb-lumen-lite' ) ),
    'content' => '<!-- wp:creceweb-lumen/posts-grid {"useGlobalDefaults":false,"source":"popular","layout":"grid","itemsDesktop":6,"itemsTablet":4,"itemsMobile":3,"columnsDesktop":3,"columnsTablet":2,"columnsMobile":1,"desktop":true,"tablet":true,"mobile":true} /-->',
);
