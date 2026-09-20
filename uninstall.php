<?php
/**
 * Uninstall cleanup for Lumen Lite.
 *
 * @package CreceWebLumenLite
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'cw_lumen_lite_settings' );
delete_post_meta_by_key( '_cw_lumen_lite_menu_button' );
delete_post_meta_by_key( '_cw_lumen_lite_menu_visibility' );

delete_option( 'cw_lumen_lite_starter_site_imports' );
