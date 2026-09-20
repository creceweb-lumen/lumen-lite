<?php
/**
 * Active Lumen Theme baseline adapter.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Performance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ThemeBaselineProvider {
	public const PROVIDER_ID = 'creceweb-lumen-theme';

	/**
     * @param array{name:string,screen_id:string,hook_suffix:string,ready:bool} $context Context.
     * @return array<int,array<string,mixed>>
     */
	public function rules( array $context ): array {
		if ( 'frontend' !== $context['name'] && 'customizer' !== $context['name'] ) {
			return array();
		}

		$active = defined( 'CRECEWEB_LUMEN_VERSION' )
			|| ( function_exists( 'get_template' ) && 'creceweb-lumen' === get_template() );
		$hero_used = $active && function_exists( 'wp_style_is' )
			&& ( wp_style_is( 'creceweb-lumen-hero', 'done' ) || wp_style_is( 'creceweb-lumen-hero', 'enqueued' ) );
		$blog_archive_used = $active && function_exists( 'wp_style_is' )
			&& ( wp_style_is( 'creceweb-lumen-blog-archive', 'done' ) || wp_style_is( 'creceweb-lumen-blog-archive', 'enqueued' ) );
		$single_used = $active && function_exists( 'wp_style_is' )
			&& ( wp_style_is( 'creceweb-lumen-single', 'done' ) || wp_style_is( 'creceweb-lumen-single', 'enqueued' ) );
		$comments_used = $active && function_exists( 'wp_style_is' )
			&& ( wp_style_is( 'creceweb-lumen-comments', 'done' ) || wp_style_is( 'creceweb-lumen-comments', 'enqueued' ) );

		return array(
			$this->rule( 'theme.base', 'frontend_base', 'style', 'creceweb-lumen', $this->theme_asset_size_bytes( 'assets/css/lumen.css' ), $active, $active, __( 'Theme base stylesheet.', 'creceweb-lumen-lite' ) ),
			$this->rule( 'theme.blog_archive', 'content', 'style', 'creceweb-lumen-blog-archive', $this->theme_asset_size_bytes( 'assets/css/blog-archive.css' ), $active, $blog_archive_used, __( 'Conditional loading', 'creceweb-lumen-lite' ) ),
			$this->rule( 'theme.single', 'content', 'style', 'creceweb-lumen-single', $this->theme_asset_size_bytes( 'assets/css/single.css' ), $active, $single_used, __( 'Conditional loading', 'creceweb-lumen-lite' ) ),
			$this->rule( 'theme.comments', 'content', 'style', 'creceweb-lumen-comments', $this->theme_asset_size_bytes( 'assets/css/comments.css' ), $active, $comments_used, __( 'Conditional loading', 'creceweb-lumen-lite' ) ),
			$this->rule( 'theme.hero', 'hero', 'style', 'creceweb-lumen-hero', $this->theme_asset_size_bytes( 'assets/css/hero.css' ), $active, $hero_used, __( 'Conditional loading', 'creceweb-lumen-lite' ) ),
			$this->rule( 'theme.sections', 'sections', 'style', 'creceweb-lumen-sections', $this->theme_asset_size_bytes( 'assets/css/sections.css' ), $active, $active, __( 'Shared Lumen section contract.', 'creceweb-lumen-lite' ) ),
			$this->rule( 'theme.navigation', 'navigation', 'script', 'creceweb-lumen-navigation', $this->theme_asset_size_bytes( 'assets/js/classic-navigation.js' ), $active, $active, __( 'Classic navigation runtime.', 'creceweb-lumen-lite' ) ),
			$this->rule( 'theme.header', 'navigation', 'script', 'creceweb-lumen-header', $this->theme_asset_size_bytes( 'assets/js/header-behavior.js' ), $active, $active, __( 'Header behavior runtime.', 'creceweb-lumen-lite' ) ),
			$this->rule( 'theme.customization', 'frontend_base', 'style', 'creceweb-lumen-customization', 0, $active, $active, __( 'Dynamic customization handle with inline CSS.', 'creceweb-lumen-lite' ) ),
		);
	}

	/**
     * Returns the current byte size of a packaged asset from the active Lumen Theme.
     *
     * Child themes resolve through WordPress' template directory, which points to
     * the active parent Theme that owns these handles.
     *
     * @param string $relative_path Path relative to the Theme root.
     * @return int
     */
	private function theme_asset_size_bytes( string $relative_path ): int {
		$directory = '';
		if ( defined( 'CRECEWEB_LUMEN_DIR' ) ) {
			$directory = (string) CRECEWEB_LUMEN_DIR;
		} elseif ( function_exists( 'get_template_directory' ) ) {
			$directory = (string) get_template_directory();
		}

		if ( '' === $directory ) {
			return 0;
		}

		$path = rtrim( $directory, '/\\' ) . '/' . ltrim( $relative_path, '/\\' );
		return is_file( $path ) ? (int) filesize( $path ) : 0;
	}

	/** @return array<string,mixed> */
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
