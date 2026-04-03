<?php
/**
 * Script and style optimization for NitroMan.
 *
 * @package NitroMan
 */

namespace NitroMan;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Script_Manager
 *
 * Handles defer/async script loading, query string removal,
 * and safe jQuery dependency management with recursion guards.
 */
class Script_Manager {

	/**
	 * Singleton instance.
	 *
	 * @var Script_Manager|null
	 */
	private static $instance = null;

	/**
	 * Recursion guard for script_loader_tag.
	 *
	 * @var bool
	 */
	private static $is_processing_scripts = false;

	/**
	 * Recursion guard for style_loader_tag.
	 *
	 * @var bool
	 */
	private static $is_processing_styles = false;

	/**
	 * Scripts to exclude from deferring.
	 *
	 * @var array
	 */
	private $exclude_scripts = array(
		'jquery-core',
		'jquery-migrate',
		'jquery',
		'wp-includes/js/jquery',
	);

	/**
	 * Get the singleton instance.
	 *
	 * @return Script_Manager
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
		// Defer JavaScript.
		if ( nitroman_get_option( 'nitroman_defer_js' ) ) {
			add_filter( 'script_loader_tag', array( $this, 'defer_scripts' ), 10, 3 );
		}

		// Async JavaScript (alternative to defer).
		if ( nitroman_get_option( 'nitroman_async_js' ) && ! nitroman_get_option( 'nitroman_defer_js' ) ) {
			add_filter( 'script_loader_tag', array( $this, 'async_scripts' ), 10, 3 );
		}

		// Remove query strings from static resources.
		if ( nitroman_get_option( 'nitroman_remove_query_strings' ) ) {
			add_filter( 'script_loader_src', array( $this, 'remove_query_strings' ), 15 );
			add_filter( 'style_loader_src', array( $this, 'remove_query_strings' ), 15 );
		}
	}

	/**
	 * Add defer attribute to script tags.
	 *
	 * @param string $tag    The full <script> tag HTML.
	 * @param string $handle The script handle (registered name).
	 * @param string $src    The script source URL.
	 * @return string Modified tag.
	 */
	public function defer_scripts( $tag, $handle, $src ) {
		// Recursion guard.
		if ( self::$is_processing_scripts ) {
			return $tag;
		}
		self::$is_processing_scripts = true;

		// Don't modify in admin.
		if ( is_admin() ) {
			self::$is_processing_scripts = false;
			return $tag;
		}

		// Don't defer excluded scripts.
		if ( $this->is_excluded_script( $handle, $src ) ) {
			self::$is_processing_scripts = false;
			return $tag;
		}

		// Don't add defer if it already exists.
		if ( false !== strpos( $tag, 'defer' ) ) {
			self::$is_processing_scripts = false;
			return $tag;
		}

		// Don't defer inline scripts or scripts without src.
		if ( empty( $src ) ) {
			self::$is_processing_scripts = false;
			return $tag;
		}

		$tag = str_replace( ' src=', ' defer src=', $tag );

		self::$is_processing_scripts = false;
		return $tag;
	}

	/**
	 * Add async attribute to script tags.
	 *
	 * @param string $tag    The full <script> tag HTML.
	 * @param string $handle The script handle.
	 * @param string $src    The script source URL.
	 * @return string Modified tag.
	 */
	public function async_scripts( $tag, $handle, $src ) {
		if ( is_admin() ) {
			return $tag;
		}

		if ( $this->is_excluded_script( $handle, $src ) ) {
			return $tag;
		}

		if ( false !== strpos( $tag, 'async' ) ) {
			return $tag;
		}

		if ( empty( $src ) ) {
			return $tag;
		}

		$tag = str_replace( ' src=', ' async src=', $tag );

		return $tag;
	}

	/**
	 * Check if a script should be excluded from optimization.
	 *
	 * @param string $handle The script handle.
	 * @param string $src    The script source.
	 * @return bool
	 */
	private function is_excluded_script( $handle, $src ) {
		// Check handle against exclusion list.
		foreach ( $this->exclude_scripts as $exclude ) {
			if ( $handle === $exclude || false !== strpos( $src, $exclude ) ) {
				return true;
			}
		}

		// Check user-defined exclusions.
		$user_exclusions = nitroman_get_option( 'nitroman_delay_js_exclusions' );
		if ( ! empty( $user_exclusions ) ) {
			$exclusion_list = array_filter( array_map( 'trim', explode( "\n", $user_exclusions ) ) );
			foreach ( $exclusion_list as $pattern ) {
				if ( false !== strpos( $handle, $pattern ) || false !== strpos( $src, $pattern ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Remove query strings (version numbers) from static resource URLs.
	 *
	 * @param string $src The resource URL.
	 * @return string URL without query string.
	 */
	public function remove_query_strings( $src ) {
		if ( empty( $src ) ) {
			return $src;
		}

		// Only strip ?ver= query strings.
		if ( false !== strpos( $src, '?ver=' ) ) {
			$src = remove_query_arg( 'ver', $src );
		}

		return $src;
	}
}
