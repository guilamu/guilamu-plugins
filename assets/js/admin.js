/**
 * Guilamu's Plugins – Admin Dashboard JS
 *
 * Handles search, filters, and AJAX actions (install/activate/deactivate/delete/refresh).
 */
(function ($) {
	'use strict';

	var nonce   = guilamuPlugins.nonce;
	var ajaxUrl = guilamuPlugins.ajaxUrl;

	/* ── Filters & Search ── */

	$('#guilamu-search').on('input', applyFilters);
	$('#guilamu-status-filter').on('change', applyFilters);
	$('#guilamu-category-filter').on('change', applyFilters);

	function applyFilters() {
		var query          = ($('#guilamu-search').val() || '').toLowerCase();
		var statusFilter   = $('#guilamu-status-filter').val();
		var categoryFilter = $('#guilamu-category-filter').val();
		var visibleCount   = 0;

		$('.guilamu-plugin-card').each(function () {
			var $card    = $(this);
			var name     = ($card.attr('data-name') || '').toLowerCase();
			var desc     = ($card.attr('data-description') || '').toLowerCase();
			var status   = $card.attr('data-status');
			var category = $card.attr('data-category');

			// Search.
			var matchesSearch = !query || name.indexOf(query) !== -1 || desc.indexOf(query) !== -1;

			// Status filter.
			var matchesStatus = true;
			if (statusFilter === 'activated')      matchesStatus = (status === 'active');
			else if (statusFilter === 'installed') matchesStatus = (status === 'active' || status === 'installed');
			else if (statusFilter === 'not-installed') matchesStatus = (status === 'not-installed');

			// Category filter.
			var matchesCategory = (categoryFilter === 'all') || (category === categoryFilter);

			var visible = matchesSearch && matchesStatus && matchesCategory;
			$card.toggle(visible);
			if (visible) visibleCount++;
		});

		$('#guilamu-no-results').toggle(visibleCount === 0);
	}

	/* ── Install ── */

	$(document).on('click', '.guilamu-install-btn', function () {
		var $btn  = $(this);
		var $card = $btn.closest('.guilamu-plugin-card');
		var slug  = $card.data('slug');

		$btn.prop('disabled', true).text('Installing…');

		$.post(ajaxUrl, {
			action: 'guilamu_install_plugin',
			nonce:  nonce,
			slug:   slug
		}, function (response) {
			if (response.success) {
				var d = response.data;
				$card.attr('data-status', 'installed').attr('data-plugin-file', d.plugin_file);
				if (d.name) {
					$card.find('.guilamu-card-title').text(d.name);
					$card.attr('data-name', d.name);
				}
				if (d.description) {
					$card.find('.guilamu-card-description').text(d.description);
					$card.attr('data-description', d.description);
				}
				if (d.version) {
					$card.find('.guilamu-version').remove();
					$card.find('.guilamu-card-body').append(
						'<span class="guilamu-version">v' + escapeHtml(d.version) + '</span>'
					);
				}
				updateCardFooter($card, 'installed', false);
				updateCardHeaderButtons($card, 'installed', false);
				updateFilterCounts();
			} else {
				$btn.prop('disabled', false).text('Install');
				alert(response.data && response.data.message ? response.data.message : 'Installation failed.');
			}
		}).fail(function () {
			$btn.prop('disabled', false).text('Install');
			alert('Network error during installation.');
		});
	});

	/* ── Toggle Activate / Deactivate ── */

	$(document).on('click', '.guilamu-toggle', function () {
		var $toggle = $(this);
		if ($toggle.hasClass('loading')) return;

		var $card      = $toggle.closest('.guilamu-plugin-card');
		var isActive   = $toggle.hasClass('active');
		var pluginFile = $card.attr('data-plugin-file');
		var slug       = $card.data('slug');

		$toggle.addClass('loading');

		$.post(ajaxUrl, {
			action:      isActive ? 'guilamu_deactivate_plugin' : 'guilamu_activate_plugin',
			nonce:       nonce,
			plugin_file: pluginFile,
			slug:        slug
		}, function (response) {
			$toggle.removeClass('loading');
			if (response.success) {
				var newActive = !isActive;
				var newStatus = newActive ? 'active' : 'installed';
				$card.attr('data-status', newStatus);
				updateCardFooter($card, newStatus, newActive);
				updateCardHeaderButtons($card, newStatus, newActive);
				updateFilterCounts();
			} else {
				alert(response.data && response.data.message ? response.data.message : 'Action failed.');
			}
		}).fail(function () {
			$toggle.removeClass('loading');
			alert('Network error.');
		});
	});

	/* ── Delete ── */

	$(document).on('click', '.guilamu-delete-btn', function () {
		var $card      = $(this).closest('.guilamu-plugin-card');
		var slug       = $card.data('slug');
		var pluginFile = $card.attr('data-plugin-file');
		var name       = $card.attr('data-name');

		if (!confirm('Delete "' + name + '"? This will remove the plugin files.')) return;

		var $btn = $(this);
		$btn.prop('disabled', true);

		$.post(ajaxUrl, {
			action:      'guilamu_delete_plugin',
			nonce:       nonce,
			plugin_file: pluginFile,
			slug:        slug
		}, function (response) {
			if (response.success) {
				$card.attr('data-status', 'not-installed').attr('data-plugin-file', '');
				$card.find('.guilamu-version').remove();
				updateCardFooter($card, 'not-installed', false);
				updateCardHeaderButtons($card, 'not-installed', false);
				updateFilterCounts();
			} else {
				$btn.prop('disabled', false);
				alert(response.data && response.data.message ? response.data.message : 'Delete failed.');
			}
		}).fail(function () {
			$btn.prop('disabled', false);
			alert('Network error during deletion.');
		});
	});

	/* ── Refresh ── */

	$('#guilamu-refresh').on('click', function (e) {
		e.preventDefault();
		var $btn = $(this);
		if ($btn.hasClass('refreshing')) return;

		$btn.addClass('refreshing');

		$.post(ajaxUrl, {
			action: 'guilamu_refresh_plugins',
			nonce:  nonce
		}, function () {
			location.reload();
		}).fail(function () {
			$btn.removeClass('refreshing');
			alert('Failed to refresh. Please try again.');
		});
	});

	/* ── Helpers ── */

	function updateCardFooter($card, status, isActive) {
		var $footer = $card.find('.guilamu-card-footer');
		$footer.empty();

		if (status === 'not-installed') {
			$footer.html(
				'<span class="guilamu-status-label guilamu-status--not-installed">Not Installed</span>' +
				'<button class="button button-primary guilamu-install-btn">Install</button>'
			);
			return;
		}

		var statusClass = isActive ? 'guilamu-status--active' : 'guilamu-status--inactive';
		var statusText  = isActive ? 'Active' : 'Inactive';
		var toggleClass = isActive ? 'guilamu-toggle active' : 'guilamu-toggle';
		var ariaChecked = isActive ? 'true' : 'false';
		var ariaLabel   = isActive ? 'Deactivate' : 'Activate';

		$footer.html(
			'<div class="guilamu-toggle-wrap">' +
				'<span class="guilamu-status-label ' + statusClass + '">' + statusText + '</span>' +
				'<button class="' + toggleClass + '" role="switch" aria-checked="' + ariaChecked + '" aria-label="' + ariaLabel + '">' +
					'<span class="guilamu-toggle-thumb"></span>' +
				'</button>' +
			'</div>'
		);
	}

	function updateFilterCounts() {
		var counts = { all: 0, activated: 0, installed: 0, 'not-installed': 0 };

		$('.guilamu-plugin-card').each(function () {
			var status = $(this).attr('data-status');
			counts.all++;
			if (status === 'active') {
				counts.activated++;
				counts.installed++;
			} else if (status === 'installed') {
				counts.installed++;
			} else {
				counts['not-installed']++;
			}
		});

		// Update dropdown option labels with new counts.
		var $status = $('#guilamu-status-filter');
		$status.find('option[value="all"]').text('All Plugins (' + counts.all + ')');
		$status.find('option[value="activated"]').text('Activated (' + counts.activated + ')');
		$status.find('option[value="installed"]').text('Installed (' + counts.installed + ')');
		$status.find('option[value="not-installed"]').text('Not Installed (' + counts['not-installed'] + ')');
	}

	/* ── Bug Report (fallback when guilamu-bug-reporter is not active) ── */

	$(document).on('click', '.guilamu-header-icon-btn.guilamu-bug-report-btn', function (e) {
		// If guilamu-bug-reporter is active, its own JS handles this via the
		// .guilamu-bug-report-btn class — do nothing extra.
		if (guilamuPlugins.bugReporterActive) return;

		// Fallback: open GitHub Issues for this plugin.
		var slug = $(this).attr('data-plugin-slug');
		if (slug) {
			window.open('https://github.com/guilamu/' + encodeURIComponent(slug) + '/issues/new', '_blank');
		}
	});

	/* ── Header Buttons Helper ── */

	function updateCardHeaderButtons($card, status, isActive) {
		var $buttons = $card.find('.guilamu-card-header-buttons');
		var slug = $card.data('slug');
		var name = $card.attr('data-name');

		// Remove existing bug & delete icons (keep GitHub link).
		$buttons.find('.guilamu-header-icon-btn').remove();

		if (status === 'not-installed') return;

		// Bug icon (always for installed plugins).
		$buttons.append(
			'<button type="button" class="guilamu-header-icon-btn guilamu-bug-report-btn"' +
			' data-plugin-slug="' + escapeHtml(slug) + '"' +
			' data-plugin-name="' + escapeHtml(name) + '"' +
			' title="Report a Bug">' +
			'<span class="dashicons dashicons-flag"></span></button>'
		);

		// Delete icon (only when inactive).
		if (!isActive) {
			$buttons.append(
				'<button type="button" class="guilamu-header-icon-btn guilamu-delete-btn"' +
				' title="Delete plugin">' +
				'<span class="dashicons dashicons-trash"></span></button>'
			);
		}
	}

	function escapeHtml(str) {
		var div = document.createElement('div');
		div.appendChild(document.createTextNode(str));
		return div.innerHTML;
	}

})(jQuery);
