<?php
/**
 * Lightweight inline glyphs for the active Messaging provider.
 *
 * @package CreceWebLumenLite
 */

namespace CreceWeb\LumenLite\Messaging;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Returns plugin-controlled inline SVG glyphs. */
final class GlyphResolver {
	/** @return string */
	public function svg( string $provider ): string {
		$glyphs = $this->all();
		return $glyphs[ sanitize_key( $provider ) ] ?? '';
	}

	/** @return array<string,string> */
	public function all(): array {
		return array(
			'whatsapp' => '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M12 3.25a8.75 8.75 0 0 0-7.48 13.28L3.25 20.75l4.35-1.2A8.75 8.75 0 1 0 12 3.25Zm0 1.75a7 7 0 0 1 0 14 6.9 6.9 0 0 1-3.55-.97l-.3-.18-2.58.71.74-2.5-.2-.32A7 7 0 0 1 12 5Z" fill="currentColor"/><path d="M9.35 8.4c-.24 0-.5.06-.72.3-.22.23-.83.8-.83 1.96 0 1.15.85 2.27.96 2.43.12.16 1.67 2.67 4.12 3.63 2.04.8 2.45.64 2.9.6.45-.04 1.46-.59 1.66-1.16.2-.57.2-1.06.14-1.16-.06-.1-.22-.16-.46-.28-.24-.12-1.42-.72-1.64-.8-.22-.08-.38-.12-.54.12-.16.24-.62.8-.76.96-.14.16-.28.18-.52.06-.24-.12-1.02-.39-1.95-1.24-.72-.66-1.2-1.47-1.34-1.71-.14-.24-.01-.37.1-.49.1-.1.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.34-.75-1.82-.2-.47-.4-.4-.55-.41h-.46Z" fill="currentColor" transform="translate(12 12) scale(.82) translate(-12 -12)"/></svg>',
			'telegram' => '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path fill="currentColor" d="M20.7 4.1 3.8 10.6c-1.15.46-1.14 1.1-.21 1.38l4.34 1.35 1.67 5.16c.2.55.1.77.69.77.45 0 .65-.2.9-.45l2.08-2.02 4.32 3.19c.8.44 1.37.21 1.57-.74l2.68-12.63c.28-1.12-.43-1.63-1.14-1.31ZM9.1 13.02l8.47-5.34c.42-.25.8-.12.49.16l-6.99 6.31-.27 2.83-1.7-3.96Z"/></svg>',
			'messenger' => '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path fill="currentColor" d="M12 3.25c-5.04 0-9 3.69-9 8.4 0 2.68 1.28 5.07 3.29 6.61v2.49l2.46-1.35c1.02.42 2.12.65 3.25.65 5.04 0 9-3.69 9-8.4s-3.96-8.4-9-8.4Zm.9 11.31-2.29-2.44-4.46 2.44 4.9-5.2 2.34 2.44 4.41-2.44-4.9 5.2Z"/></svg>',
			'signal' => '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M12 3.4c4.86 0 8.8 3.56 8.8 7.96s-3.94 7.96-8.8 7.96c-1.08 0-2.11-.18-3.07-.51L4 20.6l1.63-3.92a7.53 7.53 0 0 1-2.43-5.32C3.2 6.96 7.14 3.4 12 3.4Z" fill="none" stroke="currentColor" stroke-width="1.55" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="1.45 1.55"/><circle cx="8.7" cy="11.35" r="1" fill="currentColor"/><circle cx="12" cy="11.35" r="1" fill="currentColor"/><circle cx="15.3" cy="11.35" r="1" fill="currentColor"/></svg>',
		);
	}
}
