<?php
/**
 * Settings page template for NitroMan.
 *
 * @package NitroMan
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'cache'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="wrap nitroman-wrap">

	<!-- Premium Header -->
	<div class="nm-header">
		<div class="nm-header__inner">
			<div class="nm-header__brand">
				<div class="nm-header__logo">
					<svg width="36" height="36" viewBox="0 0 36 36" fill="none"><circle cx="18" cy="18" r="18" fill="url(#nmg2)"/><path d="M11 25V11L25 25V11" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><defs><linearGradient id="nmg2" x1="0" y1="0" x2="36" y2="36"><stop stop-color="#6366f1"/><stop offset="1" stop-color="#a855f7"/></linearGradient></defs></svg>
				</div>
				<div>
					<h1 class="nm-header__title"><?php esc_html_e( 'Settings', 'nitroman' ); ?></h1>
					<span class="nm-header__badge">NitroMan</span>
				</div>
			</div>
			<p class="nm-header__subtitle"><?php esc_html_e( 'Fine-tune your performance optimizations', 'nitroman' ); ?></p>
		</div>
	</div>

	<!-- Tab Navigation -->
	<nav class="nitroman-tabs">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nitroman-settings&tab=cache' ) ); ?>"
		   class="nitroman-tabs__tab <?php echo 'cache' === $active_tab ? 'nitroman-tabs__tab--active' : ''; ?>">
			<span class="dashicons dashicons-database"></span>
			<?php esc_html_e( 'Cache', 'nitroman' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nitroman-settings&tab=images' ) ); ?>"
		   class="nitroman-tabs__tab <?php echo 'images' === $active_tab ? 'nitroman-tabs__tab--active' : ''; ?>">
			<span class="dashicons dashicons-format-image"></span>
			<?php esc_html_e( 'Images', 'nitroman' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nitroman-settings&tab=scripts' ) ); ?>"
		   class="nitroman-tabs__tab <?php echo 'scripts' === $active_tab ? 'nitroman-tabs__tab--active' : ''; ?>">
			<span class="dashicons dashicons-editor-code"></span>
			<?php esc_html_e( 'Scripts & Styles', 'nitroman' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nitroman-settings&tab=database' ) ); ?>"
		   class="nitroman-tabs__tab <?php echo 'database' === $active_tab ? 'nitroman-tabs__tab--active' : ''; ?>">
			<span class="dashicons dashicons-editor-table"></span>
			<?php esc_html_e( 'Database', 'nitroman' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nitroman-settings&tab=advanced' ) ); ?>"
		   class="nitroman-tabs__tab <?php echo 'advanced' === $active_tab ? 'nitroman-tabs__tab--active' : ''; ?>">
			<span class="dashicons dashicons-admin-tools"></span>
			<?php esc_html_e( 'Advanced', 'nitroman' ); ?>
		</a>
	</nav>

	<!-- Settings Form -->
	<form id="nitroman-settings-form" class="nitroman-settings-form">

		<!-- ==================== CACHE TAB ==================== -->
		<div class="nitroman-tab-content" id="tab-cache" style="<?php echo 'cache' !== $active_tab ? 'display:none;' : ''; ?>">
			<div class="nitroman-card">
				<div class="nitroman-card__header">
					<span class="dashicons dashicons-database nitroman-card__icon"></span>
					<h2 class="nitroman-card__title"><?php esc_html_e( 'Page Cache', 'nitroman' ); ?></h2>
				</div>
				<div class="nitroman-card__body">
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Enable Page Caching', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Generate static HTML files for lightning-fast page delivery.', 'nitroman' ); ?></p>
						</div>
						<label class="nitroman-toggle">
							<input type="checkbox" name="nitroman_cache_enabled" value="1" <?php checked( nitroman_get_option( 'nitroman_cache_enabled' ) ); ?>>
							<span class="nitroman-toggle__slider"></span>
						</label>
					</div>
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Cache Lifetime (TTL)', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Seconds before cached pages expire. Default: 86400 (24 hours).', 'nitroman' ); ?></p>
						</div>
						<input type="number" name="nitroman_cache_ttl" value="<?php echo esc_attr( nitroman_get_option( 'nitroman_cache_ttl' ) ); ?>" min="3600" max="604800" class="nm-input nm-input--sm">
					</div>
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'GZIP Compression', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Store compressed cache files for faster delivery.', 'nitroman' ); ?></p>
						</div>
						<label class="nitroman-toggle">
							<input type="checkbox" name="nitroman_cache_gzip" value="1" <?php checked( nitroman_get_option( 'nitroman_cache_gzip' ) ); ?>>
							<span class="nitroman-toggle__slider"></span>
						</label>
					</div>
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'HTML Minification', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Minify HTML output before caching (10-25% size reduction).', 'nitroman' ); ?></p>
						</div>
						<label class="nitroman-toggle">
							<input type="checkbox" name="nitroman_minify_html" value="1" <?php checked( nitroman_get_option( 'nitroman_minify_html' ) ); ?>>
							<span class="nitroman-toggle__slider"></span>
						</label>
					</div>
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Cache for Mobile', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Create separate cache files for mobile visitors.', 'nitroman' ); ?></p>
						</div>
						<label class="nitroman-toggle">
							<input type="checkbox" name="nitroman_cache_mobile" value="1" <?php checked( nitroman_get_option( 'nitroman_cache_mobile' ) ); ?>>
							<span class="nitroman-toggle__slider"></span>
						</label>
					</div>
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Cache for Logged-in Users', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Serve cached pages to logged-in users. Not recommended for dynamic sites.', 'nitroman' ); ?></p>
						</div>
						<label class="nitroman-toggle">
							<input type="checkbox" name="nitroman_cache_logged_in" value="1" <?php checked( nitroman_get_option( 'nitroman_cache_logged_in' ) ); ?>>
							<span class="nitroman-toggle__slider"></span>
						</label>
					</div>
				</div>
			</div>

			<div class="nitroman-card">
				<div class="nitroman-card__header">
					<span class="dashicons dashicons-dismiss nitroman-card__icon"></span>
					<h2 class="nitroman-card__title"><?php esc_html_e( 'Exclusions', 'nitroman' ); ?></h2>
				</div>
				<div class="nitroman-card__body">
					<div class="nm-setting-row nm-setting-row--block">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Exclude URLs from Cache', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'URLs containing these strings will not be cached (one per line).', 'nitroman' ); ?></p>
						</div>
						<textarea name="nitroman_cache_exclude_urls" rows="4" class="nm-textarea"><?php echo esc_textarea( nitroman_get_option( 'nitroman_cache_exclude_urls' ) ); ?></textarea>
					</div>
				</div>
			</div>
		</div>

		<!-- ==================== IMAGES TAB ==================== -->
		<div class="nitroman-tab-content" id="tab-images" style="<?php echo 'images' !== $active_tab ? 'display:none;' : ''; ?>">
			<div class="nitroman-card">
				<div class="nitroman-card__header">
					<span class="dashicons dashicons-format-image nitroman-card__icon" style="color: var(--nm-emerald);"></span>
					<h2 class="nitroman-card__title"><?php esc_html_e( 'Image Optimization', 'nitroman' ); ?></h2>
				</div>
				<div class="nitroman-card__body">
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Lazy Loading', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Defer off-screen images until they scroll into view. Reduces initial page weight.', 'nitroman' ); ?></p>
						</div>
						<label class="nitroman-toggle">
							<input type="checkbox" name="nitroman_lazy_load" value="1" <?php checked( nitroman_get_option( 'nitroman_lazy_load' ) ); ?>>
							<span class="nitroman-toggle__slider"></span>
						</label>
					</div>
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Add Missing Dimensions', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Automatically inject width/height attributes to prevent Cumulative Layout Shift (CLS).', 'nitroman' ); ?></p>
						</div>
						<label class="nitroman-toggle">
							<input type="checkbox" name="nitroman_add_dimensions" value="1" <?php checked( nitroman_get_option( 'nitroman_add_dimensions' ) ); ?>>
							<span class="nitroman-toggle__slider"></span>
						</label>
					</div>
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'WebP Support', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Detect and serve WebP versions of images when available.', 'nitroman' ); ?></p>
						</div>
						<label class="nitroman-toggle">
							<input type="checkbox" name="nitroman_webp_support" value="1" <?php checked( nitroman_get_option( 'nitroman_webp_support' ) ); ?>>
							<span class="nitroman-toggle__slider"></span>
						</label>
					</div>
				</div>
			</div>
		</div>

		<!-- ==================== SCRIPTS TAB ==================== -->
		<div class="nitroman-tab-content" id="tab-scripts" style="<?php echo 'scripts' !== $active_tab ? 'display:none;' : ''; ?>">
			<div class="nitroman-card">
				<div class="nitroman-card__header">
					<span class="dashicons dashicons-editor-code nitroman-card__icon" style="color: var(--nm-violet);"></span>
					<h2 class="nitroman-card__title"><?php esc_html_e( 'JavaScript', 'nitroman' ); ?></h2>
				</div>
				<div class="nitroman-card__body">
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Defer JavaScript', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Add defer attribute to JS files. jQuery and core scripts are safely excluded.', 'nitroman' ); ?></p>
						</div>
						<label class="nitroman-toggle">
							<input type="checkbox" name="nitroman_defer_js" value="1" <?php checked( nitroman_get_option( 'nitroman_defer_js' ) ); ?>>
							<span class="nitroman-toggle__slider"></span>
						</label>
					</div>
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Minify Inline JS', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Optimize inline JavaScript blocks by removing whitespace and comments.', 'nitroman' ); ?></p>
						</div>
						<label class="nitroman-toggle">
							<input type="checkbox" name="nitroman_minify_js" value="1" <?php checked( nitroman_get_option( 'nitroman_minify_js' ) ); ?>>
							<span class="nitroman-toggle__slider"></span>
						</label>
					</div>
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Remove Query Strings', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Strip ?ver= from static resources for better CDN and proxy caching.', 'nitroman' ); ?></p>
						</div>
						<label class="nitroman-toggle">
							<input type="checkbox" name="nitroman_remove_query_strings" value="1" <?php checked( nitroman_get_option( 'nitroman_remove_query_strings' ) ); ?>>
							<span class="nitroman-toggle__slider"></span>
						</label>
					</div>
				</div>
			</div>

			<div class="nitroman-card">
				<div class="nitroman-card__header">
					<span class="dashicons dashicons-art nitroman-card__icon" style="color: var(--nm-amber);"></span>
					<h2 class="nitroman-card__title"><?php esc_html_e( 'CSS', 'nitroman' ); ?></h2>
				</div>
				<div class="nitroman-card__body">
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Optimize CSS Delivery', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Defer non-critical stylesheets for faster first paint.', 'nitroman' ); ?></p>
						</div>
						<label class="nitroman-toggle">
							<input type="checkbox" name="nitroman_minify_css" value="1" <?php checked( nitroman_get_option( 'nitroman_minify_css' ) ); ?>>
							<span class="nitroman-toggle__slider"></span>
						</label>
					</div>
				</div>
			</div>

			<div class="nitroman-card">
				<div class="nitroman-card__header">
					<span class="dashicons dashicons-dismiss nitroman-card__icon"></span>
					<h2 class="nitroman-card__title"><?php esc_html_e( 'Exclusions', 'nitroman' ); ?></h2>
				</div>
				<div class="nitroman-card__body">
					<div class="nm-setting-row nm-setting-row--block">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Script Exclusions', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Scripts containing these strings will not be deferred (one per line).', 'nitroman' ); ?></p>
						</div>
						<textarea name="nitroman_delay_js_exclusions" rows="4" class="nm-textarea"><?php echo esc_textarea( nitroman_get_option( 'nitroman_delay_js_exclusions' ) ); ?></textarea>
					</div>
				</div>
			</div>
		</div>

		<!-- ==================== DATABASE TAB ==================== -->
		<div class="nitroman-tab-content" id="tab-database" style="<?php echo 'database' !== $active_tab ? 'display:none;' : ''; ?>">
			<div class="nitroman-card">
				<div class="nitroman-card__header">
					<span class="dashicons dashicons-editor-table nitroman-card__icon" style="color: var(--nm-amber);"></span>
					<h2 class="nitroman-card__title"><?php esc_html_e( 'Database Cleanup', 'nitroman' ); ?></h2>
				</div>
				<div class="nitroman-card__body">
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Scheduled Cleanup', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Automatic daily cleanup via WP-Cron.', 'nitroman' ); ?></p>
						</div>
						<label class="nitroman-toggle">
							<input type="checkbox" name="nitroman_db_cleanup_enabled" value="1" <?php checked( nitroman_get_option( 'nitroman_db_cleanup_enabled' ) ); ?>>
							<span class="nitroman-toggle__slider"></span>
						</label>
					</div>
					<div class="nm-divider"></div>
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Clean Revisions', 'nitroman' ); ?></h3>
						</div>
						<label class="nitroman-toggle">
							<input type="checkbox" name="nitroman_clean_revisions" value="1" <?php checked( nitroman_get_option( 'nitroman_clean_revisions' ) ); ?>>
							<span class="nitroman-toggle__slider"></span>
						</label>
					</div>
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Revisions to Keep', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Most recent revisions preserved per post (0 = delete all).', 'nitroman' ); ?></p>
						</div>
						<input type="number" name="nitroman_revisions_to_keep" value="<?php echo esc_attr( nitroman_get_option( 'nitroman_revisions_to_keep' ) ); ?>" min="0" max="50" class="nm-input nm-input--xs">
					</div>
					<div class="nm-setting-row">
						<div class="nm-setting-row__info"><h3 class="nm-setting-row__title"><?php esc_html_e( 'Clean Auto-Drafts', 'nitroman' ); ?></h3></div>
						<label class="nitroman-toggle"><input type="checkbox" name="nitroman_clean_auto_drafts" value="1" <?php checked( nitroman_get_option( 'nitroman_clean_auto_drafts' ) ); ?>><span class="nitroman-toggle__slider"></span></label>
					</div>
					<div class="nm-setting-row">
						<div class="nm-setting-row__info"><h3 class="nm-setting-row__title"><?php esc_html_e( 'Clean Trashed Posts', 'nitroman' ); ?></h3></div>
						<label class="nitroman-toggle"><input type="checkbox" name="nitroman_clean_trashed" value="1" <?php checked( nitroman_get_option( 'nitroman_clean_trashed' ) ); ?>><span class="nitroman-toggle__slider"></span></label>
					</div>
					<div class="nm-setting-row">
						<div class="nm-setting-row__info"><h3 class="nm-setting-row__title"><?php esc_html_e( 'Clean Spam Comments', 'nitroman' ); ?></h3></div>
						<label class="nitroman-toggle"><input type="checkbox" name="nitroman_clean_spam" value="1" <?php checked( nitroman_get_option( 'nitroman_clean_spam' ) ); ?>><span class="nitroman-toggle__slider"></span></label>
					</div>
					<div class="nm-setting-row">
						<div class="nm-setting-row__info"><h3 class="nm-setting-row__title"><?php esc_html_e( 'Clean Expired Transients', 'nitroman' ); ?></h3></div>
						<label class="nitroman-toggle"><input type="checkbox" name="nitroman_clean_transients" value="1" <?php checked( nitroman_get_option( 'nitroman_clean_transients' ) ); ?>><span class="nitroman-toggle__slider"></span></label>
					</div>
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Clean Orphaned Meta', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Remove metadata entries belonging to deleted posts.', 'nitroman' ); ?></p>
						</div>
						<label class="nitroman-toggle"><input type="checkbox" name="nitroman_clean_orphan_meta" value="1" <?php checked( nitroman_get_option( 'nitroman_clean_orphan_meta' ) ); ?>><span class="nitroman-toggle__slider"></span></label>
					</div>
				</div>
			</div>
		</div>

		<!-- ==================== ADVANCED TAB ==================== -->
		<div class="nitroman-tab-content" id="tab-advanced" style="<?php echo 'advanced' !== $active_tab ? 'display:none;' : ''; ?>">

			<div class="nitroman-card">
				<div class="nitroman-card__header">
					<span class="dashicons dashicons-networking nitroman-card__icon" style="color: var(--nm-emerald);"></span>
					<h2 class="nitroman-card__title"><?php esc_html_e( 'Resource Hints', 'nitroman' ); ?></h2>
				</div>
				<div class="nitroman-card__body">
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'DNS Prefetch', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Resolve third-party domains early for faster resource loading.', 'nitroman' ); ?></p>
						</div>
						<label class="nitroman-toggle"><input type="checkbox" name="nitroman_dns_prefetch" value="1" <?php checked( nitroman_get_option( 'nitroman_dns_prefetch' ) ); ?>><span class="nitroman-toggle__slider"></span></label>
					</div>
					<div class="nm-setting-row nm-setting-row--block">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Prefetch URLs', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Additional domains to prefetch (one per line).', 'nitroman' ); ?></p>
						</div>
						<textarea name="nitroman_dns_prefetch_urls" rows="3" class="nm-textarea"><?php echo esc_textarea( nitroman_get_option( 'nitroman_dns_prefetch_urls' ) ); ?></textarea>
					</div>
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Preconnect', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Preconnect to Google Fonts and other services.', 'nitroman' ); ?></p>
						</div>
						<label class="nitroman-toggle"><input type="checkbox" name="nitroman_preconnect" value="1" <?php checked( nitroman_get_option( 'nitroman_preconnect' ) ); ?>><span class="nitroman-toggle__slider"></span></label>
					</div>
					<div class="nm-setting-row nm-setting-row--block">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Preload Fonts', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Font file URLs to preload (one per line).', 'nitroman' ); ?></p>
						</div>
						<textarea name="nitroman_preload_fonts" rows="3" class="nm-textarea"><?php echo esc_textarea( nitroman_get_option( 'nitroman_preload_fonts' ) ); ?></textarea>
					</div>
				</div>
			</div>

			<div class="nitroman-card">
				<div class="nitroman-card__header">
					<span class="dashicons dashicons-admin-tools nitroman-card__icon" style="color: var(--nm-rose);"></span>
					<h2 class="nitroman-card__title"><?php esc_html_e( 'WordPress Tweaks', 'nitroman' ); ?></h2>
				</div>
				<div class="nitroman-card__body">
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Disable Emojis', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Remove emoji scripts and styles (~50KB savings).', 'nitroman' ); ?></p>
						</div>
						<label class="nitroman-toggle"><input type="checkbox" name="nitroman_disable_emojis" value="1" <?php checked( nitroman_get_option( 'nitroman_disable_emojis' ) ); ?>><span class="nitroman-toggle__slider"></span></label>
					</div>
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Disable Embeds', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Remove oEmbed discovery links and JavaScript.', 'nitroman' ); ?></p>
						</div>
						<label class="nitroman-toggle"><input type="checkbox" name="nitroman_disable_embeds" value="1" <?php checked( nitroman_get_option( 'nitroman_disable_embeds' ) ); ?>><span class="nitroman-toggle__slider"></span></label>
					</div>
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Disable XML-RPC', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Block XML-RPC interface. Recommended for security.', 'nitroman' ); ?></p>
						</div>
						<label class="nitroman-toggle"><input type="checkbox" name="nitroman_disable_xmlrpc" value="1" <?php checked( nitroman_get_option( 'nitroman_disable_xmlrpc' ) ); ?>><span class="nitroman-toggle__slider"></span></label>
					</div>
					<div class="nm-divider"></div>
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Heartbeat Control', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Control the Heartbeat API to reduce server load.', 'nitroman' ); ?></p>
						</div>
						<select name="nitroman_heartbeat_control" class="nm-select">
							<option value="default" <?php selected( nitroman_get_option( 'nitroman_heartbeat_control' ), 'default' ); ?>><?php esc_html_e( 'Default', 'nitroman' ); ?></option>
							<option value="modify" <?php selected( nitroman_get_option( 'nitroman_heartbeat_control' ), 'modify' ); ?>><?php esc_html_e( 'Slow Down', 'nitroman' ); ?></option>
							<option value="disable" <?php selected( nitroman_get_option( 'nitroman_heartbeat_control' ), 'disable' ); ?>><?php esc_html_e( 'Disable', 'nitroman' ); ?></option>
						</select>
					</div>
					<div class="nm-setting-row">
						<div class="nm-setting-row__info">
							<h3 class="nm-setting-row__title"><?php esc_html_e( 'Heartbeat Frequency', 'nitroman' ); ?></h3>
							<p class="nm-setting-row__desc"><?php esc_html_e( 'Seconds between heartbeat pulses (when set to Slow Down).', 'nitroman' ); ?></p>
						</div>
						<div class="nm-input-suffix">
							<input type="number" name="nitroman_heartbeat_frequency" value="<?php echo esc_attr( nitroman_get_option( 'nitroman_heartbeat_frequency' ) ); ?>" min="15" max="300" class="nm-input nm-input--xs">
							<span class="nm-input-suffix__text"><?php esc_html_e( 'sec', 'nitroman' ); ?></span>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Sticky Save Bar -->
		<div class="nitroman-save-bar">
			<button type="submit" class="nm-btn nm-btn--primary nm-btn--lg" id="nitroman-save-settings">
				<span class="dashicons dashicons-saved"></span>
				<?php esc_html_e( 'Save All Settings', 'nitroman' ); ?>
			</button>
		</div>

	</form>

	<!-- Toast -->
	<div id="nitroman-toast" class="nm-toast" style="display:none;"></div>
</div>
