<?php
/**
 * Adds explainable aggregate metrics to one diagnostic report.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Performance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ReportEnricher {
	/**
	 * @param array<string,mixed> $report Base diagnostic report.
	 * @return array<string,mixed>
	 */
	public function enrich( array $report ): array {
		$results = isset( $report['results'] ) && is_array( $report['results'] )
			? $report['results']
			: array();

		$css_bytes = 0;
		$js_bytes  = 0;
		$requests  = 0;
		$sources   = array();

		foreach ( $results as $index => $result ) {
			if ( ! is_array( $result ) || empty( $result['observed'] ) ) {
				continue;
			}

			$size = isset( $result['size_bytes'] ) && is_numeric( $result['size_bytes'] )
				? max( 0, (int) $result['size_bytes'] )
				: 0;

			if ( 'style' === (string) ( $result['type'] ?? '' ) ) {
				$css_bytes += $size;
			} elseif ( 'script' === (string) ( $result['type'] ?? '' ) ) {
				$js_bytes += $size;
			}

			$source = trim( (string) ( $result['source'] ?? '' ) );
			if ( '' !== $source ) {
				++$requests;
				$key = strtolower( $source );
				if ( ! isset( $sources[ $key ] ) ) {
					$sources[ $key ] = array(
						'source'  => $source,
						'indexes' => array(),
					);
				}
				$sources[ $key ]['indexes'][] = $index;
			}
		}

		$duplicates = array();
		foreach ( $sources as $group ) {
			if ( count( $group['indexes'] ) < 2 ) {
				continue;
			}

			$handles       = array();
			$authoritative = false;

			foreach ( $group['indexes'] as $index ) {
				$result = $results[ $index ];
				$handles[] = (string) ( $result['handle'] ?? '' );
				if ( 'observation_only' !== (string) ( $result['mode'] ?? '' ) ) {
					$authoritative = true;
				}
				$results[ $index ]['duplicate_source'] = true;
			}

			$duplicates[] = array(
				'source'   => (string) $group['source'],
				'handles'  => array_values( array_unique( array_filter( $handles ) ) ),
				'severity' => $authoritative ? 'error' : 'attention',
			);
		}

		if ( ! isset( $report['summary'] ) || ! is_array( $report['summary'] ) ) {
			$report['summary'] = array();
		}

		$report['summary']['observed_css_bytes'] = $css_bytes;
		$report['summary']['observed_js_bytes']  = $js_bytes;
		$report['summary']['observed_requests']  = $requests;
		$report['summary']['duplicate_sources']  = count( $duplicates );
		$report['duplicates']                    = $duplicates;
		$report['results']                       = $results;

		return $report;
	}
}
