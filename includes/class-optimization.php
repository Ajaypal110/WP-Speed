<?php
/**
 * HTML optimization engine for NitroMan.
 *
 * @package NitroMan
 */

namespace NitroMan;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Optimization_Engine
 *
 * Handles HTML-level optimizations: DNS prefetch, preconnect,
 * preload hints, and critical CSS inlining.
 */
class Optimization_Engine {

	/**
	 * Singleton instance.
	 *
	 * @var Optimization_Engine|null
	 */
	private static $instance = null;

	/**
	 * Recursion guard.
	 *
	 * @var bool
	 */
	private static $is_processing = false;

	/**
	 * Get the singleton instance.
	 *
	 * @return Optimization_Engine
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
		// DNS prefetch and preconnect.
		if ( nitroman_get_option( 'nitroman_dns_prefetch' ) || nitroman_get_option( 'nitroman_preconnect' ) ) {
			add_action( 'wp_head', array( $this, 'add_resource_hints' ), 1 );
		}

		// Font preloading.
		$preload_fonts = nitroman_get_option( 'nitroman_preload_fonts' );
		if ( ! empty( $preload_fonts ) ) {
			add_action( 'wp_head', array( $this, 'add_font_preloads' ), 1 );
		}

		// HTML cleanup of <head>.
		add_action( 'wp_head', array( $this, 'cleanup_head' ), 1 );
	}

	/**
	 * Add DNS prefetch and preconnect resource hints.
	 *
	 * @return void
	 */
	public function add_resource_hints() {
		// Default domains for prefetch.
		$default_domains = array(
			'//fonts.googleapis.com',
			'//fonts.gstatic.com',
			'//ajax.googleapis.com',
			'//cdnjs.cloudflare.com',
		);

		// User-defined domains.
		$custom_urls = nitroman_get_option( 'nitroman_dns_prefetch_urls' );
		if ( ! empty( $custom_urls ) ) {
			$custom_domains = array_filter( array_map( 'trim', explode( "\n", $custom_urls ) ) );
			$default_domains = array_merge( $default_domains, $custom_domains );
		}

		$default_domains = array_unique( $default_domains );

		// DNS Prefetch.
		if ( nitroman_get_option( 'nitroman_dns_prefetch' ) ) {
			foreach ( $default_domains as $domain ) {
				printf(
					'<link rel="dns-prefetch" href="%s">' . "\n",
					esc_attr( $domain )
				);
			}
		}

		// Preconnect (only for critical domains).
		if ( nitroman_get_option( 'nitroman_preconnect' ) ) {
			$preconnect_domains = array(
				'https://fonts.googleapis.com',
				'https://fonts.gstatic.com',
			);

			foreach ( $preconnect_domains as $domain ) {
				printf(
					'<link rel="preconnect" href="%s" crossorigin>' . "\n",
					esc_url( $domain )
				);
			}
		}
	}

	/**
	 * Add font preload hints.
	 *
	 * @return void
	 */
	public function add_font_preloads() {
		$fonts = nitroman_get_option( 'nitroman_preload_fonts' );
		if ( empty( $fonts ) ) {
			return;
		}

		$font_urls = array_filter( array_map( 'trim', explode( "\n", $fonts ) ) );

		foreach ( $font_urls as $url ) {
			// Determine the font type.
			$type = 'font/woff2';
			if ( false !== strpos( $url, '.woff2' ) ) {
				$type = 'font/woff2';
			} elseif ( false !== strpos( $url, '.woff' ) ) {
				$type = 'font/woff';
			} elseif ( false !== strpos( $url, '.ttf' ) ) {
				$type = 'font/ttf';
			}

			printf(
				'<link rel="preload" href="%s" as="font" type="%s" crossorigin>' . "\n",
				esc_url( $url ),
				esc_attr( $type )
			);
		}
	}

	/**
	 * Add cleanup optimizations to <head>.
	 *
	 * @return void
	 */
	public function cleanup_head() {
		// Remove WordPress version meta tag.
		remove_action( 'wp_head', 'wp_generator' );
	}
}
