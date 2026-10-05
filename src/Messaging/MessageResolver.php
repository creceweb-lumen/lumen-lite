<?php
/**
 * Reusable Messaging message-template resolution for Lumen Lite and extensions.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Messaging;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves free built-in Messaging message variables against the current request.
 *
 * The resolver is intentionally independent from rendering and settings storage so
 * other Lumen extensions can reuse the same message semantics without duplicating
 * the parser or coupling Lite to a consuming extension.
 */
final class MessageResolver {
	/**
	 * Resolves variables in a message template.
	 *
	 * Built-in variables are {site}, {title}, {description}, and {url}. Unknown variables remain
	 * untouched so extensions may add their own values through the token filter.
	 *
	 * @param string              $template Message template.
	 * @param array<string,mixed> $context  Optional context overrides.
	 * @param string              $provider Optional provider context.
	 * @return string
	 */
	public function resolve( string $template, array $context = array(), string $provider = '' ): string {
		if ( '' === $template ) {
			return '';
		}

		$tokens       = $this->tokens( $context, $template, $provider );
		$replacements = array();

		foreach ( $tokens as $name => $value ) {
			$key = trim( (string) $name, "{} \t\n\r\0\x0B" );
			if ( '' === $key ) {
				continue;
			}
			$replacements[ '{' . $key . '}' ] = $this->plain_text( $value );
		}

		$resolved = strtr( $template, $replacements );

		/**
		 * Filters the final resolved Messaging message.
		 *
		 * @param string              $resolved Resolved message.
		 * @param string              $template Original stored template.
		 * @param array<string,mixed> $tokens   Token values used for replacement.
		 * @param array<string,mixed> $context  Explicit caller context overrides.
		 */
		$filtered = apply_filters( 'creceweb_lumen_lite_messaging_resolved_message', $resolved, $template, $tokens, $context, $provider );
		$resolved = is_string( $filtered ) ? $filtered : $resolved;

		if ( 'whatsapp' === $provider ) {
			$legacy = apply_filters( 'creceweb_lumen_lite_whatsapp_resolved_message', $resolved, $template, $tokens, $context );
			$resolved = is_string( $legacy ) ? $legacy : $resolved;
		}

		return $resolved;
	}

	/**
	 * Returns the resolved token map for the current request.
	 *
	 * Extensions can add token values through the filter. Lite itself only ships
	 * the fully functional built-in variables documented in its free UI.
	 *
	 * @param array<string,mixed> $context  Optional context overrides.
	 * @param string              $template Original template for filter context.
	 * @param string              $provider Optional provider context.
	 * @return array<string,mixed>
	 */
	public function tokens( array $context = array(), string $template = '', string $provider = '' ): array {
		$current = $this->current_context();
		$tokens  = array(
			'site'        => array_key_exists( 'site', $context ) ? $context['site'] : $current['site'],
			'title'       => array_key_exists( 'title', $context ) ? $context['title'] : $current['title'],
			'description' => array_key_exists( 'description', $context ) ? $context['description'] : $current['description'],
			'url'         => array_key_exists( 'url', $context ) ? $context['url'] : $current['url'],
		);

		/**
		 * Filters Messaging message variables before replacement.
		 *
		 * This is a normal extension point: Lite does not register
		 * extension-only variables in its own package.
		 *
		 * @param array<string,mixed> $tokens   Token values keyed without braces.
		 * @param array<string,mixed> $context  Explicit caller context overrides.
		 * @param string              $template Original message template.
		 */
		$filtered = apply_filters( 'creceweb_lumen_lite_messaging_message_tokens', $tokens, $context, $template, $provider );
		$tokens = is_array( $filtered ) ? $filtered : $tokens;

		if ( 'whatsapp' === $provider ) {
			$legacy = apply_filters( 'creceweb_lumen_lite_whatsapp_message_tokens', $tokens, $context, $template );
			$tokens = is_array( $legacy ) ? $legacy : $tokens;
		}

		return $tokens;
	}

	/**
	 * Returns built-in context values for the current WordPress request.
	 *
	 * @return array{site:string,title:string,description:string,url:string}
	 */
	public function current_context(): array {
		return array(
			'site'        => $this->plain_text( get_bloginfo( 'name' ) ),
			'title'       => $this->current_title(),
			'description' => $this->plain_text( get_bloginfo( 'description' ) ),
			'url'         => $this->current_url(),
		);
	}

	/** @return string */
	private function current_title(): string {
		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			if ( $post_id > 0 ) {
				$title = get_the_title( $post_id );
				if ( is_string( $title ) && '' !== trim( $title ) ) {
					return $this->plain_text( $title );
				}
			}
		}

		if ( is_home() ) {
			$posts_page = absint( get_option( 'page_for_posts', 0 ) );
			if ( $posts_page > 0 ) {
				$title = get_the_title( $posts_page );
				if ( is_string( $title ) && '' !== trim( $title ) ) {
					return $this->plain_text( $title );
				}
			}

			return $this->plain_text( get_bloginfo( 'name' ) );
		}

		if ( is_search() ) {
			return $this->plain_text( get_search_query() );
		}

		if ( is_archive() ) {
			$title = get_the_archive_title();
			if ( is_string( $title ) && '' !== trim( $title ) ) {
				return $this->plain_text( $title );
			}
		}

		if ( is_front_page() ) {
			return $this->plain_text( get_bloginfo( 'name' ) );
		}

		$queried = get_queried_object();
		if ( is_object( $queried ) ) {
			foreach ( array( 'post_title', 'name', 'label' ) as $property ) {
				if ( isset( $queried->{$property} ) && is_scalar( $queried->{$property} ) ) {
					$title = trim( (string) $queried->{$property} );
					if ( '' !== $title ) {
						return $this->plain_text( $title );
					}
				}
			}
		}

		return '';
	}

	/** @return string */
	private function current_url(): string {
		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			if ( $post_id > 0 ) {
				$url = wp_get_canonical_url( $post_id );
				if ( ! is_string( $url ) || '' === $url ) {
					$url = get_permalink( $post_id );
				}
				if ( is_string( $url ) && '' !== $url ) {
					return esc_url_raw( $url );
				}
			}
		}

		if ( is_front_page() ) {
			return esc_url_raw( home_url( '/' ) );
		}

		if ( is_home() ) {
			$posts_page = absint( get_option( 'page_for_posts', 0 ) );
			if ( $posts_page > 0 ) {
				$url = get_permalink( $posts_page );
				if ( is_string( $url ) && '' !== $url ) {
					return esc_url_raw( $url );
				}
			}
			return esc_url_raw( home_url( '/' ) );
		}

		if ( is_search() ) {
			return esc_url_raw( get_search_link( get_search_query() ) );
		}

		if ( is_category() || is_tag() || is_tax() ) {
			$url = get_term_link( get_queried_object() );
			if ( ! is_wp_error( $url ) && is_string( $url ) ) {
				return esc_url_raw( $url );
			}
		}

		if ( is_post_type_archive() ) {
			$post_type = get_query_var( 'post_type', '' );
			if ( is_array( $post_type ) ) {
				$post_type = reset( $post_type );
			}
			if ( is_string( $post_type ) && '' !== $post_type ) {
				$url = get_post_type_archive_link( $post_type );
				if ( is_string( $url ) && '' !== $url ) {
					return esc_url_raw( $url );
				}
			}
		}

		if ( is_author() ) {
			$author = get_queried_object();
			if ( is_object( $author ) && isset( $author->ID ) ) {
				return esc_url_raw( get_author_posts_url( absint( $author->ID ) ) );
			}
		}

		$year = absint( get_query_var( 'year', 0 ) );
		if ( is_year() && $year > 0 ) {
			return esc_url_raw( get_year_link( $year ) );
		}

		$month = absint( get_query_var( 'monthnum', 0 ) );
		if ( is_month() && $year > 0 && $month > 0 ) {
			return esc_url_raw( get_month_link( $year, $month ) );
		}

		$day = absint( get_query_var( 'day', 0 ) );
		if ( is_day() && $year > 0 && $month > 0 && $day > 0 ) {
			return esc_url_raw( get_day_link( $year, $month, $day ) );
		}

		return esc_url_raw( home_url( '/' ) );
	}

	/**
	 * Converts token values to plain message text without imposing length limits.
	 *
	 * @param mixed $value Token value.
	 * @return string
	 */
	private function plain_text( mixed $value ): string {
		$text    = wp_strip_all_tags( (string) $value, true );
		$charset = (string) get_bloginfo( 'charset' );
		if ( '' === $charset ) {
			$charset = 'UTF-8';
		}
		return html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, $charset );
	}
}
