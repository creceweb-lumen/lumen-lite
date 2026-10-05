<?php
/**
 * Applies small, reversible demo-content additions declared by built-in Kits.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Library;

use CreceWeb\LumenLite\Data\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps demo content separate from portable Theme/Lite settings.
 *
 * Demo data is intentionally conservative: it never replaces an occupied
 * social widget area or an existing Messaging configuration. Restore reverts
 * only unchanged demo values; administrator edits are always preserved.
 */
final class KitDemoContent {
	private const SOCIAL_FOOTER_SIDEBAR = 'social-footer';

	/**
	 * @param array<string,mixed> $definition Demo-content definition from a built-in Kit preset.
	 * @param array<string,mixed> $previous_snapshot Previous demo snapshot from the last Kit application.
	 * @return array<string,mixed> Snapshot metadata for a later safe restore.
	 */
	public function apply( array $definition, array $previous_snapshot = array() ): array {
		$snapshot = array();

		$social = isset( $definition['social_footer'] ) && is_array( $definition['social_footer'] )
			? $definition['social_footer']
			: array();
		if ( ! empty( $social ) ) {
			$previous_social = isset( $previous_snapshot['social_footer'] ) && is_array( $previous_snapshot['social_footer'] )
				? $previous_snapshot['social_footer']
				: array();
			$snapshot['social_footer'] = $this->apply_social_footer( $social, $previous_social );
		}

		$messaging = isset( $definition['messaging'] ) && is_array( $definition['messaging'] )
			? $definition['messaging']
			: array();
		if ( ! empty( $messaging ) ) {
			$previous_messaging = isset( $previous_snapshot['messaging'] ) && is_array( $previous_snapshot['messaging'] )
				? $previous_snapshot['messaging']
				: array();
			$snapshot['messaging'] = $this->apply_messaging( $messaging, $previous_messaging );
		}

		return $snapshot;
	}

	/**
	 * @param array<string,mixed> $snapshot Snapshot returned by apply().
	 * @return void
	 */
	public function restore( array $snapshot ): void {
		$social = isset( $snapshot['social_footer'] ) && is_array( $snapshot['social_footer'] )
			? $snapshot['social_footer']
			: array();
		$this->restore_social_footer( $social );

		$messaging = isset( $snapshot['messaging'] ) && is_array( $snapshot['messaging'] )
			? $snapshot['messaging']
			: array();
		$this->restore_messaging( $messaging );
	}

	/**
	 * @param array<string,mixed> $definition Social footer definition.
	 * @param array<string,mixed> $previous_snapshot Previous generated-social snapshot.
	 * @return array<string,mixed>
	 */
	private function apply_social_footer( array $definition, array $previous_snapshot = array() ): array {
		if ( array_key_exists( 'enabled', $definition ) && empty( $definition['enabled'] ) ) {
			$this->restore_social_footer( $previous_snapshot );
			return array( 'status' => 'disabled' );
		}

		if ( ! is_registered_sidebar( self::SOCIAL_FOOTER_SIDEBAR ) ) {
			return array( 'status' => 'skipped-unavailable' );
		}

		$content = $this->social_block_markup( $definition );
		if ( '' === $content ) {
			return array( 'status' => 'skipped-empty' );
		}

		$refreshed = $this->refresh_previous_social_demo( $content, $previous_snapshot );
		if ( null !== $refreshed ) {
			return $refreshed;
		}

		if ( is_active_sidebar( self::SOCIAL_FOOTER_SIDEBAR ) ) {
			return array( 'status' => 'skipped-existing' );
		}

		$widgets = get_option( 'widget_block', array() );
		$widgets = is_array( $widgets ) ? $widgets : array();
		$number  = $this->next_widget_number( $widgets );
		$id      = 'block-' . $number;

		$widgets[ $number ] = array( 'content' => $content );
		$widgets['_multiwidget'] = 1;
		update_option( 'widget_block', $widgets );
		wp_assign_widget_to_sidebar( $id, self::SOCIAL_FOOTER_SIDEBAR );

		return array(
			'status'        => 'created',
			'widget_id'     => $id,
			'widget_number' => $number,
			'content_hash'  => hash( 'sha256', $content ),
		);
	}


	/**
	 * Refreshes a previously generated Social Icons block only when it is still
	 * byte-for-byte the demo content we created. Administrator edits or moves
	 * are treated as ownership changes and are never overwritten.
	 *
	 * @param string              $content Desired current demo markup.
	 * @param array<string,mixed> $snapshot Previous generated-social snapshot.
	 * @return array<string,mixed>|null
	 */
	private function refresh_previous_social_demo( string $content, array $snapshot ): ?array {
		if ( 'created' !== (string) ( $snapshot['status'] ?? '' ) ) {
			return null;
		}

		$number = absint( $snapshot['widget_number'] ?? 0 );
		$id     = sanitize_key( (string) ( $snapshot['widget_id'] ?? '' ) );
		$hash   = (string) ( $snapshot['content_hash'] ?? '' );
		if ( $number < 1 || '' === $id || '' === $hash ) {
			return null;
		}

		$widgets = get_option( 'widget_block', array() );
		$widgets = is_array( $widgets ) ? $widgets : array();
		$current = isset( $widgets[ $number ]['content'] ) ? (string) $widgets[ $number ]['content'] : '';
		if ( '' === $current || ! hash_equals( $hash, hash( 'sha256', $current ) ) ) {
			return null;
		}

		$sidebars = get_option( 'sidebars_widgets', array() );
		$sidebars = is_array( $sidebars ) ? $sidebars : array();
		$assigned = isset( $sidebars[ self::SOCIAL_FOOTER_SIDEBAR ] ) && is_array( $sidebars[ self::SOCIAL_FOOTER_SIDEBAR ] )
			? $sidebars[ self::SOCIAL_FOOTER_SIDEBAR ]
			: array();
		if ( ! in_array( $id, $assigned, true ) ) {
			return null;
		}

		if ( ! hash_equals( hash( 'sha256', $current ), hash( 'sha256', $content ) ) ) {
			$widgets[ $number ] = array( 'content' => $content );
			$widgets['_multiwidget'] = 1;
			update_option( 'widget_block', $widgets );
		}

		return array(
			'status'        => 'created',
			'widget_id'     => $id,
			'widget_number' => $number,
			'content_hash'  => hash( 'sha256', $content ),
		);
	}

	/**
	 * @param array<string,mixed> $snapshot Social footer snapshot.
	 * @return void
	 */
	private function restore_social_footer( array $snapshot ): void {
		if ( 'created' !== (string) ( $snapshot['status'] ?? '' ) ) {
			return;
		}

		$number = absint( $snapshot['widget_number'] ?? 0 );
		$id     = sanitize_key( (string) ( $snapshot['widget_id'] ?? '' ) );
		$hash   = (string) ( $snapshot['content_hash'] ?? '' );
		if ( $number < 1 || '' === $id || '' === $hash ) {
			return;
		}

		$widgets = get_option( 'widget_block', array() );
		$widgets = is_array( $widgets ) ? $widgets : array();
		$content = isset( $widgets[ $number ]['content'] ) ? (string) $widgets[ $number ]['content'] : '';

		// Preserve any administrator edit instead of treating it as disposable demo data.
		if ( '' === $content || ! hash_equals( $hash, hash( 'sha256', $content ) ) ) {
			return;
		}

		$sidebars = get_option( 'sidebars_widgets', array() );
		$sidebars = is_array( $sidebars ) ? $sidebars : array();
		$current  = isset( $sidebars[ self::SOCIAL_FOOTER_SIDEBAR ] ) && is_array( $sidebars[ self::SOCIAL_FOOTER_SIDEBAR ] )
			? $sidebars[ self::SOCIAL_FOOTER_SIDEBAR ]
			: array();

		// Moving the generated widget elsewhere is an administrator change; keep it.
		if ( ! in_array( $id, $current, true ) ) {
			return;
		}

		unset( $widgets[ $number ] );
		update_option( 'widget_block', $widgets );
		wp_assign_widget_to_sidebar( $id, '' );
	}

	/**
	 * @param array<string,mixed> $definition Demo Messaging settings.
	 * @param array<string,mixed> $previous_snapshot Previous generated-Messaging snapshot.
	 * @return array<string,mixed>
	 */
	private function apply_messaging( array $definition, array $previous_snapshot = array() ): array {
		$settings = Settings::get();
		$current  = isset( $settings['messaging'] ) && is_array( $settings['messaging'] ) ? $settings['messaging'] : array();

		if ( 'created' === (string) ( $previous_snapshot['status'] ?? '' ) ) {
			$previous_hash = (string) ( $previous_snapshot['applied_hash'] ?? '' );
			if ( '' !== $previous_hash && hash_equals( $previous_hash, $this->data_hash( $current ) ) ) {
				$previous = isset( $previous_snapshot['previous'] ) && is_array( $previous_snapshot['previous'] )
					? $previous_snapshot['previous']
					: array();
				Settings::update_messaging( $definition );
				$updated_settings = Settings::get();
				$updated = isset( $updated_settings['messaging'] ) && is_array( $updated_settings['messaging'] )
					? $updated_settings['messaging']
					: array();

				return array(
					'status'       => 'created',
					'previous'     => $previous,
					'applied_hash' => $this->data_hash( $updated ),
				);
			}
		}

		if ( ! $this->is_pristine_messaging( $current ) ) {
			return array( 'status' => 'skipped-existing' );
		}

		$previous = $current;
		Settings::update_messaging( $definition );
		$updated_settings = Settings::get();
		$updated = isset( $updated_settings['messaging'] ) && is_array( $updated_settings['messaging'] )
			? $updated_settings['messaging']
			: array();

		return array(
			'status'       => 'created',
			'previous'     => $previous,
			'applied_hash' => $this->data_hash( $updated ),
		);
	}

	/**
	 * @param array<string,mixed> $snapshot Messaging snapshot.
	 * @return void
	 */
	private function restore_messaging( array $snapshot ): void {
		if ( 'created' !== (string) ( $snapshot['status'] ?? '' ) ) {
			return;
		}

		$previous = isset( $snapshot['previous'] ) && is_array( $snapshot['previous'] ) ? $snapshot['previous'] : array();
		$hash     = (string) ( $snapshot['applied_hash'] ?? '' );
		if ( '' === $hash ) {
			return;
		}

		$settings = Settings::get();
		$current  = isset( $settings['messaging'] ) && is_array( $settings['messaging'] ) ? $settings['messaging'] : array();
		if ( ! hash_equals( $hash, $this->data_hash( $current ) ) ) {
			return;
		}

		Settings::update_messaging( $previous );
	}

	/**
	 * Demo Messaging may populate only the untouched Lite defaults.
	 *
	 * @param array<string,mixed> $messaging Current Messaging settings.
	 * @return bool
	 */
	private function is_pristine_messaging( array $messaging ): bool {
		$providers = isset( $messaging['providers'] ) && is_array( $messaging['providers'] ) ? $messaging['providers'] : array();
		$whatsapp  = isset( $providers['whatsapp'] ) && is_array( $providers['whatsapp'] ) ? $providers['whatsapp'] : array();
		$telegram  = isset( $providers['telegram'] ) && is_array( $providers['telegram'] ) ? $providers['telegram'] : array();
		$messenger = isset( $providers['messenger'] ) && is_array( $providers['messenger'] ) ? $providers['messenger'] : array();
		$signal    = isset( $providers['signal'] ) && is_array( $providers['signal'] ) ? $providers['signal'] : array();
		$devices   = isset( $messaging['devices'] ) && is_array( $messaging['devices'] ) ? $messaging['devices'] : array();

		return empty( $messaging['enabled'] )
			&& 'whatsapp' === (string) ( $messaging['provider'] ?? 'whatsapp' )
			&& 'provider' === (string) ( $messaging['appearance_mode'] ?? 'provider' )
			&& '' === trim( (string) ( $whatsapp['number'] ?? '' ) )
			&& '' === trim( (string) ( $whatsapp['message'] ?? '' ) )
			&& '' === trim( (string) ( $telegram['username'] ?? '' ) )
			&& '' === trim( (string) ( $telegram['message'] ?? '' ) )
			&& '' === trim( (string) ( $messenger['username'] ?? '' ) )
			&& '' === trim( (string) ( $signal['url'] ?? '' ) )
			&& '#0f172a' === strtolower( (string) ( $messaging['background_color'] ?? '#0f172a' ) )
			&& '#ffffff' === strtolower( (string) ( $messaging['icon_color'] ?? '#ffffff' ) )
			&& 46 === absint( $messaging['size'] ?? 46 )
			&& 'right' === (string) ( $messaging['position'] ?? 'right' )
			&& ! empty( $devices['desktop'] )
			&& ! empty( $devices['tablet'] )
			&& ! empty( $devices['mobile'] );
	}

	/**
	 * @param array<string,mixed> $definition Social footer definition.
	 * @return string Serialized core Social Icons block content.
	 */
	private function social_block_markup( array $definition ): string {
		$services = isset( $definition['services'] ) && is_array( $definition['services'] ) ? $definition['services'] : array();
		$inner    = '';

		foreach ( $services as $service ) {
			if ( ! is_array( $service ) ) {
				continue;
			}
			$key = sanitize_key( (string) ( $service['service'] ?? '' ) );
			$url = esc_url_raw( (string) ( $service['url'] ?? '' ) );
			if ( '' === $key || '' === $url ) {
				continue;
			}
			$attrs = wp_json_encode(
				array(
					'url'     => $url,
					'service' => $key,
				),
				JSON_UNESCAPED_SLASHES
			);
			if ( ! is_string( $attrs ) || '' === $attrs ) {
				continue;
			}
			$inner .= '<!-- wp:social-link ' . $attrs . ' /-->';
		}

		if ( '' === $inner ) {
			return '';
		}

		$attrs = wp_json_encode(
			array(
				'className' => 'cw-lumen-kit-demo-socials',
			),
			JSON_UNESCAPED_SLASHES
		);
		$attrs = is_string( $attrs ) ? $attrs : '{}';

		return '<!-- wp:social-links ' . $attrs . ' --><ul class="wp-block-social-links cw-lumen-kit-demo-socials">' . $inner . '</ul><!-- /wp:social-links -->';
	}

	/**
	 * @param array<string,mixed> $data Data to hash deterministically.
	 * @return string
	 */
	private function data_hash( array $data ): string {
		$json = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		return hash( 'sha256', is_string( $json ) ? $json : '' );
	}

	/**
	 * @param array<int|string,mixed> $widgets Existing block-widget instances.
	 * @return int
	 */
	private function next_widget_number( array $widgets ): int {
		$number = 2;
		while ( isset( $widgets[ $number ] ) ) {
			++$number;
		}
		return $number;
	}
}
