<?php
/**
 * Meta Boxes for Invoices and Clients
 *
 * @package PPI_Invoicing
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add meta boxes
 */
function ppi_add_meta_boxes() {
	// Invoice meta boxes
	add_meta_box(
		'ppi_invoice_details',
		__( 'Invoice Details', 'ppi-invoicing' ),
		'ppi_invoice_details_callback',
		'ppi_invoice',
		'normal',
		'high'
	);

	add_meta_box(
		'ppi_invoice_type',
		__( 'Invoice Type & Data', 'ppi-invoicing' ),
		'ppi_invoice_type_callback',
		'ppi_invoice',
		'normal',
		'high'
	);

	// Client meta boxes
	add_meta_box(
		'ppi_client_details',
		__( 'Client Details', 'ppi-invoicing' ),
		'ppi_client_details_callback',
		'ppi_client',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'ppi_add_meta_boxes' );

/**
 * Invoice Details meta box callback
 */
function ppi_invoice_details_callback( $post ) {
	wp_nonce_field( 'ppi_save_invoice_meta', 'ppi_invoice_meta_nonce' );

	$client_id = get_post_meta( $post->ID, '_ppi_client_id', true );
	$invoice_number = get_post_meta( $post->ID, '_ppi_invoice_number', true );
	$period_of_work = get_post_meta( $post->ID, '_ppi_period_of_work', true );
	$date_of_invoice = get_post_meta( $post->ID, '_ppi_date_of_invoice', true );
	$payment_due = get_post_meta( $post->ID, '_ppi_payment_due', true );
	$total = get_post_meta( $post->ID, '_ppi_total', true );
	$status = get_post_meta( $post->ID, '_ppi_status', true );
	$notes = get_post_meta( $post->ID, '_ppi_notes', true );
	$from_address = get_post_meta( $post->ID, '_ppi_from_address', true );

	// Get all clients
	$clients = get_posts( array(
		'post_type' => 'ppi_client',
		'posts_per_page' => -1,
		'orderby' => 'title',
		'order' => 'ASC'
	));
	?>

	<table class="form-table">
		<tr>
			<th><label for="ppi_client_id"><?php _e( 'Client', 'ppi-invoicing' ); ?></label></th>
			<td>
				<select name="ppi_client_id" id="ppi_client_id" class="regular-text">
					<option value=""><?php _e( 'Select Client', 'ppi-invoicing' ); ?></option>
					<?php foreach ( $clients as $client ) : ?>
						<option value="<?php echo esc_attr( $client->ID ); ?>" <?php selected( $client_id, $client->ID ); ?>>
							<?php echo esc_html( $client->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="ppi_invoice_number"><?php _e( 'Invoice Number', 'ppi-invoicing' ); ?></label></th>
			<td>
				<input type="text" name="ppi_invoice_number" id="ppi_invoice_number" value="<?php echo esc_attr( $invoice_number ); ?>" class="regular-text" <?php echo empty( $invoice_number ) ? 'placeholder="' . esc_attr__( 'Auto-generated on save', 'ppi-invoicing' ) . '"' : ''; ?>>
				<?php if ( ! empty( $invoice_number ) ) : ?>
					<p class="description"><?php _e( 'Invoice number was auto-generated. You can modify it if needed.', 'ppi-invoicing' ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th><label for="ppi_period_of_work"><?php _e( 'Period of Work', 'ppi-invoicing' ); ?></label></th>
			<td><input type="text" name="ppi_period_of_work" id="ppi_period_of_work" value="<?php echo esc_attr( $period_of_work ); ?>" class="regular-text" placeholder="e.g., January 1-15, 2025"></td>
		</tr>
		<tr>
			<th><label for="ppi_date_of_invoice"><?php _e( 'Date of Invoice', 'ppi-invoicing' ); ?></label></th>
			<td><input type="text" name="ppi_date_of_invoice" id="ppi_date_of_invoice" value="<?php echo esc_attr( $date_of_invoice ); ?>" class="ppi-datepicker regular-text"></td>
		</tr>
		<tr>
			<th><label for="ppi_payment_due"><?php _e( 'Payment Due', 'ppi-invoicing' ); ?></label></th>
			<td><input type="text" name="ppi_payment_due" id="ppi_payment_due" value="<?php echo esc_attr( $payment_due ); ?>" class="ppi-datepicker regular-text"></td>
		</tr>
		<tr>
			<th><label for="ppi_total"><?php _e( 'Total Override', 'ppi-invoicing' ); ?></label></th>
			<td>
				<input type="number" step="0.01" name="ppi_total" id="ppi_total" value="<?php echo esc_attr( $total ); ?>" class="regular-text">
				<p class="description"><?php _e( 'Leave empty to calculate automatically from projects/items', 'ppi-invoicing' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="ppi_status"><?php _e( 'Status', 'ppi-invoicing' ); ?></label></th>
			<td>
				<select name="ppi_status" id="ppi_status">
					<?php
					$statuses = ppi_get_invoice_statuses();
					foreach ( $statuses as $status_option ) :
					?>
						<option value="<?php echo esc_attr( $status_option['value'] ); ?>" <?php selected( $status, $status_option['value'] ); ?>>
							<?php echo esc_html( $status_option['label'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="ppi_notes"><?php _e( 'Notes', 'ppi-invoicing' ); ?></label></th>
			<td><textarea name="ppi_notes" id="ppi_notes" rows="4" class="large-text"><?php echo esc_textarea( $notes ); ?></textarea></td>
		</tr>
		<tr>
			<th><label for="ppi_from_address"><?php _e( 'From Address Override', 'ppi-invoicing' ); ?></label></th>
			<td>
				<?php
				wp_editor( $from_address, 'ppi_from_address', array(
					'textarea_name' => 'ppi_from_address',
					'textarea_rows' => 10,
					'media_buttons' => false,
					'teeny' => true,
					'quicktags' => array( 'buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,close' ),
				));
				?>
				<p class="description"><?php _e( 'Leave empty to use the address from plugin settings. Set this to preserve historical addresses for audit purposes.', 'ppi-invoicing' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Invoice Type & Data meta box callback
 */
function ppi_invoice_type_callback( $post ) {
	$invoice_type = get_post_meta( $post->ID, '_ppi_invoice_type', true );
	if ( empty( $invoice_type ) ) {
		$invoice_type = 'project';
	}

	$projects = get_post_meta( $post->ID, '_ppi_projects', true );
	if ( ! is_array( $projects ) ) {
		$projects = array();
	}

	$itemized = get_post_meta( $post->ID, '_ppi_itemized', true );
	if ( ! is_array( $itemized ) ) {
		$itemized = array();
	}
	?>

	<div class="ppi-invoice-type-selector">
		<h4><?php _e( 'Invoice Type', 'ppi-invoicing' ); ?></h4>
		<label>
			<input type="radio" name="ppi_invoice_type" value="project" <?php checked( $invoice_type, 'project' ); ?>>
			<?php _e( 'Project-based (with time entries)', 'ppi-invoicing' ); ?>
		</label>
		<label>
			<input type="radio" name="ppi_invoice_type" value="itemized" <?php checked( $invoice_type, 'itemized' ); ?>>
			<?php _e( 'Itemized', 'ppi-invoicing' ); ?>
		</label>
		<label>
			<input type="radio" name="ppi_invoice_type" value="external" <?php checked( $invoice_type, 'external' ); ?>>
			<?php _e( 'External (use post content)', 'ppi-invoicing' ); ?>
		</label>
	</div>

	<!-- Project-based invoice -->
	<div id="ppi-projects-container" class="ppi-invoice-data-container" style="<?php echo $invoice_type !== 'project' ? 'display:none;' : ''; ?>">
		<h4><?php _e( 'Projects', 'ppi-invoicing' ); ?></h4>
		<div id="ppi-projects-list">
			<?php
			if ( ! empty( $projects ) ) {
				foreach ( $projects as $index => $project ) {
					ppi_render_project_row( $index, $project );
				}
			}
			?>
		</div>
		<button type="button" class="button ppi-add-project"><?php _e( '+ Add Project', 'ppi-invoicing' ); ?></button>
	</div>

	<!-- Itemized invoice -->
	<div id="ppi-itemized-container" class="ppi-invoice-data-container" style="<?php echo $invoice_type !== 'itemized' ? 'display:none;' : ''; ?>">
		<h4><?php _e( 'Line Items', 'ppi-invoicing' ); ?></h4>
		<div id="ppi-itemized-list">
			<?php
			if ( ! empty( $itemized ) ) {
				foreach ( $itemized as $index => $item ) {
					ppi_render_itemized_row( $index, $item );
				}
			}
			?>
		</div>
		<button type="button" class="button ppi-add-itemized"><?php _e( '+ Add Line Item', 'ppi-invoicing' ); ?></button>
	</div>

	<!-- External invoice -->
	<div id="ppi-external-container" class="ppi-invoice-data-container" style="<?php echo $invoice_type !== 'external' ? 'display:none;' : ''; ?>">
		<p class="description"><?php _e( 'Use the main content editor above to add invoice details.', 'ppi-invoicing' ); ?></p>
	</div>

	<!-- Template for new project -->
	<script type="text/template" id="ppi-project-template">
		<?php ppi_render_project_row( '{{INDEX}}', array() ); ?>
	</script>

	<!-- Template for new time entry -->
	<script type="text/template" id="ppi-time-entry-template">
		<?php ppi_render_time_entry_row( '{{PROJECT_INDEX}}', '{{ENTRY_INDEX}}', array() ); ?>
	</script>

	<!-- Template for new itemized row -->
	<script type="text/template" id="ppi-itemized-template">
		<?php ppi_render_itemized_row( '{{INDEX}}', array() ); ?>
	</script>
	<?php
}

/**
 * Render a project row
 */
function ppi_render_project_row( $index, $project = array() ) {
	$project = wp_parse_args( $project, array(
		'display_project_name' => true,
		'project_name' => '',
		'project_name_dropdown' => 'none',
		'project_rate' => '',
		'time_entries' => array()
	));
	?>
	<div class="ppi-project-row" data-index="<?php echo esc_attr( $index ); ?>">
		<div class="ppi-project-header">
			<h5><?php _e( 'Project', 'ppi-invoicing' ); ?> #<span class="project-number"><?php echo (int)$index + 1; ?></span></h5>
			<button type="button" class="button button-small ppi-remove-project"><?php _e( 'Remove Project', 'ppi-invoicing' ); ?></button>
		</div>
		<table class="form-table">
			<tr>
				<th><label><?php _e( 'Display Project Name', 'ppi-invoicing' ); ?></label></th>
				<td>
					<input type="checkbox" name="ppi_projects[<?php echo esc_attr( $index ); ?>][display_project_name]" value="1" <?php checked( $project['display_project_name'], true ); ?>>
				</td>
			</tr>
			<tr>
				<th><label><?php _e( 'Project Name', 'ppi-invoicing' ); ?></label></th>
				<td><input type="text" name="ppi_projects[<?php echo esc_attr( $index ); ?>][project_name]" value="<?php echo esc_attr( $project['project_name'] ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label><?php _e( 'Project Name Dropdown', 'ppi-invoicing' ); ?></label></th>
				<td>
					<select name="ppi_projects[<?php echo esc_attr( $index ); ?>][project_name_dropdown]">
						<option value="none" <?php selected( $project['project_name_dropdown'], 'none' ); ?>><?php _e( 'Use custom name above', 'ppi-invoicing' ); ?></option>
						<option value="website" <?php selected( $project['project_name_dropdown'], 'website' ); ?>><?php _e( 'Website', 'ppi-invoicing' ); ?></option>
						<option value="design" <?php selected( $project['project_name_dropdown'], 'design' ); ?>><?php _e( 'Design', 'ppi-invoicing' ); ?></option>
						<option value="development" <?php selected( $project['project_name_dropdown'], 'development' ); ?>><?php _e( 'Development', 'ppi-invoicing' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label><?php _e( 'Project Rate Override', 'ppi-invoicing' ); ?></label></th>
				<td>
					<input type="number" step="0.01" name="ppi_projects[<?php echo esc_attr( $index ); ?>][project_rate]" value="<?php echo esc_attr( $project['project_rate'] ); ?>" class="small-text">
					<span class="description"><?php _e( 'Leave empty to use client standard rate', 'ppi-invoicing' ); ?></span>
				</td>
			</tr>
		</table>

		<div class="ppi-time-entries">
			<h6><?php _e( 'Time Entries', 'ppi-invoicing' ); ?></h6>
			<div class="ppi-time-entries-list" data-project-index="<?php echo esc_attr( $index ); ?>">
				<?php
				if ( ! empty( $project['time_entries'] ) ) {
					foreach ( $project['time_entries'] as $entry_index => $entry ) {
						ppi_render_time_entry_row( $index, $entry_index, $entry );
					}
				}
				?>
			</div>
			<button type="button" class="button button-small ppi-add-time-entry" data-project-index="<?php echo esc_attr( $index ); ?>"><?php _e( '+ Add Time Entry', 'ppi-invoicing' ); ?></button>
		</div>
	</div>
	<?php
}

/**
 * Render a time entry row
 */
function ppi_render_time_entry_row( $project_index, $entry_index, $entry = array() ) {
	$entry = wp_parse_args( $entry, array(
		'standard_date_format' => true,
		'date' => '',
		'freeform_date' => '',
		'description_of_tasks' => '',
		'hours' => '',
		'total' => ''
	));
	?>
	<div class="ppi-time-entry-row">
		<table class="form-table">
			<tr>
				<td style="width: 20%;">
					<label for="time-entry-std-date-<?php echo esc_attr($project_index.'-'.$entry_index); ?>">
						<input type="checkbox"
							   id="time-entry-std-date-<?php echo esc_attr($project_index.'-'.$entry_index); ?>"
							   name="ppi_projects[<?php echo esc_attr( $project_index ); ?>][time_entries][<?php echo esc_attr( $entry_index ); ?>][standard_date_format]"
							   value="1"
							   <?php checked( $entry['standard_date_format'], true ); ?>>
						<?php _e( 'Standard Date', 'ppi-invoicing' ); ?>
					</label>
					<label for="time-entry-date-<?php echo esc_attr($project_index.'-'.$entry_index); ?>" class="screen-reader-text">
						<?php _e('Date for time entry', 'ppi-invoicing'); ?>
					</label>
					<input type="text"
						   id="time-entry-date-<?php echo esc_attr($project_index.'-'.$entry_index); ?>"
						   name="ppi_projects[<?php echo esc_attr( $project_index ); ?>][time_entries][<?php echo esc_attr( $entry_index ); ?>][date]"
						   value="<?php echo esc_attr( $entry['date'] ); ?>"
						   class="ppi-datepicker"
						   placeholder="<?php _e( 'Date', 'ppi-invoicing' ); ?>"
						   aria-label="<?php _e('Date for time entry', 'ppi-invoicing'); ?>">
					<label for="time-entry-freeform-<?php echo esc_attr($project_index.'-'.$entry_index); ?>" class="screen-reader-text">
						<?php _e('Freeform date for time entry', 'ppi-invoicing'); ?>
					</label>
					<input type="text"
						   id="time-entry-freeform-<?php echo esc_attr($project_index.'-'.$entry_index); ?>"
						   name="ppi_projects[<?php echo esc_attr( $project_index ); ?>][time_entries][<?php echo esc_attr( $entry_index ); ?>][freeform_date]"
						   value="<?php echo esc_attr( $entry['freeform_date'] ); ?>"
						   placeholder="<?php _e( 'Freeform date', 'ppi-invoicing' ); ?>"
						   aria-label="<?php _e('Freeform date for time entry', 'ppi-invoicing'); ?>">
				</td>
				<td style="width: 40%;">
					<label for="time-entry-desc-<?php echo esc_attr($project_index.'-'.$entry_index); ?>">
						<?php _e('Description of tasks', 'ppi-invoicing'); ?>
					</label>
					<?php
					wp_editor( $entry['description_of_tasks'], 'time-entry-desc-' . $project_index . '-' . $entry_index, array(
						'textarea_name' => 'ppi_projects[' . $project_index . '][time_entries][' . $entry_index . '][description_of_tasks]',
						'textarea_rows' => 4,
						'media_buttons' => false,
						'teeny' => true,
						'quicktags' => array( 'buttons' => 'strong,em,link,ul,ol,li,close' ),
					));
					?>
				</td>
				<td style="width: 15%;">
					<label for="time-entry-hours-<?php echo esc_attr($project_index.'-'.$entry_index); ?>" class="screen-reader-text">
						<?php _e('Hours', 'ppi-invoicing'); ?>
					</label>
					<input type="number"
						   step="0.01"
						   id="time-entry-hours-<?php echo esc_attr($project_index.'-'.$entry_index); ?>"
						   name="ppi_projects[<?php echo esc_attr( $project_index ); ?>][time_entries][<?php echo esc_attr( $entry_index ); ?>][hours]"
						   value="<?php echo esc_attr( $entry['hours'] ); ?>"
						   placeholder="<?php _e( 'Hours', 'ppi-invoicing' ); ?>"
						   class="small-text"
						   aria-label="<?php _e('Hours', 'ppi-invoicing'); ?>">
				</td>
				<td style="width: 15%;">
					<label for="time-entry-total-<?php echo esc_attr($project_index.'-'.$entry_index); ?>" class="screen-reader-text">
						<?php _e('Total override', 'ppi-invoicing'); ?>
					</label>
					<input type="number"
						   step="0.01"
						   id="time-entry-total-<?php echo esc_attr($project_index.'-'.$entry_index); ?>"
						   name="ppi_projects[<?php echo esc_attr( $project_index ); ?>][time_entries][<?php echo esc_attr( $entry_index ); ?>][total]"
						   value="<?php echo esc_attr( $entry['total'] ); ?>"
						   placeholder="<?php _e( 'Total override', 'ppi-invoicing' ); ?>"
						   class="small-text"
						   aria-label="<?php _e('Total override', 'ppi-invoicing'); ?>">
				</td>
				<td style="width: 10%;">
					<button type="button"
							class="button button-small ppi-remove-time-entry"
							aria-label="<?php echo esc_attr(sprintf(__('Remove time entry %d', 'ppi-invoicing'), (int)$entry_index + 1)); ?>">
						<?php _e( 'Remove', 'ppi-invoicing' ); ?>
					</button>
				</td>
			</tr>
		</table>
	</div>
	<?php
}

/**
 * Render an itemized row
 */
function ppi_render_itemized_row( $index, $item = array() ) {
	$item = wp_parse_args( $item, array(
		'dates' => '',
		'description' => '',
		'hours' => '',
		'amount_override' => ''
	));
	?>
	<div class="ppi-itemized-row">
		<table class="form-table">
			<tr>
				<td style="width: 20%;">
					<label for="itemized-dates-<?php echo esc_attr($index); ?>" class="screen-reader-text">
						<?php _e('Date(s) for itemized entry', 'ppi-invoicing'); ?>
					</label>
					<input type="text"
						   id="itemized-dates-<?php echo esc_attr($index); ?>"
						   name="ppi_itemized[<?php echo esc_attr( $index ); ?>][dates]"
						   value="<?php echo esc_attr( $item['dates'] ); ?>"
						   placeholder="<?php _e( 'Date(s)', 'ppi-invoicing' ); ?>"
						   aria-label="<?php _e('Date(s) for itemized entry', 'ppi-invoicing'); ?>">
				</td>
				<td style="width: 40%;">
					<label for="itemized-desc-<?php echo esc_attr($index); ?>" class="screen-reader-text">
						<?php _e('Description for itemized entry', 'ppi-invoicing'); ?>
					</label>
					<textarea id="itemized-desc-<?php echo esc_attr($index); ?>"
							  name="ppi_itemized[<?php echo esc_attr( $index ); ?>][description]"
							  rows="2"
							  placeholder="<?php _e( 'Description', 'ppi-invoicing' ); ?>"
							  aria-label="<?php _e('Description for itemized entry', 'ppi-invoicing'); ?>"><?php echo esc_textarea( $item['description'] ); ?></textarea>
				</td>
				<td style="width: 15%;">
					<label for="itemized-hours-<?php echo esc_attr($index); ?>" class="screen-reader-text">
						<?php _e('Hours for itemized entry', 'ppi-invoicing'); ?>
					</label>
					<input type="number"
						   step="0.01"
						   id="itemized-hours-<?php echo esc_attr($index); ?>"
						   name="ppi_itemized[<?php echo esc_attr( $index ); ?>][hours]"
						   value="<?php echo esc_attr( $item['hours'] ); ?>"
						   placeholder="<?php _e( 'Hours', 'ppi-invoicing' ); ?>"
						   class="small-text"
						   aria-label="<?php _e('Hours for itemized entry', 'ppi-invoicing'); ?>">
				</td>
				<td style="width: 15%;">
					<label for="itemized-amount-<?php echo esc_attr($index); ?>" class="screen-reader-text">
						<?php _e('Amount for itemized entry', 'ppi-invoicing'); ?>
					</label>
					<input type="number"
						   step="0.01"
						   id="itemized-amount-<?php echo esc_attr($index); ?>"
						   name="ppi_itemized[<?php echo esc_attr( $index ); ?>][amount_override]"
						   value="<?php echo esc_attr( $item['amount_override'] ); ?>"
						   placeholder="<?php _e( 'Amount', 'ppi-invoicing' ); ?>"
						   class="small-text"
						   aria-label="<?php _e('Amount for itemized entry', 'ppi-invoicing'); ?>">
				</td>
				<td style="width: 10%;">
					<button type="button"
							class="button button-small ppi-remove-itemized"
							aria-label="<?php echo esc_attr(sprintf(__('Remove itemized entry %d', 'ppi-invoicing'), (int)$index + 1)); ?>">
						<?php _e( 'Remove', 'ppi-invoicing' ); ?>
					</button>
				</td>
			</tr>
		</table>
	</div>
	<?php
}

/**
 * Client Details meta box callback
 */
function ppi_client_details_callback( $post ) {
	wp_nonce_field( 'ppi_save_client_meta', 'ppi_client_meta_nonce' );

	$standard_rate = get_post_meta( $post->ID, '_ppi_standard_rate', true );
	$address = get_post_meta( $post->ID, '_ppi_address', true );
	?>

	<table class="form-table">
		<tr>
			<th><label for="ppi_standard_rate"><?php _e( 'Standard Rate (per hour)', 'ppi-invoicing' ); ?></label></th>
			<td>
				$<input type="number" step="0.01" name="ppi_standard_rate" id="ppi_standard_rate" value="<?php echo esc_attr( $standard_rate ); ?>" class="small-text">
			</td>
		</tr>
		<tr>
			<th><label for="ppi_address"><?php _e( 'Address', 'ppi-invoicing' ); ?></label></th>
			<td>
				<?php
				wp_editor( $address, 'ppi_address', array(
					'textarea_name' => 'ppi_address',
					'textarea_rows' => 10,
					'media_buttons' => false,
					'teeny' => true,
					'quicktags' => array( 'buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,close' ),
				));
				?>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Generate next invoice number
 */
function ppi_generate_invoice_number() {
	global $wpdb;

	// Get the highest invoice number from database
	$last_number = $wpdb->get_var(
		"SELECT meta_value
		FROM {$wpdb->postmeta}
		WHERE meta_key = '_ppi_invoice_number'
		AND meta_value LIKE 'INV-%'
		ORDER BY CAST(SUBSTRING(meta_value, 5) AS UNSIGNED) DESC
		LIMIT 1"
	);

	// Extract numeric part or start from 0
	if ( $last_number && preg_match( '/INV-(\d+)/', $last_number, $matches ) ) {
		$next_number = intval( $matches[1] ) + 1;
	} else {
		$next_number = 1;
	}

	// Format as INV-XXXX with zero padding
	return sprintf( 'INV-%04d', $next_number );
}

/**
 * Save invoice meta
 */
function ppi_save_invoice_meta( $post_id ) {
	// Check nonce
	if ( ! isset( $_POST['ppi_invoice_meta_nonce'] ) || ! wp_verify_nonce( $_POST['ppi_invoice_meta_nonce'], 'ppi_save_invoice_meta' ) ) {
		return;
	}

	// Check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check permissions
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Save client ID with validation
	if ( isset( $_POST['ppi_client_id'] ) ) {
		$client_id = absint( $_POST['ppi_client_id'] );
		if ( $client_id > 0 && get_post_type( $client_id ) === 'ppi_client' ) {
			update_post_meta( $post_id, '_ppi_client_id', $client_id );
		}
	}

	// Save status with validation
	if ( isset( $_POST['ppi_status'] ) ) {
		$status = sanitize_text_field( $_POST['ppi_status'] );
		$allowed_statuses = array_column( ppi_get_invoice_statuses(), 'value' );
		if ( in_array( $status, $allowed_statuses, true ) ) {
			update_post_meta( $post_id, '_ppi_status', $status );
		}
	}

	// Auto-generate invoice number if empty
	$invoice_number = isset( $_POST['ppi_invoice_number'] ) ? sanitize_text_field( $_POST['ppi_invoice_number'] ) : '';
	if ( empty( $invoice_number ) ) {
		$invoice_number = ppi_generate_invoice_number();
	}
	update_post_meta( $post_id, '_ppi_invoice_number', $invoice_number );

	// Save other fields
	$fields = array(
		'ppi_period_of_work' => 'sanitize_text_field',
		'ppi_date_of_invoice' => 'sanitize_text_field',
		'ppi_payment_due' => 'sanitize_text_field',
		'ppi_total' => 'floatval',
		'ppi_notes' => 'sanitize_textarea_field',
		'ppi_invoice_type' => 'sanitize_text_field',
		'ppi_from_address' => 'wp_kses_post'
	);

	foreach ( $fields as $field => $sanitize_callback ) {
		if ( isset( $_POST[ $field ] ) ) {
			update_post_meta( $post_id, '_' . $field, call_user_func( $sanitize_callback, $_POST[ $field ] ) );
		}
	}

	// Save projects
	if ( isset( $_POST['ppi_projects'] ) && is_array( $_POST['ppi_projects'] ) ) {
		$projects = array();
		foreach ( $_POST['ppi_projects'] as $project ) {
			// Validate that each project is an array
			if ( ! is_array( $project ) ) {
				continue;
			}

			$sanitized_project = array(
				'display_project_name' => isset( $project['display_project_name'] ) ? true : false,
				'project_name' => sanitize_text_field( $project['project_name'] ?? '' ),
				'project_name_dropdown' => sanitize_text_field( $project['project_name_dropdown'] ?? 'none' ),
				'project_rate' => floatval( $project['project_rate'] ?? 0 ),
				'time_entries' => array()
			);

			if ( isset( $project['time_entries'] ) && is_array( $project['time_entries'] ) ) {
				foreach ( $project['time_entries'] as $entry ) {
					// Validate that each entry is an array
					if ( ! is_array( $entry ) ) {
						continue;
					}

					$sanitized_project['time_entries'][] = array(
						'standard_date_format' => isset( $entry['standard_date_format'] ) ? true : false,
						'date' => sanitize_text_field( $entry['date'] ?? '' ),
						'freeform_date' => sanitize_text_field( $entry['freeform_date'] ?? '' ),
						'description_of_tasks' => wp_kses_post( $entry['description_of_tasks'] ?? '' ),
						'hours' => floatval( $entry['hours'] ?? 0 ),
						'total' => floatval( $entry['total'] ?? 0 )
					);
				}
			}

			$projects[] = $sanitized_project;
		}
		update_post_meta( $post_id, '_ppi_projects', $projects );
	} else {
		delete_post_meta( $post_id, '_ppi_projects' );
	}

	// Save itemized
	if ( isset( $_POST['ppi_itemized'] ) && is_array( $_POST['ppi_itemized'] ) ) {
		$itemized = array();
		foreach ( $_POST['ppi_itemized'] as $item ) {
			// Validate that each item is an array
			if ( ! is_array( $item ) ) {
				continue;
			}

			$itemized[] = array(
				'dates' => sanitize_text_field( $item['dates'] ?? '' ),
				'description' => sanitize_textarea_field( $item['description'] ?? '' ),
				'hours' => floatval( $item['hours'] ?? 0 ),
				'amount_override' => floatval( $item['amount_override'] ?? 0 )
			);
		}
		update_post_meta( $post_id, '_ppi_itemized', $itemized );
	} else {
		delete_post_meta( $post_id, '_ppi_itemized' );
	}
}
add_action( 'save_post_ppi_invoice', 'ppi_save_invoice_meta' );

/**
 * Save client meta
 */
function ppi_save_client_meta( $post_id ) {
	// Check nonce
	if ( ! isset( $_POST['ppi_client_meta_nonce'] ) || ! wp_verify_nonce( $_POST['ppi_client_meta_nonce'], 'ppi_save_client_meta' ) ) {
		return;
	}

	// Check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check permissions
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Save fields
	if ( isset( $_POST['ppi_standard_rate'] ) ) {
		update_post_meta( $post_id, '_ppi_standard_rate', floatval( $_POST['ppi_standard_rate'] ) );
	}

	if ( isset( $_POST['ppi_address'] ) ) {
		update_post_meta( $post_id, '_ppi_address', wp_kses_post( $_POST['ppi_address'] ) );
	}
}
add_action( 'save_post_ppi_client', 'ppi_save_client_meta' );
