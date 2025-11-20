<?php
/**
 * Template Loader
 *
 * @package PPI_Invoicing
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load custom templates for invoices
 */
function ppi_load_invoice_templates( $template ) {
	global $post;

	// Single invoice template
	if ( is_singular( 'ppi_invoice' ) ) {
		$plugin_template = PPI_INVOICING_PLUGIN_DIR . 'templates/single-invoice.php';
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}
	}

	// Invoice archive template
	if ( is_post_type_archive( 'ppi_invoice' ) ) {
		$plugin_template = PPI_INVOICING_PLUGIN_DIR . 'templates/archive-invoice.php';
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}
	}

	return $template;
}
add_filter( 'template_include', 'ppi_load_invoice_templates', 99 );

/**
 * Get invoice header
 */
function ppi_get_invoice_header() {
	$template = PPI_INVOICING_PLUGIN_DIR . 'templates/header-invoice.php';
	if ( file_exists( $template ) ) {
		load_template( $template );
	}
}

/**
 * Helper function to get meta with ACF-compatible naming
 */
function ppi_get_field( $field_name, $post_id = null ) {
	if ( null === $post_id ) {
		$post_id = get_the_ID();
	}

	// Map old ACF field names to new meta keys
	$field_map = array(
		'client' => '_ppi_client_id',
		'invoice_number' => '_ppi_invoice_number',
		'period_of_work' => '_ppi_period_of_work',
		'date_of_invoice' => '_ppi_date_of_invoice',
		'payment_due' => '_ppi_payment_due',
		'total' => '_ppi_total',
		'status' => '_ppi_status',
		'notes' => '_ppi_notes',
		'invoice_type' => '_ppi_invoice_type',
		'project' => '_ppi_projects',
		'itemized' => '_ppi_itemized',
		'standard_rate' => '_ppi_standard_rate',
		'address' => '_ppi_address'
	);

	$meta_key = isset( $field_map[ $field_name ] ) ? $field_map[ $field_name ] : '_ppi_' . $field_name;
	$value = get_post_meta( $post_id, $meta_key, true );

	// Handle client field - return post object instead of ID
	if ( 'client' === $field_name && ! empty( $value ) ) {
		return get_post( $value );
	}

	return $value;
}

/**
 * Check if has rows (for repeater fields)
 */
function ppi_have_rows( $field_name, $post_id = null ) {
	$rows = ppi_get_field( $field_name, $post_id );
	if ( ! is_array( $rows ) || empty( $rows ) ) {
		return false;
	}

	// Initialize row counter
	global $ppi_row_index, $ppi_current_rows;
	$ppi_row_index = -1;
	$ppi_current_rows = $rows;

	return true;
}

/**
 * The row (advance repeater)
 */
function ppi_the_row() {
	global $ppi_row_index, $ppi_current_rows, $ppi_current_row;

	$ppi_row_index++;

	if ( isset( $ppi_current_rows[ $ppi_row_index ] ) ) {
		$ppi_current_row = $ppi_current_rows[ $ppi_row_index ];
		return true;
	}

	return false;
}

/**
 * Get sub field
 */
function ppi_get_sub_field( $field_name ) {
	global $ppi_current_row;

	if ( isset( $ppi_current_row[ $field_name ] ) ) {
		return $ppi_current_row[ $field_name ];
	}

	return null;
}
