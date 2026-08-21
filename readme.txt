=== CitySpark Email Feed ===
Contributors: harveyplum
Tags: cityspark, rss, events, noptin, email, shortcode
Requires at least: 6.8
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed CitySpark RSS events in Noptin emails and WordPress content using email-safe HTML.

== Description ==

CitySpark Email Feed fetches a CitySpark RSS feed, parses event title, link, date/time, venue, and media image data, then renders the events as email-safe nested table HTML.

The plugin provides:

* A Settings page under Settings > CitySpark Email Feed.
* The [cityspark_events] shortcode.
* Noptin merge tag support for {{cityspark_events}} and {{cityspark_events limit=5}}.
* WordPress transient caching using cityspark_feed_{md5(url)} keys.

The supported CitySpark RSS item format includes title, link, description, and media:content url fields. Descriptions formatted like "Wednesday, July 1 at 4:00 PM <br> Wausau River District" are parsed into date/time and venue fields.

== Installation ==

1. Upload the `cityspark-email-feed` folder to `/wp-content/plugins/`.
2. Activate CitySpark Email Feed from the Plugins screen.
3. Go to Settings > CitySpark Email Feed.
4. Configure the RSS Feed URL and display defaults.

== Shortcode ==

Use the default shortcode:

`[cityspark_events]`

Limit the number of events:

`[cityspark_events limit="5"]`

Hide images:

`[cityspark_events image="no"]`

Use compact spacing:

`[cityspark_events layout="compact"]`

Supported attributes:

* `limit`
* `image`
* `date`
* `venue`
* `button`
* `layout`

The `button` attribute can be set to `no` to hide buttons or to custom text such as `[cityspark_events button="Details"]`.

== Noptin ==

Use this merge tag in Noptin emails:

`{{cityspark_events}}`

With a limit:

`{{cityspark_events limit=5}}`

The Noptin merge tag uses the same rendering engine as the shortcode.

== Changelog ==

= 1.0.3 =
* Added GitHub update metadata and standardized Harvey Plum branding.
* Restricted feed requests to validated public HTTPS URLs and WordPress safe HTTP handling.

= 1.0.2 =
* Fixed a settings-page menu registration fatal for users who cannot access the Settings menu.

= 1.0.1 =
* Added settings-page usage details, Harvey Plum author branding, and support footer.

= 1.0.0 =
* Initial release.
