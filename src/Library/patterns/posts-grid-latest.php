<?php
/** Lumen Posts Library resource. @package CreceWebLumenLite */
if ( ! defined( 'ABSPATH' ) ) { exit; }
return array(
    'slug' => 'posts-grid-latest',
    'family' => 'lumen-posts',
    'title' => __( 'Lumen Posts · Latest posts', 'creceweb-lumen-lite' ),
    'description' => __( 'Insert a Lumen Posts grid that follows your global defaults.', 'creceweb-lumen-lite' ),
    'keywords' => array( __( 'Posts', 'creceweb-lumen-lite' ), __( 'Grid', 'creceweb-lumen-lite' ), __( 'Latest', 'creceweb-lumen-lite' ) ),
    'content' => '<!-- wp:creceweb-lumen/posts-grid {"useGlobalDefaults":true} /-->',
);
