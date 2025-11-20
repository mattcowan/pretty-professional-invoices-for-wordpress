<?php
/**
 * AJAX Handlers
 *
 * @package PPI_Invoicing
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX handler for bulk invoice status updates
 */
function ppi_handle_invoice_status_update() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'invoice_status_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Security check failed' ) );
	}

	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'You must be logged in to perform this action' ) );
	}

	// Check if user can edit posts
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'You do not have permission to perform this action' ) );
	}

	// Validate and sanitize input before JSON decode
	if ( ! isset( $_POST['updates'] ) ) {
		wp_send_json_error( array( 'message' => 'No updates provided' ) );
	}

	$updates_json = sanitize_text_field( wp_unslash( $_POST['updates'] ) );
	$updates = json_decode( $updates_json, true );

	if ( ! is_array( $updates ) ) {
		wp_send_json_error( array( 'message' => 'Invalid updates format' ) );
	}

	$success_count = 0;
	$errors = array();

	foreach ( $updates as $update ) {
		// Validate that each update is an array
		if ( ! is_array( $update ) ) {
			continue;
		}

		$post_id = isset( $update['post_id'] ) ? absint( $update['post_id'] ) : 0;
		$status = isset( $update['status'] ) ? sanitize_text_field( $update['status'] ) : '';

		// Validate status against configured statuses
		$allowed_statuses = array_column( ppi_get_invoice_statuses(), 'value' );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$errors[] = "Invalid status for post ID $post_id";
			continue;
		}

		// Verify post exists and is an invoice
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'ppi_invoice' ) {
			$errors[] = "Invalid post ID $post_id";
			continue;
		}

		// Verify user can edit THIS specific post
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			$errors[] = "You don't have permission to edit post ID $post_id";
			continue;
		}

		// Update the status field
		if ( update_post_meta( $post_id, '_ppi_status', $status ) !== false ) {
			$success_count++;
		} else {
			$errors[] = "Failed to update post ID $post_id";
		}
	}

	wp_send_json_success( array(
		'updated' => $success_count,
		'errors' => $errors
	) );
}
add_action( 'wp_ajax_update_invoice_statuses', 'ppi_handle_invoice_status_update' );

/**
 * AJAX handler for invoice export
 */
function ppi_handle_invoice_export() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'invoice_status_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Security check failed' ) );
	}

	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'You must be logged in to perform this action' ) );
	}

	// Get format
	$format = isset( $_POST['format'] ) ? sanitize_text_field( $_POST['format'] ) : 'csv';
	if ( ! in_array( $format, array( 'csv', 'xlsx' ), true ) ) {
		wp_send_json_error( array( 'message' => 'Invalid export format' ) );
	}

	// Get invoice IDs
	$invoice_ids_json = isset( $_POST['invoice_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['invoice_ids'] ) ) : '[]';
	$invoice_ids = json_decode( $invoice_ids_json, true );

	if ( ! is_array( $invoice_ids ) || empty( $invoice_ids ) ) {
		wp_send_json_error( array( 'message' => 'No invoices to export' ) );
	}

	// Sanitize invoice IDs
	$invoice_ids = array_map( 'absint', $invoice_ids );

	// Get invoice data
	$export_data = array();
	$total_amount = 0;

	foreach ( $invoice_ids as $invoice_id ) {
		$post = get_post( $invoice_id );
		if ( ! $post || $post->post_type !== 'ppi_invoice' ) {
			continue;
		}

		// Get invoice meta
		$client = ppi_get_field( 'client', $invoice_id );
		$client_name = $client && is_object( $client ) ? $client->post_title : '';
		$invoice_number = ppi_get_field( 'invoice_number', $invoice_id ) ?: $post->post_name;
		$date_of_invoice = ppi_get_field( 'date_of_invoice', $invoice_id );
		$period_of_work = ppi_get_field( 'period_of_work', $invoice_id );
		$payment_due = ppi_get_field( 'payment_due', $invoice_id );
		$status = ppi_get_field( 'status', $invoice_id );
		$total = ppi_get_field( 'total', $invoice_id );

		// Calculate total if not set
		if ( ! $total ) {
			$client_rate = $client && is_object( $client ) ? ppi_get_field( 'standard_rate', $client->ID ) : 0;
			$calculated_pay = 0;
			$projects = ppi_get_field( 'project', $invoice_id );
			if ( is_array( $projects ) && ! empty( $projects ) ) {
				foreach ( $projects as $project ) {
					$project_hours = 0;
					$project_rate = ! empty( $project['project_rate'] ) ? $project['project_rate'] : $client_rate;

					if ( isset( $project['time_entries'] ) && is_array( $project['time_entries'] ) ) {
						foreach ( $project['time_entries'] as $entry ) {
							$hours = isset( $entry['hours'] ) ? floatval( $entry['hours'] ) : 0;
							if ( $hours > 0 ) {
								$project_hours += $hours;
							}
						}
					}

					$calculated_pay += $project_hours * $project_rate;
				}
			}
			$total = $calculated_pay;
		}

		// Get status label
		$status_label = $status;
		$statuses = ppi_get_invoice_statuses();
		foreach ( $statuses as $status_config ) {
			if ( $status_config['value'] === $status ) {
				$status_label = $status_config['label'];
				break;
			}
		}

		$export_data[] = array(
			'invoice_number' => $invoice_number,
			'date' => $date_of_invoice,
			'client' => $client_name,
			'status' => $status_label,
			'period_of_work' => $period_of_work,
			'payment_due' => $payment_due,
			'amount' => $total
		);

		$total_amount += $total;
	}

	if ( empty( $export_data ) ) {
		wp_send_json_error( array( 'message' => 'No valid invoices to export' ) );
	}

	// Generate export file
	if ( $format === 'csv' ) {
		ppi_generate_csv_export( $export_data, $total_amount );
	} elseif ( $format === 'xlsx' ) {
		ppi_generate_xlsx_export( $export_data, $total_amount );
	}

	exit;
}
add_action( 'wp_ajax_export_invoices', 'ppi_handle_invoice_export' );

/**
 * Generate CSV export
 */
function ppi_generate_csv_export( $data, $total_amount ) {
	$filename = 'invoices-' . date( 'Y-m-d' ) . '.csv';

	// Set headers for download
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );

	// Create file handle
	$output = fopen( 'php://output', 'w' );

	// Add UTF-8 BOM for Excel compatibility
	fprintf( $output, chr(0xEF).chr(0xBB).chr(0xBF) );

	// Write header row
	fputcsv( $output, array(
		'Invoice Number',
		'Date',
		'Client',
		'Status',
		'Period of Work',
		'Payment Due',
		'Amount'
	) );

	// Write data rows
	foreach ( $data as $row ) {
		fputcsv( $output, array(
			$row['invoice_number'],
			$row['date'],
			$row['client'],
			$row['status'],
			$row['period_of_work'],
			$row['payment_due'],
			number_format( $row['amount'], 2, '.', '' )
		) );
	}

	// Write summary row
	fputcsv( $output, array(
		'',
		'',
		'',
		'',
		'',
		'TOTAL',
		number_format( $total_amount, 2, '.', '' )
	) );

	fclose( $output );
}

/**
 * Generate Excel/XLSX export
 * Note: This creates a simple HTML table that Excel can open
 * For true .xlsx, would need PHPSpreadsheet library
 */
function ppi_generate_xlsx_export( $data, $total_amount ) {
	$filename = 'invoices-' . date( 'Y-m-d' ) . '.xls';

	// Set headers for download
	header( 'Content-Type: application/vnd.ms-excel' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );

	// Start output
	echo '<?xml version="1.0"?>';
	echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
	echo '<Worksheet ss:Name="Invoices">';
	echo '<Table>';

	// Header row
	echo '<Row>';
	echo '<Cell><Data ss:Type="String">Invoice Number</Data></Cell>';
	echo '<Cell><Data ss:Type="String">Date</Data></Cell>';
	echo '<Cell><Data ss:Type="String">Client</Data></Cell>';
	echo '<Cell><Data ss:Type="String">Status</Data></Cell>';
	echo '<Cell><Data ss:Type="String">Period of Work</Data></Cell>';
	echo '<Cell><Data ss:Type="String">Payment Due</Data></Cell>';
	echo '<Cell><Data ss:Type="String">Amount</Data></Cell>';
	echo '</Row>';

	// Data rows
	foreach ( $data as $row ) {
		echo '<Row>';
		echo '<Cell><Data ss:Type="String">' . htmlspecialchars( $row['invoice_number'] ) . '</Data></Cell>';
		echo '<Cell><Data ss:Type="String">' . htmlspecialchars( $row['date'] ) . '</Data></Cell>';
		echo '<Cell><Data ss:Type="String">' . htmlspecialchars( $row['client'] ) . '</Data></Cell>';
		echo '<Cell><Data ss:Type="String">' . htmlspecialchars( $row['status'] ) . '</Data></Cell>';
		echo '<Cell><Data ss:Type="String">' . htmlspecialchars( $row['period_of_work'] ) . '</Data></Cell>';
		echo '<Cell><Data ss:Type="String">' . htmlspecialchars( $row['payment_due'] ) . '</Data></Cell>';
		echo '<Cell><Data ss:Type="Number">' . number_format( $row['amount'], 2, '.', '' ) . '</Data></Cell>';
		echo '</Row>';
	}

	// Total row
	echo '<Row>';
	echo '<Cell><Data ss:Type="String"></Data></Cell>';
	echo '<Cell><Data ss:Type="String"></Data></Cell>';
	echo '<Cell><Data ss:Type="String"></Data></Cell>';
	echo '<Cell><Data ss:Type="String"></Data></Cell>';
	echo '<Cell><Data ss:Type="String"></Data></Cell>';
	echo '<Cell><Data ss:Type="String">TOTAL</Data></Cell>';
	echo '<Cell><Data ss:Type="Number">' . number_format( $total_amount, 2, '.', '' ) . '</Data></Cell>';
	echo '</Row>';

	echo '</Table>';
	echo '</Worksheet>';
	echo '</Workbook>';
}
