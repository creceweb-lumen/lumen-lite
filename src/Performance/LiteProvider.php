<?php
/**
 * Exact rule provider for Lumen Lite 1.0.1.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Performance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LiteProvider {
	public const PROVIDER_ID = 'creceweb-lumen-lite';

	public function __construct( private object $plugin ) {}

	/**
	 * @param array{name:string,screen_id:string,hook_suffix:string,ready:bool} $context Context.
	 * @return array<int,array<string,mixed>>
	 */
	public function rules( array $context ): array {
		$name       = (string) $context['name'];
		$compatible = method_exists( $this->plugin, 'compatibility' )
			&& $this->plugin->compatibility()->is_compatible();

		$rules = array();

		if ( 'frontend' === $name || 'customizer' === $name ) {
			$library_used = method_exists( $this->plugin, 'library' )
				&& $this->plugin->library()->needs_assets();
			$content_used = method_exists( $this->plugin, 'content' )
				&& $this->plugin->content()->needs_assets();
			$menu_used = method_exists( $this->plugin, 'menu' )
				&& $this->plugin->menu()->needs_assets();

			$reading_progress_configured = false;
			$reading_progress_used       = false;
			$reading_progress_style_size = 0;
			$reading_progress_script_size = 0;
			if ( method_exists( $this->plugin, 'reading_progress' ) ) {
				$config = $this->plugin->reading_progress()->settings();
				$reading_progress_configured = ! empty( $config['enabled'] );
				$reading_progress_used       = $this->plugin->reading_progress()->should_render();
				$reading_progress_style_size = $this->plugin->reading_progress()->style_size_bytes();
				$reading_progress_script_size = $this->plugin->reading_progress()->script_size_bytes();
			}

			$toc_configured = false;
			$toc_used       = false;
			$toc_style_size = 0;
			if ( method_exists( $this->plugin, 'table_of_contents' ) ) {
				$config = $this->plugin->table_of_contents()->settings();
				$toc_configured = ! empty( $config['enabled'] );
				$toc_used       = $this->plugin->table_of_contents()->should_render();
				$toc_style_size = $this->plugin->table_of_contents()->style_size_bytes();
			}

			$sharing_configured = false;
			$sharing_used       = false;
			$sharing_style_size = 0;
			$sharing_script_size = 0;
			$sharing_script_configured = false;
			$sharing_script_used = false;
			if ( method_exists( $this->plugin, 'sharing' ) ) {
				$config = $this->plugin->sharing()->settings();
				$sharing_configured = ! empty( $config['enabled'] );
				$sharing_used       = $this->plugin->sharing()->should_render();
				$sharing_style_size = $this->plugin->sharing()->style_size_bytes();
				$sharing_script_size = $this->plugin->sharing()->script_size_bytes();
				$sharing_script_configured = $sharing_configured && ! empty( $config['actions']['copy'] );
				$sharing_script_used = $sharing_used && ! empty( $config['actions']['copy'] );
			}

			$related_content_configured = false;
			$related_content_used       = false;
			$related_content_style_size = 0;
			if ( method_exists( $this->plugin, 'related_content' ) ) {
				$config = $this->plugin->related_content()->settings();
				$related_content_configured = ! empty( $config['enabled'] );
				$related_content_used       = $this->plugin->related_content()->needs_assets();
				$related_content_style_size = $this->plugin->related_content()->style_size_bytes();
			}

			$floating_configured = false;
			$floating_used       = false;
			$floating_style_size = 0;
			$floating_script_size = 0;
			if ( method_exists( $this->plugin, 'floating_action' ) ) {
				$config = $this->plugin->floating_action()->config();
				$floating_configured = ! empty( $config['enabled'] );
				$floating_used       = $this->plugin->floating_action()->should_render();
				$floating_style_size = method_exists( $this->plugin->floating_action(), 'style_size_bytes' )
					? $this->plugin->floating_action()->style_size_bytes()
					: 0;
				$floating_script_size = method_exists( $this->plugin->floating_action(), 'script_size_bytes' )
					? $this->plugin->floating_action()->script_size_bytes()
					: 0;
			}

			$messaging_configured = false;
			$messaging_used       = false;
			$messaging_size       = 0;
			if ( method_exists( $this->plugin, 'messaging' ) ) {
				$config = $this->plugin->messaging()->config();
				$messaging_configured = ! empty( $config['enabled'] ) && ! empty( $config['url'] );
				$messaging_used       = $this->plugin->messaging()->should_render();
				$messaging_size       = method_exists( $this->plugin->messaging(), 'style_size_bytes' )
					? $this->plugin->messaging()->style_size_bytes()
					: 0;
			}

			$rules[] = $this->rule( 'lite.frontend', 'library', 'style', 'creceweb-lumen-lite-frontend', $this->asset_size_bytes( 'assets/css/frontend.css' ), $compatible, $library_used, __( 'Lite pattern markers in the current content.', 'creceweb-lumen-lite' ) );
			$rules[] = $this->rule( 'lite.content', 'content', 'style', 'creceweb-lumen-lite-content', $this->asset_size_bytes( 'assets/css/content.css' ), $compatible, $content_used, __( 'Content tools report frontend assets needed.', 'creceweb-lumen-lite' ) );
			$rules[] = $this->rule( 'lite.menu', 'menu', 'style', 'creceweb-lumen-lite-menu', $this->asset_size_bytes( 'assets/css/menu.css' ), $compatible, $menu_used, __( 'Menu metadata requires Lite presentation.', 'creceweb-lumen-lite' ) );
			$rules[] = $this->rule( 'lite.reading_progress_style', 'reading_progress', 'style', 'creceweb-lumen-lite-reading-progress', $reading_progress_style_size, $reading_progress_configured, $reading_progress_used, __( 'Reading Progress is enabled for the current public content type.', 'creceweb-lumen-lite' ) );
			$rules[] = $this->rule( 'lite.reading_progress_script', 'reading_progress', 'script', 'creceweb-lumen-lite-reading-progress', $reading_progress_script_size, $reading_progress_configured, $reading_progress_used, __( 'Reading Progress updates the current page completion value while scrolling.', 'creceweb-lumen-lite' ) );
			$rules[] = $this->rule( 'lite.table_of_contents_style', 'table_of_contents', 'style', 'creceweb-lumen-lite-table-of-contents', $toc_style_size, $toc_configured, $toc_used, __( 'Table of Contents is enabled for the current public content type.', 'creceweb-lumen-lite' ) );
			$rules[] = $this->rule( 'lite.sharing_style', 'sharing', 'style', 'creceweb-lumen-lite-sharing', $sharing_style_size, $sharing_configured, $sharing_used, __( 'Sharing is enabled for the current public content type.', 'creceweb-lumen-lite' ) );
			$rules[] = $this->rule( 'lite.sharing_script', 'sharing', 'script', 'creceweb-lumen-lite-sharing', $sharing_script_size, $sharing_script_configured, $sharing_script_used, __( 'Sharing uses local JavaScript only when the Copy link action is available.', 'creceweb-lumen-lite' ) );
			$rules[] = $this->rule( 'lite.related_content_style', 'related_content', 'style', 'creceweb-lumen-lite-related-content', $related_content_style_size, $related_content_configured, $related_content_used, __( 'Related Content is enabled for the current public content type.', 'creceweb-lumen-lite' ) );
			$rules[] = $this->rule( 'lite.floating_action_style', 'floating_action', 'style', 'creceweb-lumen-lite-floating-action', $floating_style_size, $floating_configured, $floating_used, __( 'Advanced global floating action is enabled for this request.', 'creceweb-lumen-lite' ) );
			$rules[] = $this->rule( 'lite.floating_action_script', 'floating_action', 'script', 'creceweb-lumen-lite-floating-action', $floating_script_size, $floating_configured, $floating_used, __( 'Advanced global floating action behavior is enabled for this request.', 'creceweb-lumen-lite' ) );
			$rules[] = $this->rule( 'lite.messaging', 'messaging', 'style', 'creceweb-lumen-lite-messaging', $messaging_size, $messaging_configured, $messaging_used, __( 'Global messaging resolved for this request; presentation is emitted through a source-less WordPress style handle.', 'creceweb-lumen-lite' ) );
		}

		if ( 'block_editor' === $name ) {
			$rules[] = $this->rule( 'lite.editor_content', 'library', 'style', 'creceweb-lumen-lite-editor-content', $this->asset_size_bytes( 'assets/css/frontend.css' ), $compatible, $compatible, __( 'Shared Lite pattern runtime inside Gutenberg.', 'creceweb-lumen-lite' ) );
			$rules[] = $this->rule( 'lite.editor_patterns', 'library', 'style', 'creceweb-lumen-lite-editor-patterns', $this->asset_size_bytes( 'assets/css/editor-patterns.css' ), $compatible, $compatible, __( 'Gutenberg parity and native-alignment layer.', 'creceweb-lumen-lite' ) );
			$rules[] = $this->rule( 'lite.library_browser_style', 'library', 'style', 'creceweb-lumen-library-browser', $this->asset_size_bytes( 'assets/css/library-browser.css' ), $compatible, $compatible, __( 'Visual Library browser.', 'creceweb-lumen-lite' ) );
			$rules[] = $this->rule( 'lite.library_browser_script', 'library', 'script', 'creceweb-lumen-library-browser', $this->asset_size_bytes( 'assets/js/library-browser.js' ), $compatible, $compatible, __( 'Visual Library browser.', 'creceweb-lumen-lite' ) );
			$rules[] = $this->rule( 'lite.pattern_controls', 'library', 'script', 'creceweb-lumen-lite-pattern-controls', $this->asset_size_bytes( 'assets/js/pattern-controls.js' ), $compatible, $compatible, __( 'Native pattern controls.', 'creceweb-lumen-lite' ) );
		}

		if ( 'admin_lumen' === $name || 'admin_other' === $name ) {
			$hook = (string) $context['hook_suffix'];
			$screen = (string) $context['screen_id'];
			$is_menu_screen = 'nav-menus.php' === $hook || 'nav-menus' === $screen;
			$is_lumen_screen = str_contains( $screen, 'creceweb-lumen' ) || str_contains( $hook, 'creceweb-lumen' );

			$rules[] = $this->rule( 'lite.admin_style', 'admin', 'style', 'creceweb-lumen-lite-admin', $this->asset_size_bytes( 'assets/css/admin.css' ), true, $is_menu_screen || $is_lumen_screen, __( 'Lumen settings or nav-menus screen.', 'creceweb-lumen-lite' ) );
		}

		return $rules;
	}

	/**
	 * Return the current packaged byte size for a local Lite asset.
	 *
	 * @param string $relative_path Path relative to the plugin root.
	 * @return int
	 */
	private function asset_size_bytes( string $relative_path ): int {
		$path = CRECEWEB_LUMEN_LITE_DIR . ltrim( $relative_path, '/\\' );

		return is_file( $path ) ? (int) filesize( $path ) : 0;
	}

	/**
	 * @return array<string,mixed>
	 */
	private function rule( string $id, string $module, string $type, string $handle, int $size, bool $configured, bool $used, string $reason ): array {
		return array(
			'id'         => $id,
			'provider'   => self::PROVIDER_ID,
			'module'     => $module,
			'type'       => $type,
			'handle'     => $handle,
			'size_bytes' => $size,
			'configured' => $configured,
			'used'       => $used,
			'reason'     => $reason,
		);
	}
}
