<?php
/**
 * Public on-demand API for Lumen Performance.
 *
 * These generic functions are used by Lumen Lite itself. They contain no
 * commercial-product detection or licensing logic.
 *
 * @package CreceWebLumenLite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'cw_lumen_performance_load_engine' ) ) {
	/** @return void */
	function cw_lumen_performance_load_engine(): void {
		$base = CRECEWEB_LUMEN_LITE_DIR . 'src/Performance/';
		$files = array(
			'AssetObserverInterface.php',
			'WordPressAssetObserver.php',
			'ContextDetector.php',
			'DiagnosticEngine.php',
			'ReportEnricher.php',
			'LiteProvider.php',
			'ThemeBaselineProvider.php',
			'Manager.php',
		);

		foreach ( $files as $file ) {
			$path = $base . $file;
			if ( is_readable( $path ) ) {
				require_once $path;
			}
		}
	}
}

if ( ! function_exists( 'cw_lumen_performance_get_rules' ) ) {
	/**
	 * Returns Lumen Lite's Theme + Lite rule set.
	 *
	 * @param array<string,mixed> $args Optional context override.
	 * @return array<int,array<string,mixed>>
	 */
	function cw_lumen_performance_get_rules( array $args = array() ): array {
		cw_lumen_performance_load_engine();

		$plugin = \CreceWeb\LumenLite\Plugin::instance();
		if ( ! is_object( $plugin ) ) {
			return array();
		}

		return ( new \CreceWeb\LumenLite\Performance\Manager() )->rules( $plugin, $args );
	}
}

if ( ! function_exists( 'cw_lumen_performance_analyze' ) ) {
	/**
	 * Runs the shared engine against an explicit Lumen rule list.
	 *
	 * @param array<int,array<string,mixed>> $rules Explicit rules.
	 * @param array<string,mixed> $args Optional context override.
	 * @return array<string,mixed>
	 */
	function cw_lumen_performance_analyze( array $rules, array $args = array() ): array {
		cw_lumen_performance_load_engine();
		return ( new \CreceWeb\LumenLite\Performance\Manager() )->analyze_rules( $rules, $args );
	}
}

if ( ! function_exists( 'cw_lumen_performance_register_profile' ) ) {
	/**
	 * Registers an independent analysis profile.
	 *
	 * Registration does not add the profile to Lite's interface. The consumer
	 * must explicitly request and render its own profile.
	 *
	 * @param string $id Profile identifier.
	 * @param array<string,mixed> $config Profile configuration.
	 * @return bool
	 */
	function cw_lumen_performance_register_profile( string $id, array $config ): bool {
		return \CreceWeb\LumenLite\Performance\ProfileRegistry::register( $id, $config );
	}
}

if ( ! function_exists( 'cw_lumen_performance_profile_exists' ) ) {
	/**
	 * @param string $id Profile identifier.
	 * @return bool
	 */
	function cw_lumen_performance_profile_exists( string $id ): bool {
		return \CreceWeb\LumenLite\Performance\ProfileRegistry::has( $id );
	}
}

if ( ! function_exists( 'cw_lumen_performance_diagnose_profile' ) ) {
	/**
	 * Runs one registered Lumen Performance profile through the public registry.
	 *
	 * @param string              $profile Profile identifier.
	 * @param array<string,mixed> $args Optional context override.
	 * @return array<string,mixed>
	 */
	function cw_lumen_performance_diagnose_profile( string $profile, array $args = array() ): array {
		$profile = sanitize_key( $profile );
		if ( '' === $profile || ! \CreceWeb\LumenLite\Performance\ProfileRegistry::has( $profile ) ) {
			return array(
				'schema_version' => '1.0.0',
				'error'          => 'analysis_profile_unavailable',
			);
		}

		$report = \CreceWeb\LumenLite\Performance\ProfileRegistry::diagnose( $profile, $args );
		if ( ! empty( $report['error'] ) ) {
			return $report;
		}

		if ( ! \CreceWeb\LumenLite\Performance\ProfileRegistry::validate_report( $profile, $report ) ) {
			return array(
				'schema_version' => '1.0.0',
				'error'          => 'analysis_profile_invalid_scope',
			);
		}

		return $report;
	}
}

if ( ! function_exists( 'cw_lumen_lite_performance_diagnose' ) ) {
	/**
	 * Builds Lumen Lite's own Theme + Lite diagnosis.
	 *
	 * @param array<string,mixed> $args Optional context override.
	 * @return array<string,mixed>
	 */
	function cw_lumen_lite_performance_diagnose( array $args = array() ): array {
		cw_lumen_performance_load_engine();

		$plugin = \CreceWeb\LumenLite\Plugin::instance();
		if ( ! is_object( $plugin ) ) {
			return array(
				'schema_version' => '1.0.0',
				'error'          => 'lite_not_booted',
			);
		}

		return ( new \CreceWeb\LumenLite\Performance\Manager() )->diagnose( $plugin, $args );
	}
}
