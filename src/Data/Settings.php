<?php
/**
 * Lite-only option persistence.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Data;

use CreceWeb\LumenLite\PostsGrid\Config as PostsGridConfig;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores only Lumen Lite preferences.
 */
final class Settings {
	public const OPTION_KEY = 'cw_lumen_lite_settings';
	public const SCHEMA = 32;

	/** @var array<string,mixed>|null Request-local normalized settings cache. */
	private static ?array $cache = null;

	/** @return string */
	public static function default_footer_credit(): string {
		return __( '© {year} {site_name} · All rights reserved', 'creceweb-lumen-lite' );
	}

	/** @return void */
	public static function install(): void {
		$existing = get_option( self::OPTION_KEY, false );

		if ( false === $existing ) {
			$defaults = self::defaults();
			// A missing option can mean an interrupted/duplicate uninstall. Rebuild
			// derived menu state once from the surviving nav-menu item metadata.
			$defaults['menu_customizations_needs_reindex'] = true;
			add_option( self::OPTION_KEY, $defaults, '', 'no' );
			self::$cache = null;
			return;
		}

		if ( is_array( $existing ) ) {
			$stored_schema = absint( $existing['schema_version'] ?? 0 );
			if ( $stored_schema < 9 ) {
				delete_option( 'cw_lumen_lite_starter_site_imports' );
			}
			$existing   = self::migrate( $existing );
			$normalized = self::sanitize( wp_parse_args( $existing, self::defaults() ) );
			if ( $normalized !== $existing ) {
				update_option( self::OPTION_KEY, $normalized, false );
			}
		}
	}

	/** @return array<string,mixed> */
	public static function get(): array {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$stored  = get_option( self::OPTION_KEY, false );
		$missing = false === $stored;
		$value   = is_array( $stored ) ? $stored : array();
		$value   = self::migrate( $value );
		$value   = wp_parse_args( $value, self::defaults() );

		if ( $missing ) {
			$value['menu_customizations_needs_reindex'] = true;
		}

		self::$cache = self::sanitize( $value );

		return self::$cache;
	}

	/**
	 * @param array<string,mixed> $value Settings.
	 * @return void
	 */
	public static function update( array $value ): void {
		$normalized = self::sanitize( wp_parse_args( $value, self::defaults() ) );
		update_option( self::OPTION_KEY, $normalized, false );
		self::$cache = $normalized;
	}

	/**
	 * @param string $text Footer credit template.
	 * @return void
	 */
	public static function update_footer_credit( string $text ): void {
		$settings = self::get();
		$settings['footer_credit_text'] = $text;
		self::update( $settings );
	}

	/**
	 * @param bool $has_custom_items Whether at least one menu item has Lite options.
	 * @return void
	 */
	public static function update_menu_item_customizations( bool $has_custom_items ): void {
		$settings = self::get();
		$settings['menu_has_item_customizations']   = $has_custom_items;
		$settings['menu_customizations_needs_reindex'] = false;
		self::update( $settings );
	}


	/**
	 * @param array<string,mixed> $content Content preferences.
	 * @return void
	 */
	public static function update_content( array $content ): void {
		$settings = self::get();
		$settings['content'] = $content;
		self::update( $settings );
	}

	/**
	 * @param array<string,mixed> $breadcrumbs Breadcrumb preferences.
	 * @return void
	 */
	public static function update_breadcrumbs( array $breadcrumbs ): void {
		$settings = self::get();
		$settings['breadcrumbs'] = $breadcrumbs;
		self::update( $settings );
	}

	/**
	 * @param array<string,mixed> $reading_progress Reading-progress preferences.
	 * @return void
	 */
	public static function update_reading_progress( array $reading_progress ): void {
		$settings = self::get();
		$settings['reading_progress'] = $reading_progress;
		self::update( $settings );
	}

	/**
	 * @param array<string,mixed> $table_of_contents Table of Contents preferences.
	 * @return void
	 */
	public static function update_table_of_contents( array $table_of_contents ): void {
		$settings = self::get();
		$settings['table_of_contents'] = $table_of_contents;
		self::update( $settings );
	}


	/**
	 * @param array<string,mixed> $sharing Sharing preferences.
	 * @return void
	 */
	public static function update_sharing( array $sharing ): void {
		$settings = self::get();
		$settings['sharing'] = $sharing;
		self::update( $settings );
	}

	/**
	 * @param array<string,mixed> $related_content Related-content preferences.
	 * @return void
	 */
	public static function update_related_content( array $related_content ): void {
		$settings = self::get();
		$settings['related_content'] = $related_content;
		self::update( $settings );
	}


	/**
	 * @param array<string,mixed> $popular_content Popular-content preferences.
	 * @return void
	 */
	public static function update_popular_content( array $popular_content ): void {
		$settings = self::get();
		$settings['popular_content'] = $popular_content;
		self::update( $settings );
		do_action( 'creceweb_lumen_lite_popular_content_settings_updated' );
	}

	/** @param array<string,mixed> $posts_grid Posts Grid defaults. @return void */
	public static function update_posts_grid( array $posts_grid ): void {
		$settings = self::get();
		$settings['posts_grid'] = $posts_grid;
		self::update( $settings );
	}

	/**
	 * @param array<string,mixed> $color_mode Color-mode preferences.
	 * @return void
	 */
	public static function update_color_mode( array $color_mode ): void {
		$settings = self::get();
		$settings['color_mode'] = $color_mode;
		self::update( $settings );
	}

	/**
	 * @param array<string,mixed> $search_modal Search-modal preferences.
	 * @return void
	 */
	public static function update_search_modal( array $search_modal ): void {
		$settings = self::get();
		$settings['search_modal'] = $search_modal;
		self::update( $settings );
	}

	/**
	 * @param array<string,mixed> $floating_action Global floating-action preferences.
	 * @return void
	 */
	public static function update_floating_action( array $floating_action ): void {
		$settings = self::get();
		$settings['floating_action'] = $floating_action;
		self::update( $settings );
	}


	/**
	 * @param array<string,mixed> $messaging Global Messaging preferences.
	 * @return void
	 */
	public static function update_messaging( array $messaging ): void {
		$settings = self::get();
		$settings['messaging']                    = $messaging;
		$settings['messaging_migration_complete'] = true;
		$settings['messaging_migration_source']   = 'lite-messaging';
		self::update( $settings );
	}

	/**
	 * Legacy WhatsApp update adapter retained for one compatibility cycle.
	 *
	 * @param array<string,mixed> $whatsapp Global WhatsApp preferences.
	 * @return void
	 */
	public static function update_whatsapp( array $whatsapp ): void {
		$settings = self::get();
		$settings['whatsapp'] = $whatsapp;
		self::update( $settings );
	}

	/**
	 * Stores the result of the one-time Theme WhatsApp migration.
	 *
	 * @param array<string,mixed> $whatsapp Migrated WhatsApp preferences.
	 * @param string              $source Migration source identifier.
	 * @return void
	 */
	public static function complete_whatsapp_migration( array $whatsapp, string $source ): void {
		$settings = self::get();
		$settings['whatsapp']                    = $whatsapp;
		$settings['whatsapp_migration_complete'] = true;
		$settings['whatsapp_migration_source']   = sanitize_key( $source );
		self::update( $settings );
	}

	/**
	 * Migrates exact legacy defaults while preserving user-authored footer text.
	 *
	 * @param array<string,mixed> $value Stored settings.
	 * @return array<string,mixed>
	 */
	private static function migrate( array $value ): array {
		$schema = absint( $value['schema_version'] ?? 0 );

		if ( $schema < 8 && isset( $value['footer_credit_text'] ) ) {
			$legacy_footer_hash = '48bbe599fe2532fecdd2bb63d7e0acd8bbc669e8ce535d28b3b4828c515b8231';
			if ( hash_equals( $legacy_footer_hash, hash( 'sha256', trim( (string) $value['footer_credit_text'] ) ) ) ) {
				$value['footer_credit_text'] = self::default_footer_credit();
			}
		}

		if ( $schema < 10 && isset( $value['floating_action'] ) && is_array( $value['floating_action'] ) ) {
			if ( 'back_to_top' === sanitize_key( (string) ( $value['floating_action']['action'] ?? '' ) ) ) {
				$value['floating_action']['enabled']    = false;
				$value['floating_action']['action']     = 'element';
				$value['floating_action']['element_id'] = '';
			}
		}

		if ( $schema < 22 && empty( $value['messaging_migration_complete'] ) ) {
			$legacy = isset( $value['whatsapp'] ) && is_array( $value['whatsapp'] ) ? $value['whatsapp'] : array();
			$has_legacy = ! empty( $legacy['enabled'] )
				|| '' !== trim( (string) ( $legacy['number'] ?? '' ) )
				|| '' !== trim( (string) ( $legacy['message'] ?? '' ) );

			$appearance_mode = ( $schema > 0 || array_key_exists( 'whatsapp', $value ) ) ? 'custom' : 'provider';

			$value['messaging'] = array(
				'enabled'         => ! empty( $legacy['enabled'] ),
				'provider'        => 'whatsapp',
				'appearance_mode' => $appearance_mode,
				'providers' => array(
					'whatsapp' => array(
						'number'  => (string) ( $legacy['number'] ?? '' ),
						'message' => (string) ( $legacy['message'] ?? '' ),
					),
					'telegram'  => array( 'username' => '', 'message' => '' ),
					'messenger' => array( 'username' => '' ),
					'signal'    => array( 'url' => '' ),
				),
				'background_color' => (string) ( $legacy['background_color'] ?? '#0f172a' ),
				'icon_color'       => (string) ( $legacy['icon_color'] ?? '#ffffff' ),
				'size'             => $legacy['size'] ?? 46,
				'position'         => (string) ( $legacy['position'] ?? 'right' ),
				'devices'          => isset( $legacy['devices'] ) && is_array( $legacy['devices'] )
					? $legacy['devices']
					: array( 'desktop' => true, 'tablet' => true, 'mobile' => true ),
			);
			$value['messaging_migration_complete'] = true;
			$value['messaging_migration_source']   = $has_legacy ? 'lite-whatsapp' : 'fresh';
		}

		if ( $schema < 23 ) {
			$messaging = isset( $value['messaging'] ) && is_array( $value['messaging'] ) ? $value['messaging'] : array();
			if ( ! isset( $messaging['appearance_mode'] ) ) {
				// Existing Messaging installs keep their current colors after upgrade.
				$messaging['appearance_mode'] = 'custom';
			}
			$value['messaging'] = $messaging;
		}

		return $value;
	}

	/**
	 * Returns every public post type currently registered.
	 *
	 * The list is used as the initial selection for content tools that operate
	 * on singular public content. Saved choices remain free-form sanitized slugs
	 * so Lite never hard-codes an artificial post-type capability limit.
	 *
	 * @return array<int,string>
	 */
	private static function default_public_post_types(): array {
		$post_types = function_exists( 'get_post_types' )
			? get_post_types( array( 'public' => true ), 'names' )
			: array( 'post', 'page' );
		$post_types = is_array( $post_types ) ? array_values( $post_types ) : array( 'post', 'page' );

		return array_values( array_unique( array_filter( array_map( 'sanitize_key', $post_types ) ) ) );
	}

	/**
	 * Returns public post types eligible for automatic Related Content.
	 *
	 * Capability follows WordPress' public post-type registration instead of a
	 * product-specific allow/deny list, so Lite does not artificially restrict
	 * a generic local feature that can operate on any public singular content.
	 *
	 * @return array<int,string>
	 */
	private static function default_related_post_types(): array {
		return self::default_public_post_types();
	}

	/** @return array<string,mixed> */
	private static function defaults(): array {
		return array(
			'schema_version'                 => self::SCHEMA,
			'installed_version'              => CRECEWEB_LUMEN_LITE_VERSION,
			'onboarding_dismissed'           => false,
			'footer_credit_text'             => self::default_footer_credit(),
			'menu_has_item_customizations'      => false,
			'menu_customizations_needs_reindex' => false,
			'content'                        => array(
				'reading_time_enabled'   => false,
				'excerpt_length_enabled' => false,
				'excerpt_length'         => 30,
			),
			'breadcrumbs'                    => array(
				'enabled'          => false,
				'home_label'       => __( 'Home', 'creceweb-lumen-lite' ),
				'separator'        => '›',
				'background_style' => 'auto',
				'show_border'      => true,
				'show_home'        => true,
				'show_current'     => true,
				'schema_enabled'   => true,
			),
			'reading_progress'               => array(
				'enabled'    => false,
				'position'   => 'top',
				'thickness'   => 4,
				'color'       => '#10b981',
				'track_color' => '#e2e8f0',
				'post_types' => self::default_public_post_types(),
				'devices'    => array(
					'desktop' => true,
					'tablet'  => true,
					'mobile'  => true,
				),
			),
			'table_of_contents'              => array(
				'enabled'          => false,
				'title'            => __( 'Table of contents', 'creceweb-lumen-lite' ),
				'position'         => 'before_first_heading',
				'style'            => 'boxed',
				'minimum_headings' => 3,
				'heading_levels'   => array( 'h2', 'h3' ),
				'post_types'       => self::default_public_post_types(),
				'text_color'       => '',
				'link_color'       => '',
				'link_hover_color' => '',
				'background_color' => '',
				'border_color'     => '',
			),
			'sharing'                        => array(
				'enabled'                  => false,
				'position'                 => 'after',
				'show_labels'              => true,
				'minimal_style'             => false,
				'post_types'               => self::default_public_post_types(),
				'button_size'              => 40,
				'icon_size'                => 20,
				'border_width'             => 1,
				'border_radius'            => 8,
				'text_color'               => '',
				'text_hover_color'         => '',
				'background_color'         => '',
				'background_hover_color'   => '',
				'border_color'             => '',
				'border_hover_color'       => '',
				'actions'                  => array(
					'copy'     => true,
					'whatsapp' => true,
					'linkedin' => true,
					'facebook' => true,
					'email'    => true,
				),
			),
			'related_content'                => array(
				'enabled'            => false,
				'title'              => __( 'Related content', 'creceweb-lumen-lite' ),
				'count'              => 3,
				'columns_desktop'    => 3,
				'columns_tablet'     => 2,
				'columns_mobile'     => 1,
				'image_ratio_width'  => 16.0,
				'image_ratio_height' => 9.0,
				'card_gap'           => '',
				'card_style'         => 'default',
				'show_image'         => true,
				'show_excerpt'       => true,
				'show_date'          => false,
				'post_types'         => self::default_related_post_types(),
			),
			'popular_content'                => array(
				'enabled'            => false,
				'title'              => __( 'Popular content', 'creceweb-lumen-lite' ),
				'selection_mode'     => 'automatic',
				'items'              => 4,
				'period'             => 'all',
				'content_types'      => array_values( array_diff( self::default_public_post_types(), array( 'attachment' ) ) ),
				'manual_ids'         => array(),
				'taxonomy'           => '',
				'term_ids'           => array(),
				'singular_position'  => 'after',
				'show_on_singular'   => true,
				'devices'            => array(
					'desktop' => true,
					'tablet'  => true,
					'mobile'  => true,
				),
				'show_image'         => true,
				'show_taxonomy'      => true,
				'show_date'          => false,
				'show_excerpt'       => false,
				'columns_desktop'    => 4,
				'columns_tablet'     => 2,
				'columns_mobile'     => 1,
				'image_ratio_width'  => 16.0,
				'image_ratio_height' => 9.0,
				'card_gap'           => '',
				'card_style'         => 'default',
			),
			'posts_grid'                     => PostsGridConfig::defaults(),
			'color_mode'                     => array(
				'enabled'      => false,
				'default_mode' => 'system',
				'show_switch'      => true,
				'show_menu_switch' => false,
				'position'     => 'left',
				'devices'      => array(
					'desktop' => true,
					'tablet'  => true,
					'mobile'  => true,
				),
				'palette'      => array(
					'primary'    => '#334155',
					'accent'     => '#34d399',
					'background' => '#0f172a',
					'surface'    => '#111827',
					'text'       => '#e5e7eb',
					'heading'    => '#f8fafc',
					'link'       => '#6ee7b7',
					'border'     => '#334155',
				),
			),
			'search_modal'                   => array(
				'enabled'           => false,
				'show_menu_trigger' => true,
				'placeholder'       => __( 'Search…', 'creceweb-lumen-lite' ),
			),
			'floating_action'                => array(
				'enabled'         => false,
				'action'          => 'element',
				'content'         => 'both',
				'shape'           => 'pill',
				'icon'            => 'arrow-right',
				'label'            => '',
				'element_id'       => '',
				'url'              => '',
				'size'             => 46,
				'background_color' => '#0f172a',
				'background_hover' => '#1e293b',
				'icon_color'       => '#ffffff',
				'icon_hover'       => '#ffffff',
				'text_color'       => '#ffffff',
				'text_hover'       => '#ffffff',
				'position'         => 'right',
				'scroll_offset'   => 300,
				'bottom_offset'   => 16,
				'side_offset'     => 16,
				'opacity'         => 100,
				'auto_hide'       => false,
				'auto_hide_delay' => 4,
				'smooth_scroll'   => true,
				'devices'         => array(
					'desktop' => true,
					'tablet'  => true,
					'mobile'  => true,
				),
			),
			'whatsapp'                       => array(
				'enabled'          => false,
				'number'           => '',
				'message'          => '',
				'background_color' => '#0f172a',
				'icon_color'       => '#ffffff',
				'size'             => 46,
				'position'         => 'right',
				'devices'          => array(
					'desktop' => true,
					'tablet'  => true,
					'mobile'  => true,
				),
			),
			'whatsapp_migration_complete'    => false,
			'whatsapp_migration_source'      => '',
			'messaging'                      => array(
				'enabled'         => false,
				'provider'        => 'whatsapp',
				'appearance_mode' => 'provider',
				'providers' => array(
					'whatsapp' => array( 'number' => '', 'message' => '' ),
					'telegram' => array( 'username' => '', 'message' => '' ),
					'messenger' => array( 'username' => '' ),
					'signal' => array( 'url' => '' ),
				),
				'background_color' => '#0f172a',
				'icon_color'       => '#ffffff',
				'size'             => 46,
				'position'         => 'right',
				'devices'          => array(
					'desktop' => true,
					'tablet'  => true,
					'mobile'  => true,
				),
			),
			'messaging_migration_complete'   => false,
			'messaging_migration_source'     => '',
			'legacy_contexts'                => array(),
			'legacy_menu_presentation'       => array(),
		);
	}

	/**
	 * @param array<string,mixed> $value Raw settings.
	 * @return array<string,mixed>
	 */
	private static function sanitize( array $value ): array {
		$footer_credit = isset( $value['footer_credit_text'] ) ? sanitize_text_field( (string) $value['footer_credit_text'] ) : self::default_footer_credit();

		$legacy_contexts = array();
		if ( isset( $value['legacy_contexts'] ) && is_array( $value['legacy_contexts'] ) ) {
			$legacy_contexts = $value['legacy_contexts'];
		} elseif ( isset( $value['contexts'] ) && is_array( $value['contexts'] ) ) {
			$legacy_contexts = $value['contexts'];
		}

		$legacy_menu = array();
		if ( isset( $value['legacy_menu_presentation'] ) && is_array( $value['legacy_menu_presentation'] ) ) {
			$legacy_menu = $value['legacy_menu_presentation'];
		} elseif ( isset( $value['menu'] ) && is_array( $value['menu'] ) ) {
			$legacy_menu = $value['menu'];
		}

		$content = isset( $value['content'] ) && is_array( $value['content'] ) ? $value['content'] : array();
		$excerpt_length = max( 1, absint( $content['excerpt_length'] ?? 30 ) );

		$breadcrumbs = isset( $value['breadcrumbs'] ) && is_array( $value['breadcrumbs'] ) ? $value['breadcrumbs'] : array();
		$breadcrumbs_home_label = sanitize_text_field( (string) ( $breadcrumbs['home_label'] ?? __( 'Home', 'creceweb-lumen-lite' ) ) );
		if ( '' === trim( $breadcrumbs_home_label ) ) {
			$breadcrumbs_home_label = __( 'Home', 'creceweb-lumen-lite' );
		}
		$breadcrumbs_separator = sanitize_text_field( (string) ( $breadcrumbs['separator'] ?? '›' ) );
		$breadcrumbs_background_style = sanitize_key( (string) ( $breadcrumbs['background_style'] ?? 'auto' ) );
		if ( ! in_array( $breadcrumbs_background_style, array( 'auto', 'surface', 'background' ), true ) ) {
			$breadcrumbs_background_style = 'auto';
		}

		$reading_progress = isset( $value['reading_progress'] ) && is_array( $value['reading_progress'] ) ? $value['reading_progress'] : array();
		$reading_progress_position = sanitize_key( (string) ( $reading_progress['position'] ?? 'top' ) );
		if ( ! in_array( $reading_progress_position, array( 'top', 'bottom' ), true ) ) {
			$reading_progress_position = 'top';
		}
		$reading_progress_thickness = $reading_progress['thickness'] ?? 4;
		$reading_progress_thickness = is_numeric( $reading_progress_thickness ) ? max( 0, (float) $reading_progress_thickness ) : 4.0;
		$reading_progress_color = sanitize_hex_color( (string) ( $reading_progress['color'] ?? '#10b981' ) ) ?: '#10b981';
		$reading_progress_track_color = sanitize_hex_color( (string) ( $reading_progress['track_color'] ?? '#e2e8f0' ) ) ?: '#e2e8f0';
		$reading_progress_post_types = isset( $reading_progress['post_types'] ) && is_array( $reading_progress['post_types'] )
			? array_values( array_unique( array_filter( array_map( 'sanitize_key', $reading_progress['post_types'] ) ) ) )
			: self::default_public_post_types();
		$reading_progress_devices = isset( $reading_progress['devices'] ) && is_array( $reading_progress['devices'] ) ? $reading_progress['devices'] : array();
		$reading_progress_device_flags = array(
			'desktop' => ! empty( $reading_progress_devices['desktop'] ),
			'tablet'  => ! empty( $reading_progress_devices['tablet'] ),
			'mobile'  => ! empty( $reading_progress_devices['mobile'] ),
		);

		$table_of_contents = isset( $value['table_of_contents'] ) && is_array( $value['table_of_contents'] ) ? $value['table_of_contents'] : array();
		$toc_position = sanitize_key( (string) ( $table_of_contents['position'] ?? 'before_first_heading' ) );
		if ( ! in_array( $toc_position, array( 'before_first_heading', 'after_first_paragraph' ), true ) ) {
			$toc_position = 'before_first_heading';
		}
		$toc_style = sanitize_key( (string) ( $table_of_contents['style'] ?? 'boxed' ) );
		if ( ! in_array( $toc_style, array( 'boxed', 'plain' ), true ) ) {
			$toc_style = 'boxed';
		}
		$toc_levels = isset( $table_of_contents['heading_levels'] ) && is_array( $table_of_contents['heading_levels'] )
			? array_values( array_unique( array_intersect( array( 'h2', 'h3', 'h4', 'h5', 'h6' ), array_map( 'sanitize_key', $table_of_contents['heading_levels'] ) ) ) )
			: array( 'h2', 'h3' );
		$toc_post_types = isset( $table_of_contents['post_types'] ) && is_array( $table_of_contents['post_types'] )
			? array_values( array_unique( array_filter( array_map( 'sanitize_key', $table_of_contents['post_types'] ) ) ) )
			: self::default_public_post_types();
		$toc_text_color       = sanitize_hex_color( (string) ( $table_of_contents['text_color'] ?? '' ) ) ?: '';
		$toc_link_color       = sanitize_hex_color( (string) ( $table_of_contents['link_color'] ?? '' ) ) ?: '';
		$toc_link_hover_color = sanitize_hex_color( (string) ( $table_of_contents['link_hover_color'] ?? '' ) ) ?: '';
		$toc_background_color = sanitize_hex_color( (string) ( $table_of_contents['background_color'] ?? '' ) ) ?: '';
		$toc_border_color     = sanitize_hex_color( (string) ( $table_of_contents['border_color'] ?? '' ) ) ?: '';

		$sharing = isset( $value['sharing'] ) && is_array( $value['sharing'] ) ? $value['sharing'] : array();
		$sharing_position = sanitize_key( (string) ( $sharing['position'] ?? 'after' ) );
		if ( ! in_array( $sharing_position, array( 'before', 'after' ), true ) ) {
			$sharing_position = 'after';
		}
		$sharing_post_types = isset( $sharing['post_types'] ) && is_array( $sharing['post_types'] )
			? array_values( array_unique( array_filter( array_map( 'sanitize_key', $sharing['post_types'] ) ) ) )
			: self::default_public_post_types();
		$sharing_raw_actions = isset( $sharing['actions'] ) && is_array( $sharing['actions'] ) ? $sharing['actions'] : array();
		$sharing_actions = array();
		foreach ( array( 'copy', 'whatsapp', 'linkedin', 'facebook', 'email' ) as $sharing_action ) {
			$sharing_actions[ $sharing_action ] = ! empty( $sharing_raw_actions[ $sharing_action ] );
		}
		$sharing_button_size = isset( $sharing['button_size'] ) && is_numeric( $sharing['button_size'] ) ? max( 0, (float) $sharing['button_size'] ) : 40.0;
		$sharing_icon_size = isset( $sharing['icon_size'] ) && is_numeric( $sharing['icon_size'] ) ? max( 0, (float) $sharing['icon_size'] ) : 20.0;
		$sharing_border_width = isset( $sharing['border_width'] ) && is_numeric( $sharing['border_width'] ) ? max( 0, (float) $sharing['border_width'] ) : 1.0;
		$sharing_border_radius = isset( $sharing['border_radius'] ) && is_numeric( $sharing['border_radius'] ) ? max( 0, (float) $sharing['border_radius'] ) : 8.0;
		$sharing_text_color = sanitize_hex_color( (string) ( $sharing['text_color'] ?? '' ) ) ?: '';
		$sharing_text_hover_color = sanitize_hex_color( (string) ( $sharing['text_hover_color'] ?? '' ) ) ?: '';
		$sharing_background_color = sanitize_hex_color( (string) ( $sharing['background_color'] ?? '' ) ) ?: '';
		$sharing_background_hover_color = sanitize_hex_color( (string) ( $sharing['background_hover_color'] ?? '' ) ) ?: '';
		$sharing_border_color = sanitize_hex_color( (string) ( $sharing['border_color'] ?? '' ) ) ?: '';
		$sharing_border_hover_color = sanitize_hex_color( (string) ( $sharing['border_hover_color'] ?? '' ) ) ?: '';

		$related_content = isset( $value['related_content'] ) && is_array( $value['related_content'] ) ? $value['related_content'] : array();
		$related_post_types = isset( $related_content['post_types'] ) && is_array( $related_content['post_types'] )
			? array_values( array_unique( array_filter( array_map( 'sanitize_key', $related_content['post_types'] ) ) ) )
			: self::default_related_post_types();
		$related_count           = max( 1, absint( $related_content['count'] ?? 3 ) );
		$related_columns_desktop = max( 1, absint( $related_content['columns_desktop'] ?? $related_content['columns'] ?? 3 ) );
		$related_columns_tablet  = max( 1, absint( $related_content['columns_tablet'] ?? 2 ) );
		$related_columns_mobile  = max( 1, absint( $related_content['columns_mobile'] ?? 1 ) );
		$related_ratio_width     = isset( $related_content['image_ratio_width'] ) && is_numeric( $related_content['image_ratio_width'] ) && is_finite( (float) $related_content['image_ratio_width'] ) && (float) $related_content['image_ratio_width'] > 0
			? (float) $related_content['image_ratio_width']
			: 16.0;
		$related_ratio_height    = isset( $related_content['image_ratio_height'] ) && is_numeric( $related_content['image_ratio_height'] ) && is_finite( (float) $related_content['image_ratio_height'] ) && (float) $related_content['image_ratio_height'] > 0
			? (float) $related_content['image_ratio_height']
			: 9.0;
		$related_card_gap        = '';
		if ( isset( $related_content['card_gap'] ) && is_scalar( $related_content['card_gap'] ) && '' !== trim( (string) $related_content['card_gap'] ) && is_numeric( $related_content['card_gap'] ) && is_finite( (float) $related_content['card_gap'] ) ) {
			$related_card_gap = max( 0, (float) $related_content['card_gap'] );
		}
		$related_card_style = sanitize_key( is_scalar( $related_content['card_style'] ?? null ) ? (string) $related_content['card_style'] : 'default' );
		if ( ! in_array( $related_card_style, array( 'default', 'elevated', 'minimal' ), true ) ) {
			$related_card_style = 'default';
		}

		$popular_content = isset( $value['popular_content'] ) && is_array( $value['popular_content'] ) ? $value['popular_content'] : array();
		$popular_selection_mode = sanitize_key( (string) ( $popular_content['selection_mode'] ?? 'automatic' ) );
		if ( ! in_array( $popular_selection_mode, array( 'automatic', 'manual' ), true ) ) {
			$popular_selection_mode = 'automatic';
		}
		$popular_period = sanitize_key( (string) ( $popular_content['period'] ?? 'all' ) );
		if ( ! in_array( $popular_period, array( 'all', '24h', '7d', '30d' ), true ) ) {
			$popular_period = 'all';
		}
		$popular_content_types = isset( $popular_content['content_types'] ) && is_array( $popular_content['content_types'] )
			? array_values( array_unique( array_diff( array_filter( array_map( 'sanitize_key', $popular_content['content_types'] ) ), array( 'attachment' ) ) ) )
			: array_values( array_diff( self::default_public_post_types(), array( 'attachment' ) ) );
		$popular_manual_ids = isset( $popular_content['manual_ids'] ) && is_array( $popular_content['manual_ids'] )
			? array_values( array_unique( array_filter( array_map( 'absint', $popular_content['manual_ids'] ) ) ) )
			: array();
		$popular_taxonomy = sanitize_key( (string) ( $popular_content['taxonomy'] ?? '' ) );
		$popular_term_ids = isset( $popular_content['term_ids'] ) && is_array( $popular_content['term_ids'] )
			? array_values( array_unique( array_filter( array_map( 'absint', $popular_content['term_ids'] ) ) ) )
			: array();
		if ( '' !== $popular_taxonomy && function_exists( 'get_taxonomy' ) ) {
			$taxonomy_object = get_taxonomy( $popular_taxonomy );
			$taxonomy_types  = is_object( $taxonomy_object ) && isset( $taxonomy_object->object_type ) && is_array( $taxonomy_object->object_type )
				? array_map( 'sanitize_key', $taxonomy_object->object_type )
				: array();
			if ( ! is_object( $taxonomy_object ) || empty( $taxonomy_object->public ) || empty( array_intersect( $popular_content_types, $taxonomy_types ) ) ) {
				$popular_taxonomy = '';
				$popular_term_ids = array();
			} elseif ( function_exists( 'term_exists' ) ) {
				$popular_term_ids = array_values(
					array_filter(
						$popular_term_ids,
						static function ( int $term_id ) use ( $popular_taxonomy ): bool {
							$exists = term_exists( $term_id, $popular_taxonomy );
							return null !== $exists && false !== $exists;
						}
					)
				);
			}
		}
		$popular_position = sanitize_key( (string) ( $popular_content['singular_position'] ?? 'after' ) );
		if ( ! in_array( $popular_position, array( 'before', 'after' ), true ) ) {
			$popular_position = 'after';
		}
		$popular_devices = isset( $popular_content['devices'] ) && is_array( $popular_content['devices'] ) ? $popular_content['devices'] : null;
		$popular_device_flags = null === $popular_devices
			? array( 'desktop' => true, 'tablet' => true, 'mobile' => true )
			: array(
				'desktop' => ! empty( $popular_devices['desktop'] ),
				'tablet'  => ! empty( $popular_devices['tablet'] ),
				'mobile'  => ! empty( $popular_devices['mobile'] ),
			);
		$popular_columns_desktop = max( 1, absint( $popular_content['columns_desktop'] ?? 4 ) );
		$popular_columns_tablet  = max( 1, absint( $popular_content['columns_tablet'] ?? 2 ) );
		$popular_columns_mobile  = max( 1, absint( $popular_content['columns_mobile'] ?? 1 ) );
		$popular_ratio_width     = isset( $popular_content['image_ratio_width'] ) && is_numeric( $popular_content['image_ratio_width'] ) && is_finite( (float) $popular_content['image_ratio_width'] ) && (float) $popular_content['image_ratio_width'] > 0 ? (float) $popular_content['image_ratio_width'] : 16.0;
		$popular_ratio_height    = isset( $popular_content['image_ratio_height'] ) && is_numeric( $popular_content['image_ratio_height'] ) && is_finite( (float) $popular_content['image_ratio_height'] ) && (float) $popular_content['image_ratio_height'] > 0 ? (float) $popular_content['image_ratio_height'] : 9.0;
		$popular_card_gap        = '';
		if ( isset( $popular_content['card_gap'] ) && is_scalar( $popular_content['card_gap'] ) && '' !== trim( (string) $popular_content['card_gap'] ) && is_numeric( $popular_content['card_gap'] ) && is_finite( (float) $popular_content['card_gap'] ) ) {
			$popular_card_gap = max( 0, (float) $popular_content['card_gap'] );
		}
		$popular_card_style = sanitize_key( is_scalar( $popular_content['card_style'] ?? null ) ? (string) $popular_content['card_style'] : 'default' );
		if ( ! in_array( $popular_card_style, array( 'default', 'elevated', 'minimal' ), true ) ) {
			$popular_card_style = 'default';
		}

		$posts_grid = PostsGridConfig::normalize( isset( $value['posts_grid'] ) && is_array( $value['posts_grid'] ) ? $value['posts_grid'] : array() );

		$color_mode = isset( $value['color_mode'] ) && is_array( $value['color_mode'] ) ? $value['color_mode'] : array();
		$color_mode_default = sanitize_key( (string) ( $color_mode['default_mode'] ?? 'system' ) );
		if ( ! in_array( $color_mode_default, array( 'light', 'dark', 'system' ), true ) ) {
			$color_mode_default = 'system';
		}
		$color_mode_position = sanitize_key( (string) ( $color_mode['position'] ?? 'left' ) );
		if ( ! in_array( $color_mode_position, array( 'left', 'right' ), true ) ) {
			$color_mode_position = 'left';
		}
		$color_mode_devices = isset( $color_mode['devices'] ) && is_array( $color_mode['devices'] ) ? $color_mode['devices'] : null;
		$color_mode_device_flags = null === $color_mode_devices
			? array( 'desktop' => true, 'tablet' => true, 'mobile' => true )
			: array(
				'desktop' => ! empty( $color_mode_devices['desktop'] ),
				'tablet'  => ! empty( $color_mode_devices['tablet'] ),
				'mobile'  => ! empty( $color_mode_devices['mobile'] ),
			);
		$color_mode_palette_input = isset( $color_mode['palette'] ) && is_array( $color_mode['palette'] ) ? $color_mode['palette'] : array();
		$color_mode_palette_defaults = array(
			'primary'    => '#334155',
			'accent'     => '#34d399',
			'background' => '#0f172a',
			'surface'    => '#111827',
			'text'       => '#e5e7eb',
			'heading'    => '#f8fafc',
			'link'       => '#6ee7b7',
			'border'     => '#334155',
		);
		$color_mode_palette = array();
		foreach ( $color_mode_palette_defaults as $color_key => $color_default ) {
			$color_value = sanitize_hex_color( (string) ( $color_mode_palette_input[ $color_key ] ?? $color_default ) );
			$color_mode_palette[ $color_key ] = $color_value ? strtolower( $color_value ) : $color_default;
		}

		$search_modal = isset( $value['search_modal'] ) && is_array( $value['search_modal'] ) ? $value['search_modal'] : array();
		$search_placeholder = array_key_exists( 'placeholder', $search_modal )
			? sanitize_text_field( (string) $search_modal['placeholder'] )
			: __( 'Search…', 'creceweb-lumen-lite' );

		$floating_action = isset( $value['floating_action'] ) && is_array( $value['floating_action'] ) ? $value['floating_action'] : array();
		$floating_action_type = sanitize_key( (string) ( $floating_action['action'] ?? 'element' ) );
		if ( ! in_array( $floating_action_type, array( 'element', 'url' ), true ) ) {
			$floating_action_type = 'element';
		}
		$floating_content = sanitize_key( (string) ( $floating_action['content'] ?? 'both' ) );
		if ( ! in_array( $floating_content, array( 'icon', 'text', 'both' ), true ) ) {
			$floating_content = 'both';
		}
		$floating_shape = sanitize_key( (string) ( $floating_action['shape'] ?? 'pill' ) );
		if ( ! in_array( $floating_shape, array( 'pill', 'rounded', 'square' ), true ) ) {
			$floating_shape = 'pill';
		}
		$floating_icon = sanitize_key( (string) ( $floating_action['icon'] ?? 'arrow-right' ) );
		if ( ! in_array( $floating_icon, array( 'arrow-right', 'chevron-right', 'arrow-down', 'plus', 'info', 'mail', 'phone', 'calendar', 'map-pin', 'chat' ), true ) ) {
			$floating_icon = 'arrow-right';
		}
		$floating_label = sanitize_text_field( (string) ( $floating_action['label'] ?? '' ) );
		$floating_element = ltrim( trim( (string) ( $floating_action['element_id'] ?? '' ) ), '#' );
		$floating_element = preg_replace( '/[^A-Za-z0-9_:\-.]/', '', $floating_element );
		$floating_element = is_string( $floating_element ) ? $floating_element : '';
		$floating_position = sanitize_key( (string) ( $floating_action['position'] ?? 'right' ) );
		if ( ! in_array( $floating_position, array( 'left', 'right' ), true ) ) {
			$floating_position = 'right';
		}
		$floating_size = max( 1, absint( $floating_action['size'] ?? 46 ) );
		$floating_background = sanitize_hex_color( (string) ( $floating_action['background_color'] ?? '#0f172a' ) ) ?: '#0f172a';
		$floating_background_hover = sanitize_hex_color( (string) ( $floating_action['background_hover'] ?? '#1e293b' ) ) ?: '#1e293b';
		$floating_icon_color = sanitize_hex_color( (string) ( $floating_action['icon_color'] ?? '#ffffff' ) ) ?: '#ffffff';
		$floating_icon_hover = sanitize_hex_color( (string) ( $floating_action['icon_hover'] ?? '#ffffff' ) ) ?: '#ffffff';
		$floating_text_color = sanitize_hex_color( (string) ( $floating_action['text_color'] ?? '#ffffff' ) ) ?: '#ffffff';
		$floating_text_hover = sanitize_hex_color( (string) ( $floating_action['text_hover'] ?? '#ffffff' ) ) ?: '#ffffff';
		$floating_devices = isset( $floating_action['devices'] ) && is_array( $floating_action['devices'] ) ? $floating_action['devices'] : array();
		$floating_device_flags = array(
			'desktop' => ! empty( $floating_devices['desktop'] ),
			'tablet'  => ! empty( $floating_devices['tablet'] ),
			'mobile'  => ! empty( $floating_devices['mobile'] ),
		);
		if ( ! in_array( true, $floating_device_flags, true ) ) {
			$floating_device_flags = array( 'desktop' => true, 'tablet' => true, 'mobile' => true );
		}

		$whatsapp = isset( $value['whatsapp'] ) && is_array( $value['whatsapp'] ) ? $value['whatsapp'] : array();
		$number = preg_replace( '/\D+/', '', (string) ( $whatsapp['number'] ?? '' ) );
		$number = is_string( $number ) ? substr( $number, 0, 20 ) : '';
		$message = sanitize_textarea_field( (string) ( $whatsapp['message'] ?? '' ) );
		$position = sanitize_key( (string) ( $whatsapp['position'] ?? 'right' ) );
		if ( ! in_array( $position, array( 'left', 'right' ), true ) ) {
			$position = 'right';
		}
		$devices = isset( $whatsapp['devices'] ) && is_array( $whatsapp['devices'] ) ? $whatsapp['devices'] : array();
		$device_flags = array(
			'desktop' => ! empty( $devices['desktop'] ),
			'tablet'  => ! empty( $devices['tablet'] ),
			'mobile'  => ! empty( $devices['mobile'] ),
		);
		if ( ! in_array( true, $device_flags, true ) ) {
			$device_flags = array( 'desktop' => true, 'tablet' => true, 'mobile' => true );
		}

		$messaging = isset( $value['messaging'] ) && is_array( $value['messaging'] ) ? $value['messaging'] : array();
		$messaging_provider = sanitize_key( (string) ( $messaging['provider'] ?? 'whatsapp' ) );
		if ( ! in_array( $messaging_provider, array( 'whatsapp', 'telegram', 'messenger', 'signal' ), true ) ) {
			$messaging_provider = 'whatsapp';
		}
		$messaging_appearance_mode = sanitize_key( (string) ( $messaging['appearance_mode'] ?? 'provider' ) );
		if ( ! in_array( $messaging_appearance_mode, array( 'provider', 'custom' ), true ) ) {
			$messaging_appearance_mode = 'provider';
		}
		$provider_values = isset( $messaging['providers'] ) && is_array( $messaging['providers'] ) ? $messaging['providers'] : array();
		$messaging_whatsapp = isset( $provider_values['whatsapp'] ) && is_array( $provider_values['whatsapp'] ) ? $provider_values['whatsapp'] : array();
		$messaging_number = preg_replace( '/\D+/', '', (string) ( $messaging_whatsapp['number'] ?? '' ) );
		$messaging_number = is_string( $messaging_number ) ? substr( $messaging_number, 0, 20 ) : '';
		$messaging_telegram = isset( $provider_values['telegram'] ) && is_array( $provider_values['telegram'] ) ? $provider_values['telegram'] : array();
		$telegram_username = ltrim( trim( (string) ( $messaging_telegram['username'] ?? '' ) ), '@' );
		$telegram_username = preg_replace( '/[^A-Za-z0-9_]/', '', $telegram_username );
		$telegram_username = is_string( $telegram_username ) ? substr( $telegram_username, 0, 64 ) : '';
		$messaging_messenger = isset( $provider_values['messenger'] ) && is_array( $provider_values['messenger'] ) ? $provider_values['messenger'] : array();
		$messenger_username = ltrim( trim( (string) ( $messaging_messenger['username'] ?? '' ) ), '@/ ' );
		$messenger_username = preg_replace( '/[^A-Za-z0-9._-]/', '', $messenger_username );
		$messenger_username = is_string( $messenger_username ) ? $messenger_username : '';
		$messaging_signal = isset( $provider_values['signal'] ) && is_array( $provider_values['signal'] ) ? $provider_values['signal'] : array();
		$signal_url = self::sanitize_signal_url( (string) ( $messaging_signal['url'] ?? '' ) );
		$messaging_position = sanitize_key( (string) ( $messaging['position'] ?? 'right' ) );
		if ( ! in_array( $messaging_position, array( 'left', 'right' ), true ) ) {
			$messaging_position = 'right';
		}
		$messaging_devices = isset( $messaging['devices'] ) && is_array( $messaging['devices'] ) ? $messaging['devices'] : array();
		$messaging_device_flags = array(
			'desktop' => ! empty( $messaging_devices['desktop'] ),
			'tablet'  => ! empty( $messaging_devices['tablet'] ),
			'mobile'  => ! empty( $messaging_devices['mobile'] ),
		);
		if ( ! in_array( true, $messaging_device_flags, true ) ) {
			$messaging_device_flags = array( 'desktop' => true, 'tablet' => true, 'mobile' => true );
		}

		return array(
			'schema_version'               => self::SCHEMA,
			'installed_version'            => CRECEWEB_LUMEN_LITE_VERSION,
			'onboarding_dismissed'         => ! empty( $value['onboarding_dismissed'] ),
			'footer_credit_text'           => $footer_credit,
			'menu_has_item_customizations'      => ! empty( $value['menu_has_item_customizations'] ),
			'menu_customizations_needs_reindex' => ! empty( $value['menu_customizations_needs_reindex'] ),
			'content'                      => array(
				'reading_time_enabled'   => ! empty( $content['reading_time_enabled'] ),
				'excerpt_length_enabled' => ! empty( $content['excerpt_length_enabled'] ),
				'excerpt_length'         => $excerpt_length,
			),
			'breadcrumbs'                  => array(
				'enabled'          => ! empty( $breadcrumbs['enabled'] ),
				'home_label'       => $breadcrumbs_home_label,
				'separator'        => $breadcrumbs_separator,
				'background_style' => $breadcrumbs_background_style,
				'show_border'      => ! array_key_exists( 'show_border', $breadcrumbs ) || ! empty( $breadcrumbs['show_border'] ),
				'show_home'        => ! empty( $breadcrumbs['show_home'] ),
				'show_current'     => ! empty( $breadcrumbs['show_current'] ),
				'schema_enabled'   => ! empty( $breadcrumbs['schema_enabled'] ),
			),
			'reading_progress'             => array(
				'enabled'    => ! empty( $reading_progress['enabled'] ),
				'position'   => $reading_progress_position,
				'thickness'   => $reading_progress_thickness,
				'color'       => $reading_progress_color,
				'track_color' => $reading_progress_track_color,
				'post_types' => $reading_progress_post_types,
				'devices'    => $reading_progress_device_flags,
			),
			'table_of_contents'            => array(
				'enabled'          => ! empty( $table_of_contents['enabled'] ),
				'title'            => sanitize_text_field( (string) ( $table_of_contents['title'] ?? __( 'Table of contents', 'creceweb-lumen-lite' ) ) ),
				'position'         => $toc_position,
				'style'            => $toc_style,
				'minimum_headings' => max( 1, absint( $table_of_contents['minimum_headings'] ?? 3 ) ),
				'heading_levels'   => $toc_levels,
				'post_types'       => $toc_post_types,
				'text_color'       => $toc_text_color,
				'link_color'       => $toc_link_color,
				'link_hover_color' => $toc_link_hover_color,
				'background_color' => $toc_background_color,
				'border_color'     => $toc_border_color,
			),
			'sharing'                      => array(
				'enabled'                => ! empty( $sharing['enabled'] ),
				'position'               => $sharing_position,
				'show_labels'            => ! empty( $sharing['show_labels'] ),
				'minimal_style'           => ! empty( $sharing['minimal_style'] ),
				'post_types'             => $sharing_post_types,
				'button_size'            => $sharing_button_size,
				'icon_size'              => $sharing_icon_size,
				'border_width'           => $sharing_border_width,
				'border_radius'          => $sharing_border_radius,
				'text_color'             => $sharing_text_color,
				'text_hover_color'       => $sharing_text_hover_color,
				'background_color'       => $sharing_background_color,
				'background_hover_color' => $sharing_background_hover_color,
				'border_color'           => $sharing_border_color,
				'border_hover_color'     => $sharing_border_hover_color,
				'actions'                => $sharing_actions,
			),
			'related_content'              => array(
				'enabled'            => ! empty( $related_content['enabled'] ),
				'title'              => sanitize_text_field( (string) ( $related_content['title'] ?? __( 'Related content', 'creceweb-lumen-lite' ) ) ),
				'count'              => $related_count,
				'columns_desktop'    => $related_columns_desktop,
				'columns_tablet'     => $related_columns_tablet,
				'columns_mobile'     => $related_columns_mobile,
				'image_ratio_width'  => $related_ratio_width,
				'image_ratio_height' => $related_ratio_height,
				'card_gap'           => $related_card_gap,
				'card_style'         => $related_card_style,
				'show_image'         => ! empty( $related_content['show_image'] ),
				'show_excerpt'       => ! empty( $related_content['show_excerpt'] ),
				'show_date'          => ! empty( $related_content['show_date'] ),
				'post_types'         => $related_post_types,
			),
			'popular_content'              => array(
				'enabled'            => ! empty( $popular_content['enabled'] ),
				'title'              => sanitize_text_field( (string) ( $popular_content['title'] ?? __( 'Popular content', 'creceweb-lumen-lite' ) ) ),
				'selection_mode'     => $popular_selection_mode,
				'items'              => max( 1, absint( $popular_content['items'] ?? 4 ) ),
				'period'             => $popular_period,
				'content_types'      => $popular_content_types,
				'manual_ids'         => $popular_manual_ids,
				'taxonomy'           => $popular_taxonomy,
				'term_ids'           => $popular_term_ids,
				'singular_position'  => $popular_position,
				'show_on_singular'   => ! empty( $popular_content['show_on_singular'] ),
				'devices'            => $popular_device_flags,
				'show_image'         => ! empty( $popular_content['show_image'] ),
				'show_taxonomy'      => ! empty( $popular_content['show_taxonomy'] ),
				'show_date'          => ! empty( $popular_content['show_date'] ),
				'show_excerpt'       => ! empty( $popular_content['show_excerpt'] ),
				'columns_desktop'    => $popular_columns_desktop,
				'columns_tablet'     => $popular_columns_tablet,
				'columns_mobile'     => $popular_columns_mobile,
				'image_ratio_width'  => $popular_ratio_width,
				'image_ratio_height' => $popular_ratio_height,
				'card_gap'           => $popular_card_gap,
				'card_style'         => $popular_card_style,
			),
			'posts_grid'                   => $posts_grid,
			'color_mode'                   => array(
				'enabled'      => ! empty( $color_mode['enabled'] ),
				'default_mode' => $color_mode_default,
				'show_switch'      => ! empty( $color_mode['show_switch'] ),
				'show_menu_switch' => ! empty( $color_mode['show_menu_switch'] ),
				'position'     => $color_mode_position,
				'devices'      => $color_mode_device_flags,
				'palette'      => $color_mode_palette,
			),
			'search_modal'                 => array(
				'enabled'           => ! empty( $search_modal['enabled'] ),
				'show_menu_trigger' => ! empty( $search_modal['show_menu_trigger'] ),
				'placeholder'       => $search_placeholder,
			),
			'floating_action'              => array(
				'enabled'         => ! empty( $floating_action['enabled'] ),
				'action'          => $floating_action_type,
				'content'         => $floating_content,
				'shape'           => $floating_shape,
				'icon'            => $floating_icon,
				'label'           => $floating_label,
				'element_id'      => $floating_element,
				'url'              => esc_url_raw( (string) ( $floating_action['url'] ?? '' ) ),
				'size'             => $floating_size,
				'background_color' => $floating_background,
				'background_hover' => $floating_background_hover,
				'icon_color'       => $floating_icon_color,
				'icon_hover'       => $floating_icon_hover,
				'text_color'       => $floating_text_color,
				'text_hover'       => $floating_text_hover,
				'position'         => $floating_position,
				'scroll_offset'   => max( 0, absint( $floating_action['scroll_offset'] ?? 300 ) ),
				'bottom_offset'   => max( 0, absint( $floating_action['bottom_offset'] ?? 16 ) ),
				'side_offset'     => max( 0, absint( $floating_action['side_offset'] ?? 16 ) ),
				'opacity'         => max( 0, min( 100, absint( $floating_action['opacity'] ?? 100 ) ) ),
				'auto_hide'       => ! empty( $floating_action['auto_hide'] ),
				'auto_hide_delay' => max( 0, absint( $floating_action['auto_hide_delay'] ?? 4 ) ),
				'smooth_scroll'   => ! empty( $floating_action['smooth_scroll'] ),
				'devices'         => $floating_device_flags,
			),
			'whatsapp'                     => array(
				'enabled'          => ! empty( $whatsapp['enabled'] ),
				'number'           => $number,
				'message'          => $message,
				'background_color' => sanitize_hex_color( (string) ( $whatsapp['background_color'] ?? '#0f172a' ) ) ?: '#0f172a',
				'icon_color'       => sanitize_hex_color( (string) ( $whatsapp['icon_color'] ?? '#ffffff' ) ) ?: '#ffffff',
				'size'             => max( 1, absint( $whatsapp['size'] ?? 46 ) ),
				'position'         => $position,
				'devices'          => $device_flags,
			),
			'whatsapp_migration_complete' => ! empty( $value['whatsapp_migration_complete'] ),
			'whatsapp_migration_source'   => sanitize_key( (string) ( $value['whatsapp_migration_source'] ?? '' ) ),
			'messaging'                    => array(
				'enabled'         => ! empty( $messaging['enabled'] ),
				'provider'        => $messaging_provider,
				'appearance_mode' => $messaging_appearance_mode,
				'providers' => array(
					'whatsapp' => array(
						'number'  => $messaging_number,
						'message' => sanitize_textarea_field( (string) ( $messaging_whatsapp['message'] ?? '' ) ),
					),
					'telegram' => array(
						'username' => $telegram_username,
						'message'  => sanitize_textarea_field( (string) ( $messaging_telegram['message'] ?? '' ) ),
					),
					'messenger' => array( 'username' => $messenger_username ),
					'signal'    => array( 'url' => $signal_url ),
				),
				'background_color' => sanitize_hex_color( (string) ( $messaging['background_color'] ?? '#0f172a' ) ) ?: '#0f172a',
				'icon_color'       => sanitize_hex_color( (string) ( $messaging['icon_color'] ?? '#ffffff' ) ) ?: '#ffffff',
				'size'             => max( 1, absint( $messaging['size'] ?? 46 ) ),
				'position'         => $messaging_position,
				'devices'          => $messaging_device_flags,
			),
			'messaging_migration_complete' => ! empty( $value['messaging_migration_complete'] ),
			'messaging_migration_source'   => sanitize_key( (string) ( $value['messaging_migration_source'] ?? '' ) ),
			'legacy_contexts'              => self::sanitize_legacy_contexts( $legacy_contexts ),
			'legacy_menu_presentation'     => self::sanitize_legacy_menu( $legacy_menu ),
		);
	}

	/**
	 * Sanitizes a Signal share URL without making any remote request.
	 *
	 * @param string $url Candidate Signal share URL.
	 * @return string
	 */
	private static function sanitize_signal_url( string $url ): string {
		$url = trim( $url );
		if ( '' === $url ) {
			return '';
		}

		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) ) {
			return '';
		}
		$scheme = strtolower( (string) ( $parts['scheme'] ?? '' ) );
		$host   = strtolower( (string) ( $parts['host'] ?? '' ) );
		if ( 'https' !== $scheme || ! in_array( $host, array( 'signal.me', 'signal.link' ), true ) ) {
			return '';
		}
		if ( isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return '';
		}

		return esc_url_raw( $url );
	}

	/**
	 * Keeps removed alpha settings available for diagnostics without applying them.
	 *
	 * @param array<string,mixed> $contexts Legacy contexts.
	 * @return array<string,array<string,string>>
	 */
	private static function sanitize_legacy_contexts( array $contexts ): array {
		$clean = array();
		foreach ( array( 'front_page', 'pages', 'posts' ) as $context_id ) {
			$raw = isset( $contexts[ $context_id ] ) && is_array( $contexts[ $context_id ] ) ? $contexts[ $context_id ] : array();
			$clean[ $context_id ] = array(
				'sidebar' => sanitize_key( (string) ( $raw['sidebar'] ?? 'inherit' ) ),
				'width'   => sanitize_key( (string) ( $raw['width'] ?? 'inherit' ) ),
				'header'  => sanitize_key( (string) ( $raw['header'] ?? 'inherit' ) ),
			);
		}
		return $clean;
	}

	/**
	 * @param array<string,mixed> $menu Legacy menu presentation.
	 * @return array<string,string>
	 */
	private static function sanitize_legacy_menu( array $menu ): array {
		return array(
			'current_indicator' => sanitize_key( (string) ( $menu['current_indicator'] ?? 'none' ) ),
			'spacing'           => sanitize_key( (string) ( $menu['spacing'] ?? 'normal' ) ),
		);
	}
}
