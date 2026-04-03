<?php
/**
 * Dashboard page template for NitroMan.
 *
 * @package NitroMan
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cache_size    = nitroman_format_bytes( nitroman_get_cache_size() );
$cache_count   = nitroman_get_cache_count();
$cache_on      = nitroman_is_cache_enabled();
$db_cleaner    = NitroMan\Database_Cleaner::get_instance();
$db_stats      = $db_cleaner->get_stats();
$htaccess_mgr  = NitroMan\Htaccess_Manager::get_instance();
$htaccess_on   = nitroman_get_option( 'nitroman_htaccess_optimization' );
$htaccess_can  = $htaccess_mgr->is_writable();

// Count active optimizations for the score gauge.
$active_count = 0;
$total_checks = 10;
if ( $cache_on ) ++$active_count;
if ( $htaccess_on ) ++$active_count;
if ( nitroman_get_option( 'nitroman_lazy_load' ) ) ++$active_count;
if ( nitroman_get_option( 'nitroman_defer_js' ) ) ++$active_count;
if ( nitroman_get_option( 'nitroman_minify_html' ) ) ++$active_count;
if ( nitroman_get_option( 'nitroman_minify_css' ) ) ++$active_count;
if ( nitroman_get_option( 'nitroman_disable_emojis' ) ) ++$active_count;
if ( nitroman_get_option( 'nitroman_remove_query_strings' ) ) ++$active_count;
if ( nitroman_get_option( 'nitroman_dns_prefetch' ) ) ++$active_count;
if ( nitroman_get_option( 'nitroman_disable_xmlrpc' ) ) ++$active_count;
$score = round( ( $active_count / $total_checks ) * 100 );
?>
<div class="wrap nitroman-wrap">

	<!-- Premium Header -->
	<div class="nm-header">
		<div class="nm-header__inner">
			<div class="nm-header__brand">
				<div class="nm-header__logo">
					<svg width="36" height="36" viewBox="0 0 36 36" fill="none"><circle cx="18" cy="18" r="18" fill="url(#nmg)"/><path d="M11 25V11L25 25V11" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><defs><linearGradient id="nmg" x1="0" y1="0" x2="36" y2="36"><stop stop-color="#6366f1"/><stop offset="1" stop-color="#a855f7"/></linearGradient></defs></svg>
				</div>
				<div>
					<h1 class="nm-header__title">NitroMan</h1>
					<span class="nm-header__badge">v<?php echo esc_html( NITROMAN_VERSION ); ?></span>
				</div>
			</div>
			<p class="nm-header__subtitle"><?php esc_html_e( 'Speed Optimization Engine', 'nitroman' ); ?></p>
		</div>
	</div>

	<!-- Performance Score + Status Row -->
	<div class="nm-score-row">

		<!-- Score Gauge -->
		<div class="nm-gauge-card">
			<div class="nm-gauge" data-score="<?php echo esc_attr( $score ); ?>">
				<svg viewBox="0 0 120 120" class="nm-gauge__svg">
					<circle cx="60" cy="60" r="52" class="nm-gauge__bg"/>
					<circle cx="60" cy="60" r="52" class="nm-gauge__fill" style="--score: <?php echo esc_attr( $score ); ?>"/>
				</svg>
				<div class="nm-gauge__value">
					<span class="nm-gauge__number"><?php echo esc_html( $score ); ?></span>
					<span class="nm-gauge__label"><?php esc_html_e( 'Score', 'nitroman' ); ?></span>
				</div>
			</div>
			<p class="nm-gauge__desc">
				<strong><?php echo esc_html( $active_count ); ?>/<?php echo esc_html( $total_checks ); ?></strong> <?php esc_html_e( 'optimizations active', 'nitroman' ); ?>
			</p>
		</div>

		<!-- Status Chips -->
		<div class="nm-status-chips">
			<div class="nm-chip <?php echo $cache_on ? 'nm-chip--on' : 'nm-chip--off'; ?>">
				<span class="nm-chip__dot"></span>
				<?php esc_html_e( 'Page Cache', 'nitroman' ); ?>
			</div>
			<div class="nm-chip <?php echo $htaccess_on ? 'nm-chip--on' : 'nm-chip--off'; ?>">
				<span class="nm-chip__dot"></span>
				<?php esc_html_e( 'Server GZIP', 'nitroman' ); ?>
			</div>
			<div class="nm-chip <?php echo nitroman_get_option( 'nitroman_lazy_load' ) ? 'nm-chip--on' : 'nm-chip--off'; ?>">
				<span class="nm-chip__dot"></span>
				<?php esc_html_e( 'Lazy Images', 'nitroman' ); ?>
			</div>
			<div class="nm-chip <?php echo nitroman_get_option( 'nitroman_defer_js' ) ? 'nm-chip--on' : 'nm-chip--off'; ?>">
				<span class="nm-chip__dot"></span>
				<?php esc_html_e( 'JS Defer', 'nitroman' ); ?>
			</div>
			<div class="nm-chip <?php echo nitroman_get_option( 'nitroman_minify_html' ) ? 'nm-chip--on' : 'nm-chip--off'; ?>">
				<span class="nm-chip__dot"></span>
				<?php esc_html_e( 'HTML Minify', 'nitroman' ); ?>
			</div>
			<div class="nm-chip <?php echo nitroman_get_option( 'nitroman_disable_emojis' ) ? 'nm-chip--on' : 'nm-chip--off'; ?>">
				<span class="nm-chip__dot"></span>
				<?php esc_html_e( 'Emoji Cleanup', 'nitroman' ); ?>
			</div>
		</div>
	</div>

	<!-- Main Grid -->
	<div class="nm-grid">

		<!-- Cache Card -->
		<div class="nm-card nm-card--gradient-indigo">
			<div class="nm-card__header">
				<div class="nm-card__icon-wrap"><span class="dashicons dashicons-performance"></span></div>
				<h2 class="nm-card__title"><?php esc_html_e( 'Page Cache', 'nitroman' ); ?></h2>
			</div>
			<div class="nm-card__body">
				<div class="nm-stats-row">
					<div class="nm-stat-block">
						<span class="nm-stat-block__num" id="nitroman-cache-count"><?php echo esc_html( $cache_count ); ?></span>
						<span class="nm-stat-block__label"><?php esc_html_e( 'Pages', 'nitroman' ); ?></span>
					</div>
					<div class="nm-stat-block">
						<span class="nm-stat-block__num" id="nitroman-cache-size"><?php echo esc_html( $cache_size ); ?></span>
						<span class="nm-stat-block__label"><?php esc_html_e( 'Size', 'nitroman' ); ?></span>
					</div>
				</div>
			</div>
			<div class="nm-card__actions">
				<button type="button" class="nm-btn nm-btn--primary" id="nitroman-purge-cache">
					<span class="dashicons dashicons-trash"></span>
					<?php esc_html_e( 'Purge Cache', 'nitroman' ); ?>
				</button>
			</div>
		</div>

		<!-- Server Optimization Card -->
		<div class="nm-card nm-card--gradient-emerald">
			<div class="nm-card__header">
				<div class="nm-card__icon-wrap nm-card__icon-wrap--emerald"><span class="dashicons dashicons-shield"></span></div>
				<h2 class="nm-card__title"><?php esc_html_e( 'Server Optimization', 'nitroman' ); ?></h2>
			</div>
			<div class="nm-card__body">
				<p class="nm-card__desc"><?php esc_html_e( 'GZIP compression, browser caching, security headers, and Keep-Alive via .htaccess. This alone can boost speed by 15-20%.', 'nitroman' ); ?></p>
				<div class="nm-chip nm-chip--lg <?php echo $htaccess_on ? 'nm-chip--on' : 'nm-chip--off'; ?>" id="nm-htaccess-status">
					<span class="nm-chip__dot"></span>
					<?php echo $htaccess_on ? esc_html__( 'Active', 'nitroman' ) : esc_html__( 'Inactive', 'nitroman' ); ?>
				</div>
			</div>
			<div class="nm-card__actions">
				<?php if ( $htaccess_can ) : ?>
					<button type="button" class="nm-btn <?php echo $htaccess_on ? 'nm-btn--danger' : 'nm-btn--primary'; ?>" id="nitroman-toggle-htaccess" data-active="<?php echo $htaccess_on ? '1' : '0'; ?>">
						<span class="dashicons <?php echo $htaccess_on ? 'dashicons-no' : 'dashicons-yes'; ?>"></span>
						<?php echo $htaccess_on ? esc_html__( 'Deactivate', 'nitroman' ) : esc_html__( 'Activate', 'nitroman' ); ?>
					</button>
				<?php else : ?>
					<p class="nm-card__warn"><?php esc_html_e( '.htaccess is not writable.', 'nitroman' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<!-- Database Card -->
		<div class="nm-card nm-card--gradient-amber">
			<div class="nm-card__header">
				<div class="nm-card__icon-wrap nm-card__icon-wrap--amber"><span class="dashicons dashicons-editor-table"></span></div>
				<h2 class="nm-card__title"><?php esc_html_e( 'Database', 'nitroman' ); ?></h2>
			</div>
			<div class="nm-card__body">
				<ul class="nm-db-list">
					<li><span><?php esc_html_e( 'Revisions', 'nitroman' ); ?></span><span class="nm-db-list__badge" id="nitroman-stat-revisions"><?php echo esc_html( $db_stats['revisions'] ); ?></span></li>
					<li><span><?php esc_html_e( 'Auto-Drafts', 'nitroman' ); ?></span><span class="nm-db-list__badge" id="nitroman-stat-auto-drafts"><?php echo esc_html( $db_stats['auto_drafts'] ); ?></span></li>
					<li><span><?php esc_html_e( 'Trashed', 'nitroman' ); ?></span><span class="nm-db-list__badge" id="nitroman-stat-trashed"><?php echo esc_html( $db_stats['trashed'] ); ?></span></li>
					<li><span><?php esc_html_e( 'Spam', 'nitroman' ); ?></span><span class="nm-db-list__badge" id="nitroman-stat-spam"><?php echo esc_html( $db_stats['spam'] ); ?></span></li>
					<li><span><?php esc_html_e( 'Transients', 'nitroman' ); ?></span><span class="nm-db-list__badge" id="nitroman-stat-transients"><?php echo esc_html( $db_stats['transients'] ); ?></span></li>
					<li><span><?php esc_html_e( 'Orphaned', 'nitroman' ); ?></span><span class="nm-db-list__badge" id="nitroman-stat-orphan-meta"><?php echo esc_html( $db_stats['orphan_meta'] ); ?></span></li>
				</ul>
			</div>
			<div class="nm-card__actions">
				<button type="button" class="nm-btn nm-btn--primary" id="nitroman-run-cleanup">
					<span class="dashicons dashicons-database-remove"></span>
					<?php esc_html_e( 'Clean Now', 'nitroman' ); ?>
				</button>
			</div>
		</div>

		<!-- Quick Navigation Card -->
		<div class="nm-card">
			<div class="nm-card__header">
				<div class="nm-card__icon-wrap nm-card__icon-wrap--slate"><span class="dashicons dashicons-admin-generic"></span></div>
				<h2 class="nm-card__title"><?php esc_html_e( 'Settings', 'nitroman' ); ?></h2>
			</div>
			<div class="nm-card__body nm-card__body--links">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=nitroman-settings&tab=cache' ) ); ?>" class="nm-nav-link">
					<span class="dashicons dashicons-database"></span><?php esc_html_e( 'Cache', 'nitroman' ); ?><span class="nm-nav-link__arrow">→</span>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=nitroman-settings&tab=images' ) ); ?>" class="nm-nav-link">
					<span class="dashicons dashicons-format-image"></span><?php esc_html_e( 'Images', 'nitroman' ); ?><span class="nm-nav-link__arrow">→</span>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=nitroman-settings&tab=scripts' ) ); ?>" class="nm-nav-link">
					<span class="dashicons dashicons-editor-code"></span><?php esc_html_e( 'Scripts & Styles', 'nitroman' ); ?><span class="nm-nav-link__arrow">→</span>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=nitroman-settings&tab=database' ) ); ?>" class="nm-nav-link">
					<span class="dashicons dashicons-editor-table"></span><?php esc_html_e( 'Database', 'nitroman' ); ?><span class="nm-nav-link__arrow">→</span>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=nitroman-settings&tab=advanced' ) ); ?>" class="nm-nav-link">
					<span class="dashicons dashicons-admin-tools"></span><?php esc_html_e( 'Advanced', 'nitroman' ); ?><span class="nm-nav-link__arrow">→</span>
				</a>
			</div>
		</div>

	</div>

	<!-- Toast -->
	<div id="nitroman-toast" class="nm-toast" style="display:none;"></div>
</div>
