/**
 * Admin JavaScript
 * Pretty Professional Invoices
 */

jQuery(document).ready(function($) {
	'use strict';

	// Helper function for screen reader announcements
	function announceToScreenReader(message) {
		var $liveRegion = $('#ppi-sr-live-region');
		if ($liveRegion.length === 0) {
			$liveRegion = $('<div id="ppi-sr-live-region" class="screen-reader-text" aria-live="polite" aria-atomic="true"></div>');
			$('body').append($liveRegion);
		}
		$liveRegion.text(message);
		setTimeout(function() {
			$liveRegion.text('');
		}, 1000);
	}

	// Initialize datepickers with accessibility improvements
	if ($.fn.datepicker) {
		$('.ppi-datepicker').datepicker({
			dateFormat: 'MM d, yy',
			changeMonth: true,
			changeYear: true,
			showButtonPanel: true,
			beforeShow: function(input, inst) {
				// Add ARIA label
				$(input).attr('aria-describedby', 'ui-datepicker-div');
			},
			onClose: function(dateText, inst) {
				// Return focus to input
				$(this).focus();
			}
		});

		// Add keyboard hint to datepickers
		$('.ppi-datepicker').each(function() {
			var label = $('label[for="' + $(this).attr('id') + '"]').text() || 'Date';
			$(this).attr('aria-label', label + ' - Use down arrow to open calendar picker');
		});
	}

	// Invoice type switcher
	$('input[name="ppi_invoice_type"]').on('change', function() {
		var type = $(this).val();
		$('.ppi-invoice-data-container').hide();
		$('#ppi-' + type + '-container, #ppi-' + type + 's-container').show();
	});

	// Add project
	var projectIndex = $('#ppi-projects-list .ppi-project-row').length;
	$('.ppi-add-project').on('click', function() {
		var template = $('#ppi-project-template').html();
		var html = template.replace(/\{\{INDEX\}\}/g, projectIndex);
		var $newProject = $(html);
		$('#ppi-projects-list').append($newProject);

		// Move focus to first input in new project
		$newProject.find('input, select, textarea').first().focus();

		// Announce to screen readers
		announceToScreenReader('Project ' + (projectIndex + 1) + ' added');

		projectIndex++;
		updateProjectNumbers();
		initializeDatepickers();
	});

	// Remove project
	$(document).on('click', '.ppi-remove-project', function() {
		var projectNum = $(this).closest('.ppi-project-row').find('.project-number').text();
		if (confirm('Are you sure you want to remove project ' + projectNum + '?')) {
			$(this).closest('.ppi-project-row').remove();
			updateProjectNumbers();
			announceToScreenReader('Project removed');
		}
	});

	// Add time entry
	$(document).on('click', '.ppi-add-time-entry', function() {
		var projectIndex = $(this).data('project-index');
		var timeEntriesList = $(this).prev('.ppi-time-entries-list');
		var entryIndex = timeEntriesList.find('.ppi-time-entry-row').length;

		var template = $('#ppi-time-entry-template').html();
		var html = template.replace(/\{\{PROJECT_INDEX\}\}/g, projectIndex)
		                   .replace(/\{\{ENTRY_INDEX\}\}/g, entryIndex);

		var $newEntry = $(html);
		timeEntriesList.append($newEntry);

		// Move focus to first input in new time entry
		$newEntry.find('input, textarea').first().focus();

		// Announce to screen readers
		announceToScreenReader('Time entry added');

		initializeDatepickers();
	});

	// Remove time entry
	$(document).on('click', '.ppi-remove-time-entry', function() {
		if (confirm('Are you sure you want to remove this time entry?')) {
			$(this).closest('.ppi-time-entry-row').remove();
			announceToScreenReader('Time entry removed');
		}
	});

	// Add itemized line
	var itemizedIndex = $('#ppi-itemized-list .ppi-itemized-row').length;
	$('.ppi-add-itemized').on('click', function() {
		var template = $('#ppi-itemized-template').html();
		var html = template.replace(/\{\{INDEX\}\}/g, itemizedIndex);
		var $newItem = $(html);
		$('#ppi-itemized-list').append($newItem);

		// Move focus to first input in new itemized row
		$newItem.find('input, textarea').first().focus();

		// Announce to screen readers
		announceToScreenReader('Itemized entry added');

		itemizedIndex++;
	});

	// Remove itemized line
	$(document).on('click', '.ppi-remove-itemized', function() {
		if (confirm('Are you sure you want to remove this itemized entry?')) {
			$(this).closest('.ppi-itemized-row').remove();
			announceToScreenReader('Itemized entry removed');
		}
	});

	// Make projects sortable
	if ($.fn.sortable) {
		$('#ppi-projects-list').sortable({
			handle: '.ppi-project-header h5',
			placeholder: 'ui-sortable-placeholder',
			update: function() {
				updateProjectNumbers();
			}
		});

		// Make time entries sortable within projects
		$(document).on('mouseenter', '.ppi-time-entries-list', function() {
			if (!$(this).hasClass('ui-sortable')) {
				$(this).sortable({
					placeholder: 'ui-sortable-placeholder'
				});
			}
		});

		// Make itemized rows sortable
		$('#ppi-itemized-list').sortable({
			placeholder: 'ui-sortable-placeholder'
		});
	}

	// Add keyboard reordering support for projects
	$(document).on('keydown', '.ppi-project-row', function(e) {
		var $project = $(this);

		if (e.ctrlKey && e.key === 'ArrowUp') {
			e.preventDefault();
			var $prev = $project.prev('.ppi-project-row');
			if ($prev.length) {
				$project.insertBefore($prev);
				$project.focus();
				updateProjectNumbers();
				announceToScreenReader('Project moved up');
			}
		} else if (e.ctrlKey && e.key === 'ArrowDown') {
			e.preventDefault();
			var $next = $project.next('.ppi-project-row');
			if ($next.length) {
				$project.insertAfter($next);
				$project.focus();
				updateProjectNumbers();
				announceToScreenReader('Project moved down');
			}
		}
	});

	// Make projects focusable for keyboard navigation
	$('.ppi-project-row').attr('tabindex', '0');

	// Update project numbers
	function updateProjectNumbers() {
		$('#ppi-projects-list .ppi-project-row').each(function(index) {
			$(this).find('.project-number').text(index + 1);
			$(this).attr('data-index', index);
		});
	}

	// Initialize datepickers for dynamically added elements
	function initializeDatepickers() {
		if ($.fn.datepicker) {
			$('.ppi-datepicker').not('.hasDatepicker').datepicker({
				dateFormat: 'MM d, yy',
				changeMonth: true,
				changeYear: true
			});
		}
	}

	// Auto-calculate totals (optional feature)
	$(document).on('blur', 'input[name*="[hours]"], select[name="ppi_client_id"]', function() {
		calculateProjectTotals();
	});

	function calculateProjectTotals() {
		var clientId = $('select[name="ppi_client_id"]').val();
		if (!clientId) return;

		// This would need an AJAX call to get the client rate
		// Simplified version - you can enhance this
		console.log('Calculate totals for client:', clientId);
	}

	// Prevent form submission if required fields are empty
	$('form#post').on('submit', function(e) {
		var hasErrors = false;
		var errorMessages = [];

		// Remove previous error messages
		$('.ppi-validation-error').remove();
		$('[aria-invalid="true"]').removeAttr('aria-invalid').next('.error-message').remove();

		// Check if invoice type is selected
		if (!$('input[name="ppi_invoice_type"]:checked').val()) {
			hasErrors = true;
			errorMessages.push('Please select an invoice type');
		}

		// Check if client is selected
		if (!$('select[name="ppi_client_id"]').val()) {
			hasErrors = true;
			errorMessages.push('Please select a client');
			$('select[name="ppi_client_id"]').attr('aria-invalid', 'true')
				.after('<span class="error-message" role="alert" style="color: #dc3545; display: block;">This field is required</span>');
		}

		if (hasErrors) {
			e.preventDefault();

			// Add error summary at top
			var $errorSummary = $('<div class="ppi-validation-error notice notice-error" role="alert">' +
				'<p><strong>Please fix the following errors:</strong></p>' +
				'<ul></ul>' +
				'</div>');

			errorMessages.forEach(function(msg) {
				$errorSummary.find('ul').append('<li>' + msg + '</li>');
			});

			$('#post').prepend($errorSummary);

			// Move focus to error summary
			$errorSummary.attr('tabindex', '-1').focus();

			// Scroll to top
			$('html, body').animate({ scrollTop: 0 }, 300);

			return false;
		}
	});
});
