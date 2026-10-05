<?php
/**
 * Pattern registration and local Library catalog.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Library;

use CreceWeb\LumenLite\Support\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers Lite-owned patterns and exposes them through the shared catalog.
 */
final class Controller {
	private const CATEGORY_SLUG = 'creceweb-lumen-lite';

	private PageCatalog $pages;

	/** @var array<string,array<string,mixed>>|null */
	private ?array $definitions = null;

	/** Locale used to build the cached, translated definitions. */
	private ?string $definitions_locale = null;

	public function __construct( private Compatibility $compatibility, private KitCatalog $kits, private KitPresetManager $kit_presets ) {
		$this->pages = new PageCatalog();
	}

	/** @return void */
	public function register(): void {
		add_action( 'init', array( $this, 'register_patterns' ), 20 );
		add_action( 'wp_ajax_cw_lumen_lite_apply_page_template', array( $this, 'ajax_apply_page_template' ) );
		add_filter( 'creceweb_lumen_lite_library_families', array( $this, 'filter_families' ), 10 );
		add_filter( 'creceweb_lumen_lite_library_catalog', array( $this, 'filter_catalog' ), 10 );
	}


	/** @return void */
	public function register_patterns(): void {
		if ( ! function_exists( 'register_block_pattern' ) || ! $this->compatibility->validate() ) {
			return;
		}

		if ( function_exists( 'register_block_pattern_category' ) ) {
			register_block_pattern_category(
				self::CATEGORY_SLUG,
				array( 'label' => __( 'Lumen Lite', 'creceweb-lumen-lite' ) )
			);
		}

		foreach ( array_merge( $this->definitions(), $this->pages->definitions() ) as $definition ) {
			register_block_pattern(
				'creceweb-lumen-lite/' . $definition['slug'],
				array(
					'title'       => $definition['title'],
					'description' => $definition['description'],
					'categories'  => array( self::CATEGORY_SLUG ),
					'keywords'    => $definition['keywords'],
					'source'      => 'plugin',
					'inserter'    => false,
					'content'     => $definition['content'],
				)
			);
		}
	}

	/**
	 * @param array<string,array<string,string>> $families Existing families.
	 * @return array<string,array<string,string>>
	 */
	public function filter_families( array $families ): array {

		return array_merge( $families, $this->families() );
	}

	/**
	 * @param array<string,array<string,mixed>> $catalog Existing catalog.
	 * @return array<string,array<string,mixed>>
	 */
	public function filter_catalog( array $catalog ): array {

		foreach ( $this->definitions() as $definition ) {
			$explicit_preview = sanitize_file_name( (string) ( $definition['preview'] ?? '' ) );
			$preview_file     = '' !== $explicit_preview ? $explicit_preview : $definition['slug'] . '.avif';
			$preview_path     = CRECEWEB_LUMEN_LITE_DIR . 'assets/images/library-previews/' . $preview_file;

			// Never treat the previews directory itself as a valid image. Normalized
			// pattern definitions may contain an empty preview key; in that case the
			// canonical Lite preview is the slug-matched AVIF asset.
			$preview_url = is_file( $preview_path ) && is_readable( $preview_path )
				? CRECEWEB_LUMEN_LITE_URL . 'assets/images/library-previews/' . $preview_file
				: CRECEWEB_LUMEN_LITE_URL . 'assets/images/library-placeholder.svg';

			$catalog[ $definition['slug'] ] = array(
				'type'         => 'section',
				'title'        => $definition['title'],
				'description'  => $definition['description'],
				'family'       => $definition['family'],
				'pattern_name' => 'creceweb-lumen-lite/' . $definition['slug'],
				'preview_url'  => $preview_url,
				'use'          => $definition['description'],
				'content'      => $definition['content'],
				'keywords'     => $definition['keywords'],
				'source'       => 'included',
				'source_label' => __( 'Built-in', 'creceweb-lumen-lite' ),
			);
		}


		foreach ( $this->pages->definitions() as $definition ) {
			$preview = isset( $definition['preview'] ) ? sanitize_file_name( (string) $definition['preview'] ) : 'library-placeholder.svg';
			$catalog[ 'page-' . $definition['slug'] ] = array(
				'type'         => 'page',
				'title'        => $definition['title'],
				'description'  => $definition['description'],
				'family'       => '',
				'pattern_name' => 'creceweb-lumen-lite/' . $definition['slug'],
				'preview_url'  => CRECEWEB_LUMEN_LITE_URL . ( 'library-placeholder.svg' === $preview ? 'assets/images/' : 'assets/images/library-previews/' ) . $preview,
				'use'          => __( 'Insert a complete editable page layout and replace its sample content with your own.', 'creceweb-lumen-lite' ),
				'content'      => $definition['content'],
				'keywords'     => $definition['keywords'],
				'template'     => isset( $definition['template'] ) ? (string) $definition['template'] : '',
				'source'       => 'included',
				'source_label' => __( 'Built-in', 'creceweb-lumen-lite' ),
			);
		}

		return $catalog;
	}

	/**
	 * Persists a Library page's recommended Theme template.
	 *
	 * This is generic: the requested template must be registered by the active
	 * Theme for Pages. No Kit or product name is part of the decision.
	 *
	 * @return void
	 */
	public function ajax_apply_page_template(): void {
		check_ajax_referer( 'cw_lumen_lite_apply_page_template', 'nonce' );

		$post_id  = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified above.
		$template = isset( $_POST['template'] ) ? sanitize_text_field( wp_unslash( $_POST['template'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified above.

		$post = $post_id > 0 ? get_post( $post_id ) : null;
		if ( ! $post || 'page' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error(
				array( 'message' => __( 'The recommended page template could not be applied to this content.', 'creceweb-lumen-lite' ) ),
				403
			);
		}

		$templates = wp_get_theme()->get_page_templates( $post, 'page' );
		if ( '' === $template || ! isset( $templates[ $template ] ) ) {
			wp_send_json_error(
				array( 'message' => __( 'The recommended page template is not available in the active Theme.', 'creceweb-lumen-lite' ) ),
				400
			);
		}

		update_post_meta( $post_id, '_wp_page_template', $template );

		wp_send_json_success(
			array(
				'template' => $template,
				'label'    => sanitize_text_field( (string) $templates[ $template ] ),
			)
		);
	}

	/**
	 * Loads the single shared Library browser owned by Lite.
	 * Separately installed extensions can extend the catalog through public Lite
	 * filters without registering a second editor sidebar, command, or modal.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets(): void {
		if ( ! $this->compatibility->is_compatible() || ! function_exists( 'wp_enqueue_script' ) ) {
			return;
		}

		wp_enqueue_style(
			'creceweb-lumen-library-browser',
			CRECEWEB_LUMEN_LITE_URL . 'assets/css/library-browser.css',
			array( 'wp-edit-blocks' ),
			CRECEWEB_LUMEN_LITE_ASSET_VERSION
		);
		wp_enqueue_script(
			'creceweb-lumen-library-browser',
			CRECEWEB_LUMEN_LITE_URL . 'assets/js/library-browser.js',
			array( 'wp-block-editor', 'wp-blocks', 'wp-commands', 'wp-components', 'wp-data', 'wp-editor', 'wp-element', 'wp-notices', 'wp-plugins' ),
			CRECEWEB_LUMEN_LITE_ASSET_VERSION,
			true
		);
		wp_enqueue_script(
			'creceweb-lumen-lite-pattern-controls',
			CRECEWEB_LUMEN_LITE_URL . 'assets/js/pattern-controls.js',
			array( 'wp-block-editor', 'wp-components', 'wp-compose', 'wp-data', 'wp-element', 'wp-hooks' ),
			CRECEWEB_LUMEN_LITE_ASSET_VERSION,
			true
		);
		wp_localize_script(
			'creceweb-lumen-lite-pattern-controls',
			'cwLumenLitePatternControls',
			array(
				'panelTitle'      => __( 'Eyebrow position', 'creceweb-lumen-lite' ),
				'defaultPosition' => __( 'Default', 'creceweb-lumen-lite' ),
				'left'            => __( 'Left', 'creceweb-lumen-lite' ),
				'center'          => __( 'Center', 'creceweb-lumen-lite' ),
				'right'           => __( 'Right', 'creceweb-lumen-lite' ),
				'help'            => __( 'The default option preserves the pattern original alignment.', 'creceweb-lumen-lite' ),
				'notePanelTitle'  => __( 'Aurora note', 'creceweb-lumen-lite' ),
				'noteAccentLabel' => __( 'Accent color', 'creceweb-lumen-lite' ),
				'noteAccentHelp'  => __( 'Changes only the top accent line and Open note link.', 'creceweb-lumen-lite' ),
				'noteAccentReset' => __( 'Use default accent', 'creceweb-lumen-lite' ),
			)
		);

		$patterns = $this->visual_library_patterns();
		$kits     = $this->visual_library_kits( $patterns );
		wp_localize_script(
			'creceweb-lumen-library-browser',
			'cwLumenVisualLibrary',
			array(
				'autoOpen'        => isset( $_GET['cw_lumen_library'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['cw_lumen_library'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only editor shortcut.
				'patterns'        => $patterns,
				'kits'            => $kits,
				'configPreset'    => array(
					'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
					'nonce'       => wp_create_nonce( 'cw_lumen_lite_kit_config_preset' ),
					'hasSnapshot' => $this->kit_presets->has_snapshot(),
				),
				'pageTemplate'   => array(
					'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
					'nonce'         => wp_create_nonce( 'cw_lumen_lite_apply_page_template' ),
					'currentPostId' => get_the_ID(),
					'currentType'   => get_post_type() ?: '',
					'editPostUrl'   => admin_url( 'post.php' ),
				),
				'types'           => array(
					array( 'key' => 'section', 'label' => __( 'Sections', 'creceweb-lumen-lite' ) ),
					array( 'key' => 'page', 'label' => __( 'Pages', 'creceweb-lumen-lite' ) ),
					array( 'key' => 'kit', 'label' => __( 'Kits', 'creceweb-lumen-lite' ) ),
				),
				'families'        => $this->visual_library_families(),
				'sources'         => $this->visual_library_sources( array_merge( $patterns, $kits ) ),
				'commandKeywords' => array( __( 'library', 'creceweb-lumen-lite' ), __( 'Lumen', 'creceweb-lumen-lite' ), __( 'patterns', 'creceweb-lumen-lite' ), __( 'patterns', 'creceweb-lumen-lite' ), __( 'blocks', 'creceweb-lumen-lite' ) ),
				'labels'          => array(
					'open'               => __( 'Lumen Library', 'creceweb-lumen-lite' ),
					'openLibrary'        => __( 'Open visual Library', 'creceweb-lumen-lite' ),
					'sidebarTitle'       => __( 'Visual Library', 'creceweb-lumen-lite' ),
					'sidebarDescription' => __( 'Search and insert Lumen sections and pages, or browse curated Kits.', 'creceweb-lumen-lite' ),
					'patternCount'       => sprintf(
						/* translators: %d: Number of patterns available in the shared Lumen Library. */
						_n( '%d available Library item', '%d available Library items', count( $patterns ) + count( $kits ), 'creceweb-lumen-lite' ),
						count( $patterns ) + count( $kits )
					),
					'quickAccess'        => __( 'Quick access', 'creceweb-lumen-lite' ),
					'commandLabel'       => __( 'Open Lumen Library', 'creceweb-lumen-lite' ),
					'commandHint'        => __( 'You can also open it from the command palette with Ctrl/Cmd + K.', 'creceweb-lumen-lite' ),
					'title'              => __( 'Lumen Library', 'creceweb-lumen-lite' ),
					'searchLabel'        => __( 'Search Library', 'creceweb-lumen-lite' ),
					'searchPlaceholder'  => __( 'E.g. hero, services, contact', 'creceweb-lumen-lite' ),
					'filterLabel'        => __( 'Filter sections', 'creceweb-lumen-lite' ),
					'sourceFilterLabel'  => __( 'Filter by source', 'creceweb-lumen-lite' ),
					'preview'            => __( 'Preview', 'creceweb-lumen-lite' ),
					'insert'             => __( 'Insert', 'creceweb-lumen-lite' ),
					'insertPattern'      => __( 'Insert pattern', 'creceweb-lumen-lite' ),
					'back'               => __( 'Back to the Library', 'creceweb-lumen-lite' ),
					'recommendedUse'     => __( 'Recommended use', 'creceweb-lumen-lite' ),
					'oneResult'          => __( '1 available item', 'creceweb-lumen-lite' ),
					'results'            => __( 'available items', 'creceweb-lumen-lite' ),
					'noResultsTitle'     => __( 'No Library items found', 'creceweb-lumen-lite' ),
					'noResults'          => __( 'Try another search or change the active Library tab.', 'creceweb-lumen-lite' ),
					'sections'           => __( 'Sections', 'creceweb-lumen-lite' ),
					'pages'              => __( 'Pages', 'creceweb-lumen-lite' ),
					'kits'               => __( 'Kits', 'creceweb-lumen-lite' ),
					'insertPage'         => __( 'Insert page', 'creceweb-lumen-lite' ),
					'pageTemplateApplied'=> __( 'Page inserted and the recommended Lumen page template was applied.', 'creceweb-lumen-lite' ),
					'pageTemplateFailed' => __( 'The page was inserted, but the recommended template could not be saved. You can choose it manually from the Page settings.', 'creceweb-lumen-lite' ),
					'pageTemplateHelp'   => __( 'Complete Kit pages can recommend a Lumen page template. Sections never change the current page template.', 'creceweb-lumen-lite' ),
					'pageType'           => __( 'Page', 'creceweb-lumen-lite' ),
					'kitType'            => __( 'Kit', 'creceweb-lumen-lite' ),
					'includedItems'      => __( 'Included Library items', 'creceweb-lumen-lite' ),
					'kitHelp'            => __( 'Kits are curated groups of existing editable Library items. Insert only the pages or sections you need; Kits do not install runtime code.', 'creceweb-lumen-lite' ),
					'individualPages'     => __( 'Insert individual pages', 'creceweb-lumen-lite' ),
					'individualPagesHelp' => __( 'Prefer to build selectively? Insert only the editable pages you need.', 'creceweb-lumen-lite' ),
					'recommendedDesign'   => __( 'Recommended design', 'creceweb-lumen-lite' ),
					'configPresetHelp'    => __( 'The recommended design changes the global portable Lumen configuration and may include clearly marked demo contact or social placeholders. Replace demo destinations before publishing. The previous portable design can be restored afterward.', 'creceweb-lumen-lite' ),
					'applyPreset'         => __( 'Apply recommended design', 'creceweb-lumen-lite' ),
					'insertWithPreset'    => __( 'Insert + recommended design', 'creceweb-lumen-lite' ),
					'insertWithPresetConfirm' => __( 'Insert this page and apply the Kit recommended design? The current portable Lumen configuration will be saved so it can be restored. Demo social/contact destinations included by the Kit must be replaced before publishing.', 'creceweb-lumen-lite' ),
					'restorePreset'       => __( 'Restore previous design', 'creceweb-lumen-lite' ),
					'applyPresetConfirm'  => __( 'Apply this Kit\'s recommended global Theme design? Your current configuration will be saved first.', 'creceweb-lumen-lite' ),
					'restorePresetConfirm'=> __( 'Restore the design configuration saved before the last Kit preset?', 'creceweb-lumen-lite' ),
					'presetExportHelp'    => __( 'After applying it, you can export the Theme section from Lumen Lite → Tools → Import / Export.', 'creceweb-lumen-lite' ),
					'presetRequestError'  => __( 'The design preset request could not be completed.', 'creceweb-lumen-lite' ),
					'reloadEditor'        => __( 'Reload editor', 'creceweb-lumen-lite' ),
					'presetReloadNotice'  => __( 'Reload the editor to refresh the preview with the recommended design changes.', 'creceweb-lumen-lite' ),
					'presetRestoreReloadNotice' => __( 'Reload the editor to refresh the preview with the restored design.', 'creceweb-lumen-lite' ),
					'insertWithPresetReloadNotice' => __( 'The page was inserted and the recommended design was applied. Reload the editor to refresh the preview immediately.', 'creceweb-lumen-lite' ),
					'insertWithPresetPreparing' => __( 'Saving the current page and preparing the recommended design…', 'creceweb-lumen-lite' ),
					'insertWithPresetSaveError' => __( 'The current page could not be saved before reloading, so the combined Kit action was stopped.', 'creceweb-lumen-lite' ),
					'insertWithPresetSuccess' => __( 'Page inserted with the recommended design.', 'creceweb-lumen-lite' ),
					'insertWithPresetAutoError' => __( 'The editor reloaded with the recommended design, but the selected page could not be inserted automatically. Open Lumen Library and insert it again.', 'creceweb-lumen-lite' ),
					'parseError'         => __( 'The pattern could not be prepared.', 'creceweb-lumen-lite' ),
					'insertError'        => __( 'The pattern could not be inserted at this location. Select an editable content area.', 'creceweb-lumen-lite' ),
					'insertSuccess'      => __( 'Pattern inserted successfully.', 'creceweb-lumen-lite' ),
				),
			)
		);
	}

	/** @return array<int,array<string,mixed>> */
	private function visual_library_patterns(): array {
		$families = function_exists( 'cw_lumen_lite_library_families' ) ? cw_lumen_lite_library_families() : array();
		$items    = function_exists( 'cw_lumen_lite_library_catalog' ) ? cw_lumen_lite_library_catalog() : array();
		$patterns = array();

		foreach ( $items as $item ) {
			$content = (string) ( $item['content'] ?? '' );
			if ( '' === $content ) {
				continue;
			}
			$type         = isset( $item['type'] ) && 'page' === (string) $item['type'] ? 'page' : 'section';
			$family       = sanitize_key( (string) ( $item['family'] ?? 'content' ) );
			$source       = 'included' === (string) ( $item['source'] ?? 'extension' ) ? 'included' : 'extension';
			$source_label = sanitize_text_field( (string) ( $item['source_label'] ?? '' ) );
			if ( '' === $source_label ) {
				$source_label = 'included' === $source
					? __( 'Built-in', 'creceweb-lumen-lite' )
					: __( 'Extension', 'creceweb-lumen-lite' );
			}

			$patterns[] = array(
				'slug'        => sanitize_key( (string) ( $item['id'] ?? '' ) ),
				'type'        => $type,
				'title'       => (string) ( $item['title'] ?? '' ),
				'description' => (string) ( $item['description'] ?? '' ),
				'use'         => (string) ( $item['use'] ?? $item['description'] ?? '' ),
				'family'      => $family,
				'familyLabel' => 'page' === $type ? __( 'Page', 'creceweb-lumen-lite' ) : (string) ( $families[ $family ]['label'] ?? ucfirst( $family ) ),
				'keywords'    => (array) ( $item['keywords'] ?? array() ),
				'preview'     => (string) ( $item['preview_url'] ?? CRECEWEB_LUMEN_LITE_URL . 'assets/images/library-placeholder.svg' ),
				'content'     => $content,
				'template'    => 'page' === $type ? sanitize_text_field( (string) ( $item['template'] ?? '' ) ) : '',
				'source'      => $source,
				'sourceLabel' => $source_label,
			);
		}

		return $patterns;
	}



	/**
	 * @param array<int,array<string,mixed>> $patterns Visible Library items.
	 * @return array<int,array<string,mixed>>
	 */
	private function visual_library_kits( array $patterns ): array {
		$available = array();
		foreach ( $patterns as $pattern ) {
			$id = sanitize_key( (string) ( $pattern['slug'] ?? '' ) );
			if ( '' !== $id ) {
				$available[ $id ] = true;
			}
		}

		$kits = array();
		foreach ( $this->kits->items() as $kit ) {
			$items = array_values(
				array_filter(
					(array) ( $kit['items'] ?? array() ),
					static fn( $id ): bool => isset( $available[ sanitize_key( (string) $id ) ] )
				)
			);
			if ( empty( $items ) ) {
				continue;
			}

			$source       = 'included' === (string) ( $kit['source'] ?? 'extension' ) ? 'included' : 'extension';
			$source_label = sanitize_text_field( (string) ( $kit['source_label'] ?? '' ) );
			if ( '' === $source_label ) {
				$source_label = 'included' === $source ? __( 'Built-in', 'creceweb-lumen-lite' ) : __( 'Extension', 'creceweb-lumen-lite' );
			}

			$kits[] = array(
				'slug'        => sanitize_key( (string) ( $kit['id'] ?? '' ) ),
				'type'        => 'kit',
				'title'       => (string) ( $kit['title'] ?? '' ),
				'description' => (string) ( $kit['description'] ?? '' ),
				'use'         => (string) ( $kit['use'] ?? '' ),
				'keywords'    => (array) ( $kit['keywords'] ?? array() ),
				'preview'     => (string) ( $kit['preview_url'] ?? CRECEWEB_LUMEN_LITE_URL . 'assets/images/library-placeholder.svg' ),
				'items'       => array_map( 'sanitize_key', $items ),
				'hasConfigPreset' => $this->kit_presets->kit_has_preset( sanitize_key( (string) ( $kit['id'] ?? '' ) ) ),
				'source'      => $source,
				'sourceLabel' => $source_label,
			);
		}

		return $kits;
	}

	/** @return array<int,array<string,string>> */
	private function visual_library_families(): array {
		$families = array( array( 'key' => 'all', 'label' => __( 'All', 'creceweb-lumen-lite' ) ) );
		foreach ( cw_lumen_lite_library_families() as $key => $family ) {
			$families[] = array( 'key' => sanitize_key( (string) $key ), 'label' => (string) ( $family['label'] ?? $key ) );
		}
		return $families;
	}

	/**
	 * @param array<int,array<string,mixed>> $patterns Visible patterns.
	 * @return array<int,array<string,mixed>>
	 */
	private function visual_library_sources( array $patterns ): array {
		$groups = array();
		foreach ( $patterns as $pattern ) {
			$source = 'included' === (string) ( $pattern['source'] ?? 'extension' ) ? 'included' : 'extension';
			if ( ! isset( $groups[ $source ] ) ) {
				$groups[ $source ] = array(
					'key'   => $source,
					'label' => (string) ( $pattern['sourceLabel'] ?? ( 'included' === $source ? __( 'Built-in', 'creceweb-lumen-lite' ) : __( 'Extension', 'creceweb-lumen-lite' ) ) ),
					'count' => 0,
				);
			}
			++$groups[ $source ]['count'];
		}

		$sources = array(
			array(
				'key'   => 'all',
				'label' => __( 'All sources', 'creceweb-lumen-lite' ),
				'count' => count( $patterns ),
			),
		);
		foreach ( array( 'included', 'extension' ) as $source ) {
			if ( isset( $groups[ $source ] ) && $groups[ $source ]['count'] > 0 ) {
				$sources[] = $groups[ $source ];
			}
		}

		return $sources;
	}

	/**
	 * Discover Lite-owned Kit style profiles from local CSS files.
	 *
	 * The Library engine does not know product names. A profile named `example.css`
	 * is activated only when the current content contains
	 * `cw-lumen-kit-profile--example`.
	 *
	 * @return array<string,array{path:string,url:string,marker:string}>
	 */
	public function kit_style_profiles(): array {
		static $profiles = null;

		if ( is_array( $profiles ) ) {
			return $profiles;
		}

		$profiles  = array();
		$directory = CRECEWEB_LUMEN_LITE_DIR . 'assets/css/library-kits/profiles/';
		foreach ( glob( $directory . '*.css' ) ?: array() as $file ) {
			$id = sanitize_key( (string) pathinfo( $file, PATHINFO_FILENAME ) );
			if ( '' === $id ) {
				continue;
			}
			$profiles[ $id ] = array(
				'path'   => $file,
				'url'    => CRECEWEB_LUMEN_LITE_URL . 'assets/css/library-kits/profiles/' . $id . '.css',
				'marker' => 'cw-lumen-kit-profile--' . $id,
			);
		}

		ksort( $profiles );
		return $profiles;
	}

	/** @return bool */
	public function needs_kit_component_assets(): bool {
		return $this->post_contains_any_marker( array( 'cw-lumen-kit-component' ) );
	}

	/**
	 * @return array<string,array{path:string,url:string,marker:string}>
	 */
	public function active_kit_style_profiles(): array {
		$active = array();
		foreach ( $this->kit_style_profiles() as $id => $profile ) {
			if ( $this->post_contains_any_marker( array( $profile['marker'] ) ) ) {
				$active[ $id ] = $profile;
			}
		}
		return $active;
	}

	/** @return bool */
	public function needs_assets(): bool {
		return $this->post_contains_any_marker( $this->asset_markers() );
	}

	/**
	 * Whether the current frontend post contains one of the historical Pro
	 * pattern class names that Lite can preserve when the Pro add-on is absent.
	 *
	 * @return bool
	 */
	public function needs_legacy_pro_assets(): bool {
		return $this->post_contains_any_marker( $this->legacy_pro_asset_markers() );
	}

	/**
	 * @param array<int,string> $markers Class-name fragments to search for.
	 * @return bool
	 */
	private function post_contains_any_marker( array $markers ): bool {
		if ( ! $this->compatibility->is_compatible() ) {
			return false;
		}

		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		$content = (string) $post->post_content;
		foreach ( $markers as $marker ) {
			if ( str_contains( $content, $marker ) ) {
				return true;
			}
		}

		return false;
	}

	/** @return array<string,array<string,string>> */
	private function families(): array {
		return array(
			'heroes'     => array( 'label' => __( 'Presentations', 'creceweb-lumen-lite' ), 'description' => __( 'Prominent headings to present your value proposition.', 'creceweb-lumen-lite' ) ),
			'services'    => array( 'label' => __( 'Services', 'creceweb-lumen-lite' ), 'description' => __( 'Services, benefits, and value propositions.', 'creceweb-lumen-lite' ) ),
			'content'    => array( 'label' => __( 'Content', 'creceweb-lumen-lite' ), 'description' => __( 'Work stages and answers for your visitors.', 'creceweb-lumen-lite' ) ),
			'lumen-posts' => array( 'label' => __( 'Lumen Posts', 'creceweb-lumen-lite' ), 'description' => __( 'Editable post grids and lists powered by the Lumen Posts block.', 'creceweb-lumen-lite' ) ),
			'credibility' => array( 'label' => __( 'Confidence', 'creceweb-lumen-lite' ), 'description' => __( 'Customer testimonials and highlighted results.', 'creceweb-lumen-lite' ) ),
			'conversion'  => array( 'label' => __( 'Conversion', 'creceweb-lumen-lite' ), 'description' => __( 'Sections for inviting an inquiry or displaying your contact details.', 'creceweb-lumen-lite' ) ),
		);
	}

	/** @return array<string,array<string,mixed>> */
	private function definitions(): array {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();

		if ( null !== $this->definitions && $locale === $this->definitions_locale ) {
			return $this->definitions;
		}

		$this->definitions        = array();
		$this->definitions_locale = $locale;
		$directory = CRECEWEB_LUMEN_LITE_DIR . 'src/Library/patterns/';
		foreach ( glob( $directory . '*.php' ) ?: array() as $file ) {
			$definition = require $file;
			if ( ! is_array( $definition ) ) {
				continue;
			}

			$slug = sanitize_key( (string) ( $definition['slug'] ?? '' ) );
			if ( '' === $slug || empty( $definition['content'] ) ) {
				continue;
			}

			$definition['slug']        = $slug;
			$definition['family']      = sanitize_key( (string) ( $definition['family'] ?? 'content' ) );
			$definition['title']       = sanitize_text_field( (string) ( $definition['title'] ?? $slug ) );
			$definition['description'] = sanitize_text_field( (string) ( $definition['description'] ?? '' ) );
			$definition['preview']     = isset( $definition['preview'] ) ? sanitize_file_name( (string) $definition['preview'] ) : '';
			$definition['keywords']    = isset( $definition['keywords'] ) && is_array( $definition['keywords'] ) ? array_values( array_filter( array_map( 'sanitize_text_field', $definition['keywords'] ) ) ) : array();
			$this->definitions[ $slug ] = $definition;
		}

		ksort( $this->definitions );
		return $this->definitions;
	}

	/** @return array<int,string> */
	private function asset_markers(): array {
		return array(
			'cw-lumen-lite-pattern',
		);
	}

	/** @return array<int,string> */
	private function legacy_pro_asset_markers(): array {
		return array(
			'cw-lumen-pro-hero--base',
			'cw-lumen-pro-pattern--services-3',
			'cw-lumen-pro-pattern--benefits',
			'cw-lumen-pro-pattern--process',
			'cw-lumen-pro-pattern--faq-simple',
			'cw-lumen-pro-pattern--cta-centered',
		);
	}
}
