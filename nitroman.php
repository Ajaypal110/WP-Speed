<?php
/**
 * Plugin Name:       NitroMan – Speed Optimization Engine
 * Plugin URI:        https://github.com/Ajaypal110/NitroMan.git
 * Description:       Advanced performance optimizer to boost WordPress speed safely. Page caching, asset optimization, image compression, database cleanup, and Core Web Vitals improvements.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Ajaypal Singh
 * Author URI:        https://github.com/Ajaypal110
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       nitroman
 * Domain Path:       /languages
 *
 * @package NitroMan
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version constant.
 */
define( 'NITROMAN_VERSION', '1.0.0' );

/**
 * Plugin file path constant.
 */
define( 'NITROMAN_FILE', __FILE__ );

/**
 * Plugin directory path constant.
 */
define( 'NITROMAN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin URL constant.
 */
define( 'NITROMAN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename constant.
 */
define( 'NITROMAN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Cache directory path constant.
 */
define( 'NITROMAN_CACHE_DIR', WP_CONTENT_DIR . '/cache/nitroman/' );

/**
 * Cache URL constant.
 */
define( 'NITROMAN_CACHE_URL', content_url( '/cache/nitroman/' ) );

/**
 * Minimum PHP version required.
 */
define( 'NITROMAN_MIN_PHP', '7.4' );

/**
 * Minimum WordPress version required.
 */
define( 'NITROMAN_MIN_WP', '5.8' );

/**
 * Check PHP version compatibility before loading.
 */
if ( version_compare( PHP_VERSION, NITROMAN_MIN_PHP, '<' ) ) {
	add_action( 'admin_notices', 'nitroman_php_version_notice' );
	return;
}

/**
 * Display admin notice for insufficient PHP version.
 *
 * @return void
 */
function nitroman_php_version_notice() {
	$message = sprintf(
		/* translators: 1: Required PHP version, 2: Current PHP version. */
		esc_html__( 'NitroMan requires PHP %1$s or higher. You are running PHP %2$s. Please upgrade your PHP version.', 'nitroman' ),
		NITROMAN_MIN_PHP,
		PHP_VERSION
	);
	printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
}

/**
 * Check WordPress version compatibility.
 */
if ( version_compare( get_bloginfo( 'version' ), NITROMAN_MIN_WP, '<' ) ) {
	add_action( 'admin_notices', 'nitroman_wp_version_notice' );
	return;
}

/**
 * Display admin notice for insufficient WordPress version.
 *
 * @return void
 */
function nitroman_wp_version_notice() {
	$message = sprintf(
		/* translators: 1: Required WordPress version. */
		esc_html__( 'NitroMan requires WordPress %1$s or higher. Please update WordPress.', 'nitroman' ),
		NITROMAN_MIN_WP
	);
	printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
}

// Load helper functions.
require_once NITROMAN_PATH . 'includes/helper-functions.php';

// Load the main initialization class.
require_once NITROMAN_PATH . 'includes/class-security.php';
require_once NITROMAN_PATH . 'includes/class-init.php';

/**
 * Plugin activation hook.
 *
 * @return void
 */
function nitroman_activate() {
	// Set default options.
	$defaults = nitroman_get_default_options();
	foreach ( $defaults as $key => $value ) {
		if ( false === get_option( $key ) ) {
			add_option( $key, $value );
		}
	}

	// Create cache directory.
	if ( ! file_exists( NITROMAN_CACHE_DIR ) ) {
		wp_mkdir_p( NITROMAN_CACHE_DIR );
	}

	// Create an index.php in the cache directory for security.
	$index_file = NITROMAN_CACHE_DIR . 'index.php';
	if ( ! file_exists( $index_file ) ) {
		file_put_contents( $index_file, '<?php // Silence is golden.' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	// Schedule cron events.
	if ( ! wp_next_scheduled( 'nitroman_cache_preload' ) ) {
		wp_schedule_event( time(), 'twicedaily', 'nitroman_cache_preload' );
	}

	if ( ! wp_next_scheduled( 'nitroman_db_cleanup' ) ) {
		wp_schedule_event( time(), 'daily', 'nitroman_db_cleanup' );
	}

	// Set activation flag.
	set_transient( 'nitroman_activated', true, 30 );

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'nitroman_activate' );

/**
 * Plugin deactivation hook.
 *
 * @return void
 */
function nitroman_deactivate() {
	// Clear scheduled events.
	wp_clear_scheduled_hook( 'nitroman_cache_preload' );
	wp_clear_scheduled_hook( 'nitroman_db_cleanup' );

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'nitroman_deactivate' );

/**
 * Initialize the plugin.
 *
 * @return void
 */
function nitroman_init() {
	NitroMan\Init::get_instance();
}
add_action( 'plugins_loaded', 'nitroman_init' );

/**
 * Load plugin textdomain for translations.
 *
 * @return void
 */
function nitroman_load_textdomain() {
	load_plugin_textdomain( 'nitroman', false, dirname( NITROMAN_BASENAME ) . '/languages/' );
}
add_action( 'init', 'nitroman_load_textdomain' );
