<?php
/**
 * Simple menu-item options supplied by Lumen Lite.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Menu;

use CreceWeb\LumenLite\Data\Settings;
use CreceWeb\LumenLite\Support\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds optional highlighted actions and reusable device visibility per menu item.
 */
final class Controller {
	private const META_BUTTON = '_cw_lumen_lite_menu_button';
	private const META_VISIBILITY = '_cw_lumen_lite_menu_visibility';
	private const DEVICES = array( 'desktop', 'tablet', 'mobile' );

	public function __construct( private Compatibility $compatibility ) {}

	/** @return void */
	public function register(): void {
		add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'render_item_fields' ), 10, 5 );
		add_action( 'wp_update_nav_menu_item', array( $this, 'save_item_fields' ), 10, 2 );
		add_action( 'deleted_post', array( $this, 'refresh_custom_item_flag_after_delete' ), 10, 2 );
		add_filter( 'nav_menu_css_class', array( $this, 'filter_item_classes' ), 10, 4 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_menu_editor_assets' ) );
	}

	/** @return bool */
	public function needs_assets(): bool {
		if ( ! $this->compatibility->is_compatible() ) {
			return false;
		}

		$settings = Settings::get();
		return ! empty( $settings['menu_has_item_customizations'] );
	}

	/**
	 * @param int      $item_id Menu item ID.
	 * @param \WP_Post $menu_item Menu item object.
	 * @param int      $depth Menu depth.
	 * @param mixed    $args Menu editor arguments.
	 * @param int      $current_object_id Current object ID.
	 * @return void
	 */
	public function render_item_fields( int $item_id, \WP_Post $menu_item, int $depth, mixed $args, int $current_object_id ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WordPress action signature.
		$button  = '1' === (string) get_post_meta( $item_id, self::META_BUTTON, true );
		$devices = $this->devices_for_item( $item_id );
		?>
		<div class="cw-lumen-lite-menu-item-options description-wide">
			<p class="description"><strong><?php echo esc_html__( 'Lumen Lite options', 'creceweb-lumen-lite' ); ?></strong></p>
			<p class="description description-wide">
				<label for="edit-menu-item-cw-lumen-lite-button-<?php echo esc_attr( (string) $item_id ); ?>">
					<input class="cw-lumen-lite-menu-highlight-control" type="checkbox" id="edit-menu-item-cw-lumen-lite-button-<?php echo esc_attr( (string) $item_id ); ?>" name="menu-item-cw-lumen-lite-button[<?php echo esc_attr( (string) $item_id ); ?>]" value="1" <?php checked( $button ); ?>>
					<?php echo esc_html__( 'Highlight this link as a button', 'creceweb-lumen-lite' ); ?>
				</label>
			</p>
			<p class="description"><?php echo esc_html__( 'You can highlight any number of links, including submenu links.', 'creceweb-lumen-lite' ); ?></p>
			<fieldset class="cw-lumen-lite-menu-devices">
				<legend><?php echo esc_html__( 'Show this link on', 'creceweb-lumen-lite' ); ?></legend>
				<label><input type="checkbox" name="menu-item-cw-lumen-lite-devices[<?php echo esc_attr( (string) $item_id ); ?>][]" value="desktop" <?php checked( in_array( 'desktop', $devices, true ) ); ?>> <?php echo esc_html__( 'Computers', 'creceweb-lumen-lite' ); ?></label>
				<label><input type="checkbox" name="menu-item-cw-lumen-lite-devices[<?php echo esc_attr( (string) $item_id ); ?>][]" value="tablet" <?php checked( in_array( 'tablet', $devices, true ) ); ?>> <?php echo esc_html__( 'Tablets', 'creceweb-lumen-lite' ); ?></label>
				<label><input type="checkbox" name="menu-item-cw-lumen-lite-devices[<?php echo esc_attr( (string) $item_id ); ?>][]" value="mobile" <?php checked( in_array( 'mobile', $devices, true ) ); ?>> <?php echo esc_html__( 'Phones', 'creceweb-lumen-lite' ); ?></label>
				<p class="description"><?php echo esc_html__( 'You can repeat this selection on every link and combine one or more devices.', 'creceweb-lumen-lite' ); ?></p>
			</fieldset>
		</div>
		<?php
	}

	/**
	 * @param int $menu_id Menu ID.
	 * @param int $menu_item_db_id Menu item ID.
	 * @return void
	 */
	public function save_item_fields( int $menu_id, int $menu_item_db_id ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed -- WordPress action signature.
		if ( ! current_user_can( 'edit_theme_options' ) || ! isset( $_POST['update-nav-menu-nonce'] ) ) {
			return;
		}

		check_admin_referer( 'update-nav_menu', 'update-nav-menu-nonce' );

		$button_values = isset( $_POST['menu-item-cw-lumen-lite-button'] ) && is_array( $_POST['menu-item-cw-lumen-lite-button'] )
			? map_deep( wp_unslash( $_POST['menu-item-cw-lumen-lite-button'] ), 'sanitize_text_field' )
			: array();
		$device_values = isset( $_POST['menu-item-cw-lumen-lite-devices'] ) && is_array( $_POST['menu-item-cw-lumen-lite-devices'] )
			? map_deep( wp_unslash( $_POST['menu-item-cw-lumen-lite-devices'] ), 'sanitize_key' )
			: array();

		$is_button = isset( $button_values[ $menu_item_db_id ] ) && '1' === (string) $button_values[ $menu_item_db_id ];
		if ( $is_button ) {
			update_post_meta( $menu_item_db_id, self::META_BUTTON, '1' );
		} else {
			delete_post_meta( $menu_item_db_id, self::META_BUTTON );
		}

		$devices = isset( $device_values[ $menu_item_db_id ] ) && is_array( $device_values[ $menu_item_db_id ] )
			? $this->normalize_devices( $device_values[ $menu_item_db_id ] )
			: self::DEVICES;

		if ( self::DEVICES === $devices ) {
			delete_post_meta( $menu_item_db_id, self::META_VISIBILITY );
		} else {
			update_post_meta( $menu_item_db_id, self::META_VISIBILITY, implode( ',', $devices ) );
		}

		Settings::update_menu_item_customizations( $this->has_any_custom_items() );
	}

	/**
	 * @param int      $post_id Deleted post ID.
	 * @param \WP_Post $post Deleted post object.
	 * @return void
	 */
	public function refresh_custom_item_flag_after_delete( int $post_id, \WP_Post $post ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed -- WordPress action signature.
		if ( 'nav_menu_item' === $post->post_type ) {
			Settings::update_menu_item_customizations( $this->has_any_custom_items() );
		}
	}

	/**
	 * @param array<int,string> $classes Existing classes.
	 * @param \WP_Post          $menu_item Menu item object.
	 * @param mixed             $args Menu arguments.
	 * @param int               $depth Menu depth.
	 * @return array<int,string>
	 */
	public function filter_item_classes( array $classes, \WP_Post $menu_item, mixed $args, int $depth ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WordPress filter signature.
		if ( ! $this->compatibility->is_compatible() ) {
			return $classes;
		}

		if ( '1' === (string) get_post_meta( $menu_item->ID, self::META_BUTTON, true ) ) {
			$classes[] = 'cw-lumen-lite-menu-item--button';
		}

		$devices = $this->devices_for_item( (int) $menu_item->ID );
		foreach ( self::DEVICES as $device ) {
			if ( ! in_array( $device, $devices, true ) ) {
				$classes[] = 'cw-lumen-lite-menu-item--hide-' . $device;
			}
		}

		return array_values( array_unique( array_filter( array_map( 'sanitize_html_class', $classes ) ) ) );
	}

	/**
	 * @param string $hook_suffix Current screen hook.
	 * @return void
	 */
	public function enqueue_menu_editor_assets( string $hook_suffix ): void {
		if ( 'nav-menus.php' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'creceweb-lumen-lite-admin', CRECEWEB_LUMEN_LITE_URL . 'assets/css/admin.css', array(), CRECEWEB_LUMEN_LITE_ASSET_VERSION );
	}

	/**
	 * @param int $item_id Menu item ID.
	 * @return array<int,string>
	 */
	private function devices_for_item( int $item_id ): array {
		$stored = sanitize_text_field( (string) get_post_meta( $item_id, self::META_VISIBILITY, true ) );
		if ( '' === $stored || 'all' === $stored ) {
			return self::DEVICES;
		}
		if ( 'desktop' === $stored ) {
			return array( 'desktop' );
		}
		if ( 'mobile' === $stored ) {
			return array( 'tablet', 'mobile' );
		}

		return $this->normalize_devices( explode( ',', $stored ) );
	}

	/**
	 * @param array<int|string,mixed> $devices Device identifiers.
	 * @return array<int,string>
	 */
	private function normalize_devices( array $devices ): array {
		$normalized = array();
		foreach ( $devices as $device ) {
			$device = sanitize_key( (string) $device );
			if ( in_array( $device, self::DEVICES, true ) ) {
				$normalized[] = $device;
			}
		}
		$normalized = array_values( array_unique( $normalized ) );
		if ( array() === $normalized ) {
			return self::DEVICES;
		}

		return array_values( array_intersect( self::DEVICES, $normalized ) );
	}

	/** @return bool */
	private function has_any_custom_items(): bool {
		$query = new \WP_Query(
			array(
				'post_type'              => 'nav_menu_item',
				'post_status'            => 'any',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Runs only when menu items are saved or deleted.
					'relation' => 'OR',
					array(
						'key'   => self::META_BUTTON,
						'value' => '1',
					),
					array(
						'key'     => self::META_VISIBILITY,
						'compare' => 'EXISTS',
					),
				),
			)
		);

		return ! empty( $query->posts );
	}
}
