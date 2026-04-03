<?php
/**
 * Main initialization class for NitroMan.
 *
 * @package NitroMan
 */

namespace NitroMan;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Init
 *
 * The master controller that bootstraps all plugin modules.
 * Uses singleton pattern to prevent duplicate initialization.
 */
class Init {

	/**
	 * Singleton instance.
	 *
	 * @var Init|null
	 */
	private static $instance = null;

	/**
	 * Whether modules have been loaded.
	 *
	 * @var bool
	 */
	private $modules_loaded = false;

	/**
	 * Get the singleton instance.
	 *
	 * @return Init
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
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @throws \Exception Always.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}

	/**
	 * Load required class files.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		$includes_path = NITROMAN_PATH . 'includes/';

		// Core modules.
		require_once $includes_path . 'class-cache.php';
		require_once $includes_path . 'class-optimization.php';
		require_once $includes_path . 'class-images.php';
		require_once $includes_path . 'class-script-manager.php';
		require_once $includes_path . 'class-minifier.php';
		require_once $includes_path . 'class-html-minifier.php';
		require_once $includes_path . 'class-database.php';
		require_once $includes_path . 'class-htaccess.php';

		// Admin modules (only when in admin).
		if ( is_admin() ) {
			require_once NITROMAN_PATH . 'admin/class-admin.php';
		}
	}

	/**
	 * Register all WordPress hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Load modules after theme setup to ensure conditional tags work.
		add_action( 'wp', array( $this, 'load_frontend_modules' ) );

		// Admin modules.
		if ( is_admin() ) {
			$this->load_admin_modules();
		}

		// Register cleanup cron handler.
		add_action( 'nitroman_db_cleanup', array( $this, 'run_database_cleanup' ) );

		// Cache invalidation hooks.
		add_action( 'save_post', array( $this, 'invalidate_cache_on_update' ) );
		add_action( 'comment_post', array( $this, 'invalidate_cache_on_update' ) );
		add_action( 'wp_update_comment_count', array( $this, 'invalidate_cache_on_update' ) );
		add_action( 'switch_theme', array( $this, 'purge_entire_cache' ) );
		add_action( 'customize_save_after', array( $this, 'purge_entire_cache' ) );

		// WordPress performance tweaks.
		add_action( 'init', array( $this, 'apply_wp_tweaks' ) );

		// Activation redirect.
		add_action( 'admin_init', array( $this, 'activation_redirect' ) );

		// Plugin action links.
		add_filter( 'plugin_action_links_' . NITROMAN_BASENAME, array( $this, 'add_action_links' ) );
	}

	/**
	 * Load frontend optimization modules (lazy-loaded).
	 *
	 * @return void
	 */
	public function load_frontend_modules() {
		// Guard: Only load once.
		if ( $this->modules_loaded ) {
			return;
		}
		$this->modules_loaded = true;

		// Guard: Only on the frontend.
		if ( ! nitroman_is_frontend() ) {
			return;
		}

		// Page caching.
		if ( nitroman_is_cache_enabled() ) {
			Cache_Manager::get_instance();
		}

		// HTML optimization.
		Optimization_Engine::get_instance();

		// Image optimization.
		if ( nitroman_get_option( 'nitroman_lazy_load' ) || nitroman_get_option( 'nitroman_add_dimensions' ) ) {
			Image_Optimizer::get_instance();
		}

		// Script optimization.
		if ( nitroman_get_option( 'nitroman_defer_js' ) || nitroman_get_option( 'nitroman_remove_query_strings' ) ) {
			Script_Manager::get_instance();
		}

		// CSS/JS minification.
		if ( nitroman_get_option( 'nitroman_minify_js' ) || nitroman_get_option( 'nitroman_minify_css' ) ) {
			Minifier::get_instance();
		}
	}

	/**
	 * Load admin modules.
	 *
	 * @return void
	 */
	private function load_admin_modules() {
		Admin_Manager::get_instance();
	}

	/**
	 * Apply WordPress performance tweaks.
	 *
	 * @return void
	 */
	public function apply_wp_tweaks() {
		// Disable emoji scripts.
		if ( nitroman_get_option( 'nitroman_disable_emojis' ) ) {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );
			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
			add_filter( 'tiny_mce_plugins', array( $this, 'disable_emojis_tinymce' ) );
			add_filter( 'wp_resource_hints', array( $this, 'disable_emojis_dns_prefetch' ), 10, 2 );
		}

		// Disable embeds.
		if ( nitroman_get_option( 'nitroman_disable_embeds' ) ) {
			remove_action( 'rest_api_init', 'wp_oembed_register_route' );
			remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
			remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
			remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		}

		// Disable XML-RPC.
		if ( nitroman_get_option( 'nitroman_disable_xmlrpc' ) ) {
			add_filter( 'xmlrpc_enabled', '__return_false' );
			remove_action( 'wp_head', 'rsd_link' );
		}

		// Remove WLW manifest link.
		if ( nitroman_get_option( 'nitroman_remove_wlwmanifest' ) ) {
			remove_action( 'wp_head', 'wlwmanifest_link' );
		}

		// Remove shortlink.
		if ( nitroman_get_option( 'nitroman_remove_shortlink' ) ) {
			remove_action( 'wp_head', 'wp_shortlink_wp_head' );
			remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
		}

		// Remove REST API link from head.
		if ( nitroman_get_option( 'nitroman_remove_rest_api_link' ) ) {
			remove_action( 'wp_head', 'rest_output_link_wp_head' );
			remove_action( 'template_redirect', 'rest_output_link_header', 11 );
		}

		// Heartbeat control.
		$heartbeat = nitroman_get_option( 'nitroman_heartbeat_control' );
		if ( 'disable' === $heartbeat ) {
			wp_deregister_script( 'heartbeat' );
		} elseif ( 'modify' === $heartbeat ) {
			add_filter( 'heartbeat_settings', array( $this, 'modify_heartbeat' ) );
		}
	}

	/**
	 * Disable emoji support in TinyMCE.
	 *
	 * @param array $plugins TinyMCE plugins array.
	 * @return array
	 */
	public function disable_emojis_tinymce( $plugins ) {
		if ( is_array( $plugins ) ) {
			return array_diff( $plugins, array( 'wpemoji' ) );
		}
		return array();
	}

	/**
	 * Remove emoji CDN from DNS prefetch.
	 *
	 * @param array  $urls          URLs list.
	 * @param string $relation_type The relation type.
	 * @return array
	 */
	public function disable_emojis_dns_prefetch( $urls, $relation_type ) {
		if ( 'dns-prefetch' === $relation_type ) {
			$urls = array_filter( $urls, function ( $url ) {
				return false === strpos( $url, 'https://s.w.org/images/core/emoji/' );
			});
		}
		return $urls;
	}

	/**
	 * Modify heartbeat settings.
	 *
	 * @param array $settings Heartbeat settings.
	 * @return array
	 */
	public function modify_heartbeat( $settings ) {
		$frequency = nitroman_get_option( 'nitroman_heartbeat_frequency' );
		$settings['interval'] = Security::sanitize_int( $frequency, 15, 300 );
		return $settings;
	}

	/**
	 * Invalidate cache when content is updated.
	 *
	 * @param int $id Post or comment ID.
	 * @return void
	 */
	public function invalidate_cache_on_update( $id = 0 ) {
		if ( ! nitroman_is_cache_enabled() ) {
			return;
		}

		// Purge the specific post cache if possible.
		if ( $id > 0 ) {
			$url = get_permalink( $id );
			if ( $url ) {
				$cache_file = nitroman_get_cache_path( $url );
				if ( file_exists( $cache_file ) ) {
					unlink( $cache_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				}
				// Also remove the gzipped version.
				$gz_file = $cache_file . '.gz';
				if ( file_exists( $gz_file ) ) {
					unlink( $gz_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				}
			}
		}

		// Also purge the home page cache.
		$home_cache = nitroman_get_cache_path( home_url( '/' ) );
		if ( file_exists( $home_cache ) ) {
			unlink( $home_cache ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		}
	}

	/**
	 * Purge the entire cache directory.
	 *
	 * @return void
	 */
	public function purge_entire_cache() {
		nitroman_purge_cache();
	}

	/**
	 * Run scheduled database cleanup.
	 *
	 * @return void
	 */
	public function run_database_cleanup() {
		if ( ! nitroman_get_option( 'nitroman_db_cleanup_enabled' ) ) {
			return;
		}
		Database_Cleaner::get_instance()->run_cleanup();
	}

	/**
	 * Redirect to settings page after activation.
	 *
	 * @return void
	 */
	public function activation_redirect() {
		if ( get_transient( 'nitroman_activated' ) ) {
			delete_transient( 'nitroman_activated' );

			if ( ! isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				wp_safe_redirect( admin_url( 'admin.php?page=nitroman' ) );
				exit;
			}
		}
	}

	/**
	 * Add settings link to the plugins page.
	 *
	 * @param array $links Plugin action links.
	 * @return array
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=nitroman' ) ),
			esc_html__( 'Settings', 'nitroman' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}
}
