<?php
/**
 * Pure diagnostic classifier.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Performance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DiagnosticEngine {
	public const STATUS_CORRECT          = 'correct';
	public const STATUS_CORRECT_NO_COST  = 'correct_no_cost';
	public const STATUS_OPTIMIZABLE      = 'optimizable';
	public const STATUS_ANOMALY          = 'anomaly';
	public const STATUS_FUNCTIONAL_ERROR = 'functional_error';
	public const STATUS_INFORMATIONAL    = 'informational';
	public const STATUS_NOT_APPLICABLE   = 'not_applicable';
	public const STATUS_PENDING          = 'pending';

	public function __construct( private AssetObserverInterface $observer ) {}

	/**
	 * @param array<int,array<string,mixed>> $rules Diagnostic rules.
	 * @param array{name:string,screen_id:string,hook_suffix:string,ready:bool} $context Context.
	 * @return array<string,mixed>
	 */
	public function run( array $rules, array $context ): array {
		$results = array();

		foreach ( $rules as $rule ) {
			$normalized = $this->normalize_rule( $rule );
			if ( null === $normalized ) {
				continue;
			}

			$observation = $this->observer->observe(
				$normalized['type'],
				$normalized['handle']
			);

			$results[] = $this->classify(
				$normalized,
				$observation,
				(bool) $context['ready']
			);
		}

		return array(
			'schema_version' => '1.0.0',
			'context'        => $context,
			'summary'        => $this->summarize( $results ),
			'results'        => $results,
		);
	}

	/**
	 * @param array<string,mixed> $rule Raw rule.
	 * @return array<string,mixed>|null
	 */
	private function normalize_rule( array $rule ): ?array {
		$required = array( 'id', 'provider', 'module', 'type', 'handle' );
		foreach ( $required as $key ) {
			if ( empty( $rule[ $key ] ) || ! is_string( $rule[ $key ] ) ) {
				return null;
			}
		}

		$type = 'style' === $rule['type'] ? 'style' : ( 'script' === $rule['type'] ? 'script' : '' );
		if ( '' === $type ) {
			return null;
		}

		return array(
			'id'         => preg_replace( '/[^a-z0-9._-]/', '', strtolower( $rule['id'] ) ),
			'provider'   => sanitize_key( $rule['provider'] ),
			'module'     => sanitize_key( $rule['module'] ),
			'type'       => $type,
			'handle'     => sanitize_key( $rule['handle'] ),
			'configured' => ! empty( $rule['configured'] ),
			'used'       => ! empty( $rule['used'] ),
			'mode'       => isset( $rule['mode'] ) && 'observation_only' === $rule['mode'] ? 'observation_only' : 'expectation',
			'size_bytes' => isset( $rule['size_bytes'] ) && is_numeric( $rule['size_bytes'] ) ? max( 0, (int) $rule['size_bytes'] ) : null,
			'reason'     => isset( $rule['reason'] ) ? sanitize_text_field( (string) $rule['reason'] ) : '',
		);
	}

	/**
	 * @param array<string,mixed> $rule Rule.
	 * @param array{observed:bool,state:string} $observation Observation.
	 * @return array<string,mixed>
	 */
	private function classify( array $rule, array $observation, bool $ready ): array {
		$observed = ! empty( $observation['observed'] );
		$expected = ! empty( $rule['configured'] ) && ! empty( $rule['used'] );
		$status   = self::STATUS_PENDING;

		if ( 'observation_only' === $rule['mode'] ) {
			$status = $observed ? self::STATUS_INFORMATIONAL : self::STATUS_NOT_APPLICABLE;
		} elseif ( ! $ready ) {
			$status = self::STATUS_PENDING;
		} elseif ( ! empty( $rule['used'] ) && empty( $rule['configured'] ) ) {
			$status = self::STATUS_FUNCTIONAL_ERROR;
		} elseif ( $expected && $observed ) {
			$status = self::STATUS_CORRECT;
		} elseif ( $expected && ! $observed ) {
			$status = self::STATUS_FUNCTIONAL_ERROR;
		} elseif ( ! $expected && $observed && ! empty( $rule['configured'] ) ) {
			$status = self::STATUS_OPTIMIZABLE;
		} elseif ( ! $expected && $observed ) {
			$status = self::STATUS_ANOMALY;
		} else {
			$status = self::STATUS_CORRECT_NO_COST;
		}

		return array_merge(
			$rule,
			array(
				'expected'       => $expected,
				'observed'       => $observed,
				'observed_state' => sanitize_key( (string) ( $observation['state'] ?? 'unknown' ) ),
				'source'         => isset( $observation['source'] ) ? sanitize_text_field( (string) $observation['source'] ) : '',
				'status'         => $status,
			)
		);
	}

	/**
	 * @param array<int,array<string,mixed>> $results Results.
	 * @return array<string,mixed>
	 */
	private function summarize( array $results ): array {
		$counts         = array();
		$observed_bytes = 0;
		$expected_bytes = 0;
		$providers      = array();

		foreach ( $results as $result ) {
			$status = (string) $result['status'];
			$counts[ $status ] = (int) ( $counts[ $status ] ?? 0 ) + 1;

			$size = is_int( $result['size_bytes'] ) ? $result['size_bytes'] : 0;
			if ( ! empty( $result['observed'] ) ) {
				$observed_bytes += $size;
			}
			if ( ! empty( $result['expected'] ) ) {
				$expected_bytes += $size;
			}

			$provider = (string) $result['provider'];
			if ( ! isset( $providers[ $provider ] ) ) {
				$providers[ $provider ] = array(
					'results'        => 0,
					'observed_bytes' => 0,
					'statuses'       => array(),
				);
			}
			++$providers[ $provider ]['results'];
			if ( ! empty( $result['observed'] ) ) {
				$providers[ $provider ]['observed_bytes'] += $size;
			}
			$providers[ $provider ]['statuses'][ $status ] =
				(int) ( $providers[ $provider ]['statuses'][ $status ] ?? 0 ) + 1;
		}

		return array(
			'total_results'            => count( $results ),
			'status_counts'            => $counts,
			'observed_inventory_bytes' => $observed_bytes,
			'expected_inventory_bytes' => $expected_bytes,
			'providers'                => $providers,
		);
	}
}
