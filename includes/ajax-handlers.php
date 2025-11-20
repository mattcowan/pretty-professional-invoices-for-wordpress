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
