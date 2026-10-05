<?php
/**
 * On-demand Lumen Performance manager.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Performance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Manager {
	/**
	 * Returns the rules used by Lumen Lite itself.
	 *
	 * No extension filter is applied here: Lite's own panel remains limited to
	 * Lumen Theme and Lumen Lite.
	 *
	 * @param object $plugin Lumen Lite plugin instance.
	 * @param array<string,mixed> $args Optional context override.
	 * @return array<int,array<string,mixed>>
	 */
	public function rules( object $plugin, array $args = array() ): array {
		$context = ContextDetector::detect( $args );

		return array_merge(
			( new ThemeBaselineProvider() )->rules( $context ),
			( new LiteProvider( $plugin ) )->rules( $context )
		);
	}

	/**
	 * Runs the shared engine against an explicit rule list.
	 *
	 * @param array<int,array<string,mixed>> $rules Explicit Lumen rules.
	 * @param array<string,mixed> $args Optional context override.
	 * @return array<string,mixed>
	 */
	public function analyze_rules( array $rules, array $args = array() ): array {
		$context = ContextDetector::detect( $args );
		$report  = ( new DiagnosticEngine( new WordPressAssetObserver() ) )->run( $rules, $context );
		$report  = ( new ReportEnricher() )->enrich( $report );

		$report['generated_by'] = 'creceweb-lumen-performance';
		$report['engine_version'] = '1.4.0';
		$report['notes'] = array(
			__( 'Inventory bytes are raw packaged file sizes, not compressed transfer sizes.', 'creceweb-lumen-lite' ),
			__( 'No remote request, database write, cron or persistent frontend instrumentation is performed.', 'creceweb-lumen-lite' ),
		);

		return $report;
	}

	/**
	 * Lumen Lite's own Theme + Lite diagnosis.
	 *
	 * @param object $plugin Lumen Lite plugin instance.
	 * @param array<string,mixed> $args Optional context override.
	 * @return array<string,mixed>
	 */
	public function diagnose( object $plugin, array $args = array() ): array {
		$report = $this->analyze_rules( $this->rules( $plugin, $args ), $args );
		$report['baseline'] = array(
			'theme' => defined( 'CRECEWEB_LUMEN_VERSION' ) ? (string) CRECEWEB_LUMEN_VERSION : '',
			'lite'  => defined( 'CRECEWEB_LUMEN_LITE_VERSION' ) ? (string) CRECEWEB_LUMEN_LITE_VERSION : '',
		);

		return $report;
	}
}
