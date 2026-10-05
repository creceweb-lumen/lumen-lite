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
	private const META_INDICATOR_TYPE = '_cw_lumen_lite_menu_indicator_type';
	private const META_INDICATOR_TEXT = '_cw_lumen_lite_menu_indicator_text';
	private const META_INDICATOR_ICON = '_cw_lumen_lite_menu_indicator_icon';
	private const META_INDICATOR_IMAGE = '_cw_lumen_lite_menu_indicator_image';
	private const META_INDICATOR_VISIBILITY = '_cw_lumen_lite_menu_indicator_visibility';
	private const DEVICES = array( 'desktop', 'tablet', 'mobile' );

	public function __construct( private Compatibility $compatibility ) {}

	/** @return void */
	public function register(): void {
		add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'render_item_fields' ), 10, 5 );
		add_action( 'wp_update_nav_menu_item', array( $this, 'save_item_fields' ), 10, 2 );
		add_action( 'deleted_post', array( $this, 'refresh_custom_item_flag_after_delete' ), 10, 2 );
		add_filter( 'nav_menu_css_class', array( $this, 'filter_item_classes' ), 10, 4 );
		add_filter( 'nav_menu_item_title', array( $this, 'filter_item_title' ), 10, 4 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_menu_editor_assets' ) );
	}

	/** @return bool */
	public function needs_assets(): bool {
		if ( ! $this->compatibility->is_compatible() ) {
			return false;
		}

		$settings = Settings::get();
		if ( ! empty( $settings['menu_has_item_customizations'] ) ) {
			return true;
		}

		if ( empty( $settings['menu_customizations_needs_reindex'] ) ) {
			return false;
		}

		$has_custom_items = $this->has_any_custom_items();
		Settings::update_menu_item_customizations( $has_custom_items );

		return $has_custom_items;
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
		$button         = '1' === (string) get_post_meta( $item_id, self::META_BUTTON, true );
		$devices        = $this->devices_for_item( $item_id );
		$indicator_type = $this->indicator_type_for_item( $item_id );
		$indicator_text = sanitize_text_field( (string) get_post_meta( $item_id, self::META_INDICATOR_TEXT, true ) );
		$indicator_icon = sanitize_key( (string) get_post_meta( $item_id, self::META_INDICATOR_ICON, true ) );
		$indicator_image = absint( get_post_meta( $item_id, self::META_INDICATOR_IMAGE, true ) );
		$indicator_devices = $this->indicator_devices_for_item( $item_id );
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
			<div class="cw-lumen-lite-menu-indicator" data-cw-lumen-menu-indicator>
				<p class="description description-wide"><label for="edit-menu-item-cw-lumen-lite-indicator-type-<?php echo esc_attr( (string) $item_id ); ?>"><strong><?php echo esc_html__( 'Menu indicator', 'creceweb-lumen-lite' ); ?></strong></label><select id="edit-menu-item-cw-lumen-lite-indicator-type-<?php echo esc_attr( (string) $item_id ); ?>" name="menu-item-cw-lumen-lite-indicator-type[<?php echo esc_attr( (string) $item_id ); ?>]" data-cw-lumen-menu-indicator-type><option value="none" <?php selected( $indicator_type, 'none' ); ?>><?php echo esc_html__( 'None', 'creceweb-lumen-lite' ); ?></option><option value="text" <?php selected( $indicator_type, 'text' ); ?>><?php echo esc_html__( 'Text', 'creceweb-lumen-lite' ); ?></option><option value="icon" <?php selected( $indicator_type, 'icon' ); ?>><?php echo esc_html__( 'Icon', 'creceweb-lumen-lite' ); ?></option><option value="image" <?php selected( $indicator_type, 'image' ); ?>><?php echo esc_html__( 'Image', 'creceweb-lumen-lite' ); ?></option></select></p>
				<p class="description description-wide" data-cw-lumen-menu-indicator-panel="text"><label><?php echo esc_html__( 'Text', 'creceweb-lumen-lite' ); ?><input type="text" class="widefat" name="menu-item-cw-lumen-lite-indicator-text[<?php echo esc_attr( (string) $item_id ); ?>]" value="<?php echo esc_attr( $indicator_text ); ?>" placeholder="01"></label></p>
				<p class="description description-wide" data-cw-lumen-menu-indicator-panel="icon"><label><?php echo esc_html__( 'Icon', 'creceweb-lumen-lite' ); ?><select class="widefat" name="menu-item-cw-lumen-lite-indicator-icon[<?php echo esc_attr( (string) $item_id ); ?>]"><?php foreach ( $this->icon_choices() as $icon_key => $icon_label ) : ?><option value="<?php echo esc_attr( $icon_key ); ?>" <?php selected( $indicator_icon, $icon_key ); ?>><?php echo esc_html( $icon_label ); ?></option><?php endforeach; ?></select></label></p>
				<div class="description description-wide" data-cw-lumen-menu-indicator-panel="image">
					<input type="hidden" name="menu-item-cw-lumen-lite-indicator-image[<?php echo esc_attr( (string) $item_id ); ?>]" value="<?php echo esc_attr( (string) $indicator_image ); ?>" data-cw-lumen-menu-indicator-image-id>
					<div class="cw-lumen-lite-menu-indicator__image-preview" data-cw-lumen-menu-indicator-image-preview><?php if ( $indicator_image > 0 ) { echo wp_get_attachment_image( $indicator_image, 'thumbnail', false, array( 'alt' => '' ) ); } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core attachment HTML. ?></div>
					<p><button type="button" class="button" data-cw-lumen-menu-indicator-image-select><?php echo esc_html__( 'Choose image', 'creceweb-lumen-lite' ); ?></button> <button type="button" class="button-link-delete" data-cw-lumen-menu-indicator-image-remove><?php echo esc_html__( 'Remove', 'creceweb-lumen-lite' ); ?></button></p>
				</div>
				<fieldset class="cw-lumen-lite-menu-devices cw-lumen-lite-menu-indicator-devices" data-cw-lumen-menu-indicator-visibility<?php echo 'none' === $indicator_type ? ' hidden' : ''; ?>>
					<legend><?php echo esc_html__( 'Show indicator on', 'creceweb-lumen-lite' ); ?></legend>
					<label><input type="checkbox" name="menu-item-cw-lumen-lite-indicator-devices[<?php echo esc_attr( (string) $item_id ); ?>][]" value="desktop" <?php checked( in_array( 'desktop', $indicator_devices, true ) ); ?>> <?php echo esc_html__( 'Computers', 'creceweb-lumen-lite' ); ?></label>
					<label><input type="checkbox" name="menu-item-cw-lumen-lite-indicator-devices[<?php echo esc_attr( (string) $item_id ); ?>][]" value="tablet" <?php checked( in_array( 'tablet', $indicator_devices, true ) ); ?>> <?php echo esc_html__( 'Tablets', 'creceweb-lumen-lite' ); ?></label>
					<label><input type="checkbox" name="menu-item-cw-lumen-lite-indicator-devices[<?php echo esc_attr( (string) $item_id ); ?>][]" value="mobile" <?php checked( in_array( 'mobile', $indicator_devices, true ) ); ?>> <?php echo esc_html__( 'Phones', 'creceweb-lumen-lite' ); ?></label>
					<p class="description"><?php echo esc_html__( 'Choose where the indicator appears. The link itself keeps the separate visibility selection below.', 'creceweb-lumen-lite' ); ?></p>
				</fieldset>
				<p class="description"><?php echo esc_html__( 'The indicator appears before the link name on the selected devices. Text can be used for values such as 01, NEW or PRO.', 'creceweb-lumen-lite' ); ?></p>
			</div>
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
		$indicator_types = isset( $_POST['menu-item-cw-lumen-lite-indicator-type'] ) && is_array( $_POST['menu-item-cw-lumen-lite-indicator-type'] ) ? map_deep( wp_unslash( $_POST['menu-item-cw-lumen-lite-indicator-type'] ), 'sanitize_key' ) : array();
		$indicator_texts = isset( $_POST['menu-item-cw-lumen-lite-indicator-text'] ) && is_array( $_POST['menu-item-cw-lumen-lite-indicator-text'] ) ? map_deep( wp_unslash( $_POST['menu-item-cw-lumen-lite-indicator-text'] ), 'sanitize_text_field' ) : array();
		$indicator_icons = isset( $_POST['menu-item-cw-lumen-lite-indicator-icon'] ) && is_array( $_POST['menu-item-cw-lumen-lite-indicator-icon'] ) ? map_deep( wp_unslash( $_POST['menu-item-cw-lumen-lite-indicator-icon'] ), 'sanitize_key' ) : array();
		$indicator_images = isset( $_POST['menu-item-cw-lumen-lite-indicator-image'] ) && is_array( $_POST['menu-item-cw-lumen-lite-indicator-image'] ) ? array_map( 'absint', wp_unslash( $_POST['menu-item-cw-lumen-lite-indicator-image'] ) ) : array();
		$indicator_device_values = isset( $_POST['menu-item-cw-lumen-lite-indicator-devices'] ) && is_array( $_POST['menu-item-cw-lumen-lite-indicator-devices'] ) ? map_deep( wp_unslash( $_POST['menu-item-cw-lumen-lite-indicator-devices'] ), 'sanitize_key' ) : array();

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

		$type = isset( $indicator_types[ $menu_item_db_id ] ) ? $this->normalize_indicator_type( (string) $indicator_types[ $menu_item_db_id ] ) : 'none';
		$value = '';
		if ( 'text' === $type ) { $value = (string) ( $indicator_texts[ $menu_item_db_id ] ?? '' ); }
		if ( 'icon' === $type ) { $value = (string) ( $indicator_icons[ $menu_item_db_id ] ?? '' ); }
		if ( 'image' === $type ) { $value = (string) absint( $indicator_images[ $menu_item_db_id ] ?? 0 ); }
		$this->set_item_indicator( $menu_item_db_id, $type, $value, false );
		if ( 'none' !== $type ) {
			$indicator_devices = isset( $indicator_device_values[ $menu_item_db_id ] ) && is_array( $indicator_device_values[ $menu_item_db_id ] )
				? $this->normalize_devices( $indicator_device_values[ $menu_item_db_id ] )
				: array( 'mobile' );
			$this->set_item_indicator_devices( $menu_item_db_id, $indicator_devices, false );
		}

		Settings::update_menu_item_customizations( $this->has_any_custom_items() );
	}



	/**
	 * Stores one optional menu indicator for a menu item.
	 *
	 * @param int        $item_id Menu item ID.
	 * @param string     $type Indicator type: none, text, icon or image.
	 * @param string|int $value Type-specific value.
	 * @param bool       $refresh_flag Whether to refresh the global conditional-assets flag.
	 * @return void
	 */
	public function set_item_indicator( int $item_id, string $type = 'none', string|int $value = '', bool $refresh_flag = true ): void {
		$item_id = absint( $item_id );
		if ( $item_id < 1 ) {
			return;
		}
		$type = $this->normalize_indicator_type( $type );
		delete_post_meta( $item_id, self::META_INDICATOR_TEXT );
		delete_post_meta( $item_id, self::META_INDICATOR_ICON );
		delete_post_meta( $item_id, self::META_INDICATOR_IMAGE );

		if ( 'text' === $type ) {
			$value = sanitize_text_field( (string) $value );
			if ( '' !== $value ) {
				update_post_meta( $item_id, self::META_INDICATOR_TEXT, $value );
			} else {
				$type = 'none';
			}
		} elseif ( 'icon' === $type ) {
			$value = sanitize_key( (string) $value );
			if ( array_key_exists( $value, $this->icon_choices() ) ) {
				update_post_meta( $item_id, self::META_INDICATOR_ICON, $value );
			} else {
				$type = 'none';
			}
		} elseif ( 'image' === $type ) {
			$image_id = absint( $value );
			if ( $image_id > 0 && 'attachment' === get_post_type( $image_id ) ) {
				update_post_meta( $item_id, self::META_INDICATOR_IMAGE, $image_id );
			} else {
				$type = 'none';
			}
		}

		if ( 'none' === $type ) {
			delete_post_meta( $item_id, self::META_INDICATOR_TYPE );
			delete_post_meta( $item_id, self::META_INDICATOR_VISIBILITY );
		} else {
			update_post_meta( $item_id, self::META_INDICATOR_TYPE, $type );
			if ( ! metadata_exists( 'post', $item_id, self::META_INDICATOR_VISIBILITY ) ) {
				update_post_meta( $item_id, self::META_INDICATOR_VISIBILITY, 'mobile' );
			}
		}
		if ( $refresh_flag ) {
			Settings::update_menu_item_customizations( $this->has_any_custom_items() );
		}
	}


	/**
	 * Stores the devices where an item's decorative indicator is visible.
	 *
	 * @param int               $item_id Menu item ID.
	 * @param array<int,string> $devices Device identifiers.
	 * @param bool              $refresh_flag Whether to refresh the global conditional-assets flag.
	 * @return void
	 */
	public function set_item_indicator_devices( int $item_id, array $devices, bool $refresh_flag = true ): void {
		$item_id = absint( $item_id );
		if ( $item_id < 1 || 'none' === $this->indicator_type_for_item( $item_id ) ) {
			return;
		}
		$devices = $this->normalize_devices( $devices );
		update_post_meta( $item_id, self::META_INDICATOR_VISIBILITY, self::DEVICES === $devices ? 'all' : implode( ',', $devices ) );
		if ( $refresh_flag ) {
			Settings::update_menu_item_customizations( true );
		}
	}

	/**
	 * Programmatically toggle the existing Menu Highlight presentation.
	 *
	 * @param int  $item_id Menu item ID.
	 * @param bool $enabled Whether the item should be highlighted.
	 * @return void
	 */
	public function set_item_button( int $item_id, bool $enabled = true ): void {
		$item_id = absint( $item_id );
		if ( $item_id < 1 ) {
			return;
		}

		if ( $enabled ) {
			update_post_meta( $item_id, self::META_BUTTON, '1' );
			Settings::update_menu_item_customizations( true );
			return;
		}

		delete_post_meta( $item_id, self::META_BUTTON );
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
	 * Adds the saved decorative indicator before the native menu-item title.
	 * Theme remains responsible for link/row geometry.
	 *
	 * @param string   $title Native menu title.
	 * @param \WP_Post $menu_item Menu item object.
	 * @param mixed    $args Menu arguments.
	 * @param int      $depth Menu depth.
	 * @return string
	 */
	public function filter_item_title( string $title, \WP_Post $menu_item, mixed $args, int $depth ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WordPress filter signature.
		if ( ! $this->compatibility->is_compatible() ) {
			return $title;
		}
		$indicator = $this->render_indicator( (int) $menu_item->ID );
		if ( '' === $indicator ) {
			return $title;
		}
		return $indicator . '<span class="cw-lumen-lite-menu-item__label">' . $title . '</span>';
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
		wp_enqueue_media();
		wp_enqueue_script( 'creceweb-lumen-lite-menu-admin', CRECEWEB_LUMEN_LITE_URL . 'assets/js/menu-admin.js', array( 'jquery' ), CRECEWEB_LUMEN_LITE_ASSET_VERSION, true );
	}


	/** @return array<string,string> */
	private function icon_choices(): array {
		return array(
			'star' => __( 'Star', 'creceweb-lumen-lite' ),
			'home' => __( 'Home', 'creceweb-lumen-lite' ),
			'grid' => __( 'Grid', 'creceweb-lumen-lite' ),
			'briefcase' => __( 'Briefcase', 'creceweb-lumen-lite' ),
			'folder' => __( 'Folder', 'creceweb-lumen-lite' ),
			'user' => __( 'User', 'creceweb-lumen-lite' ),
			'mail' => __( 'Mail', 'creceweb-lumen-lite' ),
			'arrow' => __( 'Arrow', 'creceweb-lumen-lite' ),
		);
	}

	/** @return string */
	private function normalize_indicator_type( string $type ): string {
		$type = sanitize_key( $type );
		return in_array( $type, array( 'text', 'icon', 'image' ), true ) ? $type : 'none';
	}

	/** @return string */
	private function indicator_type_for_item( int $item_id ): string {
		return $this->normalize_indicator_type( (string) get_post_meta( $item_id, self::META_INDICATOR_TYPE, true ) );
	}

	/** @return string */
	private function render_indicator( int $item_id ): string {
		$type = $this->indicator_type_for_item( $item_id );
		if ( 'none' === $type ) {
			return '';
		}
		$devices = $this->indicator_devices_for_item( $item_id );
		$classes = array( 'cw-lumen-lite-menu-item__indicator', 'cw-lumen-lite-menu-item__indicator--' . $type );
		foreach ( self::DEVICES as $device ) {
			if ( ! in_array( $device, $devices, true ) ) {
				$classes[] = 'cw-lumen-lite-menu-item__indicator--hide-' . $device;
			}
		}
		$class_attr = esc_attr( implode( ' ', $classes ) );
		if ( 'text' === $type ) {
			$text = sanitize_text_field( (string) get_post_meta( $item_id, self::META_INDICATOR_TEXT, true ) );
			return '' !== $text ? '<span class="' . $class_attr . '" aria-hidden="true">' . esc_html( $text ) . '</span>' : '';
		}
		if ( 'icon' === $type ) {
			$icon = sanitize_key( (string) get_post_meta( $item_id, self::META_INDICATOR_ICON, true ) );
			$svg = $this->icon_svg( $icon );
			return '' !== $svg ? '<span class="' . $class_attr . '" aria-hidden="true">' . $svg . '</span>' : '';
		}
		if ( 'image' === $type ) {
			$image_id = absint( get_post_meta( $item_id, self::META_INDICATOR_IMAGE, true ) );
			if ( $image_id < 1 ) { return ''; }
			$image = wp_get_attachment_image( $image_id, 'thumbnail', false, array( 'class' => 'cw-lumen-lite-menu-item__indicator-image', 'alt' => '', 'aria-hidden' => 'true' ) );
			return is_string( $image ) && '' !== $image ? '<span class="' . $class_attr . '" aria-hidden="true">' . $image . '</span>' : '';
		}
		return '';
	}

	/** @return string */
	private function icon_svg( string $icon ): string {
		$paths = array(
			'star' => '<path d="M12 2.5l2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.3l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94L12 2.5z"/>',
			'home' => '<path d="M3 10.5L12 3l9 7.5V21h-6v-6H9v6H3V10.5z"/>',
			'grid' => '<path d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z"/>',
			'briefcase' => '<path d="M9 5V3h6v2h5a2 2 0 012 2v11a2 2 0 01-2 2H4a2 2 0 01-2-2V7a2 2 0 012-2h5zm2 0h2V4h-2v1zm-7 5v8h16v-8h-6v2h-4v-2H4z"/>',
			'folder' => '<path d="M3 5h7l2 2h9v12H3V5z"/>',
			'user' => '<path d="M12 12a4 4 0 100-8 4 4 0 000 8zm-7 9a7 7 0 0114 0H5z"/>',
			'mail' => '<path d="M3 5h18v14H3V5zm2 2v.2l7 5.25 7-5.25V7H5zm14 10V9.7l-7 5.25L5 9.7V17h14z"/>',
			'arrow' => '<path d="M5 11h10.17l-3.59-3.59L13 6l6 6-6 6-1.42-1.41L15.17 13H5v-2z"/>',
		);
		if ( ! isset( $paths[ $icon ] ) ) { return ''; }
		return '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true" fill="currentColor">' . $paths[ $icon ] . '</svg>';
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
	 * @param int $item_id Menu item ID.
	 * @return array<int,string>
	 */
	private function indicator_devices_for_item( int $item_id ): array {
		$stored = sanitize_text_field( (string) get_post_meta( $item_id, self::META_INDICATOR_VISIBILITY, true ) );
		if ( 'all' === $stored ) {
			return self::DEVICES;
		}
		if ( '' === $stored ) {
			// K2.7.17 indicators were mobile-only; keep that behavior when metadata is absent.
			return array( 'mobile' );
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
					array(
						'key'     => self::META_INDICATOR_TYPE,
						'compare' => 'EXISTS',
					),
				),
			)
		);

		return ! empty( $query->posts );
	}
}
