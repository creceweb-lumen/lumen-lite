=== CreceWeb Lumen Lite ===
Contributors: creceweb
Donate link: https://creceweb.com.ar/lumen/apoyar-theme-lite
Tags: block patterns, navigation, reading progress, messaging, whatsapp
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lumen patterns, content tools, Reading Progress, Table of Contents, Sharing, Related Content, Messaging, and footer tools for Lumen Theme.

== Description ==

Lumen Lite by CreceWeb adds practical tools to websites built with Lumen Theme 1.4.91 or later.

* Insert ten ready-made sections from the visual Lumen Library inside the WordPress block editor.
* Add an optional global Floating Action for a page section or normal page/URL, with local icons, appearance, position, behavior, and device visibility controls.
* Configure one optional global Messaging button using WhatsApp, Telegram, Messenger, or Signal, with automatic service colors or custom brand colors.
* Add estimated reading time, Reading Progress, an automatic Table of Contents, local Sharing actions, and automatic Related Content for selected public content types.
* Highlight menu and submenu links as optional buttons and control their device visibility.
* Set excerpt length and customize footer text with the safe `{year}` and `{site_name}` variables.
* Inspect local Theme/Lite resource usage from Lumen Performance.
* Manage the tools from the existing Lumen Theme screen.

Lumen Lite does not include telemetry, a private updater, or background requests to CreceWeb services.

= Theme requirement =

Lumen Lite is designed specifically for Lumen Theme. When a compatible version is not active, Theme-dependent modules remain inactive and WordPress displays a compatibility notice instead of producing a fatal error.

= Bundled visual assets =

Images and visual previews included with Lumen Lite were created specifically for CreceWeb and are distributed under GPLv2-or-later with the plugin. Library previews render the plugin's own Gutenberg patterns.

= Privacy and Messaging services =

Lumen Lite does not contact messaging services in the background. The optional global Messaging button builds its destination locally. Only when a visitor explicitly clicks the enabled button does the browser open the selected provider.

* WhatsApp opens `wa.me` with the configured phone number and optional drafted message.
* Telegram opens `t.me` with the configured public username and optional drafted message.
* Messenger opens `m.me` with the configured Messenger username.
* Signal opens the administrator-provided HTTPS contact link on `signal.me` or `signal.link`.

No provider SDK, pixel, remote counter, remote icon file, or provider JavaScript is loaded for Messaging. Provider-identifying glyphs are local plugin-controlled inline SVG. Contact data stays in the site's WordPress settings until the visitor chooses to open the provider.

* WhatsApp service: https://www.whatsapp.com/ — Terms: https://www.whatsapp.com/legal/terms-of-service — Privacy: https://www.whatsapp.com/legal/privacy-policy
* Telegram service: https://telegram.org/ — Terms: https://telegram.org/tos — Privacy: https://telegram.org/privacy
* Messenger service: https://www.messenger.com/ — Meta Terms: https://www.facebook.com/legal/terms/ — Privacy: https://www.facebook.com/privacy/policy/
* Signal service: https://signal.org/ — Terms and Privacy: https://signal.org/legal/

= Privacy and Sharing services =

Sharing loads no social-network SDKs, remote counters, tracking pixels, or background requests. Copy link uses the browser clipboard locally and Email opens the visitor's mail handler. When a visitor explicitly clicks a WhatsApp, LinkedIn, or Facebook share action, the browser opens that provider with the current public content URL and, where supported, title.

* WhatsApp: https://www.whatsapp.com/ — Terms: https://www.whatsapp.com/legal/terms-of-service — Privacy: https://www.whatsapp.com/legal/privacy-policy
* LinkedIn: https://www.linkedin.com/ — User Agreement: https://www.linkedin.com/legal/user-agreement — Privacy: https://www.linkedin.com/legal/privacy-policy
* Facebook: https://www.facebook.com/ — Meta Terms: https://www.facebook.com/legal/terms/ — Privacy: https://www.facebook.com/privacy/policy/

= Service names and trademarks =

WhatsApp, Telegram, Messenger, Signal, LinkedIn, Facebook, and Meta names are used only to identify compatible third-party destinations. Their trademarks belong to their respective owners. CreceWeb Lumen Lite is not affiliated with or endorsed by those providers, and it does not bundle or download their official logo files.

== Installation ==

1. Install and activate Lumen Theme 1.4.91 or later.
2. Install and activate Lumen Lite.
3. Open Appearance > Lumen Theme.
4. Configure Menu, Content and Blog, Floating Action, Messaging, and Footer.
5. Open the WordPress block editor and use the Lumen Library to insert a section.

== Frequently Asked Questions ==

= Does Lumen Lite require Lumen Theme? =

Yes. Its patterns and customization tools are designed for Lumen Theme 1.4.91 or later.

= Does the plugin send usage data? =

No. Lumen Lite includes no telemetry or background requests to CreceWeb services.

= What happens when a visitor clicks Messaging? =

The browser opens the one service selected by the administrator: WhatsApp, Telegram, Messenger, or Signal. Lumen Lite makes no request to that messaging provider before the visitor clicks.

= How are Messaging colors chosen? =

New configurations can use the selected service color automatically or custom brand colors. Upgraded configurations preserve their previously saved custom colors.

= How does Related Content choose recommendations? =

On enabled singular content, Lumen ranks published items from the same post type by shared terms in public taxonomies. The current item is excluded; when no suitable match exists, the section is not rendered.

== Screenshots ==

1. Lumen Lite Menu overview with highlighted links and independent visibility by device.
2. Lumen Library with reusable Sections and Pages available directly inside the WordPress block editor.
3. WordPress Menus extended with per-link highlight and device visibility controls, including submenu items.
4. Reading Progress with appearance, content-type and device controls plus a live preview.
5. Automatic Table of Contents with insertion, appearance, heading-level and content-type controls.
6. Advanced Floating Action with destination, appearance, positioning, device visibility and live preview.
7. Site-wide Messaging with WhatsApp, Telegram, Messenger and Signal, service or custom colors, device visibility and live preview.

== Changelog ==

= 1.0.1 =
* Replaces the WhatsApp-only contact control with one complete global Messaging channel for WhatsApp, Telegram, Messenger, or Signal, with local glyphs, provider-specific destinations, and no provider request before click.
* Adds provider-aware Messaging appearance with automatic service colors or custom brand colors while preserving existing saved colors on upgrade. Lite API 2.4.0; settings schema 23.
* Adds Reading Progress, automatic Table of Contents, local Sharing, automatic Related Content, and the advanced Floating Action.
* Adds local Lumen Performance diagnostics and keeps resource reporting aligned with the current Theme/Lite assets.
* Improves Library/admin UX, responsive behavior, accessibility, validation, conditional loading, and builder/theme compatibility.

= 1.0.0 =
* First public release with the visual Lumen Library, global WhatsApp, menu visibility, reading time, excerpt controls, and editable footer text.

== Upgrade Notice ==

= 1.0.1 =
* Adds Messaging with four selectable providers, service/custom colors, Reading Progress, Table of Contents, Sharing, Related Content, Floating Action, and local Performance diagnostics while preserving existing settings.
