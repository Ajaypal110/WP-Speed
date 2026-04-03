<?php
/**
 * Helper functions for NitroMan.
 *
 * @package NitroMan
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the default plugin options.
 *
 * @return array Default options keyed by option name.
 */
function nitroman_get_default_options() {
	return array(
		// Cache settings.
		'nitroman_cache_enabled'        => false,
		'nitroman_cache_ttl'            => 86400,
		'nitroman_cache_mobile'         => false,
		'nitroman_cache_logged_in'      => false,
		'nitroman_cache_exclude_urls'   => '',
		'nitroman_cache_gzip'           => true,

		// Image settings.
		'nitroman_lazy_load'            => true,
		'nitroman_lazy_load_threshold'  => '200px',
		'nitroman_add_dimensions'       => true,
		'nitroman_webp_support'         => true,

		// Script settings.
		'nitroman_defer_js'             => true,
		'nitroman_async_js'             => false,
		'nitroman_delay_js'             => false,
		'nitroman_delay_js_exclusions'  => '',
		'nitroman_minify_js'            => false,
		'nitroman_minify_css'           => false,
		'nitroman_combine_css'          => false,
		'nitroman_remove_query_strings' => true,
		'nitroman_minify_html'          => true,

		// Server settings.
		'nitroman_htaccess_optimization' => false,

		// Database settings.
		'nitroman_db_cleanup_enabled'   => false,
		'nitroman_clean_revisions'      => true,
		'nitroman_clean_auto_drafts'    => true,
		'nitroman_clean_trashed'        => true,
		'nitroman_clean_spam'           => true,
		'nitroman_clean_transients'     => true,
		'nitroman_clean_orphan_meta'    => false,
		'nitroman_revisions_to_keep'    => 5,

		// Advanced settings.
		'nitroman_dns_prefetch'         => true,
		'nitroman_dns_prefetch_urls'    => '',
		'nitroman_preconnect'           => true,
		'nitroman_preload_fonts'        => '',
		'nitroman_disable_emojis'       => true,
		'nitroman_disable_embeds'       => false,
		'nitroman_disable_xmlrpc'       => true,
		'nitroman_remove_wlwmanifest'   => true,
		'nitroman_remove_rsd'           => true,
		'nitroman_remove_shortlink'     => true,
		'nitroman_remove_rest_api_link' => false,
		'nitroman_heartbeat_control'    => 'default',
		'nitroman_heartbeat_frequency'  => 60,
	);
}

/**
 * Get a single plugin option with its default.
 *
 * @param string $key The option key.
 * @return mixed The option value.
 */
function nitroman_get_option( $key ) {
	$defaults = nitroman_get_default_options();
	$default  = isset( $defaults[ $key ] ) ? $defaults[ $key ] : false;

	return get_option( $key, $default );
}

/**
 * Check if page caching is enabled.
 *
 * @return bool
 */
function nitroman_is_cache_enabled() {
	return (bool) nitroman_get_option( 'nitroman_cache_enabled' );
}

/**
 * Check if the current request should be cached.
 *
 * @return bool
 */
function nitroman_should_cache_request() {
	// Don't cache in admin.
	if ( is_admin() ) {
		return false;
	}

	// Don't cache AJAX requests.
	if ( wp_doing_ajax() ) {
		return false;
	}

	// Don't cache cron requests.
	if ( wp_doing_cron() ) {
		return false;
	}

	// Don't cache REST API requests.
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return false;
	}

	// Don't cache POST requests.
	if ( 'GET' !== $_SERVER['REQUEST_METHOD'] ) {
		return false;
	}

	// Don't cache if query parameters exist (unless explicitly allowed).
	if ( ! empty( $_GET ) && ! apply_filters( 'nitroman_cache_query_strings', false ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return false;
	}

	// Don't cache for logged-in users (unless enabled).
	if ( is_user_logged_in() && ! nitroman_get_option( 'nitroman_cache_logged_in' ) ) {
		return false;
	}

	// Don't cache search results.
	if ( is_search() ) {
		return false;
	}

	// Don't cache 404 pages.
	if ( is_404() ) {
		return false;
	}

	// Check excluded URLs.
	$excluded    = nitroman_get_option( 'nitroman_cache_exclude_urls' );
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

	if ( ! empty( $excluded ) ) {
		$excluded_urls = array_filter( array_map( 'trim', explode( "\n", $excluded ) ) );
		foreach ( $excluded_urls as $pattern ) {
			if ( false !== strpos( $request_uri, $pattern ) ) {
				return false;
			}
		}
	}

	return true;
}

/**
 * Generate cache file path for a given URL.
 *
 * @param string $url The URL to generate a cache path for. Defaults to current request.
 * @return string The cache file path.
 */
function nitroman_get_cache_path( $url = '' ) {
	if ( empty( $url ) ) {
		$url  = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$url .= isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
	}

	$parsed = wp_parse_url( $url );
	$host   = isset( $parsed['host'] ) ? $parsed['host'] : sanitize_file_name( $url );
	$path   = isset( $parsed['path'] ) ? $parsed['path'] : '/';

	// Normalize the path.
	$path = trailingslashit( $path );

	// Build the cache directory.
	$cache_dir = NITROMAN_CACHE_DIR . $host . $path;

	return $cache_dir . 'index.html';
}

/**
 * Sanitize a textarea input of URLs (one per line).
 *
 * @param string $input Raw textarea content.
 * @return string Sanitized content.
 */
function nitroman_sanitize_textarea_urls( $input ) {
	$lines = explode( "\n", $input );
	$clean = array();

	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( ! empty( $line ) ) {
			$clean[] = esc_url_raw( $line );
		}
	}

	return implode( "\n", $clean );
}

/**
 * Sanitize a textarea input of text (one per line).
 *
 * @param string $input Raw textarea content.
 * @return string Sanitized content.
 */
function nitroman_sanitize_textarea_text( $input ) {
	$lines = explode( "\n", $input );
	$clean = array();

	foreach ( $lines as $line ) {
		$line = sanitize_text_field( trim( $line ) );
		if ( ! empty( $line ) ) {
			$clean[] = $line;
		}
	}

	return implode( "\n", $clean );
}

/**
 * Format bytes to a human-readable string.
 *
 * @param int $bytes    Number of bytes.
 * @param int $decimals Number of decimal places.
 * @return string Formatted string.
 */
function nitroman_format_bytes( $bytes, $decimals = 2 ) {
	if ( 0 === $bytes ) {
		return '0 B';
	}

	$factor = floor( log( $bytes, 1024 ) );
	$units  = array( 'B', 'KB', 'MB', 'GB', 'TB' );
	$unit   = isset( $units[ $factor ] ) ? $units[ $factor ] : 'B';

	return sprintf( "%.{$decimals}f %s", $bytes / pow( 1024, $factor ), $unit );
}

/**
 * Get the total size of the cache directory.
 *
 * @return int Size in bytes.
 */
function nitroman_get_cache_size() {
	if ( ! is_dir( NITROMAN_CACHE_DIR ) ) {
		return 0;
	}

	$size     = 0;
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( NITROMAN_CACHE_DIR ) );

	foreach ( $iterator as $file ) {
		if ( $file->isFile() ) {
			$size += $file->getSize();
		}
	}

	return $size;
}

/**
 * Count the number of cached pages.
 *
 * @return int Number of cached HTML files.
 */
function nitroman_get_cache_count() {
	if ( ! is_dir( NITROMAN_CACHE_DIR ) ) {
		return 0;
	}

	$count    = 0;
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( NITROMAN_CACHE_DIR ) );

	foreach ( $iterator as $file ) {
		if ( $file->isFile() && 'html' === pathinfo( $file->getFilename(), PATHINFO_EXTENSION ) ) {
			++$count;
		}
	}

	return $count;
}

/**
 * Delete all files in the cache directory recursively.
 *
 * @param string $dir Directory path. Defaults to NITROMAN_CACHE_DIR.
 * @return bool True on success.
 */
function nitroman_purge_cache( $dir = '' ) {
	if ( empty( $dir ) ) {
		$dir = NITROMAN_CACHE_DIR;
	}

	if ( ! is_dir( $dir ) ) {
		return false;
	}

	$items = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $items as $item ) {
		if ( $item->isDir() ) {
			rmdir( $item->getRealPath() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		} else {
			unlink( $item->getRealPath() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		}
	}

	return true;
}

/**
 * Check if the current request is a frontend request.
 *
 * @return bool
 */
function nitroman_is_frontend() {
	if ( is_admin() ) {
		return false;
	}

	if ( wp_doing_ajax() ) {
		return false;
	}

	if ( wp_doing_cron() ) {
		return false;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return false;
	}

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return false;
	}

	return true;
}
