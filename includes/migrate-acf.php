<?php
/**
 * ACF Migration Tool (TEMPORARY)
 *
 * Remove this file after migration is complete.
 *
 * @package PPI_Invoicing
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add migration submenu page
 */
function ppi_add_migration_page() {
	add_submenu_page(
		'edit.php?post_type=ppi_invoice',
		__( 'Migrate from ACF', 'ppi-invoicing' ),
		__( 'Migrate from ACF', 'ppi-invoicing' ),
		'manage_options',
		'ppi-migrate-acf',
		'ppi_render_migration_page'
	);
}
add_action( 'admin_menu', 'ppi_add_migration_page' );

/**
 * Render migration page
 */
function ppi_render_migration_page() {
	// Check user permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.', 'ppi-invoicing' ) );
	}

	// Handle form submission
	$message = '';
	$error = '';

	if ( isset( $_POST['ppi_migrate_invoices'] ) && check_admin_referer( 'ppi_migrate_acf' ) ) {
		$result = ppi_migrate_invoice_data();
		$message = $result['message'];
		if ( $result['error'] ) {
			$error = $result['error'];
		}
	}

	if ( isset( $_POST['ppi_migrate_clients'] ) && check_admin_referer( 'ppi_migrate_acf' ) ) {
		$result = ppi_migrate_client_data();
		$message = $result['message'];
		if ( $result['error'] ) {
			$error = $result['error'];
		}
	}

	?>
	<div class="wrap">
		<h1><?php _e( 'Migrate from ACF', 'ppi-invoicing' ); ?></h1>

		<div class="notice notice-warning">
			<p><strong><?php _e( 'Important:', 'ppi-invoicing' ); ?></strong> <?php _e( 'Backup your database before running migration!', 'ppi-invoicing' ); ?></p>
		</div>

		<?php if ( $message ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo esc_html( $message ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( $error ) : ?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo esc_html( $error ); ?></p>
			</div>
		<?php endif; ?>

		<div class="card" style="max-width: 800px;">
			<h2><?php _e( 'Migrate Invoices', 'ppi-invoicing' ); ?></h2>
			<p><?php _e( 'This will migrate all invoices from the old ACF-based system to the plugin\'s native meta fields.', 'ppi-invoicing' ); ?></p>
			<ul>
				<li><?php _e( 'Migrates all simple fields (invoice number, dates, status, etc.)', 'ppi-invoicing' ); ?></li>
				<li><?php _e( 'Converts ACF repeater fields (projects, time entries, itemized) to arrays', 'ppi-invoicing' ); ?></li>
				<li><?php _e( 'Changes post type from invoice_2025 to ppi_invoice', 'ppi-invoicing' ); ?></li>
			</ul>

			<form method="post" onsubmit="return confirm('<?php _e( 'Are you sure you want to migrate all invoices? Make sure you have backed up your database!', 'ppi-invoicing' ); ?>');">
				<?php wp_nonce_field( 'ppi_migrate_acf' ); ?>
				<p>
					<input type="submit" name="ppi_migrate_invoices" class="button button-primary" value="<?php _e( 'Migrate All Invoices', 'ppi-invoicing' ); ?>">
				</p>
			</form>
		</div>

		<div class="card" style="max-width: 800px; margin-top: 20px;">
			<h2><?php _e( 'Migrate Clients', 'ppi-invoicing' ); ?></h2>
			<p><?php _e( 'This will migrate all clients from the old ACF-based system to the plugin\'s native meta fields.', 'ppi-invoicing' ); ?></p>
			<ul>
				<li><?php _e( 'Migrates standard rate and address fields', 'ppi-invoicing' ); ?></li>
				<li><?php _e( 'Changes post type from client to ppi_client', 'ppi-invoicing' ); ?></li>
			</ul>

			<form method="post" onsubmit="return confirm('<?php _e( 'Are you sure you want to migrate all clients? Make sure you have backed up your database!', 'ppi-invoicing' ); ?>');">
				<?php wp_nonce_field( 'ppi_migrate_acf' ); ?>
				<p>
					<input type="submit" name="ppi_migrate_clients" class="button button-primary" value="<?php _e( 'Migrate All Clients', 'ppi-invoicing' ); ?>">
				</p>
			</form>
		</div>

		<div class="card" style="max-width: 800px; margin-top: 20px; border-left: 4px solid #d63638;">
			<h2><?php _e( 'After Migration', 'ppi-invoicing' ); ?></h2>
			<ol>
				<li><?php _e( 'Test your invoices and clients thoroughly', 'ppi-invoicing' ); ?></li>
				<li><?php _e( 'View/edit invoices in admin and on frontend', 'ppi-invoicing' ); ?></li>
				<li><?php _e( 'Once confirmed working, delete this file: includes/migrate-acf.php', 'ppi-invoicing' ); ?></li>
				<li><?php _e( 'Remove the require line from ppi-invoicing-system.php', 'ppi-invoicing' ); ?></li>
			</ol>
		</div>
	</div>
	<?php
}

/**
 * Migrate invoice data from ACF to native meta
 */
function ppi_migrate_invoice_data() {
	// Check if ACF is active
	if ( ! function_exists( 'get_field' ) ) {
		return array(
			'message' => '',
			'error' => __( 'ACF plugin is not active. Cannot migrate data.', 'ppi-invoicing' )
		);
	}

	$invoices = get_posts( array(
		'post_type'      => 'invoice_2025',
		'posts_per_page' => -1,
		'post_status'    => 'any'
	) );

	if ( empty( $invoices ) ) {
		return array(
			'message' => __( 'No invoices found to migrate.', 'ppi-invoicing' ),
			'error'   => ''
		);
	}

	$migrated = 0;
	$errors = array();

	foreach ( $invoices as $invoice ) {
		try {
			// Migrate simple fields
			$fields_map = array(
				'client'          => '_ppi_client_id',
				'invoice_number'  => '_ppi_invoice_number',
				'period_of_work'  => '_ppi_period_of_work',
				'date_of_invoice' => '_ppi_date_of_invoice',
				'payment_due'     => '_ppi_payment_due',
				'total'           => '_ppi_total',
				'status'          => '_ppi_status',
				'notes'           => '_ppi_notes',
				'invoice_type'    => '_ppi_invoice_type',
			);

			foreach ( $fields_map as $acf_field => $meta_key ) {
				$value = get_field( $acf_field, $invoice->ID );
				if ( $value !== false && $value !== '' ) {
					// Handle client field specially (convert post object to ID)
					if ( $acf_field === 'client' && is_object( $value ) ) {
						$value = $value->ID;
					}
					update_post_meta( $invoice->ID, $meta_key, $value );
				}
			}

			// Migrate projects (repeater field)
			if ( have_rows( 'project', $invoice->ID ) ) {
				$projects = array();
				while ( have_rows( 'project', $invoice->ID ) ) {
					the_row();
					$project = array(
						'display_project_name'  => get_sub_field( 'display_project_name' ),
						'project_name'          => get_sub_field( 'project_name' ),
						'project_name_dropdown' => get_sub_field( 'project_name_dropdown' ),
						'project_rate'          => get_sub_field( 'project_rate' ),
						'time_entries'          => array()
					);

					if ( have_rows( 'time_entries' ) ) {
						while ( have_rows( 'time_entries' ) ) {
							the_row();
							$project['time_entries'][] = array(
								'standard_date_format' => get_sub_field( 'standard_date_format' ),
								'date'                 => get_sub_field( 'date' ),
								'freeform_date'        => get_sub_field( 'freeform_date' ),
								'description_of_tasks' => get_sub_field( 'description_of_tasks' ),
								'hours'                => get_sub_field( 'hours' ),
								'total'                => get_sub_field( 'total' )
							);
						}
					}

					$projects[] = $project;
				}
				update_post_meta( $invoice->ID, '_ppi_projects', $projects );
			}

			// Migrate itemized (repeater field)
			if ( have_rows( 'itemized', $invoice->ID ) ) {
				$itemized = array();
				while ( have_rows( 'itemized', $invoice->ID ) ) {
					the_row();
					$itemized[] = array(
						'dates'           => get_sub_field( 'dates' ),
						'description'     => get_sub_field( 'description' ),
						'hours'           => get_sub_field( 'hours' ),
						'amount_override' => get_sub_field( 'amount_override' )
					);
				}
				update_post_meta( $invoice->ID, '_ppi_itemized', $itemized );
			}

			// Change post type to new plugin post type
			wp_update_post( array(
				'ID'        => $invoice->ID,
				'post_type' => 'ppi_invoice'
			) );

			$migrated++;

		} catch ( Exception $e ) {
			$errors[] = sprintf(
				__( 'Error migrating invoice #%d: %s', 'ppi-invoicing' ),
				$invoice->ID,
				$e->getMessage()
			);
		}
	}

	$message = sprintf(
		__( 'Successfully migrated %d of %d invoices!', 'ppi-invoicing' ),
		$migrated,
		count( $invoices )
	);

	return array(
		'message' => $message,
		'error'   => ! empty( $errors ) ? implode( '<br>', $errors ) : ''
	);
}

/**
 * Migrate client data from ACF to native meta
 */
function ppi_migrate_client_data() {
	// Check if ACF is active
	if ( ! function_exists( 'get_field' ) ) {
		return array(
			'message' => '',
			'error' => __( 'ACF plugin is not active. Cannot migrate data.', 'ppi-invoicing' )
		);
	}

	$clients = get_posts( array(
		'post_type'      => 'client',
		'posts_per_page' => -1,
		'post_status'    => 'any'
	) );

	if ( empty( $clients ) ) {
		return array(
			'message' => __( 'No clients found to migrate.', 'ppi-invoicing' ),
			'error'   => ''
		);
	}

	$migrated = 0;
	$errors = array();

	foreach ( $clients as $client ) {
		try {
			$standard_rate = get_field( 'standard_rate', $client->ID );
			$address = get_field( 'address', $client->ID );

			if ( $standard_rate ) {
				update_post_meta( $client->ID, '_ppi_standard_rate', $standard_rate );
			}
			if ( $address ) {
				update_post_meta( $client->ID, '_ppi_address', $address );
			}

			// Change post type
			wp_update_post( array(
				'ID'        => $client->ID,
				'post_type' => 'ppi_client'
			) );

			$migrated++;

		} catch ( Exception $e ) {
			$errors[] = sprintf(
				__( 'Error migrating client #%d: %s', 'ppi-invoicing' ),
				$client->ID,
				$e->getMessage()
			);
		}
	}

	$message = sprintf(
		__( 'Successfully migrated %d of %d clients!', 'ppi-invoicing' ),
		$migrated,
		count( $clients )
	);

	return array(
		'message' => $message,
		'error'   => ! empty( $errors ) ? implode( '<br>', $errors ) : ''
	);
}
