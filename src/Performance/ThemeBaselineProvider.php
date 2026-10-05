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
		$sections_used = $active && function_exists( 'wp_style_is' )
			&& ( wp_style_is( 'creceweb-lumen-sections', 'done' ) || wp_style_is( 'creceweb-lumen-sections', 'enqueued' ) );
		$navigation_used = $active && function_exists( 'wp_script_is' )
			&& ( wp_script_is( 'creceweb-lumen-navigation', 'done' ) || wp_script_is( 'creceweb-lumen-navigation', 'enqueued' ) );
		$header_used = $active && function_exists( 'wp_script_is' )
			&& ( wp_script_is( 'creceweb-lumen-header', 'done' ) || wp_script_is( 'creceweb-lumen-header', 'enqueued' ) );

		return array(
			$this->rule( 'theme.base', 'frontend_base', 'style', 'creceweb-lumen', $this->primary_theme_asset_size_bytes(), $active, $active, __( 'Theme base stylesheet.', 'creceweb-lumen-lite' ) ),
			$this->rule( 'theme.blog_archive', 'content', 'style', 'creceweb-lumen-blog-archive', $this->preferred_theme_asset_size_bytes( 'assets/css/blog-archive.css' ), $active, $blog_archive_used, __( 'Conditional loading', 'creceweb-lumen-lite' ) ),
			$this->rule( 'theme.single', 'content', 'style', 'creceweb-lumen-single', $this->preferred_theme_asset_size_bytes( 'assets/css/single.css' ), $active, $single_used, __( 'Conditional loading', 'creceweb-lumen-lite' ) ),
			$this->rule( 'theme.comments', 'content', 'style', 'creceweb-lumen-comments', $this->preferred_theme_asset_size_bytes( 'assets/css/comments.css' ), $active, $comments_used, __( 'Conditional loading', 'creceweb-lumen-lite' ) ),
			$this->rule( 'theme.hero', 'hero', 'style', 'creceweb-lumen-hero', $this->preferred_theme_asset_size_bytes( 'assets/css/hero.css' ), $active, $hero_used, __( 'Conditional loading', 'creceweb-lumen-lite' ) ),
			$this->rule( 'theme.sections', 'sections', 'style', 'creceweb-lumen-sections', $this->preferred_theme_asset_size_bytes( 'assets/css/sections.css' ), $active, $sections_used, __( 'Shared Lumen section contract.', 'creceweb-lumen-lite' ) ),
			$this->rule( 'theme.navigation', 'navigation', 'script', 'creceweb-lumen-navigation', $this->preferred_theme_asset_size_bytes( 'assets/js/classic-navigation.js' ), $active, $navigation_used, __( 'Classic navigation runtime.', 'creceweb-lumen-lite' ) ),
			$this->rule( 'theme.header', 'navigation', 'script', 'creceweb-lumen-header', $this->preferred_theme_asset_size_bytes( 'assets/js/header-behavior.js' ), $active, $header_used, __( 'Header behavior runtime.', 'creceweb-lumen-lite' ) ),
			$this->rule( 'theme.customization', 'frontend_base', 'style', 'creceweb-lumen-customization', 0, $active, $active, __( 'Dynamic customization handle with inline CSS.', 'creceweb-lumen-lite' ) ),
		);
	}

	/** @return int */
	private function primary_theme_asset_size_bytes(): int {
		$relative_path = 'assets/css/lumen.css';

		if ( function_exists( '\CreceWeb\Lumen\get_required_frontend_modules' ) && function_exists( '\CreceWeb\Lumen\get_primary_stylesheet_path' ) ) {
			$modules = \CreceWeb\Lumen\get_required_frontend_modules();
			$relative_path = \CreceWeb\Lumen\get_primary_stylesheet_path( is_array( $modules ) ? $modules : array() );
		} else {
			$relative_path = $this->preferred_theme_asset_path( $relative_path );
		}

		return $this->theme_asset_size_bytes( $relative_path );
	}

	/**
	 * @param string $relative_path Source asset path relative to the Theme root.
	 * @return int
	 */
	private function preferred_theme_asset_size_bytes( string $relative_path ): int {
		return $this->theme_asset_size_bytes( $this->preferred_theme_asset_path( $relative_path ) );
	}

	/**
	 * Mirrors the active Theme's production minified-asset selection, with a
	 * local fallback for older compatible Theme versions.
	 *
	 * @param string $relative_path Source asset path relative to the Theme root.
	 * @return string
	 */
	private function preferred_theme_asset_path( string $relative_path ): string {
		if ( function_exists( '\CreceWeb\Lumen\get_preferred_minified_asset_path' ) ) {
			$preferred = \CreceWeb\Lumen\get_preferred_minified_asset_path( $relative_path );
			return is_string( $preferred ) && '' !== $preferred ? $preferred : $relative_path;
		}

		$minified = preg_replace( '/\.(css|js)$/', '.min.$1', $relative_path );
		if ( ! is_string( $minified ) || $minified === $relative_path ) {
			return $relative_path;
		}

		$directory = $this->theme_directory();
		return '' !== $directory && is_file( rtrim( $directory, '/\\' ) . '/' . ltrim( $minified, '/\\' ) )
			? $minified
			: $relative_path;
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
		$directory = $this->theme_directory();

		if ( '' === $directory ) {
			return 0;
		}

		$path = rtrim( $directory, '/\\' ) . '/' . ltrim( $relative_path, '/\\' );
		return is_file( $path ) ? (int) filesize( $path ) : 0;
	}

	/** @return string */
	private function theme_directory(): string {
		if ( defined( 'CRECEWEB_LUMEN_DIR' ) ) {
			return (string) CRECEWEB_LUMEN_DIR;
		}
		return function_exists( 'get_template_directory' ) ? (string) get_template_directory() : '';
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
