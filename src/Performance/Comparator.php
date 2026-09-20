<?php
/**
 * Manual comparison of two Lumen Performance reports.
 *
 * No persistence is performed here. The controller owns the short-lived,
 * administrator-selected baseline transient.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Performance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Comparator {
	/**
	 * @param array<string,mixed> $baseline Baseline report.
	 * @param array<string,mixed> $current Current report.
	 * @return array<string,mixed>
	 */
	public function compare( array $baseline, array $current, string $mode = 'before_after' ): array {
		$mode        = $this->normalize_mode( $mode );
		$equivalence = $this->equivalence( $baseline, $current, $mode );

		return array(
			'mode'            => $mode,
			'equivalence'     => $equivalence,
			'same_sample'     => $this->sample_id( $baseline ) === $this->sample_id( $current ),
			'metrics'         => $this->metric_changes( $baseline, $current ),
			'browser_transfer'=> $this->browser_transfer_changes( $baseline, $current ),
			'versions'        => $this->version_changes( $baseline, $current ),
			'resources'       => $this->resource_changes( $baseline, $current ),
			'modules'         => $this->module_changes( $baseline, $current ),
		);
	}

	/**
	 * @return array{equivalent:bool,reasons:array<int,string>,url:string,profile:string,device:string}
	 */
	private function equivalence( array $baseline, array $current, string $mode ): array {
		$a = isset( $baseline['analysis'] ) && is_array( $baseline['analysis'] ) ? $baseline['analysis'] : array();
		$b = isset( $current['analysis'] ) && is_array( $current['analysis'] ) ? $current['analysis'] : array();

		$url_a = $this->normalize_url( (string) ( $a['url'] ?? '' ) );
		$url_b = $this->normalize_url( (string) ( $b['url'] ?? '' ) );
		$profile_a = sanitize_key( (string) ( $a['profile'] ?? '' ) );
		$profile_b = sanitize_key( (string) ( $b['profile'] ?? '' ) );
		$device_a = sanitize_key( (string) ( $a['device'] ?? '' ) );
		$device_b = sanitize_key( (string) ( $b['device'] ?? '' ) );

		$reasons = array();
		if ( 'before_after' === $mode && ( '' === $url_a || '' === $url_b || $url_a !== $url_b ) ) {
			$reasons[] = 'url';
		}
		if ( 'pages' === $mode && ( '' === $url_a || '' === $url_b ) ) {
			$reasons[] = 'url_missing';
		}
		if ( '' === $profile_a || '' === $profile_b || $profile_a !== $profile_b ) {
			$reasons[] = 'profile';
		}
		if ( '' === $device_a || '' === $device_b || $device_a !== $device_b ) {
			$reasons[] = 'device';
		}

		return array(
			'equivalent' => empty( $reasons ),
			'reasons'    => $reasons,
			'url'        => $url_b,
			'profile'    => $profile_b,
			'device'     => $device_b,
		);
	}

	private function normalize_mode( string $mode ): string {
		$mode = sanitize_key( $mode );
		return in_array( $mode, array( 'before_after', 'pages' ), true ) ? $mode : 'before_after';
	}

	private function normalize_url( string $url ): string {
		$url = trim( $url );
		if ( '' === $url ) {
			return '';
		}
		return untrailingslashit( $url );
	}

	private function sample_id( array $report ): string {
		$analysis = isset( $report['analysis'] ) && is_array( $report['analysis'] ) ? $report['analysis'] : array();
		return implode(
			'|',
			array(
				(string) ( $analysis['profile'] ?? '' ),
				$this->normalize_url( (string) ( $analysis['url'] ?? '' ) ),
				(string) ( $analysis['analyzed_at'] ?? '' ),
				(string) ( $analysis['captured_at'] ?? '' ),
			)
		);
	}

	/**
	 * @return array<string,array{baseline:int,current:int,delta:int,percent:float|null}>
	 */
	private function metric_changes( array $baseline, array $current ): array {
		$a = $this->metrics( $baseline );
		$b = $this->metrics( $current );
		$out = array();

		foreach ( array( 'css_bytes', 'js_bytes', 'resources', 'requests' ) as $key ) {
			$out[ $key ] = $this->delta( (int) $a[ $key ], (int) $b[ $key ] );
		}
		return $out;
	}

	/** @return array{css_bytes:int,js_bytes:int,resources:int,requests:int} */
	private function metrics( array $report ): array {
		$summary = isset( $report['summary'] ) && is_array( $report['summary'] ) ? $report['summary'] : array();
		$observed = 0;
		foreach ( (array) ( $report['results'] ?? array() ) as $result ) {
			if ( is_array( $result ) && ! empty( $result['observed'] ) ) {
				++$observed;
			}
		}
		return array(
			'css_bytes' => (int) ( $summary['observed_css_bytes'] ?? 0 ),
			'js_bytes'  => (int) ( $summary['observed_js_bytes'] ?? 0 ),
			'resources' => $observed,
			'requests'  => (int) ( $summary['observed_requests'] ?? 0 ),
		);
	}

	/**
	 * Browser transfer is context only because cache state can change between runs.
	 *
	 * @return array<string,mixed>
	 */
	private function browser_transfer_changes( array $baseline, array $current ): array {
		$a = isset( $baseline['browser_metrics'] ) && is_array( $baseline['browser_metrics'] ) ? $baseline['browser_metrics'] : array();
		$b = isset( $current['browser_metrics'] ) && is_array( $current['browser_metrics'] ) ? $current['browser_metrics'] : array();

		$available = ! empty( $a['available'] ) && ! empty( $b['available'] );
		return array(
			'available' => $available,
			'total'     => $this->delta( (int) ( $a['transfer_bytes'] ?? 0 ), (int) ( $b['transfer_bytes'] ?? 0 ) ),
			'css'       => $this->delta( (int) ( $a['css_transfer_bytes'] ?? 0 ), (int) ( $b['css_transfer_bytes'] ?? 0 ) ),
			'js'        => $this->delta( (int) ( $a['js_transfer_bytes'] ?? 0 ), (int) ( $b['js_transfer_bytes'] ?? 0 ) ),
		);
	}

	/**
	 * @return array<int,array{component:string,baseline:string,current:string}>
	 */
	private function version_changes( array $baseline, array $current ): array {
		$a = isset( $baseline['baseline'] ) && is_array( $baseline['baseline'] ) ? $baseline['baseline'] : array();
		$b = isset( $current['baseline'] ) && is_array( $current['baseline'] ) ? $current['baseline'] : array();
		$changes = array();

		foreach ( array_unique( array_merge( array_keys( $a ), array_keys( $b ) ) ) as $key ) {
			$before = (string) ( $a[ $key ] ?? '' );
			$after  = (string) ( $b[ $key ] ?? '' );
			if ( $before === $after ) {
				continue;
			}
			$changes[] = array(
				'component' => sanitize_key( (string) $key ),
				'baseline'  => $before,
				'current'   => $after,
			);
		}
		return $changes;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private function resource_changes( array $baseline, array $current ): array {
		$a = $this->index_resources( $baseline );
		$b = $this->index_resources( $current );
		$changes = array();

		foreach ( array_unique( array_merge( array_keys( $a ), array_keys( $b ) ) ) as $key ) {
			$before = $a[ $key ] ?? null;
			$after  = $b[ $key ] ?? null;

			if ( null === $before ) {
				$changes[] = $this->resource_change( 'added', null, $after );
				continue;
			}
			if ( null === $after ) {
				$changes[] = $this->resource_change( 'removed', $before, null );
				continue;
			}

			$fields = array( 'module', 'configured', 'used', 'expected', 'observed', 'size_bytes', 'status', 'source' );
			$changed = false;
			foreach ( $fields as $field ) {
				if ( ( $before[ $field ] ?? null ) !== ( $after[ $field ] ?? null ) ) {
					$changed = true;
					break;
				}
			}
			if ( $changed ) {
				$changes[] = $this->resource_change( 'changed', $before, $after );
			}
		}

		return $changes;
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	private function index_resources( array $report ): array {
		$indexed = array();
		foreach ( (array) ( $report['results'] ?? array() ) as $result ) {
			if ( ! is_array( $result ) ) {
				continue;
			}
			$key = implode(
				'|',
				array(
					(string) ( $result['provider'] ?? '' ),
					(string) ( $result['type'] ?? '' ),
					(string) ( $result['handle'] ?? '' ),
				)
			);
			$indexed[ $key ] = $result;
		}
		return $indexed;
	}

	/**
	 * @param array<string,mixed>|null $before Baseline resource.
	 * @param array<string,mixed>|null $after Current resource.
	 * @return array<string,mixed>
	 */
	private function resource_change( string $change, ?array $before, ?array $after ): array {
		$item = null !== $after ? $after : (array) $before;
		$context_changed = false;

		if ( null !== $before && null !== $after ) {
			foreach ( array( 'configured', 'used', 'expected' ) as $field ) {
				if ( ( $before[ $field ] ?? null ) !== ( $after[ $field ] ?? null ) ) {
					$context_changed = true;
					break;
				}
			}
		}

		return array(
			'change'          => $change,
			'provider'        => (string) ( $item['provider'] ?? '' ),
			'module'          => (string) ( $item['module'] ?? '' ),
			'type'            => (string) ( $item['type'] ?? '' ),
			'handle'          => (string) ( $item['handle'] ?? '' ),
			'context_changed' => $context_changed,
			'baseline'        => $before,
			'current'         => $after,
		);
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private function module_changes( array $baseline, array $current ): array {
		$a = $this->aggregate_modules( $baseline );
		$b = $this->aggregate_modules( $current );
		$changes = array();

		foreach ( array_unique( array_merge( array_keys( $a ), array_keys( $b ) ) ) as $key ) {
			$before = $a[ $key ] ?? null;
			$after  = $b[ $key ] ?? null;

			if ( $before === $after ) {
				continue;
			}

			$item = null !== $after ? $after : (array) $before;
			$changes[] = array(
				'change'   => null === $before ? 'added' : ( null === $after ? 'removed' : 'changed' ),
				'provider' => (string) ( $item['provider'] ?? '' ),
				'module'   => (string) ( $item['module'] ?? '' ),
				'baseline' => $before,
				'current'  => $after,
			);
		}
		return $changes;
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	private function aggregate_modules( array $report ): array {
		$groups = array();
		foreach ( (array) ( $report['results'] ?? array() ) as $result ) {
			if ( ! is_array( $result ) ) {
				continue;
			}
			$provider = (string) ( $result['provider'] ?? '' );
			$module   = (string) ( $result['module'] ?? '' );
			$key      = $provider . '|' . $module;

			if ( ! isset( $groups[ $key ] ) ) {
				$groups[ $key ] = array(
					'provider'   => $provider,
					'module'     => $module,
					'configured' => 0,
					'used'       => 0,
					'expected'   => 0,
					'observed'   => 0,
					'bytes'      => 0,
					'statuses'   => array(),
				);
			}

			foreach ( array( 'configured', 'used', 'expected', 'observed' ) as $field ) {
				if ( ! empty( $result[ $field ] ) ) {
					++$groups[ $key ][ $field ];
				}
			}
			if ( ! empty( $result['observed'] ) ) {
				$groups[ $key ]['bytes'] += max( 0, (int) ( $result['size_bytes'] ?? 0 ) );
			}
			$status = (string) ( $result['status'] ?? '' );
			if ( '' !== $status ) {
				$groups[ $key ]['statuses'][] = $status;
			}
		}

		foreach ( $groups as &$group ) {
			$group['statuses'] = array_values( array_unique( $group['statuses'] ) );
			sort( $group['statuses'] );
		}
		unset( $group );

		return $groups;
	}

	/** @return array{baseline:int,current:int,delta:int,percent:float|null} */
	private function delta( int $baseline, int $current ): array {
		$delta = $current - $baseline;
		return array(
			'baseline' => $baseline,
			'current'  => $current,
			'delta'    => $delta,
			'percent'  => 0 !== $baseline ? round( ( $delta / $baseline ) * 100, 1 ) : null,
		);
	}
}
