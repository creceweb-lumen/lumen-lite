<?php
/**
 * Accessible native breadcrumbs for Lumen Theme.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Breadcrumbs;

use CreceWeb\LumenLite\Data\Settings;
use CreceWeb\LumenLite\Support\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds one native breadcrumb trail from the current WordPress query.
 */
final class Controller {
	private const REQUIRED_THEME_BRIDGE = '1.9.4';

	public function __construct( private Compatibility $compatibility ) {}

	/** @return void */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 35 );
		add_action( 'creceweb_lumen_breadcrumb_area', array( $this, 'render' ), 10, 1 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Adds a stable body class only on requests where the breadcrumb band renders.
	 *
	 * @param array<int,string> $classes Existing body classes.
	 * @return array<int,string>
	 */
	public function body_class( array $classes ): array {
		if ( $this->should_render() ) {
			$classes[] = 'cw-lumen-has-breadcrumbs';
		}

		return array_values( array_unique( $classes ) );
	}

	/** @return array<string,mixed> */
	public function settings(): array {
		$settings = Settings::get();
		$value    = isset( $settings['breadcrumbs'] ) && is_array( $settings['breadcrumbs'] ) ? $settings['breadcrumbs'] : array();

		return wp_parse_args(
			$value,
			array(
				'enabled'        => false,
				'home_label'       => __( 'Home', 'creceweb-lumen-lite' ),
				'separator'        => '›',
				'background_style' => 'auto',
				'show_border'      => true,
				'show_home'        => true,
				'show_current'   => true,
				'schema_enabled' => true,
			)
		);
	}

	/** @return bool */
	public function bridge_supported(): bool {
		$version = defined( 'CRECEWEB_LUMEN_BRIDGE_API_VERSION' )
			? (string) CRECEWEB_LUMEN_BRIDGE_API_VERSION
			: $this->compatibility->get_bridge_version();

		return '' !== $version && version_compare( $version, self::REQUIRED_THEME_BRIDGE, '>=' );
	}

	/** @return bool */
	public function should_render(): bool {
		$settings = $this->settings();
		$is_static_front_page = is_front_page() && ! is_home();
		$enabled  = ! empty( $settings['enabled'] )
			&& $this->compatibility->validate()
			&& $this->bridge_supported()
			&& ! is_admin()
			&& ! $is_static_front_page
			&& ! is_feed()
			&& ! is_embed();

		/**
		 * Filters whether native breadcrumbs should render for the current request.
		 *
		 * @param bool $enabled Current decision.
		 */
		return (bool) apply_filters( 'creceweb_lumen_lite_breadcrumbs_should_render', $enabled );
	}

	/** @return void */
	public function enqueue_assets(): void {
		if ( ! $this->should_render() ) {
			return;
		}

		wp_enqueue_style(
			'creceweb-lumen-lite-content',
			CRECEWEB_LUMEN_LITE_URL . 'assets/css/content.css',
			array(),
			CRECEWEB_LUMEN_LITE_ASSET_VERSION
		);
	}

	/**
	 * @return array<int,array{label:string,url:string,current:bool}>
	 */
	public function items(): array {
		if ( ! $this->should_render() ) {
			return array();
		}

		$settings = $this->settings();
		$items    = array();

		if ( ! empty( $settings['show_home'] ) ) {
			$items[] = $this->item(
				trim( (string) $settings['home_label'] ) ?: __( 'Home', 'creceweb-lumen-lite' ),
				home_url( '/' ),
				false
			);
		}

		if ( is_home() ) {
			$items[] = $this->current_item( $this->blog_label(), $this->blog_url() );
		} elseif ( is_singular() ) {
			$items = array_merge( $items, $this->singular_items() );
		} elseif ( is_category() ) {
			$items = array_merge( $items, $this->blog_prefix_items(), $this->term_items( get_queried_object() ) );
		} elseif ( is_tag() ) {
			$items = array_merge( $items, $this->blog_prefix_items() );
			$term  = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$items[] = $this->current_item( (string) $term->name, $this->term_url( $term ) );
			}
		} elseif ( is_tax() ) {
			$items = array_merge( $items, $this->taxonomy_archive_prefix_items(), $this->term_items( get_queried_object() ) );
		} elseif ( is_author() ) {
			$items = array_merge( $items, $this->blog_prefix_items() );
			$author = get_queried_object();
			$name   = $author instanceof \WP_User ? (string) $author->display_name : '';
			/* translators: %s: Author display name. */
			$items[] = $this->current_item( sprintf( __( 'Author: %s', 'creceweb-lumen-lite' ), $name ), $this->current_url() );
		} elseif ( is_date() ) {
			$items = array_merge( $items, $this->blog_prefix_items(), $this->date_items() );
		} elseif ( is_post_type_archive() ) {
			$items[] = $this->current_item( $this->archive_label(), $this->current_url() );
		} elseif ( is_search() ) {
			/* translators: %s: Search query. */
			$items[] = $this->current_item( sprintf( __( 'Search results for: %s', 'creceweb-lumen-lite' ), get_search_query() ), $this->current_url() );
		} elseif ( is_404() ) {
			$items[] = $this->current_item( __( 'Page not found', 'creceweb-lumen-lite' ), '' );
		} elseif ( is_archive() ) {
			$items[] = $this->current_item( $this->archive_label(), $this->current_url() );
		}

		if ( empty( $settings['show_current'] ) ) {
			$items = array_values(
				array_filter(
					$items,
					static fn( array $item ): bool => empty( $item['current'] )
				)
			);
		}

		/**
		 * Filters the normalized breadcrumb items.
		 *
		 * Each item uses label, url, and current keys.
		 *
		 * @param array<int,array{label:string,url:string,current:bool}> $items Breadcrumb items.
		 */
		$items = apply_filters( 'creceweb_lumen_lite_breadcrumb_items', $items );
		$items = is_array( $items ) ? $items : array();

		return $this->normalize_items( $items );
	}

	/** @return void */
	public function render( string $placement = 'after_header' ): void {
		$items = $this->items();
		if ( empty( $items ) ) {
			return;
		}

		$settings  = $this->settings();
		$separator = (string) ( $settings['separator'] ?? '›' );
		$background_style = sanitize_key( (string) ( $settings['background_style'] ?? 'auto' ) );
		if ( ! in_array( $background_style, array( 'auto', 'surface', 'background' ), true ) ) {
			$background_style = 'auto';
		}
		$show_border = ! array_key_exists( 'show_border', $settings ) || ! empty( $settings['show_border'] );
		?>
		<nav class="cw-lumen-breadcrumbs cw-lumen-breadcrumbs--<?php echo esc_attr( 'after_hero' === $placement ? 'after-hero' : 'after-header' ); ?> cw-lumen-breadcrumbs--bg-<?php echo esc_attr( $background_style ); ?><?php echo $show_border ? '' : ' cw-lumen-breadcrumbs--no-border'; ?>" aria-label="<?php echo esc_attr__( 'Breadcrumb', 'creceweb-lumen-lite' ); ?>">
			<div class="cw-lumen-breadcrumbs__inner">
				<ol class="cw-lumen-breadcrumbs__list">
					<?php foreach ( $items as $index => $item ) : ?>
						<?php if ( $index > 0 ) : ?><li class="cw-lumen-breadcrumbs__separator" aria-hidden="true"><?php echo esc_html( $separator ); ?></li><?php endif; ?>
						<li class="cw-lumen-breadcrumbs__item<?php echo ! empty( $item['current'] ) ? ' is-current' : ''; ?>">
							<?php if ( ! empty( $item['url'] ) && empty( $item['current'] ) ) : ?>
								<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
							<?php else : ?>
								<span<?php echo ! empty( $item['current'] ) ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $item['label'] ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		</nav>
		<?php

		if ( ! empty( $settings['schema_enabled'] ) ) {
			$this->render_schema( $items );
		}
	}

	/**
	 * @param array<int,array{label:string,url:string,current:bool}> $items Breadcrumb items.
	 * @return void
	 */
	private function render_schema( array $items ): void {
		$list = array();
		foreach ( $items as $index => $item ) {
			$entry = array(
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'name'     => $item['label'],
			);
			if ( '' !== $item['url'] ) {
				$entry['item'] = $item['url'];
			}
			$list[] = $entry;
		}

		if ( empty( $list ) ) {
			return;
		}

		$schema = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $list,
		);

		echo '<script type="application/ld+json" class="cw-lumen-breadcrumbs-schema">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode produces the complete JSON-LD payload.
	}

	/**
	 * @return array<int,array{label:string,url:string,current:bool}>
	 */
	private function singular_items(): array {
		$post_id   = get_queried_object_id();
		$post_type = get_post_type( $post_id );
		if ( $post_id < 1 || ! is_string( $post_type ) || '' === $post_type ) {
			return array();
		}

		$items = array();

		if ( 'post' === $post_type ) {
			$items = array_merge( $items, $this->blog_prefix_items() );
		} else {
			$post_type_object = get_post_type_object( $post_type );
			if ( $post_type_object instanceof \WP_Post_Type && ! empty( $post_type_object->has_archive ) ) {
				$url = get_post_type_archive_link( $post_type );
				if ( is_string( $url ) && '' !== $url ) {
					$items[] = $this->item( (string) $post_type_object->labels->name, $url, false );
				}
			}
		}

		if ( is_post_type_hierarchical( $post_type ) ) {
			$ancestors = array_reverse( get_post_ancestors( $post_id ) );
			foreach ( $ancestors as $ancestor_id ) {
				$label = get_the_title( $ancestor_id );
				$url   = get_permalink( $ancestor_id );
				if ( is_string( $label ) && '' !== trim( $label ) && is_string( $url ) && '' !== $url ) {
					$items[] = $this->item( $label, $url, false );
				}
			}
		}

		$label = get_the_title( $post_id );
		if ( ! is_string( $label ) || '' === trim( $label ) ) {
			$label = get_the_title();
		}
		$items[] = $this->current_item( (string) $label, get_permalink( $post_id ) );

		return $items;
	}

	/**
	 * @param mixed $queried Queried term.
	 * @return array<int,array{label:string,url:string,current:bool}>
	 */
	private function term_items( $queried ): array {
		if ( ! $queried instanceof \WP_Term ) {
			return array();
		}

		$items = array();
		if ( is_taxonomy_hierarchical( $queried->taxonomy ) ) {
			$ancestors = array_reverse( get_ancestors( $queried->term_id, $queried->taxonomy, 'taxonomy' ) );
			foreach ( $ancestors as $ancestor_id ) {
				$term = get_term( $ancestor_id, $queried->taxonomy );
				if ( $term instanceof \WP_Term ) {
					$url = $this->term_url( $term );
					if ( '' !== $url ) {
						$items[] = $this->item( (string) $term->name, $url, false );
					}
				}
			}
		}

		$items[] = $this->current_item( (string) $queried->name, $this->term_url( $queried ) );

		return $items;
	}

	/**
	 * @return array<int,array{label:string,url:string,current:bool}>
	 */
	private function taxonomy_archive_prefix_items(): array {
		$term = get_queried_object();
		if ( ! $term instanceof \WP_Term ) {
			return array();
		}

		$taxonomy = get_taxonomy( $term->taxonomy );
		if ( ! $taxonomy instanceof \WP_Taxonomy || ! is_array( $taxonomy->object_type ) || 1 !== count( $taxonomy->object_type ) ) {
			return array();
		}

		$post_type = sanitize_key( (string) reset( $taxonomy->object_type ) );
		if ( '' === $post_type || 'post' === $post_type ) {
			return $this->blog_prefix_items();
		}

		$post_type_object = get_post_type_object( $post_type );
		if ( ! $post_type_object instanceof \WP_Post_Type || empty( $post_type_object->has_archive ) ) {
			return array();
		}

		$url = get_post_type_archive_link( $post_type );
		if ( ! is_string( $url ) || '' === $url ) {
			return array();
		}

		return array( $this->item( (string) $post_type_object->labels->name, $url, false ) );
	}

	/**
	 * @return array<int,array{label:string,url:string,current:bool}>
	 */
	private function date_items(): array {
		$items = array();
		$year  = absint( get_query_var( 'year' ) );
		$month = absint( get_query_var( 'monthnum' ) );
		$day   = absint( get_query_var( 'day' ) );

		if ( $year > 0 ) {
			$year_label = (string) $year;
			$items[] = $day > 0 || $month > 0
				? $this->item( $year_label, get_year_link( $year ), false )
				: $this->current_item( $year_label, get_year_link( $year ) );
		}

		if ( $month > 0 ) {
			$month_label = wp_date( 'F', mktime( 0, 0, 0, $month, 1, max( 1970, $year ) ) );
			$items[] = $day > 0
				? $this->item( $month_label, get_month_link( $year, $month ), false )
				: $this->current_item( $month_label, get_month_link( $year, $month ) );
		}

		if ( $day > 0 ) {
			$items[] = $this->current_item( (string) $day, get_day_link( $year, $month, $day ) );
		}

		return $items;
	}

	/**
	 * @return array<int,array{label:string,url:string,current:bool}>
	 */
	private function blog_prefix_items(): array {
		$url = $this->blog_url();
		if ( '' === $url ) {
			return array();
		}

		return array( $this->item( $this->blog_label(), $url, false ) );
	}

	/** @return string */
	private function blog_label(): string {
		$page_id = absint( get_option( 'page_for_posts' ) );
		if ( $page_id > 0 ) {
			$title = get_the_title( $page_id );
			if ( is_string( $title ) && '' !== trim( $title ) ) {
				return $title;
			}
		}

		return __( 'Blog', 'creceweb-lumen-lite' );
	}

	/** @return string */
	private function blog_url(): string {
		$page_id = absint( get_option( 'page_for_posts' ) );
		if ( $page_id < 1 ) {
			return '';
		}

		$url = get_permalink( $page_id );

		return is_string( $url ) ? $url : '';
	}

	/** @return string */
	private function archive_label(): string {
		$title = get_the_archive_title();

		return trim( wp_strip_all_tags( is_string( $title ) ? $title : '' ) );
	}

	/** @return string */
	private function current_url(): string {
		if ( is_singular() ) {
			$url = get_permalink( get_queried_object_id() );
			return is_string( $url ) ? $url : '';
		}

		if ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			return $term instanceof \WP_Term ? $this->term_url( $term ) : '';
		}

		if ( is_post_type_archive() ) {
			$post_type = get_query_var( 'post_type' );
			$post_type = is_array( $post_type ) ? (string) reset( $post_type ) : (string) $post_type;
			$url = '' !== $post_type ? get_post_type_archive_link( $post_type ) : false;
			return is_string( $url ) ? $url : '';
		}

		return '';
	}

	/**
	 * @param \WP_Term $term Term.
	 * @return string
	 */
	private function term_url( \WP_Term $term ): string {
		$url = get_term_link( $term );

		return is_wp_error( $url ) ? '' : (string) $url;
	}

	/**
	 * @param string $label Label.
	 * @param string $url URL.
	 * @param bool   $current Current item.
	 * @return array{label:string,url:string,current:bool}
	 */
	private function item( string $label, string $url, bool $current ): array {
		return array(
			'label'   => trim( wp_strip_all_tags( $label ) ),
			'url'     => esc_url_raw( $url ),
			'current' => $current,
		);
	}

	/**
	 * @param string $label Label.
	 * @param mixed  $url URL.
	 * @return array{label:string,url:string,current:bool}
	 */
	private function current_item( string $label, $url ): array {
		return $this->item( $label, is_string( $url ) ? $url : '', true );
	}

	/**
	 * @param array<int,mixed> $items Raw filtered items.
	 * @return array<int,array{label:string,url:string,current:bool}>
	 */
	private function normalize_items( array $items ): array {
		$normalized = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || ! is_scalar( $item['label'] ?? null ) ) {
				continue;
			}

			$label = trim( wp_strip_all_tags( (string) $item['label'] ) );
			if ( '' === $label ) {
				continue;
			}

			$url = is_scalar( $item['url'] ?? null ) ? esc_url_raw( (string) $item['url'] ) : '';
			$normalized[] = array(
				'label'   => $label,
				'url'     => $url,
				'current' => ! empty( $item['current'] ),
			);
		}

		return $normalized;
	}
}
