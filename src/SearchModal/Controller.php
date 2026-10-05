<?php
/**
 * Accessible native WordPress search modal for Lumen Lite.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\SearchModal;

use CreceWeb\LumenLite\Data\Settings;
use CreceWeb\LumenLite\Support\AdminRedirect;
use CreceWeb\LumenLite\Support\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds one global search dialog using WordPress' native search request.
 */
final class Controller {
	public const ACTION_SAVE = 'cw_lumen_lite_save_search_modal';
	private bool $rendered = false;
	private bool $requested = false;
	/** @var array<string,mixed>|null */
	private ?array $config_cache = null;

	public function __construct( private Compatibility $compatibility ) {}

	/** @return void */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION_SAVE, array( $this, 'handle_save' ) );
		add_action( 'init', array( $this, 'register_shortcode' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 35 );
		add_action( 'wp_footer', array( $this, 'render_modal' ), 6 );
		add_filter( 'wp_nav_menu_items', array( $this, 'filter_primary_menu_items' ), 19, 2 );
	}

	/** @return void */
	public function register_shortcode(): void {
		add_shortcode( 'lumen_search', array( $this, 'shortcode' ) );
	}

	/** @return array<string,mixed> */
	public function settings(): array {
		$settings = Settings::get();
		return isset( $settings['search_modal'] ) && is_array( $settings['search_modal'] )
			? $settings['search_modal']
			: array();
	}

	/** @return array<string,mixed> */
	public function config(): array {
		if ( null !== $this->config_cache ) {
			return $this->config_cache;
		}

		$config = $this->settings();
		$filtered = apply_filters( 'creceweb_lumen_lite_search_modal_config', $config );
		$this->config_cache = is_array( $filtered ) ? $filtered : $config;
		return $this->config_cache;
	}

	/** @return bool */
	public function is_enabled(): bool {
		$this->compatibility->validate();
		$config = $this->config();
		return $this->compatibility->is_compatible() && ! empty( $config['enabled'] );
	}

	/**
	 * Whether this request contains a real Search Modal trigger.
	 *
	 * @return bool
	 */
	public function needs_assets(): bool {
		if ( ! $this->is_enabled() ) {
			return false;
		}

		$config = $this->config();
		$needed = ! empty( $config['show_menu_trigger'] ) || $this->requested || $this->request_has_shortcode();

		return (bool) apply_filters( 'creceweb_lumen_lite_search_modal_needs_assets', $needed, $config );
	}

	/** @return int */
	public function style_size_bytes(): int {
		$path = CRECEWEB_LUMEN_LITE_DIR . 'assets/css/search-modal.css';
		return is_file( $path ) ? (int) filesize( $path ) : 0;
	}

	/** @return int */
	public function script_size_bytes(): int {
		$path = CRECEWEB_LUMEN_LITE_DIR . 'assets/js/search-modal.js';
		return is_file( $path ) ? (int) filesize( $path ) : 0;
	}

	/** @return void */
	public function enqueue_assets(): void {
		if ( ! $this->needs_assets() ) {
			return;
		}

		wp_enqueue_style(
			'creceweb-lumen-lite-search-modal',
			CRECEWEB_LUMEN_LITE_URL . 'assets/css/search-modal.css',
			array( 'creceweb-lumen' ),
			CRECEWEB_LUMEN_LITE_ASSET_VERSION
		);
		wp_enqueue_script(
			'creceweb-lumen-lite-search-modal',
			CRECEWEB_LUMEN_LITE_URL . 'assets/js/search-modal.js',
			array(),
			CRECEWEB_LUMEN_LITE_ASSET_VERSION,
			true
		);
	}

	/**
	 * @param array<string,mixed>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode( array|string $atts = array() ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Reserved for future compatible attributes.
		if ( ! $this->is_enabled() ) {
			return '';
		}

		$this->requested = true;
		$this->enqueue_assets();

		return $this->trigger_markup( 'shortcode' );
	}

	/**
	 * Appends the trigger to Lumen's primary navigation.
	 *
	 * @param string $items Existing menu item markup.
	 * @param mixed  $args WordPress menu arguments.
	 * @return string
	 */
	public function filter_primary_menu_items( string $items, mixed $args ): string {
		$location = is_object( $args ) && isset( $args->theme_location ) ? (string) $args->theme_location : '';
		$config   = $this->config();
		if ( 'primary' !== $location || ! $this->is_enabled() || empty( $config['show_menu_trigger'] ) ) {
			return $items;
		}

		$this->requested = true;

		return $items . '<li class="menu-item cw-lumen-search-menu-item">' . $this->trigger_markup( 'menu' ) . '</li>';
	}

	/** @return void */
	public function render_modal(): void {
		if ( $this->rendered || ! $this->needs_assets() ) {
			return;
		}
		$this->rendered = true;
		$config = $this->config();
		$placeholder = isset( $config['placeholder'] ) ? (string) $config['placeholder'] : __( 'Search…', 'creceweb-lumen-lite' );
		?>
		<div id="cw-lumen-search-modal" class="cw-lumen-search-modal" data-cw-search-modal role="dialog" aria-modal="true" aria-labelledby="cw-lumen-search-modal-title" hidden>
			<div class="cw-lumen-search-modal__panel" role="document">
				<div class="cw-lumen-search-modal__head">
					<h2 id="cw-lumen-search-modal-title" class="cw-lumen-search-modal__title"><?php echo esc_html__( 'Search', 'creceweb-lumen-lite' ); ?></h2>
					<button type="button" class="cw-lumen-search-modal__close" data-cw-search-close aria-label="<?php echo esc_attr__( 'Close search', 'creceweb-lumen-lite' ); ?>">
						<span aria-hidden="true">&times;</span>
						<span class="screen-reader-text"><?php echo esc_html__( 'Close search', 'creceweb-lumen-lite' ); ?></span>
					</button>
				</div>
				<form class="cw-lumen-search-modal__form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label class="cw-lumen-search-modal__label" for="cw-lumen-search-field"><?php echo esc_html__( 'Search the site', 'creceweb-lumen-lite' ); ?></label>
					<div class="cw-lumen-search-modal__field-row">
						<input id="cw-lumen-search-field" class="cw-lumen-search-modal__input" data-cw-search-input type="search" name="s" value="" placeholder="<?php echo esc_attr( $placeholder ); ?>" autocomplete="off">
						<button class="cw-lumen-search-modal__submit" type="submit"><?php echo esc_html__( 'Search', 'creceweb-lumen-lite' ); ?></button>
					</div>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Detect shortcode placement before frontend assets are printed.
	 *
	 * Elementor Free stores Shortcode widget settings in _elementor_data, so
	 * inspect that local post meta only when regular post content has no match.
	 *
	 * @return bool
	 */
	private function request_has_shortcode(): bool {
		if ( is_admin() || ! function_exists( 'get_queried_object' ) || ! function_exists( 'has_shortcode' ) ) {
			return false;
		}

		$post = get_queried_object();
		if ( ! is_object( $post ) ) {
			return false;
		}

		$content = (string) ( $post->post_content ?? '' );
		if ( '' !== $content && has_shortcode( $content, 'lumen_search' ) ) {
			return true;
		}

		$post_id = isset( $post->ID ) ? absint( $post->ID ) : 0;
		if ( 0 === $post_id || ! function_exists( 'get_post_meta' ) ) {
			return false;
		}

		$elementor_data = get_post_meta( $post_id, '_elementor_data', true );
		return is_string( $elementor_data )
			&& '' !== $elementor_data
			&& has_shortcode( $elementor_data, 'lumen_search' );
	}

	/** @return string */
	private function trigger_markup( string $placement ): string {
		$classes = array( 'cw-lumen-search-trigger', 'cw-lumen-search-trigger--' . sanitize_html_class( $placement ) );
		$icon = '<svg class="cw-lumen-search-trigger__icon" aria-hidden="true" viewBox="0 0 24 24" focusable="false"><circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
		$visible_label = 'shortcode' === $placement ? '<span class="cw-lumen-search-trigger__label">' . esc_html__( 'Search', 'creceweb-lumen-lite' ) . '</span>' : '';
		$screen_reader_label = 'menu' === $placement ? '<span class="screen-reader-text">' . esc_html__( 'Search', 'creceweb-lumen-lite' ) . '</span>' : '';

		return '<button type="button" class="' . esc_attr( implode( ' ', $classes ) ) . '" data-cw-search-trigger aria-haspopup="dialog" aria-controls="cw-lumen-search-modal" aria-expanded="false">' . $icon . $visible_label . $screen_reader_label . '</button>';
	}

	/** @return void */
	public function handle_save(): void {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'Your user account does not have permission to save these changes.', 'creceweb-lumen-lite' ) );
		}
		check_admin_referer( self::ACTION_SAVE );

		$return_url = isset( $_POST[ AdminRedirect::FIELD ] )
			? esc_url_raw( wp_unslash( (string) $_POST[ AdminRedirect::FIELD ] ) )
			: ( isset( $_POST['_wp_http_referer'] ) ? esc_url_raw( wp_unslash( (string) $_POST['_wp_http_referer'] ) ) : '' );
		$posted = isset( $_POST['search_modal'] ) && is_array( $_POST['search_modal'] )
			? wp_unslash( $_POST['search_modal'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Settings sanitizes every field before persistence.
			: array();

		Settings::update_search_modal(
			array(
				'enabled'           => ! empty( $posted['enabled'] ),
				'show_menu_trigger' => ! empty( $posted['show_menu_trigger'] ),
				'placeholder'       => (string) ( $posted['placeholder'] ?? '' ),
			)
		);

		$url = AdminRedirect::target( $return_url, admin_url( 'themes.php' ), 'search_modal_saved' );
		wp_safe_redirect( esc_url_raw( $url ) );
		exit;
	}
}
