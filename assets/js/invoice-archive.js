/**
 * Invoice Archive JavaScript
 * Pretty Professional Invoices - Status Updates
 */

jQuery(document).ready(function($) {
	'use strict';

	var changedStatuses = {};
	var currentSort = { column: null, direction: 'asc' };
	var activeFilters = {
		client: '',
		statuses: [],
		dateStart: '',
		dateEnd: '',
		amountMin: '',
		amountMax: '',
		search: ''
	};

	// Track status changes
	$('.status-select').on('change', function() {
		var postId = $(this).data('post-id');
		var newStatus = $(this).val();
		changedStatuses[postId] = newStatus;

		// Update visual indicator
		updateStatusCount();
	});

	// Bulk save functionality
	$('#bulk-save').on('click', function() {
		if (Object.keys(changedStatuses).length === 0) {
			$('#save-status').text('No changes to save');
			setTimeout(function() {
				$('#save-status').text('');
			}, 3000);
			return;
		}

		// Validate updates array
		var updates = [];
		for (var postId in changedStatuses) {
			if (!postId || !changedStatuses[postId]) continue;

			updates.push({
				post_id: parseInt(postId, 10),
				status: String(changedStatuses[postId])
			});
		}

		if (updates.length === 0) {
			$('#save-status').text('No valid changes to save');
			return;
		}

		$('#save-status').text('Saving...');
		$('#bulk-save').prop('disabled', true).attr('aria-busy', 'true');

		$.ajax({
			url: ppiInvoicing.ajaxUrl,
			type: 'POST',
			data: {
				action: 'update_invoice_statuses',
				updates: JSON.stringify(updates),
				nonce: ppiInvoicing.nonce
			},
			success: function(response) {
				if (response.success) {
					var message = response.data.updated + ' invoice' + (response.data.updated !== 1 ? 's' : '') + ' updated';
					if (response.data.errors.length > 0) {
						message += ' (' + response.data.errors.length + ' error' + (response.data.errors.length !== 1 ? 's' : '') + ')';
						console.log('Errors:', response.data.errors);
					}
					$('#save-status').text(message);
					changedStatuses = {};
					updateStatusCount();

					// Reload page after 2 seconds to show updated totals
					setTimeout(function() {
						location.reload();
					}, 2000);
				} else {
					$('#save-status').text('Save failed: ' + (response.data.message || 'Unknown error'));
				}
			},
			error: function(xhr, status, error) {
				$('#save-status').text('Save failed: ' + error);
				console.error('AJAX Error:', xhr, status, error);
			},
			complete: function() {
				$('#bulk-save').prop('disabled', false).removeAttr('aria-busy');
			}
		});
	});

	// Update status count display
	function updateStatusCount() {
		var count = Object.keys(changedStatuses).length;
		if (count > 0) {
			$('#bulk-save').text('Save Status Changes (' + count + ')');
			$('#save-status').text(count + ' unsaved change' + (count !== 1 ? 's' : ''));
		} else {
			$('#bulk-save').text('Save Status Changes');
			$('#save-status').text('');
		}
	}

	// Keyboard shortcut: Ctrl/Cmd + S to save
	$(document).on('keydown', function(e) {
		if ((e.ctrlKey || e.metaKey) && e.key === 's') {
			e.preventDefault();
			if (Object.keys(changedStatuses).length > 0) {
				$('#bulk-save').click();
			}
		}
	});

	// Warn before leaving page with unsaved changes
	$(window).on('beforeunload', function() {
		if (Object.keys(changedStatuses).length > 0) {
			return 'You have unsaved status changes. Are you sure you want to leave?';
		}
	});

	// Table sorting functionality
	$('.sortable').on('click', function() {
		var column = $(this).data('sort');
		var $tbody = $('#invoice-table tbody');
		var rows = $tbody.find('tr').get();

		// Toggle sort direction
		if (currentSort.column === column) {
			currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
		} else {
			currentSort.column = column;
			currentSort.direction = 'asc';
		}

		// Update visual indicators
		$('.sortable').removeClass('sort-asc sort-desc');
		$(this).addClass('sort-' + currentSort.direction);

		// Sort rows
		rows.sort(function(a, b) {
			var aVal = $(a).data(column);
			var bVal = $(b).data(column);

			// Handle different data types
			if (column === 'amount') {
				aVal = parseFloat(aVal) || 0;
				bVal = parseFloat(bVal) || 0;
			} else if (column === 'date') {
				aVal = new Date(aVal);
				bVal = new Date(bVal);
			} else if (column === 'status') {
				// Custom sort order for status
				var statusOrder = { 'progress': 1, 'issued': 2, 'paid': 3, 'abandoned': 4 };
				aVal = statusOrder[aVal] || 999;
				bVal = statusOrder[bVal] || 999;
			} else {
				// String comparison
				aVal = String(aVal).toLowerCase();
				bVal = String(bVal).toLowerCase();
			}

			var comparison = 0;
			if (aVal > bVal) {
				comparison = 1;
			} else if (aVal < bVal) {
				comparison = -1;
			}

			return currentSort.direction === 'asc' ? comparison : -comparison;
		});

		// Reorder rows in the table
		$.each(rows, function(index, row) {
			$tbody.append(row);
		});
	});

	// ========== FILTERING FUNCTIONALITY ==========

	// Initialize status filters (all checked by default)
	function initializeStatusFilters() {
		activeFilters.statuses = [];
		$('.status-filter-checkbox:checked').each(function() {
			activeFilters.statuses.push($(this).val());
		});
	}
	initializeStatusFilters();

	// Apply all filters to table rows
	function applyFilters() {
		var visibleCount = 0;
		var $rows = $('#invoice-table tbody tr');

		$rows.each(function() {
			var $row = $(this);
			var visible = true;

			// Client filter
			if (activeFilters.client !== '') {
				if ($row.data('client-id') != activeFilters.client) {
					visible = false;
				}
			}

			// Status filter
			if (activeFilters.statuses.length > 0) {
				if (activeFilters.statuses.indexOf($row.data('status')) === -1) {
					visible = false;
				}
			}

			// Date range filter
			var rowDate = new Date($row.data('date'));
			if (activeFilters.dateStart !== '') {
				var startDate = new Date(activeFilters.dateStart);
				if (rowDate < startDate) {
					visible = false;
				}
			}
			if (activeFilters.dateEnd !== '') {
				var endDate = new Date(activeFilters.dateEnd);
				if (rowDate > endDate) {
					visible = false;
				}
			}

			// Amount range filter
			var rowAmount = parseFloat($row.data('amount')) || 0;
			if (activeFilters.amountMin !== '') {
				if (rowAmount < parseFloat(activeFilters.amountMin)) {
					visible = false;
				}
			}
			if (activeFilters.amountMax !== '') {
				if (rowAmount > parseFloat(activeFilters.amountMax)) {
					visible = false;
				}
			}

			// Invoice number search filter
			if (activeFilters.search !== '') {
				var invoiceNumber = $row.data('invoice').toString().toLowerCase();
				var searchTerm = activeFilters.search.toLowerCase();
				if (invoiceNumber.indexOf(searchTerm) === -1) {
					visible = false;
				}
			}

			// Show/hide row
			if (visible) {
				$row.show();
				visibleCount++;
			} else {
				$row.hide();
			}
		});

		// Update visible count
		updateVisibleCount(visibleCount, $rows.length);

		// Update sidebar statistics
		updateSidebarStats();
	}

	// Update visible count display
	function updateVisibleCount(visible, total) {
		if (visible === total) {
			$('#visible-count').text('Showing all ' + total + ' invoices');
		} else {
			$('#visible-count').text('Showing ' + visible + ' of ' + total + ' invoices');
		}
	}

	// Update sidebar statistics based on visible rows
	function updateSidebarStats() {
		var clientStats = {};
		var statusTotals = {};
		var grandTotal = 0;
		var everythingTotal = 0;

		// Calculate stats from visible rows
		$('#invoice-table tbody tr:visible').each(function() {
			var $row = $(this);
			var clientId = $row.data('client-id');
			var clientName = $row.data('client-name');
			var status = $row.data('status');
			var amount = parseFloat($row.data('amount')) || 0;

			// Calculate amount (if total is 0, it was calculated from projects)
			if (amount === 0) {
				// For calculated amounts, we'll use what's displayed in the cell
				var displayedAmount = $row.find('td:last').text().replace(/,/g, '');
				amount = parseFloat(displayedAmount) || 0;
			}

			everythingTotal += amount;

			// Status totals
			if (!statusTotals[status]) {
				statusTotals[status] = 0;
			}
			statusTotals[status] += amount;

			// Client breakdown (only if client exists)
			if (clientId && clientName) {
				if (!clientStats[clientId]) {
					clientStats[clientId] = {
						name: clientName,
						total: 0,
						count: 0,
						statusTotals: {}
					};
				}

				clientStats[clientId].count++;
				clientStats[clientId].total += amount;

				if (!clientStats[clientId].statusTotals[status]) {
					clientStats[clientId].statusTotals[status] = 0;
				}
				clientStats[clientId].statusTotals[status] += amount;
			}
		});

		// Update footer totals
		updateFooterTotals(statusTotals, grandTotal, everythingTotal);

		// Update sidebar
		updateSidebarContent(clientStats);
	}

	// Update footer totals
	function updateFooterTotals(statusTotals, grandTotal, everythingTotal) {
		var $tfoot = $('#invoice-table tfoot');

		// Calculate grand total from visible counted statuses
		grandTotal = 0;
		$tfoot.find('tr').each(function() {
			var $row = $(this);
			var $amountCell = $row.find('td:last');

			// Update status total rows
			var $statusIcon = $row.find('i[class*="ppi-"], i[class*="mnc-"]').first();
			if ($statusIcon.length) {
				var statusClass = $statusIcon.attr('class');
				var status = null;

				// Find matching status by icon class
				$('#invoice-table tbody tr:first .status-select option').each(function() {
					var optStatus = $(this).val();
					if (statusTotals[optStatus] !== undefined) {
						status = optStatus;
						var amount = statusTotals[status] || 0;
						$amountCell.text(number_format(amount, 2));

						// Check if counts towards total
						// This is simplified - would need status config data
						if (status === 'issued' || status === 'paid') {
							grandTotal += amount;
						}
					}
				});
			}

			// Update grand total row
			if ($row.find('.ppi-pig').length && $row.hasClass('bg-sum')) {
				$amountCell.text(number_format(grandTotal, 2));
			}

			// Update everything total row
			if ($row.find('.ppi-pig').length && !$row.hasClass('bg-sum')) {
				$amountCell.text(number_format(everythingTotal, 2));
			}
		});
	}

	// Update sidebar content
	function updateSidebarContent(clientStats) {
		var $clientList = $('.client-list');

		if (Object.keys(clientStats).length === 0) {
			$clientList.html('<p class="text-muted">No client data available with current filters.</p>');
			return;
		}

		// Calculate total revenue for percentages
		var totalRevenue = 0;
		$.each(clientStats, function(clientId, client) {
			totalRevenue += client.total;
		});

		// Sort clients by total (highest first)
		var sortedClients = Object.keys(clientStats).sort(function(a, b) {
			return clientStats[b].total - clientStats[a].total;
		});

		// Build HTML
		var html = '';
		$.each(sortedClients, function(index, clientId) {
			var client = clientStats[clientId];
			var percentage = totalRevenue > 0 ? (client.total / totalRevenue) * 100 : 0;

			html += '<div class="client-item">';
			html += '<div class="client-header">';
			html += '<div class="client-name">' + escapeHtml(client.name) + '</div>';
			html += '<div class="client-percentage">' + number_format(percentage, 1) + '%</div>';
			html += '</div>';

			if (percentage > 0) {
				html += '<div class="percentage-bar" role="img" aria-label="' + number_format(percentage, 1) + '% of total revenue">';
				html += '<div class="percentage-fill" style="width: ' + percentage + '%" aria-hidden="true"></div>';
				html += '</div>';
			}

			html += '<div class="client-stats">';
			html += '<div class="stat-row">';
			html += '<span class="stat-label">Invoices:</span>';
			html += '<span class="stat-value">' + client.count + '</span>';
			html += '</div>';

			// Status totals for this client
			$.each(client.statusTotals, function(status, amount) {
				if (amount > 0) {
					html += '<div class="stat-row">';
					html += '<span class="stat-label">' + ucfirst(status) + ':</span>';
					html += '<span class="stat-value">$' + number_format(amount, 2) + '</span>';
					html += '</div>';
				}
			});

			html += '<div class="stat-row total">';
			html += '<span class="stat-label font-weight-bold">Total:</span>';
			html += '<span class="stat-value font-weight-bold">$' + number_format(client.total, 2) + '</span>';
			html += '</div>';
			html += '</div>';
			html += '</div>';
		});

		$clientList.html(html);
	}

	// Helper: Format numbers
	function number_format(number, decimals) {
		return parseFloat(number).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
	}

	// Helper: Escape HTML
	function escapeHtml(text) {
		var map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return text.replace(/[&<>"']/g, function(m) { return map[m]; });
	}

	// Helper: Uppercase first letter
	function ucfirst(str) {
		return str.charAt(0).toUpperCase() + str.slice(1);
	}

	// ========== FILTER EVENT HANDLERS ==========

	// Client filter
	$('#filter-client').on('change', function() {
		activeFilters.client = $(this).val();
		applyFilters();
	});

	// Status checkboxes
	$('.status-filter-checkbox').on('change', function() {
		initializeStatusFilters();
		applyFilters();
	});

	// Date filters
	$('#filter-date-start, #filter-date-end').on('change', function() {
		activeFilters.dateStart = $('#filter-date-start').val();
		activeFilters.dateEnd = $('#filter-date-end').val();
		applyFilters();
	});

	// Amount filters
	$('#filter-amount-min, #filter-amount-max').on('input', function() {
		activeFilters.amountMin = $('#filter-amount-min').val();
		activeFilters.amountMax = $('#filter-amount-max').val();
		applyFilters();
	});

	// Search filter
	$('#filter-search').on('input', function() {
		activeFilters.search = $(this).val();
		applyFilters();
	});

	// Clear filters button
	$('#clear-filters').on('click', function() {
		// Reset all filter inputs
		$('#filter-client').val('');
		$('.status-filter-checkbox').prop('checked', true);
		$('#filter-date-start').val('');
		$('#filter-date-end').val('');
		$('#filter-amount-min').val('');
		$('#filter-amount-max').val('');
		$('#filter-search').val('');

		// Reset filter state
		activeFilters = {
			client: '',
			statuses: [],
			dateStart: '',
			dateEnd: '',
			amountMin: '',
			amountMax: '',
			search: ''
		};
		initializeStatusFilters();

		// Show all rows and recalculate
		$('#invoice-table tbody tr').show();
		applyFilters();
	});

	// ========== COLLAPSIBLE SIDEBAR ==========

	// Load sidebar state from localStorage
	var sidebarCollapsed = localStorage.getItem('ppi-sidebar-collapsed') === 'true';

	if (sidebarCollapsed) {
		$('#sidebar-content').hide();
		$('#sidebar-toggle').attr('aria-expanded', 'false');
		$('#sidebar-toggle .toggle-icon').html('&#9656;'); // Right arrow
	}

	// Toggle sidebar
	$('#sidebar-toggle').on('click', function() {
		var $content = $('#sidebar-content');
		var $icon = $(this).find('.toggle-icon');
		var isExpanded = $(this).attr('aria-expanded') === 'true';

		$content.slideToggle(300);

		if (isExpanded) {
			$(this).attr('aria-expanded', 'false');
			$icon.html('&#9656;'); // Right arrow
			localStorage.setItem('ppi-sidebar-collapsed', 'true');
		} else {
			$(this).attr('aria-expanded', 'true');
			$icon.html('&#9662;'); // Down arrow
			localStorage.setItem('ppi-sidebar-collapsed', 'false');
		}
	});

	// Initialize filter display
	applyFilters();

	// ========== EXPORT FUNCTIONALITY ==========

	// Toggle export dropdown
	$('#export-dropdown-toggle').on('click', function(e) {
		e.preventDefault();
		e.stopPropagation();
		var $menu = $('#export-dropdown-menu');
		var isExpanded = $(this).attr('aria-expanded') === 'true';

		if (isExpanded) {
			$menu.hide();
			$(this).attr('aria-expanded', 'false');
		} else {
			$menu.show();
			$(this).attr('aria-expanded', 'true');
		}
	});

	// Close dropdown when clicking outside
	$(document).on('click', function(e) {
		if (!$(e.target).closest('.export-dropdown').length) {
			$('#export-dropdown-menu').hide();
			$('#export-dropdown-toggle').attr('aria-expanded', 'false');
		}
	});

	// Hover effect for export menu items
	$('.export-option').on('mouseenter', function() {
		$(this).css('background-color', '#f5f5f5');
	}).on('mouseleave', function() {
		$(this).css('background-color', 'white');
	});

	// Handle export click
	$('.export-option').on('click', function(e) {
		e.preventDefault();
		var format = $(this).data('format');

		// Close dropdown
		$('#export-dropdown-menu').hide();
		$('#export-dropdown-toggle').attr('aria-expanded', 'false');

		// Get visible invoice IDs
		var invoiceIds = [];
		$('#invoice-table tbody tr:visible').each(function() {
			var postId = $(this).data('post-id');
			if (postId) {
				invoiceIds.push(postId);
			}
		});

		if (invoiceIds.length === 0) {
			$('#export-status').text('No invoices to export');
			setTimeout(function() {
				$('#export-status').text('');
			}, 3000);
			return;
		}

		// Show loading message
		$('#export-status').text('Preparing export...');
		$('#export-dropdown-toggle').prop('disabled', true);

		// Create a form and submit it to trigger download
		var form = $('<form>', {
			method: 'POST',
			action: ppiInvoicing.ajaxUrl
		});

		form.append($('<input>', {
			type: 'hidden',
			name: 'action',
			value: 'export_invoices'
		}));

		form.append($('<input>', {
			type: 'hidden',
			name: 'format',
			value: format
		}));

		form.append($('<input>', {
			type: 'hidden',
			name: 'invoice_ids',
			value: JSON.stringify(invoiceIds)
		}));

		form.append($('<input>', {
			type: 'hidden',
			name: 'nonce',
			value: ppiInvoicing.nonce
		}));

		// Append to body, submit, and remove
		form.appendTo('body').submit().remove();

		// Clear status after a delay
		setTimeout(function() {
			$('#export-status').text(invoiceIds.length + ' invoice' + (invoiceIds.length !== 1 ? 's' : '') + ' exported as ' + format.toUpperCase());
			$('#export-dropdown-toggle').prop('disabled', false);

			setTimeout(function() {
				$('#export-status').text('');
			}, 3000);
		}, 1000);
	});
});
