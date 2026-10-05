<?php
/**
 * Portable configuration import/export owned by Lumen Lite.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\ConfigTransfer;

use CreceWeb\LumenLite\Data\Settings;
use CreceWeb\LumenLite\Support\AdminRedirect;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates a versioned JSON format while products keep ownership of their data.
 */
final class Manager {
	private const FORMAT = 'creceweb-lumen-config';
	private const FORMAT_VERSION = 1;
	private const MAX_IMPORT_BYTES = 1048576;

	/** @return string */
	public static function format_name(): string {
		return self::FORMAT;
	}

	/** @return int */
	public static function format_version(): int {
		return self::FORMAT_VERSION;
	}

	/** @return void */
	public function register(): void {
		add_action( 'admin_post_cw_lumen_lite_export_config', array( $this, 'handle_export' ) );
		add_action( 'admin_post_cw_lumen_lite_import_config', array( $this, 'handle_import' ) );
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	public function sections(): array {
		$lite = array(
			'label'       => __( 'Lumen Lite', 'creceweb-lumen-lite' ),
			'owner'       => 'lite',
			'schema'      => Settings::SCHEMA,
			'version'     => defined( 'CRECEWEB_LUMEN_LITE_VERSION' ) ? (string) CRECEWEB_LUMEN_LITE_VERSION : '',
			'description' => __( 'User-facing Lite tools and presentation settings. Internal onboarding and migration state is excluded.', 'creceweb-lumen-lite' ),
			'export'      => array( $this, 'export_lite' ),
			'import'      => array( $this, 'import_lite' ),
		);

		/*
		 * Active Lumen components may contribute their own portable section.
		 * Lite owns only its native `lite` section and does not know or require
		 * any particular optional component.
		 */
		$filtered = apply_filters(
			'creceweb_lumen_config_transfer_sections',
			array( 'lite' => $lite )
		);
		$filtered = is_array( $filtered ) ? $filtered : array();

		$normalized = array(
			'lite' => $lite,
		);

		foreach ( $filtered as $id => $definition ) {
			$id = sanitize_key( (string) $id );
			if ( '' === $id || 'lite' === $id || ! is_array( $definition ) ) {
				continue;
			}

			$export = $definition['export'] ?? null;
			$import = $definition['import'] ?? null;
			if ( ! is_callable( $export ) || ! is_callable( $import ) ) {
				continue;
			}

			$owner = sanitize_key( (string) ( $definition['owner'] ?? $id ) );
			if ( '' === $owner ) {
				$owner = $id;
			}

			$normalized[ $id ] = array(
				'label'       => sanitize_text_field( (string) ( $definition['label'] ?? ucfirst( $id ) ) ),
				'owner'       => $owner,
				'schema'      => max( 1, absint( $definition['schema'] ?? 1 ) ),
				'version'     => sanitize_text_field( (string) ( $definition['version'] ?? '' ) ),
				'description' => sanitize_text_field( (string) ( $definition['description'] ?? '' ) ),
				'export'      => $export,
				'import'      => $import,
			);
		}

		return $normalized;
	}

	/** @return void */
	public function handle_export(): void {
		$this->require_access( 'cw_lumen_lite_export_config' );

		$payload = array(
			'format'         => self::FORMAT,
			'format_version' => self::FORMAT_VERSION,
			'generated_at'   => gmdate( 'c' ),
			'generator'      => array(
				'product' => 'CreceWeb Lumen Lite',
				'version' => defined( 'CRECEWEB_LUMEN_LITE_VERSION' ) ? (string) CRECEWEB_LUMEN_LITE_VERSION : '',
			),
			'sections'       => array(),
		);

		$available = $this->sections();
		$selected  = isset( $_POST['export_sections'] ) && is_array( $_POST['export_sections'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The export nonce is verified by require_access() before any form data is read.
			? wp_unslash( $_POST['export_sections'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce already verified; each section ID is sanitized and intersected with the active registry below.
			: array();

		$selected = array_values(
			array_unique(
				array_filter(
					array_map(
						static fn( $value ): string => sanitize_key( (string) $value ),
						$selected
					)
				)
			)
		);

		$selected = array_values( array_intersect( $selected, array_keys( $available ) ) );

		if ( empty( $selected ) ) {
			$this->redirect( 'export_selection_required' );
		}

		foreach ( $selected as $id ) {
			$definition = $available[ $id ];
			$data       = call_user_func( $definition['export'] );
			if ( ! is_array( $data ) ) {
				continue;
			}

			$payload['sections'][ $id ] = array(
				'owner'   => $definition['owner'],
				'schema'  => $definition['schema'],
				'version' => $definition['version'],
				'data'    => $data,
			);
		}

		$json = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( ! is_string( $json ) || '' === $json ) {
			wp_die( esc_html__( 'Lumen could not create the configuration file.', 'creceweb-lumen-lite' ) );
		}

		$filename = sanitize_file_name( 'lumen-config-' . gmdate( 'Y-m-d-His' ) . '.json' );
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'X-Content-Type-Options: nosniff' );
		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON is generated by wp_json_encode from sanitized product settings.
		exit;
	}

	/** @return void */
	public function handle_import(): void {
		$this->require_access( 'cw_lumen_lite_import_config' );

		if ( empty( $_POST['confirm_import'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by require_access().
			$this->redirect( 'confirm_required' );
		}

		$file = isset( $_FILES['config_file'] ) && is_array( $_FILES['config_file'] ) ? $_FILES['config_file'] : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Native upload metadata is validated below; file content is JSON-decoded and product-sanitized.
		$error = isset( $file['error'] ) ? absint( $file['error'] ) : UPLOAD_ERR_NO_FILE;
		$size  = isset( $file['size'] ) ? absint( $file['size'] ) : 0;
		$tmp   = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';

		if ( UPLOAD_ERR_OK !== $error || '' === $tmp || $size < 1 || $size > self::MAX_IMPORT_BYTES || ! is_uploaded_file( $tmp ) ) {
			$this->redirect( 'invalid_file' );
		}

		$name = isset( $file['name'] ) ? sanitize_file_name( (string) $file['name'] ) : '';
		if ( 'json' !== strtolower( (string) pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
			$this->redirect( 'invalid_file' );
		}

		$payload = wp_json_file_decode( $tmp, array( 'associative' => true ) );
		if ( ! is_array( $payload ) || self::FORMAT !== (string) ( $payload['format'] ?? '' ) || self::FORMAT_VERSION !== absint( $payload['format_version'] ?? 0 ) ) {
			$this->redirect( 'invalid_format' );
		}

		$incoming = isset( $payload['sections'] ) && is_array( $payload['sections'] ) ? $payload['sections'] : array();
		$applied  = 0;

		foreach ( $this->sections() as $id => $definition ) {
			$section = $incoming[ $id ] ?? null;
			$data    = is_array( $section ) && isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : null;
			if ( null === $data ) {
				continue;
			}

			$result = call_user_func( $definition['import'], $data );
			if ( is_wp_error( $result ) || true !== $result ) {
				continue;
			}

			$applied++;
		}

		$this->redirect( $applied > 0 ? 'imported' : 'no_supported_sections', $applied );
	}

	/**
	 * Lite exports an explicit user-facing allowlist. Migration state, onboarding
	 * state, legacy compatibility payloads, and derived menu state never leave the site.
	 *
	 * @return array<string,mixed>
	 */
	public function export_lite(): array {
		$settings = Settings::get();
		$keys = array(
			'footer_credit_text',
			'content',
			'breadcrumbs',
			'reading_progress',
			'table_of_contents',
			'sharing',
			'related_content',
			'popular_content',
			'posts_grid',
			'color_mode',
			'search_modal',
			'floating_action',
			'messaging',
		);
		$portable = array();

		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $settings ) ) {
				$portable[ $key ] = $settings[ $key ];
			}
		}

		return $portable;
	}

	/**
	 * @param array<string,mixed> $data Imported Lite section.
	 * @return true
	 */
	public function import_lite( array $data ): bool {
		$current = Settings::get();
		$allowed = array_flip(
			array(
				'footer_credit_text',
				'content',
				'breadcrumbs',
				'reading_progress',
				'table_of_contents',
				'sharing',
				'related_content',
				'popular_content',
				'posts_grid',
				'color_mode',
				'search_modal',
				'floating_action',
				'messaging',
			)
		);

		foreach ( array_intersect_key( $data, $allowed ) as $key => $value ) {
			$current[ $key ] = $value;
		}

		Settings::update( $current );

		return true;
	}

	/** @return void */
	public function render(): void {
		$sections = $this->sections();
		$notice = isset( $_GET['cw_lumen_config_notice'] ) ? sanitize_key( wp_unslash( $_GET['cw_lumen_config_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice.
		$count  = isset( $_GET['cw_lumen_config_count'] ) ? absint( $_GET['cw_lumen_config_count'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice count.

		$messages = array(
			'imported'              => array( 'success', sprintf(
				/* translators: %d: Number of product configuration sections imported. */
				_n( '%d configuration section was imported.', '%d configuration sections were imported.', $count, 'creceweb-lumen-lite' ),
				$count
			) ),
			'confirm_required'      => array( 'error', __( 'Confirm the import before applying configuration changes.', 'creceweb-lumen-lite' ) ),
			'invalid_file'          => array( 'error', __( 'Choose a valid Lumen JSON configuration file smaller than 1 MB.', 'creceweb-lumen-lite' ) ),
			'invalid_format'        => array( 'error', __( 'This JSON file is not a supported Lumen configuration export.', 'creceweb-lumen-lite' ) ),
			'no_supported_sections'      => array( 'warning', __( 'The file does not contain configuration sections supported by the active Lumen components on this site.', 'creceweb-lumen-lite' ) ),
			'export_selection_required' => array( 'warning', __( 'Select at least one available configuration section to export.', 'creceweb-lumen-lite' ) ),
		);

		if ( isset( $messages[ $notice ] ) ) {
			echo '<div class="notice notice-' . esc_attr( $messages[ $notice ][0] ) . '"><p>' . esc_html( $messages[ $notice ][1] ) . '</p></div>';
		}
		?>
		<section class="cw-lumen-admin__section">
			<div class="cw-lumen-admin__grid">
				<article class="cw-lumen-admin__card cw-lumen-admin__card--wide">
					<p class="cw-lumen-admin__eyebrow"><?php echo esc_html__( 'Portable configuration', 'creceweb-lumen-lite' ); ?></p>
					<h2><?php echo esc_html__( 'Import / Export', 'creceweb-lumen-lite' ); ?></h2>
					<p><?php echo esc_html__( 'Move supported Lumen settings between staging and production, keep a configuration backup, or attach a reproducible setup to a support case.', 'creceweb-lumen-lite' ); ?></p>
					<p class="description"><?php echo esc_html__( 'Lumen never includes license keys, API tokens, analytics credentials, update credentials, or other secrets in this file.', 'creceweb-lumen-lite' ); ?></p>
				</article>

				<article class="cw-lumen-admin__card">
					<h3><?php echo esc_html__( 'Export configuration', 'creceweb-lumen-lite' ); ?></h3>
					<p><?php echo esc_html__( 'Choose which currently available configuration sections to include in the versioned JSON file.', 'creceweb-lumen-lite' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="cw_lumen_lite_export_config">
						<?php wp_nonce_field( 'cw_lumen_lite_export_config' ); ?>
						<fieldset>
							<legend class="screen-reader-text"><?php echo esc_html__( 'Configuration sections to export', 'creceweb-lumen-lite' ); ?></legend>
							<?php foreach ( $sections as $id => $definition ) : ?>
								<p>
									<label>
										<input type="checkbox" name="export_sections[]" value="<?php echo esc_attr( $id ); ?>" checked>
										<strong><?php echo esc_html( $definition['label'] ); ?></strong>
										<?php if ( '' !== $definition['description'] ) : ?>
											<br><span class="description"><?php echo esc_html( $definition['description'] ); ?></span>
										<?php endif; ?>
									</label>
								</p>
							<?php endforeach; ?>
						</fieldset>
						<p class="description"><?php echo esc_html__( 'You can export one section, several sections, or all available sections together.', 'creceweb-lumen-lite' ); ?></p>
						<p><button class="button button-primary" type="submit"><?php echo esc_html__( 'Download selected JSON', 'creceweb-lumen-lite' ); ?></button></p>
					</form>
				</article>

				<article class="cw-lumen-admin__card">
					<h3><?php echo esc_html__( 'Import configuration', 'creceweb-lumen-lite' ); ?></h3>
					<p><?php echo esc_html__( 'Import applies only to sections registered by active Lumen components. Sections from components that are not active are ignored.', 'creceweb-lumen-lite' ); ?></p>
					<p class="description"><?php echo esc_html__( 'Supported settings in the file replace the corresponding supported settings on this site. Site-specific content, term, menu, or media references may only be meaningful when both sites share the same content IDs.', 'creceweb-lumen-lite' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
						<input type="hidden" name="action" value="cw_lumen_lite_import_config">
						<?php wp_nonce_field( 'cw_lumen_lite_import_config' ); ?>
						<p><input type="file" name="config_file" accept=".json,application/json" required></p>
						<p><label><input type="checkbox" name="confirm_import" value="1" required> <?php echo esc_html__( 'I understand that supported settings in this file will replace the matching Lumen configuration on this site.', 'creceweb-lumen-lite' ); ?></label></p>
						<p><button class="button button-secondary" type="submit"><?php echo esc_html__( 'Import JSON', 'creceweb-lumen-lite' ); ?></button></p>
					</form>
				</article>
			</div>
		</section>
		<?php
	}

	/**
	 * @param string $nonce_action Nonce action.
	 * @return void
	 */
	private function require_access( string $nonce_action ): void {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'Your user account cannot manage Lumen configuration.', 'creceweb-lumen-lite' ) );
		}

		check_admin_referer( $nonce_action );
	}

	/**
	 * @param string $notice Notice identifier.
	 * @param int    $count Imported section count.
	 * @return never
	 */
	private function redirect( string $notice, int $count = 0 ): void {
		$url = apply_filters(
			'creceweb_lumen_lite_admin_section_url',
			admin_url( 'themes.php?page=creceweb-lumen-lite&tab=lite-tools' ),
			'lite-tools',
			''
		);
		$url = add_query_arg(
			array(
				'cw_lumen_config_notice' => sanitize_key( $notice ),
				'cw_lumen_config_count'  => max( 0, $count ),
			),
			(string) $url
		);

		wp_safe_redirect( $url );
		exit;
	}
}
