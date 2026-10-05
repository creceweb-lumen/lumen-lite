<?php
/**
 * Complete-site installation for declarative Lumen Site Kits.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Library;

use CreceWeb\LumenLite\Menu\Controller as MenuController;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps complete-site installation outside Gutenberg and deliberately avoids
 * any post-install synchronization or repair workflow.
 */
final class SiteKitManager {
	private const ACTION = 'cw_lumen_lite_install_site_kit';
	private const RESULT_TRANSIENT_PREFIX = 'cw_lumen_lite_site_kit_result_';

	public function __construct(
		private KitCatalog $kits,
		private PageCatalog $pages,
		private KitPresetManager $presets,
		private MenuController $menu
	) {}

	/** @return void */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_install' ) );
	}

	/** @return void */
	public function handle_install(): void {
		if ( ! current_user_can( 'edit_theme_options' ) || ! current_user_can( 'edit_pages' ) || ! current_user_can( 'publish_pages' ) ) {
			wp_die( esc_html__( 'Your user account does not have permission to install a complete Site Kit.', 'creceweb-lumen-lite' ) );
		}

		check_admin_referer( self::ACTION );

		$kit_id       = isset( $_POST['kit_id'] ) ? sanitize_key( wp_unslash( $_POST['kit_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$apply_design = isset( $_POST['apply_design'] ) && '1' === sanitize_key( wp_unslash( $_POST['apply_design'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$result       = $this->install( $kit_id, $apply_design );

		if ( is_wp_error( $result ) ) {
			$result = array(
				'status'  => 'error',
				'message' => $result->get_error_message(),
			);
		}

		set_transient( self::RESULT_TRANSIENT_PREFIX . get_current_user_id(), $result, 5 * MINUTE_IN_SECONDS );
		wp_safe_redirect( $this->section_url() );
		exit;
	}

	/**
	 * Create one fresh independent Site Kit installation.
	 *
	 * Existing pages and menus are never adopted or modified. WordPress resolves
	 * title/slug/menu-name collisions with fresh unique objects.
	 *
	 * @param string $kit_id Kit identifier.
	 * @param bool   $apply_design Whether to apply the Kit recommended design.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function install( string $kit_id, bool $apply_design = true ) {
		$kit_id = sanitize_key( $kit_id );
		$kit    = $this->kits->items()[ $kit_id ] ?? null;
		if ( ! is_array( $kit ) ) {
			return new \WP_Error( 'lumen_site_kit_missing', __( 'The selected Site Kit is no longer available.', 'creceweb-lumen-lite' ) );
		}

		$setup = isset( $kit['site_setup'] ) && is_array( $kit['site_setup'] ) ? $kit['site_setup'] : array();
		if ( empty( $setup['pages'] ) || empty( $setup['navigation'] ) || empty( $setup['front_page'] ) ) {
			return new \WP_Error( 'lumen_site_kit_setup_missing', __( 'This Kit does not include a complete-site setup manifest.', 'creceweb-lumen-lite' ) );
		}

		$page_catalog = $this->pages->definitions();
		$page_ids     = array();
		$page_titles  = array();
		$created      = array();

		foreach ( $setup['pages'] as $page ) {
			if ( ! is_array( $page ) ) {
				continue;
			}

			$key       = sanitize_key( (string) ( $page['key'] ?? '' ) );
			$item      = sanitize_key( (string) ( $page['item'] ?? '' ) );
			$page_slug = str_starts_with( $item, 'page-' ) ? substr( $item, 5 ) : '';
			$definition = '' !== $page_slug && isset( $page_catalog[ $page_slug ] ) ? $page_catalog[ $page_slug ] : null;
			if ( '' === $key || ! is_array( $definition ) ) {
				return new \WP_Error( 'lumen_site_kit_page_missing', __( 'One of the Site Kit pages is no longer available in Lumen Library.', 'creceweb-lumen-lite' ) );
			}

			$title = sanitize_text_field( (string) ( $page['title'] ?? $definition['title'] ?? $key ) );
			$post_id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_name'    => sanitize_title( $title ),
					'post_content' => wp_slash( (string) ( $definition['content'] ?? '' ) ),
				),
				true
			);
			if ( is_wp_error( $post_id ) || absint( $post_id ) < 1 ) {
				return new \WP_Error( 'lumen_site_kit_page_create_failed', __( 'The Site Kit could not create all of its pages. Existing content was left untouched.', 'creceweb-lumen-lite' ) );
			}

			$post_id = absint( $post_id );
			$template = sanitize_text_field( (string) ( $definition['template'] ?? '' ) );
			if ( '' !== $template ) {
				update_post_meta( $post_id, '_wp_page_template', $template );
			}
			update_post_meta( $post_id, '_cw_lumen_site_kit', $kit_id );
			update_post_meta( $post_id, '_cw_lumen_site_kit_page_key', $key );

			$page_ids[ $key ]    = $post_id;
			$page_titles[ $key ] = $title;
			$created[]           = $post_id;
		}

		$link_result = $this->resolve_created_page_links( $page_ids );
		if ( is_wp_error( $link_result ) ) {
			return $link_result;
		}

		$front_key = sanitize_key( (string) $setup['front_page'] );
		if ( ! isset( $page_ids[ $front_key ] ) ) {
			return new \WP_Error( 'lumen_site_kit_home_missing', __( 'The Site Kit Home page could not be resolved.', 'creceweb-lumen-lite' ) );
		}

		$menu_location = sanitize_key( (string) ( $setup['menu_location'] ?? 'primary' ) );
		$registered    = get_registered_nav_menus();
		if ( ! isset( $registered[ $menu_location ] ) ) {
			return new \WP_Error( 'lumen_site_kit_menu_location_missing', __( 'The active Theme does not expose the navigation location required by this Site Kit.', 'creceweb-lumen-lite' ) );
		}

		$menu_name = $this->unique_menu_name( sanitize_text_field( (string) ( $setup['menu_name'] ?? $kit['title'] ?? __( 'Lumen Site Kit', 'creceweb-lumen-lite' ) ) ) );
		$menu_id   = wp_create_nav_menu( $menu_name );
		if ( is_wp_error( $menu_id ) || absint( $menu_id ) < 1 ) {
			return new \WP_Error( 'lumen_site_kit_menu_create_failed', __( 'The Site Kit pages were created, but WordPress could not create the navigation menu.', 'creceweb-lumen-lite' ) );
		}
		$menu_id = absint( $menu_id );

		$menu_item_ids = array();
		$pending       = array_values( (array) $setup['navigation'] );
		$guard         = count( $pending ) + 2;
		while ( ! empty( $pending ) && $guard-- > 0 ) {
			$remaining = array();
			$progress  = false;
			foreach ( $pending as $entry ) {
				if ( ! is_array( $entry ) ) {
					continue;
				}

				$type = sanitize_key( (string) ( $entry['type'] ?? '' ) );
				if ( 'page' === $type ) {
					$page_key   = sanitize_key( (string) ( $entry['page'] ?? '' ) );
					$parent_key = sanitize_key( (string) ( $entry['parent'] ?? '' ) );
					if ( ! isset( $page_ids[ $page_key ] ) ) {
						return new \WP_Error( 'lumen_site_kit_navigation_page_missing', __( 'A Site Kit navigation page could not be resolved.', 'creceweb-lumen-lite' ) );
					}
					if ( '' !== $parent_key && ! isset( $menu_item_ids[ $parent_key ] ) ) {
						$remaining[] = $entry;
						continue;
					}
					$item_id = wp_update_nav_menu_item(
						$menu_id,
						0,
						array(
							'menu-item-object-id' => $page_ids[ $page_key ],
							'menu-item-object'    => 'page',
							'menu-item-type'      => 'post_type',
							'menu-item-title'     => $page_titles[ $page_key ],
							'menu-item-status'    => 'publish',
							'menu-item-parent-id' => '' !== $parent_key ? $menu_item_ids[ $parent_key ] : 0,
						)
					);
					if ( is_wp_error( $item_id ) || absint( $item_id ) < 1 ) {
						return new \WP_Error( 'lumen_site_kit_menu_item_failed', __( 'The Site Kit menu could not be completed.', 'creceweb-lumen-lite' ) );
					}
					$menu_item_ids[ $page_key ] = absint( $item_id );
					if ( ! empty( $entry['highlight'] ) ) {
						$this->menu->set_item_button( absint( $item_id ), true );
					}
					if ( isset( $entry['indicator'] ) && '' !== trim( (string) $entry['indicator'] ) ) {
						$this->menu->set_item_indicator( absint( $item_id ), 'text', sanitize_text_field( (string) $entry['indicator'] ) );
						$this->menu->set_item_indicator_devices( absint( $item_id ), isset( $entry['indicator_devices'] ) && is_array( $entry['indicator_devices'] ) ? $entry['indicator_devices'] : array( 'mobile' ) );
					}
					$progress = true;
					continue;
				}

				if ( 'custom' === $type ) {
					$target_page = sanitize_key( (string) ( $entry['target_page'] ?? '' ) );
					$item_url    = '' !== $target_page && isset( $page_ids[ $target_page ] )
						? get_permalink( $page_ids[ $target_page ] )
						: (string) ( $entry['url'] ?? '' );
					$item_url    = esc_url_raw( (string) $item_url );
					if ( '' === $item_url ) {
						return new \WP_Error( 'lumen_site_kit_menu_item_target_missing', __( 'A Site Kit navigation destination could not be resolved.', 'creceweb-lumen-lite' ) );
					}
					$item_id = wp_update_nav_menu_item(
						$menu_id,
						0,
						array(
							'menu-item-type'   => 'custom',
							'menu-item-title'  => sanitize_text_field( (string) ( $entry['label'] ?? '' ) ),
							'menu-item-url'    => $item_url,
							'menu-item-status' => 'publish',
						)
					);
					if ( is_wp_error( $item_id ) || absint( $item_id ) < 1 ) {
						return new \WP_Error( 'lumen_site_kit_menu_item_failed', __( 'The Site Kit menu could not be completed.', 'creceweb-lumen-lite' ) );
					}
					if ( ! empty( $entry['highlight'] ) ) {
						$this->menu->set_item_button( absint( $item_id ), true );
					}
					if ( isset( $entry['indicator'] ) && '' !== trim( (string) $entry['indicator'] ) ) {
						$this->menu->set_item_indicator( absint( $item_id ), 'text', sanitize_text_field( (string) $entry['indicator'] ) );
						$this->menu->set_item_indicator_devices( absint( $item_id ), isset( $entry['indicator_devices'] ) && is_array( $entry['indicator_devices'] ) ? $entry['indicator_devices'] : array( 'mobile' ) );
					}
					$progress = true;
				}
			}

			if ( ! $progress && ! empty( $remaining ) ) {
				return new \WP_Error( 'lumen_site_kit_menu_parent_failed', __( 'The Site Kit menu hierarchy could not be resolved safely.', 'creceweb-lumen-lite' ) );
			}
			$pending = $remaining;
		}

		$warnings       = array();
		$design_applied = false;
		if ( $apply_design ) {
			$design_result = $this->presets->apply( $kit_id );
			if ( is_wp_error( $design_result ) ) {
				$warnings[] = $design_result->get_error_message();
			} else {
				$design_applied = true;
			}
		}

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_ids[ $front_key ] );
		$locations                   = get_theme_mod( 'nav_menu_locations', array() );
		$locations                   = is_array( $locations ) ? $locations : array();
		$locations[ $menu_location ] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );

		return array(
			'status'         => empty( $warnings ) ? 'success' : 'warning',
			'message'        => empty( $warnings )
				? __( 'The complete Site Kit was installed successfully.', 'creceweb-lumen-lite' )
				: __( 'The Site Kit structure was installed, but the recommended design needs attention.', 'creceweb-lumen-lite' ),
			'warnings'       => $warnings,
			'kit_id'         => $kit_id,
			'page_ids'       => $page_ids,
			'front_page_id'  => $page_ids[ $front_key ],
			'menu_id'        => $menu_id,
			'menu_name'      => $menu_name,
			'design_applied' => $design_applied,
		);
	}


	/**
	 * Resolve declarative cross-page links inside pages created by this install.
	 * Runs once before the Site Kit is handed to the user; there is no later sync.
	 *
	 * @param array<string,int> $page_ids Page keys mapped to newly created IDs.
	 * @return true|\WP_Error
	 */
	private function resolve_created_page_links( array $page_ids ) {
		$replacements = array();
		foreach ( $page_ids as $key => $page_id ) {
			$key = sanitize_key( (string) $key );
			$url = get_permalink( absint( $page_id ) );
			if ( '' === $key || ! is_string( $url ) || '' === $url ) {
				continue;
			}
			$resolved_href = 'href="' . esc_url( $url ) . '"';
			$replacements[ 'href="#cw-site-kit-page-' . $key . '"' ] = $resolved_href;
			// Backward compatibility for pre-K2.7.35 declarative markers.
			$replacements[ 'href="#" data-cw-site-kit-page="' . $key . '"' ] = $resolved_href;
		}

		foreach ( $page_ids as $page_id ) {
			$page_id = absint( $page_id );
			$content = (string) get_post_field( 'post_content', $page_id );
			if (
				'' === $content
				|| ( ! str_contains( $content, '#cw-site-kit-page-' ) && ! str_contains( $content, 'data-cw-site-kit-page=' ) )
			) {
				continue;
			}
			$resolved = strtr( $content, $replacements );
			if ( $resolved === $content ) {
				continue;
			}
			$result = wp_update_post(
				array(
					'ID'           => $page_id,
					'post_content' => wp_slash( $resolved ),
				),
				true
			);
			if ( is_wp_error( $result ) ) {
				return new \WP_Error( 'lumen_site_kit_link_resolution_failed', __( 'The Site Kit pages were created, but their internal links could not be resolved safely.', 'creceweb-lumen-lite' ) );
			}
		}

		return true;
	}

	/** @return void */
	public function render(): void {
		$result = get_transient( self::RESULT_TRANSIENT_PREFIX . get_current_user_id() );
		if ( false !== $result ) {
			delete_transient( self::RESULT_TRANSIENT_PREFIX . get_current_user_id() );
		}

		$kits = array_filter(
			$this->kits->items(),
			static fn( $kit ): bool => is_array( $kit ) && ! empty( $kit['site_setup'] )
		);
		?>
		<div class="cw-lumen-ui-tool cw-lumen-ui-tool--site-kits">
			<section class="cw-lumen-ui-section-head">
				<p class="cw-lumen-admin__eyebrow"><?php echo esc_html__( 'Complete sites', 'creceweb-lumen-lite' ); ?></p>
				<h2><?php echo esc_html__( 'Site Kits', 'creceweb-lumen-lite' ); ?></h2>
				<p><?php echo esc_html__( 'Install a complete Lumen site without opening a temporary Gutenberg page. Each installation creates fresh WordPress pages and navigation, then leaves them fully editable.', 'creceweb-lumen-lite' ); ?></p>
			</section>

			<?php $this->render_result( is_array( $result ) ? $result : array() ); ?>

			<div class="cw-lumen-site-kits">
				<?php foreach ( $kits as $kit ) : ?>
					<?php $this->render_kit_card( $kit ); ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * @param array<string,mixed> $kit Kit definition.
	 * @return void
	 */
	private function render_kit_card( array $kit ): void {
		$setup       = (array) ( $kit['site_setup'] ?? array() );
		$page_count  = count( (array) ( $setup['pages'] ?? array() ) );
		$current_home = absint( get_option( 'page_on_front', 0 ) );
		$locations   = get_nav_menu_locations();
		$current_menu = isset( $locations['primary'] ) ? wp_get_nav_menu_object( absint( $locations['primary'] ) ) : false;
		?>
		<article class="cw-lumen-site-kit-card">
			<div class="cw-lumen-site-kit-card__preview">
				<img src="<?php echo esc_url( (string) ( $kit['preview_url'] ?? '' ) ); ?>" alt="<?php echo esc_attr( (string) ( $kit['title'] ?? '' ) ); ?>">
			</div>
			<div class="cw-lumen-site-kit-card__body">
				<p class="cw-lumen-admin__eyebrow"><?php echo esc_html__( 'Complete Site Kit', 'creceweb-lumen-lite' ); ?></p>
				<h3><?php echo esc_html( (string) ( $kit['title'] ?? '' ) ); ?></h3>
				<p><?php echo esc_html( (string) ( $kit['description'] ?? '' ) ); ?></p>
				<ul class="cw-lumen-site-kit-card__summary">
					<li><?php echo esc_html( sprintf( /* translators: %d: number of pages created by a Site Kit. */ _n( '%d published page', '%d published pages', $page_count, 'creceweb-lumen-lite' ), $page_count ) ); ?></li>
					<li><?php echo esc_html__( 'Primary navigation with highlighted CTA', 'creceweb-lumen-lite' ); ?></li>
					<li><?php echo esc_html__( 'Static Home assignment', 'creceweb-lumen-lite' ); ?></li>
					<li><?php echo esc_html__( 'Optional recommended design', 'creceweb-lumen-lite' ); ?></li>
				</ul>
				<div class="cw-lumen-site-kit-card__state">
					<p><strong><?php echo esc_html__( 'Current Home:', 'creceweb-lumen-lite' ); ?></strong> <?php echo $current_home ? esc_html( get_the_title( $current_home ) ) : esc_html__( 'None assigned', 'creceweb-lumen-lite' ); ?></p>
					<p><strong><?php echo esc_html__( 'Current primary menu:', 'creceweb-lumen-lite' ); ?></strong> <?php echo $current_menu && ! is_wp_error( $current_menu ) ? esc_html( (string) $current_menu->name ) : esc_html__( 'None assigned', 'creceweb-lumen-lite' ); ?></p>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cw-lumen-site-kit-card__form" data-cw-site-kit-install-form data-confirm="<?php echo esc_attr__( 'Install this complete Site Kit now? Existing content will not be deleted, but the active Home and primary-menu assignments will change.', 'creceweb-lumen-lite' ); ?>">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
					<input type="hidden" name="kit_id" value="<?php echo esc_attr( (string) ( $kit['id'] ?? '' ) ); ?>">
					<?php wp_nonce_field( self::ACTION ); ?>
					<label><input type="checkbox" name="apply_design" value="1" checked> <?php echo esc_html__( 'Apply recommended design', 'creceweb-lumen-lite' ); ?></label>
					<p class="description"><?php echo esc_html__( 'This creates a new independent copy. Existing pages and menus are never deleted or edited; only the active Home and primary-menu assignments are changed after a successful installation.', 'creceweb-lumen-lite' ); ?></p>
					<p><button type="submit" class="button button-primary button-hero"><?php echo esc_html__( 'Install complete site', 'creceweb-lumen-lite' ); ?></button></p>
				</form>
			</div>
		</article>
		<?php
	}

	/**
	 * @param array<string,mixed> $result Installation result.
	 * @return void
	 */
	private function render_result( array $result ): void {
		if ( empty( $result['status'] ) ) {
			return;
		}
		$status = in_array( $result['status'], array( 'success', 'warning', 'error' ), true ) ? $result['status'] : 'info';
		?>
		<div class="notice notice-<?php echo esc_attr( $status ); ?> cw-lumen-site-kit-result"<?php echo ! empty( $result['design_applied'] ) ? ' data-cw-site-kit-reset-color-mode="1"' : ''; ?>>
			<p><strong><?php echo esc_html( (string) ( $result['message'] ?? '' ) ); ?></strong></p>
			<?php if ( ! empty( $result['warnings'] ) && is_array( $result['warnings'] ) ) : ?>
				<ul><?php foreach ( $result['warnings'] as $warning ) : ?><li><?php echo esc_html( (string) $warning ); ?></li><?php endforeach; ?></ul>
			<?php endif; ?>
			<?php if ( ! empty( $result['front_page_id'] ) ) : ?>
				<p class="cw-lumen-site-kit-result__actions">
					<a class="button button-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'View site', 'creceweb-lumen-lite' ); ?></a>
					<a class="button" href="<?php echo esc_url( get_edit_post_link( absint( $result['front_page_id'] ), 'raw' ) ); ?>"><?php echo esc_html__( 'Edit Home', 'creceweb-lumen-lite' ); ?></a>
					<a class="button" href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>"><?php echo esc_html__( 'Manage menus', 'creceweb-lumen-lite' ); ?></a>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * @param string $base Requested menu name.
	 * @return string
	 */
	private function unique_menu_name( string $base ): string {
		$base = '' !== trim( $base ) ? trim( $base ) : __( 'Lumen Site Kit', 'creceweb-lumen-lite' );
		$name = $base;
		$i    = 2;
		while ( wp_get_nav_menu_object( $name ) ) {
			$name = sprintf( '%1$s %2$d', $base, $i++ );
		}
		return $name;
	}

	/** @return string */
	private function section_url(): string {
		$fallback = add_query_arg(
			array(
				'page' => 'creceweb-lumen-lite',
				'tab'  => 'lite-site-kits',
			),
			admin_url( 'themes.php' )
		);

		return (string) apply_filters( 'creceweb_lumen_lite_admin_section_url', $fallback, 'lite-site-kits', '' );
	}
}
