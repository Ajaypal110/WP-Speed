<?php
/**
 * .htaccess optimization module for NitroMan.
 *
 * @package NitroMan
 */

namespace NitroMan;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Htaccess_Manager
 *
 * Manages server-level optimizations via .htaccess including
 * GZIP compression, browser caching, ETags, and security headers.
 * These provide the most significant real-world speed improvements.
 */
class Htaccess_Manager {

	/**
	 * Singleton instance.
	 *
	 * @var Htaccess_Manager|null
	 */
	private static $instance = null;

	/**
	 * .htaccess file path.
	 *
	 * @var string
	 */
	private $htaccess_path = '';

	/**
	 * Markers for our rules.
	 */
	const MARKER_START = '# BEGIN NitroMan Optimization';
	const MARKER_END   = '# END NitroMan Optimization';

	/**
	 * Get the singleton instance.
	 *
	 * @return Htaccess_Manager
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		$this->htaccess_path = ABSPATH . '.htaccess';
	}

	/**
	 * Apply all optimizations to .htaccess.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function apply_optimizations() {
		if ( ! $this->is_apache() ) {
			return new \WP_Error( 'not_apache', __( 'Server is not running Apache.', 'nitroman' ) );
		}

		if ( ! $this->is_writable() ) {
			return new \WP_Error( 'not_writable', __( '.htaccess file is not writable.', 'nitroman' ) );
		}

		$rules = $this->generate_rules();
		return $this->insert_rules( $rules );
	}

	/**
	 * Remove all NitroMan rules from .htaccess.
	 *
	 * @return bool
	 */
	public function remove_optimizations() {
		if ( ! file_exists( $this->htaccess_path ) || ! $this->is_writable() ) {
			return false;
		}

		$content = file_get_contents( $this->htaccess_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$pattern = '/' . preg_quote( self::MARKER_START, '/' ) . '.*?' . preg_quote( self::MARKER_END, '/' ) . '\s*/s';
		$content = preg_replace( $pattern, '', $content );

		return (bool) file_put_contents( $this->htaccess_path, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	/**
	 * Generate all .htaccess optimization rules.
	 *
	 * @return string The complete rules block.
	 */
	public function generate_rules() {
		$rules = array();

		$rules[] = self::MARKER_START;
		$rules[] = '';

		// GZIP Compression — single biggest performance gain.
		$rules[] = '# GZIP Compression';
		$rules[] = '<IfModule mod_deflate.c>';
		$rules[] = '  # Compress HTML, CSS, JavaScript, Text, XML and fonts';
		$rules[] = '  AddOutputFilterByType DEFLATE application/javascript';
		$rules[] = '  AddOutputFilterByType DEFLATE application/json';
		$rules[] = '  AddOutputFilterByType DEFLATE application/ld+json';
		$rules[] = '  AddOutputFilterByType DEFLATE application/manifest+json';
		$rules[] = '  AddOutputFilterByType DEFLATE application/rss+xml';
		$rules[] = '  AddOutputFilterByType DEFLATE application/vnd.ms-fontobject';
		$rules[] = '  AddOutputFilterByType DEFLATE application/x-font-ttf';
		$rules[] = '  AddOutputFilterByType DEFLATE application/x-javascript';
		$rules[] = '  AddOutputFilterByType DEFLATE application/xhtml+xml';
		$rules[] = '  AddOutputFilterByType DEFLATE application/xml';
		$rules[] = '  AddOutputFilterByType DEFLATE font/opentype';
		$rules[] = '  AddOutputFilterByType DEFLATE font/otf';
		$rules[] = '  AddOutputFilterByType DEFLATE font/ttf';
		$rules[] = '  AddOutputFilterByType DEFLATE font/woff';
		$rules[] = '  AddOutputFilterByType DEFLATE font/woff2';
		$rules[] = '  AddOutputFilterByType DEFLATE image/svg+xml';
		$rules[] = '  AddOutputFilterByType DEFLATE image/x-icon';
		$rules[] = '  AddOutputFilterByType DEFLATE text/css';
		$rules[] = '  AddOutputFilterByType DEFLATE text/html';
		$rules[] = '  AddOutputFilterByType DEFLATE text/javascript';
		$rules[] = '  AddOutputFilterByType DEFLATE text/plain';
		$rules[] = '  AddOutputFilterByType DEFLATE text/xml';
		$rules[] = '';
		$rules[] = '  # Exclude pre-compressed files';
		$rules[] = '  SetEnvIfNoCase Request_URI \\.(?:gz|tgz|zip|bz2)$ no-gzip dont-vary';
		$rules[] = '  SetEnvIfNoCase Request_URI \\.(?:gif|jpe?g|png|webp|avif)$ no-gzip dont-vary';
		$rules[] = '</IfModule>';
		$rules[] = '';

		// Browser Caching — reduces repeat visitor load times by 60-80%.
		$rules[] = '# Browser Caching';
		$rules[] = '<IfModule mod_expires.c>';
		$rules[] = '  ExpiresActive On';
		$rules[] = '  ExpiresDefault "access plus 1 month"';
		$rules[] = '';
		$rules[] = '  # HTML';
		$rules[] = '  ExpiresByType text/html "access plus 0 seconds"';
		$rules[] = '';
		$rules[] = '  # Data interchange';
		$rules[] = '  ExpiresByType application/json "access plus 0 seconds"';
		$rules[] = '  ExpiresByType application/xml "access plus 0 seconds"';
		$rules[] = '  ExpiresByType text/xml "access plus 0 seconds"';
		$rules[] = '';
		$rules[] = '  # Feeds';
		$rules[] = '  ExpiresByType application/rss+xml "access plus 1 hour"';
		$rules[] = '  ExpiresByType application/atom+xml "access plus 1 hour"';
		$rules[] = '';
		$rules[] = '  # Favicon';
		$rules[] = '  ExpiresByType image/x-icon "access plus 1 year"';
		$rules[] = '  ExpiresByType image/vnd.microsoft.icon "access plus 1 year"';
		$rules[] = '';
		$rules[] = '  # Media';
		$rules[] = '  ExpiresByType image/gif "access plus 1 year"';
		$rules[] = '  ExpiresByType image/png "access plus 1 year"';
		$rules[] = '  ExpiresByType image/jpeg "access plus 1 year"';
		$rules[] = '  ExpiresByType image/webp "access plus 1 year"';
		$rules[] = '  ExpiresByType image/avif "access plus 1 year"';
		$rules[] = '  ExpiresByType image/svg+xml "access plus 1 year"';
		$rules[] = '  ExpiresByType video/mp4 "access plus 1 year"';
		$rules[] = '  ExpiresByType video/webm "access plus 1 year"';
		$rules[] = '';
		$rules[] = '  # Web fonts';
		$rules[] = '  ExpiresByType font/collection "access plus 1 year"';
		$rules[] = '  ExpiresByType font/eot "access plus 1 year"';
		$rules[] = '  ExpiresByType font/opentype "access plus 1 year"';
		$rules[] = '  ExpiresByType font/otf "access plus 1 year"';
		$rules[] = '  ExpiresByType font/ttf "access plus 1 year"';
		$rules[] = '  ExpiresByType font/woff "access plus 1 year"';
		$rules[] = '  ExpiresByType font/woff2 "access plus 1 year"';
		$rules[] = '  ExpiresByType application/vnd.ms-fontobject "access plus 1 year"';
		$rules[] = '';
		$rules[] = '  # CSS and JavaScript';
		$rules[] = '  ExpiresByType text/css "access plus 1 year"';
		$rules[] = '  ExpiresByType application/javascript "access plus 1 year"';
		$rules[] = '  ExpiresByType text/javascript "access plus 1 year"';
		$rules[] = '</IfModule>';
		$rules[] = '';

		// Cache-Control headers.
		$rules[] = '# Cache-Control Headers';
		$rules[] = '<IfModule mod_headers.c>';
		$rules[] = '  # Immutable static assets (1 year)';
		$rules[] = '  <FilesMatch "\.(ico|pdf|flv|jpg|jpeg|png|gif|webp|avif|svg|js|css|swf|woff|woff2|ttf|otf|eot)$">';
		$rules[] = '    Header set Cache-Control "max-age=31536000, public, immutable"';
		$rules[] = '  </FilesMatch>';
		$rules[] = '';
		$rules[] = '  # HTML — no caching (managed by NitroMan page cache)';
		$rules[] = '  <FilesMatch "\.(html|htm|php)$">';
		$rules[] = '    Header set Cache-Control "no-cache, no-store, must-revalidate"';
		$rules[] = '    Header set Pragma "no-cache"';
		$rules[] = '    Header set Expires 0';
		$rules[] = '  </FilesMatch>';
		$rules[] = '';
		$rules[] = '  # Vary header for compressed content';
		$rules[] = '  <IfModule mod_deflate.c>';
		$rules[] = '    Header append Vary Accept-Encoding';
		$rules[] = '  </IfModule>';
		$rules[] = '';
		$rules[] = '  # Security headers';
		$rules[] = '  Header set X-Content-Type-Options "nosniff"';
		$rules[] = '  Header set X-Frame-Options "SAMEORIGIN"';
		$rules[] = '  Header set X-XSS-Protection "1; mode=block"';
		$rules[] = '  Header set Referrer-Policy "strict-origin-when-cross-origin"';
		$rules[] = '</IfModule>';
		$rules[] = '';

		// Disable ETags — replaced by Cache-Control.
		$rules[] = '# Disable ETags';
		$rules[] = '<IfModule mod_headers.c>';
		$rules[] = '  Header unset ETag';
		$rules[] = '</IfModule>';
		$rules[] = 'FileETag None';
		$rules[] = '';

		// Keep-Alive connections.
		$rules[] = '# Keep-Alive';
		$rules[] = '<IfModule mod_headers.c>';
		$rules[] = '  Header set Connection keep-alive';
		$rules[] = '</IfModule>';
		$rules[] = '';

		// WebP serving — serve WebP when available.
		$rules[] = '# Serve WebP images when available';
		$rules[] = '<IfModule mod_rewrite.c>';
		$rules[] = '  RewriteEngine On';
		$rules[] = '  RewriteCond %{HTTP_ACCEPT} image/webp';
		$rules[] = '  RewriteCond %{REQUEST_FILENAME} (.*)\.(jpe?g|png)$';
		$rules[] = '  RewriteCond %1.webp -f';
		$rules[] = '  RewriteRule (.+)\.(jpe?g|png)$ $1.webp [T=image/webp,L]';
		$rules[] = '</IfModule>';
		$rules[] = '';

		$rules[] = self::MARKER_END;

		return implode( "\n", $rules );
	}

	/**
	 * Insert rules into .htaccess.
	 *
	 * @param string $rules The rules to insert.
	 * @return bool True on success.
	 */
	private function insert_rules( $rules ) {
		// Remove existing rules first.
		$this->remove_optimizations();

		$content = '';
		if ( file_exists( $this->htaccess_path ) ) {
			$content = file_get_contents( $this->htaccess_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		}

		// Insert before WordPress rules.
		$wp_marker = '# BEGIN WordPress';
		$position  = strpos( $content, $wp_marker );

		if ( false !== $position ) {
			$content = substr_replace( $content, $rules . "\n\n", $position, 0 );
		} else {
			$content = $rules . "\n\n" . $content;
		}

		return (bool) file_put_contents( $this->htaccess_path, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	/**
	 * Check if the server is Apache.
	 *
	 * @return bool
	 */
	private function is_apache() {
		global $is_apache, $is_litespeed;

		if ( $is_apache || $is_litespeed ) {
			return true;
		}

		if ( function_exists( 'apache_get_version' ) ) {
			return true;
		}

		$server = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
		if ( false !== stripos( $server, 'apache' ) || false !== stripos( $server, 'litespeed' ) ) {
			return true;
		}

		// Bypass for strict local-host proxy environments or if .htaccess already exists.
		if ( file_exists( ABSPATH . '.htaccess' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if .htaccess is writable.
	 *
	 * @return bool
	 */
	public function is_writable() {
		if ( file_exists( $this->htaccess_path ) ) {
			return wp_is_writable( $this->htaccess_path );
		}
		return wp_is_writable( ABSPATH );
	}

	/**
	 * Check if NitroMan rules are currently active in .htaccess.
	 *
	 * @return bool
	 */
	public function has_rules() {
		if ( ! file_exists( $this->htaccess_path ) ) {
			return false;
		}

		$content = file_get_contents( $this->htaccess_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return false !== strpos( $content, self::MARKER_START );
	}
}
