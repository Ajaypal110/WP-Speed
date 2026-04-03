<?php
/**
 * Page caching engine for NitroMan.
 *
 * @package NitroMan
 */

namespace NitroMan;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Cache_Manager
 *
 * Full-page caching engine with output buffer management,
 * GZIP compression support, and safe buffer nesting.
 */
class Cache_Manager {

	/**
	 * Singleton instance.
	 *
	 * @var Cache_Manager|null
	 */
	private static $instance = null;

	/**
	 * Whether output buffering has been started.
	 *
	 * @var bool
	 */
	private $buffer_started = false;

	/**
	 * The initial output buffer level when we started.
	 *
	 * @var int
	 */
	private $initial_ob_level = 0;

	/**
	 * Get the singleton instance.
	 *
	 * @return Cache_Manager
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
		$this->init_hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Try to serve cached page early.
		add_action( 'template_redirect', array( $this, 'serve_cached_page' ), 0 );

		// Start output buffering.
		add_action( 'template_redirect', array( $this, 'start_buffer' ), 1 );

		// End output buffering and cache the page.
		add_action( 'shutdown', array( $this, 'end_buffer' ), 0 );
	}

	/**
	 * Attempt to serve a cached page.
	 *
	 * @return void
	 */
	public function serve_cached_page() {
		if ( ! nitroman_should_cache_request() ) {
			return;
		}

		$cache_file = nitroman_get_cache_path();

		if ( ! file_exists( $cache_file ) ) {
			return;
		}

		// Check TTL.
		$ttl      = (int) nitroman_get_option( 'nitroman_cache_ttl' );
		$file_age = time() - filemtime( $cache_file );

		if ( $file_age > $ttl ) {
			// Cache expired, delete it.
			unlink( $cache_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			return;
		}

		// Try to serve GZIP version if supported.
		if ( nitroman_get_option( 'nitroman_cache_gzip' ) && $this->client_accepts_gzip() ) {
			$gz_file = $cache_file . '.gz';
			if ( file_exists( $gz_file ) ) {
				header( 'Content-Encoding: gzip' );
				header( 'Content-Type: text/html; charset=UTF-8' );
				header( 'X-NitroMan-Cache: HIT (gzip)' );
				readfile( $gz_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
				exit;
			}
		}

		// Serve the regular cached page.
		header( 'Content-Type: text/html; charset=UTF-8' );
		header( 'X-NitroMan-Cache: HIT' );
		readfile( $cache_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}

	/**
	 * Start output buffering.
	 *
	 * @return void
	 */
	public function start_buffer() {
		if ( ! nitroman_should_cache_request() ) {
			return;
		}

		// Buffer nesting safety: record the current level.
		$this->initial_ob_level = ob_get_level();

		ob_start( array( $this, 'process_buffer' ) );
		$this->buffer_started = true;
	}

	/**
	 * End output buffering safely.
	 *
	 * @return void
	 */
	public function end_buffer() {
		if ( ! $this->buffer_started ) {
			return;
		}

		// Only flush if our buffer level is still active.
		$current_level = ob_get_level();
		if ( $current_level > $this->initial_ob_level ) {
			ob_end_flush();
		}

		$this->buffer_started = false;
	}

	/**
	 * Process the output buffer and write cache file.
	 *
	 * @param string $buffer The page HTML content.
	 * @return string The unmodified buffer.
	 */
	public function process_buffer( $buffer ) {
		// Don't cache empty or error pages.
		if ( empty( $buffer ) || strlen( $buffer ) < 255 ) {
			return $buffer;
		}

		// Don't cache if it doesn't look like valid HTML.
		if ( false === stripos( $buffer, '</html>' ) ) {
			return $buffer;
		}

		// Minify HTML before caching (10-25% size reduction).
		if ( nitroman_get_option( 'nitroman_minify_html' ) ) {
			$original_size = strlen( $buffer );
			$buffer        = HTML_Minifier::minify( $buffer );
			$minified_size = strlen( $buffer );
			$savings       = round( ( 1 - $minified_size / $original_size ) * 100, 1 );
		}

		// Add cache signature comment.
		$timestamp = gmdate( 'Y-m-d H:i:s' );
		$signature = sprintf(
			"\n<!-- Cached by NitroMan v%s on %s UTC -->",
			NITROMAN_VERSION,
			$timestamp
		);
		$buffer .= $signature;

		// Write the cache file.
		$cache_file = nitroman_get_cache_path();
		$cache_dir  = dirname( $cache_file );

		if ( ! is_dir( $cache_dir ) ) {
			wp_mkdir_p( $cache_dir );
		}

		// Write HTML cache.
		file_put_contents( $cache_file, $buffer ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		// Write GZIP cache if enabled.
		if ( nitroman_get_option( 'nitroman_cache_gzip' ) && function_exists( 'gzencode' ) ) {
			$gz_content = gzencode( $buffer, 6 );
			if ( false !== $gz_content ) {
				file_put_contents( $cache_file . '.gz', $gz_content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				// Free memory immediately.
				unset( $gz_content );
			}
		}

		return $buffer;
	}

	/**
	 * Check if the client accepts GZIP encoding.
	 *
	 * @return bool
	 */
	private function client_accepts_gzip() {
		$accept = isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ) : '';
		return false !== strpos( $accept, 'gzip' );
	}
}
