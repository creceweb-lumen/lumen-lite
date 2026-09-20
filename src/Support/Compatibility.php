<?php
/**
 * Lumen Theme compatibility checks.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps Theme-dependent modules inert when the environment is incompatible.
 */
final class Compatibility {
	/** @var array<int,string> */
	private array $issues = array();
	private bool $validated = false;
	private string $lumen_version = '';
	private string $bridge_version = '';
	private string $announced_lumen_version = '';
	private string $announced_bridge_version = '';

	/**
	 * Validates the current Theme and bridge. Safe to call repeatedly.
	 *
	 * @return bool
	 */
	public function validate(): bool {
		$this->issues         = array();
		$this->validated      = true;
		$this->lumen_version  = defined( 'CRECEWEB_LUMEN_VERSION' )
			? (string) CRECEWEB_LUMEN_VERSION
			: $this->announced_lumen_version;
		$this->bridge_version = defined( 'CRECEWEB_LUMEN_BRIDGE_API_VERSION' )
			? (string) CRECEWEB_LUMEN_BRIDGE_API_VERSION
			: $this->announced_bridge_version;

		if ( ! function_exists( 'get_template' ) || 'creceweb-lumen' !== get_template() ) {
			$this->issues[] = 'theme_not_active';

			return false;
		}

		if ( ! $this->is_semver( $this->lumen_version ) ) {
			$this->issues[] = 'theme_version_missing';
		} elseif ( version_compare( $this->lumen_version, CRECEWEB_LUMEN_LITE_REQUIRED_THEME_VERSION, '<' ) ) {
			$this->issues[] = 'theme_version_unsupported';
		}

		if ( ! $this->is_semver( $this->bridge_version ) ) {
			$this->issues[] = 'bridge_missing';
		} elseif ( ! $this->is_bridge_compatible( $this->bridge_version ) ) {
			$this->issues[] = 'bridge_incompatible';
		}

		$this->issues = array_values( array_unique( $this->issues ) );

		return $this->is_compatible();
	}

	/**
	 * @param string $lumen_version Theme version.
	 * @param string $bridge_version Bridge API version.
	 * @return void
	 */
	public function receive_lumen_announcement( string $lumen_version, string $bridge_version = '' ): void {
		$this->announced_lumen_version  = trim( $lumen_version );
		$this->announced_bridge_version = trim( $bridge_version );
		$this->validate();
	}

	/** @return bool */
	public function is_compatible(): bool {
		return $this->validated && array() === $this->issues;
	}

	/** @return array<int,string> */
	public function get_issues(): array {
		return $this->issues;
	}

	/** @return string */
	public function get_lumen_version(): string {
		return $this->lumen_version;
	}

	/** @return string */
	public function get_bridge_version(): string {
		return $this->bridge_version;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function status(): array {
		if ( ! $this->validated ) {
			$this->validate();
		}

		return array(
			'compatible'       => $this->is_compatible(),
			'issues'           => $this->get_issues(),
			'theme_version'    => $this->get_lumen_version(),
			'bridge_version'   => $this->get_bridge_version(),
			'required_theme'   => CRECEWEB_LUMEN_LITE_REQUIRED_THEME_VERSION,
			'required_bridge'  => CRECEWEB_LUMEN_LITE_BRIDGE_API_VERSION,
		);
	}

	/**
	 * @param string $issue Issue code.
	 * @return string
	 */
	public function describe_issue( string $issue ): string {
		$messages = array(
			'theme_not_active'          => sprintf(
				/* translators: %s: Minimum compatible Lumen Theme version. */
				__( 'Lumen Lite is designed for Lumen Theme %s or later. Activate a compatible version to use the integrated tools.', 'creceweb-lumen-lite' ),
				CRECEWEB_LUMEN_LITE_REQUIRED_THEME_VERSION
			),
			'theme_version_missing'     => __( 'We could not determine which Lumen Theme version you are using.', 'creceweb-lumen-lite' ),
			'theme_version_unsupported' => sprintf(
				/* translators: %s: Minimum compatible Lumen Theme version. */
				__( 'Update Lumen Theme to version %s or later.', 'creceweb-lumen-lite' ),
				CRECEWEB_LUMEN_LITE_REQUIRED_THEME_VERSION
			),
			'bridge_missing'            => __( 'Update Lumen Theme to connect it with Lumen Lite.', 'creceweb-lumen-lite' ),
			'bridge_incompatible'       => __( 'Update Lumen Theme or Lumen Lite so both versions work together.', 'creceweb-lumen-lite' ),
		);

		return $messages[ $issue ] ?? __( 'We could not complete one of the checks. Confirm that WordPress, the Theme, and Lumen Lite are up to date.', 'creceweb-lumen-lite' );
	}

	/**
	 * @param string $version Version identifier.
	 * @return bool
	 */
	private function is_semver( string $version ): bool {
		$pattern = '/^(0|[1-9]\\d*)\\.(0|[1-9]\\d*)\\.(0|[1-9]\\d*)(?:-(?:0|[1-9A-Za-z-][0-9A-Za-z-]*)(?:\\.(?:0|[1-9A-Za-z-][0-9A-Za-z-]*))*)?(?:\\+[0-9A-Za-z-]+(?:\\.[0-9A-Za-z-]+)*)?$/D';

		return 1 === preg_match( $pattern, $version );
	}

	/**
	 * @param string $theme_api Theme bridge API.
	 * @return bool
	 */
	private function is_bridge_compatible( string $theme_api ): bool {
		$plugin_api = CRECEWEB_LUMEN_LITE_BRIDGE_API_VERSION;

		if ( ! $this->is_semver( $plugin_api ) || ! $this->is_semver( $theme_api ) ) {
			return false;
		}

		$theme_major  = (int) strtok( $theme_api, '.' );
		$plugin_major = (int) strtok( $plugin_api, '.' );

		return $theme_major === $plugin_major && version_compare( $theme_api, $plugin_api, '>=' );
	}
}
