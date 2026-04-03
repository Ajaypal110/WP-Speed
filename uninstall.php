<?php
/**
 * Uninstall script for NitroMan.
 *
 * Runs only when the plugin is deleted via the WordPress admin.
 * Removes all plugin options, cache files, and scheduled events.
 *
 * @package NitroMan
 */

// Prevent direct access.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up all plugin data on uninstall.
 */
function nitroman_uninstall_cleanup() {
	// All option keys used by the plugin.
	$options = array(
		'nitroman_cache_enabled',
		'nitroman_cache_ttl',
		'nitroman_cache_mobile',
		'nitroman_cache_logged_in',
		'nitroman_cache_exclude_urls',
		'nitroman_cache_gzip',
		'nitroman_lazy_load',
		'nitroman_lazy_load_threshold',
		'nitroman_add_dimensions',
		'nitroman_webp_support',
		'nitroman_defer_js',
		'nitroman_async_js',
		'nitroman_delay_js',
		'nitroman_delay_js_exclusions',
		'nitroman_minify_js',
		'nitroman_minify_css',
		'nitroman_combine_css',
		'nitroman_remove_query_strings',
		'nitroman_db_cleanup_enabled',
		'nitroman_clean_revisions',
		'nitroman_clean_auto_drafts',
		'nitroman_clean_trashed',
		'nitroman_clean_spam',
		'nitroman_clean_transients',
		'nitroman_clean_orphan_meta',
		'nitroman_revisions_to_keep',
		'nitroman_dns_prefetch',
		'nitroman_dns_prefetch_urls',
		'nitroman_preconnect',
		'nitroman_preload_fonts',
		'nitroman_disable_emojis',
		'nitroman_disable_embeds',
		'nitroman_disable_xmlrpc',
		'nitroman_remove_wlwmanifest',
		'nitroman_remove_rsd',
		'nitroman_remove_shortlink',
		'nitroman_remove_rest_api_link',
		'nitroman_heartbeat_control',
		'nitroman_heartbeat_frequency',
	);

	// Delete all options.
	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// Clear scheduled cron events.
	wp_clear_scheduled_hook( 'nitroman_cache_preload' );
	wp_clear_scheduled_hook( 'nitroman_db_cleanup' );

	// Remove cache directory.
	$cache_dir = WP_CONTENT_DIR . '/cache/nitroman/';
	if ( is_dir( $cache_dir ) ) {
		nitroman_uninstall_rmdir( $cache_dir );
	}

	// Clear any remaining transients.
	delete_transient( 'nitroman_activated' );
}

/**
 * Recursively remove a directory and its contents.
 *
 * @param string $dir Directory path.
 * @return void
 */
function nitroman_uninstall_rmdir( $dir ) {
	if ( ! is_dir( $dir ) ) {
		return;
	}

	$objects = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $objects as $object ) {
		if ( $object->isDir() ) {
			rmdir( $object->getRealPath() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		} else {
			unlink( $object->getRealPath() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		}
	}

	rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
}

// Run cleanup.
nitroman_uninstall_cleanup();
