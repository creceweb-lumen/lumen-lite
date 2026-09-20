<?php
/**
 * Shared manual, same-origin Lumen Performance controller.
 *
 * The public page is loaded by the administrator's browser in a temporary
 * hidden iframe. PHP never performs an outbound HTTP request.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Performance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MvpController {
	public const SECTION = 'lite-performance';
	public const QUERY_ARG = 'cw_lumen_perf_probe';
	public const NONCE_ACTION = 'cw_lumen_performance_mvp';
	public const AJAX_PREPARE = 'cw_lumen_performance_prepare';
	public const AJAX_RESULT = 'cw_lumen_performance_result';
	public const AJAX_METRICS = 'cw_lumen_performance_metrics';
	public const AJAX_BASELINE_SET = 'cw_lumen_performance_baseline_set';
	public const AJAX_BASELINE_CLEAR = 'cw_lumen_performance_baseline_clear';

	private const PENDING_PREFIX  = 'cw_lumen_perf_pending_';
	private const RESULT_PREFIX   = 'cw_lumen_perf_result_';
	private const LAST_PREFIX     = 'cw_lumen_perf_last_';
	private const BASELINE_PREFIX = 'cw_lumen_perf_baseline_';
	private const PROBE_TTL      = 120;
	private const LAST_TTL       = 1800;
	private string $render_profile = 'lite';
	private bool $probe_report_captured = false;

	/** @return void */
	public function register(): void {
		if ( is_admin() ) {
			add_action( 'wp_ajax_' . self::AJAX_PREPARE, array( $this, 'ajax_prepare' ) );
			add_action( 'wp_ajax_' . self::AJAX_RESULT, array( $this, 'ajax_result' ) );
			add_action( 'wp_ajax_' . self::AJAX_METRICS, array( $this, 'ajax_metrics' ) );
			add_action( 'wp_ajax_' . self::AJAX_BASELINE_SET, array( $this, 'ajax_baseline_set' ) );
			add_action( 'wp_ajax_' . self::AJAX_BASELINE_CLEAR, array( $this, 'ajax_baseline_clear' ) );
		}

		$token = $this->request_token();
		if ( '' !== $token ) {
			add_action( 'send_headers', array( $this, 'send_probe_headers' ), PHP_INT_MAX );
			add_action( 'wp_enqueue_scripts', array( $this, 'prepare_probe_assets' ), PHP_INT_MAX );
			add_action( 'wp_footer', array( $this, 'capture_frontend' ), 19 );
			add_action( 'shutdown', array( $this, 'capture_frontend_fallback' ), PHP_INT_MAX );
		}
	}

	/**
	 * Data localized only on the Performance administration screen.
	 *
	 * @return array<string,mixed>
	 */
	public function ajax_settings( string $profile = 'lite' ): array {
		$profile = $this->normalize_profile( $profile );
		return array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
			'profile' => $profile,
			'actions' => array(
				'prepare'       => self::AJAX_PREPARE,
				'result'        => self::AJAX_RESULT,
				'baselineSet'   => self::AJAX_BASELINE_SET,
				'baselineClear' => self::AJAX_BASELINE_CLEAR,
			),
			'messages' => array(
				'running'           => __( 'Analyzing the selected public URL…', 'creceweb-lumen-lite' ),
				'timeout'           => __( 'The analysis did not finish in time. A page cache or redirect may have prevented the Lumen probe from running.', 'creceweb-lumen-lite' ),
				'error'             => __( 'The analysis could not be completed. Check the URL and try again.', 'creceweb-lumen-lite' ),
				'baselineError'     => __( 'The temporary comparison baseline could not be updated.', 'creceweb-lumen-lite' ),
				'baselineSet'       => __( 'Saving temporary baseline…', 'creceweb-lumen-lite' ),
				'baselineClear'     => __( 'Clearing baseline…', 'creceweb-lumen-lite' ),
				'resultError'       => __( 'Result error', 'creceweb-lumen-lite' ),
				'prepareError'      => __( 'Prepare error', 'creceweb-lumen-lite' ),
				'analysisComplete'  => __( 'Analysis complete. Updating results…', 'creceweb-lumen-lite' ),
				'analysisExpired'   => __( 'The analysis expired.', 'creceweb-lumen-lite' ),
				'refreshFailed'     => __( 'Performance view refresh failed.', 'creceweb-lumen-lite' ),
				'markupUnavailable' => __( 'Performance view markup is unavailable.', 'creceweb-lumen-lite' ),
				// translators: %d is the number of elements matching the active filter.
				'filterCount'       => __( '%d elements', 'creceweb-lumen-lite' ),
				// translators: %1$d is the number of matching elements and %2$d is the total number of elements.
				'filterCountOf'     => __( 'Showing %1$d of %2$d elements', 'creceweb-lumen-lite' ),
			),
		);
	}

	/** @return void */
	public function ajax_prepare(): void {
		$this->guard_ajax_capability();
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$raw_url = filter_input( INPUT_POST, 'url', FILTER_UNSAFE_RAW );
		$raw_url = is_string( $raw_url ) ? sanitize_text_field( $raw_url ) : '';
		$url     = $this->validate_public_url( $raw_url );

		$raw_profile = filter_input( INPUT_POST, 'profile', FILTER_UNSAFE_RAW );
		$profile     = is_string( $raw_profile ) ? sanitize_key( $raw_profile ) : 'lite';
		$profile     = '' !== $profile ? $profile : 'lite';

		if ( ! ProfileRegistry::has( $profile ) ) {
			wp_send_json_error( array( 'message' => __( 'This analysis profile is not available.', 'creceweb-lumen-lite' ) ), 400 );
		}

		if ( is_wp_error( $url ) ) {
			wp_send_json_error(
				array( 'message' => $url->get_error_message() ),
				400
			);
		}

		$token = wp_generate_password( 32, false, false );
		set_transient(
			self::PENDING_PREFIX . $token,
			array(
				'user_id' => get_current_user_id(),
				'url'     => $url,
				'profile' => $profile,
				'created' => time(),
			),
			self::PROBE_TTL
		);

		wp_send_json_success(
			array(
				'token'    => $token,
				'probeUrl' => add_query_arg( self::QUERY_ARG, rawurlencode( $token ), $url ),
			)
		);
	}

	/** @return void */
	public function ajax_metrics(): void {
		$this->guard_ajax_capability();
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$raw_token = filter_input( INPUT_POST, 'token', FILTER_UNSAFE_RAW );
		$raw_token = is_string( $raw_token ) ? sanitize_text_field( $raw_token ) : '';
		$token     = preg_replace( '/[^A-Za-z0-9]/', '', $raw_token );
		$token     = is_string( $token ) ? $token : '';

		if ( '' === $token ) {
			wp_send_json_error( array( 'message' => __( 'Invalid analysis token.', 'creceweb-lumen-lite' ) ), 400 );
		}

		$pending = get_transient( self::PENDING_PREFIX . $token );
		if ( ! is_array( $pending ) || (int) ( $pending['user_id'] ?? 0 ) !== get_current_user_id() ) {
			wp_send_json_error( array( 'message' => __( 'This analysis token is no longer valid.', 'creceweb-lumen-lite' ) ), 403 );
		}

		$raw = filter_input( INPUT_POST, 'resources', FILTER_UNSAFE_RAW );
		$raw = is_string( $raw ) ? $raw : '[]';

		if ( strlen( $raw ) > 131072 ) {
			wp_send_json_error( array( 'message' => __( 'The browser metrics payload is too large.', 'creceweb-lumen-lite' ) ), 400 );
		}

		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			$decoded = array();
		}

		$resources = array();
		foreach ( array_slice( $decoded, 0, 100 ) as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$url = isset( $item['url'] ) ? esc_url_raw( (string) $item['url'], array( 'http', 'https' ) ) : '';
			$profile = sanitize_key( (string) ( $pending['profile'] ?? 'lite' ) );
			if ( '' === $url || ! $this->resource_allowed_for_profile( $url, $profile ) ) {
				continue;
			}

			$resources[] = array(
				'url'              => preg_replace( '/[?#].*$/', '', $url ),
				'transferSize'     => isset( $item['transferSize'] ) ? max( 0, (int) $item['transferSize'] ) : 0,
				'encodedBodySize'  => isset( $item['encodedBodySize'] ) ? max( 0, (int) $item['encodedBodySize'] ) : 0,
				'decodedBodySize'  => isset( $item['decodedBodySize'] ) ? max( 0, (int) $item['decodedBodySize'] ) : 0,
				'duration'         => isset( $item['duration'] ) ? max( 0, (float) $item['duration'] ) : 0.0,
				'initiatorType'    => isset( $item['initiatorType'] ) ? sanitize_key( (string) $item['initiatorType'] ) : '',
			);
		}

		$report = get_transient( self::RESULT_PREFIX . $token );
		if ( is_array( $report ) && isset( $report['report'] ) && is_array( $report['report'] ) ) {
			$report['report']['browser_metrics'] = $this->browser_metrics_summary( $resources );
			if ( isset( $report['report']['analysis'] ) && is_array( $report['report']['analysis'] ) ) {
				$report['report']['analysis']['browser_metrics_pending'] = false;
			}
			set_transient( self::RESULT_PREFIX . $token, $report, self::PROBE_TTL );
		}

		wp_send_json_success(
			array(
				'accepted' => count( $resources ),
			)
		);
	}

	/** @return void */
	public function ajax_result(): void {
		$this->guard_ajax_capability();
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$raw_token = filter_input( INPUT_POST, 'token', FILTER_UNSAFE_RAW );
		$raw_token = is_string( $raw_token ) ? sanitize_text_field( $raw_token ) : '';
		$token     = preg_replace( '/[^A-Za-z0-9]/', '', $raw_token );
		$token     = is_string( $token ) ? $token : '';

		if ( '' === $token ) {
			wp_send_json_error( array( 'message' => __( 'Invalid analysis token.', 'creceweb-lumen-lite' ) ), 400 );
		}

		$stored = get_transient( self::RESULT_PREFIX . $token );
		if ( ! is_array( $stored ) ) {
			$pending = get_transient( self::PENDING_PREFIX . $token );
			wp_send_json_success(
				array(
					'ready'   => false,
					'expired' => ! is_array( $pending ),
				)
			);
		}

		if ( (int) ( $stored['user_id'] ?? 0 ) !== get_current_user_id() || ! isset( $stored['report'] ) || ! is_array( $stored['report'] ) ) {
			delete_transient( self::RESULT_PREFIX . $token );
			wp_send_json_error( array( 'message' => __( 'This analysis result does not belong to the current user.', 'creceweb-lumen-lite' ) ), 403 );
		}

		$profile_error = (string) ( $stored['report']['error'] ?? '' );
		if ( in_array( $profile_error, array( 'analysis_profile_unavailable', 'analysis_profile_invalid_scope' ), true ) ) {
			delete_transient( self::RESULT_PREFIX . $token );
			delete_transient( self::PENDING_PREFIX . $token );
			$message = 'analysis_profile_invalid_scope' === $profile_error
				? __( 'The analyzed page returned a report that does not match the requested analysis profile. No misleading result was saved.', 'creceweb-lumen-lite' )
				: __( 'The requested analysis profile was not available on the analyzed page. No fallback result was saved.', 'creceweb-lumen-lite' );
			wp_send_json_error(
				array( 'message' => $message ),
				409
			);
		}

		$metrics_pending = ! empty( $stored['report']['analysis']['browser_metrics_pending'] );
		$profile         = $this->normalize_profile( (string) ( $stored['report']['analysis']['profile'] ?? 'lite' ) );
		$captured_at     = (int) ( $stored['report']['analysis']['captured_at'] ?? 0 );

		if ( $metrics_pending && $captured_at > 0 && ( time() - $captured_at ) < 5 ) {
			wp_send_json_success(
				array(
					'ready'   => false,
					'expired' => false,
				)
			);
		}

		if ( $metrics_pending && isset( $stored['report']['analysis'] ) && is_array( $stored['report']['analysis'] ) ) {
			$stored['report']['analysis']['browser_metrics_pending'] = false;
			$stored['report']['browser_metrics'] = array(
				'available'          => false,
				'resource_count'     => 0,
				'transfer_bytes'     => 0,
				'encoded_bytes'      => 0,
				'decoded_bytes'      => 0,
				'css_transfer_bytes' => 0,
				'js_transfer_bytes'  => 0,
			);
		}

		set_transient(
			$this->last_key( $profile, get_current_user_id() ),
			$stored['report'],
			self::LAST_TTL
		);
		delete_transient( self::RESULT_PREFIX . $token );
		delete_transient( self::PENDING_PREFIX . $token );

		wp_send_json_success(
			array(
				'ready'    => true,
				'redirect' => $this->panel_url( 'summary', 'complete', $profile ),
			)
		);
	}

	/** @return void */
	public function ajax_baseline_set(): void {
		$this->guard_ajax_capability();
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$raw_profile = filter_input( INPUT_POST, 'profile', FILTER_UNSAFE_RAW );
		$profile     = is_string( $raw_profile ) ? sanitize_key( $raw_profile ) : 'lite';
		$profile     = $this->normalize_profile( $profile );

		$raw_mode = filter_input( INPUT_POST, 'mode', FILTER_UNSAFE_RAW );
		$mode     = is_string( $raw_mode ) ? $this->normalize_comparison_mode( $raw_mode ) : 'before_after';

		$report = $this->last_report( $profile );
		if ( null === $report || empty( $report['analysis']['url'] ) || empty( $report['analysis']['device'] ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Run a fresh analysis before selecting a comparison baseline.', 'creceweb-lumen-lite' ) ),
				400
			);
		}

		$report['comparison_baseline_saved_at'] = gmdate( 'c' );
		set_transient(
			$this->baseline_key( $profile, get_current_user_id() ),
			$report,
			self::LAST_TTL
		);

		wp_send_json_success(
			array(
				'redirect' => $this->comparison_url( $mode, 'baseline-set', $profile ),
			)
		);
	}

	/** @return void */
	public function ajax_baseline_clear(): void {
		$this->guard_ajax_capability();
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$raw_profile = filter_input( INPUT_POST, 'profile', FILTER_UNSAFE_RAW );
		$profile     = is_string( $raw_profile ) ? sanitize_key( $raw_profile ) : 'lite';
		$profile     = $this->normalize_profile( $profile );

		$raw_mode = filter_input( INPUT_POST, 'mode', FILTER_UNSAFE_RAW );
		$mode     = is_string( $raw_mode ) ? $this->normalize_comparison_mode( $raw_mode ) : 'before_after';

		delete_transient( $this->baseline_key( $profile, get_current_user_id() ) );

		wp_send_json_success(
			array(
				'redirect' => $this->comparison_url( $mode, 'baseline-cleared', $profile ),
			)
		);
	}

	/** @return void */
	public function send_probe_headers(): void {
		if ( ! headers_sent() ) {
			nocache_headers();
			header( 'X-Robots-Tag: noindex, nofollow', true );
		}
	}

	/** @return void */
	public function prepare_probe_assets(): void {
		$token = $this->request_token();
		if ( '' === $token ) {
			return;
		}

		$pending = get_transient( self::PENDING_PREFIX . $token );
		if ( ! is_array( $pending ) || empty( $pending['url'] ) || empty( $pending['user_id'] ) ) {
			return;
		}

		$profile = $this->strict_profile( (string) ( $pending['profile'] ?? '' ) );
		if ( '' === $profile ) {
			$this->store_profile_unavailable_result( $token, $pending );
			return;
		}

		wp_enqueue_script(
			'creceweb-lumen-lite-performance-probe',
			CRECEWEB_LUMEN_LITE_URL . 'assets/js/performance-probe.js',
			array(),
			CRECEWEB_LUMEN_LITE_ASSET_VERSION,
			true
		);
		wp_localize_script(
			'creceweb-lumen-lite-performance-probe',
			'cwLumenPerformanceProbe',
			array(
				'enabled'      => true,
				'token'        => $token,
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'action'       => self::AJAX_METRICS,
				'nonce'        => wp_create_nonce( self::NONCE_ACTION ),
				'allowedPaths' => $this->profile_allowed_paths( $profile ),
			)
		);
	}

	/** @return void */
	public function capture_frontend(): void {
		if ( $this->probe_report_captured ) {
			return;
		}

		$token = $this->request_token();
		if ( '' === $token ) {
			return;
		}

		$pending = get_transient( self::PENDING_PREFIX . $token );
		if ( ! is_array( $pending ) || empty( $pending['url'] ) || empty( $pending['user_id'] ) ) {
			return;
		}

		$profile = $this->strict_profile( (string) ( $pending['profile'] ?? '' ) );
		if ( '' === $profile ) {
			$this->store_profile_unavailable_result( $token, $pending );
			$this->probe_report_captured = true;
			return;
		}

		$report  = ProfileRegistry::diagnose(
			$profile,
			array(
				'context' => 'frontend',
				'ready'   => true,
			)
		);

		if ( ! ProfileRegistry::validate_report( $profile, $report ) ) {
			$this->store_profile_failure_result( $token, $pending, 'analysis_profile_invalid_scope' );
			$this->probe_report_captured = true;
			return;
		}

		$report['analysis'] = array(
			'url'                     => esc_url_raw( (string) $pending['url'] ),
			'analyzed_at'             => gmdate( 'c' ),
			'captured_at'             => time(),
			'current_site'            => esc_url_raw( home_url( '/' ) ),
			'multisite'               => is_multisite(),
			'profile'                 => $profile,
			'device'                  => wp_is_mobile() ? 'mobile' : 'desktop',
			'browser_metrics_pending' => true,
		);

		set_transient(
			self::RESULT_PREFIX . $token,
			array(
				'user_id' => (int) $pending['user_id'],
				'report'  => $report,
			),
			self::PROBE_TTL
		);

		$this->probe_report_captured = true;
	}

	/** @return void */
	public function capture_frontend_fallback(): void {
		if ( ! $this->probe_report_captured ) {
			$this->capture_frontend();
		}
	}

	/**
	 * Last temporary result for the current administrator.
	 *
	 * @return array<string,mixed>|null
	 */
	public function last_report( string $profile = 'lite' ): ?array {
		if ( ! is_user_logged_in() ) {
			return null;
		}

		$profile = $this->normalize_profile( $profile );
		$report = get_transient( $this->last_key( $profile, get_current_user_id() ) );
		return is_array( $report ) ? $report : null;
	}

	/**
	 * Temporary, manually selected comparison baseline for this administrator.
	 *
	 * @return array<string,mixed>|null
	 */
	public function baseline_report( string $profile = 'lite' ): ?array {
		if ( ! is_user_logged_in() ) {
			return null;
		}

		$profile = $this->normalize_profile( $profile );
		$report  = get_transient( $this->baseline_key( $profile, get_current_user_id() ) );
		return is_array( $report ) ? $report : null;
	}

	/**
	 * Render the shared Lumen Performance views.
	 *
	 * @return void
	 */
	public function render( string $profile = 'lite' ): void {
		$this->render_profile = $this->normalize_profile( $profile );
		$view = isset( $_GET['perf_view'] ) ? sanitize_key( wp_unslash( $_GET['perf_view'] ) ) : 'summary'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation.
		$allowed_views = array( 'summary', 'resources', 'modules', 'recommendations', 'comparison' );
		if ( $this->profile_has_budgets() ) {
			$allowed_views[] = 'budgets';
		}
		$custom_views  = $this->profile_custom_views();
		$allowed_views = array_merge( $allowed_views, array_keys( $custom_views ) );
		$view          = in_array( $view, $allowed_views, true ) ? $view : 'summary';
		$report        = $this->last_report( $this->render_profile );
		?>
		<div class="cw-lumen-performance-shell" data-cw-lumen-performance-shell data-cw-performance-active-view="<?php echo esc_attr( $view ); ?>">
			<?php $this->render_intro( $report ); ?>
			<?php $this->render_analysis_form( $report ); ?>
			<?php $this->render_views( $view ); ?>

			<?php if ( null === $report ) : ?>
				<?php $this->render_empty(); ?>
			<?php else : ?>
				<section class="cw-lumen-performance__view-panel" data-cw-performance-view-panel="summary"<?php echo 'summary' === $view ? '' : ' hidden'; ?>><?php $this->render_summary( $report ); ?></section>
				<section class="cw-lumen-performance__view-panel" data-cw-performance-view-panel="resources"<?php echo 'resources' === $view ? '' : ' hidden'; ?>><?php $this->render_resources( $report ); ?></section>
				<section class="cw-lumen-performance__view-panel" data-cw-performance-view-panel="modules"<?php echo 'modules' === $view ? '' : ' hidden'; ?>><?php $this->render_modules( $report ); ?></section>
				<section class="cw-lumen-performance__view-panel" data-cw-performance-view-panel="recommendations"<?php echo 'recommendations' === $view ? '' : ' hidden'; ?>><?php $this->render_recommendations( $report ); ?></section>
				<section class="cw-lumen-performance__view-panel" data-cw-performance-view-panel="comparison"<?php echo 'comparison' === $view ? '' : ' hidden'; ?>><?php $this->render_comparison( $report ); ?></section>
				<?php if ( $this->profile_has_budgets() ) : ?>
					<section class="cw-lumen-performance__view-panel" data-cw-performance-view-panel="budgets"<?php echo 'budgets' === $view ? '' : ' hidden'; ?>><?php $this->render_budgets( $report ); ?></section>
				<?php endif; ?>
				<?php foreach ( $custom_views as $custom_id => $custom_view ) : ?>
					<section class="cw-lumen-performance__view-panel" data-cw-performance-view-panel="<?php echo esc_attr( $custom_id ); ?>"<?php echo $custom_id === $view ? '' : ' hidden'; ?>>
						<?php
						call_user_func(
							$custom_view['render'],
							$report,
							array(
								'profile'  => $this->render_profile,
								'baseline' => $this->baseline_report( $this->render_profile ),
							)
						);
						?>
					</section>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * @param array<string,mixed>|null $report Report.
	 * @return void
	 */
	private function render_intro( ?array $report ): void {
		?>
		<section class="cw-lumen-performance">
			<div class="cw-lumen-admin__intro">
				<p class="cw-lumen-admin__eyebrow"><?php echo esc_html__( 'Lumen Performance', 'creceweb-lumen-lite' ); ?></p>
				<h2><?php echo esc_html__( 'Health and performance of your Lumen setup', 'creceweb-lumen-lite' ); ?></h2>
				<p><?php echo esc_html( $this->profile_copy( 'intro_description', __( 'Analyze one public URL on this site to see which Lumen resources load, why they are present, and whether the result matches the known Theme and Lite rules.', 'creceweb-lumen-lite' ) ) ); ?></p>
			</div>
			<div class="cw-lumen-performance__scope" role="note">
				<strong><?php echo esc_html__( 'Lumen-only scope.', 'creceweb-lumen-lite' ); ?></strong>
				<?php echo esc_html__( 'This panel does not score or recommend changes to third-party plugins, themes, hosting, or external services.', 'creceweb-lumen-lite' ); ?>
			</div>
			<?php if ( null !== $report && isset( $report['analysis']['url'] ) ) : ?>
				<p class="cw-lumen-performance__last">
					<strong><?php echo esc_html__( 'Last analyzed URL:', 'creceweb-lumen-lite' ); ?></strong>
					<code><?php echo esc_html( (string) $report['analysis']['url'] ); ?></code>
				</p>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * @param array<string,mixed>|null $report Report.
	 * @return void
	 */
	private function render_analysis_form( ?array $report ): void {
		$url = null !== $report && isset( $report['analysis']['url'] )
			? (string) $report['analysis']['url']
			: home_url( '/' );
		?>
		<section class="cw-lumen-performance__analyzer" aria-labelledby="cw-lumen-performance-analyzer-title">
			<div>
				<h3 id="cw-lumen-performance-analyzer-title"><?php echo esc_html__( 'Analyze a public URL', 'creceweb-lumen-lite' ); ?></h3>
				<p><?php echo esc_html__( 'The browser loads the selected URL once with a temporary Lumen probe. No external performance service is contacted. Only the latest result and an optional manual comparison baseline are kept temporarily for this administrator.', 'creceweb-lumen-lite' ); ?></p>
			</div>
			<form class="cw-lumen-performance__form" data-cw-lumen-performance-form>
				<label for="cw-lumen-performance-url"><?php echo esc_html__( 'Public URL on this site', 'creceweb-lumen-lite' ); ?></label>
				<div class="cw-lumen-performance__form-row">
					<input id="cw-lumen-performance-url" name="performance_url" type="url" required value="<?php echo esc_attr( $url ); ?>">
					<button class="button button-primary" type="submit"><?php echo esc_html__( 'Run analysis', 'creceweb-lumen-lite' ); ?></button>
				</div>
				<p class="description"><?php echo esc_html__( 'For privacy and security, the MVP accepts only public URLs from the current site and rejects admin, login, preview, nonce, password, or token URLs.', 'creceweb-lumen-lite' ); ?></p>
				<div class="cw-lumen-performance__progress" hidden data-cw-lumen-performance-progress role="status" aria-live="polite"></div>
				<iframe class="cw-lumen-performance__probe" hidden title="<?php echo esc_attr__( 'Temporary Lumen performance probe', 'creceweb-lumen-lite' ); ?>" data-cw-lumen-performance-probe></iframe>
			</form>
		</section>
		<?php
	}

	/**
	 * @param string $active Active view.
	 * @return void
	 */
	private function render_views( string $active ): void {
		$tabs = array(
			'summary'         => __( 'Overview', 'creceweb-lumen-lite' ),
			'resources'       => __( 'Assets', 'creceweb-lumen-lite' ),
			'modules'         => __( 'Features', 'creceweb-lumen-lite' ),
			'recommendations' => __( 'Actions', 'creceweb-lumen-lite' ),
			'comparison'      => __( 'Compare', 'creceweb-lumen-lite' ),
		);
		if ( $this->profile_has_budgets() ) {
			$tabs['budgets'] = __( 'Limits', 'creceweb-lumen-lite' );
		}
		foreach ( $this->profile_custom_views() as $id => $custom_view ) {
			$tabs[ $id ] = (string) $custom_view['label'];
		}
		?>
		<nav class="nav-tab-wrapper cw-lumen-performance__tabs" aria-label="<?php echo esc_attr__( 'Lumen Performance views', 'creceweb-lumen-lite' ); ?>" data-cw-performance-tabs>
			<?php foreach ( $tabs as $id => $label ) : ?>
				<a class="nav-tab<?php echo $active === $id ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( $this->panel_url( $id ) ); ?>" data-cw-performance-tab="<?php echo esc_attr( $id ); ?>" aria-selected="<?php echo $active === $id ? 'true' : 'false'; ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/** @return void */
	private function render_empty(): void {
		?>
		<div class="cw-lumen-performance__empty">
			<h3><?php echo esc_html__( 'No analysis yet', 'creceweb-lumen-lite' ); ?></h3>
			<p><?php echo esc_html__( 'Run a manual analysis to create a temporary result for this administrator. Performance keeps only the latest result for 30 minutes.', 'creceweb-lumen-lite' ); ?></p>
		</div>
		<?php
	}

	/**
	 * @param array<string,mixed> $report Report.
	 * @return void
	 */
	private function render_summary( array $report ): void {
		$metrics      = $this->metrics( $report );
		$state        = $this->overall_state( $report );
		$friendly     = $this->friendly_overall_state( $state );
		$browser      = isset( $report['browser_metrics'] ) && is_array( $report['browser_metrics'] ) ? $report['browser_metrics'] : array();
		$issues       = $this->actionable_issue_count( $report );
		$payload      = $metrics['css_bytes'] + $metrics['js_bytes'];
		$conditional  = $this->conditional_loading_summary( $report );
		$footprint    = $this->provider_footprint( $report );
		$contributors = $this->top_contributors( $report, 5 );
		?>
		<div class="cw-lumen-performance__state cw-lumen-performance__state--<?php echo esc_attr( $state['key'] ); ?>">
			<div>
				<span class="cw-lumen-performance__state-label"><?php echo esc_html__( 'Lumen status', 'creceweb-lumen-lite' ); ?></span>
				<strong><?php echo esc_html( $friendly['label'] ); ?></strong>
			</div>
			<div>
				<p><?php echo esc_html( $friendly['description'] ); ?></p>
				<p class="cw-lumen-performance__scope-note"><?php echo esc_html__( 'This describes Lumen resource loading only. It is not a Lighthouse score or a score for WordPress, hosting, images, or third-party code.', 'creceweb-lumen-lite' ); ?></p>
			</div>
		</div>

		<div class="cw-lumen-performance__metrics cw-lumen-performance__metrics--primary">
			<?php $this->metric_card( __( 'Lumen payload', 'creceweb-lumen-lite' ), $this->format_bytes( $payload ), __( 'Packaged CSS + JavaScript loaded', 'creceweb-lumen-lite' ) ); ?>
			<?php $this->metric_card( __( 'Loaded assets', 'creceweb-lumen-lite' ), (string) $metrics['observed'], __( 'Lumen handles observed on this page', 'creceweb-lumen-lite' ) ); ?>
			<?php $this->metric_card( __( 'Requests', 'creceweb-lumen-lite' ), (string) $metrics['requests'], __( 'Loaded Lumen assets with a source URL', 'creceweb-lumen-lite' ) ); ?>
			<?php $this->metric_card( __( 'Items to review', 'creceweb-lumen-lite' ), (string) $issues, 0 === $issues ? __( 'No action required by current rules', 'creceweb-lumen-lite' ) : __( 'Open Actions for the review list', 'creceweb-lumen-lite' ) ); ?>
		</div>

		<div class="cw-lumen-performance__guided-grid">
			<section class="cw-lumen-performance__guided-card">
				<div class="cw-lumen-performance__section-heading cw-lumen-performance__section-heading--inside">
					<h3><?php echo esc_html__( 'What this means', 'creceweb-lumen-lite' ); ?></h3>
				</div>
				<?php $this->render_meaning_list( $report, $issues, $conditional ); ?>
			</section>
			<section class="cw-lumen-performance__guided-card">
				<div class="cw-lumen-performance__section-heading cw-lumen-performance__section-heading--inside">
					<h3><?php echo esc_html__( 'Loaded code', 'creceweb-lumen-lite' ); ?></h3>
					<p><?php echo esc_html__( 'Packaged inventory for the current Lumen analysis.', 'creceweb-lumen-lite' ); ?></p>
				</div>
				<div class="cw-lumen-performance__mini-metrics">
					<div><span><?php echo esc_html__( 'CSS', 'creceweb-lumen-lite' ); ?></span><strong><?php echo esc_html( $this->format_bytes( $metrics['css_bytes'] ) ); ?></strong></div>
					<div><span><?php echo esc_html__( 'JavaScript', 'creceweb-lumen-lite' ); ?></span><strong><?php echo esc_html( $this->format_bytes( $metrics['js_bytes'] ) ); ?></strong></div>
				</div>
			</section>
		</div>

		<?php if ( $this->profile_has_budgets() ) : ?>
			<?php $this->render_overview_limits( $report ); ?>
		<?php endif; ?>

		<div class="cw-lumen-performance__guided-grid">
			<section class="cw-lumen-performance__guided-card">
				<div class="cw-lumen-performance__section-heading cw-lumen-performance__section-heading--inside">
					<h3><?php echo esc_html__( 'Lumen footprint by product', 'creceweb-lumen-lite' ); ?></h3>
					<p><?php echo esc_html__( 'Shows which Lumen product contributes the packaged assets loaded on this page.', 'creceweb-lumen-lite' ); ?></p>
				</div>
				<?php $this->render_provider_footprint( $footprint ); ?>
			</section>
			<section class="cw-lumen-performance__guided-card">
				<div class="cw-lumen-performance__section-heading cw-lumen-performance__section-heading--inside">
					<h3><?php echo esc_html__( 'Conditional loading', 'creceweb-lumen-lite' ); ?></h3>
				</div>
				<?php if ( $conditional['count'] > 0 ) : ?>
					<strong class="cw-lumen-performance__big-number"><?php echo esc_html( $this->format_bytes( $conditional['bytes'] ) ); ?></strong>
					<?php /* translators: %d: number of configured Lumen assets not needed on the current page. */ ?>
					<p><?php echo esc_html( sprintf( __( '%d configured Lumen assets stayed off this page because the current rules did not detect a need for them.', 'creceweb-lumen-lite' ), $conditional['count'] ) ); ?></p>
					<p class="cw-lumen-performance__scope-note"><?php echo esc_html__( 'This is packaged asset size kept out of the current inventory, not measured network savings.', 'creceweb-lumen-lite' ); ?></p>
				<?php else : ?>
					<p><?php echo esc_html__( 'No configured-but-unused packaged Lumen assets were identified in this analysis.', 'creceweb-lumen-lite' ); ?></p>
				<?php endif; ?>
			</section>
		</div>

		<section class="cw-lumen-performance__guided-card cw-lumen-performance__guided-card--wide">
			<div class="cw-lumen-performance__section-heading cw-lumen-performance__section-heading--inside">
				<h3><?php echo esc_html__( 'Largest Lumen assets on this page', 'creceweb-lumen-lite' ); ?></h3>
				<p><?php echo esc_html__( 'Top loaded packaged assets. Each bar shows that asset’s share of the current loaded Lumen payload; it is a contribution indicator, not a health score.', 'creceweb-lumen-lite' ); ?></p>
			</div>
			<?php $this->render_top_contributors( $contributors, $payload ); ?>
		</section>

		<details class="cw-lumen-performance__disclosure">
			<summary><?php echo esc_html__( 'Browser transfer context', 'creceweb-lumen-lite' ); ?></summary>
			<div class="cw-lumen-performance__transfer">
				<p><?php echo esc_html__( 'Browser Resource Timing is cache-sensitive. Zero bytes can mean memory cache, service worker, or timing data unavailable to the browser.', 'creceweb-lumen-lite' ); ?></p>
				<?php if ( ! empty( $browser['available'] ) ) : ?>
					<div class="cw-lumen-performance__metrics cw-lumen-performance__metrics--transfer">
						<?php $this->metric_card( __( 'Transferred CSS', 'creceweb-lumen-lite' ), $this->format_bytes( (int) ( $browser['css_transfer_bytes'] ?? 0 ) ), __( 'Browser-reported transfer', 'creceweb-lumen-lite' ) ); ?>
						<?php $this->metric_card( __( 'Transferred JavaScript', 'creceweb-lumen-lite' ), $this->format_bytes( (int) ( $browser['js_transfer_bytes'] ?? 0 ) ), __( 'Browser-reported transfer', 'creceweb-lumen-lite' ) ); ?>
						<?php $this->metric_card( __( 'Transferred total', 'creceweb-lumen-lite' ), $this->format_bytes( (int) ( $browser['transfer_bytes'] ?? 0 ) ), $this->profile_copy( 'transfer_scope', __( 'Theme + Lite resources detected', 'creceweb-lumen-lite' ) ) ); ?>
						<?php $this->metric_card( __( 'Timed resources', 'creceweb-lumen-lite' ), (string) (int) ( $browser['resource_count'] ?? 0 ), __( 'Resource Timing entries', 'creceweb-lumen-lite' ) ); ?>
					</div>
				<?php else : ?>
					<p><?php echo esc_html__( 'The browser did not expose transfer metrics for this analysis. The packaged inventory above remains available.', 'creceweb-lumen-lite' ); ?></p>
				<?php endif; ?>
			</div>
		</details>

		<details class="cw-lumen-performance__disclosure">
			<summary><?php echo esc_html__( 'Detected Lumen versions', 'creceweb-lumen-lite' ); ?></summary>
			<div class="cw-lumen-performance__component-grid cw-lumen-performance__component-grid--inside">
				<?php foreach ( $this->profile_components( $report ) as $component ) : ?>
					<?php if ( ! is_array( $component ) || empty( $component['name'] ) ) { continue; } ?>
					<?php $this->component_card( sanitize_text_field( (string) $component['name'] ), sanitize_text_field( (string) ( $component['version'] ?? '' ) ), ! empty( $component['active'] ) ); ?>
				<?php endforeach; ?>
			</div>
		</details>

		<p class="cw-lumen-performance__footnote"><?php echo esc_html__( 'Displayed packaged bytes are uncompressed manifest file sizes. They are not transfer size, Lighthouse score, or total page weight.', 'creceweb-lumen-lite' ); ?></p>
		<?php
	}

	/**
	 * @param array<string,mixed> $report Report.
	 * @return void
	 */
	private function render_resources( array $report ): void {
		$results = isset( $report['results'] ) && is_array( $report['results'] ) ? $report['results'] : array();
		?>
		<div class="cw-lumen-performance__section-heading">
			<h3><?php echo esc_html__( 'Assets on this page', 'creceweb-lumen-lite' ); ?></h3>
			<p><?php echo esc_html__( 'See what loaded, what stayed off the page, and why Lumen expected each asset.', 'creceweb-lumen-lite' ); ?></p>
		</div>
		<?php if ( $this->profile_filters_enabled( 'resources' ) ) : ?><?php $this->render_filter_bar( 'resource', array( 'component', 'type', 'state' ) ); ?><?php endif; ?>
		<div class="cw-lumen-performance__table-wrap">
			<table class="widefat striped cw-lumen-performance__table cw-lumen-performance__table--assets">
				<thead><tr>
					<th><?php echo esc_html__( 'Product', 'creceweb-lumen-lite' ); ?></th>
					<th><?php echo esc_html__( 'Feature', 'creceweb-lumen-lite' ); ?></th>
					<th><?php echo esc_html__( 'Asset', 'creceweb-lumen-lite' ); ?></th>
					<th><?php echo esc_html__( 'Type', 'creceweb-lumen-lite' ); ?></th>
					<th><?php echo esc_html__( 'What happened', 'creceweb-lumen-lite' ); ?></th>
					<th><?php echo esc_html__( 'Packaged size', 'creceweb-lumen-lite' ); ?></th>
					<th><?php echo esc_html__( 'Why', 'creceweb-lumen-lite' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $results as $result ) : ?>
					<?php if ( ! is_array( $result ) ) { continue; } ?>
					<?php
					$state           = $this->friendly_resource_state( $result );
					$provider        = (string) ( $result['provider'] ?? '' );
					$provider_label  = $this->provider_label( $provider );
					$resource_type   = strtolower( (string) ( $result['type'] ?? '' ) );
					$resource_module = (string) ( $result['module'] ?? '' );
					?>
					<tr data-cw-performance-item="resource" data-filter-component="<?php echo esc_attr( sanitize_key( $provider ) ); ?>" data-filter-component-label="<?php echo esc_attr( $provider_label ); ?>" data-filter-type="<?php echo esc_attr( sanitize_key( $resource_type ) ); ?>" data-filter-type-label="<?php echo esc_attr( strtoupper( $resource_type ) ); ?>" data-filter-state="<?php echo esc_attr( $state['key'] ); ?>" data-filter-state-label="<?php echo esc_attr( $state['label'] ); ?>">
						<td><?php echo esc_html( $provider_label ); ?></td>
						<td><?php echo esc_html( $this->module_label( $resource_module, $provider ) ); ?></td>
						<td><code><?php echo esc_html( (string) ( $result['handle'] ?? '' ) ); ?></code><?php if ( ! empty( $result['duplicate_source'] ) ) : ?> <span class="cw-lumen-performance__duplicate"><?php echo esc_html__( 'duplicate', 'creceweb-lumen-lite' ); ?></span><?php endif; ?></td>
						<td><?php echo esc_html( strtoupper( $resource_type ) ); ?></td>
						<td><span class="cw-lumen-performance__badge cw-lumen-performance__badge--<?php echo esc_attr( $state['key'] ); ?>"><?php echo esc_html( $state['label'] ); ?></span></td>
						<td><?php echo esc_html( $this->format_bytes( isset( $result['size_bytes'] ) ? (int) $result['size_bytes'] : 0 ) ); ?></td>
						<td class="cw-lumen-performance__why">
							<?php echo esc_html( $this->resource_explanation( $result ) ); ?>
							<details><summary><?php echo esc_html__( 'Technical details', 'creceweb-lumen-lite' ); ?></summary><code><?php echo esc_html( $this->resource_counts_label( $result ) ); ?></code></details>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php if ( $this->profile_filters_enabled( 'resources' ) ) : ?><p class="cw-lumen-performance__filter-empty" data-cw-performance-filter-empty="resource" hidden><?php echo esc_html__( 'No assets match the selected filters.', 'creceweb-lumen-lite' ); ?></p><?php endif; ?>
		<p class="cw-lumen-performance__footnote"><?php echo esc_html__( 'Technical details use C/U/E/L: Configured / Used / Expected / Loaded.', 'creceweb-lumen-lite' ); ?></p>
		<?php
	}

	/**
	 * @param array<string,mixed> $report Report.
	 * @return void
	 */
	private function render_modules( array $report ): void {
		$groups = $this->module_groups( $report );
		$buckets = array( 'attention' => array(), 'used' => array(), 'available' => array(), 'inactive' => array() );
		foreach ( $groups as $group ) {
			if ( 'correct' !== (string) $group['worst']['key'] ) { $buckets['attention'][] = $group; }
			elseif ( (int) $group['used'] > 0 || (int) $group['observed'] > 0 ) { $buckets['used'][] = $group; }
			elseif ( (int) $group['configured'] > 0 ) { $buckets['available'][] = $group; }
			else { $buckets['inactive'][] = $group; }
		}
		?>
		<div class="cw-lumen-performance__section-heading">
			<h3><?php echo esc_html__( 'Lumen features', 'creceweb-lumen-lite' ); ?></h3>
			<p><?php echo esc_html__( 'Start with what is actually in use. Technical counters stay available when you need them.', 'creceweb-lumen-lite' ); ?></p>
		</div>
		<?php if ( $this->profile_filters_enabled( 'modules' ) ) : ?><?php $this->render_filter_bar( 'module', array( 'component', 'state', 'usage' ) ); ?><?php endif; ?>

		<?php if ( ! empty( $buckets['attention'] ) ) : ?>
			<?php $this->render_feature_group( __( 'Needs attention', 'creceweb-lumen-lite' ), __( 'These features contain at least one Lumen state that should be reviewed.', 'creceweb-lumen-lite' ), $buckets['attention'], 'attention' ); ?>
		<?php endif; ?>
		<?php $this->render_feature_group( __( 'In use on this page', 'creceweb-lumen-lite' ), __( 'Features whose Lumen assets are currently used or loaded.', 'creceweb-lumen-lite' ), $buckets['used'], 'used' ); ?>
		<?php $this->render_feature_group( __( 'Available but not needed', 'creceweb-lumen-lite' ), __( 'Configured features that correctly kept their packaged assets off this page.', 'creceweb-lumen-lite' ), $buckets['available'], 'available' ); ?>

		<?php if ( ! empty( $buckets['inactive'] ) ) : ?>
		<details class="cw-lumen-performance__disclosure cw-lumen-performance__disclosure--features">
			<?php /* translators: %d: number of inactive Lumen features. */ ?>
			<summary><?php echo esc_html( sprintf( __( 'Inactive features (%d)', 'creceweb-lumen-lite' ), count( $buckets['inactive'] ) ) ); ?></summary>
			<?php $this->render_feature_group( '', __( 'These features are not configured for the current request.', 'creceweb-lumen-lite' ), $buckets['inactive'], 'inactive' ); ?>
		</details>
		<?php endif; ?>
		<?php if ( $this->profile_filters_enabled( 'modules' ) ) : ?><p class="cw-lumen-performance__filter-empty" data-cw-performance-filter-empty="module" hidden><?php echo esc_html__( 'No features match the selected filters.', 'creceweb-lumen-lite' ); ?></p><?php endif; ?>
		<?php
	}

	/**
	 * @param array<string,mixed> $current Current report.
	 * @return void
	 */
	private function render_budgets( array $report ): void {
		$budgets = $this->profile_budgets();
		$result  = ( new BudgetEvaluator() )->evaluate( $report, $budgets );
		$items   = isset( $result['items'] ) && is_array( $result['items'] ) ? $result['items'] : array();
		?>
		<div class="cw-lumen-performance__section-heading">
			<h3><?php echo esc_html__( 'Lumen reference limits', 'creceweb-lumen-lite' ); ?></h3>
			<p><?php echo esc_html( $this->profile_copy( 'budgets_description', __( 'Reference limits apply only to Lumen-owned packaged resources detected in the current manual analysis.', 'creceweb-lumen-lite' ) ) ); ?></p>
		</div>
		<div class="cw-lumen-performance__comparison-note" role="note">
			<strong><?php echo esc_html__( 'Current analysis only.', 'creceweb-lumen-lite' ); ?></strong>
			<?php echo esc_html__( 'These guardrails are not a page-speed score and do not evaluate hosting, images, WordPress core, or third-party code. No scheduled alert, email, or background analysis is created.', 'creceweb-lumen-lite' ); ?>
		</div>
		<?php if ( empty( $items ) ) : ?>
			<div class="cw-lumen-performance__empty cw-lumen-performance__empty--compact"><p><?php echo esc_html__( 'No reference limits are registered for this analysis profile.', 'creceweb-lumen-lite' ); ?></p></div>
			<?php return; ?>
		<?php endif; ?>
		<?php if ( 'over' === (string) ( $result['state'] ?? '' ) ) : ?>
			<div class="notice notice-warning inline cw-lumen-performance__notice"><p><strong><?php echo esc_html__( 'A Lumen reference limit is exceeded.', 'creceweb-lumen-lite' ); ?></strong> <?php echo esc_html__( 'Review the current Lumen resource inventory before treating the difference as a regression.', 'creceweb-lumen-lite' ); ?></p></div>
		<?php elseif ( 'near' === (string) ( $result['state'] ?? '' ) ) : ?>
			<div class="notice notice-info inline cw-lumen-performance__notice"><p><?php echo esc_html__( 'At least one Lumen metric is close to its current reference limit.', 'creceweb-lumen-lite' ); ?></p></div>
		<?php endif; ?>
		<div class="cw-lumen-performance__budget-grid">
			<?php foreach ( $items as $item ) : ?>
				<?php
				if ( ! is_array( $item ) ) { continue; }
				$state = (string) ( $item['state'] ?? 'within' );
				$badge = 'within' === $state ? 'correct' : 'attention';
				$state_label = 'over' === $state ? __( 'Over limit', 'creceweb-lumen-lite' ) : ( 'near' === $state ? __( 'Near limit', 'creceweb-lumen-lite' ) : __( 'Within limit', 'creceweb-lumen-lite' ) );
				$is_bytes = 'bytes' === (string) ( $item['unit'] ?? '' );
				$current = $is_bytes ? $this->format_bytes( (int) $item['current'] ) : (string) (int) $item['current'];
				$limit = $is_bytes ? $this->format_bytes( (int) $item['max'] ) : (string) (int) $item['max'];
				$room_raw = 'over' === $state ? (int) $item['over_by'] : (int) $item['headroom'];
				$room = $is_bytes ? $this->format_bytes( $room_raw ) : (string) $room_raw;
				?>
				<?php $ratio = (int) $item['max'] > 0 ? round( ( (int) $item['current'] / (int) $item['max'] ) * 100, 1 ) : 0; ?>
				<?php $progress_band = $this->limit_progress_band( (float) $ratio ); ?>
				<article class="cw-lumen-performance__budget cw-lumen-performance__budget--<?php echo esc_attr( $state ); ?>">
					<div class="cw-lumen-performance__budget-head"><div><span><?php echo esc_html( (string) $item['label'] ); ?></span><strong><?php echo esc_html( $current ); ?></strong></div><span class="cw-lumen-performance__badge cw-lumen-performance__badge--<?php echo esc_attr( $badge ); ?>"><?php echo esc_html( $state_label ); ?></span></div>
					<div class="cw-lumen-performance__limit-progress cw-lumen-performance__limit-progress--<?php echo esc_attr( $progress_band ); ?>" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( (string) min( 100, max( 0, $ratio ) ) ); ?>"><span style="width:<?php echo esc_attr( (string) min( 100, max( 0, $ratio ) ) ); ?>%"></span></div>
					<dl><div><dt><?php echo esc_html__( 'Reference limit', 'creceweb-lumen-lite' ); ?></dt><dd><?php echo esc_html( $limit ); ?></dd></div><div><dt><?php echo esc_html( 'over' === $state ? __( 'Over by', 'creceweb-lumen-lite' ) : __( 'Headroom', 'creceweb-lumen-lite' ) ); ?></dt><dd><?php echo esc_html( $room ); ?></dd></div></dl>
				</article>
			<?php endforeach; ?>
		</div>
		<p class="cw-lumen-performance__footnote"><?php echo esc_html__( 'Reference limits are conservative Lumen guardrails. Exceeding one is a review signal, not proof of a performance defect.', 'creceweb-lumen-lite' ); ?></p>
		<?php
	}

	private function render_comparison( array $current ): void {
		$baseline = $this->baseline_report( $this->render_profile );
		$mode     = $this->comparison_mode();
		?>
		<div class="cw-lumen-performance__section-heading">
			<h3><?php echo esc_html__( 'Manual comparison', 'creceweb-lumen-lite' ); ?></h3>
			<p><?php echo esc_html__( 'Keep one temporary baseline and choose how to interpret it. No historical series is created and the baseline expires automatically after 30 minutes.', 'creceweb-lumen-lite' ); ?></p>
		</div>

		<nav class="cw-lumen-performance__comparison-modes" aria-label="<?php echo esc_attr__( 'Comparison mode', 'creceweb-lumen-lite' ); ?>">
			<a class="button<?php echo 'before_after' === $mode ? ' button-primary' : ''; ?>" href="<?php echo esc_url( $this->comparison_url( 'before_after' ) ); ?>" data-cw-performance-soft-refresh><?php echo esc_html__( 'Before / after', 'creceweb-lumen-lite' ); ?></a>
			<a class="button<?php echo 'pages' === $mode ? ' button-primary' : ''; ?>" href="<?php echo esc_url( $this->comparison_url( 'pages' ) ); ?>" data-cw-performance-soft-refresh><?php echo esc_html__( 'Compare pages', 'creceweb-lumen-lite' ); ?></a>
		</nav>

		<div class="cw-lumen-performance__comparison-mode-note">
			<?php if ( 'pages' === $mode ) : ?>
				<strong><?php echo esc_html__( 'Compare pages.', 'creceweb-lumen-lite' ); ?></strong>
				<?php echo esc_html__( 'Use Page A and Page B to compare which Lumen resources and modules each URL uses. Different URLs are allowed; the analysis profile and logical device must still match.', 'creceweb-lumen-lite' ); ?>
			<?php else : ?>
				<strong><?php echo esc_html__( 'Before / after.', 'creceweb-lumen-lite' ); ?></strong>
				<?php echo esc_html__( 'Use two analyses of the same URL to inspect differences after a configuration, content, or code change. URL, analysis profile, and logical device must match.', 'creceweb-lumen-lite' ); ?>
			<?php endif; ?>
		</div>

		<div class="cw-lumen-performance__comparison-actions" data-cw-lumen-performance-comparison-mode="<?php echo esc_attr( $mode ); ?>">
			<button type="button" class="button button-primary" data-cw-lumen-performance-baseline-action="set"><?php echo esc_html( null === $baseline ? __( 'Use current result as baseline', 'creceweb-lumen-lite' ) : __( 'Replace baseline with current result', 'creceweb-lumen-lite' ) ); ?></button>
			<?php if ( null !== $baseline ) : ?>
				<button type="button" class="button" data-cw-lumen-performance-baseline-action="clear"><?php echo esc_html__( 'Clear baseline', 'creceweb-lumen-lite' ); ?></button>
			<?php endif; ?>
			<span class="cw-lumen-performance__comparison-progress" data-cw-lumen-performance-comparison-progress role="status" aria-live="polite"></span>
		</div>

		<?php if ( null === $baseline ) : ?>
			<div class="cw-lumen-performance__empty cw-lumen-performance__empty--compact"><p>
				<?php echo esc_html( 'pages' === $mode
					? __( 'Select the current result as Page A, analyze another public URL with the same logical device/profile, then return to Compare pages.', 'creceweb-lumen-lite' )
					: __( 'Select the current result as the temporary baseline, then run another analysis of the same URL with the same logical device/profile.', 'creceweb-lumen-lite' ) ); ?>
			</p></div>
			<?php return; ?>
		<?php endif; ?>

		<?php
		$comparison = ( new Comparator() )->compare( $baseline, $current, $mode );
		$this->render_comparison_context( $baseline, $current, $mode );
		?>

		<?php if ( ! empty( $comparison['same_sample'] ) ) : ?>
			<div class="notice notice-info inline cw-lumen-performance__notice"><p><?php echo esc_html__( 'The current result is the same sample as the selected baseline. Run another analysis before evaluating differences.', 'creceweb-lumen-lite' ); ?></p></div>
			<?php return; ?>
		<?php endif; ?>

		<?php if ( empty( $comparison['equivalence']['equivalent'] ) ) : ?>
			<div class="notice notice-warning inline cw-lumen-performance__notice">
				<p><strong><?php echo esc_html__( 'These analyses cannot be compared in this mode.', 'creceweb-lumen-lite' ); ?></strong>
				<?php echo esc_html( $this->comparison_equivalence_message( (array) ( $comparison['equivalence']['reasons'] ?? array() ), $mode ) ); ?></p>
			</div>
			<?php return; ?>
		<?php endif; ?>

		<?php if ( 'pages' === $mode && $this->comparison_urls_match( $baseline, $current ) ) : ?>
			<div class="notice notice-info inline cw-lumen-performance__notice"><p><?php echo esc_html__( 'Both samples use the same URL. This is valid, but Before / after is clearer when evaluating changes to one page.', 'creceweb-lumen-lite' ); ?></p></div>
		<?php endif; ?>

		<div class="cw-lumen-performance__comparison-note" role="note">
			<strong><?php echo esc_html__( 'Interpretation boundary.', 'creceweb-lumen-lite' ); ?></strong>
			<?php echo esc_html( 'pages' === $mode
				? __( 'This is a page-to-page inventory comparison. Differences are not presented as regressions, improvements, or causal evidence. Browser transfer remains cache-sensitive context.', 'creceweb-lumen-lite' )
				: __( 'The comparison reports differences only. It does not claim that a version, setting, or module caused a change. Browser transfer values are cache-sensitive context and should not be treated as deterministic regression evidence.', 'creceweb-lumen-lite' ) ); ?>
		</div>

		<?php $this->render_comparison_highlights( $comparison, $mode ); ?>
		<?php $this->render_comparison_metrics( (array) $comparison['metrics'], $mode ); ?>
		<?php $this->render_comparison_story( (array) $comparison['modules'], $mode ); ?>

		<details class="cw-lumen-performance__disclosure cw-lumen-performance__comparison-technical">
			<summary><?php echo esc_html__( 'Show technical comparison', 'creceweb-lumen-lite' ); ?></summary>
			<div class="cw-lumen-performance__comparison-technical-inner">
				<p class="cw-lumen-performance__scope-note"><?php echo esc_html__( 'Technical tables keep exact handles and C/U/E/L counters for support and debugging.', 'creceweb-lumen-lite' ); ?></p>
				<?php $this->render_version_changes( (array) $comparison['versions'], $mode ); ?>
				<?php $this->render_resource_changes( (array) $comparison['resources'], $mode ); ?>
				<?php $this->render_module_changes( (array) $comparison['modules'], $mode ); ?>
				<?php $this->render_browser_transfer_comparison( (array) $comparison['browser_transfer'], $mode ); ?>
			</div>
		</details>
		<?php
	}

	/**
	 * @param array<string,mixed> $baseline Baseline report.
	 * @param array<string,mixed> $current Current report.
	 * @return void
	 */
	private function render_comparison_context( array $baseline, array $current, string $mode = 'before_after' ): void {
		$a = isset( $baseline['analysis'] ) && is_array( $baseline['analysis'] ) ? $baseline['analysis'] : array();
		$b = isset( $current['analysis'] ) && is_array( $current['analysis'] ) ? $current['analysis'] : array();
		$left_label  = 'pages' === $mode ? __( 'Page A', 'creceweb-lumen-lite' ) : __( 'Baseline', 'creceweb-lumen-lite' );
		$right_label = 'pages' === $mode ? __( 'Page B', 'creceweb-lumen-lite' ) : __( 'Current', 'creceweb-lumen-lite' );
		?>
		<div class="cw-lumen-performance__comparison-context">
			<div>
				<span><?php echo esc_html( $left_label ); ?></span>
				<strong><?php echo esc_html( (string) ( $a['analyzed_at'] ?? '—' ) ); ?></strong>
				<small><?php echo esc_html( (string) ( $a['url'] ?? '' ) ); ?></small>
				<small><?php echo esc_html( ucfirst( (string) ( $a['device'] ?? 'unknown' ) ) ); ?></small>
				<small><?php echo esc_html__( 'Profile:', 'creceweb-lumen-lite' ); ?> <code><?php echo esc_html( (string) ( $a['profile'] ?? '' ) ); ?></code></small>
			</div>
			<div>
				<span><?php echo esc_html( $right_label ); ?></span>
				<strong><?php echo esc_html( (string) ( $b['analyzed_at'] ?? '—' ) ); ?></strong>
				<small><?php echo esc_html( (string) ( $b['url'] ?? '' ) ); ?></small>
				<small><?php echo esc_html( ucfirst( (string) ( $b['device'] ?? 'unknown' ) ) ); ?></small>
				<small><?php echo esc_html__( 'Profile:', 'creceweb-lumen-lite' ); ?> <code><?php echo esc_html( (string) ( $b['profile'] ?? '' ) ); ?></code></small>
			</div>
		</div>
		<?php
	}

	/**
	 * @param array<int,string> $reasons Reasons.
	 * @return string
	 */
	private function comparison_equivalence_message( array $reasons, string $mode = 'before_after' ): string {
		$labels = array(
			'url'         => __( 'URL differs', 'creceweb-lumen-lite' ),
			'url_missing' => __( 'a page URL is missing', 'creceweb-lumen-lite' ),
			'profile'     => __( 'analysis profile differs', 'creceweb-lumen-lite' ),
			'device'      => __( 'logical device differs', 'creceweb-lumen-lite' ),
		);
		$items = array();
		foreach ( $reasons as $reason ) {
			$reason = sanitize_key( (string) $reason );
			if ( isset( $labels[ $reason ] ) ) {
				$items[] = $labels[ $reason ];
			}
		}
		return empty( $items )
			? __( 'Run two fresh analyses under equivalent conditions.', 'creceweb-lumen-lite' )
			: sprintf(
				/* translators: %s: Comma-separated reasons analyses cannot be compared. */
				__( 'Run a new comparison because: %s.', 'creceweb-lumen-lite' ),
				implode( ', ', $items )
			);
	}

	/**
	 * @param array<string,array<string,mixed>> $metrics Metrics.
	 * @return void
	 */
	private function render_comparison_metrics( array $metrics, string $mode = 'before_after' ): void {
		$labels = array(
			'css_bytes' => __( 'Packaged CSS', 'creceweb-lumen-lite' ),
			'js_bytes'  => __( 'Packaged JavaScript', 'creceweb-lumen-lite' ),
			'resources' => __( 'Loaded resources', 'creceweb-lumen-lite' ),
			'requests'  => __( 'Requests', 'creceweb-lumen-lite' ),
		);
		?>
		<div class="cw-lumen-performance__section-heading cw-lumen-performance__section-heading--compare">
			<h3><?php echo esc_html( 'pages' === $mode ? __( 'Page metrics', 'creceweb-lumen-lite' ) : __( 'Metric changes', 'creceweb-lumen-lite' ) ); ?></h3>
		</div>
		<div class="cw-lumen-performance__comparison-metrics">
			<?php foreach ( $labels as $key => $label ) : ?>
				<?php
				$item = isset( $metrics[ $key ] ) && is_array( $metrics[ $key ] ) ? $metrics[ $key ] : array();
				$is_bytes = in_array( $key, array( 'css_bytes', 'js_bytes' ), true );
				?>
				<div class="cw-lumen-performance__comparison-metric">
					<span><?php echo esc_html( $label ); ?></span>
					<div><small><?php echo esc_html( 'pages' === $mode ? __( 'Page A', 'creceweb-lumen-lite' ) : __( 'Baseline', 'creceweb-lumen-lite' ) ); ?></small><strong><?php echo esc_html( $is_bytes ? $this->format_bytes( (int) ( $item['baseline'] ?? 0 ) ) : (string) (int) ( $item['baseline'] ?? 0 ) ); ?></strong></div>
					<div><small><?php echo esc_html( 'pages' === $mode ? __( 'Page B', 'creceweb-lumen-lite' ) : __( 'Current', 'creceweb-lumen-lite' ) ); ?></small><strong><?php echo esc_html( $is_bytes ? $this->format_bytes( (int) ( $item['current'] ?? 0 ) ) : (string) (int) ( $item['current'] ?? 0 ) ); ?></strong></div>
					<p><?php echo esc_html( 'pages' === $mode ? $this->comparison_absolute_difference_label( $item, $is_bytes ) : $this->comparison_delta_label( $item, $is_bytes ) ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * @param array<string,mixed> $item Delta.
	 * @param bool $bytes Format as bytes.
	 * @return string
	 */
	private function comparison_delta_label( array $item, bool $bytes = false ): string {
		$delta = (int) ( $item['delta'] ?? 0 );
		$value = $bytes ? $this->format_bytes( abs( $delta ) ) : (string) abs( $delta );
		$sign  = $delta > 0 ? '+' : ( $delta < 0 ? '−' : '' );
		$percent = isset( $item['percent'] ) && is_numeric( $item['percent'] )
			? ' (' . ( (float) $item['percent'] > 0 ? '+' : '' ) . (string) $item['percent'] . '%)'
			: '';
		return $sign . $value . $percent;
	}

	private function comparison_absolute_difference_label( array $item, bool $bytes = false ): string {
		$difference = abs( (int) ( $item['current'] ?? 0 ) - (int) ( $item['baseline'] ?? 0 ) );
		return sprintf(
			/* translators: %s: Absolute difference between Page A and Page B. */
			__( 'Absolute difference: %s', 'creceweb-lumen-lite' ),
			$bytes ? $this->format_bytes( $difference ) : (string) $difference
		);
	}

	/**
	 * @param array<int,array<string,mixed>> $changes Version changes.
	 * @return void
	 */
	private function render_version_changes( array $changes, string $mode = 'before_after' ): void {
		if ( empty( $changes ) ) {
			return;
		}
		?>
		<div class="cw-lumen-performance__section-heading cw-lumen-performance__section-heading--compare">
			<h3><?php echo esc_html( 'pages' === $mode ? __( 'Component versions', 'creceweb-lumen-lite' ) : __( 'Component version changes', 'creceweb-lumen-lite' ) ); ?></h3>
			<p><?php echo esc_html__( 'Version differences are context only and are not presented as the cause of another change.', 'creceweb-lumen-lite' ); ?></p>
		</div>
		<div class="cw-lumen-performance__table-wrap">
			<table class="widefat striped cw-lumen-performance__table">
				<thead><tr>
					<th><?php echo esc_html__( 'Component', 'creceweb-lumen-lite' ); ?></th>
					<th><?php echo esc_html( 'pages' === $mode ? __( 'Page A', 'creceweb-lumen-lite' ) : __( 'Baseline', 'creceweb-lumen-lite' ) ); ?></th>
					<th><?php echo esc_html( 'pages' === $mode ? __( 'Page B', 'creceweb-lumen-lite' ) : __( 'Current', 'creceweb-lumen-lite' ) ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $changes as $change ) : ?>
					<tr>
						<td><?php echo esc_html( ucfirst( (string) ( $change['component'] ?? '' ) ) ); ?></td>
						<td><?php echo esc_html( (string) ( $change['baseline'] ?? '' ) ); ?></td>
						<td><?php echo esc_html( (string) ( $change['current'] ?? '' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * @param array<int,array<string,mixed>> $changes Resource changes.
	 * @return void
	 */
	private function render_resource_changes( array $changes, string $mode = 'before_after' ): void {
		?>
		<div class="cw-lumen-performance__section-heading cw-lumen-performance__section-heading--compare">
			<h3><?php echo esc_html( 'pages' === $mode ? __( 'Resource comparison', 'creceweb-lumen-lite' ) : __( 'Resource changes', 'creceweb-lumen-lite' ) ); ?></h3>
			<p><?php echo esc_html( 'pages' === $mode ? __( 'See which Lumen resources are exclusive to one page or differ between Page A and Page B.', 'creceweb-lumen-lite' ) : __( 'Configuration or usage changes are marked separately from observation-only changes.', 'creceweb-lumen-lite' ) ); ?></p>
		</div>
		<?php if ( empty( $changes ) ) : ?>
			<div class="cw-lumen-performance__empty cw-lumen-performance__empty--compact"><p><?php echo esc_html( 'pages' === $mode ? __( 'The two pages use the same Lumen resource inventory and states.', 'creceweb-lumen-lite' ) : __( 'No Lumen resource changes were detected.', 'creceweb-lumen-lite' ) ); ?></p></div>
			<?php return; ?>
		<?php endif; ?>
		<div class="cw-lumen-performance__table-wrap">
			<table class="widefat striped cw-lumen-performance__table">
				<thead><tr>
					<th><?php echo esc_html__( 'Change', 'creceweb-lumen-lite' ); ?></th>
					<th><?php echo esc_html__( 'Component', 'creceweb-lumen-lite' ); ?></th>
					<th><?php echo esc_html__( 'Module', 'creceweb-lumen-lite' ); ?></th>
					<th><?php echo esc_html__( 'Handle', 'creceweb-lumen-lite' ); ?></th>
					<th><?php echo esc_html__( 'Interpretation', 'creceweb-lumen-lite' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $changes as $change ) : ?>
					<tr>
						<td>
							<?php
							$change_key = (string) ( $change['change'] ?? '' );
							if ( 'pages' === $mode ) {
								$change_labels = array(
									'added'   => __( 'Loaded only on Page B', 'creceweb-lumen-lite' ),
									'removed' => __( 'Loaded only on Page A', 'creceweb-lumen-lite' ),
									'changed' => __( 'Different', 'creceweb-lumen-lite' ),
								);
								echo esc_html( $change_labels[ $change_key ] ?? ucfirst( $change_key ) );
							} else {
								echo esc_html( ucfirst( $change_key ) );
							}
							?>
						</td>
						<td><?php echo esc_html( $this->provider_label( (string) ( $change['provider'] ?? '' ) ) ); ?></td>
						<td><?php echo esc_html( $this->module_label( (string) ( $change['module'] ?? '' ), (string) ( $change['provider'] ?? '' ) ) ); ?></td>
						<td><code><?php echo esc_html( (string) ( $change['handle'] ?? '' ) ); ?></code></td>
						<td><?php echo esc_html( ! empty( $change['context_changed'] ) ? __( 'Configuration/usage context changed', 'creceweb-lumen-lite' ) : __( 'Inventory or observed state changed', 'creceweb-lumen-lite' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * @param array<int,array<string,mixed>> $changes Module changes.
	 * @return void
	 */
	private function render_module_changes( array $changes, string $mode = 'before_after' ): void {
		?>
		<div class="cw-lumen-performance__section-heading cw-lumen-performance__section-heading--compare">
			<h3><?php echo esc_html( 'pages' === $mode ? __( 'Module comparison', 'creceweb-lumen-lite' ) : __( 'Module changes', 'creceweb-lumen-lite' ) ); ?></h3>
		</div>
		<?php if ( empty( $changes ) ) : ?>
			<div class="cw-lumen-performance__empty cw-lumen-performance__empty--compact"><p><?php echo esc_html( 'pages' === $mode ? __( 'The two pages have the same Lumen module aggregates.', 'creceweb-lumen-lite' ) : __( 'No Lumen module aggregate changes were detected.', 'creceweb-lumen-lite' ) ); ?></p></div>
			<?php return; ?>
		<?php endif; ?>
		<div class="cw-lumen-performance__table-wrap">
			<table class="widefat striped cw-lumen-performance__table">
				<thead><tr>
					<th><?php echo esc_html__( 'Change', 'creceweb-lumen-lite' ); ?></th>
					<th><?php echo esc_html__( 'Component', 'creceweb-lumen-lite' ); ?></th>
					<th><?php echo esc_html__( 'Module', 'creceweb-lumen-lite' ); ?></th>
					<th><?php echo esc_html( 'pages' === $mode ? __( 'Page A C/U/E/L', 'creceweb-lumen-lite' ) : __( 'Baseline C/U/E/L', 'creceweb-lumen-lite' ) ); ?></th>
					<th><?php echo esc_html( 'pages' === $mode ? __( 'Page B C/U/E/L', 'creceweb-lumen-lite' ) : __( 'Current C/U/E/L', 'creceweb-lumen-lite' ) ); ?></th>
					<th><?php echo esc_html__( 'Packaged bytes', 'creceweb-lumen-lite' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $changes as $change ) : ?>
					<?php $a = isset( $change['baseline'] ) && is_array( $change['baseline'] ) ? $change['baseline'] : array(); ?>
					<?php $b = isset( $change['current'] ) && is_array( $change['current'] ) ? $change['current'] : array(); ?>
					<tr>
						<td>
							<?php
							$change_key = (string) ( $change['change'] ?? '' );
							if ( 'pages' === $mode ) {
								$change_labels = array(
									'added'   => __( 'Loaded only on Page B', 'creceweb-lumen-lite' ),
									'removed' => __( 'Loaded only on Page A', 'creceweb-lumen-lite' ),
									'changed' => __( 'Different', 'creceweb-lumen-lite' ),
								);
								echo esc_html( $change_labels[ $change_key ] ?? ucfirst( $change_key ) );
							} else {
								echo esc_html( ucfirst( $change_key ) );
							}
							?>
						</td>
						<td><?php echo esc_html( $this->provider_label( (string) ( $change['provider'] ?? '' ) ) ); ?></td>
						<td><?php echo esc_html( $this->module_label( (string) ( $change['module'] ?? '' ), (string) ( $change['provider'] ?? '' ) ) ); ?></td>
						<td><?php echo esc_html( $this->module_counts_label( $a ) ); ?></td>
						<td><?php echo esc_html( $this->module_counts_label( $b ) ); ?></td>
						<td><?php echo esc_html( $this->format_bytes( (int) ( $a['bytes'] ?? 0 ) ) . ' → ' . $this->format_bytes( (int) ( $b['bytes'] ?? 0 ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/** @return string */
	private function module_counts_label( array $module ): string {
		return implode(
			' / ',
			array(
				(string) (int) ( $module['configured'] ?? 0 ),
				(string) (int) ( $module['used'] ?? 0 ),
				(string) (int) ( $module['expected'] ?? 0 ),
				(string) (int) ( $module['observed'] ?? 0 ),
			)
		);
	}

	/**
	 * @param array<string,mixed> $transfer Transfer comparison.
	 * @return void
	 */
	private function render_browser_transfer_comparison( array $transfer, string $mode = 'before_after' ): void {
		?>
		<div class="cw-lumen-performance__section-heading cw-lumen-performance__section-heading--compare">
			<h3><?php echo esc_html__( 'Browser transfer context', 'creceweb-lumen-lite' ); ?></h3>
			<p><?php echo esc_html__( 'Transfer bytes can change because of memory cache, browser cache, service workers, or timing availability. This section is context only.', 'creceweb-lumen-lite' ); ?></p>
		</div>
		<?php if ( empty( $transfer['available'] ) ) : ?>
			<div class="cw-lumen-performance__empty cw-lumen-performance__empty--compact"><p><?php echo esc_html__( 'Comparable browser transfer data is not available for both analyses.', 'creceweb-lumen-lite' ); ?></p></div>
			<?php return; ?>
		<?php endif; ?>
		<div class="cw-lumen-performance__comparison-metrics">
			<?php foreach ( array( 'css' => __( 'Transferred CSS', 'creceweb-lumen-lite' ), 'js' => __( 'Transferred JavaScript', 'creceweb-lumen-lite' ), 'total' => __( 'Transferred total', 'creceweb-lumen-lite' ) ) as $key => $label ) : ?>
				<?php $item = isset( $transfer[ $key ] ) && is_array( $transfer[ $key ] ) ? $transfer[ $key ] : array(); ?>
				<div class="cw-lumen-performance__comparison-metric">
					<span><?php echo esc_html( $label ); ?></span>
					<div><small><?php echo esc_html( 'pages' === $mode ? __( 'Page A', 'creceweb-lumen-lite' ) : __( 'Baseline', 'creceweb-lumen-lite' ) ); ?></small><strong><?php echo esc_html( $this->format_bytes( (int) ( $item['baseline'] ?? 0 ) ) ); ?></strong></div>
					<div><small><?php echo esc_html( 'pages' === $mode ? __( 'Page B', 'creceweb-lumen-lite' ) : __( 'Current', 'creceweb-lumen-lite' ) ); ?></small><strong><?php echo esc_html( $this->format_bytes( (int) ( $item['current'] ?? 0 ) ) ); ?></strong></div>
					<p><?php echo esc_html( 'pages' === $mode ? $this->comparison_absolute_difference_label( $item, true ) : $this->comparison_delta_label( $item, true ) ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * @param array<string,mixed> $report Report.
	 * @return void
	 */
	private function render_recommendations( array $report ): void {
		$recommendations = array();
		foreach ( (array) ( $report['results'] ?? array() ) as $result ) {
			if ( ! is_array( $result ) || 'observation_only' === (string) ( $result['mode'] ?? '' ) ) { continue; }
			$status = (string) ( $result['status'] ?? '' );
			if ( ! in_array( $status, array( 'optimizable', 'anomaly', 'functional_error', 'pending' ), true ) ) { continue; }
			$recommendations[] = array(
				'status' => $this->public_status( $result ),
				'title'  => $this->recommendation_title( $result ),
				'text'   => $this->recommendation_text( $result ),
				'impact' => isset( $result['size_bytes'] ) ? max( 0, (int) $result['size_bytes'] ) : 0,
				'url'    => $this->module_url( (string) ( $result['provider'] ?? '' ), (string) ( $result['module'] ?? '' ) ),
			);
		}
		foreach ( (array) ( $report['duplicates'] ?? array() ) as $duplicate ) {
			if ( ! is_array( $duplicate ) ) { continue; }
			$recommendations[] = array(
				'status' => array( 'key' => 'error', 'label' => __( 'Needs attention', 'creceweb-lumen-lite' ) ),
				'title'  => __( 'The same Lumen asset source is registered more than once', 'creceweb-lumen-lite' ),
				/* translators: %s: comma-separated list of Lumen asset handles. */
				'text'   => sprintf( __( 'Review these handles: %s. Lumen only flags exact duplicate owned sources.', 'creceweb-lumen-lite' ), implode( ', ', array_map( 'sanitize_text_field', (array) ( $duplicate['handles'] ?? array() ) ) ) ),
				'impact' => 0,
				'url'    => '',
			);
		}
		?>
		<div class="cw-lumen-performance__section-heading">
			<h3><?php echo esc_html__( 'Actions', 'creceweb-lumen-lite' ); ?></h3>
			<p><?php echo esc_html__( 'This is a read-only review list. Lumen Performance never changes configuration automatically.', 'creceweb-lumen-lite' ); ?></p>
		</div>
		<?php if ( empty( $recommendations ) ) : ?>
			<div class="cw-lumen-performance__empty cw-lumen-performance__empty--success">
				<h3><?php echo esc_html__( 'No action needed', 'creceweb-lumen-lite' ); ?></h3>
				<p><?php echo esc_html( $this->profile_copy( 'no_recommendations_description', __( 'The Lumen resources detected by the current rules match their configuration and usage.', 'creceweb-lumen-lite' ) ) ); ?></p>
			</div>
		<?php else : ?>
			<div class="cw-lumen-performance__recommendations">
				<?php foreach ( $recommendations as $item ) : ?>
					<article class="cw-lumen-performance__recommendation">
						<span class="cw-lumen-performance__badge cw-lumen-performance__badge--<?php echo esc_attr( $item['status']['key'] ); ?>"><?php echo esc_html( $item['status']['label'] ); ?></span>
						<div><h4><?php echo esc_html( $item['title'] ); ?></h4><p><?php echo esc_html( $item['text'] ); ?></p>
						<?php if ( (int) $item['impact'] > 0 ) : ?><p class="cw-lumen-performance__action-impact"><strong><?php echo esc_html__( 'Packaged asset:', 'creceweb-lumen-lite' ); ?></strong> <?php echo esc_html( $this->format_bytes( (int) $item['impact'] ) ); ?></p><?php endif; ?>
						<?php if ( '' !== $item['url'] ) : ?><a class="button button-secondary" href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html__( 'Review Lumen configuration', 'creceweb-lumen-lite' ); ?></a><?php endif; ?></div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Summarizes browser-reported transfer metrics allowed by the active analysis profile.
	 *
	 * Values can legitimately be zero when the resource came from memory cache,
	 * service worker, or the browser hides cross-origin timing details.
	 *
	 * @param array<int,array<string,mixed>> $resources Resource timings.
	 * @return array<string,mixed>
	 */
	private function browser_metrics_summary( array $resources ): array {
		$summary = array(
			'available'          => false,
			'resource_count'     => 0,
			'transfer_bytes'     => 0,
			'encoded_bytes'      => 0,
			'decoded_bytes'      => 0,
			'css_transfer_bytes' => 0,
			'js_transfer_bytes'  => 0,
		);

		foreach ( $resources as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$url = (string) ( $item['url'] ?? '' );
			if ( '' === $url ) {
				continue;
			}

			++$summary['resource_count'];
			$summary['available']      = true;
			$summary['transfer_bytes'] += (int) ( $item['transferSize'] ?? 0 );
			$summary['encoded_bytes']  += (int) ( $item['encodedBodySize'] ?? 0 );
			$summary['decoded_bytes']  += (int) ( $item['decodedBodySize'] ?? 0 );

			$path = strtolower( (string) wp_parse_url( $url, PHP_URL_PATH ) );
			if ( str_ends_with( $path, '.css' ) ) {
				$summary['css_transfer_bytes'] += (int) ( $item['transferSize'] ?? 0 );
			} elseif ( str_ends_with( $path, '.js' ) ) {
				$summary['js_transfer_bytes'] += (int) ( $item['transferSize'] ?? 0 );
			}
		}

		return $summary;
	}

	/** @return void */
	private function guard_ajax_capability(): void {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to run this analysis.', 'creceweb-lumen-lite' ) ), 403 );
		}
	}

	/**
	 * Current probe token, if any.
	 *
	 * @return string
	 */
	private function request_token(): string {
		$raw_token = filter_input( INPUT_GET, self::QUERY_ARG, FILTER_UNSAFE_RAW );
		if ( ! is_string( $raw_token ) || '' === $raw_token ) {
			return '';
		}

		$raw_token = sanitize_text_field( $raw_token );
		$token     = preg_replace( '/[^A-Za-z0-9]/', '', $raw_token );

		return is_string( $token ) ? $token : '';
	}

	/**
	 * Restricts the MVP to a public URL of the current site.
	 *
	 * @param string $raw_url Raw user URL.
	 * @return string|\WP_Error
	 */
	private function validate_public_url( string $raw_url ) {
		$url = esc_url_raw( trim( $raw_url ), array( 'http', 'https' ) );
		if ( '' === $url ) {
			return new \WP_Error( 'invalid_url', __( 'Enter a valid public URL.', 'creceweb-lumen-lite' ) );
		}

		$home   = wp_parse_url( home_url( '/' ) );
		$target = wp_parse_url( $url );

		if ( ! is_array( $home ) || ! is_array( $target ) ) {
			return new \WP_Error( 'invalid_url', __( 'The URL could not be parsed.', 'creceweb-lumen-lite' ) );
		}

		$home_scheme = strtolower( (string) ( $home['scheme'] ?? '' ) );
		$home_host   = strtolower( (string) ( $home['host'] ?? '' ) );
		$home_port   = (int) ( $home['port'] ?? ( 'https' === $home_scheme ? 443 : 80 ) );

		$target_scheme = strtolower( (string) ( $target['scheme'] ?? '' ) );
		$target_host   = strtolower( (string) ( $target['host'] ?? '' ) );
		$target_port   = (int) ( $target['port'] ?? ( 'https' === $target_scheme ? 443 : 80 ) );

		if ( $home_scheme !== $target_scheme || $home_host !== $target_host || $home_port !== $target_port ) {
			return new \WP_Error( 'outside_site', __( 'The MVP only analyzes URLs from the current site.', 'creceweb-lumen-lite' ) );
		}

		$home_path   = '/' . ltrim( (string) ( $home['path'] ?? '/' ), '/' );
		$target_path = '/' . ltrim( (string) ( $target['path'] ?? '/' ), '/' );

		if ( '/' !== $home_path && ! str_starts_with( trailingslashit( $target_path ), trailingslashit( $home_path ) ) ) {
			return new \WP_Error( 'outside_site_path', __( 'The URL is outside the current WordPress site path.', 'creceweb-lumen-lite' ) );
		}

		if ( str_contains( $target_path, '/wp-admin/' ) || str_ends_with( $target_path, '/wp-login.php' ) ) {
			return new \WP_Error( 'private_url', __( 'Administration and login URLs cannot be analyzed.', 'creceweb-lumen-lite' ) );
		}

		if ( isset( $target['query'] ) && '' !== (string) $target['query'] ) {
			parse_str( (string) $target['query'], $query );
			$blocked = array( '_wpnonce', 'preview', 'preview_id', 'preview_nonce', 'token', 'key', 'password', self::QUERY_ARG );
			foreach ( $blocked as $key ) {
				if ( array_key_exists( $key, $query ) ) {
					return new \WP_Error( 'private_query', __( 'Preview, nonce, password, key, or token URLs cannot be analyzed.', 'creceweb-lumen-lite' ) );
				}
			}
		}

		return remove_query_arg( self::QUERY_ARG, $url );
	}

	/**
	 * @param string $view View.
	 * @param string $notice Notice.
	 * @param string $profile Explicit analysis profile, or the current render profile.
	 * @return string
	 */
	private function normalize_comparison_mode( string $mode ): string {
		$mode = sanitize_key( $mode );
		return in_array( $mode, array( 'before_after', 'pages' ), true ) ? $mode : 'before_after';
	}

	private function comparison_mode(): string {
		$raw = isset( $_GET['compare_mode'] ) ? sanitize_key( wp_unslash( $_GET['compare_mode'] ) ) : 'before_after'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only comparison mode.
		return $this->normalize_comparison_mode( $raw );
	}

	private function comparison_url( string $mode = 'before_after', string $notice = '', string $profile = '' ): string {
		return add_query_arg(
			array( 'compare_mode' => $this->normalize_comparison_mode( $mode ) ),
			$this->panel_url( 'comparison', $notice, $profile )
		);
	}

	private function comparison_urls_match( array $baseline, array $current ): bool {
		$a = isset( $baseline['analysis']['url'] ) ? untrailingslashit( (string) $baseline['analysis']['url'] ) : '';
		$b = isset( $current['analysis']['url'] ) ? untrailingslashit( (string) $current['analysis']['url'] ) : '';
		return '' !== $a && $a === $b;
	}

	private function panel_url( string $view = 'summary', string $notice = '', string $profile = '' ): string {
		$profile = '' !== $profile ? $this->normalize_profile( $profile ) : $this->render_profile;
		$args = array( 'perf_view' => sanitize_key( $view ) );
		if ( '' !== $notice ) {
			$args['cw_lumen_perf_notice'] = sanitize_key( $notice );
		}

		$config = ProfileRegistry::get( $profile );
		if ( is_array( $config ) && is_callable( $config['panel_url'] ?? null ) ) {
			$url = call_user_func( $config['panel_url'], sanitize_key( $view ), sanitize_key( $notice ) );
			if ( is_string( $url ) && '' !== $url ) {
				return esc_url_raw( $url );
			}
		}

		if ( function_exists( '\\CreceWeb\\Lumen\\get_admin_section_url' ) ) {
			return \CreceWeb\Lumen\get_admin_section_url( self::SECTION, $args );
		}

		$url = add_query_arg(
			array_merge(
				array(
					'page' => 'creceweb-lumen-lite',
					'tab'  => self::SECTION,
				),
				$args
			),
			admin_url( 'themes.php' )
		);
		return $url;
	}

	/** @return array{label:string,description:string} */
	private function friendly_overall_state( array $state ): array {
		$key = (string) ( $state['key'] ?? 'insufficient' );
		if ( 'correct' === $key ) { return array( 'label' => __( 'Healthy', 'creceweb-lumen-lite' ), 'description' => __( 'Everything Lumen expected on this page loaded correctly, with no current rule requiring action.', 'creceweb-lumen-lite' ) ); }
		if ( 'error' === $key ) { return array( 'label' => __( 'Needs attention', 'creceweb-lumen-lite' ), 'description' => (string) ( $state['description'] ?? '' ) ); }
		return array( 'label' => __( 'Review', 'creceweb-lumen-lite' ), 'description' => (string) ( $state['description'] ?? '' ) );
	}

	private function actionable_issue_count( array $report ): int {
		$count = 0;
		foreach ( (array) ( $report['results'] ?? array() ) as $result ) {
			if ( ! is_array( $result ) || 'observation_only' === (string) ( $result['mode'] ?? '' ) ) { continue; }
			if ( in_array( (string) ( $result['status'] ?? '' ), array( 'optimizable', 'anomaly', 'functional_error', 'pending' ), true ) ) { ++$count; }
		}
		foreach ( (array) ( $report['duplicates'] ?? array() ) as $duplicate ) { if ( is_array( $duplicate ) ) { ++$count; } }
		return $count;
	}

	/** @return array{count:int,bytes:int} */
	private function conditional_loading_summary( array $report ): array {
		$count = 0; $bytes = 0;
		foreach ( (array) ( $report['results'] ?? array() ) as $result ) {
			if ( ! is_array( $result ) || empty( $result['configured'] ) || ! empty( $result['used'] ) || ! empty( $result['expected'] ) || ! empty( $result['observed'] ) ) { continue; }
			++$count; $bytes += max( 0, (int) ( $result['size_bytes'] ?? 0 ) );
		}
		return array( 'count' => $count, 'bytes' => $bytes );
	}

	/** @return array<int,array{provider:string,label:string,bytes:int,percent:float}> */
	private function provider_footprint( array $report ): array {
		$groups = array(); $total = 0;
		foreach ( (array) ( $report['results'] ?? array() ) as $result ) {
			if ( ! is_array( $result ) ) { continue; }
			$provider = (string) ( $result['provider'] ?? '' );
			if ( '' === $provider ) { continue; }
			if ( ! isset( $groups[ $provider ] ) ) { $groups[ $provider ] = 0; }
			if ( ! empty( $result['observed'] ) ) { $size = max( 0, (int) ( $result['size_bytes'] ?? 0 ) ); $groups[ $provider ] += $size; $total += $size; }
		}
		$out = array();
		foreach ( $groups as $provider => $bytes ) { $out[] = array( 'provider' => $provider, 'label' => $this->provider_label( $provider ), 'bytes' => $bytes, 'percent' => $total > 0 ? round( ( $bytes / $total ) * 100, 1 ) : 0.0 ); }
		return $out;
	}

	/** @return array<int,array<string,mixed>> */
	private function top_contributors( array $report, int $limit = 5 ): array {
		$items = array();
		foreach ( (array) ( $report['results'] ?? array() ) as $result ) {
			if ( ! is_array( $result ) || empty( $result['observed'] ) || (int) ( $result['size_bytes'] ?? 0 ) <= 0 ) { continue; }
			$items[] = $result;
		}
		usort( $items, static fn( array $a, array $b ): int => (int) ( $b['size_bytes'] ?? 0 ) <=> (int) ( $a['size_bytes'] ?? 0 ) );
		return array_slice( $items, 0, max( 1, $limit ) );
	}

	private function render_meaning_list( array $report, int $issues, array $conditional ): void {
		$missing = false; $unexpected = false;
		foreach ( (array) ( $report['results'] ?? array() ) as $result ) {
			if ( ! is_array( $result ) ) { continue; }
			$status = (string) ( $result['status'] ?? '' );
			if ( 'functional_error' === $status ) { $missing = true; }
			if ( in_array( $status, array( 'optimizable', 'anomaly' ), true ) ) { $unexpected = true; }
		}
		$limits_within = true;
		if ( $this->profile_has_budgets() ) { $budget = ( new BudgetEvaluator() )->evaluate( $report, $this->profile_budgets() ); $limits_within = 'within' === (string) ( $budget['state'] ?? 'within' ); }
		$items = array(
			array( ! $missing, $missing ? __( 'At least one expected Lumen asset was not observed.', 'creceweb-lumen-lite' ) : __( 'All expected Lumen assets were observed.', 'creceweb-lumen-lite' ) ),
			array( ! $unexpected, $unexpected ? __( 'At least one Lumen asset loaded outside its expected use.', 'creceweb-lumen-lite' ) : __( 'No unexpected Lumen loads were detected.', 'creceweb-lumen-lite' ) ),
			array( empty( $report['duplicates'] ), empty( $report['duplicates'] ) ? __( 'No exact duplicate Lumen asset sources were detected.', 'creceweb-lumen-lite' ) : __( 'An exact duplicate Lumen asset source needs review.', 'creceweb-lumen-lite' ) ),
			array( true, $conditional['count'] > 0 ? __( 'Conditional loading kept unused configured assets off this page.', 'creceweb-lumen-lite' ) : __( 'No configured-but-unused Lumen assets were identified.', 'creceweb-lumen-lite' ) ),
		);
		if ( $this->profile_has_budgets() ) { $items[] = array( $limits_within, $limits_within ? __( 'All Lumen reference limits are within range.', 'creceweb-lumen-lite' ) : __( 'At least one Lumen reference limit should be reviewed.', 'creceweb-lumen-lite' ) ); }
		?><ul class="cw-lumen-performance__meaning-list"><?php foreach ( $items as $item ) : ?><li class="<?php echo $item[0] ? 'is-good' : 'is-review'; ?>"><span aria-hidden="true"><?php echo $item[0] ? '✓' : '!'; ?></span><?php echo esc_html( $item[1] ); ?></li><?php endforeach; ?></ul><?php
	}

	/**
	 * Visual utilization band for reference-limit progress bars.
	 * This is presentation only; BudgetEvaluator remains the source of truth
	 * for within / near / over state.
	 *
	 * @param float $ratio Percentage of the reference limit in use.
	 * @return string
	 */
	private function limit_progress_band( float $ratio ): string {
		if ( $ratio > 100 ) {
			return 'over';
		}
		if ( $ratio >= 90 ) {
			return 'high';
		}
		if ( $ratio >= 70 ) {
			return 'medium';
		}

		return 'low';
	}

	private function render_overview_limits( array $report ): void {
		$result = ( new BudgetEvaluator() )->evaluate( $report, $this->profile_budgets() ); $items = (array) ( $result['items'] ?? array() );
		?><section class="cw-lumen-performance__guided-card cw-lumen-performance__guided-card--wide"><div class="cw-lumen-performance__section-heading cw-lumen-performance__section-heading--inside"><h3><?php echo esc_html__( 'Reference limits at a glance', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'These are Lumen guardrails, not a page-speed score.', 'creceweb-lumen-lite' ); ?></p></div><div class="cw-lumen-performance__limit-list"><?php
		foreach ( $items as $item ) { if ( ! is_array( $item ) ) { continue; } $max=max(1,(int)($item['max']??0)); $current=max(0,(int)($item['current']??0)); $ratio=round(($current/$max)*100,1); $is_bytes='bytes'===(string)($item['unit']??''); ?>
			<div class="cw-lumen-performance__limit-row"><div><strong><?php echo esc_html((string)($item['label']??'')); ?></strong><span><?php echo esc_html(($is_bytes?$this->format_bytes($current):(string)$current).' of '.($is_bytes?$this->format_bytes($max):(string)$max).' · '.$ratio.'%'); ?></span></div><div class="cw-lumen-performance__limit-progress cw-lumen-performance__limit-progress--<?php echo esc_attr($this->limit_progress_band((float)$ratio)); ?>" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr((string)min(100,max(0,$ratio))); ?>"><span style="width:<?php echo esc_attr((string)min(100,max(0,$ratio))); ?>%"></span></div></div>
		<?php } ?></div></section><?php
	}

	private function render_provider_footprint( array $footprint ): void {
		if ( empty( $footprint ) ) { echo '<p>' . esc_html__( 'No Lumen footprint data is available.', 'creceweb-lumen-lite' ) . '</p>'; return; }
		?><div class="cw-lumen-performance__footprint-list"><?php foreach ( $footprint as $item ) : ?><div class="cw-lumen-performance__footprint-row"><div><strong><?php echo esc_html((string)$item['label']); ?></strong><span><?php echo esc_html($this->format_bytes((int)$item['bytes']).' · '.(string)$item['percent'].'%'); ?></span></div><div class="cw-lumen-performance__footprint-bar"><span style="width:<?php echo esc_attr((string)min(100,max(0,(float)$item['percent']))); ?>%"></span></div></div><?php endforeach; ?></div><?php
	}

	private function render_top_contributors( array $items, int $payload_bytes = 0 ): void {
		if ( empty( $items ) ) {
			echo '<p>' . esc_html__( 'No loaded packaged assets were reported.', 'creceweb-lumen-lite' ) . '</p>';
			return;
		}

		$payload_bytes = max( 0, $payload_bytes );
		?>
		<ol class="cw-lumen-performance__contributors">
			<?php foreach ( $items as $item ) : ?>
				<?php
				$provider   = (string) ( $item['provider'] ?? '' );
				$size       = max( 0, (int) ( $item['size_bytes'] ?? 0 ) );
				$share      = $payload_bytes > 0 ? min( 100, max( 0, ( $size / $payload_bytes ) * 100 ) ) : 0;
				$share_band = $share >= 50 ? 'dominant' : ( $share >= 25 ? 'high' : ( $share >= 10 ? 'medium' : 'low' ) );
				?>
				<li>
					<div class="cw-lumen-performance__contributor-meta">
						<div>
							<strong><?php echo esc_html( $this->module_label( (string) ( $item['module'] ?? '' ), $provider ) ); ?></strong>
							<span><?php echo esc_html( $this->provider_label( $provider ) ); ?></span>
						</div>
						<div class="cw-lumen-performance__contributor-value">
							<strong><?php echo esc_html( $this->format_bytes( $size ) ); ?></strong>
							<span><?php echo esc_html( $this->format_percent( $share ) ); ?> <?php echo esc_html__( 'of loaded Lumen payload', 'creceweb-lumen-lite' ); ?></span>
						</div>
					</div>
					<div class="cw-lumen-performance__contributor-bar cw-lumen-performance__contributor-bar--<?php echo esc_attr( $share_band ); ?>" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( (string) round( $share, 1 ) ); ?>" aria-label="<?php echo esc_attr__( 'Share of loaded Lumen payload', 'creceweb-lumen-lite' ); ?>">
						<span style="width:<?php echo esc_attr( (string) round( $share, 2 ) ); ?>%"></span>
					</div>
					<code><?php echo esc_html( (string) ( $item['handle'] ?? '' ) ); ?></code>
				</li>
			<?php endforeach; ?>
		</ol>
		<p class="cw-lumen-performance__scope-note"><?php echo esc_html__( 'Darker blue means a larger share of the current Lumen payload. It does not mean the asset is incorrect or should be removed.', 'creceweb-lumen-lite' ); ?></p>
		<?php
	}


	/** @return array<string,array<string,mixed>> */
	private function module_groups( array $report ): array {
		$groups=array();
		foreach((array)($report['results']??array()) as $result){ if(!is_array($result))continue; $provider=(string)($result['provider']??''); $module=(string)($result['module']??''); $key=$provider.':'.$module; if(!isset($groups[$key])){$groups[$key]=array('provider'=>$provider,'module'=>$module,'results'=>array(),'configured'=>0,'used'=>0,'expected'=>0,'observed'=>0,'bytes'=>0,'worst'=>array('key'=>'correct','label'=>__('Correct','creceweb-lumen-lite')));} $groups[$key]['results'][]=$result; foreach(array('configured','used','expected') as $flag){if(!empty($result[$flag]))++$groups[$key][$flag];} if(!empty($result['observed'])){++$groups[$key]['observed'];$groups[$key]['bytes']+=max(0,(int)($result['size_bytes']??0));} $status=$this->public_status($result); if($this->status_rank($status['key'])>$this->status_rank($groups[$key]['worst']['key']))$groups[$key]['worst']=$status; }
		return $groups;
	}

	private function render_feature_group( string $title, string $description, array $groups, string $bucket ): void {
		if ( empty( $groups ) && 'used' !== $bucket ) { return; }
		?><section class="cw-lumen-performance__feature-section"><?php if(''!==$title):?><div class="cw-lumen-performance__section-heading cw-lumen-performance__section-heading--inside"><h3><?php echo esc_html($title); ?></h3><p><?php echo esc_html($description); ?></p></div><?php elseif(''!==$description):?><p class="cw-lumen-performance__scope-note"><?php echo esc_html($description); ?></p><?php endif; ?>
		<?php if(empty($groups)):?><div class="cw-lumen-performance__empty cw-lumen-performance__empty--compact"><p><?php echo esc_html__('No Lumen features are currently in use in this group.','creceweb-lumen-lite'); ?></p></div><?php else:?><div class="cw-lumen-performance__module-grid"><?php foreach($groups as $group): $provider_label=$this->provider_label((string)$group['provider']); $usage=(int)$group['used']>0?'used':'unused'; $usage_label='used'===$usage?__('Used','creceweb-lumen-lite'):__('Not used','creceweb-lumen-lite'); $badge_key='attention'===$bucket?(string)$group['worst']['key']:('inactive'===$bucket?'inactive':('available'===$bucket?'inactive':'correct')); $badge_label='attention'===$bucket?(string)$group['worst']['label']:('inactive'===$bucket?__('Inactive','creceweb-lumen-lite'):('available'===$bucket?__('Not needed','creceweb-lumen-lite'):__('In use','creceweb-lumen-lite'))); ?>
		<article class="cw-lumen-performance__module" data-cw-performance-item="module" data-filter-component="<?php echo esc_attr(sanitize_key((string)$group['provider'])); ?>" data-filter-component-label="<?php echo esc_attr($provider_label); ?>" data-filter-state="<?php echo esc_attr($badge_key); ?>" data-filter-state-label="<?php echo esc_attr($badge_label); ?>" data-filter-usage="<?php echo esc_attr($usage); ?>" data-filter-usage-label="<?php echo esc_attr($usage_label); ?>"><div class="cw-lumen-performance__module-head"><div><span><?php echo esc_html($provider_label); ?></span><h4><?php echo esc_html($this->module_label((string)$group['module'],(string)$group['provider'])); ?></h4></div><span class="cw-lumen-performance__badge cw-lumen-performance__badge--<?php echo esc_attr($badge_key); ?>"><?php echo esc_html($badge_label); ?></span></div><?php /* translators: %1$d: number of loaded assets; %2$s: packaged size. */ ?><p><?php echo esc_html(sprintf(__('Loaded: %1$d assets · %2$s packaged','creceweb-lumen-lite'),(int)$group['observed'],$this->format_bytes((int)$group['bytes']))); ?></p><details class="cw-lumen-performance__technical"><summary><?php echo esc_html__('Technical details','creceweb-lumen-lite'); ?></summary><dl class="cw-lumen-performance__module-facts"><div><dt><?php echo esc_html__('Configured','creceweb-lumen-lite'); ?></dt><dd><?php echo esc_html((string)(int)$group['configured']); ?></dd></div><div><dt><?php echo esc_html__('Used','creceweb-lumen-lite'); ?></dt><dd><?php echo esc_html((string)(int)$group['used']); ?></dd></div><div><dt><?php echo esc_html__('Expected','creceweb-lumen-lite'); ?></dt><dd><?php echo esc_html((string)(int)$group['expected']); ?></dd></div><div><dt><?php echo esc_html__('Loaded','creceweb-lumen-lite'); ?></dt><dd><?php echo esc_html((string)(int)$group['observed']); ?></dd></div></dl></details></article>
		<?php endforeach;?></div><?php endif;?></section><?php
	}

	/** @return array{key:string,label:string} */
	private function friendly_resource_state( array $result ): array {
		if ( 'observation_only' === (string) ( $result['mode'] ?? '' ) ) { return ! empty($result['observed']) ? array('key'=>'attention','label'=>__('Detected','creceweb-lumen-lite')) : array('key'=>'inactive','label'=>__('Not loaded','creceweb-lumen-lite')); }
		$status=(string)($result['status']??'pending');
		if('correct'===$status)return array('key'=>'correct','label'=>__('Loaded as expected','creceweb-lumen-lite'));
		if('correct_no_cost'===$status)return array('key'=>'inactive','label'=>__('Not needed','creceweb-lumen-lite'));
		if('optimizable'===$status||'anomaly'===$status)return array('key'=>'optimizable','label'=>__('Unexpected load','creceweb-lumen-lite'));
		if('functional_error'===$status)return array('key'=>'error','label'=>__('Expected but missing','creceweb-lumen-lite'));
		return array('key'=>'attention','label'=>__('Review','creceweb-lumen-lite'));
	}

	private function resource_explanation( array $result ): string {
		$reason=trim((string)($result['reason']??'')); $status=(string)($result['status']??'');
		if('correct'===$status)return ''!==$reason?$reason:__('This asset was needed on the current page and loaded as expected.','creceweb-lumen-lite');
		if('correct_no_cost'===$status)return !empty($result['configured'])?__('Available, but the current page does not need it, so it stayed unloaded.','creceweb-lumen-lite'):__('This feature is inactive for the current page, so the asset is not expected.','creceweb-lumen-lite');
		if('functional_error'===$status)return ''!==$reason?$reason:__('The current Lumen rule expected this asset, but it was not observed.','creceweb-lumen-lite');
		if(in_array($status,array('optimizable','anomaly'),true))return ''!==$reason?$reason:__('This asset loaded although the current rule did not detect a need for it.','creceweb-lumen-lite');
		return ''!==$reason?$reason:__('Run another analysis to confirm this asset state.','creceweb-lumen-lite');
	}

	private function resource_counts_label( array $result ): string {
		return 'C/U/E/L: '.(!empty($result['configured'])?'1':'0').'/'.(!empty($result['used'])?'1':'0').'/'.(!empty($result['expected'])?'1':'0').'/'.(!empty($result['observed'])?'1':'0');
	}

	private function render_comparison_highlights( array $comparison, string $mode ): void {
		$metrics          = (array) ( $comparison['metrics'] ?? array() );
		$module_changes   = (array) ( $comparison['modules'] ?? array() );
		$resource_changes = (array) ( $comparison['resources'] ?? array() );

		$metric_key   = '';
		$metric_delta = 0;
		foreach ( array( 'css_bytes', 'js_bytes', 'resources', 'requests' ) as $key ) {
			$delta = (int) ( $metrics[ $key ]['delta'] ?? 0 );
			if ( abs( $delta ) > abs( $metric_delta ) ) {
				$metric_delta = $delta;
				$metric_key   = $key;
			}
		}

		$metric_labels = array(
			'css_bytes' => __( 'Packaged CSS', 'creceweb-lumen-lite' ),
			'js_bytes'  => __( 'Packaged JavaScript', 'creceweb-lumen-lite' ),
			'resources' => __( 'Loaded assets', 'creceweb-lumen-lite' ),
			'requests'  => __( 'Requests', 'creceweb-lumen-lite' ),
		);

		$largest       = null;
		$largest_delta = 0;
		foreach ( $module_changes as $change ) {
			if ( ! is_array( $change ) ) {
				continue;
			}
			$a     = is_array( $change['baseline'] ?? null ) ? $change['baseline'] : array();
			$b     = is_array( $change['current'] ?? null ) ? $change['current'] : array();
			$delta = (int) ( $b['bytes'] ?? 0 ) - (int) ( $a['bytes'] ?? 0 );
			if ( abs( $delta ) > abs( $largest_delta ) ) {
				$largest_delta = $delta;
				$largest       = $change;
			}
		}

		$counts = array( 'added' => 0, 'removed' => 0, 'changed' => 0 );
		foreach ( $resource_changes as $change ) {
			if ( ! is_array( $change ) ) {
				continue;
			}
			$key = (string) ( $change['change'] ?? '' );
			if ( isset( $counts[ $key ] ) ) {
				++$counts[ $key ];
			}
		}

		$is_pages   = 'pages' === $mode;
		$is_bytes   = in_array( $metric_key, array( 'css_bytes', 'js_bytes' ), true );
		$metric_val = $is_bytes ? $this->format_bytes( abs( $metric_delta ) ) : (string) abs( $metric_delta );
		$metric_dir = $metric_delta > 0
			? ( $is_pages ? __( 'more on Page B', 'creceweb-lumen-lite' ) : __( 'more in Current', 'creceweb-lumen-lite' ) )
			: ( $metric_delta < 0
				? ( $is_pages ? __( 'less on Page B', 'creceweb-lumen-lite' ) : __( 'less in Current', 'creceweb-lumen-lite' ) )
				: __( 'no difference', 'creceweb-lumen-lite' ) );
		?>
		<section class="cw-lumen-performance__comparison-highlights">
			<div class="cw-lumen-performance__section-heading cw-lumen-performance__section-heading--inside">
				<h3><?php echo esc_html__( 'At a glance', 'creceweb-lumen-lite' ); ?></h3>
				<p><?php echo esc_html( $is_pages
					? __( 'Start here: these are the largest inventory differences between the two pages.', 'creceweb-lumen-lite' )
					: __( 'Start here: these are the largest differences between the baseline and current analysis.', 'creceweb-lumen-lite' ) ); ?></p>
			</div>
			<div class="cw-lumen-performance__comparison-summary-grid">
				<article>
					<span><?php echo esc_html__( 'Largest metric difference', 'creceweb-lumen-lite' ); ?></span>
					<strong><?php echo esc_html( '' !== $metric_key ? (string) $metric_labels[ $metric_key ] : __( 'No metric difference', 'creceweb-lumen-lite' ) ); ?></strong>
					<p><?php echo esc_html( '' !== $metric_key ? $metric_val . ' · ' . $metric_dir : __( 'The four headline metrics match.', 'creceweb-lumen-lite' ) ); ?></p>
				</article>
				<article>
					<span><?php echo esc_html__( 'Largest feature difference', 'creceweb-lumen-lite' ); ?></span>
					<?php if ( is_array( $largest ) && 0 !== $largest_delta ) : ?>
						<?php $provider = (string) ( $largest['provider'] ?? '' ); ?>
						<strong><?php echo esc_html( $this->module_label( (string) ( $largest['module'] ?? '' ), $provider ) ); ?></strong>
						<p><?php echo esc_html( $this->provider_label( $provider ) . ' · ' . $this->format_bytes( abs( $largest_delta ) ) . ' ' . ( $largest_delta > 0 ? __( 'more on the second sample', 'creceweb-lumen-lite' ) : __( 'less on the second sample', 'creceweb-lumen-lite' ) ) ); ?></p>
					<?php else : ?>
						<strong><?php echo esc_html__( 'No loaded feature difference', 'creceweb-lumen-lite' ); ?></strong>
						<p><?php echo esc_html__( 'Loaded packaged bytes match at feature level.', 'creceweb-lumen-lite' ); ?></p>
					<?php endif; ?>
				</article>
				<article>
					<span><?php echo esc_html__( 'Asset inventory', 'creceweb-lumen-lite' ); ?></span>
					<?php if ( $is_pages ) : ?>
						<strong><?php echo esc_html( (string) ( $counts['added'] + $counts['removed'] + $counts['changed'] ) ); ?> <?php echo esc_html__( 'differences', 'creceweb-lumen-lite' ); ?></strong>
						<p>
							<?php
							/* translators: %1$d: assets only on Page B; %2$d: assets only on Page A; %3$d: changed assets. */
							echo esc_html( sprintf( __( '%1$d only on Page B · %2$d only on Page A · %3$d different', 'creceweb-lumen-lite' ), $counts['added'], $counts['removed'], $counts['changed'] ) );
							?>
						</p>
					<?php else : ?>
						<strong><?php echo esc_html( (string) ( $counts['added'] + $counts['removed'] + $counts['changed'] ) ); ?> <?php echo esc_html__( 'asset changes', 'creceweb-lumen-lite' ); ?></strong>
						<p>
							<?php
							/* translators: %1$d: added assets; %2$d: removed assets; %3$d: changed assets. */
							echo esc_html( sprintf( __( '%1$d added · %2$d removed · %3$d changed', 'creceweb-lumen-lite' ), $counts['added'], $counts['removed'], $counts['changed'] ) );
							?>
						</p>
					<?php endif; ?>
				</article>
			</div>
		</section>
		<?php
	}

	/**
	 * Human-first feature comparison. Exact handles and counters remain in the
	 * collapsed technical comparison below.
	 *
	 * @param array<int,array<string,mixed>> $changes Module changes.
	 * @return void
	 */
	private function render_comparison_story( array $changes, string $mode ): void {
		$is_pages = 'pages' === $mode;
		$buckets  = $is_pages
			? array( 'left' => array(), 'right' => array(), 'different' => array() )
			: array( 'added' => array(), 'removed' => array(), 'changed' => array() );

		foreach ( $changes as $change ) {
			if ( ! is_array( $change ) ) {
				continue;
			}

			$a = is_array( $change['baseline'] ?? null ) ? $change['baseline'] : array();
			$b = is_array( $change['current'] ?? null ) ? $change['current'] : array();

			$a_loaded = (int) ( $a['observed'] ?? 0 );
			$b_loaded = (int) ( $b['observed'] ?? 0 );
			$a_bytes  = max( 0, (int) ( $a['bytes'] ?? 0 ) );
			$b_bytes  = max( 0, (int) ( $b['bytes'] ?? 0 ) );

			// Configuration-only differences with no loaded code stay technical.
			if ( 0 === $a_loaded && 0 === $b_loaded && 0 === $a_bytes && 0 === $b_bytes ) {
				continue;
			}

			$item = array(
				'provider' => (string) ( $change['provider'] ?? '' ),
				'module'   => (string) ( $change['module'] ?? '' ),
				'a_loaded' => $a_loaded,
				'b_loaded' => $b_loaded,
				'a_bytes'  => $a_bytes,
				'b_bytes'  => $b_bytes,
				'weight'   => max( $a_bytes, $b_bytes ),
			);

			if ( $is_pages ) {
				if ( $a_loaded > 0 && 0 === $b_loaded ) {
					$buckets['left'][] = $item;
				} elseif ( $b_loaded > 0 && 0 === $a_loaded ) {
					$buckets['right'][] = $item;
				} else {
					$buckets['different'][] = $item;
				}
			} else {
				if ( 0 === $a_loaded && $b_loaded > 0 ) {
					$buckets['added'][] = $item;
				} elseif ( $a_loaded > 0 && 0 === $b_loaded ) {
					$buckets['removed'][] = $item;
				} else {
					$buckets['changed'][] = $item;
				}
			}
		}

		foreach ( $buckets as &$bucket ) {
			usort(
				$bucket,
				static function ( array $a, array $b ): int {
					return (int) $b['weight'] <=> (int) $a['weight'];
				}
			);
		}
		unset( $bucket );

		$labels = $is_pages
			? array(
				'left'      => array( __( 'Loaded only on Page A', 'creceweb-lumen-lite' ), __( 'Loaded Lumen features present on Page A but not Page B.', 'creceweb-lumen-lite' ) ),
				'right'     => array( __( 'Loaded only on Page B', 'creceweb-lumen-lite' ), __( 'Loaded Lumen features present on Page B but not Page A.', 'creceweb-lumen-lite' ) ),
				'different' => array( __( 'Loaded on both, with changes', 'creceweb-lumen-lite' ), __( 'Shared loaded features whose packaged contribution differs between Page A and Page B.', 'creceweb-lumen-lite' ) ),
			)
			: array(
				'added'   => array( __( 'Added in Current', 'creceweb-lumen-lite' ), __( 'Features that were not loaded in the baseline and are loaded now.', 'creceweb-lumen-lite' ) ),
				'removed' => array( __( 'Removed from Current', 'creceweb-lumen-lite' ), __( 'Features that were loaded in the baseline and are no longer loaded.', 'creceweb-lumen-lite' ) ),
				'changed' => array( __( 'Loaded in both, but changed', 'creceweb-lumen-lite' ), __( 'Features present in both analyses whose packaged contribution differs.', 'creceweb-lumen-lite' ) ),
			);
		?>
		<section class="cw-lumen-performance__comparison-story">
			<div class="cw-lumen-performance__section-heading cw-lumen-performance__section-heading--compare">
				<h3><?php echo esc_html( $is_pages ? __( 'What differs between these pages', 'creceweb-lumen-lite' ) : __( 'What changed', 'creceweb-lumen-lite' ) ); ?></h3>
				<p><?php echo esc_html__( 'This section focuses on loaded Lumen features. Configuration-only differences remain in Technical comparison.', 'creceweb-lumen-lite' ); ?></p>
			</div>
			<div class="cw-lumen-performance__comparison-story-grid">
				<?php foreach ( $labels as $key => $label ) : ?>
					<article class="cw-lumen-performance__comparison-story-card">
						<div class="cw-lumen-performance__comparison-story-head">
							<div>
								<h4><?php echo esc_html( $label[0] ); ?></h4>
								<p><?php echo esc_html( $label[1] ); ?></p>
							</div>
							<span class="cw-lumen-performance__comparison-count"><?php echo esc_html( (string) count( $buckets[ $key ] ) ); ?></span>
						</div>
						<?php if ( empty( $buckets[ $key ] ) ) : ?>
							<p class="cw-lumen-performance__scope-note"><?php echo esc_html__( 'No shared loaded features changed in this comparison.', 'creceweb-lumen-lite' ); ?></p>
						<?php else : ?>
							<ul class="cw-lumen-performance__comparison-feature-list">
								<?php foreach ( array_slice( $buckets[ $key ], 0, 6 ) as $item ) : ?>
									<li>
										<div>
											<strong><?php echo esc_html( $this->module_label( $item['module'], $item['provider'] ) ); ?></strong>
											<span><?php echo esc_html( $this->provider_label( $item['provider'] ) ); ?></span>
										</div>
										<?php if ( $is_pages ) : ?>
											<small><?php echo esc_html( $this->format_bytes( $item['a_bytes'] ) . ' → ' . $this->format_bytes( $item['b_bytes'] ) ); ?></small>
										<?php else : ?>
											<small><?php echo esc_html( $this->format_bytes( $item['a_bytes'] ) . ' → ' . $this->format_bytes( $item['b_bytes'] ) ); ?></small>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
							<?php if ( count( $buckets[ $key ] ) > 6 ) : ?>
								<p class="cw-lumen-performance__scope-note">
									<?php
									/* translators: %d: number of additional feature differences hidden from the summary. */
									echo esc_html( sprintf( __( '%d more feature differences are available in Technical comparison.', 'creceweb-lumen-lite' ), count( $buckets[ $key ] ) - 6 ) );
									?>
								</p>
							<?php endif; ?>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}


	/**
	 * @param array<string,mixed> $report Report.
	 * @return array{css_bytes:int,js_bytes:int,observed:int,requests:int}
	 */
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
			'observed'  => $observed,
			'requests'  => (int) ( $summary['observed_requests'] ?? 0 ),
		);
	}

	/**
	 * @param array<string,mixed> $report Report.
	 * @return array{key:string,label:string,description:string}
	 */
	private function overall_state( array $report ): array {
		$key = 'correct';

		foreach ( (array) ( $report['results'] ?? array() ) as $result ) {
			if ( ! is_array( $result ) || 'observation_only' === (string) ( $result['mode'] ?? '' ) ) {
				continue;
			}
			$status = $this->public_status( $result );
			if ( $this->status_rank( $status['key'] ) > $this->status_rank( $key ) ) {
				$key = $status['key'];
			}
		}

		foreach ( (array) ( $report['duplicates'] ?? array() ) as $duplicate ) {
			if ( is_array( $duplicate ) && 'error' === (string) ( $duplicate['severity'] ?? '' ) ) {
				$key = 'error';
			}
		}

		$states = array(
			'correct' => array(
				'key' => 'correct',
				'label' => __( 'Correct', 'creceweb-lumen-lite' ),
				'description' => $this->profile_copy( 'correct_description', __( 'The Theme and Lite resources detected on this URL match the current configuration and usage rules.', 'creceweb-lumen-lite' ) ),
			),
			'attention' => array(
				'key' => 'attention',
				'label' => __( 'Attention', 'creceweb-lumen-lite' ),
				'description' => __( 'Lumen detected a valid condition that deserves review before drawing a stronger conclusion.', 'creceweb-lumen-lite' ),
			),
			'optimizable' => array(
				'key' => 'optimizable',
				'label' => __( 'Optimizable', 'creceweb-lumen-lite' ),
				'description' => __( 'At least one Lumen resource loaded although the current rules did not detect usage on this URL.', 'creceweb-lumen-lite' ),
			),
			'error' => array(
				'key' => 'error',
				'label' => __( 'Error', 'creceweb-lumen-lite' ),
				'description' => __( 'A Lumen resource is missing, loaded outside its expected state, or duplicated by exact source.', 'creceweb-lumen-lite' ),
			),
			'insufficient' => array(
				'key' => 'insufficient',
				'label' => __( 'Not enough data', 'creceweb-lumen-lite' ),
				'description' => __( 'The analyzer does not have enough reliable information to classify every expected resource.', 'creceweb-lumen-lite' ),
			),
		);

		return $states[ $key ] ?? $states['insufficient'];
	}

	/**
	 * @param array<string,mixed> $result Result.
	 * @return array{key:string,label:string}
	 */
	private function public_status( array $result ): array {
		if ( 'observation_only' === (string) ( $result['mode'] ?? '' ) ) {
			return array(
				'key'   => 'attention',
				'label' => ! empty( $result['observed'] ) ? __( 'Detected', 'creceweb-lumen-lite' ) : __( 'Not loaded', 'creceweb-lumen-lite' ),
			);
		}

		$status = (string) ( $result['status'] ?? 'pending' );

		if ( in_array( $status, array( 'correct', 'correct_no_cost' ), true ) ) {
			return array( 'key' => 'correct', 'label' => __( 'Correct', 'creceweb-lumen-lite' ) );
		}
		if ( 'optimizable' === $status ) {
			return array( 'key' => 'optimizable', 'label' => __( 'Optimizable', 'creceweb-lumen-lite' ) );
		}
		if ( in_array( $status, array( 'anomaly', 'functional_error' ), true ) ) {
			return array( 'key' => 'error', 'label' => __( 'Error', 'creceweb-lumen-lite' ) );
		}
		if ( 'informational' === $status ) {
			return array( 'key' => 'attention', 'label' => __( 'Attention', 'creceweb-lumen-lite' ) );
		}
		return array( 'key' => 'insufficient', 'label' => __( 'Not enough data', 'creceweb-lumen-lite' ) );
	}

	/** @return int */
	private function status_rank( string $key ): int {
		return array(
			'correct'      => 0,
			'attention'    => 1,
			'insufficient' => 2,
			'optimizable'  => 3,
			'error'        => 4,
		)[ $key ] ?? 2;
	}

	/** @return array{theme:string,lite:string} */
	private function versions(): array {
		return array(
			'theme' => defined( 'CRECEWEB_LUMEN_VERSION' ) ? (string) CRECEWEB_LUMEN_VERSION : '',
			'lite'  => defined( 'CRECEWEB_LUMEN_LITE_VERSION' ) ? (string) CRECEWEB_LUMEN_LITE_VERSION : '',
		);
	}

	/**
	 * @param string $label Label.
	 * @param string $value Value.
	 * @param string $help Help.
	 * @return void
	 */
	private function metric_card( string $label, string $value, string $help ): void {
		?>
		<article class="cw-lumen-performance__metric">
			<span><?php echo esc_html( $label ); ?></span>
			<strong><?php echo esc_html( $value ); ?></strong>
			<small><?php echo esc_html( $help ); ?></small>
		</article>
		<?php
	}

	/**
	 * @param string $name Component.
	 * @param string $version Version.
	 * @param bool $active Active.
	 * @return void
	 */
	private function component_card( string $name, string $version, bool $active ): void {
		if ( $active ) {
			/* translators: %s: detected Lumen component version number. */
			$version_label = sprintf( __( 'Version %s', 'creceweb-lumen-lite' ), $version );
		} else {
			$version_label = __( 'Not active', 'creceweb-lumen-lite' );
		}
		?>
		<article class="cw-lumen-performance__component">
			<div>
				<strong><?php echo esc_html( $name ); ?></strong>
				<span><?php echo esc_html( $version_label ); ?></span>
			</div>
			<span class="cw-lumen-performance__dot<?php echo $active ? ' is-active' : ''; ?>" aria-hidden="true"></span>
		</article>
		<?php
	}

	/** @return string */
	private function provider_label( string $provider ): string {
		$label = array(
			'creceweb-lumen-theme' => 'Lumen Theme',
			'creceweb-lumen-lite'  => 'Lumen Lite',
		)[ $provider ] ?? $provider;

		$config = $this->profile_config();
		if ( is_callable( $config['provider_label'] ?? null ) ) {
			$value = call_user_func( $config['provider_label'], $provider, $label );
			if ( is_string( $value ) && '' !== $value ) {
				$label = $value;
			}
		}
		return sanitize_text_field( $label );
	}

	/** @return string */
	private function module_label( string $module, string $provider = '' ): string {
		$labels = array(
			'frontend_base'      => __( 'Base frontend', 'creceweb-lumen-lite' ),
			'sections'           => __( 'Sections', 'creceweb-lumen-lite' ),
			'navigation'         => __( 'Navigation', 'creceweb-lumen-lite' ),
			'library'            => __( 'Library', 'creceweb-lumen-lite' ),
			'content'            => __( 'Content and blog', 'creceweb-lumen-lite' ),
			'reading_progress'   => __( 'Reading progress', 'creceweb-lumen-lite' ),
			'table_of_contents'  => __( 'Table of contents', 'creceweb-lumen-lite' ),
			'sharing'            => __( 'Sharing', 'creceweb-lumen-lite' ),
			'related_content'     => __( 'Related content', 'creceweb-lumen-lite' ),
			'menu'               => __( 'Menu', 'creceweb-lumen-lite' ),
			'floating_action'    => __( 'Floating action', 'creceweb-lumen-lite' ),
			'messaging'          => __( 'Messaging', 'creceweb-lumen-lite' ),
			'admin'              => __( 'Administration', 'creceweb-lumen-lite' ),
		);
		$label = $labels[ $module ] ?? ucfirst( str_replace( '_', ' ', $module ) );
		$config = $this->profile_config();
		if ( is_callable( $config['module_label'] ?? null ) ) {
			$value = call_user_func( $config['module_label'], $module, $label, $provider );
			if ( is_string( $value ) && '' !== $value ) {
				$label = $value;
			}
		}
		return sanitize_text_field( $label );
	}

	/**
	 * Whether the active profile explicitly enables filters for a view.
	 *
	 * @param string $view View key.
	 * @return bool
	 */
	/** @return array<int,array<string,mixed>> */
	/**
	 * Generic profile-owned extra views.
	 *
	 * The shared engine only provides the extension point. A profile must
	 * explicitly register a label and render callback; Lite registers none.
	 *
	 * @return array<string,array{label:string,render:callable}>
	 */
	private function profile_custom_views(): array {
		$config = $this->profile_config();
		$raw    = isset( $config['views'] ) && is_array( $config['views'] ) ? $config['views'] : array();
		$views  = array();
		$reserved = array( 'summary', 'resources', 'modules', 'recommendations', 'comparison', 'budgets' );

		foreach ( $raw as $id => $view ) {
			$id = sanitize_key( (string) $id );
			if ( '' === $id || in_array( $id, $reserved, true ) || ! is_array( $view ) ) {
				continue;
			}

			$label  = sanitize_text_field( (string) ( $view['label'] ?? '' ) );
			$render = $view['render'] ?? null;
			if ( '' === $label || ! is_callable( $render ) ) {
				continue;
			}

			$views[ $id ] = array(
				'label'  => $label,
				'render' => $render,
			);
		}

		return $views;
	}

	private function profile_budgets(): array {
		$config  = $this->profile_config();
		$budgets = isset( $config['budgets'] ) && is_array( $config['budgets'] ) ? $config['budgets'] : array();
		return array_values( array_filter( $budgets, 'is_array' ) );
	}

	private function profile_has_budgets(): bool {
		return ! empty( $this->profile_budgets() );
	}

	private function profile_filters_enabled( string $view ): bool {
		$config  = $this->profile_config();
		$filters = isset( $config['filters'] ) && is_array( $config['filters'] ) ? $config['filters'] : array();
		return ! empty( $filters[ sanitize_key( $view ) ] );
	}

	/**
	 * Renders generic controls whose options are populated from item data.
	 *
	 * @param string $item_type Item type.
	 * @param array<int,string> $keys Filter keys.
	 * @return void
	 */
	private function render_filter_bar( string $item_type, array $keys ): void {
		$labels = array(
			'component' => __( 'Component', 'creceweb-lumen-lite' ),
			'type'      => __( 'Type', 'creceweb-lumen-lite' ),
			'state'     => __( 'State', 'creceweb-lumen-lite' ),
			'usage'     => __( 'Usage', 'creceweb-lumen-lite' ),
		);
		?>
		<div class="cw-lumen-performance__filters" data-cw-performance-filters="<?php echo esc_attr( sanitize_key( $item_type ) ); ?>">
			<div class="cw-lumen-performance__filter-fields">
				<?php foreach ( $keys as $key ) : ?>
					<?php
					$key = sanitize_key( $key );
					if ( ! isset( $labels[ $key ] ) ) {
						continue;
					}
					?>
					<label class="cw-lumen-performance__filter">
						<span><?php echo esc_html( $labels[ $key ] ); ?></span>
						<select data-cw-performance-filter="<?php echo esc_attr( $key ); ?>">
							<option value=""><?php echo esc_html__( 'All', 'creceweb-lumen-lite' ); ?></option>
						</select>
					</label>
				<?php endforeach; ?>
				<button type="button" class="button cw-lumen-performance__filter-reset" data-cw-performance-filter-reset><?php echo esc_html__( 'Clear filters', 'creceweb-lumen-lite' ); ?></button>
			</div>
			<p class="cw-lumen-performance__filter-count" data-cw-performance-filter-count aria-live="polite"></p>
		</div>
		<?php
	}

	private function strict_profile( string $profile ): string {
		$profile = sanitize_key( $profile );
		return '' !== $profile && ProfileRegistry::has( $profile ) ? $profile : '';
	}

	/**
	 * @param string $token Probe token.
	 * @param array<string,mixed> $pending Pending request.
	 * @return void
	 */
	private function store_profile_unavailable_result( string $token, array $pending ): void {
		$this->store_profile_failure_result( $token, $pending, 'analysis_profile_unavailable' );
	}

	/**
	 * @param string $token Probe token.
	 * @param array<string,mixed> $pending Pending request.
	 * @param string $error Error code.
	 * @return void
	 */
	private function store_profile_failure_result( string $token, array $pending, string $error ): void {
		set_transient(
			self::RESULT_PREFIX . $token,
			array(
				'user_id' => (int) ( $pending['user_id'] ?? 0 ),
				'report'  => array(
					'schema_version' => '1.0.0',
					'error'          => sanitize_key( $error ),
					'analysis'       => array(
						'url'               => esc_url_raw( (string) ( $pending['url'] ?? '' ) ),
						'requested_profile' => sanitize_key( (string) ( $pending['profile'] ?? '' ) ),
						'captured_at'       => time(),
					),
				),
			),
			self::PROBE_TTL
		);
	}

	/** @return string */
	private function normalize_profile( string $profile ): string {
		$profile = sanitize_key( $profile );
		return '' !== $profile && ProfileRegistry::has( $profile ) ? $profile : 'lite';
	}

	/** @return array<string,mixed> */
	private function profile_config(): array {
		$config = ProfileRegistry::get( $this->render_profile );
		return is_array( $config ) ? $config : array();
	}

	private function profile_copy( string $key, string $default ): string {
		$config = $this->profile_config();
		$copy   = isset( $config['copy'] ) && is_array( $config['copy'] ) ? $config['copy'] : array();
		return isset( $copy[ $key ] ) && is_string( $copy[ $key ] )
			? sanitize_text_field( $copy[ $key ] )
			: $default;
	}

	/**
	 * @param array<string,mixed> $report Report.
	 * @return array<int,array<string,mixed>>
	 */
	private function profile_components( array $report ): array {
		$config = $this->profile_config();
		if ( is_callable( $config['components'] ?? null ) ) {
			$value = call_user_func( $config['components'], $report );
			if ( is_array( $value ) ) {
				return $value;
			}
		}

		$versions = $this->versions();
		return array(
			array( 'name' => 'Lumen Theme', 'version' => $versions['theme'], 'active' => true ),
			array( 'name' => 'Lumen Lite', 'version' => $versions['lite'], 'active' => true ),
		);
	}

	/** @return array<int,string> */
	private function profile_allowed_paths( string $profile ): array {
		$config = ProfileRegistry::get( $this->normalize_profile( $profile ) );
		$paths  = is_array( $config ) && isset( $config['allowed_paths'] ) && is_array( $config['allowed_paths'] )
			? $config['allowed_paths']
			: array(
				'/wp-content/themes/creceweb-lumen/',
				'/wp-content/plugins/creceweb-lumen-lite/',
			);

		$clean = array();
		foreach ( $paths as $path ) {
			$path = is_string( $path ) ? trim( $path ) : '';
			if ( '' !== $path && str_starts_with( $path, '/' ) ) {
				$clean[] = $path;
			}
		}
		return array_values( array_unique( $clean ) );
	}

	private function resource_allowed_for_profile( string $url, string $profile ): bool {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		if ( '' === $path ) {
			return false;
		}
		foreach ( $this->profile_allowed_paths( $profile ) as $allowed ) {
			if ( str_starts_with( $path, $allowed ) ) {
				return true;
			}
		}
		return false;
	}

	private function baseline_key( string $profile, int $user_id ): string {
		return self::BASELINE_PREFIX . sanitize_key( $profile ) . '_' . absint( $user_id );
	}

	private function last_key( string $profile, int $user_id ): string {
		return self::LAST_PREFIX . sanitize_key( $profile ) . '_' . absint( $user_id );
	}

	/** @return string */
	private function format_percent( float $value ): string {
		$value = max( 0, min( 100, $value ) );
		return number_format_i18n( $value, $value < 0.05 ? 0 : 1 ) . '%';
	}

	/** @return string */
	private function format_bytes( int $bytes ): string {
		if ( $bytes <= 0 ) {
			return '0 B';
		}
		if ( $bytes < 1024 ) {
			return $bytes . ' B';
		}
		if ( $bytes < 1048576 ) {
			return number_format_i18n( $bytes / 1024, 1 ) . ' KB';
		}
		return number_format_i18n( $bytes / 1048576, 2 ) . ' MB';
	}

	/**
	 * @param array<string,mixed> $result Result.
	 * @return string
	 */
	private function recommendation_title( array $result ): string {
		$status = (string) ( $result['status'] ?? '' );
		$module = $this->module_label( (string) ( $result['module'] ?? '' ) );

		if ( 'optimizable' === $status ) {
			/* translators: %s: Lumen module name. */
			return sprintf( __( '%s loaded without detected use', 'creceweb-lumen-lite' ), $module );
		}
		if ( 'functional_error' === $status ) {
			/* translators: %s: Lumen module name. */
			return sprintf( __( '%s is missing an expected asset', 'creceweb-lumen-lite' ), $module );
		}
		if ( 'anomaly' === $status ) {
			/* translators: %s: Lumen module name. */
			return sprintf( __( '%s loads outside its expected state', 'creceweb-lumen-lite' ), $module );
		}
		/* translators: %s: Lumen module name. */
		return sprintf( __( '%s needs another analysis', 'creceweb-lumen-lite' ), $module );
	}

	/**
	 * @param array<string,mixed> $result Result.
	 * @return string
	 */
	private function recommendation_text( array $result ): string {
		$handle = (string) ( $result['handle'] ?? '' );
		$reason = (string) ( $result['reason'] ?? '' );
		$status = (string) ( $result['status'] ?? '' );

		if ( 'optimizable' === $status ) {
			return sprintf(
				/* translators: 1: WordPress asset handle. 2: Explanation of why the Lumen resource may be unnecessary. */
				__( 'Lumen observed %1$s even though the current module rule did not detect use on this URL. Review the module configuration and run the analysis again. %2$s', 'creceweb-lumen-lite' ),
				$handle,
				$reason
			);
		}
		if ( 'functional_error' === $status ) {
			return sprintf(
				/* translators: 1: WordPress asset handle. 2: Explanation of why the Lumen resource was expected. */
				__( 'The current rule expected %1$s but WordPress did not report it as loaded. Review the Lumen configuration and retry. %2$s', 'creceweb-lumen-lite' ),
				$handle,
				$reason
			);
		}
		if ( 'anomaly' === $status ) {
			return sprintf(
				/* translators: 1: WordPress asset handle. 2: Explanation of the unexpected Lumen resource state. */
				__( 'WordPress reported %1$s loaded while the current Lumen rule considered it inactive or unused. %2$s', 'creceweb-lumen-lite' ),
				$handle,
				$reason
			);
		}

		return __( 'The analyzer ran before enough enqueue information was available. Run the analysis again from the public URL.', 'creceweb-lumen-lite' );
	}

	/** @return string */
	private function module_url( string $provider, string $module ): string {
		$url = '';

		if ( 'creceweb-lumen-theme' === $provider && function_exists( '\\CreceWeb\\Lumen\\get_admin_section_url' ) ) {
			$url = \CreceWeb\Lumen\get_admin_section_url( 'theme' );
		} elseif ( 'creceweb-lumen-lite' === $provider ) {
			$section = array(
				'content'         => 'lite-content',
				'reading_progress' => 'lite-content',
				'table_of_contents' => 'lite-content',
				'sharing'           => 'lite-content',
				'related_content'    => 'lite-content',
				'menu'            => 'lite-menu',
				'floating_action' => 'lite-floating-action',
				'messaging'       => 'lite-messaging',
			)[ $module ] ?? '';

			if ( '' !== $section ) {
				$url = function_exists( '\\CreceWeb\\Lumen\\get_admin_section_url' )
					? \CreceWeb\Lumen\get_admin_section_url( $section )
					: add_query_arg(
						array( 'page' => 'creceweb-lumen-lite', 'tab' => $section ),
						admin_url( 'themes.php' )
					);
			}
		}

		$config = $this->profile_config();
		if ( is_callable( $config['module_url'] ?? null ) ) {
			$value = call_user_func( $config['module_url'], $provider, $module, $url );
			if ( is_string( $value ) ) {
				$url = $value;
			}
		}

		return esc_url_raw( $url );
	}

}
