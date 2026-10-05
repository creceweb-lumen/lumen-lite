=== CreceWeb Lumen Lite ===
Contributors: creceweb
Donate link: https://creceweb.com.ar/lumen/apoyar-theme-lite
Tags: block patterns, dark mode, search, popular posts, reading progress
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lumen patterns, Site Kits, reading tools, Lumen Posts, Color Mode, Search Modal, sharing, messaging, menus, and footer tools for Lumen Theme.

== Description ==

Lumen Lite by CreceWeb adds optional tools to websites built with Lumen Theme 1.4.91 or later.

* Browse editable Gutenberg Patterns, Pages, and three Site Kits: Prisma Business, Aurora Education, and Voltio Education. Kit design presets are optional, restorable, and do not install plugins or remote frameworks.
* Add Breadcrumbs, estimated reading time, Reading Progress, Table of Contents, local Sharing, Related Content, and Popular Content.
* Use Lumen Posts in Gutenberg, Elementor Free, or `[lumen_posts]` with responsive item and column controls and one shared server-side renderer.
* Add Color Mode with Light, Dark, or System defaults, a configurable dark palette, menu/floating switches, and `[lumen_color_mode_switch]`.
* Highlight any menu or submenu links, control device visibility, add menu indicators, and enable the native Search Modal or `[lumen_search]` trigger.
* Configure a global Floating Action and one optional Messaging button using WhatsApp, Telegram, Messenger, or Signal.
* Export or import selected portable Lumen settings as versioned JSON without credentials, license data, or other secrets.
* Customize excerpt length and footer text, and inspect local Theme/Lite resource usage with Lumen Performance.

Manage the tools from Appearance > CreceWeb Lumen. Lumen Lite includes no telemetry, private updater, or background request to CreceWeb services.

= Lumen Library and Site Kits =

The Library groups editable Patterns, complete Pages, and Site Kits inside the block editor. Site Kits combine local Gutenberg content with an optional Theme/Lite design preset. Existing pages and menus are not deleted by Kit installation, and demo social or Messaging identities are not activated.

= Theme requirement =

Lumen Lite is designed for Lumen Theme. If a compatible Theme version is not active, Theme-dependent modules stay inactive and WordPress shows a compatibility notice instead of producing a fatal error.

= Import / Export =

Administrators choose which registered portable sections to export. The versioned JSON excludes license keys, API tokens, analytics/updater credentials, onboarding state, and migration state.

= Source code =

Human-readable equivalents of compact production CSS are included under `source/css/`. They contain the corresponding selectors and declarations formatted for review and are not enqueued at runtime. No external build service is required.

= Bundled visual assets and translations =

Bundled Library/Site Kit previews and visual assets were created for CreceWeb Lumen Lite and are GPLv2-or-later. Prisma, Aurora, and Voltio/VoltioLab are fictional demo identities. No third-party fonts or official provider logo files are bundled.

English is the source language. WordPress.org translations are delivered through the official language-pack system; PO/MO/POT catalogs are not bundled in the plugin package.

= External services and privacy =

Lumen Lite loads no messaging or sharing SDK, remote counter, tracking pixel, provider JavaScript, or remote provider icon. Contact/share data is prepared locally. A third-party service is contacted only after a visitor explicitly chooses its action.

Messaging may open:

* WhatsApp (`wa.me`) — https://www.whatsapp.com/ — Terms: https://www.whatsapp.com/legal/terms-of-service — Privacy: https://www.whatsapp.com/legal/privacy-policy
* Telegram (`t.me`) — https://telegram.org/ — Terms: https://telegram.org/tos — Privacy: https://telegram.org/privacy
* Messenger (`m.me`) — https://www.messenger.com/ — Terms: https://www.facebook.com/legal/terms/ — Privacy: https://www.facebook.com/privacy/policy/
* Signal (`signal.me` or `signal.link`) — https://signal.org/ — Terms and Privacy: https://signal.org/legal/

Sharing may open WhatsApp, LinkedIn, or Facebook with the current public content URL. Copy Link uses the browser clipboard and Email opens the visitor's mail handler.

* LinkedIn — https://www.linkedin.com/ — User Agreement: https://www.linkedin.com/legal/user-agreement — Privacy: https://www.linkedin.com/legal/privacy-policy
* Facebook — https://www.facebook.com/ — Terms: https://www.facebook.com/legal/terms/ — Privacy: https://www.facebook.com/privacy/policy/

Provider names are used only to identify compatible destinations. Their trademarks belong to their respective owners; CreceWeb Lumen Lite is not affiliated with or endorsed by them.

== Installation ==

1. Install and activate Lumen Theme 1.4.91 or later.
2. Install and activate Lumen Lite.
3. Open Appearance > CreceWeb Lumen.
4. Open the block editor and launch Lumen Library for Patterns, Pages, and Site Kits.
5. Enable only the tools you need.

== Frequently Asked Questions ==

= Does Lumen Lite require Lumen Theme? =

Yes. Its Library, Site Kits, and Theme-coordinated tools are designed for Lumen Theme 1.4.91 or later.

= Does the plugin send usage data? =

No. Lumen Lite includes no telemetry or background requests to CreceWeb services.

= Does a Site Kit install or activate external services? =

No. Site Kits use local editable content and optional Lumen design settings. They do not install another theme/plugin, remote framework, demo social account, or Messaging identity.

= How does Search Modal work? =

It submits the normal WordPress `?s=` search request. It uses no AJAX service, remote index, or external search provider.

== Screenshots ==

1. Lumen Library with reusable Patterns and complete page designs.
2. Three complete Site Kits with optional recommended design.
3. Content and blog tools including Breadcrumbs, reading tools, Related Content, Popular Content, and Lumen Posts.
4. Per-link menu highlighting, indicators, and independent device visibility.
5. Color Mode with semantic dark palette, contrast checks, and multiple switch placements.
6. Advanced Floating Action with destination, appearance, positioning, device visibility, and live preview.
7. Site-wide Messaging with WhatsApp, Telegram, Messenger, and Signal.
8. Lumen Performance diagnostics for Theme and Lite resources.
9. Portable configuration export and import for Lumen settings.

== Changelog ==

= 1.1.0 =
* Adds editable Site Kits, Breadcrumbs, Lumen Posts, Color Mode, Popular Content, Search Modal, and selective Import / Export.
* Expands the Lumen Library with Patterns, Pages, local previews, and safe optional Kit design presets with restore support.
* Extends menu controls with indicators and independent device visibility while keeping assets conditional.
* Refreshes Lumen Performance diagnostics and exposes a validated read-only analysis-profile contract.
* Improves editor parity, accessibility, conditional loading, packaging/uninstall safety, and Theme integration.

= 1.0.1 =
* Adds one complete Messaging channel for WhatsApp, Telegram, Messenger, or Signal with local glyphs and no provider request before click.
* Adds Reading Progress, Table of Contents, Sharing, Related Content, Floating Action, and local Performance diagnostics.
* Improves Library/admin UX, responsive behavior, accessibility, validation, and conditional loading.

= 1.0.0 =
* First public release with Lumen Library, menu visibility/highlighting, reading time, excerpt controls, WhatsApp, and editable footer text.

== Upgrade Notice ==

= 1.1.0 =
Adds Site Kits, Breadcrumbs, Search Modal, Lumen Posts, Color Mode, Popular Content, and selective Import / Export while preserving existing settings.
