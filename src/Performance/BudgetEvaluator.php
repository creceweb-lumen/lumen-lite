<?php
/**
 * Generic read-only evaluator for current-analysis Lumen budgets.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Performance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BudgetEvaluator {
	/**
	 * @param array<string,mixed> $report Analysis report.
	 * @param array<int,array<string,mixed>> $budgets Budget definitions.
	 * @return array{state:string,items:array<int,array<string,mixed>>,counts:array<string,int>}
	 */
	public function evaluate( array $report, array $budgets ): array {
		$metrics = $this->metrics( $report );
		$items   = array();
		$counts  = array( 'within' => 0, 'near' => 0, 'over' => 0 );

		foreach ( $budgets as $budget ) {
			if ( ! is_array( $budget ) ) { continue; }
			$id     = sanitize_key( (string) ( $budget['id'] ?? '' ) );
			$metric = sanitize_key( (string) ( $budget['metric'] ?? '' ) );
			$label  = sanitize_text_field( (string) ( $budget['label'] ?? '' ) );
			$unit   = 'bytes' === (string) ( $budget['unit'] ?? '' ) ? 'bytes' : 'count';
			$max    = isset( $budget['max'] ) && is_numeric( $budget['max'] ) ? max( 1, (int) $budget['max'] ) : 0;
			if ( '' === $id || '' === $metric || '' === $label || 0 === $max || ! array_key_exists( $metric, $metrics ) ) { continue; }

			$current = max( 0, (int) $metrics[ $metric ] );
			$warning_ratio = isset( $budget['warning_ratio'] ) && is_numeric( $budget['warning_ratio'] )
				? min( 0.99, max( 0.5, (float) $budget['warning_ratio'] ) ) : 0.9;
			$state = 'within';
			if ( $current > $max ) { $state = 'over'; }
			elseif ( $current >= (int) ceil( $max * $warning_ratio ) ) { $state = 'near'; }
			$counts[ $state ]++;
			$items[] = array(
				'id'=>$id, 'metric'=>$metric, 'label'=>$label, 'unit'=>$unit,
				'current'=>$current, 'max'=>$max, 'warning_ratio'=>$warning_ratio,
				'state'=>$state, 'headroom'=>max(0,$max-$current), 'over_by'=>max(0,$current-$max),
			);
		}
		$state = $counts['over'] > 0 ? 'over' : ( $counts['near'] > 0 ? 'near' : 'within' );
		return array( 'state'=>$state, 'items'=>$items, 'counts'=>$counts );
	}

	/** @param array<string,mixed> $report Report. @return array<string,int> */
	private function metrics( array $report ): array {
		$summary = isset($report['summary']) && is_array($report['summary']) ? $report['summary'] : array();
		$observed=0;
		foreach ( (array)($report['results']??array()) as $result ) {
			if ( is_array($result) && ! empty($result['observed']) ) { $observed++; }
		}
		return array(
			'css_bytes'=>max(0,(int)($summary['observed_css_bytes']??0)),
			'js_bytes'=>max(0,(int)($summary['observed_js_bytes']??0)),
			'resources'=>$observed,
			'requests'=>max(0,(int)($summary['observed_requests']??0)),
		);
	}
}
