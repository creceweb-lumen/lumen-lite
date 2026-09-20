<?php
/**
 * Automatic Table of Contents for Lumen Lite.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\TableOfContents;

use CreceWeb\LumenLite\Data\Settings;
use CreceWeb\LumenLite\Support\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds an accessible Table of Contents from rendered headings without
 * changing stored post content.
 */
final class Controller {
	public function __construct( private Compatibility $compatibility ) {}

	/** @return void */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 38 );
		add_filter( 'the_content', array( $this, 'filter_content' ), 999 );
	}

	/** @return array<string,mixed> */
	public function settings(): array {
		$settings = Settings::get();
		$toc      = isset( $settings['table_of_contents'] ) && is_array( $settings['table_of_contents'] ) ? $settings['table_of_contents'] : array();

		return wp_parse_args(
			$toc,
			array(
				'enabled'          => false,
				'title'            => __( 'Table of contents', 'creceweb-lumen-lite' ),
				'position'         => 'before_first_heading',
				'style'            => 'boxed',
				'minimum_headings' => 3,
				'heading_levels'   => array( 'h2', 'h3' ),
				'post_types'       => array(),
				'text_color'       => '',
				'link_color'       => '',
				'link_hover_color' => '',
				'background_color' => '',
				'border_color'     => '',
			)
		);
	}

	/**
	 * Returns every viewable public post type currently registered.
	 *
	 * @return array<string,string>
	 */
	public function available_post_types(): array {
		$objects = get_post_types( array( 'public' => true ), 'objects' );
		$objects = is_array( $objects ) ? $objects : array();
		$types   = array();

		foreach ( $objects as $key => $object ) {
			if ( ! is_object( $object ) ) {
				continue;
			}
			$name = sanitize_key( (string) ( $object->name ?? $key ) );
			if ( '' === $name || empty( $object->public ) ) {
				continue;
			}
			if ( function_exists( 'is_post_type_viewable' ) && ! is_post_type_viewable( $object ) ) {
				continue;
			}
			$label = isset( $object->labels->name ) ? sanitize_text_field( (string) $object->labels->name ) : $name;
			$types[ $name ] = '' !== $label ? $label : $name;
		}

		/**
		 * Filters public post types offered by the Table of Contents tool.
		 *
		 * @param array<string,string> $types Post-type labels keyed by slug.
		 */
		$filtered = apply_filters( 'creceweb_lumen_lite_toc_post_types', $types );
		return is_array( $filtered ) ? $filtered : $types;
	}

	/** @return bool */
	public function should_render(): bool {
		$this->compatibility->validate();
		$settings = $this->settings();
		if ( ! $this->compatibility->is_compatible() || empty( $settings['enabled'] ) || ! is_singular() || is_admin() ) {
			return false;
		}

		$post_id = get_queried_object_id();
		if ( $post_id < 1 || post_password_required( $post_id ) ) {
			return false;
		}

		$post_type = sanitize_key( (string) get_post_type( $post_id ) );
		$selected  = isset( $settings['post_types'] ) && is_array( $settings['post_types'] )
			? array_values( array_unique( array_filter( array_map( 'sanitize_key', $settings['post_types'] ) ) ) )
			: array();
		$available = $this->available_post_types();
		$enabled   = '' !== $post_type && isset( $available[ $post_type ] ) && in_array( $post_type, $selected, true );

		/**
		 * Filters whether the Table of Contents can render on the current singular view.
		 *
		 * @param bool   $enabled Current decision.
		 * @param int    $post_id Current post ID.
		 * @param string $post_type Current public post type.
		 * @param array<string,mixed> $settings Table of Contents settings.
		 */
		return (bool) apply_filters( 'creceweb_lumen_lite_toc_should_render', $enabled, $post_id, $post_type, $settings );
	}

	/** @return void */
	public function enqueue_assets(): void {
		if ( ! $this->should_render() ) {
			return;
		}

		wp_enqueue_style(
			'creceweb-lumen-lite-table-of-contents',
			CRECEWEB_LUMEN_LITE_URL . 'assets/css/table-of-contents.css',
			array(),
			CRECEWEB_LUMEN_LITE_ASSET_VERSION
		);
	}

	/** @return int */
	public function style_size_bytes(): int {
		$path = CRECEWEB_LUMEN_LITE_DIR . 'assets/css/table-of-contents.css';
		$size = is_readable( $path ) ? filesize( $path ) : false;
		return false === $size ? 0 : max( 0, (int) $size );
	}

	/**
	 * Adds generated runtime anchors and inserts the TOC into rendered content.
	 *
	 * @param string $content Rendered post content.
	 * @return string
	 */
	public function filter_content( string $content ): string {
		if ( '' === trim( $content ) || str_contains( $content, 'data-cw-lumen-toc' ) || ! $this->should_render() ) {
			return $content;
		}

		$post_id = get_queried_object_id();
		if ( $post_id < 1 || ( function_exists( 'get_the_ID' ) && get_the_ID() > 0 && get_the_ID() !== $post_id ) ) {
			return $content;
		}

		$settings = $this->settings();
		$levels   = isset( $settings['heading_levels'] ) && is_array( $settings['heading_levels'] ) ? $settings['heading_levels'] : array();
		$levels   = array_values( array_unique( array_intersect( array( 'h2', 'h3', 'h4', 'h5', 'h6' ), array_map( 'sanitize_key', $levels ) ) ) );
		if ( empty( $levels ) ) {
			return $content;
		}

		$allowed_levels = array_map( static fn( string $level ): int => (int) substr( $level, 1 ), $levels );
		$entries        = array();
		$used_ids       = array();
		$minimum_level  = min( $allowed_levels );
		$pattern        = '~<h([2-6])\b([^>]*)>(.*?)</h\1\s*>~is';

		$processed = preg_replace_callback(
			$pattern,
			function ( array $match ) use ( &$entries, &$used_ids, $allowed_levels, $minimum_level ): string {
				$level = (int) $match[1];
				if ( ! in_array( $level, $allowed_levels, true ) ) {
					return $match[0];
				}

				$charset = get_bloginfo( 'charset' );
				$charset = is_string( $charset ) && '' !== $charset ? $charset : 'UTF-8';
				$text    = html_entity_decode( wp_strip_all_tags( (string) $match[3], true ), ENT_QUOTES | ENT_HTML5, $charset );
				$text    = preg_replace( '/\s+/u', ' ', trim( $text ) );
				$text    = is_string( $text ) ? $text : '';
				if ( '' === $text ) {
					return $match[0];
				}

				$attributes = (string) $match[2];
				$id_pattern = '/\bid\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+))/i';
				$id_match   = array();
				$has_id     = 1 === preg_match( $id_pattern, $attributes, $id_match );
				$id         = '';
				if ( $has_id ) {
					$raw_id = '';
					foreach ( array( 1, 2, 3 ) as $index ) {
						if ( isset( $id_match[ $index ] ) && '' !== $id_match[ $index ] ) {
							$raw_id = (string) $id_match[ $index ];
							break;
						}
					}
					$id = trim( html_entity_decode( $raw_id, ENT_QUOTES | ENT_HTML5, $charset ) );
				}

				if ( '' === $id || isset( $used_ids[ $id ] ) ) {
					$base   = sanitize_title( $text );
					$base   = '' !== $base ? $base : 'section';
					$id     = $base;
					$suffix = 2;
					while ( isset( $used_ids[ $id ] ) ) {
						$id = $base . '-' . $suffix;
						++$suffix;
					}
					if ( $has_id ) {
						$attributes = (string) preg_replace( $id_pattern, ' id="' . esc_attr( $id ) . '"', $attributes, 1 );
					} else {
						$attributes .= ' id="' . esc_attr( $id ) . '"';
					}
				}

				$used_ids[ $id ] = true;
				if ( ! str_contains( $attributes, 'data-cw-lumen-toc-target' ) ) {
					$attributes .= ' data-cw-lumen-toc-target';
				}

				$entries[] = array(
					'id'    => $id,
					'text'  => $text,
					'level' => $level,
					'depth' => max( 0, $level - $minimum_level ),
				);

				return '<h' . $level . $attributes . '>' . $match[3] . '</h' . $level . '>';
			},
			$content
		);

		if ( ! is_string( $processed ) ) {
			return $content;
		}

		$minimum = max( 1, absint( $settings['minimum_headings'] ?? 3 ) );
		if ( count( $entries ) < $minimum ) {
			return $content;
		}

		$markup = $this->markup( $entries, $settings, $post_id );
		if ( '' === $markup ) {
			return $content;
		}

		$position  = sanitize_key( (string) ( $settings['position'] ?? 'before_first_heading' ) );
		$processed = self::insert_markup_at_safe_boundary( $processed, $markup, $position );

		return $processed;
	}

	/**
	 * Inserts generated TOC markup without splitting a rendered top-level component.
	 *
	 * Headings and paragraphs can live inside composite blocks such as Group,
	 * Columns, Cover, or third-party block wrappers. The TOC still indexes nested
	 * headings, but its own box belongs outside the top-level rendered component
	 * that contains the selected insertion target.
	 *
	 * @param string $html Rendered post content with TOC targets already applied.
	 * @param string $markup Generated TOC markup.
	 * @param string $position Configured insertion position.
	 * @return string
	 */
	private static function insert_markup_at_safe_boundary( string $html, string $markup, string $position ): string {
		if ( 'after_first_paragraph' === $position && preg_match( '~<p\b[^>]*>~i', $html, $paragraph_match, PREG_OFFSET_CAPTURE ) ) {
			$target_offset = (int) $paragraph_match[0][1];
			$bounds        = self::top_level_container_bounds( $html, $target_offset );

			if ( is_array( $bounds ) ) {
				$insert_at = $bounds[1];
				return substr( $html, 0, $insert_at ) . $markup . substr( $html, $insert_at );
			}

			if ( preg_match( '~</p\s*>~i', $html, $paragraph_close, PREG_OFFSET_CAPTURE, $target_offset ) ) {
				$insert_at = (int) $paragraph_close[0][1] + strlen( (string) $paragraph_close[0][0] );
				return substr( $html, 0, $insert_at ) . $markup . substr( $html, $insert_at );
			}
		}

		if ( preg_match( '/<h[2-6]\b[^>]*\bdata-cw-lumen-toc-target\b/i', $html, $heading_match, PREG_OFFSET_CAPTURE ) ) {
			$target_offset = (int) $heading_match[0][1];
			$bounds        = self::top_level_container_bounds( $html, $target_offset );

			if ( is_array( $bounds ) ) {
				$insert_at = $bounds[0];
				return substr( $html, 0, $insert_at ) . $markup . substr( $html, $insert_at );
			}

			return substr( $html, 0, $target_offset ) . $markup . substr( $html, $target_offset );
		}

		return $markup . $html;
	}

	/**
	 * Finds the direct top-level HTML element containing a byte offset.
	 *
	 * The scanner intentionally leaves the original HTML untouched. It only tracks
	 * tag boundaries so inserting the TOC never requires reparsing or serializing
	 * the complete post content.
	 *
	 * @param string $html Rendered post content.
	 * @param int    $target_offset Byte offset located inside the target element.
	 * @return array{0:int,1:int}|null Start/end byte offsets for the top-level container.
	 */
	private static function top_level_container_bounds( string $html, int $target_offset ): ?array {
		if ( $target_offset < 0 || $target_offset >= strlen( $html ) ) {
			return null;
		}

		$matched = preg_match_all(
			'~<!--[\s\S]*?-->|<!\[CDATA\[[\s\S]*?\]\]>|<![^>]*>|<\?[^>]*\?>|</?[A-Za-z][A-Za-z0-9:-]*\b[^>]*>~',
			$html,
			$tokens,
			PREG_OFFSET_CAPTURE
		);
		if ( false === $matched || 0 === $matched || empty( $tokens[0] ) ) {
			return null;
		}

		$void_tags = array(
			'area' => true,
			'base' => true,
			'br' => true,
			'col' => true,
			'embed' => true,
			'hr' => true,
			'img' => true,
			'input' => true,
			'link' => true,
			'meta' => true,
			'param' => true,
			'source' => true,
			'track' => true,
			'wbr' => true,
		);
		$stack             = array();
		$target_root_start = null;
		$target_root_tag   = '';

		foreach ( $tokens[0] as $token_data ) {
			$token  = (string) $token_data[0];
			$offset = (int) $token_data[1];
			$end    = $offset + strlen( $token );

			if ( null === $target_root_start && $offset > $target_offset && ! empty( $stack ) ) {
				$target_root_start = (int) $stack[0]['start'];
				$target_root_tag   = (string) $stack[0]['tag'];
			}
			if ( null === $target_root_start && $offset > $target_offset && empty( $stack ) ) {
				return null;
			}
			if ( ! preg_match( '~^<\s*(/?)\s*([A-Za-z][A-Za-z0-9:-]*)~', $token, $tag_match ) ) {
				continue;
			}

			$is_closing = '/' === $tag_match[1];
			$tag        = strtolower( (string) $tag_match[2] );

			if ( $is_closing ) {
				for ( $index = count( $stack ) - 1; $index >= 0; --$index ) {
					if ( $stack[ $index ]['tag'] !== $tag ) {
						continue;
					}

					$closing_target_root = 0 === $index && null !== $target_root_start && $target_root_tag === $tag;
					$stack               = array_slice( $stack, 0, $index );
					if ( $closing_target_root ) {
						return array( (int) $target_root_start, $end );
					}
					break;
				}
				continue;
			}

			$is_void = isset( $void_tags[ $tag ] ) || 1 === preg_match( '~/\s*>$~', $token );
			if ( $is_void ) {
				if ( $offset <= $target_offset && $target_offset < $end && empty( $stack ) ) {
					return array( $offset, $end );
				}
				continue;
			}

			$stack[] = array(
				'tag'   => $tag,
				'start' => $offset,
			);

			if ( null === $target_root_start && $offset <= $target_offset && $target_offset < $end ) {
				$target_root_start = (int) $stack[0]['start'];
				$target_root_tag   = (string) $stack[0]['tag'];
			}
		}

		return null;
	}

	/**
	 * @param array<int,array{id:string,text:string,level:int,depth:int}> $entries TOC entries.
	 * @param array<string,mixed> $settings Settings.
	 * @param int $post_id Current post ID.
	 * @return string
	 */
	private function markup( array $entries, array $settings, int $post_id ): string {
		$style = sanitize_key( (string) ( $settings['style'] ?? 'boxed' ) );
		if ( ! in_array( $style, array( 'boxed', 'plain' ), true ) ) {
			$style = 'boxed';
		}

		$title    = sanitize_text_field( (string) ( $settings['title'] ?? '' ) );
		$title_id = 'cw-lumen-lite-toc-title-' . max( 1, $post_id );
		$label    = '' !== $title
			? ' aria-labelledby="' . esc_attr( $title_id ) . '"'
			: ' aria-label="' . esc_attr__( 'Table of contents', 'creceweb-lumen-lite' ) . '"';

		$items = '';
		foreach ( $entries as $entry ) {
			$items .= sprintf(
				'<li class="cw-lumen-lite-toc__item" data-depth="%1$d"><a href="#%2$s">%3$s</a></li>',
				max( 0, (int) $entry['depth'] ),
				esc_attr( $entry['id'] ),
				esc_html( $entry['text'] )
			);
		}

		$appearance_map = array(
			'text_color'       => '--cw-lumen-lite-toc-text',
			'link_color'       => '--cw-lumen-lite-toc-link',
			'link_hover_color' => '--cw-lumen-lite-toc-link-hover',
			'background_color' => '--cw-lumen-lite-toc-background',
			'border_color'     => '--cw-lumen-lite-toc-border',
		);
		$appearance     = array();
		foreach ( $appearance_map as $setting_key => $property ) {
			$color = sanitize_hex_color( (string) ( $settings[ $setting_key ] ?? '' ) );
			if ( $color ) {
				$appearance[] = $property . ':' . $color;
			}
		}
		$style_attribute = empty( $appearance ) ? '' : ' style="' . esc_attr( implode( ';', $appearance ) ) . '"';

		$heading = '' !== $title ? '<p class="cw-lumen-lite-toc__title" id="' . esc_attr( $title_id ) . '">' . esc_html( $title ) . '</p>' : '';
		$html    = '<nav class="cw-lumen-lite-toc cw-lumen-lite-toc--' . esc_attr( $style ) . '" data-cw-lumen-toc' . $label . $style_attribute . '>' . $heading . '<ol class="cw-lumen-lite-toc__list">' . $items . '</ol></nav>';

		/**
		 * Filters the final Table of Contents markup.
		 *
		 * @param string $html Final markup.
		 * @param array<int,array{id:string,text:string,level:int,depth:int}> $entries Heading entries.
		 * @param int $post_id Current post ID.
		 */
		$filtered = apply_filters( 'creceweb_lumen_lite_toc_markup', $html, $entries, $post_id );
		return is_scalar( $filtered ) ? (string) $filtered : $html;
	}
}
