/**
 * NitroMan Premium Admin JavaScript
 *
 * @package NitroMan
 */

(function($) {
	'use strict';

	var NM = {

		init: function() {
			this.bindEvents();
			this.animateGauge();
		},

		bindEvents: function() {
			$(document).on('click', '#nitroman-purge-cache', this.purgeCache);
			$(document).on('click', '.nitroman-purge-link a', function(e) {
				e.preventDefault();
				NM.purgeCache(e);
			});
			$(document).on('click', '#nitroman-run-cleanup', this.runDbCleanup);
			$(document).on('click', '#nitroman-toggle-htaccess', this.toggleHtaccess);
			$(document).on('submit', '#nitroman-settings-form', this.saveSettings);
			$(document).on('click', '.nitroman-tabs__tab', this.handleTabClick);
		},

		/**
		 * Animate the SVG gauge on load.
		 */
		animateGauge: function() {
			var $gauge = $('.nm-gauge');
			if (!$gauge.length) return;

			// Add gradient definition if not present.
			var svg = $gauge.find('svg')[0];
			if (svg && !svg.querySelector('#nmGaugeGrad')) {
				var defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
				defs.innerHTML =
					'<linearGradient id="nmGaugeGrad" x1="0%" y1="0%" x2="100%" y2="100%">' +
					'<stop offset="0%" stop-color="#6366f1"/>' +
					'<stop offset="100%" stop-color="#a855f7"/>' +
					'</linearGradient>';
				svg.insertBefore(defs, svg.firstChild);
			}
		},

		/**
		 * Purge cache via AJAX.
		 */
		purgeCache: function(e) {
			e.preventDefault();
			var $btn = $('#nitroman-purge-cache');
			$btn.addClass('is-loading').prop('disabled', true);
			NM.toast(nitromanAdmin.strings.purging, 'info');

			$.post(nitromanAdmin.ajaxUrl, {
				action: 'nitroman_purge_cache',
				nonce: nitromanAdmin.purgeNonce
			}, function(res) {
				if (res.success) {
					NM.toast(res.data.message, 'success');
					$('#nitroman-cache-count').text(res.data.page_count);
					$('#nitroman-cache-size').text(res.data.cache_size);
				} else {
					NM.toast(res.data.message || nitromanAdmin.strings.error, 'error');
				}
			}).fail(function() {
				NM.toast(nitromanAdmin.strings.error, 'error');
			}).always(function() {
				$btn.removeClass('is-loading').prop('disabled', false);
			});
		},

		/**
		 * Run database cleanup via AJAX.
		 */
		runDbCleanup: function(e) {
			e.preventDefault();
			var $btn = $(this);
			$btn.addClass('is-loading').prop('disabled', true);
			NM.toast(nitromanAdmin.strings.cleaning, 'info');

			$.post(nitromanAdmin.ajaxUrl, {
				action: 'nitroman_run_db_cleanup',
				nonce: nitromanAdmin.dbCleanupNonce
			}, function(res) {
				if (res.success) {
					NM.toast(res.data.message, 'success');
					if (res.data.stats) {
						var s = res.data.stats;
						$('#nitroman-stat-revisions').text(s.revisions || 0);
						$('#nitroman-stat-auto-drafts').text(s.auto_drafts || 0);
						$('#nitroman-stat-trashed').text(s.trashed || 0);
						$('#nitroman-stat-spam').text(s.spam || 0);
						$('#nitroman-stat-transients').text(s.transients || 0);
						$('#nitroman-stat-orphan-meta').text(s.orphan_meta || 0);
					}
				} else {
					NM.toast(res.data.message || nitromanAdmin.strings.error, 'error');
				}
			}).fail(function() {
				NM.toast(nitromanAdmin.strings.error, 'error');
			}).always(function() {
				$btn.removeClass('is-loading').prop('disabled', false);
			});
		},

		/**
		 * Toggle .htaccess optimizations via AJAX.
		 */
		toggleHtaccess: function(e) {
			e.preventDefault();
			var $btn = $(this);
			var isActive = $btn.data('active') === 1;
			var enable = !isActive;

			$btn.addClass('is-loading').prop('disabled', true);

			$.post(nitromanAdmin.ajaxUrl, {
				action: 'nitroman_toggle_htaccess',
				nonce: nitromanAdmin.htaccessNonce,
				enable: enable ? 1 : 0
			}, function(res) {
				if (res.success) {
					NM.toast(res.data.message, 'success');

					if (res.data.active) {
						$btn.data('active', 1)
							.removeClass('nm-btn--primary').addClass('nm-btn--danger')
							.html('<span class="dashicons dashicons-no"></span> Deactivate');
						$('#nm-htaccess-status')
							.removeClass('nm-chip--off').addClass('nm-chip--on')
							.find('.nm-chip__dot').end()
							.contents().filter(function() { return this.nodeType === 3; }).last()[0].textContent = ' Active';
					} else {
						$btn.data('active', 0)
							.removeClass('nm-btn--danger').addClass('nm-btn--primary')
							.html('<span class="dashicons dashicons-yes"></span> Activate');
						$('#nm-htaccess-status')
							.removeClass('nm-chip--on').addClass('nm-chip--off')
							.contents().filter(function() { return this.nodeType === 3; }).last()[0].textContent = ' Inactive';
					}
				} else {
					NM.toast(res.data.message || nitromanAdmin.strings.error, 'error');
				}
			}).fail(function() {
				NM.toast(nitromanAdmin.strings.error, 'error');
			}).always(function() {
				$btn.removeClass('is-loading').prop('disabled', false);
			});
		},

		/**
		 * Save settings via AJAX.
		 */
		saveSettings: function(e) {
			e.preventDefault();
			var $btn = $('#nitroman-save-settings');
			$btn.addClass('is-loading').prop('disabled', true);
			NM.toast(nitromanAdmin.strings.saving, 'info');

			var formData = $('#nitroman-settings-form').serialize();
			formData += '&action=nitroman_save_settings';
			formData += '&nonce=' + nitromanAdmin.saveSettingsNonce;

			$.post(nitromanAdmin.ajaxUrl, formData, function(res) {
				if (res.success) {
					NM.toast(res.data.message, 'success');
				} else {
					NM.toast(res.data.message || nitromanAdmin.strings.error, 'error');
				}
			}).fail(function() {
				NM.toast(nitromanAdmin.strings.error, 'error');
			}).always(function() {
				$btn.removeClass('is-loading').prop('disabled', false);
			});
		},

		/**
		 * Tab navigation.
		 */
		handleTabClick: function(e) {
			var href = $(this).attr('href');
			if (!href || href.indexOf('tab=') === -1) return;
			e.preventDefault();

			var tabMatch = href.match(/tab=([a-z]+)/);
			if (!tabMatch) return;

			$('.nitroman-tabs__tab').removeClass('nitroman-tabs__tab--active');
			$(this).addClass('nitroman-tabs__tab--active');

			$('.nitroman-tab-content').hide();
			$('#tab-' + tabMatch[1]).fadeIn(200);

			if (window.history && window.history.pushState) {
				window.history.pushState({}, '', href);
			}
		},

		/**
		 * Show toast notification.
		 */
		toast: function(message, type) {
			var $t = $('#nitroman-toast');
			$t.stop(true, true)
				.removeClass('nm-toast--info nm-toast--success nm-toast--error')
				.addClass('nm-toast--' + (type || 'info'))
				.text(message)
				.fadeIn(200).delay(3500).fadeOut(400);
		}
	};

	$(function() {
		NM.init();
	});

})(jQuery);
