<?php
/**
 * Admin dashboard manager for NitroMan.
 *
 * @package NitroMan
 */

namespace NitroMan;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin_Manager
 *
 * Handles the admin menu, settings pages, AJAX handlers,
 * and admin-specific assets.
 */
class Admin_Manager {

	/**
	 * Singleton instance.
	 *
	 * @var Admin_Manager|null
	 */
	private static $instance = null;

	/**
	 * Admin page hook suffix.
	 *
	 * @var string
	 */
	private $page_hook = '';

	/**
	 * Settings page hook suffix.
	 *
	 * @var string
	 */
	private $settings_hook = '';

	/**
	 * Get the singleton instance.
	 *
	 * @return Admin_Manager
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
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_nitroman_purge_cache', array( $this, 'ajax_purge_cache' ) );
		add_action( 'wp_ajax_nitroman_run_db_cleanup', array( $this, 'ajax_run_db_cleanup' ) );
		add_action( 'wp_ajax_nitroman_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_nitroman_toggle_htaccess', array( $this, 'ajax_toggle_htaccess' ) );

		// Admin bar.
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 100 );

		// Admin notices.
		add_action( 'admin_notices', array( $this, 'display_notices' ) );
	}

	/**
	 * Register admin menu pages.
	 *
	 * @return void
	 */
	public function register_menus() {
		$this->page_hook = add_menu_page(
			__( 'NitroMan', 'nitroman' ),
			__( 'NitroMan', 'nitroman' ),
			Security::REQUIRED_CAPABILITY,
			'nitroman',
			array( $this, 'render_dashboard' ),
			'dashicons-performance',
			80
		);

		$this->settings_hook = add_submenu_page(
			'nitroman',
			__( 'NitroMan Settings', 'nitroman' ),
			__( 'Settings', 'nitroman' ),
			Security::REQUIRED_CAPABILITY,
			'nitroman-settings',
			array( $this, 'render_settings' )
		);
	}

	/**
	 * Enqueue admin CSS and JS.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		// Only load on our pages.
		if ( $hook !== $this->page_hook && $hook !== $this->settings_hook ) {
			return;
		}

		wp_enqueue_style(
			'nitroman-admin',
			NITROMAN_URL . 'admin/css/admin.css',
			array(),
			NITROMAN_VERSION
		);

		wp_enqueue_script(
			'nitroman-admin',
			NITROMAN_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			NITROMAN_VERSION,
			true
		);

		wp_localize_script( 'nitroman-admin', 'nitromanAdmin', array(
			'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
			'purgeNonce'         => wp_create_nonce( Security::NONCE_PREFIX . 'purge_cache' ),
			'dbCleanupNonce'     => wp_create_nonce( Security::NONCE_PREFIX . 'db_cleanup' ),
			'saveSettingsNonce'  => wp_create_nonce( Security::NONCE_PREFIX . 'save_settings' ),
			'htaccessNonce'      => wp_create_nonce( Security::NONCE_PREFIX . 'toggle_htaccess' ),
			'strings'            => array(
				'purging'        => __( 'Purging cache...', 'nitroman' ),
				'purged'         => __( 'Cache purged successfully!', 'nitroman' ),
				'cleaning'       => __( 'Running cleanup...', 'nitroman' ),
				'cleaned'        => __( 'Database cleaned successfully!', 'nitroman' ),
				'saving'         => __( 'Saving settings...', 'nitroman' ),
				'saved'          => __( 'Settings saved!', 'nitroman' ),
				'error'          => __( 'An error occurred. Please try again.', 'nitroman' ),
				'htaccessOn'     => __( 'Server optimizations applied!', 'nitroman' ),
				'htaccessOff'    => __( 'Server optimizations removed.', 'nitroman' ),
			),
		) );
	}

	/**
	 * Render the dashboard page.
	 *
	 * @return void
	 */
	public function render_dashboard() {
		Security::check_permissions_or_die();
		include NITROMAN_PATH . 'admin/views/dashboard.php';
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings() {
		Security::check_permissions_or_die();
		include NITROMAN_PATH . 'admin/views/settings.php';
	}

	/**
	 * AJAX handler: Purge cache.
	 *
	 * @return void
	 */
	public function ajax_purge_cache() {
		check_ajax_referer( Security::NONCE_PREFIX . 'purge_cache', 'nonce' );

		if ( ! Security::current_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'nitroman' ) ) );
		}

		nitroman_purge_cache();

		wp_send_json_success( array(
			'message'    => __( 'Cache purged successfully.', 'nitroman' ),
			'cache_size' => nitroman_format_bytes( nitroman_get_cache_size() ),
			'page_count' => nitroman_get_cache_count(),
		) );
	}

	/**
	 * AJAX handler: Run database cleanup.
	 *
	 * @return void
	 */
	public function ajax_run_db_cleanup() {
		check_ajax_referer( Security::NONCE_PREFIX . 'db_cleanup', 'nonce' );

		if ( ! Security::current_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'nitroman' ) ) );
		}

		$cleaner = Database_Cleaner::get_instance();
		$results = $cleaner->run_cleanup();
		$stats   = $cleaner->get_stats();

		$total = 0;
		foreach ( $results as $count ) {
			$total += (int) $count;
		}

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: %d: number of items cleaned */
				__( 'Cleaned %d items from the database.', 'nitroman' ),
				$total
			),
			'details' => $results,
			'stats'   => $stats,
		) );
	}

	/**
	 * AJAX handler: Save settings.
	 *
	 * @return void
	 */
	public function ajax_save_settings() {
		check_ajax_referer( Security::NONCE_PREFIX . 'save_settings', 'nonce' );

		if ( ! Security::current_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'nitroman' ) ) );
		}

		$defaults = nitroman_get_default_options();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce already checked above.
		foreach ( $defaults as $key => $default ) {
			if ( isset( $_POST[ $key ] ) ) {
				$value = wp_unslash( $_POST[ $key ] );

				// Sanitize based on default type.
				if ( is_bool( $default ) ) {
					$value = Security::sanitize_bool( $value );
				} elseif ( is_int( $default ) ) {
					$value = Security::sanitize_int( $value );
				} elseif ( false !== strpos( $key, '_urls' ) || false !== strpos( $key, '_fonts' ) ) {
					$value = nitroman_sanitize_textarea_urls( $value );
				} elseif ( false !== strpos( $key, '_exclusions' ) || false !== strpos( $key, '_exclude' ) ) {
					$value = nitroman_sanitize_textarea_text( $value );
				} elseif ( 'nitroman_heartbeat_control' === $key ) {
					$value = Security::sanitize_select( $value, array( 'default', 'modify', 'disable' ), 'default' );
				} else {
					$value = sanitize_text_field( $value );
				}

				update_option( $key, $value );
			} else {
				// Checkboxes that weren't posted are unchecked (false).
				if ( is_bool( $default ) ) {
					update_option( $key, false );
				}
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		wp_send_json_success( array(
			'message' => __( 'Settings saved successfully.', 'nitroman' ),
		) );
	}

	/**
	 * Add NitroMan to the admin bar for quick cache purge.
	 *
	 * @param \WP_Admin_Bar $admin_bar The admin bar object.
	 * @return void
	 */
	public function admin_bar_menu( $admin_bar ) {
		if ( ! Security::current_user_can_manage() ) {
			return;
		}

		$admin_bar->add_node( array(
			'id'    => 'nitroman',
			'title' => '<span class="ab-icon dashicons dashicons-performance"></span> NitroMan',
			'href'  => admin_url( 'admin.php?page=nitroman' ),
		) );

		$admin_bar->add_node( array(
			'id'     => 'nitroman-purge',
			'parent' => 'nitroman',
			'title'  => __( 'Purge Cache', 'nitroman' ),
			'href'   => '#',
			'meta'   => array( 'class' => 'nitroman-purge-link' ),
		) );

		$admin_bar->add_node( array(
			'id'     => 'nitroman-settings',
			'parent' => 'nitroman',
			'title'  => __( 'Settings', 'nitroman' ),
			'href'   => admin_url( 'admin.php?page=nitroman-settings' ),
		) );
	}

	/**
	 * Display admin notices.
	 *
	 * @return void
	 */
	public function display_notices() {
		// Cache directory writable check.
		if ( nitroman_is_cache_enabled() ) {
			// Try to create the cache directory if it doesn't exist.
			if ( ! is_dir( NITROMAN_CACHE_DIR ) ) {
				wp_mkdir_p( NITROMAN_CACHE_DIR );
			}

			// Only warn if directory still isn't writable after creation attempt.
			if ( ! wp_is_writable( NITROMAN_CACHE_DIR ) ) {
				printf(
					'<div class="notice notice-warning"><p>%s</p></div>',
					esc_html__( 'NitroMan: The cache directory is not writable. Please check file permissions.', 'nitroman' )
				);
			}
		}
	}

	/**
	 * AJAX handler: Toggle .htaccess optimizations.
	 *
	 * @return void
	 */
	public function ajax_toggle_htaccess() {
		check_ajax_referer( Security::NONCE_PREFIX . 'toggle_htaccess', 'nonce' );

		if ( ! Security::current_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'nitroman' ) ) );
		}

		$htaccess = Htaccess_Manager::get_instance();
		$enable   = isset( $_POST['enable'] ) && Security::sanitize_bool( $_POST['enable'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( $enable ) {
			$result = $htaccess->apply_optimizations();
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}
			update_option( 'nitroman_htaccess_optimization', true );
			wp_send_json_success( array( 'message' => __( 'Server optimizations applied to .htaccess!', 'nitroman' ), 'active' => true ) );
		} else {
			$htaccess->remove_optimizations();
			update_option( 'nitroman_htaccess_optimization', false );
			wp_send_json_success( array( 'message' => __( 'Server optimizations removed from .htaccess.', 'nitroman' ), 'active' => false ) );
		}
	}
}
