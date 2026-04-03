<?php
/**
 * Database cleanup module for NitroMan.
 *
 * @package NitroMan
 */

namespace NitroMan;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Database_Cleaner
 *
 * Handles scheduled and on-demand database cleanup tasks
 * including revisions, auto-drafts, transients, spam, and orphaned metadata.
 */
class Database_Cleaner {

	/**
	 * Singleton instance.
	 *
	 * @var Database_Cleaner|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Database_Cleaner
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
		// No hooks needed — this runs on-demand via cron or AJAX.
	}

	/**
	 * Run all enabled cleanup tasks.
	 *
	 * @return array Results with counts of cleaned items per category.
	 */
	public function run_cleanup() {
		$results = array();

		if ( nitroman_get_option( 'nitroman_clean_revisions' ) ) {
			$results['revisions'] = $this->clean_revisions();
		}

		if ( nitroman_get_option( 'nitroman_clean_auto_drafts' ) ) {
			$results['auto_drafts'] = $this->clean_auto_drafts();
		}

		if ( nitroman_get_option( 'nitroman_clean_trashed' ) ) {
			$results['trashed'] = $this->clean_trashed_posts();
		}

		if ( nitroman_get_option( 'nitroman_clean_spam' ) ) {
			$results['spam'] = $this->clean_spam_comments();
		}

		if ( nitroman_get_option( 'nitroman_clean_transients' ) ) {
			$results['transients'] = $this->clean_expired_transients();
		}

		if ( nitroman_get_option( 'nitroman_clean_orphan_meta' ) ) {
			$results['orphan_meta'] = $this->clean_orphan_postmeta();
		}

		// Optimize tables after cleanup.
		$this->optimize_tables();

		return $results;
	}

	/**
	 * Clean post revisions beyond the keep limit.
	 *
	 * @return int Number of deleted revisions.
	 */
	public function clean_revisions() {
		global $wpdb;

		$keep = (int) nitroman_get_option( 'nitroman_revisions_to_keep' );

		if ( $keep <= 0 ) {
			// Delete ALL revisions.
			$count = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				"DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'"
			);
			return (int) $count;
		}

		// Get all post IDs that have revisions.
		$post_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT DISTINCT post_parent FROM {$wpdb->posts} WHERE post_type = 'revision'"
		);

		$total_deleted = 0;

		foreach ( $post_ids as $post_id ) {
			// Get revision IDs ordered by date (newest first), skipping the ones to keep.
			$revisions_to_delete = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts}
					WHERE post_type = 'revision' AND post_parent = %d
					ORDER BY post_date DESC
					LIMIT 999 OFFSET %d",
					(int) $post_id,
					$keep
				)
			);

			if ( ! empty( $revisions_to_delete ) ) {
				foreach ( $revisions_to_delete as $revision_id ) {
					wp_delete_post_revision( (int) $revision_id );
					++$total_deleted;
				}
			}
		}

		return $total_deleted;
	}

	/**
	 * Clean auto-draft posts.
	 *
	 * @return int Number of deleted auto-drafts.
	 */
	public function clean_auto_drafts() {
		global $wpdb;

		$count = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"DELETE FROM {$wpdb->posts} WHERE post_status = 'auto-draft'"
		);

		return (int) $count;
	}

	/**
	 * Clean trashed posts.
	 *
	 * @return int Number of deleted trashed posts.
	 */
	public function clean_trashed_posts() {
		global $wpdb;

		$trashed = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'trash'"
		);

		$count = 0;
		foreach ( $trashed as $post_id ) {
			wp_delete_post( (int) $post_id, true );
			++$count;
		}

		return $count;
	}

	/**
	 * Clean spam and trashed comments.
	 *
	 * @return int Number of deleted comments.
	 */
	public function clean_spam_comments() {
		global $wpdb;

		$count = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"DELETE FROM {$wpdb->comments} WHERE comment_approved IN ('spam', 'trash')"
		);

		// Clean orphaned comment metadata.
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"DELETE FROM {$wpdb->commentmeta} WHERE comment_id NOT IN (SELECT comment_ID FROM {$wpdb->comments})"
		);

		return (int) $count;
	}

	/**
	 * Clean expired transients.
	 *
	 * @return int Number of deleted transients.
	 */
	public function clean_expired_transients() {
		global $wpdb;

		$time  = time();
		$count = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE a, b FROM {$wpdb->options} a
				INNER JOIN {$wpdb->options} b ON b.option_name = REPLACE(a.option_name, '_timeout', '')
				WHERE a.option_name LIKE '%%_transient_timeout_%%'
				AND a.option_value < %d",
				$time
			)
		);

		return (int) $count;
	}

	/**
	 * Clean orphaned post metadata.
	 *
	 * @return int Number of deleted orphan metadata rows.
	 */
	public function clean_orphan_postmeta() {
		global $wpdb;

		$count = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"DELETE pm FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE p.ID IS NULL"
		);

		return (int) $count;
	}

	/**
	 * Optimize database tables for performance.
	 *
	 * @return void
	 */
	private function optimize_tables() {
		global $wpdb;

		$tables = array(
			$wpdb->posts,
			$wpdb->postmeta,
			$wpdb->comments,
			$wpdb->commentmeta,
			$wpdb->options,
		);

		foreach ( $tables as $table ) {
			$wpdb->query( "OPTIMIZE TABLE {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}

	/**
	 * Get statistics about cleanable items.
	 *
	 * @return array Counts of each cleanable type.
	 */
	public function get_stats() {
		global $wpdb;

		return array(
			'revisions'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'" ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			'auto_drafts'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'" ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			'trashed'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'" ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			'spam'         => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved IN ('spam', 'trash')" ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			'transients'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '%_transient_timeout_%'" ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			'orphan_meta'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.ID IS NULL" ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		);
	}
}
