=== Schema Nerd ===
Contributors: localimage
Tags: schema, seo, structured-data
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 1.0.5
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

API interface for Schema Nerd organizations.

== Description ==

Connect your WordPress site to Schema Nerd on schemanerd.app to output organization schema, location shortcodes, and a location builder widget or block.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/schema-nerd`, or install through the WordPress plugins screen.
2. Activate the plugin through the Plugins screen.
3. Enter your Schema Nerd API key under Schema Nerd → Settings and select your organization.

== Changelog ==

= 1.0.5 =
* Plugin Check and WordPress coding standards compliance (escaping, sanitization, i18n, ABSPATH guards).
* Standardized text domain to `schema-nerd` and prefixed global functions/variables.
* Added plugin license headers and WordPress.org-style readme.
* Removed legacy GitHub updater in preparation for new release/update workflow.
* Hardened REST API auth header handling and cached fallback user lookup.

= 1.0.4 =
* Initial release.
