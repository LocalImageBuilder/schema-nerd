=== Schema Nerd ===
Contributors: localimage
Tags: schema, structured data, local seo, json-ld, localbusiness
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 1.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect your WordPress site to Schema Nerd to automatically output valid LocalBusiness JSON-LD structured data and display location information anywhere on your site.

== Description ==

**Schema Nerd** is a LocalBusiness schema management platform at [schemanerd.app](https://schemanerd.app). This plugin connects your WordPress site to your Schema Nerd account so your structured data and location information stay in sync — automatically.

Most businesses manage structured data in one place and their WordPress site in another. Without this plugin, keeping JSON-LD schema accurate means manual copy-paste and the constant risk of stale, inconsistent information. Schema Nerd fixes that: update your business data once in Schema Nerd, and your website updates with it.

= Core features =

**Automatic JSON-LD output**
The plugin injects your complete LocalBusiness JSON-LD schema into the site `<head>` on every page load. Data comes directly from your Schema Nerd organization. No code editing required.

**Shortcodes for location data**
Display formatted phone numbers, addresses, hours, email, and fax anywhere on your site. The plugin only outputs shortcodes for fields that exist in your schema — no empty placeholders for missing data.

Single-location shortcodes:

* `[schema_nerd_phone]`
* `[schema_nerd_address]`
* `[schema_nerd_hours]`
* `[schema_nerd_fax]`

Multi-location shortcodes:

* `[schema_nerd_locations_phone]`
* `[schema_nerd_locations_address]`
* `[schema_nerd_locations_hours]`
* `[schema_nerd_locations_email]`

Per-location shortcode:

* `[schema_nerd_location field="phone" location="0"]`

**Location Builder Block (Gutenberg)**
Pick a location and field visually in the block editor. Includes an optional interactive location-finder for front-end use and a shortcode copy box for editors who want to reuse the shortcode elsewhere.

**Location Builder Widget**
The same functionality as the block, available in classic widget areas, sidebars, and footers.

**Admin Shortcode Builder**
Under *Schema Nerd → Helpful Shortcodes*: browse every available shortcode based on your live schema, build per-location shortcodes, preview output, and copy with one click.

**Support Tab**
Under *Schema Nerd → Support*: searchable help articles pulled from schemanerd.app with links to full documentation.

**Advanced Settings**
Globally hide location names from shortcode output — useful when a page already has a heading for the location. Widgets and blocks have their own per-instance override.

= Who it's for =

* Single-location businesses that need correct LocalBusiness schema on WordPress
* Multi-location businesses, dealerships, franchises, and medical groups managing location-specific contact info on different pages
* Agencies that build WordPress sites for Schema Nerd clients and want a standardized, low-maintenance integration
* Non-technical site owners who prefer to update business data in one place without editing their website

= External service =

This plugin connects to **schemanerd.app**, a third-party schema management service operated by Local Image.

When you enter and save your API key, the plugin sends that key to schemanerd.app to retrieve your organization's schema and location data. This request occurs on the WordPress admin settings page and on the front end when schema is injected into the page head. No data about your website visitors is collected or transmitted.

Use of schemanerd.app is subject to the [Schema Nerd Terms of Service](https://schemanerd.app/terms/) and [Privacy Policy](https://schemanerd.app/privacy/). A free account is available at [schemanerd.app](https://schemanerd.app) — no credit card required to get started.

== Installation ==

1. Upload the `schema-nerd` folder to the `/wp-content/plugins/` directory, or install the plugin directly through the WordPress Plugins screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Schema Nerd → Settings**.
4. Enter your API key from your Schema Nerd account at schemanerd.app.
5. Select your organization and save.
6. Your JSON-LD schema is now live in the site head. Use shortcodes, the Location Builder block, or the Location Builder widget to display location information anywhere on your site.

== Frequently Asked Questions ==

= Do I need a Schema Nerd account? =

Yes. The plugin connects WordPress to schemanerd.app — a free account is available at [schemanerd.app](https://schemanerd.app) with no credit card required.

= Does this plugin create or edit schema inside WordPress? =

No. Schema is built and managed at schemanerd.app. This plugin reads your published schema and displays it on your WordPress site. All editing happens in your Schema Nerd account.

= What if I have multiple locations? =

The plugin detects how many locations are in your organization's schema and makes the appropriate shortcodes available automatically. Multi-location accounts get list shortcodes, a per-location shortcode builder, and a location picker in the block and widget.

= Will this conflict with my SEO plugin? =

Schema Nerd is designed to be the primary source of LocalBusiness JSON-LD on your site. If your SEO plugin also outputs LocalBusiness schema, you may end up with duplicate markup. We recommend disabling LocalBusiness schema output in your SEO plugin when using Schema Nerd.

= Can I style the output? =

Yes. All shortcodes output semantic HTML with consistent `schema-nerd-*` CSS classes so your theme or custom CSS can control the appearance.

= Is the plugin translation-ready? =

Yes. All user-facing strings are internationalized using the `schema-nerd` text domain.

== Screenshots ==

1. Settings screen — enter your API key and select your organization.
2. Helpful Shortcodes — browse, build, preview, and copy shortcodes for your schema.
3. Location Builder Block in the Gutenberg editor.
4. Location Builder Widget in the classic widget area.
5. Support tab — searchable help articles from schemanerd.app.

== Changelog ==

= 1.1.0 =
* Fix: wrap JSON-LD schema output in script tags when the API returns raw JSON, preventing broken page layouts.
* Add settings-saved toast notification that briefly confirms saves and fades out.
* Display settings errors on the admin page for standard WordPress feedback.

= 1.0.8 =
* Add automatic updates from public GitHub Releases (no token required).
* Add release workflow that publishes schema-nerd.zip on version tags.

= 1.0.7 =
* Plugin updates are now delivered through the WordPress.org plugin directory.
* Preparation for WordPress.org submission.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.0 =
Fixes a bug where raw JSON-LD schema could break the front-end page layout. Update recommended.

= 1.0.8 =
Adds automatic plugin updates from GitHub Releases. Make sure the GitHub repository is public so updates work without a token.