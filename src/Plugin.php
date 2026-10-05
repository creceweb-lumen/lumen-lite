<?php
/**
 * Main plugin bootstrap.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite;

use CreceWeb\LumenLite\Admin\Admin;
use CreceWeb\LumenLite\Content\Controller as ContentController;
use CreceWeb\LumenLite\ConfigTransfer\Manager as ConfigTransferManager;
use CreceWeb\LumenLite\ColorMode\Controller as ColorModeController;
use CreceWeb\LumenLite\Breadcrumbs\Controller as BreadcrumbsController;
use CreceWeb\LumenLite\Data\Settings;
use CreceWeb\LumenLite\Footer\Controller as FooterController;
use CreceWeb\LumenLite\FloatingAction\Controller as FloatingActionController;
use CreceWeb\LumenLite\Library\Catalog;
use CreceWeb\LumenLite\Library\Controller as LibraryController;
use CreceWeb\LumenLite\Library\KitCatalog;
use CreceWeb\LumenLite\Library\KitPresetManager;
use CreceWeb\LumenLite\Library\PageCatalog;
use CreceWeb\LumenLite\Library\SiteKitManager;
use CreceWeb\LumenLite\Menu\Controller as MenuController;
use CreceWeb\LumenLite\Performance\MvpController;
use CreceWeb\LumenLite\PopularContent\Controller as PopularContentController;
use CreceWeb\LumenLite\PostsGrid\Controller as PostsGridController;
use CreceWeb\LumenLite\ReadingProgress\Controller as ReadingProgressController;
use CreceWeb\LumenLite\SearchModal\Controller as SearchModalController;
use CreceWeb\LumenLite\RelatedContent\Controller as RelatedContentController;
use CreceWeb\LumenLite\Sharing\Controller as SharingController;
use CreceWeb\LumenLite\Support\Compatibility;
use CreceWeb\LumenLite\TableOfContents\Controller as TableOfContentsController;
use CreceWeb\LumenLite\Messaging\Controller as MessagingController;
use CreceWeb\LumenLite\Messaging\GlyphResolver;
use CreceWeb\LumenLite\Messaging\MessageResolver as MessagingMessageResolver;
use CreceWeb\LumenLite\Messaging\StyleResolver;
use CreceWeb\LumenLite\Messaging\UrlResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Owns the shared Lite lifecycle and public extension services.
 */
final class Plugin {
	private static ?self $instance = null;
	private Compatibility $compatibility;
	private Catalog $catalog;
	private KitCatalog $kit_catalog;
	private KitPresetManager $kit_presets;
	private SiteKitManager $site_kits;
	private LibraryController $library;
	private FooterController $footer;
	private ConfigTransferManager $config_transfer;
	private ContentController $content;
	private BreadcrumbsController $breadcrumbs;
	private MenuController $menu;
	private ReadingProgressController $reading_progress;
	private TableOfContentsController $table_of_contents;
	private SharingController $sharing;
	private RelatedContentController $related_content;
	private PopularContentController $popular_content;
	private PostsGridController $posts_grid;
	private ColorModeController $color_mode;
	private SearchModalController $search_modal;
	private FloatingActionController $floating_action;
	private MessagingMessageResolver $messaging_message_resolver;
	private UrlResolver $messaging_url_resolver;
	private GlyphResolver $messaging_glyph_resolver;
	private StyleResolver $messaging_style_resolver;
	private MessagingController $messaging;
	private MvpController $performance;
	private Admin $admin;
	private bool $announced = false;

	/** @return void */
	public static function boot(): void {
		if ( null !== self::$instance ) {
			return;
		}
		self::$instance = new self();
		self::$instance->register_hooks();
	}

	/** @return void */
	public static function activate(): void {
		Settings::install();
	}

	/** @return void */
	public static function deactivate(): void {
		// Lite keeps user settings, menu metadata and inserted block content.
	}

	/** @return self|null */
	public static function instance(): ?self {
		return self::$instance;
	}

	private function __construct() {
		$this->compatibility = new Compatibility();
		$this->catalog         = new Catalog();
		$this->kit_catalog     = new KitCatalog();
		$this->config_transfer = new ConfigTransferManager();
		$this->kit_presets     = new KitPresetManager( $this->kit_catalog, $this->config_transfer );
		$this->library         = new LibraryController( $this->compatibility, $this->kit_catalog, $this->kit_presets );
		$this->footer          = new FooterController( $this->compatibility );
		$this->content       = new ContentController( $this->compatibility );
		$this->breadcrumbs   = new BreadcrumbsController( $this->compatibility );
		$this->menu             = new MenuController( $this->compatibility );
		$this->site_kits        = new SiteKitManager( $this->kit_catalog, new PageCatalog(), $this->kit_presets, $this->menu );
		$this->reading_progress = new ReadingProgressController( $this->compatibility );
		$this->table_of_contents = new TableOfContentsController( $this->compatibility );
		$this->sharing          = new SharingController( $this->compatibility );
		$this->related_content  = new RelatedContentController( $this->compatibility );
		$this->popular_content  = new PopularContentController( $this->compatibility );
		$this->posts_grid       = new PostsGridController();
		$this->color_mode       = new ColorModeController( $this->compatibility );
		$this->search_modal      = new SearchModalController( $this->compatibility );
		$this->floating_action            = new FloatingActionController( $this->compatibility );
		$this->messaging_message_resolver = new MessagingMessageResolver();
		$this->messaging_url_resolver     = new UrlResolver( $this->messaging_message_resolver );
		$this->messaging_glyph_resolver   = new GlyphResolver();
		$this->messaging_style_resolver   = new StyleResolver();
		$this->messaging                  = new MessagingController( $this->compatibility, $this->messaging_message_resolver, $this->messaging_url_resolver, $this->messaging_glyph_resolver, $this->messaging_style_resolver );
		$this->performance                = new MvpController();

		if ( function_exists( 'cw_lumen_performance_register_profile' ) ) {
			cw_lumen_performance_register_profile(
				'lite',
				array(
					'diagnose'      => 'cw_lumen_lite_performance_diagnose',
					'allowed_paths' => array(
						'/wp-content/themes/creceweb-lumen/',
						'/wp-content/plugins/creceweb-lumen-lite/',
					),
				)
			);
		}

		$this->admin = new Admin( $this->compatibility, $this->footer, $this->content, $this->reading_progress, $this->table_of_contents, $this->sharing, $this->related_content, $this->popular_content, $this->posts_grid, $this->color_mode, $this->search_modal, $this->floating_action, $this->messaging, $this->performance, $this->config_transfer, $this->site_kits );
	}

	/** @return void */
	private function register_hooks(): void {
		add_action( 'plugins_loaded', array( $this, 'announce_loaded' ), 20 );
		add_action( 'after_setup_theme', array( $this, 'validate_theme' ), 100 );
		add_action( 'creceweb_lumen_loaded', array( $this, 'receive_lumen_announcement' ), 100, 2 );
		add_action( 'admin_init', array( $this, 'maybe_install' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ), 30 );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_editor_content_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		$this->library->register();
		$this->footer->register();
		$this->content->register();
		$this->breadcrumbs->register();
		$this->menu->register();
		$this->reading_progress->register();
		$this->table_of_contents->register();
		$this->sharing->register();
		$this->related_content->register();
		$this->popular_content->register();
		$this->posts_grid->register();
		$this->color_mode->register();
		$this->search_modal->register();
		$this->floating_action->register();
		$this->messaging->register();
		$this->performance->register();
		$this->config_transfer->register();
		$this->kit_presets->register();
		$this->site_kits->register();
		$this->admin->register();
	}

	/** @return void */
	public function announce_loaded(): void {
		if ( $this->announced ) {
			return;
		}
		$this->announced = true;
		do_action( 'creceweb_lumen_lite_loaded', CRECEWEB_LUMEN_LITE_VERSION, CRECEWEB_LUMEN_LITE_API_VERSION, $this );
	}

	/** @return void */
	public function validate_theme(): void {
		$this->compatibility->validate();
	}

	/**
	 * @param string $lumen_version Theme version.
	 * @param string $bridge_version Theme bridge API version.
	 * @return void
	 */
	public function receive_lumen_announcement( string $lumen_version, string $bridge_version = '' ): void {
		$this->compatibility->receive_lumen_announcement( $lumen_version, $bridge_version );
	}


	/** @return void */
	public function maybe_install(): void {
		Settings::install();
	}

	/** @return void */
	public function enqueue_frontend_assets(): void {
		if ( $this->library->needs_assets() ) {
			wp_enqueue_style( 'creceweb-lumen-lite-frontend', CRECEWEB_LUMEN_LITE_URL . 'assets/css/frontend.css', array(), CRECEWEB_LUMEN_LITE_ASSET_VERSION );
		}

		if ( $this->library->needs_kit_component_assets() ) {
			wp_enqueue_style(
				'creceweb-lumen-lite-kit-components',
				CRECEWEB_LUMEN_LITE_URL . 'assets/css/library-kits/components.css',
				array( 'creceweb-lumen-lite-frontend' ),
				CRECEWEB_LUMEN_LITE_ASSET_VERSION
			);
			foreach ( $this->library->active_kit_style_profiles() as $id => $profile ) {
				wp_enqueue_style(
					'creceweb-lumen-lite-kit-profile-' . $id,
					$profile['url'],
					array( 'creceweb-lumen-lite-kit-components' ),
					CRECEWEB_LUMEN_LITE_ASSET_VERSION
				);
			}
		}

		if ( ! $this->is_lumen_pro_active() && $this->library->needs_legacy_pro_assets() ) {
			wp_enqueue_style(
				'creceweb-lumen-lite-pro-legacy-compat',
				CRECEWEB_LUMEN_LITE_URL . 'assets/css/pro-legacy-compat.css',
				array(),
				CRECEWEB_LUMEN_LITE_ASSET_VERSION
			);
		}

		if ( $this->content->needs_assets() ) {
			wp_enqueue_style( 'creceweb-lumen-lite-content', CRECEWEB_LUMEN_LITE_URL . 'assets/css/content.css', array(), CRECEWEB_LUMEN_LITE_ASSET_VERSION );
		}

		if ( $this->menu->needs_assets() ) {
			wp_enqueue_style( 'creceweb-lumen-lite-menu', CRECEWEB_LUMEN_LITE_URL . 'assets/css/menu.css', array(), CRECEWEB_LUMEN_LITE_ASSET_VERSION );
		}
	}

	/** Loads pattern presentation inside Gutenberg's editor canvas/iframe. @return void */
	public function enqueue_editor_content_assets(): void {
		if ( ! is_admin() || ! $this->compatibility->is_compatible() ) {
			return;
		}
		wp_enqueue_style( 'creceweb-lumen-lite-editor-content', CRECEWEB_LUMEN_LITE_URL . 'assets/css/frontend.css', array(), CRECEWEB_LUMEN_LITE_ASSET_VERSION );

		if ( ! $this->is_lumen_pro_active() ) {
			wp_enqueue_style(
				'creceweb-lumen-lite-pro-legacy-compat',
				CRECEWEB_LUMEN_LITE_URL . 'assets/css/pro-legacy-compat.css',
				array(),
				CRECEWEB_LUMEN_LITE_ASSET_VERSION
			);
		}
		wp_enqueue_style(
			'creceweb-lumen-lite-editor-patterns',
			CRECEWEB_LUMEN_LITE_URL . 'assets/css/editor-patterns.css',
			array( 'creceweb-lumen-lite-editor-content' ),
			CRECEWEB_LUMEN_LITE_ASSET_VERSION
		);
		wp_enqueue_style(
			'creceweb-lumen-lite-kit-components',
			CRECEWEB_LUMEN_LITE_URL . 'assets/css/library-kits/components.css',
			array( 'creceweb-lumen-lite-editor-content' ),
			CRECEWEB_LUMEN_LITE_ASSET_VERSION
		);
		foreach ( $this->library->kit_style_profiles() as $id => $profile ) {
			wp_enqueue_style(
				'creceweb-lumen-lite-kit-profile-' . $id,
				$profile['url'],
				array( 'creceweb-lumen-lite-kit-components' ),
				CRECEWEB_LUMEN_LITE_ASSET_VERSION
			);
		}
	}

	/** Loads only the Library browser controls in the editor interface. @return void */
	public function enqueue_editor_assets(): void {
		if ( ! $this->compatibility->is_compatible() ) {
			return;
		}
		$this->library->enqueue_editor_assets();
	}

	/**
	 * Pro exposes its version constant while active plugins are loaded.
	 * Enqueue hooks run later, so no plugin-list API or remote request is needed.
	 *
	 * @return bool
	 */
	private function is_lumen_pro_active(): bool {
		return defined( 'CRECEWEB_LUMEN_PRO_VERSION' );
	}

	/** @return Compatibility */
	public function compatibility(): Compatibility { return $this->compatibility; }
	/** @return Catalog */
	public function catalog(): Catalog { return $this->catalog; }
	/** @return KitCatalog */
	public function kit_catalog(): KitCatalog { return $this->kit_catalog; }
	/** @return SiteKitManager */
	public function site_kits(): SiteKitManager { return $this->site_kits; }
	/** @return LibraryController */
	public function library(): LibraryController { return $this->library; }
	/** @return FooterController */
	public function footer(): FooterController { return $this->footer; }
	/** @return ContentController */
	public function content(): ContentController { return $this->content; }
	/** @return BreadcrumbsController */
	public function breadcrumbs(): BreadcrumbsController { return $this->breadcrumbs; }
	/** @return MenuController */
	public function menu(): MenuController { return $this->menu; }
	/** @return ReadingProgressController */
	public function reading_progress(): ReadingProgressController { return $this->reading_progress; }
	/** @return TableOfContentsController */
	public function table_of_contents(): TableOfContentsController { return $this->table_of_contents; }
	/** @return SharingController */
	public function sharing(): SharingController { return $this->sharing; }
	/** @return RelatedContentController */
	public function related_content(): RelatedContentController { return $this->related_content; }
	/** @return PopularContentController */
	public function popular_content(): PopularContentController { return $this->popular_content; }
	/** @return PostsGridController */
	public function posts_grid(): PostsGridController { return $this->posts_grid; }
	/** @return ColorModeController */
	public function color_mode(): ColorModeController { return $this->color_mode; }
	/** @return SearchModalController */
	public function search_modal(): SearchModalController { return $this->search_modal; }
	/** @return FloatingActionController */
	public function floating_action(): FloatingActionController { return $this->floating_action; }
	/** @return MessagingMessageResolver */
	public function messaging_message_resolver(): MessagingMessageResolver { return $this->messaging_message_resolver; }
	/** @return UrlResolver */
	public function messaging_url_resolver(): UrlResolver { return $this->messaging_url_resolver; }
	/** @return GlyphResolver */
	public function messaging_glyph_resolver(): GlyphResolver { return $this->messaging_glyph_resolver; }
	/** @return StyleResolver */
	public function messaging_style_resolver(): StyleResolver { return $this->messaging_style_resolver; }
	/** @return MessagingController */
	public function messaging(): MessagingController { return $this->messaging; }
	/** @return MessagingMessageResolver Legacy alias for one compatibility cycle. */
	public function whatsapp_message_resolver(): MessagingMessageResolver { return $this->messaging_message_resolver; }
	/** @return MvpController */
	public function performance(): MvpController { return $this->performance; }
}
