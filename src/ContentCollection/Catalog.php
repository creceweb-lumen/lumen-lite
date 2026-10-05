<?php
/** Shared public-content catalog helpers. @package CreceWebLumenLite */
namespace CreceWeb\LumenLite\ContentCollection;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Catalog {
	/** @return array<string,string> */
	public function post_types(): array {
		$objects = get_post_types( array( 'public' => true ), 'objects' );
		$objects = is_array( $objects ) ? $objects : array();
		$result = array();
		foreach ( $objects as $key => $object ) {
			if ( ! is_object( $object ) ) { continue; }
			$name = sanitize_key( (string) ( $object->name ?? $key ) );
			if ( '' === $name || 'attachment' === $name || empty( $object->public ) ) { continue; }
			if ( function_exists( 'is_post_type_viewable' ) && ! is_post_type_viewable( $object ) ) { continue; }
			$label = isset( $object->labels->name ) ? sanitize_text_field( (string) $object->labels->name ) : $name;
			$result[ $name ] = '' !== $label ? $label : $name;
		}
		return $result;
	}

	/** @param array<int,string> $post_types @return array<string,string> */
	public function taxonomies( array $post_types ): array {
		$post_types = array_values( array_unique( array_filter( array_map( 'sanitize_key', $post_types ) ) ) );
		if ( empty( $post_types ) ) { return array(); }
		$objects = get_taxonomies( array( 'public' => true ), 'objects' );
		$objects = is_array( $objects ) ? $objects : array();
		$result = array();
		foreach ( $objects as $key => $object ) {
			if ( ! is_object( $object ) || empty( $object->public ) ) { continue; }
			$name = sanitize_key( (string) ( $object->name ?? $key ) );
			$types = isset( $object->object_type ) && is_array( $object->object_type ) ? array_map( 'sanitize_key', $object->object_type ) : array();
			if ( '' === $name || empty( array_intersect( $post_types, $types ) ) ) { continue; }
			$label = isset( $object->labels->name ) ? sanitize_text_field( (string) $object->labels->name ) : $name;
			$result[ $name ] = '' !== $label ? $label : $name;
		}
		return $result;
	}

	/** @return array<int,string> */
	public function terms( string $taxonomy ): array {
		$taxonomy = sanitize_key( $taxonomy );
		if ( '' === $taxonomy ) { return array(); }
		$objects = get_taxonomies( array( 'public' => true ), 'objects' );
		$objects = is_array( $objects ) ? $objects : array();
		$valid = false;
		foreach ( $objects as $key => $object ) {
			$name = is_object( $object ) ? sanitize_key( (string) ( $object->name ?? $key ) ) : '';
			if ( $name === $taxonomy && ! empty( $object->public ) ) { $valid = true; break; }
		}
		if ( ! $valid ) { return array(); }
		$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) { return array(); }
		$result = array();
		foreach ( $terms as $term ) {
			if ( ! is_object( $term ) ) { continue; }
			$id = absint( $term->term_id ?? 0 );
			$name = sanitize_text_field( (string) ( $term->name ?? '' ) );
			if ( $id > 0 && '' !== $name ) { $result[ $id ] = $name; }
		}
		return $result;
	}
}
