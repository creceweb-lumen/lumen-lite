<?php
/**
 * Plugin Name: CreceWeb Lumen Lite
 * Plugin URI: https://creceweb.com.ar/lumen-lite
 * Description: Lumen patterns, reading tools, sharing, floating actions, messaging, menu enhancements, and footer tools for Lumen Theme.
 * Version: 1.0.1
 * Requires at least: 6.7
 * Requires PHP: 8.1
 * Author: CreceWeb
 * Author URI: https://creceweb.com.ar/
 * Text Domain: creceweb-lumen-lite
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package CreceWebLumenLite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CRECEWEB_LUMEN_LITE_VERSION' ) ) {
	return;
}

define( 'CRECEWEB_LUMEN_LITE_VERSION', '1.0.1' );
define( 'CRECEWEB_LUMEN_LITE_ASSET_VERSION', '1.0.1.71' );
define( 'CRECEWEB_LUMEN_PERFORMANCE_API_VERSION', '1.6.1' );
define( 'CRECEWEB_LUMEN_LITE_API_VERSION', '2.4.0' );
define( 'CRECEWEB_LUMEN_LITE_BRIDGE_API_VERSION', '1.5.0' );
define( 'CRECEWEB_LUMEN_LITE_REQUIRED_THEME_VERSION', '1.4.91' );
define( 'CRECEWEB_LUMEN_LITE_FILE', __FILE__ );
define( 'CRECEWEB_LUMEN_LITE_DIR', plugin_dir_path( __FILE__ ) );
define( 'CRECEWEB_LUMEN_LITE_URL', plugin_dir_url( __FILE__ ) );
define( 'CRECEWEB_LUMEN_LITE_BASENAME', plugin_basename( __FILE__ ) );

$creceweb_lumen_lite_files = array(
	'src/Support/Compatibility.php',
	'src/Support/AdminRedirect.php',
	'src/Data/Settings.php',
	'src/Library/IconFactory.php',
	'src/Library/Catalog.php',
	'src/Library/PageCatalog.php',
	'src/Library/Controller.php',
	'src/Footer/Controller.php',
	'src/Content/Controller.php',
	'src/ReadingProgress/Controller.php',
	'src/TableOfContents/Controller.php',
	'src/Sharing/Controller.php',
	'src/RelatedContent/Controller.php',
	'src/Menu/Controller.php',
	'src/FloatingAction/Controller.php',
	'src/Messaging/MessageResolver.php',
	'src/Messaging/UrlResolver.php',
	'src/Messaging/GlyphResolver.php',
	'src/Messaging/StyleResolver.php',
	'src/Messaging/Controller.php',
	'src/Performance/ProfileRegistry.php',
	'src/Performance/API.php',
	'src/Performance/Comparator.php',
	'src/Performance/BudgetEvaluator.php',
	'src/Performance/MvpController.php',
	'src/Admin/Admin.php',
	'src/Plugin.php',
	'src/functions.php',
);

foreach ( $creceweb_lumen_lite_files as $creceweb_lumen_lite_file ) {
	$creceweb_lumen_lite_path = CRECEWEB_LUMEN_LITE_DIR . $creceweb_lumen_lite_file;

	if ( is_readable( $creceweb_lumen_lite_path ) ) {
		require_once $creceweb_lumen_lite_path;
	}
}

register_activation_hook( __FILE__, array( '\\CreceWeb\\LumenLite\\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\\CreceWeb\\LumenLite\\Plugin', 'deactivate' ) );

\CreceWeb\LumenLite\Plugin::boot();
