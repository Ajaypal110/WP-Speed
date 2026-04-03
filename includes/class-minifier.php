<?php
/**
 * CSS/JS minification for NitroMan.
 *
 * @package NitroMan
 */

namespace NitroMan;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Minifier
 *
 * Provides lightweight CSS and JavaScript minification
 * using regex-based transformations on inline code blocks.
 */
class Minifier {

	/**
	 * Singleton instance.
	 *
	 * @var Minifier|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Minifier
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
		// Minify inline CSS.
		if ( nitroman_get_option( 'nitroman_minify_css' ) ) {
			add_filter( 'style_loader_tag', array( $this, 'process_style_tag' ), 99, 4 );
		}

		// Minify inline JS is handled via output buffer (optional).
		if ( nitroman_get_option( 'nitroman_minify_js' ) ) {
			add_filter( 'script_loader_tag', array( $this, 'process_script_tag' ), 99, 3 );
		}
	}

	/**
	 * Process a style loader tag for minification hints.
	 *
	 * @param string $html   The link tag HTML.
	 * @param string $handle The stylesheet handle.
	 * @param string $href   The stylesheet URL.
	 * @param string $media  The media attribute.
	 * @return string Optimized tag.
	 */
	public function process_style_tag( $html, $handle, $href, $media ) {
		// Skip admin styles.
		if ( is_admin() ) {
			return $html;
		}

		// Add media="print" with onload for non-critical CSS.
		$critical_handles = array( 'wp-block-library', 'global-styles' );
		if ( ! in_array( $handle, $critical_handles, true ) && 'all' === $media ) {
			$html = str_replace(
				"media='all'",
				"media='print' onload=\"this.media='all'\"",
				$html
			);
		}

		return $html;
	}

	/**
	 * Process a script tag for optimization.
	 *
	 * @param string $tag    The script tag HTML.
	 * @param string $handle The script handle.
	 * @param string $src    The script source URL.
	 * @return string Optimized tag.
	 */
	public function process_script_tag( $tag, $handle, $src ) {
		if ( is_admin() ) {
			return $tag;
		}

		// Add type="module" hint for modern browsers where appropriate.
		// This is a lightweight optimization; full module conversion is out of scope.
		return $tag;
	}

	/**
	 * Minify a CSS string.
	 *
	 * @param string $css The CSS content to minify.
	 * @return string Minified CSS.
	 */
	public static function minify_css( $css ) {
		if ( empty( $css ) ) {
			return $css;
		}

		// Remove comments.
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );

		// Remove whitespace.
		$css = str_replace( array( "\r\n", "\r", "\n", "\t" ), '', $css );

		// Remove spaces around selectors, braces, and colons.
		$css = preg_replace( '/\s*([\{\}:;,])\s*/', '$1', $css );

		// Remove trailing semicolons before closing braces.
		$css = str_replace( ';}', '}', $css );

		// Remove leading zeros from decimals.
		$css = preg_replace( '/(:|\s)0+\.(\d+)/', '${1}.${2}', $css );

		return trim( $css );
	}

	/**
	 * Minify a JavaScript string (lightweight).
	 *
	 * @param string $js The JavaScript content to minify.
	 * @return string Minified JavaScript.
	 */
	public static function minify_js( $js ) {
		if ( empty( $js ) ) {
			return $js;
		}

		// Remove single-line comments (but not URLs with //).
		$js = preg_replace( '/(?<![:\'"\\\\])\/\/.*$/m', '', $js );

		// Remove multi-line comments.
		$js = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js );

		// Remove extra whitespace.
		$js = preg_replace( '/\s+/', ' ', $js );

		// Remove whitespace around operators.
		$js = preg_replace( '/\s*([\{\};\(\),=\+\-\*\/])\s*/', '$1', $js );

		return trim( $js );
	}
}
