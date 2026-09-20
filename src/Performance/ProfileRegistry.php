<?php
/**
 * Registry for independent Lumen Performance analysis profiles.
 *
 * Profiles provide an analyzer and optional presentation metadata. The Lite
 * profile uses the same registry as any external consumer; the Lite panel
 * never discovers or renders other profiles automatically.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Performance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ProfileRegistry {
	/** @var array<string,array<string,mixed>> */
	private static array $profiles = array();

	/**
	 * @param string $id Profile identifier.
	 * @param array<string,mixed> $config Profile configuration.
	 * @return bool
	 */
	public static function register( string $id, array $config ): bool {
		$id = sanitize_key( $id );
		if ( '' === $id || empty( $config['diagnose'] ) || ! is_callable( $config['diagnose'] ) ) {
			return false;
		}

		$config['id'] = $id;
		self::$profiles[ $id ] = $config;
		return true;
	}

	/**
	 * @param string $id Profile identifier.
	 * @return bool
	 */
	public static function has( string $id ): bool {
		return isset( self::$profiles[ sanitize_key( $id ) ] );
	}

	/**
	 * @param string $id Profile identifier.
	 * @return array<string,mixed>|null
	 */
	public static function get( string $id ): ?array {
		$id = sanitize_key( $id );
		return isset( self::$profiles[ $id ] ) && is_array( self::$profiles[ $id ] )
			? self::$profiles[ $id ]
			: null;
	}

	/**
	 * Lets a profile owner validate that the generated report really belongs
	 * to that requested profile.
	 *
	 * @param string $id Profile identifier.
	 * @param array<string,mixed> $report Generated report.
	 * @return bool
	 */
	public static function validate_report( string $id, array $report ): bool {
		$config = self::get( $id );
		if ( null === $config || ! empty( $report['error'] ) ) {
			return false;
		}

		if ( ! is_callable( $config['validate_report'] ?? null ) ) {
			return true;
		}

		return true === call_user_func( $config['validate_report'], $report );
	}

	/**
	 * @param string $id Profile identifier.
	 * @param array<string,mixed> $args Diagnostic context.
	 * @return array<string,mixed>
	 */
	public static function diagnose( string $id, array $args = array() ): array {
		$config = self::get( $id );
		if ( null === $config || ! is_callable( $config['diagnose'] ?? null ) ) {
			return array(
				'schema_version' => '1.0.0',
				'error'          => 'analysis_profile_unavailable',
			);
		}

		$report = call_user_func( $config['diagnose'], $args );
		return is_array( $report )
			? $report
			: array(
				'schema_version' => '1.0.0',
				'error'          => 'analysis_profile_invalid_result',
			);
	}
}
