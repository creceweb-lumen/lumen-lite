<?php
/**
 * Applies data-only Library Kit configuration presets through Config Transfer.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Library;

use CreceWeb\LumenLite\ConfigTransfer\Manager as ConfigTransferManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps Kit presets declarative and reuses each active component's canonical
 * export/import callbacks. No Kit can register executable callbacks here.
 */
final class KitPresetManager {
	private const SNAPSHOT_OPTION = 'cw_lumen_lite_kit_config_snapshot';
	private KitDemoContent $demo_content;

	public function __construct( private KitCatalog $kits, private ConfigTransferManager $config_transfer, ?KitDemoContent $demo_content = null ) {
		$this->demo_content = $demo_content ?? new KitDemoContent();
	}

	/** @return void */
	public function register(): void {
		add_action( 'wp_ajax_cw_lumen_lite_kit_config_preset', array( $this, 'handle_ajax' ) );
	}

	/** @return bool */
	public function has_snapshot(): bool {
		$snapshot = get_option( self::SNAPSHOT_OPTION, array() );
		return is_array( $snapshot ) && ! empty( $snapshot['sections'] ) && is_array( $snapshot['sections'] );
	}

	/** @return bool */
	public function kit_has_preset( string $kit_id ): bool {
		$kit = $this->kits->items()[ sanitize_key( $kit_id ) ] ?? null;
		return is_array( $kit ) && ! is_wp_error( $this->load_payload( $kit ) );
	}

	/**
	 * Whether this preset operation changes the Lite Color Mode configuration.
	 *
	 * The browser stores a visitor override in localStorage. A Kit preset that
	 * changes Color Mode must clear that override in the current admin browser
	 * so the newly applied site default is visible after the editor reload.
	 *
	 * @param string $operation apply or restore.
	 * @param string $kit_id Kit identifier.
	 * @return bool
	 */
	private function operation_affects_color_mode( string $operation, string $kit_id ): bool {
		if ( 'restore' === $operation ) {
			$snapshot = get_option( self::SNAPSHOT_OPTION, array() );
			$lite = is_array( $snapshot ) && isset( $snapshot['sections']['lite'] ) && is_array( $snapshot['sections']['lite'] )
				? $snapshot['sections']['lite']
				: array();
			return isset( $lite['color_mode'] ) && is_array( $lite['color_mode'] );
		}

		$kit = $this->kits->items()[ sanitize_key( $kit_id ) ] ?? null;
		if ( ! is_array( $kit ) ) {
			return false;
		}

		$payload = $this->load_payload( $kit );
		if ( is_wp_error( $payload ) ) {
			return false;
		}

		$lite = isset( $payload['sections']['lite']['data'] ) && is_array( $payload['sections']['lite']['data'] )
			? $payload['sections']['lite']['data']
			: array();
		return isset( $lite['color_mode'] ) && is_array( $lite['color_mode'] );
	}

	/** @return void */
	public function handle_ajax(): void {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to change the site design.', 'creceweb-lumen-lite' ) ), 403 );
		}
		check_ajax_referer( 'cw_lumen_lite_kit_config_preset', 'nonce' );

		$operation = isset( $_POST['operation'] ) ? sanitize_key( wp_unslash( $_POST['operation'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$kit_id    = isset( $_POST['kit_id'] ) ? sanitize_key( wp_unslash( $_POST['kit_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.

		$reset_color_mode_preference = $this->operation_affects_color_mode( $operation, $kit_id );
		$result = 'restore' === $operation ? $this->restore_snapshot() : $this->apply( $kit_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success(
			array(
				'message'      => 'restore' === $operation
					? __( 'The previous site design was restored. Reload the editor to refresh Theme styles.', 'creceweb-lumen-lite' )
					: __( 'The recommended design was applied. Reload the editor to refresh Theme styles.', 'creceweb-lumen-lite' ),
				'hasSnapshot'  => $this->has_snapshot(),
				'resetColorModePreference' => $reset_color_mode_preference,
			)
		);
	}

	/**
	 * Apply one Kit preset while preserving the first restore point across Kit
	 * switches and repeat applications.
	 *
	 * @param string $kit_id Kit identifier.
	 * @return true|\WP_Error
	 */
	public function apply( string $kit_id ) {
		$kit_id = sanitize_key( $kit_id );
		$kit    = $this->kits->items()[ $kit_id ] ?? null;
		if ( ! is_array( $kit ) ) {
			return new \WP_Error( 'lumen_kit_missing', __( 'The selected Kit is no longer available.', 'creceweb-lumen-lite' ) );
		}

		$payload = $this->load_payload( $kit );
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		$previous_snapshot = get_option( self::SNAPSHOT_OPTION, array() );
		$has_previous      = is_array( $previous_snapshot ) && ! empty( $previous_snapshot['sections'] ) && is_array( $previous_snapshot['sections'] );

		$available = $this->config_transfer->sections();
		$incoming  = isset( $payload['sections'] ) && is_array( $payload['sections'] ) ? $payload['sections'] : array();
		$rollback  = array();
		$changes   = array();

		foreach ( $incoming as $id => $section ) {
			$id = sanitize_key( (string) $id );
			if ( '' === $id || ! isset( $available[ $id ] ) || ! is_array( $section ) ) {
				continue;
			}
			$data = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : null;
			if ( null === $data ) {
				continue;
			}
			$current = call_user_func( $available[ $id ]['export'] );
			if ( ! is_array( $current ) ) {
				continue;
			}
			$rollback[ $id ] = $current;
			$changes[ $id ]  = array_replace_recursive( $current, $data );
		}

		if ( empty( $changes ) ) {
			return new \WP_Error( 'lumen_kit_preset_unsupported', __( 'None of the preset configuration sections are available on this site.', 'creceweb-lumen-lite' ) );
		}

		$applied = array();
		foreach ( $changes as $id => $data ) {
			$result = call_user_func( $available[ $id ]['import'], $data );
			if ( is_wp_error( $result ) || true !== $result ) {
				foreach ( $applied as $applied_id ) {
					call_user_func( $available[ $applied_id ]['import'], $rollback[ $applied_id ] );
				}
				return new \WP_Error( 'lumen_kit_preset_apply_failed', __( 'The recommended design could not be applied safely. The previous configuration was kept.', 'creceweb-lumen-lite' ) );
			}
			$applied[] = $id;
		}

		$demo_definition = isset( $payload['demo_content'] ) && is_array( $payload['demo_content'] ) ? $payload['demo_content'] : array();
		$previous_demo   = $has_previous && isset( $previous_snapshot['demo_content'] ) && is_array( $previous_snapshot['demo_content'] )
			? $previous_snapshot['demo_content']
			: array();
		$demo_snapshot   = $this->demo_content->apply( $demo_definition, $previous_demo );

		// Reapplying or switching Kits preserves generated-demo identities from
		// the first application so Restore can remove only unchanged demo content.
		foreach ( array( 'social_footer', 'messaging' ) as $demo_key ) {
			if (
				'created' === (string) ( $previous_demo[ $demo_key ]['status'] ?? '' )
				&& ( ! isset( $demo_snapshot[ $demo_key ] ) || 'skipped-existing' === (string) ( $demo_snapshot[ $demo_key ]['status'] ?? '' ) )
			) {
				$demo_snapshot[ $demo_key ] = $previous_demo[ $demo_key ];
			}
		}

		$persistent_sections = $has_previous ? (array) $previous_snapshot['sections'] : $rollback;
		$created_at          = $has_previous && ! empty( $previous_snapshot['created_at'] )
			? sanitize_text_field( (string) $previous_snapshot['created_at'] )
			: gmdate( 'c' );

		update_option(
			self::SNAPSHOT_OPTION,
			array(
				'created_at'      => $created_at,
				'last_applied_at' => gmdate( 'c' ),
				'kit_id'          => sanitize_key( (string) ( $kit['id'] ?? $kit_id ) ),
				'sections'        => $persistent_sections,
				'demo_content'    => $demo_snapshot,
			),
			false
		);

		return true;
	}

	/** @return true|\WP_Error */
	private function restore_snapshot() {
		$snapshot = get_option( self::SNAPSHOT_OPTION, array() );
		$sections = is_array( $snapshot ) && isset( $snapshot['sections'] ) && is_array( $snapshot['sections'] ) ? $snapshot['sections'] : array();
		if ( empty( $sections ) ) {
			return new \WP_Error( 'lumen_kit_snapshot_missing', __( 'There is no previous Kit design snapshot to restore.', 'creceweb-lumen-lite' ) );
		}

		$available = $this->config_transfer->sections();
		foreach ( $sections as $id => $data ) {
			$id = sanitize_key( (string) $id );
			if ( ! isset( $available[ $id ] ) || ! is_array( $data ) ) {
				continue;
			}
			$result = call_user_func( $available[ $id ]['import'], $data );
			if ( is_wp_error( $result ) || true !== $result ) {
				return new \WP_Error( 'lumen_kit_snapshot_restore_failed', __( 'The previous design snapshot could not be restored completely.', 'creceweb-lumen-lite' ) );
			}
		}

		$demo_snapshot = isset( $snapshot['demo_content'] ) && is_array( $snapshot['demo_content'] ) ? $snapshot['demo_content'] : array();
		$this->demo_content->restore( $demo_snapshot );

		delete_option( self::SNAPSHOT_OPTION );
		return true;
	}

	/**
	 * @param array<string,mixed> $kit Normalized Kit definition.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function load_payload( array $kit ) {
		$filename = sanitize_file_name( (string) ( $kit['config_preset'] ?? '' ) );
		if ( '' === $filename || 'json' !== strtolower( (string) pathinfo( $filename, PATHINFO_EXTENSION ) ) ) {
			return new \WP_Error( 'lumen_kit_preset_missing', __( 'This Kit does not include a configuration preset.', 'creceweb-lumen-lite' ) );
		}

		$path = CRECEWEB_LUMEN_LITE_DIR . 'src/Library/presets/' . $filename;
		if ( ! is_readable( $path ) ) {
			return new \WP_Error( 'lumen_kit_preset_unreadable', __( 'The Kit configuration preset is not available.', 'creceweb-lumen-lite' ) );
		}

		$payload = wp_json_file_decode( $path, array( 'associative' => true ) );
		if (
			! is_array( $payload )
			|| ConfigTransferManager::format_name() !== (string) ( $payload['format'] ?? '' )
			|| ConfigTransferManager::format_version() !== absint( $payload['format_version'] ?? 0 )
		) {
			return new \WP_Error( 'lumen_kit_preset_invalid', __( 'The Kit configuration preset has an unsupported format.', 'creceweb-lumen-lite' ) );
		}

		return $payload;
	}
}
