<?php
/**
 * Lumen Lite administration registered through the Theme-owned bridge.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Admin;

use CreceWeb\LumenLite\Content\Controller as ContentController;
use CreceWeb\LumenLite\Footer\Controller as FooterController;
use CreceWeb\LumenLite\FloatingAction\Controller as FloatingActionController;
use CreceWeb\LumenLite\Performance\MvpController;
use CreceWeb\LumenLite\ReadingProgress\Controller as ReadingProgressController;
use CreceWeb\LumenLite\RelatedContent\Controller as RelatedContentController;
use CreceWeb\LumenLite\Sharing\Controller as SharingController;
use CreceWeb\LumenLite\Support\AdminRedirect;
use CreceWeb\LumenLite\Support\Compatibility;
use CreceWeb\LumenLite\TableOfContents\Controller as TableOfContentsController;
use CreceWeb\LumenLite\Messaging\Controller as MessagingController;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds Lite sections to Lumen Theme and keeps a safe fallback when needed.
 */
final class Admin {
	public const FALLBACK_PAGE_SLUG = 'creceweb-lumen-lite';
	private string $fallback_hook = '';

	public function __construct(
		private Compatibility $compatibility,
		private FooterController $footer,
		private ContentController $content,
		private ReadingProgressController $reading_progress,
		private TableOfContentsController $table_of_contents,
		private SharingController $sharing,
		private RelatedContentController $related_content,
		private FloatingActionController $floating_action,
		private MessagingController $messaging,
		private MvpController $performance
	) {}

	/** @return void */
	public function register(): void {
		add_filter( 'creceweb_lumen_admin_sections', array( $this, 'register_theme_sections' ) );
		add_action( 'admin_menu', array( $this, 'register_fallback_page' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . CRECEWEB_LUMEN_LITE_BASENAME, array( $this, 'plugin_action_links' ) );
		add_filter( 'creceweb_lumen_lite_admin_section_url', array( $this, 'filter_section_url' ), 10, 3 );
	}

	/**
	 * @param array<string,array<string,mixed>> $sections Existing sections.
	 * @return array<string,array<string,mixed>>
	 */
	public function register_theme_sections( array $sections ): array {
		$this->compatibility->validate();
		if ( ! $this->compatibility->is_compatible() ) {
			return $sections;
		}

		$sections['lite-menu'] = array(
			'label'      => __( 'Menu', 'creceweb-lumen-lite' ),
			'callback'   => array( $this, 'render_theme_section' ),
			'capability' => 'edit_theme_options',
			'priority'   => 40,
			'owner'      => 'lite',
		);
		$sections['lite-content'] = array(
			'label'      => __( 'Content and blog', 'creceweb-lumen-lite' ),
			'callback'   => array( $this, 'render_theme_section' ),
			'capability' => 'edit_theme_options',
			'priority'   => 45,
			'owner'      => 'lite',
		);
		$sections['lite-floating-action'] = array(
			'label'      => __( 'Floating action', 'creceweb-lumen-lite' ),
			'callback'   => array( $this, 'render_theme_section' ),
			'capability' => 'edit_theme_options',
			'priority'   => 46,
			'owner'      => 'lite',
		);
		$sections['lite-messaging'] = array(
			'label'      => __( 'Messaging', 'creceweb-lumen-lite' ),
			'callback'   => array( $this, 'render_theme_section' ),
			'capability' => 'edit_theme_options',
			'priority'   => 47,
			'owner'      => 'lite',
		);
		$sections['lite-footer'] = array(
			'label'      => __( 'Footer', 'creceweb-lumen-lite' ),
			'callback'   => array( $this, 'render_theme_section' ),
			'capability' => 'edit_theme_options',
			'priority'   => 50,
			'owner'      => 'lite',
		);
		$sections['lite-performance'] = array(
			'label'      => __( 'Performance', 'creceweb-lumen-lite' ),
			'callback'   => array( $this, 'render_theme_section' ),
			'capability' => 'edit_theme_options',
			'priority'   => 55,
			'owner'      => 'lite',
		);

		return $sections;
	}

	/** @return void */
	public function register_fallback_page(): void {
		$this->compatibility->validate();
		if ( $this->compatibility->is_compatible() && function_exists( '\\CreceWeb\\Lumen\\get_admin_section_url' ) ) {
			return;
		}

		$this->fallback_hook = (string) add_theme_page(
			__( 'Lumen Lite', 'creceweb-lumen-lite' ),
			__( 'Lumen Lite', 'creceweb-lumen-lite' ),
			'edit_theme_options',
			self::FALLBACK_PAGE_SLUG,
			array( $this, 'render_fallback_page' )
		);
	}

	/**
	 * @param string $hook_suffix Current admin screen.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'appearance_page_creceweb-lumen' !== $hook_suffix && $this->fallback_hook !== $hook_suffix && 'nav-menus.php' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'creceweb-lumen-lite-admin', CRECEWEB_LUMEN_LITE_URL . 'assets/css/admin.css', array(), CRECEWEB_LUMEN_LITE_ASSET_VERSION );

		$requested_section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only section detection.
		$requested_tab     = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only fallback tab detection.
		if ( 'lite-content' === $requested_section || 'lite-content' === $requested_tab ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script(
				'creceweb-lumen-lite-content-admin',
				CRECEWEB_LUMEN_LITE_URL . 'assets/js/content-admin.js',
				array(),
				CRECEWEB_LUMEN_LITE_ASSET_VERSION,
				true
			);
			wp_enqueue_script(
				'creceweb-lumen-lite-reading-progress-admin',
				CRECEWEB_LUMEN_LITE_URL . 'assets/js/reading-progress-admin.js',
				array(),
				CRECEWEB_LUMEN_LITE_ASSET_VERSION,
				true
			);
			wp_enqueue_script(
				'creceweb-lumen-lite-table-of-contents-admin',
				CRECEWEB_LUMEN_LITE_URL . 'assets/js/table-of-contents-admin.js',
				array( 'jquery', 'wp-color-picker' ),
				CRECEWEB_LUMEN_LITE_ASSET_VERSION,
				true
			);
			wp_enqueue_script(
				'creceweb-lumen-lite-sharing-admin',
				CRECEWEB_LUMEN_LITE_URL . 'assets/js/sharing-admin.js',
				array( 'jquery', 'wp-color-picker' ),
				CRECEWEB_LUMEN_LITE_ASSET_VERSION,
				true
			);
		}

		if ( 'lite-floating-action' === $requested_section || 'lite-floating-action' === $requested_tab ) {
			wp_enqueue_script(
				'creceweb-lumen-lite-floating-action-admin',
				CRECEWEB_LUMEN_LITE_URL . 'assets/js/floating-action-admin.js',
				array(),
				CRECEWEB_LUMEN_LITE_ASSET_VERSION,
				true
			);
		}

		if ( in_array( $requested_section, array( 'lite-messaging', 'lite-whatsapp' ), true ) || in_array( $requested_tab, array( 'lite-messaging', 'lite-whatsapp' ), true ) ) {
			wp_enqueue_script(
				'creceweb-lumen-lite-messaging-admin',
				CRECEWEB_LUMEN_LITE_URL . 'assets/js/messaging-admin.js',
				array(),
				CRECEWEB_LUMEN_LITE_ASSET_VERSION,
				true
			);
		}

		if ( MvpController::SECTION === $requested_section || MvpController::SECTION === $requested_tab ) {
			wp_enqueue_style(
				'creceweb-lumen-lite-performance-admin',
				CRECEWEB_LUMEN_LITE_URL . 'assets/css/performance-admin.css',
				array( 'creceweb-lumen-lite-admin' ),
				CRECEWEB_LUMEN_LITE_ASSET_VERSION
			);
			wp_enqueue_script(
				'creceweb-lumen-lite-performance-admin',
				CRECEWEB_LUMEN_LITE_URL . 'assets/js/performance-admin.js',
				array(),
				CRECEWEB_LUMEN_LITE_ASSET_VERSION,
				true
			);
			wp_localize_script(
				'creceweb-lumen-lite-performance-admin',
				'cwLumenPerformanceMvp',
				$this->performance->ajax_settings()
			);
		}
	}

	/**
	 * @param array<int,string> $links Existing plugin links.
	 * @return array<int,string>
	 */
	public function plugin_action_links( array $links ): array {
		array_unshift( $links, '<a href="' . esc_url( $this->section_url( 'lite-menu' ) ) . '">' . esc_html__( 'Configure', 'creceweb-lumen-lite' ) . '</a>' );
		return $links;
	}

	/**
	 * @param string $url Default URL.
	 * @param string $section Section identifier.
	 * @param string $notice Notice identifier.
	 * @return string
	 */
	public function filter_section_url( string $url, string $section, string $notice = '' ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Public URL filter.
		return $this->section_url( $section, $notice );
	}

	/**
	 * Theme bridge callback.
	 *
	 * @param string $section Requested section.
	 * @return void
	 */
	public function render_theme_section( string $section ): void {
		echo '<div class="cw-lumen-admin cw-lumen-admin--embedded">';
		$this->render_section( $section, false );
		echo '</div>';
	}

	/** @return void */
	public function render_fallback_page(): void {
		$requested = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'home'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation.
		?>
		<div class="wrap cw-lumen-admin">
			<?php $this->render_header(); ?>
			<?php $this->render_fallback_navigation( $requested ); ?>
			<main class="cw-lumen-admin__content">
				<?php $this->render_section( $requested, true ); ?>
			</main>
		</div>
		<?php
	}

	/**
	 * @param string $section Requested section.
	 * @param bool   $fallback Standalone fallback.
	 * @return void
	 */
	private function render_section( string $section, bool $fallback ): void {
		if ( 'lite-whatsapp' === $section ) {
			$section = 'lite-messaging';
		}

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'Your user account does not have permission to view these options.', 'creceweb-lumen-lite' ) );
		}

		$allowed = $fallback
			? array( 'home', 'lite-menu', 'lite-content', 'lite-floating-action', 'lite-messaging', 'lite-footer', 'lite-performance' )
			: array( 'lite-menu', 'lite-content', 'lite-floating-action', 'lite-messaging', 'lite-footer', 'lite-performance' );
		$active = in_array( $section, $allowed, true ) ? $section : ( $fallback ? 'home' : 'lite-menu' );

		if ( ! $fallback ) {
			$this->render_section_intro( $active );
		}
		$this->render_notice();

		if ( 'home' === $active ) {
			$this->render_home();
		} elseif ( 'lite-menu' === $active ) {
			$this->render_menu();
		} elseif ( 'lite-content' === $active ) {
			$this->render_content();
		} elseif ( 'lite-floating-action' === $active ) {
			$this->render_floating_action();
		} elseif ( 'lite-messaging' === $active ) {
			$this->render_messaging();
		} elseif ( 'lite-footer' === $active ) {
			$this->render_footer();
		} elseif ( 'lite-performance' === $active ) {
			$this->performance->render();
		} else {
			$this->render_footer();
		}
	}

	/** @return void */
	private function render_header(): void {
		$this->render_product_header();
	}

	/** @return void */
	private function render_product_header(): void {
		?>
		<header class="cw-lumen-admin__product-hero">
			<div>
				<p class="cw-lumen-admin__brand-version">
					<span>
						<?php
						/* translators: %s: Current Lumen Lite version number. */
						echo esc_html( sprintf( __( 'Version %s', 'creceweb-lumen-lite' ), CRECEWEB_LUMEN_LITE_VERSION ) );
						?>
					</span>
				</p>
				<h1 class="cw-lumen-admin__brand-title">
					<?php echo esc_html__( 'Lumen Lite', 'creceweb-lumen-lite' ); ?>
					<span><?php echo esc_html__( 'by', 'creceweb-lumen-lite' ); ?> <a href="https://creceweb.com.ar/" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'CreceWeb.com.ar', 'creceweb-lumen-lite' ); ?></a></span>
				</h1>
				<p class="cw-lumen-admin__product-copy"><?php echo esc_html__( 'Patterns and tools for Lumen Theme in one place.', 'creceweb-lumen-lite' ); ?></p>
			</div>
			<p class="cw-lumen-admin__product-actions"><a class="button button-primary button-hero" href="<?php echo esc_url( cw_lumen_lite_library_url() ); ?>"><?php echo esc_html__( 'Open Lumen Library', 'creceweb-lumen-lite' ); ?></a></p>
		</header>
		<?php
	}

	/**
	 * @param string $active Active fallback tab.
	 * @return void
	 */
	private function render_fallback_navigation( string $active ): void {
		$tabs = array(
			'home'               => __( 'Home', 'creceweb-lumen-lite' ),
			'lite-menu'   => __( 'Menu', 'creceweb-lumen-lite' ),
			'lite-content' => __( 'Content and blog', 'creceweb-lumen-lite' ),
			'lite-floating-action' => __( 'Floating action', 'creceweb-lumen-lite' ),
			'lite-messaging' => __( 'Messaging', 'creceweb-lumen-lite' ),
			'lite-footer'      => __( 'Footer', 'creceweb-lumen-lite' ),
			'lite-performance' => __( 'Performance', 'creceweb-lumen-lite' ),
		);
		?>
		<nav class="nav-tab-wrapper cw-lumen-admin__tabs" aria-label="<?php echo esc_attr__( 'Lumen Lite options', 'creceweb-lumen-lite' ); ?>">
			<?php foreach ( $tabs as $id => $label ) : ?>
				<a class="nav-tab<?php echo $active === $id ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( $this->section_url( $id ) ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/**
	 * @param string $section Active section.
	 * @return void
	 */
	private function render_section_intro( string $section ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Shared product header for every Lite-owned section.
		$this->render_product_header();
	}

	/** @return void */
	private function render_notice(): void {
		$notice = isset( $_GET['cw_lumen_lite_notice'] ) ? sanitize_key( wp_unslash( $_GET['cw_lumen_lite_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice.
		$messages = array(
			'content_saved'               => array( 'success', __( 'Content and blog settings were saved successfully.', 'creceweb-lumen-lite' ) ),
			'floating_action_saved'       => array( 'success', __( 'The floating action settings were saved successfully.', 'creceweb-lumen-lite' ) ),
			'messaging_saved'             => array( 'success', __( 'The global messaging configuration was saved successfully.', 'creceweb-lumen-lite' ) ),
			'whatsapp_saved'              => array( 'success', __( 'The global messaging configuration was saved successfully.', 'creceweb-lumen-lite' ) ),
			'footer_saved'                => array( 'success', __( 'The footer text was saved successfully.', 'creceweb-lumen-lite' ) ),
			'footer_reset'                => array( 'success', __( 'The original footer text was restored.', 'creceweb-lumen-lite' ) ),
		);
		if ( isset( $messages[ $notice ] ) ) {
			$type = in_array( $messages[ $notice ][0], array( 'success', 'warning', 'error', 'info' ), true ) ? $messages[ $notice ][0] : 'info';
			echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $messages[ $notice ][1] ) . '</p></div>';
		}
	}

	/** @return void */
	private function render_home(): void {
		?>
		<section class="cw-lumen-admin__section">
			<div class="cw-lumen-admin__intro">
				<p class="cw-lumen-admin__eyebrow"><?php echo esc_html__( 'Lumen Lite', 'creceweb-lumen-lite' ); ?></p>
				<h2><?php echo esc_html__( 'Simple tools to complete your site', 'creceweb-lumen-lite' ); ?></h2>
				<p><?php echo esc_html__( 'Open the Lumen Library from the header and configure the menu, content, floating action, messaging, and footer from these options.', 'creceweb-lumen-lite' ); ?></p>
			</div>
			<div class="cw-lumen-admin__grid">
				<article class="cw-lumen-admin__card"><h3><?php echo esc_html__( 'Menu links', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Highlight any menu links you want and decide on each link whether it appears on desktop, tablets, or mobile.', 'creceweb-lumen-lite' ); ?></p><a href="<?php echo esc_url( $this->section_url( 'lite-menu' ) ); ?>"><?php echo esc_html__( 'Configure menu', 'creceweb-lumen-lite' ); ?></a></article>
				<article class="cw-lumen-admin__card"><h3><?php echo esc_html__( 'Content and blog', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Show estimated reading time, add Reading Progress, a Table of Contents, Sharing and Related Content, and choose how long excerpts should be.', 'creceweb-lumen-lite' ); ?></p><a href="<?php echo esc_url( $this->section_url( 'lite-content' ) ); ?>"><?php echo esc_html__( 'Configure content', 'creceweb-lumen-lite' ); ?></a></article>
				<article class="cw-lumen-admin__card"><h3><?php echo esc_html__( 'Floating action', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Create a global section/URL action with its own content, icon, colors, size, position, and visibility.', 'creceweb-lumen-lite' ); ?></p><a href="<?php echo esc_url( $this->section_url( 'lite-floating-action' ) ); ?>"><?php echo esc_html__( 'Configure floating action', 'creceweb-lumen-lite' ); ?></a></article>
				<article class="cw-lumen-admin__card"><h3><?php echo esc_html__( 'Global messaging', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Choose WhatsApp, Telegram, Messenger, or Signal and configure one global contact action.', 'creceweb-lumen-lite' ); ?></p><a href="<?php echo esc_url( $this->section_url( 'lite-messaging' ) ); ?>"><?php echo esc_html__( 'Configure messaging', 'creceweb-lumen-lite' ); ?></a></article>
				<article class="cw-lumen-admin__card"><h3><?php echo esc_html__( 'Footer', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Change the site\'s closing text while keeping the year and site name updated automatically.', 'creceweb-lumen-lite' ); ?></p><a href="<?php echo esc_url( $this->section_url( 'lite-footer' ) ); ?>"><?php echo esc_html__( 'Configure footer', 'creceweb-lumen-lite' ); ?></a></article>
				<article class="cw-lumen-admin__card"><h3><?php echo esc_html__( 'Lumen Library', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Search, preview, and insert patterns from a single visual Library.', 'creceweb-lumen-lite' ); ?></p><a href="<?php echo esc_url( cw_lumen_lite_library_url() ); ?>"><?php echo esc_html__( 'Open Lumen Library', 'creceweb-lumen-lite' ); ?></a></article>
				<article class="cw-lumen-admin__card"><h3><?php echo esc_html__( 'Lumen Performance', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Analyze a public URL and review the Theme and Lite resources that Lumen loads there.', 'creceweb-lumen-lite' ); ?></p><a href="<?php echo esc_url( $this->section_url( 'lite-performance' ) ); ?>"><?php echo esc_html__( 'Open Performance', 'creceweb-lumen-lite' ); ?></a></article>
			</div>
		</section>
		<?php if ( ! $this->compatibility->is_compatible() ) : ?>
			<?php $this->render_status(); ?>
		<?php endif; ?>
		<?php
	}

	/** @return void */
	private function render_menu(): void {
		?>
		<section class="cw-lumen-admin__section">
			<div class="cw-lumen-admin__intro">
				<h2><?php echo esc_html__( 'Options for each link', 'creceweb-lumen-lite' ); ?></h2>
				<p><?php echo esc_html__( 'Open a link inside the menu to find the Lumen Lite options.', 'creceweb-lumen-lite' ); ?></p>
			</div>
			<div class="cw-lumen-admin__grid">
				<article class="cw-lumen-admin__card">
					<h3><?php echo esc_html__( 'Highlighted links', 'creceweb-lumen-lite' ); ?></h3>
					<p><?php echo esc_html__( 'Highlight any menu links you want as buttons, including submenu links.', 'creceweb-lumen-lite' ); ?></p>
				</article>
				<article class="cw-lumen-admin__card">
					<h3><?php echo esc_html__( 'Visibility by device', 'creceweb-lumen-lite' ); ?></h3>
					<p><?php echo esc_html__( 'Use the same selection on any number of links and combine desktop, tablet, and mobile independently.', 'creceweb-lumen-lite' ); ?></p>
				</article>
			</div>
			<p class="cw-lumen-admin__actions"><a class="button button-primary" href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>"><?php echo esc_html__( 'Open Menus', 'creceweb-lumen-lite' ); ?></a></p>
		</section>
		<?php
	}

	/** @return void */
	private function render_content(): void {
		$settings = $this->content->settings();
		$reading_progress = $this->reading_progress->settings();
		$reading_progress_devices = isset( $reading_progress['devices'] ) && is_array( $reading_progress['devices'] ) ? $reading_progress['devices'] : array();
		$reading_progress_types = $this->reading_progress->available_post_types();
		$reading_progress_selected = isset( $reading_progress['post_types'] ) && is_array( $reading_progress['post_types'] ) ? $reading_progress['post_types'] : array();
		$table_of_contents = $this->table_of_contents->settings();
		$toc_types = $this->table_of_contents->available_post_types();
		$toc_selected = isset( $table_of_contents['post_types'] ) && is_array( $table_of_contents['post_types'] ) ? $table_of_contents['post_types'] : array();
		$toc_levels = isset( $table_of_contents['heading_levels'] ) && is_array( $table_of_contents['heading_levels'] ) ? $table_of_contents['heading_levels'] : array();
		$sharing = $this->sharing->settings();
		$sharing_types = $this->sharing->available_post_types();
		$sharing_selected = isset( $sharing['post_types'] ) && is_array( $sharing['post_types'] ) ? $sharing['post_types'] : array();
		$sharing_actions = isset( $sharing['actions'] ) && is_array( $sharing['actions'] ) ? $sharing['actions'] : array();
		$related_content = $this->related_content->settings();
		$related_types = $this->related_content->available_post_types();
		$related_selected = isset( $related_content['post_types'] ) && is_array( $related_content['post_types'] ) ? $related_content['post_types'] : array();
		$related_ratio_width = is_numeric( $related_content['image_ratio_width'] ?? null ) ? (float) $related_content['image_ratio_width'] : 16.0;
		$related_ratio_height = is_numeric( $related_content['image_ratio_height'] ?? null ) ? (float) $related_content['image_ratio_height'] : 9.0;
		$related_ratio_presets = array( '16:9' => array( 16.0, 9.0 ), '4:3' => array( 4.0, 3.0 ), '3:2' => array( 3.0, 2.0 ), '1:1' => array( 1.0, 1.0 ), '9:16' => array( 9.0, 16.0 ) );
		$related_ratio_preset = 'custom';
		if ( $related_ratio_width > 0 && $related_ratio_height > 0 ) {
			$current_ratio = $related_ratio_width / $related_ratio_height;
			foreach ( $related_ratio_presets as $preset_key => $preset_dimensions ) {
				if ( abs( $current_ratio - ( $preset_dimensions[0] / $preset_dimensions[1] ) ) < 0.000001 ) { $related_ratio_preset = $preset_key; break; }
			}
		}
		$content_views = array( 'general', 'reading-progress', 'table-of-contents', 'sharing', 'related-content' );
		$requested_content_view = isset( $_GET['content_view'] ) ? sanitize_key( wp_unslash( $_GET['content_view'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only internal view selection.
		$active_content_view = in_array( $requested_content_view, $content_views, true ) ? $requested_content_view : 'general';
		$content_return_url = add_query_arg( 'content_view', $active_content_view, $this->section_url( 'lite-content' ) );
		$content_tabs = array(
			'general'           => __( 'General', 'creceweb-lumen-lite' ),
			'reading-progress'  => __( 'Reading progress', 'creceweb-lumen-lite' ),
			'table-of-contents' => __( 'Table of contents', 'creceweb-lumen-lite' ),
			'sharing'           => __( 'Sharing', 'creceweb-lumen-lite' ),
			'related-content'   => __( 'Related content', 'creceweb-lumen-lite' ),
		);
		?>
		<section class="cw-lumen-admin__section">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cw_lumen_lite_save_content">
				<input type="hidden" name="<?php echo esc_attr( AdminRedirect::FIELD ); ?>" value="<?php echo esc_url( $content_return_url ); ?>" data-cw-content-return-url>
				<input type="hidden" name="cw_lumen_lite_content_view" value="<?php echo esc_attr( $active_content_view ); ?>" data-cw-content-view-input>
				<?php wp_nonce_field( 'cw_lumen_lite_save_content' ); ?>
				<div class="cw-lumen-content-tabs-shell" data-cw-content-tabs data-cw-content-active-view="<?php echo esc_attr( $active_content_view ); ?>">
					<nav class="nav-tab-wrapper cw-lumen-content-tabs" aria-label="<?php echo esc_attr__( 'Content and blog', 'creceweb-lumen-lite' ); ?>" role="tablist">
						<?php foreach ( $content_tabs as $view => $label ) : ?>
							<?php
							$tab_id   = 'cw-lumen-content-tab-' . $view;
							$panel_id = 'cw-lumen-content-panel-' . $view;
							$tab_url  = add_query_arg( 'content_view', $view, $this->section_url( 'lite-content' ) );
							?>
							<a id="<?php echo esc_attr( $tab_id ); ?>" class="nav-tab<?php echo $active_content_view === $view ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( $tab_url ); ?>" data-cw-content-tab="<?php echo esc_attr( $view ); ?>" role="tab" aria-controls="<?php echo esc_attr( $panel_id ); ?>" aria-selected="<?php echo $active_content_view === $view ? 'true' : 'false'; ?>" tabindex="<?php echo $active_content_view === $view ? '0' : '-1'; ?>"><?php echo esc_html( $label ); ?></a>
						<?php endforeach; ?>
					</nav>

					<div id="cw-lumen-content-panel-general" class="cw-lumen-content-panel" data-cw-content-panel="general" role="tabpanel" aria-labelledby="cw-lumen-content-tab-general"<?php if ( 'general' !== $active_content_view ) : ?> hidden<?php endif; ?>>
						<div class="cw-lumen-admin__grid">
					<article class="cw-lumen-admin__card cw-lumen-content-card--full">
						<h2><?php echo esc_html__( 'Estimated reading time', 'creceweb-lumen-lite' ); ?></h2>
						<p><?php echo esc_html__( 'Show a simple estimate on individual posts and supported blog listings, including latest posts, categories, tags, archives, and search results.', 'creceweb-lumen-lite' ); ?></p>
						<label>
							<input type="checkbox" name="reading_time_enabled" value="1" <?php checked( ! empty( $settings['reading_time_enabled'] ) ); ?>>
							<?php echo esc_html__( 'Show estimated reading time', 'creceweb-lumen-lite' ); ?>
						</label>
					</article>
					<article class="cw-lumen-admin__card cw-lumen-content-card--full">
						<h2><?php echo esc_html__( 'Excerpt length', 'creceweb-lumen-lite' ); ?></h2>
						<p><?php echo esc_html__( 'Lumen Theme continues deciding where excerpts appear. Lite limits visible text without changing the excerpt stored in the post.', 'creceweb-lumen-lite' ); ?></p>
						<p><label>
							<input type="checkbox" name="excerpt_length_enabled" value="1" <?php checked( ! empty( $settings['excerpt_length_enabled'] ) ); ?>>
							<?php echo esc_html__( 'Use a custom length', 'creceweb-lumen-lite' ); ?>
						</label></p>
						<label for="cw-lumen-lite-excerpt-length"><?php echo esc_html__( 'Approximate word count', 'creceweb-lumen-lite' ); ?></label>
						<input id="cw-lumen-lite-excerpt-length" type="number" min="1" step="1" name="excerpt_length" value="<?php echo esc_attr( (string) max( 1, absint( $settings['excerpt_length'] ) ) ); ?>">
						</article>
						</div>
					</div>

					<div id="cw-lumen-content-panel-reading-progress" class="cw-lumen-content-panel" data-cw-content-panel="reading-progress" role="tabpanel" aria-labelledby="cw-lumen-content-tab-reading-progress"<?php if ( 'reading-progress' !== $active_content_view ) : ?> hidden<?php endif; ?>>
						<div class="cw-lumen-admin__grid">
							<article class="cw-lumen-admin__card cw-lumen-admin__card--wide cw-lumen-reading-progress-settings" data-cw-reading-progress-settings>
						<div class="cw-lumen-reading-progress-heading">
							<div>
								<p class="cw-lumen-admin__eyebrow"><?php echo esc_html__( 'Content tool', 'creceweb-lumen-lite' ); ?></p>
								<h2><?php echo esc_html__( 'Reading progress', 'creceweb-lumen-lite' ); ?></h2>
								<p><?php echo esc_html__( 'Show a clear page-reading progress bar on individual content. Every viewable public post type registered on the site is available below.', 'creceweb-lumen-lite' ); ?></p>
							</div>
							<label class="cw-lumen-reading-progress-enabled"><input type="checkbox" name="reading_progress[enabled]" value="1" <?php checked( ! empty( $reading_progress['enabled'] ) ); ?>> <span><?php echo esc_html__( 'Enable Reading Progress', 'creceweb-lumen-lite' ); ?></span></label>
						</div>
						<div class="cw-lumen-reading-progress-layout">
							<div class="cw-lumen-reading-progress-controls">
								<section class="cw-lumen-reading-progress-group" aria-labelledby="cw-lumen-reading-progress-appearance">
									<div class="cw-lumen-reading-progress-group__heading">
										<h3 id="cw-lumen-reading-progress-appearance"><?php echo esc_html__( 'Appearance', 'creceweb-lumen-lite' ); ?></h3>
										<p><?php echo esc_html__( 'Choose where the bar appears and how strongly it stands out from the page.', 'creceweb-lumen-lite' ); ?></p>
									</div>
									<div class="cw-lumen-reading-progress-appearance-grid">
										<p><label class="cw-lumen-admin__label" for="cw-lumen-reading-progress-position"><?php echo esc_html__( 'Position', 'creceweb-lumen-lite' ); ?></label><select id="cw-lumen-reading-progress-position" name="reading_progress[position]"><option value="top" <?php selected( (string) ( $reading_progress['position'] ?? 'top' ), 'top' ); ?>><?php echo esc_html__( 'Top of window', 'creceweb-lumen-lite' ); ?></option><option value="bottom" <?php selected( (string) ( $reading_progress['position'] ?? 'top' ), 'bottom' ); ?>><?php echo esc_html__( 'Bottom of window', 'creceweb-lumen-lite' ); ?></option></select></p>
										<p><label class="cw-lumen-admin__label" for="cw-lumen-reading-progress-thickness"><?php echo esc_html__( 'Thickness', 'creceweb-lumen-lite' ); ?></label><span class="cw-lumen-reading-progress-number"><input id="cw-lumen-reading-progress-thickness" type="number" min="0" step="0.1" name="reading_progress[thickness]" value="<?php echo esc_attr( (string) ( is_numeric( $reading_progress['thickness'] ?? null ) ? max( 0, (float) $reading_progress['thickness'] ) : 4 ) ); ?>"><span>px</span></span><span class="description"><?php echo esc_html__( 'Enter any non-negative pixel value.', 'creceweb-lumen-lite' ); ?></span></p>
										<div class="cw-lumen-reading-progress-color"><label class="cw-lumen-admin__label" for="cw-lumen-reading-progress-color"><?php echo esc_html__( 'Progress color', 'creceweb-lumen-lite' ); ?></label><div><input type="color" value="<?php echo esc_attr( (string) ( $reading_progress['color'] ?? '#10b981' ) ); ?>" data-cw-reading-progress-color-picker><input id="cw-lumen-reading-progress-color" type="text" maxlength="7" pattern="#[0-9A-Fa-f]{6}" name="reading_progress[color]" value="<?php echo esc_attr( (string) ( $reading_progress['color'] ?? '#10b981' ) ); ?>"></div></div>
										<div class="cw-lumen-reading-progress-color"><label class="cw-lumen-admin__label" for="cw-lumen-reading-progress-track-color"><?php echo esc_html__( 'Track color', 'creceweb-lumen-lite' ); ?></label><div><input type="color" value="<?php echo esc_attr( (string) ( $reading_progress['track_color'] ?? '#e2e8f0' ) ); ?>" data-cw-reading-progress-track-color-picker><input id="cw-lumen-reading-progress-track-color" type="text" maxlength="7" pattern="#[0-9A-Fa-f]{6}" name="reading_progress[track_color]" value="<?php echo esc_attr( (string) ( $reading_progress['track_color'] ?? '#e2e8f0' ) ); ?>"></div><span class="description"><?php echo esc_html__( 'The track stays visible behind the completed portion so progress is easier to distinguish.', 'creceweb-lumen-lite' ); ?></span></div>
									</div>
								</section>

								<section class="cw-lumen-reading-progress-group" aria-labelledby="cw-lumen-reading-progress-content-types">
									<div class="cw-lumen-reading-progress-group__heading">
										<h3 id="cw-lumen-reading-progress-content-types"><?php echo esc_html__( 'Content', 'creceweb-lumen-lite' ); ?></h3>
										<p><?php echo esc_html__( 'Choose every public content type where Reading Progress should appear on singular views.', 'creceweb-lumen-lite' ); ?></p>
									</div>
									<fieldset class="cw-lumen-reading-progress-types"><legend class="screen-reader-text"><?php echo esc_html__( 'Public content types', 'creceweb-lumen-lite' ); ?></legend><div><?php foreach ( $reading_progress_types as $post_type => $label ) : ?><label><input type="checkbox" name="reading_progress[post_types][]" value="<?php echo esc_attr( $post_type ); ?>" <?php checked( in_array( $post_type, $reading_progress_selected, true ) ); ?>><span><?php echo esc_html( $label ); ?><small><?php echo esc_html( $post_type ); ?></small></span></label><?php endforeach; ?></div></fieldset>
								</section>

								<section class="cw-lumen-reading-progress-group" aria-labelledby="cw-lumen-reading-progress-devices-heading">
									<div class="cw-lumen-reading-progress-group__heading">
										<h3 id="cw-lumen-reading-progress-devices-heading"><?php echo esc_html__( 'Devices', 'creceweb-lumen-lite' ); ?></h3>
										<p><?php echo esc_html__( 'Show or hide the bar independently on each device range.', 'creceweb-lumen-lite' ); ?></p>
									</div>
									<fieldset class="cw-lumen-reading-progress-devices"><legend class="screen-reader-text"><?php echo esc_html__( 'Devices', 'creceweb-lumen-lite' ); ?></legend><div><label><input type="checkbox" name="reading_progress[devices][desktop]" value="1" <?php checked( ! empty( $reading_progress_devices['desktop'] ) ); ?>><span><?php echo esc_html__( 'Desktop', 'creceweb-lumen-lite' ); ?></span></label><label><input type="checkbox" name="reading_progress[devices][tablet]" value="1" <?php checked( ! empty( $reading_progress_devices['tablet'] ) ); ?>><span><?php echo esc_html__( 'Tablet', 'creceweb-lumen-lite' ); ?></span></label><label><input type="checkbox" name="reading_progress[devices][mobile]" value="1" <?php checked( ! empty( $reading_progress_devices['mobile'] ) ); ?>><span><?php echo esc_html__( 'Mobile', 'creceweb-lumen-lite' ); ?></span></label></div></fieldset>
								</section>
							</div>
							<aside class="cw-lumen-reading-progress-preview-card">
								<div class="cw-lumen-reading-progress-preview-card__heading"><span><?php echo esc_html__( 'Live preview', 'creceweb-lumen-lite' ); ?></span><output data-cw-reading-progress-preview-value for="cw-lumen-reading-progress-preview-range">64%</output></div>
								<div class="cw-lumen-reading-progress-preview-stage" aria-hidden="true">
									<span class="cw-lumen-reading-progress-preview" data-cw-reading-progress-preview data-position="<?php echo esc_attr( (string) ( $reading_progress['position'] ?? 'top' ) ); ?>" style="--cw-reading-progress-preview-thickness:<?php echo esc_attr( (string) ( is_numeric( $reading_progress['thickness'] ?? null ) ? max( 0, (float) $reading_progress['thickness'] ) : 4 ) ); ?>px;--cw-reading-progress-preview-color:<?php echo esc_attr( (string) ( $reading_progress['color'] ?? '#10b981' ) ); ?>;--cw-reading-progress-preview-track-color:<?php echo esc_attr( (string) ( $reading_progress['track_color'] ?? '#e2e8f0' ) ); ?>;--cw-reading-progress-preview-value:.64"><i></i></span>
									<div class="cw-lumen-reading-progress-preview-page">
										<span class="cw-lumen-reading-progress-preview-kicker"></span>
										<strong></strong>
										<b></b><b></b><b></b><b></b><b></b><b></b>
									</div>
								</div>
								<label class="cw-lumen-reading-progress-preview-range" for="cw-lumen-reading-progress-preview-range"><span><?php echo esc_html__( 'Preview progress', 'creceweb-lumen-lite' ); ?></span><input id="cw-lumen-reading-progress-preview-range" type="range" min="0" max="100" step="1" value="64" data-cw-reading-progress-preview-range></label>
								<p><?php echo esc_html__( 'Move the preview control to compare the completed and remaining portions before saving.', 'creceweb-lumen-lite' ); ?></p>
							</aside>
						</div>
							</article>
						</div>
					</div>

					<div id="cw-lumen-content-panel-table-of-contents" class="cw-lumen-content-panel" data-cw-content-panel="table-of-contents" role="tabpanel" aria-labelledby="cw-lumen-content-tab-table-of-contents"<?php if ( 'table-of-contents' !== $active_content_view ) : ?> hidden<?php endif; ?>>
						<div class="cw-lumen-admin__grid">
							<article class="cw-lumen-admin__card cw-lumen-admin__card--wide cw-lumen-toc-settings">
						<div class="cw-lumen-toc-heading">
							<div>
								<p class="cw-lumen-admin__eyebrow"><?php echo esc_html__( 'Content tool', 'creceweb-lumen-lite' ); ?></p>
								<h2><?php echo esc_html__( 'Table of contents', 'creceweb-lumen-lite' ); ?></h2>
								<p><?php echo esc_html__( 'Build an accessible list of links from headings in the rendered entry or page without changing the content stored in WordPress.', 'creceweb-lumen-lite' ); ?></p>
							</div>
							<label class="cw-lumen-toc-enabled"><input type="checkbox" name="table_of_contents[enabled]" value="1" <?php checked( ! empty( $table_of_contents['enabled'] ) ); ?>> <span><?php echo esc_html__( 'Enable Table of Contents', 'creceweb-lumen-lite' ); ?></span></label>
						</div>
						<div class="cw-lumen-toc-layout">
							<section class="cw-lumen-toc-group">
								<div class="cw-lumen-toc-group__heading"><h3><?php echo esc_html__( 'General', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Choose the label, automatic insertion point and visual treatment.', 'creceweb-lumen-lite' ); ?></p></div>
								<div class="cw-lumen-toc-field-grid">
									<p><label class="cw-lumen-admin__label" for="cw-lumen-toc-title"><?php echo esc_html__( 'Title', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-toc-title" class="regular-text" type="text" name="table_of_contents[title]" value="<?php echo esc_attr( (string) ( $table_of_contents['title'] ?? '' ) ); ?>"><span class="description"><?php echo esc_html__( 'Leave empty to hide the visible title; the navigation keeps an accessible label.', 'creceweb-lumen-lite' ); ?></span></p>
									<p><label class="cw-lumen-admin__label" for="cw-lumen-toc-position"><?php echo esc_html__( 'Position', 'creceweb-lumen-lite' ); ?></label><select id="cw-lumen-toc-position" name="table_of_contents[position]"><option value="before_first_heading" <?php selected( (string) ( $table_of_contents['position'] ?? 'before_first_heading' ), 'before_first_heading' ); ?>><?php echo esc_html__( 'Before the first selected heading', 'creceweb-lumen-lite' ); ?></option><option value="after_first_paragraph" <?php selected( (string) ( $table_of_contents['position'] ?? 'before_first_heading' ), 'after_first_paragraph' ); ?>><?php echo esc_html__( 'After the first paragraph', 'creceweb-lumen-lite' ); ?></option></select></p>
									<p><label class="cw-lumen-admin__label" for="cw-lumen-toc-style"><?php echo esc_html__( 'Style', 'creceweb-lumen-lite' ); ?></label><select id="cw-lumen-toc-style" name="table_of_contents[style]"><option value="boxed" <?php selected( (string) ( $table_of_contents['style'] ?? 'boxed' ), 'boxed' ); ?>><?php echo esc_html__( 'Boxed', 'creceweb-lumen-lite' ); ?></option><option value="plain" <?php selected( (string) ( $table_of_contents['style'] ?? 'boxed' ), 'plain' ); ?>><?php echo esc_html__( 'Plain', 'creceweb-lumen-lite' ); ?></option></select></p>
									<p><label class="cw-lumen-admin__label" for="cw-lumen-toc-minimum"><?php echo esc_html__( 'Minimum headings', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-toc-minimum" type="number" min="1" step="1" name="table_of_contents[minimum_headings]" value="<?php echo esc_attr( (string) max( 1, absint( $table_of_contents['minimum_headings'] ?? 3 ) ) ); ?>"><span class="description"><?php echo esc_html__( 'The Table of Contents stays hidden until the rendered content contains at least this many selected headings. No maximum is imposed.', 'creceweb-lumen-lite' ); ?></span></p>
								</div>
							</section>

							<section class="cw-lumen-toc-group" aria-labelledby="cw-lumen-toc-appearance">
								<div class="cw-lumen-toc-group__heading"><h3 id="cw-lumen-toc-appearance"><?php echo esc_html__( 'Appearance', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Set optional colors for the Table of Contents. Empty fields keep the inherited Lumen or content colors.', 'creceweb-lumen-lite' ); ?></p></div>
								<div class="cw-lumen-toc-color-grid">
									<div class="cw-lumen-toc-color-control"><label class="cw-lumen-admin__label" for="cw-lumen-toc-text-color"><?php echo esc_html__( 'Text color', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-toc-text-color" class="cw-lumen-toc-color-field" type="text" name="table_of_contents[text_color]" value="<?php echo esc_attr( (string) ( $table_of_contents['text_color'] ?? '' ) ); ?>" data-default-color=""><span class="description"><?php echo esc_html__( 'Leave empty to inherit the surrounding content color.', 'creceweb-lumen-lite' ); ?></span></div>
									<div class="cw-lumen-toc-color-control"><label class="cw-lumen-admin__label" for="cw-lumen-toc-link-color"><?php echo esc_html__( 'Link color', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-toc-link-color" class="cw-lumen-toc-color-field" type="text" name="table_of_contents[link_color]" value="<?php echo esc_attr( (string) ( $table_of_contents['link_color'] ?? '' ) ); ?>" data-default-color=""><span class="description"><?php echo esc_html__( 'Leave empty to use the Table of Contents text color.', 'creceweb-lumen-lite' ); ?></span></div>
									<div class="cw-lumen-toc-color-control"><label class="cw-lumen-admin__label" for="cw-lumen-toc-link-hover-color"><?php echo esc_html__( 'Link hover color', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-toc-link-hover-color" class="cw-lumen-toc-color-field" type="text" name="table_of_contents[link_hover_color]" value="<?php echo esc_attr( (string) ( $table_of_contents['link_hover_color'] ?? '' ) ); ?>" data-default-color=""><span class="description"><?php echo esc_html__( 'Leave empty to keep the normal link color on hover and keyboard focus.', 'creceweb-lumen-lite' ); ?></span></div>
									<div class="cw-lumen-toc-color-control"><label class="cw-lumen-admin__label" for="cw-lumen-toc-background-color"><?php echo esc_html__( 'Background color', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-toc-background-color" class="cw-lumen-toc-color-field" type="text" name="table_of_contents[background_color]" value="<?php echo esc_attr( (string) ( $table_of_contents['background_color'] ?? '' ) ); ?>" data-default-color=""><span class="description"><?php echo esc_html__( 'Boxed style only. Leave empty to keep the subtle background derived from the inherited text color.', 'creceweb-lumen-lite' ); ?></span></div>
									<div class="cw-lumen-toc-color-control"><label class="cw-lumen-admin__label" for="cw-lumen-toc-border-color"><?php echo esc_html__( 'Border color', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-toc-border-color" class="cw-lumen-toc-color-field" type="text" name="table_of_contents[border_color]" value="<?php echo esc_attr( (string) ( $table_of_contents['border_color'] ?? '' ) ); ?>" data-default-color=""><span class="description"><?php echo esc_html__( 'Boxed style only. Leave empty to keep the subtle border derived from the inherited text color.', 'creceweb-lumen-lite' ); ?></span></div>
								</div>
							</section>

							<section class="cw-lumen-toc-group">
								<div class="cw-lumen-toc-group__heading"><h3><?php echo esc_html__( 'Heading levels', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Include any combination from H2 through H6. Lumen Lite does not impose a heading-count limit.', 'creceweb-lumen-lite' ); ?></p></div>
								<fieldset class="cw-lumen-toc-levels"><legend class="screen-reader-text"><?php echo esc_html__( 'Heading levels', 'creceweb-lumen-lite' ); ?></legend><div><?php foreach ( array( 'h2', 'h3', 'h4', 'h5', 'h6' ) as $level ) : ?><label><input type="checkbox" name="table_of_contents[heading_levels][]" value="<?php echo esc_attr( $level ); ?>" <?php checked( in_array( $level, $toc_levels, true ) ); ?>><span><?php echo esc_html( strtoupper( $level ) ); ?></span></label><?php endforeach; ?></div></fieldset>
							</section>

							<section class="cw-lumen-toc-group">
								<div class="cw-lumen-toc-group__heading"><h3><?php echo esc_html__( 'Content', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Choose every public content type where the Table of Contents may appear on singular views.', 'creceweb-lumen-lite' ); ?></p></div>
								<fieldset class="cw-lumen-toc-types"><legend class="screen-reader-text"><?php echo esc_html__( 'Public content types', 'creceweb-lumen-lite' ); ?></legend><div><?php foreach ( $toc_types as $post_type => $label ) : ?><label><input type="checkbox" name="table_of_contents[post_types][]" value="<?php echo esc_attr( $post_type ); ?>" <?php checked( in_array( $post_type, $toc_selected, true ) ); ?>><span><?php echo esc_html( $label ); ?><small><?php echo esc_html( $post_type ); ?></small></span></label><?php endforeach; ?></div></fieldset>
							</section>
						</div>
							</article>
						</div>
					</div>
				</div>

				<div id="cw-lumen-content-panel-sharing" class="cw-lumen-content-panel" data-cw-content-panel="sharing" role="tabpanel" aria-labelledby="cw-lumen-content-tab-sharing"<?php if ( 'sharing' !== $active_content_view ) : ?> hidden<?php endif; ?>>
					<div class="cw-lumen-admin__grid">
						<article class="cw-lumen-admin__card cw-lumen-admin__card--wide cw-lumen-sharing-settings">
							<div class="cw-lumen-toc-heading">
								<div><p class="cw-lumen-admin__eyebrow"><?php echo esc_html__( 'Content tool', 'creceweb-lumen-lite' ); ?></p><h2><?php echo esc_html__( 'Sharing', 'creceweb-lumen-lite' ); ?></h2><p><?php echo esc_html__( 'Add lightweight share actions to selected public content without external SDKs, counters, accounts, or remote tracking.', 'creceweb-lumen-lite' ); ?></p></div>
								<label><input type="checkbox" name="sharing[enabled]" value="1" <?php checked( ! empty( $sharing['enabled'] ) ); ?>> <span><?php echo esc_html__( 'Enable Sharing', 'creceweb-lumen-lite' ); ?></span></label>
							</div>
							<div class="cw-lumen-toc-layout">
								<section class="cw-lumen-toc-group"><div class="cw-lumen-toc-group__heading"><h3><?php echo esc_html__( 'General', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Choose where the share actions appear and whether button text is shown.', 'creceweb-lumen-lite' ); ?></p></div><div class="cw-lumen-toc-field-grid">
									<p><label class="cw-lumen-admin__label" for="cw-lumen-sharing-position"><?php echo esc_html__( 'Position', 'creceweb-lumen-lite' ); ?></label><select id="cw-lumen-sharing-position" name="sharing[position]"><option value="before" <?php selected( (string) ( $sharing['position'] ?? 'after' ), 'before' ); ?>><?php echo esc_html__( 'Before the content', 'creceweb-lumen-lite' ); ?></option><option value="after" <?php selected( (string) ( $sharing['position'] ?? 'after' ), 'after' ); ?>><?php echo esc_html__( 'After the content', 'creceweb-lumen-lite' ); ?></option></select></p>
									<p><label><input type="checkbox" name="sharing[show_labels]" value="1" <?php checked( ! empty( $sharing['show_labels'] ) ); ?>> <?php echo esc_html__( 'Show text labels next to icons', 'creceweb-lumen-lite' ); ?></label></p>
								</div></section>
								<section class="cw-lumen-toc-group"><div class="cw-lumen-toc-group__heading"><h3><?php echo esc_html__( 'Actions', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Enable any combination of local share actions. Copy link uses the browser clipboard with a local fallback.', 'creceweb-lumen-lite' ); ?></p></div><fieldset class="cw-lumen-toc-levels"><legend class="screen-reader-text"><?php echo esc_html__( 'Share actions', 'creceweb-lumen-lite' ); ?></legend><div>
								<?php foreach ( array( 'copy' => __( 'Copy link', 'creceweb-lumen-lite' ), 'whatsapp' => __( 'WhatsApp', 'creceweb-lumen-lite' ), 'linkedin' => __( 'LinkedIn', 'creceweb-lumen-lite' ), 'facebook' => __( 'Facebook', 'creceweb-lumen-lite' ), 'email' => __( 'Email', 'creceweb-lumen-lite' ) ) as $action_key => $action_label ) : ?><label><input type="checkbox" name="sharing[actions][<?php echo esc_attr( $action_key ); ?>]" value="1" <?php checked( ! empty( $sharing_actions[ $action_key ] ) ); ?>><span><?php echo esc_html( $action_label ); ?></span></label><?php endforeach; ?>
								</div></fieldset></section>
								<section class="cw-lumen-toc-group">
									<div class="cw-lumen-toc-group__heading"><h3><?php echo esc_html__( 'Appearance', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Adjust button geometry and colors. Empty color fields keep the inherited Lumen or content appearance.', 'creceweb-lumen-lite' ); ?></p></div>
									<div class="cw-lumen-toc-field-grid">
										<p><label class="cw-lumen-admin__label" for="cw-lumen-sharing-button-size"><?php echo esc_html__( 'Button size (px)', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-sharing-button-size" type="number" min="0" step="0.1" name="sharing[button_size]" value="<?php echo esc_attr( (string) ( $sharing['button_size'] ?? 40 ) ); ?>"><span class="description"><?php echo esc_html__( 'Sets the minimum button height; icon-only buttons use the same width.', 'creceweb-lumen-lite' ); ?></span></p>
										<p><label class="cw-lumen-admin__label" for="cw-lumen-sharing-icon-size"><?php echo esc_html__( 'Icon size (px)', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-sharing-icon-size" type="number" min="0" step="0.1" name="sharing[icon_size]" value="<?php echo esc_attr( (string) ( $sharing['icon_size'] ?? 20 ) ); ?>"></p>
										<p><label class="cw-lumen-admin__label" for="cw-lumen-sharing-border-width"><?php echo esc_html__( 'Border width (px)', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-sharing-border-width" type="number" min="0" step="0.1" name="sharing[border_width]" value="<?php echo esc_attr( (string) ( $sharing['border_width'] ?? 1 ) ); ?>"></p>
										<p><label class="cw-lumen-admin__label" for="cw-lumen-sharing-border-radius"><?php echo esc_html__( 'Border radius (px)', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-sharing-border-radius" type="number" min="0" step="0.1" name="sharing[border_radius]" value="<?php echo esc_attr( (string) ( $sharing['border_radius'] ?? 8 ) ); ?>"></p>
									</div>
									<p class="cw-lumen-sharing-minimal-style"><label><input type="checkbox" name="sharing[minimal_style]" value="1" <?php checked( ! empty( $sharing['minimal_style'] ) ); ?>> <?php echo esc_html__( 'No background', 'creceweb-lumen-lite' ); ?></label><span class="description"><?php echo esc_html__( 'Removes button backgrounds in normal and hover states while keeping border settings unchanged.', 'creceweb-lumen-lite' ); ?></span></p>
									<div class="cw-lumen-toc-color-grid">
										<?php foreach ( array(
											'text_color' => __( 'Text and icon color', 'creceweb-lumen-lite' ),
											'text_hover_color' => __( 'Text and icon hover color', 'creceweb-lumen-lite' ),
											'background_color' => __( 'Background color', 'creceweb-lumen-lite' ),
											'background_hover_color' => __( 'Background hover color', 'creceweb-lumen-lite' ),
											'border_color' => __( 'Border color', 'creceweb-lumen-lite' ),
											'border_hover_color' => __( 'Border hover color', 'creceweb-lumen-lite' ),
										) as $color_key => $color_label ) : ?>
											<p class="cw-lumen-toc-color-control"><label class="cw-lumen-admin__label" for="cw-lumen-sharing-<?php echo esc_attr( str_replace( '_', '-', $color_key ) ); ?>"><?php echo esc_html( $color_label ); ?></label><input id="cw-lumen-sharing-<?php echo esc_attr( str_replace( '_', '-', $color_key ) ); ?>" class="cw-lumen-sharing-color-field" type="text" name="sharing[<?php echo esc_attr( $color_key ); ?>]" value="<?php echo esc_attr( (string) ( $sharing[ $color_key ] ?? '' ) ); ?>"></p>
										<?php endforeach; ?>
									</div>
								</section>
								<section class="cw-lumen-toc-group"><div class="cw-lumen-toc-group__heading"><h3><?php echo esc_html__( 'Content', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Choose every public content type where Sharing may appear on singular views.', 'creceweb-lumen-lite' ); ?></p></div><fieldset class="cw-lumen-toc-types"><legend class="screen-reader-text"><?php echo esc_html__( 'Public content types', 'creceweb-lumen-lite' ); ?></legend><div><?php foreach ( $sharing_types as $post_type => $label ) : ?><label><input type="checkbox" name="sharing[post_types][]" value="<?php echo esc_attr( $post_type ); ?>" <?php checked( in_array( $post_type, $sharing_selected, true ) ); ?>><span><?php echo esc_html( $label ); ?><small><?php echo esc_html( $post_type ); ?></small></span></label><?php endforeach; ?></div></fieldset></section>
							</div>
						</article>
					</div>
				</div>

				<div id="cw-lumen-content-panel-related-content" class="cw-lumen-content-panel" data-cw-content-panel="related-content" role="tabpanel" aria-labelledby="cw-lumen-content-tab-related-content"<?php if ( 'related-content' !== $active_content_view ) : ?> hidden<?php endif; ?>>
					<div class="cw-lumen-admin__grid">
						<article class="cw-lumen-admin__card cw-lumen-admin__card--wide cw-lumen-related-content-settings">
							<div class="cw-lumen-toc-heading">
								<div><p class="cw-lumen-admin__eyebrow"><?php echo esc_html__( 'Content tool', 'creceweb-lumen-lite' ); ?></p><h2><?php echo esc_html__( 'Related content', 'creceweb-lumen-lite' ); ?></h2><p><?php echo esc_html__( 'Automatically recommend relevant content after the current item by comparing shared public taxonomy terms.', 'creceweb-lumen-lite' ); ?></p></div>
								<label class="cw-lumen-toc-enabled"><input type="checkbox" name="related_content[enabled]" value="1" <?php checked( ! empty( $related_content['enabled'] ) ); ?>> <span><?php echo esc_html__( 'Enable Related Content', 'creceweb-lumen-lite' ); ?></span></label>
							</div>
							<div class="cw-lumen-toc-layout cw-lumen-related-content-layout">
								<section class="cw-lumen-toc-group cw-lumen-related-content-general">
									<div class="cw-lumen-toc-group__heading"><h3><?php echo esc_html__( 'General', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Name the section and choose how many recommendations to display. Positive values are not capped by Lumen.', 'creceweb-lumen-lite' ); ?></p></div>
									<div class="cw-lumen-toc-field-grid cw-lumen-related-content-general-grid">
										<p><label class="cw-lumen-admin__label" for="cw-lumen-related-content-title"><?php echo esc_html__( 'Section title', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-related-content-title" type="text" name="related_content[title]" value="<?php echo esc_attr( (string) ( $related_content['title'] ?? __( 'Related content', 'creceweb-lumen-lite' ) ) ); ?>"><span class="description"><?php echo esc_html__( 'Leave empty to render the recommendations without a visible heading.', 'creceweb-lumen-lite' ); ?></span></p>
										<p><label class="cw-lumen-admin__label" for="cw-lumen-related-content-count"><?php echo esc_html__( 'Items to show', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-related-content-count" type="number" min="1" step="1" name="related_content[count]" value="<?php echo esc_attr( (string) max( 1, absint( $related_content['count'] ?? 3 ) ) ); ?>"><span class="description"><?php echo esc_html__( 'Lumen ranks all matching items first, then displays this many results.', 'creceweb-lumen-lite' ); ?></span></p>
									</div>
								</section>

								<section class="cw-lumen-toc-group cw-lumen-related-content-responsive">
									<div class="cw-lumen-toc-group__heading"><h3><?php echo esc_html__( 'Responsive layout', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Choose the grid independently for desktop, tablet, and mobile. Each field accepts any positive column count.', 'creceweb-lumen-lite' ); ?></p></div>
									<div class="cw-lumen-related-content-responsive-grid">
										<label class="cw-lumen-related-content-device" for="cw-lumen-related-content-columns-desktop"><span class="cw-lumen-related-content-device__badge" aria-hidden="true">D</span><span class="cw-lumen-related-content-device__copy"><strong><?php echo esc_html__( 'Desktop columns', 'creceweb-lumen-lite' ); ?></strong><small><?php echo esc_html__( 'Wide screens', 'creceweb-lumen-lite' ); ?></small></span><input id="cw-lumen-related-content-columns-desktop" type="number" min="1" step="1" name="related_content[columns_desktop]" value="<?php echo esc_attr( (string) max( 1, absint( $related_content['columns_desktop'] ?? $related_content['columns'] ?? 3 ) ) ); ?>"></label>
										<label class="cw-lumen-related-content-device" for="cw-lumen-related-content-columns-tablet"><span class="cw-lumen-related-content-device__badge" aria-hidden="true">T</span><span class="cw-lumen-related-content-device__copy"><strong><?php echo esc_html__( 'Tablet columns', 'creceweb-lumen-lite' ); ?></strong><small><?php echo esc_html__( 'Medium screens', 'creceweb-lumen-lite' ); ?></small></span><input id="cw-lumen-related-content-columns-tablet" type="number" min="1" step="1" name="related_content[columns_tablet]" value="<?php echo esc_attr( (string) max( 1, absint( $related_content['columns_tablet'] ?? 2 ) ) ); ?>"></label>
										<label class="cw-lumen-related-content-device" for="cw-lumen-related-content-columns-mobile"><span class="cw-lumen-related-content-device__badge" aria-hidden="true">M</span><span class="cw-lumen-related-content-device__copy"><strong><?php echo esc_html__( 'Mobile columns', 'creceweb-lumen-lite' ); ?></strong><small><?php echo esc_html__( 'Small screens', 'creceweb-lumen-lite' ); ?></small></span><input id="cw-lumen-related-content-columns-mobile" type="number" min="1" step="1" name="related_content[columns_mobile]" value="<?php echo esc_attr( (string) max( 1, absint( $related_content['columns_mobile'] ?? 1 ) ) ); ?>"></label>
									</div>
								</section>

								<section class="cw-lumen-toc-group cw-lumen-related-content-appearance">
									<div class="cw-lumen-toc-group__heading"><h3><?php echo esc_html__( 'Appearance', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Control card structure here. Colors, typography, and global visual tokens continue to inherit from the active Theme or WordPress styles.', 'creceweb-lumen-lite' ); ?></p></div>
									<div class="cw-lumen-related-content-appearance-grid">
										<div class="cw-lumen-related-content-ratio-field">
										<label class="cw-lumen-admin__label" for="cw-lumen-related-content-image-ratio"><?php echo esc_html__( 'Image ratio', 'creceweb-lumen-lite' ); ?></label>
										<select id="cw-lumen-related-content-image-ratio" name="related_content[image_ratio_preset]" data-cw-related-ratio-preset aria-controls="cw-lumen-related-content-custom-ratio">
											<option value="16:9" <?php selected( $related_ratio_preset, '16:9' ); ?>><?php echo esc_html__( '16:9 — Widescreen', 'creceweb-lumen-lite' ); ?></option>
											<option value="4:3" <?php selected( $related_ratio_preset, '4:3' ); ?>><?php echo esc_html__( '4:3 — Standard', 'creceweb-lumen-lite' ); ?></option>
											<option value="3:2" <?php selected( $related_ratio_preset, '3:2' ); ?>><?php echo esc_html__( '3:2 — Photo', 'creceweb-lumen-lite' ); ?></option>
											<option value="1:1" <?php selected( $related_ratio_preset, '1:1' ); ?>><?php echo esc_html__( '1:1 — Square', 'creceweb-lumen-lite' ); ?></option>
											<option value="9:16" <?php selected( $related_ratio_preset, '9:16' ); ?>><?php echo esc_html__( '9:16 — Portrait', 'creceweb-lumen-lite' ); ?></option>
											<option value="custom" <?php selected( $related_ratio_preset, 'custom' ); ?>><?php echo esc_html__( 'Custom ratio', 'creceweb-lumen-lite' ); ?></option>
										</select>
										<span class="description"><?php echo esc_html__( 'Presets are shortcuts. Choose Custom ratio to enter any positive width and height.', 'creceweb-lumen-lite' ); ?></span>
										<div id="cw-lumen-related-content-custom-ratio" class="cw-lumen-related-content-ratio-custom" data-cw-related-ratio-custom>
											<div class="cw-lumen-related-content-ratio-inputs">
												<label><span class="screen-reader-text"><?php echo esc_html__( 'Image ratio width', 'creceweb-lumen-lite' ); ?></span><input type="number" step="any" name="related_content[image_ratio_width]" value="<?php echo esc_attr( (string) ( $related_content['image_ratio_width'] ?? 16 ) ); ?>"></label>
												<span aria-hidden="true">:</span>
												<label><span class="screen-reader-text"><?php echo esc_html__( 'Image ratio height', 'creceweb-lumen-lite' ); ?></span><input type="number" step="any" name="related_content[image_ratio_height]" value="<?php echo esc_attr( (string) ( $related_content['image_ratio_height'] ?? 9 ) ); ?>"></label>
											</div>
										</div>
									</div>
									<p class="cw-lumen-related-content-gap-field"><label class="cw-lumen-admin__label" for="cw-lumen-related-content-card-gap"><?php echo esc_html__( 'Card gap (px)', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-related-content-card-gap" type="number" min="0" step="0.1" name="related_content[card_gap]" value="<?php echo esc_attr( '' === (string) ( $related_content['card_gap'] ?? '' ) ? '' : (string) $related_content['card_gap'] ); ?>"><span class="description"><?php echo esc_html__( 'Leave empty to use Lumen’s responsive default spacing. Zero is allowed.', 'creceweb-lumen-lite' ); ?></span></p>
									</div>
									<fieldset class="cw-lumen-related-content-style-field"><legend class="cw-lumen-admin__label"><?php echo esc_html__( 'Card style', 'creceweb-lumen-lite' ); ?></legend><div class="cw-lumen-related-content-style-grid">
										<label class="cw-lumen-related-content-style-option"><input type="radio" name="related_content[card_style]" value="default" <?php checked( (string) ( $related_content['card_style'] ?? 'default' ), 'default' ); ?>><span><strong><?php echo esc_html__( 'Theme', 'creceweb-lumen-lite' ); ?></strong><small><?php echo esc_html__( 'Uses the inherited surface, border, radius, and color tokens.', 'creceweb-lumen-lite' ); ?></small></span></label>
										<label class="cw-lumen-related-content-style-option"><input type="radio" name="related_content[card_style]" value="elevated" <?php checked( (string) ( $related_content['card_style'] ?? 'default' ), 'elevated' ); ?>><span><strong><?php echo esc_html__( 'Elevated', 'creceweb-lumen-lite' ); ?></strong><small><?php echo esc_html__( 'Keeps inherited colors and adds a subtle card shadow.', 'creceweb-lumen-lite' ); ?></small></span></label>
										<label class="cw-lumen-related-content-style-option"><input type="radio" name="related_content[card_style]" value="minimal" <?php checked( (string) ( $related_content['card_style'] ?? 'default' ), 'minimal' ); ?>><span><strong><?php echo esc_html__( 'Minimal', 'creceweb-lumen-lite' ); ?></strong><small><?php echo esc_html__( 'Removes the card surface and border while preserving content and media.', 'creceweb-lumen-lite' ); ?></small></span></label>
									</div></fieldset>
								</section>

								<section class="cw-lumen-toc-group cw-lumen-related-content-card-content">
									<div class="cw-lumen-toc-group__heading"><h3><?php echo esc_html__( 'Card content', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Choose which native WordPress information appears in every recommendation.', 'creceweb-lumen-lite' ); ?></p></div>
									<fieldset class="cw-lumen-related-content-options"><legend class="screen-reader-text"><?php echo esc_html__( 'Card content', 'creceweb-lumen-lite' ); ?></legend><div>
										<label class="cw-lumen-related-content-option"><input type="checkbox" name="related_content[show_image]" value="1" <?php checked( ! empty( $related_content['show_image'] ) ); ?>><span class="cw-lumen-related-content-option__indicator" aria-hidden="true"></span><span><strong><?php echo esc_html__( 'Featured image', 'creceweb-lumen-lite' ); ?></strong><small><?php echo esc_html__( 'Show the post thumbnail when one exists.', 'creceweb-lumen-lite' ); ?></small></span></label>
										<label class="cw-lumen-related-content-option"><input type="checkbox" name="related_content[show_excerpt]" value="1" <?php checked( ! empty( $related_content['show_excerpt'] ) ); ?>><span class="cw-lumen-related-content-option__indicator" aria-hidden="true"></span><span><strong><?php echo esc_html__( 'Excerpt', 'creceweb-lumen-lite' ); ?></strong><small><?php echo esc_html__( 'Add the native WordPress excerpt below the title.', 'creceweb-lumen-lite' ); ?></small></span></label>
										<label class="cw-lumen-related-content-option"><input type="checkbox" name="related_content[show_date]" value="1" <?php checked( ! empty( $related_content['show_date'] ) ); ?>><span class="cw-lumen-related-content-option__indicator" aria-hidden="true"></span><span><strong><?php echo esc_html__( 'Date', 'creceweb-lumen-lite' ); ?></strong><small><?php echo esc_html__( 'Show the publication date as card metadata.', 'creceweb-lumen-lite' ); ?></small></span></label>
									</div></fieldset>
								</section>

								<aside class="cw-lumen-related-content-note" aria-label="<?php echo esc_attr__( 'Automatic matching', 'creceweb-lumen-lite' ); ?>"><span class="cw-lumen-related-content-note__icon" aria-hidden="true">↔</span><div><strong><?php echo esc_html__( 'Automatic matching', 'creceweb-lumen-lite' ); ?></strong><p><?php echo esc_html__( 'Lumen compares public taxonomy terms, prioritizes stronger overlaps, excludes the current item, and recommends published content from the same post type. If nothing matches, the entire section stays hidden.', 'creceweb-lumen-lite' ); ?></p></div></aside>

								<section class="cw-lumen-toc-group cw-lumen-related-content-types"><div class="cw-lumen-toc-group__heading"><h3><?php echo esc_html__( 'Content types', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Choose any viewable public post types where automatic Related Content may appear on singular views.', 'creceweb-lumen-lite' ); ?></p></div><fieldset class="cw-lumen-toc-types"><legend class="screen-reader-text"><?php echo esc_html__( 'Public content types', 'creceweb-lumen-lite' ); ?></legend><div><?php foreach ( $related_types as $post_type => $label ) : ?><label><input type="checkbox" name="related_content[post_types][]" value="<?php echo esc_attr( $post_type ); ?>" <?php checked( in_array( $post_type, $related_selected, true ) ); ?>><span><?php echo esc_html( $label ); ?><small><?php echo esc_html( $post_type ); ?></small></span></label><?php endforeach; ?></div></fieldset></section>
							</div>
						</article>
					</div>
				</div>
				<p class="cw-lumen-admin__actions"><button class="button button-primary" type="submit"><?php echo esc_html__( 'Save changes', 'creceweb-lumen-lite' ); ?></button></p>
			</form>
		</section>
		<?php
	}


	/** @return void */
	private function render_floating_action(): void {
		$settings = $this->floating_action->settings();
		$devices  = isset( $settings['devices'] ) && is_array( $settings['devices'] ) ? $settings['devices'] : array();
		$theme_floating = function_exists( '\CreceWeb\Lumen\get_floating_action_config' )
			? \CreceWeb\Lumen\get_floating_action_config()
			: array();
		$theme_back_to_top_overlap = ! empty( $settings['enabled'] )
			&& 'back_to_top' === (string) ( $theme_floating['action'] ?? '' )
			&& 'right' === (string) ( $settings['position'] ?? 'right' );
		$messaging = $this->messaging->config();
		$overlap = ! empty( $settings['enabled'] )
			&& ! empty( $messaging['enabled'] )
			&& ! empty( $messaging['url'] )
			&& (string) ( $settings['position'] ?? 'right' ) === (string) ( $messaging['position'] ?? 'right' );
		$icons = array(
			'arrow-right'   => __( 'Arrow right', 'creceweb-lumen-lite' ),
			'chevron-right' => __( 'Chevron right', 'creceweb-lumen-lite' ),
			'arrow-down'    => __( 'Arrow down', 'creceweb-lumen-lite' ),
			'plus'          => __( 'Plus', 'creceweb-lumen-lite' ),
			'info'          => __( 'Information', 'creceweb-lumen-lite' ),
			'mail'          => __( 'Email', 'creceweb-lumen-lite' ),
			'phone'         => __( 'Phone', 'creceweb-lumen-lite' ),
			'calendar'      => __( 'Calendar', 'creceweb-lumen-lite' ),
			'map-pin'       => __( 'Location', 'creceweb-lumen-lite' ),
			'chat'          => __( 'Chat', 'creceweb-lumen-lite' ),
		);
		$preview_label = '' !== trim( (string) ( $settings['label'] ?? '' ) ) ? (string) $settings['label'] : __( 'Action', 'creceweb-lumen-lite' );
		$preview_style = sprintf(
			'--cw-preview-size:%1$dpx;--cw-preview-background:%2$s;--cw-preview-background-hover:%3$s;--cw-preview-icon:%4$s;--cw-preview-icon-hover:%5$s;--cw-preview-text:%6$s;--cw-preview-text-hover:%7$s;--cw-preview-opacity:%8$s',
			max( 1, absint( $settings['size'] ?? 46 ) ),
			sanitize_hex_color( (string) ( $settings['background_color'] ?? '#0f172a' ) ) ?: '#0f172a',
			sanitize_hex_color( (string) ( $settings['background_hover'] ?? '#1e293b' ) ) ?: '#1e293b',
			sanitize_hex_color( (string) ( $settings['icon_color'] ?? '#ffffff' ) ) ?: '#ffffff',
			sanitize_hex_color( (string) ( $settings['icon_hover'] ?? '#ffffff' ) ) ?: '#ffffff',
			sanitize_hex_color( (string) ( $settings['text_color'] ?? '#ffffff' ) ) ?: '#ffffff',
			sanitize_hex_color( (string) ( $settings['text_hover'] ?? '#ffffff' ) ) ?: '#ffffff',
			(string) ( max( 0, min( 100, absint( $settings['opacity'] ?? 100 ) ) ) / 100 )
		);
		?>
		<section class="cw-lumen-admin__section">
			<form class="cw-lumen-floating-settings" data-cw-floating-settings method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cw_lumen_lite_save_floating_action">
				<input type="hidden" name="<?php echo esc_attr( AdminRedirect::FIELD ); ?>" value="<?php echo esc_url( $this->section_url( 'lite-floating-action' ) ); ?>">
				<?php wp_nonce_field( 'cw_lumen_lite_save_floating_action' ); ?>
				<div class="cw-lumen-admin__card cw-lumen-admin__card--wide">
					<div class="cw-lumen-floating-heading">
						<div>
							<h2><?php echo esc_html__( 'Advanced floating action', 'creceweb-lumen-lite' ); ?></h2>
							<p><?php echo esc_html__( 'Create one global floating action with its own content, destination, appearance and visibility. Theme Back to top remains a separate control.', 'creceweb-lumen-lite' ); ?></p>
						</div>
						<label class="cw-lumen-floating-enabled"><input type="checkbox" name="floating_action[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>> <span><?php echo esc_html__( 'Enable floating action', 'creceweb-lumen-lite' ); ?></span></label>
					</div>
					<?php if ( $theme_back_to_top_overlap ) : ?>
						<div class="notice notice-warning inline"><p><strong><?php echo esc_html__( 'Theme Back to top and the floating action are both using the bottom-right corner.', 'creceweb-lumen-lite' ); ?></strong> <?php echo esc_html__( 'Move this action to the left or adjust its offsets to keep both controls accessible.', 'creceweb-lumen-lite' ); ?></p></div>
					<?php endif; ?>
					<?php if ( $overlap ) : ?>
						<div class="notice notice-warning inline"><p><strong><?php echo esc_html__( 'Messaging and the floating action use the same corner.', 'creceweb-lumen-lite' ); ?></strong> <?php echo esc_html__( 'Move one action to the opposite side or adjust its offsets to prevent overlap.', 'creceweb-lumen-lite' ); ?></p></div>
					<?php endif; ?>

					<div class="cw-lumen-floating-layout">
						<div class="cw-lumen-floating-controls">
							<section class="cw-lumen-floating-panel">
								<div class="cw-lumen-floating-panel__heading"><h3><?php echo esc_html__( 'Content', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Choose what visitors see inside the action.', 'creceweb-lumen-lite' ); ?></p></div>
								<div class="cw-lumen-admin__field-grid">
									<p><label class="cw-lumen-admin__label" for="cw-lumen-lite-action-content"><?php echo esc_html__( 'Content type', 'creceweb-lumen-lite' ); ?></label><select id="cw-lumen-lite-action-content" name="floating_action[content]"><option value="icon" <?php selected( (string) $settings['content'], 'icon' ); ?>><?php echo esc_html__( 'Icon only', 'creceweb-lumen-lite' ); ?></option><option value="text" <?php selected( (string) $settings['content'], 'text' ); ?>><?php echo esc_html__( 'Text only', 'creceweb-lumen-lite' ); ?></option><option value="both" <?php selected( (string) $settings['content'], 'both' ); ?>><?php echo esc_html__( 'Icon + text', 'creceweb-lumen-lite' ); ?></option></select></p>
									<p data-cw-floating-group="text"><label class="cw-lumen-admin__label" for="cw-lumen-lite-action-label"><?php echo esc_html__( 'Button text', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-lite-action-label" class="regular-text" type="text" name="floating_action[label]" value="<?php echo esc_attr( (string) $settings['label'] ); ?>"><span class="description"><?php echo esc_html__( 'If empty, Icon + text falls back to icon only.', 'creceweb-lumen-lite' ); ?></span></p>
									<p data-cw-floating-group="icon"><label class="cw-lumen-admin__label" for="cw-lumen-lite-action-icon"><?php echo esc_html__( 'Icon', 'creceweb-lumen-lite' ); ?></label><select id="cw-lumen-lite-action-icon" name="floating_action[icon]"><?php foreach ( $icons as $icon_key => $icon_label ) : ?><option value="<?php echo esc_attr( $icon_key ); ?>" <?php selected( (string) $settings['icon'], $icon_key ); ?>><?php echo esc_html( $icon_label ); ?></option><?php endforeach; ?></select></p>
								</div>
							</section>

							<section class="cw-lumen-floating-panel">
								<div class="cw-lumen-floating-panel__heading"><h3><?php echo esc_html__( 'Destination', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Send the visitor to a section on this page or to a normal page/URL.', 'creceweb-lumen-lite' ); ?></p></div>
								<div class="cw-lumen-admin__field-grid">
									<p><label class="cw-lumen-admin__label" for="cw-lumen-lite-action-type"><?php echo esc_html__( 'Destination type', 'creceweb-lumen-lite' ); ?></label><select id="cw-lumen-lite-action-type" name="floating_action[action]"><option value="element" <?php selected( (string) $settings['action'], 'element' ); ?>><?php echo esc_html__( 'Go to page section', 'creceweb-lumen-lite' ); ?></option><option value="url" <?php selected( (string) $settings['action'], 'url' ); ?>><?php echo esc_html__( 'Open page or URL', 'creceweb-lumen-lite' ); ?></option></select></p>
									<p data-cw-floating-group="element"><label class="cw-lumen-admin__label" for="cw-lumen-lite-action-element"><?php echo esc_html__( 'Element ID', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-lite-action-element" class="regular-text" type="text" name="floating_action[element_id]" placeholder="contact" value="<?php echo esc_attr( (string) $settings['element_id'] ); ?>"><span class="description"><?php echo esc_html__( 'Enter the destination ID without #. An ID near the top can also act as a return-up action.', 'creceweb-lumen-lite' ); ?></span></p>
									<p data-cw-floating-group="url"><label class="cw-lumen-admin__label" for="cw-lumen-lite-action-url"><?php echo esc_html__( 'Page or URL', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-lite-action-url" class="regular-text" type="text" name="floating_action[url]" placeholder="https://example.com/contact/" value="<?php echo esc_attr( (string) $settings['url'] ); ?>"><span class="description"><?php echo esc_html__( 'Relative or absolute WordPress URLs are accepted after sanitization.', 'creceweb-lumen-lite' ); ?></span></p>
								</div>
							</section>

							<section class="cw-lumen-floating-panel">
								<div class="cw-lumen-floating-panel__heading"><h3><?php echo esc_html__( 'Appearance', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'These values belong only to this Lite action and do not change Theme Back to top.', 'creceweb-lumen-lite' ); ?></p></div>
								<div class="cw-lumen-admin__field-grid">
									<p><label class="cw-lumen-admin__label" for="cw-lumen-lite-action-shape"><?php echo esc_html__( 'Shape', 'creceweb-lumen-lite' ); ?></label><select id="cw-lumen-lite-action-shape" name="floating_action[shape]"><option value="pill" <?php selected( (string) $settings['shape'], 'pill' ); ?>><?php echo esc_html__( 'Pill / round', 'creceweb-lumen-lite' ); ?></option><option value="rounded" <?php selected( (string) $settings['shape'], 'rounded' ); ?>><?php echo esc_html__( 'Rounded', 'creceweb-lumen-lite' ); ?></option><option value="square" <?php selected( (string) $settings['shape'], 'square' ); ?>><?php echo esc_html__( 'Square', 'creceweb-lumen-lite' ); ?></option></select></p>
									<div class="cw-lumen-floating-size-control"><label class="cw-lumen-admin__label" for="cw-lumen-lite-action-size"><?php echo esc_html__( 'Size', 'creceweb-lumen-lite' ); ?></label><div><input id="cw-lumen-lite-action-size" type="number" min="1" step="1" name="floating_action[size]" value="<?php echo esc_attr( (string) max( 1, absint( $settings['size'] ) ) ); ?>"> <span>px</span></div></div>
								</div>
								<div class="cw-lumen-floating-colors">
									<div class="cw-lumen-floating-color"><label class="cw-lumen-admin__label" for="cw-lumen-lite-action-background"><?php echo esc_html__( 'Background', 'creceweb-lumen-lite' ); ?></label><div class="cw-lumen-floating-color-control"><input type="color" value="<?php echo esc_attr( (string) $settings['background_color'] ); ?>" data-cw-color-picker="cw-lumen-lite-action-background"><input id="cw-lumen-lite-action-background" type="text" maxlength="7" pattern="#[0-9A-Fa-f]{6}" name="floating_action[background_color]" value="<?php echo esc_attr( (string) $settings['background_color'] ); ?>"></div></div>
									<div class="cw-lumen-floating-color"><label class="cw-lumen-admin__label" for="cw-lumen-lite-action-background-hover"><?php echo esc_html__( 'Background hover', 'creceweb-lumen-lite' ); ?></label><div class="cw-lumen-floating-color-control"><input type="color" value="<?php echo esc_attr( (string) $settings['background_hover'] ); ?>" data-cw-color-picker="cw-lumen-lite-action-background-hover"><input id="cw-lumen-lite-action-background-hover" type="text" maxlength="7" pattern="#[0-9A-Fa-f]{6}" name="floating_action[background_hover]" value="<?php echo esc_attr( (string) $settings['background_hover'] ); ?>"></div></div>
									<div class="cw-lumen-floating-color" data-cw-floating-group="icon"><label class="cw-lumen-admin__label" for="cw-lumen-lite-action-icon-color"><?php echo esc_html__( 'Icon', 'creceweb-lumen-lite' ); ?></label><div class="cw-lumen-floating-color-control"><input type="color" value="<?php echo esc_attr( (string) $settings['icon_color'] ); ?>" data-cw-color-picker="cw-lumen-lite-action-icon-color"><input id="cw-lumen-lite-action-icon-color" type="text" maxlength="7" pattern="#[0-9A-Fa-f]{6}" name="floating_action[icon_color]" value="<?php echo esc_attr( (string) $settings['icon_color'] ); ?>"></div></div>
									<div class="cw-lumen-floating-color" data-cw-floating-group="icon"><label class="cw-lumen-admin__label" for="cw-lumen-lite-action-icon-hover"><?php echo esc_html__( 'Icon hover', 'creceweb-lumen-lite' ); ?></label><div class="cw-lumen-floating-color-control"><input type="color" value="<?php echo esc_attr( (string) $settings['icon_hover'] ); ?>" data-cw-color-picker="cw-lumen-lite-action-icon-hover"><input id="cw-lumen-lite-action-icon-hover" type="text" maxlength="7" pattern="#[0-9A-Fa-f]{6}" name="floating_action[icon_hover]" value="<?php echo esc_attr( (string) $settings['icon_hover'] ); ?>"></div></div>
									<div class="cw-lumen-floating-color" data-cw-floating-group="text"><label class="cw-lumen-admin__label" for="cw-lumen-lite-action-text-color"><?php echo esc_html__( 'Text', 'creceweb-lumen-lite' ); ?></label><div class="cw-lumen-floating-color-control"><input type="color" value="<?php echo esc_attr( (string) $settings['text_color'] ); ?>" data-cw-color-picker="cw-lumen-lite-action-text-color"><input id="cw-lumen-lite-action-text-color" type="text" maxlength="7" pattern="#[0-9A-Fa-f]{6}" name="floating_action[text_color]" value="<?php echo esc_attr( (string) $settings['text_color'] ); ?>"></div></div>
									<div class="cw-lumen-floating-color" data-cw-floating-group="text"><label class="cw-lumen-admin__label" for="cw-lumen-lite-action-text-hover"><?php echo esc_html__( 'Text hover', 'creceweb-lumen-lite' ); ?></label><div class="cw-lumen-floating-color-control"><input type="color" value="<?php echo esc_attr( (string) $settings['text_hover'] ); ?>" data-cw-color-picker="cw-lumen-lite-action-text-hover"><input id="cw-lumen-lite-action-text-hover" type="text" maxlength="7" pattern="#[0-9A-Fa-f]{6}" name="floating_action[text_hover]" value="<?php echo esc_attr( (string) $settings['text_hover'] ); ?>"></div></div>
								</div>
							</section>

							<section class="cw-lumen-floating-panel">
								<div class="cw-lumen-floating-panel__heading"><h3><?php echo esc_html__( 'Position and behavior', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Control where the action sits and when it becomes visible.', 'creceweb-lumen-lite' ); ?></p></div>
								<div class="cw-lumen-admin__field-grid">
									<p><label class="cw-lumen-admin__label" for="cw-lumen-lite-action-position"><?php echo esc_html__( 'Position', 'creceweb-lumen-lite' ); ?></label><select id="cw-lumen-lite-action-position" name="floating_action[position]"><option value="right" <?php selected( (string) $settings['position'], 'right' ); ?>><?php echo esc_html__( 'Bottom right', 'creceweb-lumen-lite' ); ?></option><option value="left" <?php selected( (string) $settings['position'], 'left' ); ?>><?php echo esc_html__( 'Bottom left', 'creceweb-lumen-lite' ); ?></option></select></p>
									<p><label class="cw-lumen-admin__label" for="cw-lumen-lite-action-scroll"><?php echo esc_html__( 'Show after scrolling', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-lite-action-scroll" type="number" min="0" step="1" name="floating_action[scroll_offset]" value="<?php echo esc_attr( (string) absint( $settings['scroll_offset'] ) ); ?>"> <span>px</span></p>
									<p><label class="cw-lumen-admin__label" for="cw-lumen-lite-action-bottom"><?php echo esc_html__( 'Bottom offset', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-lite-action-bottom" type="number" min="0" step="1" name="floating_action[bottom_offset]" value="<?php echo esc_attr( (string) absint( $settings['bottom_offset'] ) ); ?>"> <span>px</span></p>
									<p><label class="cw-lumen-admin__label" for="cw-lumen-lite-action-side"><?php echo esc_html__( 'Side offset', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-lite-action-side" type="number" min="0" step="1" name="floating_action[side_offset]" value="<?php echo esc_attr( (string) absint( $settings['side_offset'] ) ); ?>"> <span>px</span></p>
									<p><label class="cw-lumen-admin__label" for="cw-lumen-lite-action-opacity"><?php echo esc_html__( 'Opacity', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-lite-action-opacity" type="number" min="0" max="100" step="1" name="floating_action[opacity]" value="<?php echo esc_attr( (string) absint( $settings['opacity'] ) ); ?>"> <span>%</span></p>
									<p data-cw-floating-group="auto-hide"><label class="cw-lumen-admin__label" for="cw-lumen-lite-action-delay"><?php echo esc_html__( 'Auto-hide delay', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-lite-action-delay" type="number" min="0" step="1" name="floating_action[auto_hide_delay]" value="<?php echo esc_attr( (string) absint( $settings['auto_hide_delay'] ) ); ?>"> <span><?php echo esc_html__( 'seconds', 'creceweb-lumen-lite' ); ?></span></p>
								</div>
								<div class="cw-lumen-floating-switches"><label><input type="checkbox" name="floating_action[auto_hide]" value="1" <?php checked( ! empty( $settings['auto_hide'] ) ); ?>> <span><?php echo esc_html__( 'Auto-hide after inactivity', 'creceweb-lumen-lite' ); ?></span></label><label><input type="checkbox" name="floating_action[smooth_scroll]" value="1" <?php checked( ! empty( $settings['smooth_scroll'] ) ); ?>> <span><?php echo esc_html__( 'Smooth scroll for section destinations', 'creceweb-lumen-lite' ); ?></span></label></div>
								<p class="description"><?php echo esc_html__( 'Reduced-motion preferences always disable animated scrolling.', 'creceweb-lumen-lite' ); ?></p>
							</section>

							<section class="cw-lumen-floating-panel">
								<div class="cw-lumen-floating-panel__heading"><h3><?php echo esc_html__( 'Devices', 'creceweb-lumen-lite' ); ?></h3><p><?php echo esc_html__( 'Choose where the global action is available.', 'creceweb-lumen-lite' ); ?></p></div>
								<fieldset class="cw-lumen-floating-devices"><legend class="screen-reader-text"><?php echo esc_html__( 'Show on', 'creceweb-lumen-lite' ); ?></legend><label><input type="checkbox" name="floating_action[devices][desktop]" value="1" <?php checked( ! empty( $devices['desktop'] ) ); ?>><span><strong><?php echo esc_html__( 'Desktop', 'creceweb-lumen-lite' ); ?></strong><small><?php echo esc_html__( 'Large screens', 'creceweb-lumen-lite' ); ?></small></span></label><label><input type="checkbox" name="floating_action[devices][tablet]" value="1" <?php checked( ! empty( $devices['tablet'] ) ); ?>><span><strong><?php echo esc_html__( 'Tablet', 'creceweb-lumen-lite' ); ?></strong><small><?php echo esc_html__( 'Medium screens', 'creceweb-lumen-lite' ); ?></small></span></label><label><input type="checkbox" name="floating_action[devices][mobile]" value="1" <?php checked( ! empty( $devices['mobile'] ) ); ?>><span><strong><?php echo esc_html__( 'Mobile', 'creceweb-lumen-lite' ); ?></strong><small><?php echo esc_html__( 'Small screens', 'creceweb-lumen-lite' ); ?></small></span></label></fieldset>
								<p class="description"><?php echo esc_html__( 'If none are selected, all three are enabled to avoid hiding the action accidentally.', 'creceweb-lumen-lite' ); ?></p>
							</section>
						</div>

						<aside class="cw-lumen-floating-preview-card">
							<div class="cw-lumen-floating-preview-card__heading"><span><?php echo esc_html__( 'Live preview', 'creceweb-lumen-lite' ); ?></span><small><?php echo esc_html__( 'Hover to preview hover colors', 'creceweb-lumen-lite' ); ?></small></div>
							<div class="cw-lumen-floating-preview-stage" aria-hidden="true">
								<span class="cw-lumen-floating-preview" data-cw-floating-action-preview data-content="<?php echo esc_attr( (string) $settings['content'] ); ?>" data-shape="<?php echo esc_attr( (string) $settings['shape'] ); ?>" data-cw-preview-empty-label="<?php echo esc_attr__( 'Action', 'creceweb-lumen-lite' ); ?>" style="<?php echo esc_attr( $preview_style ); ?>"><span class="cw-lumen-floating-preview__icon" data-cw-preview-icon><?php echo $this->floating_action->icon_svg( (string) $settings['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static plugin-owned SVG. ?></span><span class="cw-lumen-floating-preview__label" data-cw-preview-label><?php echo esc_html( $preview_label ); ?></span></span>
							</div>
							<p><?php echo esc_html__( 'Preview reflects content, icon, shape, size, colors and opacity before saving.', 'creceweb-lumen-lite' ); ?></p>
						</aside>
					</div>

					<div hidden data-cw-floating-icon-templates><?php foreach ( array_keys( $icons ) as $icon_key ) : ?><span data-cw-floating-icon-template="<?php echo esc_attr( $icon_key ); ?>"><?php echo $this->floating_action->icon_svg( $icon_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static plugin-owned SVG. ?></span><?php endforeach; ?></div>
					<p class="description"><?php echo esc_html__( 'The rendered action is always a real semantic link: section actions use an anchor href and page/URL actions keep their actual destination.', 'creceweb-lumen-lite' ); ?></p>
				</div>
				<p class="cw-lumen-admin__actions"><button class="button button-primary" type="submit"><?php echo esc_html__( 'Save floating action', 'creceweb-lumen-lite' ); ?></button></p>
			</form>
		</section>
		<?php
	}
	/** @return void */
	private function render_messaging(): void {
		$settings  = $this->messaging->settings();
		$provider  = sanitize_key( (string) ( $settings['provider'] ?? 'whatsapp' ) );
		$providers = isset( $settings['providers'] ) && is_array( $settings['providers'] ) ? $settings['providers'] : array();
		$devices   = isset( $settings['devices'] ) && is_array( $settings['devices'] ) ? $settings['devices'] : array();
		$all_settings = \CreceWeb\LumenLite\Data\Settings::get();
		$migration_source = sanitize_key( (string) ( $all_settings['messaging_migration_source'] ?? '' ) );
		$lite_floating = $this->floating_action->config();
		$theme_floating = function_exists( '\\CreceWeb\\Lumen\\get_floating_action_config' )
			? \CreceWeb\Lumen\get_floating_action_config()
			: array();
		$floating_active = ! empty( $lite_floating['enabled'] ) || 'back_to_top' === (string) ( $theme_floating['action'] ?? '' );
		$floating_position = ! empty( $lite_floating['enabled'] ) ? (string) ( $lite_floating['position'] ?? 'right' ) : 'right';
		$config = $this->messaging->config();
		$may_overlap_floating = ! empty( $config['enabled'] )
			&& ! empty( $config['url'] )
			&& (string) ( $settings['position'] ?? 'right' ) === $floating_position
			&& $floating_active;
		$provider_labels = array(
			'whatsapp'  => __( 'WhatsApp', 'creceweb-lumen-lite' ),
			'telegram'  => __( 'Telegram', 'creceweb-lumen-lite' ),
			'messenger' => __( 'Messenger', 'creceweb-lumen-lite' ),
			'signal'    => __( 'Signal', 'creceweb-lumen-lite' ),
		);
		$appearance_mode = sanitize_key( (string) ( $settings['appearance_mode'] ?? 'provider' ) );
		if ( ! in_array( $appearance_mode, array( 'provider', 'custom' ), true ) ) {
			$appearance_mode = 'provider';
		}
		$background = sanitize_hex_color( (string) ( $settings['background_color'] ?? '' ) ) ?: '#0f172a';
		$icon_color = sanitize_hex_color( (string) ( $settings['icon_color'] ?? '' ) ) ?: '#ffffff';
		$effective_background = sanitize_hex_color( (string) ( $config['background_color'] ?? '' ) ) ?: $background;
		$effective_icon = sanitize_hex_color( (string) ( $config['icon_color'] ?? '' ) ) ?: $icon_color;
		$style_presets = $this->messaging->style_presets();
		$size = max( 1, absint( $settings['size'] ?? 46 ) );
		$preview_style = sprintf( '--cw-preview-size:%1$dpx;--cw-preview-background:%2$s;--cw-preview-icon:%3$s', $size, $effective_background, $effective_icon );
		?>
		<section class="cw-lumen-admin__section">
			<form class="cw-lumen-messaging-settings" data-cw-messaging-settings method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cw_lumen_lite_save_messaging">
				<input type="hidden" name="<?php echo esc_attr( AdminRedirect::FIELD ); ?>" value="<?php echo esc_url( $this->section_url( 'lite-messaging' ) ); ?>">
				<?php wp_nonce_field( 'cw_lumen_lite_save_messaging' ); ?>
				<div class="cw-lumen-admin__card cw-lumen-admin__card--wide">
					<h2><?php echo esc_html__( 'Site-wide messaging', 'creceweb-lumen-lite' ); ?></h2>
					<p><?php echo esc_html__( 'Choose one messaging service for the global contact action. All four providers are available in Lumen Lite.', 'creceweb-lumen-lite' ); ?></p>
					<?php if ( $may_overlap_floating ) : ?>
						<div class="notice notice-warning inline"><p><strong><?php echo esc_html__( 'Messaging and the floating action use the same corner.', 'creceweb-lumen-lite' ); ?></strong> <?php echo esc_html__( 'Lumen does not hide or move actions automatically. Move one action to the opposite side or disable it to prevent overlap.', 'creceweb-lumen-lite' ); ?> <a href="<?php echo esc_url( $this->section_url( 'lite-floating-action' ) ); ?>"><?php echo esc_html__( 'Open floating action', 'creceweb-lumen-lite' ); ?></a></p></div>
					<?php endif; ?>
					<?php if ( in_array( $migration_source, array( 'lite-whatsapp', 'theme-legacy' ), true ) ) : ?>
						<p class="description"><?php echo esc_html__( 'Your previous WhatsApp settings were copied automatically into Messaging.', 'creceweb-lumen-lite' ); ?></p>
					<?php endif; ?>
					<p><label><input type="checkbox" name="messaging[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>> <?php echo esc_html__( 'Show global messaging button', 'creceweb-lumen-lite' ); ?></label></p>
					<div class="cw-lumen-admin__field-grid">
						<p><label class="cw-lumen-admin__label" for="cw-lumen-lite-messaging-provider"><?php echo esc_html__( 'Service', 'creceweb-lumen-lite' ); ?></label><select id="cw-lumen-lite-messaging-provider" name="messaging[provider]"><?php foreach ( $provider_labels as $provider_key => $provider_label ) : ?><option value="<?php echo esc_attr( $provider_key ); ?>" <?php selected( $provider, $provider_key ); ?>><?php echo esc_html( $provider_label ); ?></option><?php endforeach; ?></select></p>
						<p><label class="cw-lumen-admin__label" for="cw-lumen-lite-messaging-position"><?php echo esc_html__( 'Location', 'creceweb-lumen-lite' ); ?></label><select id="cw-lumen-lite-messaging-position" name="messaging[position]"><option value="right" <?php selected( (string) $settings['position'], 'right' ); ?>><?php echo esc_html__( 'Bottom right', 'creceweb-lumen-lite' ); ?></option><option value="left" <?php selected( (string) $settings['position'], 'left' ); ?>><?php echo esc_html__( 'Bottom left', 'creceweb-lumen-lite' ); ?></option></select></p>
						<p><label class="cw-lumen-admin__label" for="cw-lumen-lite-messaging-size"><?php echo esc_html__( 'Size', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-lite-messaging-size" type="number" min="1" step="1" name="messaging[size]" value="<?php echo esc_attr( (string) $size ); ?>"> <span>px</span></p>
						<p><label class="cw-lumen-admin__label" for="cw-lumen-lite-messaging-appearance"><?php echo esc_html__( 'Button appearance', 'creceweb-lumen-lite' ); ?></label><select id="cw-lumen-lite-messaging-appearance" name="messaging[appearance_mode]" data-cw-messaging-appearance-mode><option value="provider" <?php selected( 'provider', $appearance_mode ); ?>><?php echo esc_html__( 'Service color', 'creceweb-lumen-lite' ); ?></option><option value="custom" <?php selected( 'custom', $appearance_mode ); ?>><?php echo esc_html__( 'Custom', 'creceweb-lumen-lite' ); ?></option></select><span class="description"><?php echo esc_html__( 'Service color adapts automatically when you change provider. Custom keeps your brand colors.', 'creceweb-lumen-lite' ); ?></span></p>
						<p data-cw-messaging-custom-color<?php echo 'custom' === $appearance_mode ? '' : ' hidden'; ?>><label class="cw-lumen-admin__label" for="cw-lumen-lite-messaging-background"><?php echo esc_html__( 'Background color', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-lite-messaging-background" type="color" name="messaging[background_color]" value="<?php echo esc_attr( $background ); ?>"></p>
						<p data-cw-messaging-custom-color<?php echo 'custom' === $appearance_mode ? '' : ' hidden'; ?>><label class="cw-lumen-admin__label" for="cw-lumen-lite-messaging-icon"><?php echo esc_html__( 'Icon color', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-lite-messaging-icon" type="color" name="messaging[icon_color]" value="<?php echo esc_attr( $icon_color ); ?>"></p>
					</div>

					<div class="cw-lumen-floating-layout">
						<div>
							<div data-cw-messaging-provider-group="whatsapp"<?php echo 'whatsapp' === $provider ? '' : ' hidden'; ?>>
								<p class="cw-lumen-messaging-provider-field cw-lumen-messaging-provider-field--wide"><label class="cw-lumen-admin__label" for="cw-lumen-lite-messaging-whatsapp-number"><?php echo esc_html__( 'WhatsApp number', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-lite-messaging-whatsapp-number" class="regular-text cw-lumen-messaging-provider-input--wide" type="tel" inputmode="numeric" name="messaging[providers][whatsapp][number]" maxlength="20" value="<?php echo esc_attr( (string) ( $providers['whatsapp']['number'] ?? '' ) ); ?>"><span class="description cw-lumen-messaging-provider-help"><?php echo esc_html__( 'Include the country code without +, spaces, or hyphens.', 'creceweb-lumen-lite' ); ?></span></p>
								<?php $this->render_messaging_message_field( 'whatsapp', (string) ( $providers['whatsapp']['message'] ?? '' ) ); ?>
							</div>
							<div data-cw-messaging-provider-group="telegram"<?php echo 'telegram' === $provider ? '' : ' hidden'; ?>>
								<p class="cw-lumen-messaging-provider-field cw-lumen-messaging-provider-field--wide"><label class="cw-lumen-admin__label" for="cw-lumen-lite-messaging-telegram-username"><?php echo esc_html__( 'Telegram username', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-lite-messaging-telegram-username" class="regular-text cw-lumen-messaging-provider-input--wide" type="text" name="messaging[providers][telegram][username]" value="<?php echo esc_attr( (string) ( $providers['telegram']['username'] ?? '' ) ); ?>"><span class="description cw-lumen-messaging-provider-help"><?php echo esc_html__( 'Enter the public username with or without @. Telegram may open its contact page or app before the drafted message is available.', 'creceweb-lumen-lite' ); ?></span></p>
								<?php $this->render_messaging_message_field( 'telegram', (string) ( $providers['telegram']['message'] ?? '' ) ); ?>
							</div>
							<div data-cw-messaging-provider-group="messenger"<?php echo 'messenger' === $provider ? '' : ' hidden'; ?>>
								<p class="cw-lumen-messaging-provider-field cw-lumen-messaging-provider-field--wide"><label class="cw-lumen-admin__label" for="cw-lumen-lite-messaging-messenger-username"><?php echo esc_html__( 'Messenger username', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-lite-messaging-messenger-username" class="regular-text cw-lumen-messaging-provider-input--wide" type="text" name="messaging[providers][messenger][username]" value="<?php echo esc_attr( (string) ( $providers['messenger']['username'] ?? '' ) ); ?>"><span class="description cw-lumen-messaging-provider-help"><?php echo esc_html__( 'Enter the username used by your m.me contact link. Meta may require visitors to sign in to Messenger before they can send a message.', 'creceweb-lumen-lite' ); ?></span></p>
							</div>
							<div data-cw-messaging-provider-group="signal"<?php echo 'signal' === $provider ? '' : ' hidden'; ?>>
								<p class="cw-lumen-messaging-provider-field cw-lumen-messaging-provider-field--wide"><label class="cw-lumen-admin__label" for="cw-lumen-lite-messaging-signal-url"><?php echo esc_html__( 'Signal link', 'creceweb-lumen-lite' ); ?></label><input id="cw-lumen-lite-messaging-signal-url" class="regular-text cw-lumen-messaging-provider-input--wide" type="url" name="messaging[providers][signal][url]" placeholder="https://signal.me/..." value="<?php echo esc_attr( (string) ( $providers['signal']['url'] ?? '' ) ); ?>"><span class="description cw-lumen-messaging-provider-help"><?php echo esc_html__( 'Paste the contact link generated by Signal (signal.me or signal.link). Signal opens its contact page before continuing to the Signal app.', 'creceweb-lumen-lite' ); ?></span></p>
							</div>
						</div>

						<aside class="cw-lumen-floating-preview-card">
							<div class="cw-lumen-floating-preview-card__heading"><span><?php echo esc_html__( 'Live preview', 'creceweb-lumen-lite' ); ?></span><small><?php echo esc_html__( 'Shows the selected service glyph and appearance.', 'creceweb-lumen-lite' ); ?></small></div>
							<div class="cw-lumen-floating-preview-stage" aria-hidden="true">
								<span class="cw-lumen-floating-preview cw-lumen-messaging-preview" data-cw-messaging-preview data-content="icon" data-shape="pill" style="<?php echo esc_attr( $preview_style ); ?>"><span class="cw-lumen-floating-preview__icon" data-cw-messaging-preview-icon><?php echo $this->messaging->glyph_svg( $provider ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static plugin-owned SVG. ?></span></span>
							</div>
							<p><?php echo esc_html__( 'Preview reflects the selected service, size and colors before saving.', 'creceweb-lumen-lite' ); ?></p>
						</aside>
					</div>

					<div hidden data-cw-messaging-glyph-templates><?php foreach ( $this->messaging->glyphs() as $glyph_provider => $glyph_svg ) : ?><span data-cw-messaging-glyph-template="<?php echo esc_attr( $glyph_provider ); ?>"><?php echo $glyph_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static plugin-owned SVG. ?></span><?php endforeach; ?></div>
					<div hidden data-cw-messaging-style-presets><?php foreach ( $style_presets as $style_provider => $style_values ) : ?><span data-cw-messaging-style-preset="<?php echo esc_attr( $style_provider ); ?>" data-background="<?php echo esc_attr( (string) ( $style_values['background_color'] ?? '' ) ); ?>" data-icon="<?php echo esc_attr( (string) ( $style_values['icon_color'] ?? '' ) ); ?>"></span><?php endforeach; ?></div>
					<fieldset><legend class="cw-lumen-admin__label"><?php echo esc_html__( 'Show on', 'creceweb-lumen-lite' ); ?></legend><label><input type="checkbox" name="messaging[devices][desktop]" value="1" <?php checked( ! empty( $devices['desktop'] ) ); ?>> <?php echo esc_html__( 'Computers', 'creceweb-lumen-lite' ); ?></label> &nbsp; <label><input type="checkbox" name="messaging[devices][tablet]" value="1" <?php checked( ! empty( $devices['tablet'] ) ); ?>> <?php echo esc_html__( 'Tablets', 'creceweb-lumen-lite' ); ?></label> &nbsp; <label><input type="checkbox" name="messaging[devices][mobile]" value="1" <?php checked( ! empty( $devices['mobile'] ) ); ?>> <?php echo esc_html__( 'Phones', 'creceweb-lumen-lite' ); ?></label><p class="description"><?php echo esc_html__( 'If none are selected, all three are enabled to avoid hiding it accidentally.', 'creceweb-lumen-lite' ); ?></p></fieldset>
				</div>
				<p class="cw-lumen-admin__actions"><button class="button button-primary" type="submit"><?php echo esc_html__( 'Save messaging', 'creceweb-lumen-lite' ); ?></button></p>
			</form>
		</section>
		<?php
	}

	/**
	 * @param string $provider Provider slug that supports a drafted message.
	 * @param string $message Stored message template.
	 * @return void
	 */
	private function render_messaging_message_field( string $provider, string $message ): void {
		$field_id = 'cw-lumen-lite-messaging-' . $provider . '-message';
		?>
		<div class="cw-lumen-messaging-message-field">
			<label class="cw-lumen-admin__label" for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html__( 'Initial message', 'creceweb-lumen-lite' ); ?></label>
			<textarea id="<?php echo esc_attr( $field_id ); ?>" name="messaging[providers][<?php echo esc_attr( $provider ); ?>][message]" rows="4" aria-describedby="<?php echo esc_attr( $field_id . '-help' ); ?>"><?php echo esc_textarea( $message ); ?></textarea>
			<span class="cw-lumen-admin__label cw-lumen-messaging-message-tools__label"><?php echo esc_html__( 'Insert information', 'creceweb-lumen-lite' ); ?></span>
			<div class="cw-lumen-messaging-message-tools" role="group" aria-label="<?php echo esc_attr__( 'Insert information', 'creceweb-lumen-lite' ); ?>">
				<?php foreach ( array( '{site}' => __( 'Site name', 'creceweb-lumen-lite' ), '{title}' => __( 'Page title', 'creceweb-lumen-lite' ), '{description}' => __( 'Site description', 'creceweb-lumen-lite' ), '{url}' => __( 'Current URL', 'creceweb-lumen-lite' ) ) as $token => $label ) : ?>
					<button type="button" class="button button-secondary button-small" data-cw-messaging-token="<?php echo esc_attr( $token ); ?>" data-cw-messaging-token-target="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></button>
				<?php endforeach; ?>
			</div>
			<span id="<?php echo esc_attr( $field_id . '-help' ); ?>" class="description"><?php echo esc_html__( 'Use the buttons above to insert information that Lumen updates automatically for each page.', 'creceweb-lumen-lite' ); ?></span>
		</div>
		<?php
	}

	/** @return void */
	private function render_footer(): void {
		$template = $this->footer->template();
		?>
		<section class="cw-lumen-admin__section">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cw_lumen_lite_save_footer">
				<input type="hidden" name="<?php echo esc_attr( AdminRedirect::FIELD ); ?>" value="<?php echo esc_url( $this->section_url( 'lite-footer' ) ); ?>">
				<?php wp_nonce_field( 'cw_lumen_lite_save_footer' ); ?>
				<div class="cw-lumen-admin__card cw-lumen-admin__card--wide">
					<label class="cw-lumen-admin__label" for="cw-lumen-lite-footer-credit"><?php echo esc_html__( 'Site footer text', 'creceweb-lumen-lite' ); ?></label>
					<textarea id="cw-lumen-lite-footer-credit" name="footer_credit_text" rows="4"><?php echo esc_textarea( $template ); ?></textarea>
					<p class="description"><?php echo esc_html__( 'You can use {year} for the current year and {site_name} for the site name. Leave the field empty to hide this line.', 'creceweb-lumen-lite' ); ?></p>
					<p class="cw-lumen-admin__preview"><strong><?php echo esc_html__( 'Preview:', 'creceweb-lumen-lite' ); ?></strong> <?php echo esc_html( $this->footer->expanded_text() ); ?></p>
				</div>
				<p class="cw-lumen-admin__actions">
					<button class="button button-primary" type="submit"><?php echo esc_html__( 'Save changes', 'creceweb-lumen-lite' ); ?></button>
					<button class="button button-secondary" type="submit" name="cw_lumen_lite_footer_reset" value="1"><?php echo esc_html__( 'Restore original text', 'creceweb-lumen-lite' ); ?></button>
				</p>
			</form>
		</section>
		<?php
	}

	/** @return void */
	private function render_status(): void {
		$status = $this->compatibility->status();
		$compatible = ! empty( $status['compatible'] );
		?>
		<section class="cw-lumen-admin__section">
			<div class="cw-lumen-admin__card cw-lumen-admin__card--wide">
				<h2><?php echo esc_html__( 'Compatibility status', 'creceweb-lumen-lite' ); ?></h2>
				<p><?php echo esc_html( $compatible ? __( 'Lumen Lite is active and connected correctly with Lumen Theme.', 'creceweb-lumen-lite' ) : __( 'Lumen Lite is active. Theme-dependent tools become available when a compatible version of Lumen Theme is active.', 'creceweb-lumen-lite' ) ); ?></p>
				<ul class="cw-lumen-admin__status-details">
					<li><strong><?php echo esc_html__( 'Lumen Lite:', 'creceweb-lumen-lite' ); ?></strong> <?php echo esc_html( CRECEWEB_LUMEN_LITE_VERSION ); ?></li>
					<li><strong><?php echo esc_html__( 'Lumen Theme:', 'creceweb-lumen-lite' ); ?></strong> <?php echo esc_html( (string) ( $status['theme_version'] ?: __( 'Not active', 'creceweb-lumen-lite' ) ) ); ?></li>
					<?php foreach ( (array) $status['issues'] as $issue ) : ?>
						<li><?php echo esc_html( $this->compatibility->describe_issue( (string) $issue ) ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
		<?php
	}

	/**
	 * @param string $section Section identifier.
	 * @param string $notice Notice identifier.
	 * @return string
	 */
	private function section_url( string $section, string $notice = '' ): string {
		if ( in_array( $section, array( 'lite-library', 'lite-status' ), true ) ) {
			$section = 'home';
		}
		$arguments = array();
		if ( '' !== $notice ) {
			$arguments['cw_lumen_lite_notice'] = $notice;
		}

		if ( function_exists( '\\CreceWeb\\Lumen\\get_admin_section_url' ) && $this->compatibility->is_compatible() ) {
			return \CreceWeb\Lumen\get_admin_section_url( $section, $arguments );
		}

		$url = add_query_arg(
			array(
				'page' => self::FALLBACK_PAGE_SLUG,
				'tab'  => $section,
			),
			admin_url( 'themes.php' )
		);
		return '' !== $notice ? add_query_arg( 'cw_lumen_lite_notice', $notice, $url ) : $url;
	}
}
