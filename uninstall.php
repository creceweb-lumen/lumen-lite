<?php
/**
 * Uninstall cleanup for Lumen Lite.
 *
 * @package CreceWebLumenLite
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Only the canonical WordPress.org slug may remove shared plugin data. A ZIP
// installed under a duplicate folder must never destroy state used by the
// canonical copy.
if ( 'creceweb-lumen-lite/creceweb-lumen-lite.php' !== (string) WP_UNINSTALL_PLUGIN ) {
	return;
}

delete_option( 'cw_lumen_lite_settings' );
delete_option( 'cw_lumen_lite_popular_content_cache_generation' );
delete_post_meta_by_key( '_cw_lumen_lite_menu_button' );
delete_post_meta_by_key( '_cw_lumen_lite_menu_visibility' );

delete_option( 'cw_lumen_lite_starter_site_imports' );
