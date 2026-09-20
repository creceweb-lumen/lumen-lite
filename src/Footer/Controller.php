<?php
/**
 * Footer credit customization supplied by Lumen Lite.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Footer;

use CreceWeb\LumenLite\Data\Settings;
use CreceWeb\LumenLite\Support\AdminRedirect;
use CreceWeb\LumenLite\Support\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds a plain-text footer credit template without taking ownership of Theme markup.
 */
final class Controller {
	public function __construct( private Compatibility $compatibility ) {}

	/** @return void */
	public function register(): void {
		add_action( 'admin_post_cw_lumen_lite_save_footer', array( $this, 'handle_save' ) );
		add_filter( 'creceweb_lumen_footer_credit_text', array( $this, 'filter_credit' ), 20 );
	}

	/** @return string */
	public function template(): string {
		$settings = Settings::get();
		return (string) ( $settings['footer_credit_text'] ?? Settings::default_footer_credit() );
	}

	/** @return string */
	public function expanded_text(): string {
		return strtr(
			$this->template(),
			array(
				'{year}'      => wp_date( 'Y' ),
				'{site_name}' => get_bloginfo( 'name' ),
			)
		);
	}

	/**
	 * @param string $text Theme footer credit.
	 * @return string
	 */
	public function filter_credit( string $text ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Filter contract.
		if ( ! $this->compatibility->is_compatible() ) {
			return $text;
		}

		return $this->expanded_text();
	}

	/** @return void */
	public function handle_save(): void {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'Your user account does not have permission to save these changes.', 'creceweb-lumen-lite' ) );
		}

		check_admin_referer( 'cw_lumen_lite_save_footer' );
		$return_url = isset( $_POST[ AdminRedirect::FIELD ] )
			? esc_url_raw( wp_unslash( (string) $_POST[ AdminRedirect::FIELD ] ) )
			: ( isset( $_POST['_wp_http_referer'] ) ? esc_url_raw( wp_unslash( (string) $_POST['_wp_http_referer'] ) ) : '' );
		$reset = isset( $_POST['cw_lumen_lite_footer_reset'] );
		$text  = $reset ? Settings::default_footer_credit() : ( isset( $_POST['footer_credit_text'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['footer_credit_text'] ) ) : '' );
		Settings::update_footer_credit( $text );

		$url = AdminRedirect::target( $return_url, admin_url( 'themes.php' ), $reset ? 'footer_reset' : 'footer_saved' );
		wp_safe_redirect( esc_url_raw( $url ) );
		exit;
	}

}
