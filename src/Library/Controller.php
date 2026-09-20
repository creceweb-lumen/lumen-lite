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

	public function __construct( private Compatibility $compatibility ) {
		$this->pages = new PageCatalog();
	}

	/** @return void */
	public function register(): void {
		add_action( 'init', array( $this, 'register_patterns' ), 20 );
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
			$catalog[ $definition['slug'] ] = array(
				'type'         => 'section',
				'title'        => $definition['title'],
				'description'  => $definition['description'],
				'family'       => $definition['family'],
				'pattern_name' => 'creceweb-lumen-lite/' . $definition['slug'],
				'preview_url'  => CRECEWEB_LUMEN_LITE_URL . 'assets/images/library-previews/' . $definition['slug'] . '.avif',
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
				'source'       => 'included',
				'source_label' => __( 'Built-in', 'creceweb-lumen-lite' ),
			);
		}

		return $catalog;
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
			)
		);

		$patterns = $this->visual_library_patterns();
		wp_localize_script(
			'creceweb-lumen-library-browser',
			'cwLumenVisualLibrary',
			array(
				'autoOpen'        => isset( $_GET['cw_lumen_library'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['cw_lumen_library'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only editor shortcut.
				'patterns'        => $patterns,
				'types'           => array(
					array( 'key' => 'section', 'label' => __( 'Sections', 'creceweb-lumen-lite' ) ),
					array( 'key' => 'page', 'label' => __( 'Pages', 'creceweb-lumen-lite' ) ),
				),
				'families'        => $this->visual_library_families(),
				'sources'         => $this->visual_library_sources( $patterns ),
				'commandKeywords' => array( __( 'library', 'creceweb-lumen-lite' ), __( 'Lumen', 'creceweb-lumen-lite' ), __( 'patterns', 'creceweb-lumen-lite' ), __( 'patterns', 'creceweb-lumen-lite' ), __( 'blocks', 'creceweb-lumen-lite' ) ),
				'labels'          => array(
					'open'               => __( 'Lumen Library', 'creceweb-lumen-lite' ),
					'openLibrary'        => __( 'Open visual Library', 'creceweb-lumen-lite' ),
					'sidebarTitle'       => __( 'Visual Library', 'creceweb-lumen-lite' ),
					'sidebarDescription' => __( 'Search and insert Lumen sections and complete pages.', 'creceweb-lumen-lite' ),
					'patternCount'       => sprintf(
						/* translators: %d: Number of patterns available in the shared Lumen Library. */
						_n( '%d available Library item', '%d available Library items', count( $patterns ), 'creceweb-lumen-lite' ),
						count( $patterns )
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
					'insertPage'         => __( 'Insert page', 'creceweb-lumen-lite' ),
					'pageType'           => __( 'Page', 'creceweb-lumen-lite' ),
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
				'source'      => $source,
				'sourceLabel' => $source_label,
			);
		}

		return $patterns;
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
