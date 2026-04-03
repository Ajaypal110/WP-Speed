=== NitroMan - Speed Optimization Engine ===
Contributors: Ajaypal Singh
Tags: performance, speed, cache, optimization, images
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced performance optimizer to boost WordPress speed safely. Page caching, asset optimization, image compression, database cleanup, and Core Web Vitals improvements.

== Description ==

**NitroMan** is a lightweight yet powerful WordPress performance optimization plugin designed to make your website blazing fast without breaking anything.

= Key Features =

* **Page Caching** — Generate static HTML files for lightning-fast page delivery with GZIP compression support.
* **Image Optimization** — Native lazy loading, WebP detection, and automatic dimension enforcement to prevent layout shifts (CLS).
* **Script Management** — Intelligent defer/async loading with jQuery-safe exclusion lists and query string removal.
* **CSS Optimization** — Non-critical CSS deferral and inline minification.
* **Database Cleanup** — Clean revisions, auto-drafts, spam comments, expired transients, and orphaned metadata.
* **WordPress Tweaks** — Disable emojis, embeds, XML-RPC, heartbeat control, and more.
* **DNS Prefetch & Preconnect** — Resource hint injection for faster third-party resource loading.
* **Admin Dashboard** — Beautiful, modern dashboard with real-time cache stats and one-click actions.

= Built for Safety =

* Recursion guards on all content filters to prevent memory exhaustion.
* Output buffer nesting safety in the caching engine.
* Lazy module loading — frontend optimizations only run where needed.
* jQuery and core scripts are never deferred to prevent breakage.

= Requirements =

* WordPress 5.8 or higher
* PHP 7.4 or higher

== Installation ==

1. Upload the `nitroman` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **NitroMan** in the admin menu to configure settings.

== Frequently Asked Questions ==

= Will NitroMan break my site? =

NitroMan is built with extensive safety guards. jQuery and core WordPress scripts are never deferred, and all content filters use recursion guards to prevent memory issues. However, we recommend testing in a staging environment first.

= Does NitroMan work with other caching plugins? =

We recommend using only one page caching solution at a time to avoid conflicts. NitroMan's other optimization features (image lazy loading, script deferral, database cleanup) can work alongside other plugins.

= How do I purge the cache? =

You can purge the cache from the NitroMan dashboard, the admin bar dropdown, or it will be automatically purged when you update posts or change themes.

== Changelog ==

= 1.0.0 =
* Initial release.
* Page caching with GZIP compression.
* Image lazy loading and dimension enforcement.
* Script defer/async with safety guards.
* Database cleanup module.
* WordPress performance tweaks.
* Modern admin dashboard.

== Upgrade Notice ==

= 1.0.0 =
Initial release of NitroMan Speed Optimization Engine.
