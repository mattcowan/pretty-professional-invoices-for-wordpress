<?php
/**
 * Archive Invoice Template
 *
 * @package PPI_Invoicing
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// Get configured statuses
$configured_statuses = ppi_get_invoice_statuses();

// Initialize status totals dynamically
$status_totals = array();
foreach ( $configured_statuses as $status_config ) {
	$status_totals[ $status_config['value'] ] = 0;
}

// Calculate grand totals (statuses that count towards total)
$grand_total = 0;
$everything_total = 0;

// Build client breakdown data
$client_data = array();
$posts_data = array(); // Store post data for main loop

// Get all clients for filter dropdown
$all_clients = get_posts(array(
	'post_type' => 'ppi_client',
	'posts_per_page' => -1,
	'orderby' => 'title',
	'order' => 'ASC'
));
?>

<div class="container">
<div class="row">
	<div class="col-lg-8">
		<!-- Filter Bar -->
		<div class="invoice-filters mb-4">
			<h2 class="h5 mb-3"><?php _e( 'Filter Invoices', 'ppi-invoicing' ); ?></h2>
			<div class="filter-grid">
				<div class="filter-group">
					<label for="filter-client"><?php _e( 'Client', 'ppi-invoicing' ); ?></label>
					<select id="filter-client" class="form-control form-control-sm">
						<option value=""><?php _e( 'All Clients', 'ppi-invoicing' ); ?></option>
						<?php foreach ($all_clients as $client) : ?>
							<option value="<?php echo esc_attr($client->ID); ?>"><?php echo esc_html($client->post_title); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="filter-group">
					<label for="filter-status"><?php _e( 'Status', 'ppi-invoicing' ); ?></label>
					<div class="status-checkboxes">
						<?php foreach ( $configured_statuses as $status_config ) : ?>
							<label class="status-checkbox-label">
								<input type="checkbox" class="status-filter-checkbox" value="<?php echo esc_attr($status_config['value']); ?>" checked>
								<i class="<?php echo esc_attr($status_config['icon']); ?>" aria-hidden="true"></i>
								<?php echo esc_html($status_config['label']); ?>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="filter-group">
					<label for="filter-date-start"><?php _e( 'Date From', 'ppi-invoicing' ); ?></label>
					<input type="date" id="filter-date-start" class="form-control form-control-sm">
				</div>

				<div class="filter-group">
					<label for="filter-date-end"><?php _e( 'Date To', 'ppi-invoicing' ); ?></label>
					<input type="date" id="filter-date-end" class="form-control form-control-sm">
				</div>

				<div class="filter-group">
					<label for="filter-amount-min"><?php _e( 'Amount Min', 'ppi-invoicing' ); ?></label>
					<input type="number" id="filter-amount-min" class="form-control form-control-sm" placeholder="0.00" step="0.01" min="0">
				</div>

				<div class="filter-group">
					<label for="filter-amount-max"><?php _e( 'Amount Max', 'ppi-invoicing' ); ?></label>
					<input type="number" id="filter-amount-max" class="form-control form-control-sm" placeholder="0.00" step="0.01" min="0">
				</div>

				<div class="filter-group">
					<label for="filter-search"><?php _e( 'Invoice Number', 'ppi-invoicing' ); ?></label>
					<input type="text" id="filter-search" class="form-control form-control-sm" placeholder="<?php _e( 'Search...', 'ppi-invoicing' ); ?>">
				</div>

				<div class="filter-group filter-actions">
					<button id="clear-filters" class="btn btn-secondary btn-sm"><?php _e( 'Clear Filters', 'ppi-invoicing' ); ?></button>
				</div>

				<?php if (is_user_logged_in()) : ?>
				<div class="filter-group filter-actions">
					<div class="export-dropdown" style="position: relative; display: inline-block;">
						<button id="export-dropdown-toggle" class="btn btn-success btn-sm" aria-haspopup="true" aria-expanded="false">
							<?php _e( 'Export', 'ppi-invoicing' ); ?> <span class="caret">&#9662;</span>
						</button>
						<div id="export-dropdown-menu" class="export-menu" style="display: none; position: absolute; background: white; border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.1); z-index: 1000; min-width: 160px; margin-top: 2px;">
							<a href="#" class="export-option" data-format="csv" style="display: block; padding: 8px 12px; text-decoration: none; color: #333; border-bottom: 1px solid #eee;">
								<?php _e( 'Export as CSV', 'ppi-invoicing' ); ?>
							</a>
							<a href="#" class="export-option" data-format="xlsx" style="display: block; padding: 8px 12px; text-decoration: none; color: #333;">
								<?php _e( 'Export as Excel', 'ppi-invoicing' ); ?>
							</a>
						</div>
					</div>
				</div>
				<?php endif; ?>
			</div>
			<div class="filter-summary mt-2">
				<span id="visible-count" class="font-weight-bold"></span>
				<span id="export-status" class="ml-2" style="color: #666;"></span>
			</div>
		</div>

		<?php if (is_user_logged_in()) : ?>
		<div class="invoice-controls mb-3">
			<button id="bulk-save" class="btn btn-primary"><?php _e( 'Save Status Changes', 'ppi-invoicing' ); ?></button>
			<span id="save-status" class="ml-2" role="status" aria-live="polite" aria-atomic="true"></span>
		</div>
		<?php endif; ?>

		<table class="invoice-table" id="invoice-table">
			<caption class="screen-reader-text"><?php _e( 'Invoice List with Status Controls', 'ppi-invoicing' ); ?></caption>
			<thead>
				<tr>
					<th scope="col" class="sortable" data-sort="status">
						<?php _e( 'Status', 'ppi-invoicing' ); ?>
						<span class="sort-indicator" aria-hidden="true"></span>
					</th>
					<th scope="col" class="sortable" data-sort="date">
						<?php _e( 'Date', 'ppi-invoicing' ); ?>
						<span class="sort-indicator" aria-hidden="true"></span>
					</th>
					<th scope="col" class="sortable" data-sort="invoice">
						<?php _e( 'Invoice', 'ppi-invoicing' ); ?>
						<span class="sort-indicator" aria-hidden="true"></span>
					</th>
					<th scope="col" class="text-right sortable" data-sort="amount">
						<?php _e( 'Amount', 'ppi-invoicing' ); ?>
						<span class="sort-indicator" aria-hidden="true"></span>
					</th>
					<th scope="col" class="text-center no-print"><?php _e( 'Actions', 'ppi-invoicing' ); ?></th>
				</tr>
			</thead>
			<tbody>
<?php
// First loop: collect all data for calculations
while ( have_posts() ) : the_post();
	$post_data = array(
		'ID' => get_the_ID(),
		'post_name' => $post->post_name,
		'permalink' => get_the_permalink($post->ID),
		'status' => ppi_get_field('status', $post->ID),
		'date_of_invoice' => ppi_get_field('date_of_invoice', $post->ID),
		'total' => ppi_get_field('total', $post->ID),
		'client' => ppi_get_field('client', $post->ID)
	);

	// Calculate amounts for client breakdown
	if ($post_data['client']) {
		$client_id = $post_data['client']->ID;
		$client_name = $post_data['client']->post_title;

		if (!isset($client_data[$client_id])) {
			$client_data[$client_id] = array(
				'name' => $client_name,
				'status_totals' => array(),
				'total' => 0,
				'count' => 0
			);
			// Initialize status totals for this client
			foreach ( $configured_statuses as $status_config ) {
				$client_data[$client_id]['status_totals'][ $status_config['value'] ] = 0;
			}
		}

		$amount = 0;
		if ($post_data['total']) {
			$amount = $post_data['total'];
		} else {
			// Calculate from project hours if no total set
			$client_rate = ppi_get_field('standard_rate', $client_id);
			$projects = ppi_get_field('project', $post_data['ID']);
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
					$amount += $project_hours * $project_rate;
				}
			}
		}

		// Add to status totals
		$current_status = $post_data['status'];
		if ( isset( $client_data[$client_id]['status_totals'][ $current_status ] ) ) {
			$client_data[$client_id]['status_totals'][ $current_status ] += $amount;
		}

		// Find if this status counts towards total
		$counts_towards_total = false;
		foreach ( $configured_statuses as $status_config ) {
			if ( $status_config['value'] === $current_status && $status_config['count_towards_total'] ) {
				$counts_towards_total = true;
				break;
			}
		}

		if ( $counts_towards_total ) {
			$client_data[$client_id]['total'] += $amount;
		}

		// Always count invoice in the count (regardless of status)
		$client_data[$client_id]['count']++;
	}

	$posts_data[] = $post_data;
endwhile;

// Second loop: display the table
foreach ($posts_data as $post_data) : ?>
	<tr data-post-id="<?php echo esc_attr( $post_data['ID'] ); ?>"
		data-status="<?php echo esc_attr( $post_data['status'] ); ?>"
		data-date="<?php echo esc_attr( $post_data['date_of_invoice'] ); ?>"
		data-invoice="<?php echo esc_attr( $post_data['post_name'] ); ?>"
		data-amount="<?php echo esc_attr( $post_data['total'] ?: 0 ); ?>"
		data-client-id="<?php echo esc_attr( $post_data['client'] ? $post_data['client']->ID : '' ); ?>"
		data-client-name="<?php echo esc_attr( $post_data['client'] ? $post_data['client']->post_title : '' ); ?>">
		<td class="text-center"><?php
			$current_status = $post_data['status'];

			// Find current status config
			$current_status_config = null;
			foreach ( $configured_statuses as $status_config ) {
				if ( $status_config['value'] === $current_status ) {
					$current_status_config = $status_config;
					break;
				}
			}

			// Display icon
			if ( $current_status_config ) {
				echo '<i class="text-' . esc_attr( $current_status_config['color'] ) . ' ' . esc_attr( $current_status_config['icon'] ) . '" aria-hidden="true"></i>';
				echo '<span class="screen-reader-text">' . esc_html( $current_status_config['label'] ) . '</span>';
			}

			if (is_user_logged_in()) : ?>
				<label for="status-<?php echo esc_attr( $post_data['ID'] ); ?>" class="screen-reader-text">
					<?php echo esc_html( sprintf( __( 'Change status for invoice %s', 'ppi-invoicing' ), $post_data['post_name'] ) ); ?>
				</label>
				<select id="status-<?php echo esc_attr( $post_data['ID'] ); ?>"
						class="status-select form-control form-control-sm mt-1"
						data-post-id="<?php echo esc_attr( $post_data['ID'] ); ?>"
						aria-label="<?php echo esc_attr( sprintf( __( 'Change status for invoice %s', 'ppi-invoicing' ), $post_data['post_name'] ) ); ?>">
					<?php foreach ( $configured_statuses as $status_config ) : ?>
						<option value="<?php echo esc_attr( $status_config['value'] ); ?>" <?php selected( $current_status, $status_config['value'] ); ?>>
							<?php echo esc_html( $status_config['label'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php endif;
		?>
		</td>
		<td><?php echo esc_html( $post_data['date_of_invoice'] ); ?></td>
		<td><a <?php if ( $current_status_config && $current_status_config['color'] == 'muted' ) { echo 'class="text-secondary"'; } ?> href="<?php echo esc_url( $post_data['permalink'] ); ?>"><?php echo esc_html( $post_data['post_name'] ); ?></a></td>
		<td class="text-right font-monospace text-<?php echo esc_attr( $current_status_config ? $current_status_config['color'] : 'primary' ); ?> <?php echo ( $current_status_config && $current_status_config['count_towards_total'] ) ? 'font-weight-bold' : ''; ?>"><?php
			if ($post_data['total']) {
				echo esc_html( number_format($post_data['total'],2) );

				// Add to status totals
				if ( isset( $status_totals[ $current_status ] ) ) {
					$status_totals[ $current_status ] += $post_data['total'];
				}

				// Add to grand totals if this status counts
				if ( $current_status_config && $current_status_config['count_towards_total'] ) {
					$grand_total += $post_data['total'];
				}

				// Add to everything total
				$everything_total += $post_data['total'];
			} else {
				// Calculate from project hours
				$client = $post_data['client'];
				$client_rate = ($client && $client->ID) ? ppi_get_field('standard_rate',$client->ID) : 0;
				$calculated_pay = 0;
				$projects = ppi_get_field('project', $post_data['ID']);
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

				echo esc_html( number_format($calculated_pay,2) );

				// Add to status totals
				if ( isset( $status_totals[ $current_status ] ) ) {
					$status_totals[ $current_status ] += $calculated_pay;
				}

				// Add to grand totals if this status counts
				if ( $current_status_config && $current_status_config['count_towards_total'] ) {
					$grand_total += $calculated_pay;
				}

				// Add to everything total
				$everything_total += $calculated_pay;
			}

			?></td>
		<td class="text-center no-print">
			<div class="invoice-action-buttons">
				<a href="<?php echo esc_url( $post_data['permalink'] ); ?>"
				   class="action-btn action-btn-view"
				   title="<?php esc_attr_e( 'View Invoice', 'ppi-invoicing' ); ?>"
				   aria-label="<?php echo esc_attr( sprintf( __( 'View invoice %s', 'ppi-invoicing' ), $post_data['post_name'] ) ); ?>">
					<span aria-hidden="true">&#128065;</span>
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'print', '1', $post_data['permalink'] ) ); ?>"
				   class="action-btn action-btn-print"
				   title="<?php esc_attr_e( 'Print Invoice', 'ppi-invoicing' ); ?>"
				   aria-label="<?php echo esc_attr( sprintf( __( 'Print invoice %s', 'ppi-invoicing' ), $post_data['post_name'] ) ); ?>">
					<span aria-hidden="true">&#128424;</span>
				</a>
				<a href="<?php echo esc_url( $post_data['permalink'] ); ?>"
				   target="_blank"
				   class="action-btn action-btn-download"
				   title="<?php esc_attr_e( 'Download PDF', 'ppi-invoicing' ); ?>"
				   aria-label="<?php echo esc_attr( sprintf( __( 'Download PDF of invoice %s', 'ppi-invoicing' ), $post_data['post_name'] ) ); ?>">
					<span aria-hidden="true">&#128190;</span>
				</a>
			</div>
		</td>
	</tr>
<?php endforeach; ?>
			</tbody>
			<tfoot>
				<?php foreach ( $configured_statuses as $status_config ) : ?>
					<?php if ( isset( $status_totals[ $status_config['value'] ] ) && $status_totals[ $status_config['value'] ] > 0 ) : ?>
						<tr class="bg-sum">
							<td class="text-center">
								<i class="text-<?php echo esc_attr( $status_config['color'] ); ?> <?php echo esc_attr( $status_config['icon'] ); ?>" aria-hidden="true"></i>
								<span class="screen-reader-text"><?php echo esc_html( $status_config['label'] ); ?></span>
							</td>
							<td></td>
							<th scope="row"><?php echo esc_html( $status_config['label'] ); ?></th>
							<td class="text-right font-monospace"><?php echo esc_html( number_format( $status_totals[ $status_config['value'] ], 2 ) ); ?></td>
							<td class="no-print"></td>
						</tr>
					<?php endif; ?>
				<?php endforeach; ?>
				<tr class="bg-sum">
					<td class="text-center"><i class="ppi-pig" aria-hidden="true"></i><span class="screen-reader-text"><?php _e( 'Total (Counted Statuses)', 'ppi-invoicing' ); ?></span></td>
					<td></td>
					<th scope="row"><?php _e( 'Total (Counted Statuses)', 'ppi-invoicing' ); ?></th>
					<td class="text-right font-monospace"><?php echo esc_html( number_format($grand_total,2) ); ?></td>
					<td class="no-print"></td>
				</tr>
				<tr class="">
					<td class="text-center"><i class="ppi-pig" aria-hidden="true"></i><span class="screen-reader-text"><?php _e( 'Everything', 'ppi-invoicing' ); ?></span></td>
					<td></td>
					<th scope="row"><?php _e( 'Everything', 'ppi-invoicing' ); ?></th>
					<td class="text-right font-monospace"><?php echo esc_html( number_format($everything_total,2) ); ?></td>
					<td class="no-print"></td>
				</tr>
			</tfoot>
		</table>
	</div>

	<div class="col-lg-4">
		<div class="client-breakdown-report">
			<div class="sidebar-header">
				<h2 class="h4"><?php _e( '2025 Client Breakdown', 'ppi-invoicing' ); ?></h2>
				<button id="sidebar-toggle" class="btn btn-sm btn-link" aria-expanded="true" aria-controls="sidebar-content">
					<span class="toggle-icon" aria-hidden="true">&#9662;</span>
					<span class="screen-reader-text"><?php _e( 'Toggle sidebar', 'ppi-invoicing' ); ?></span>
				</button>
			</div>
			<div id="sidebar-content" class="sidebar-content">
			<?php if (!empty($client_data)) :
				// Calculate total revenue across all clients for percentage calculations
				$total_revenue = array_sum(array_column($client_data, 'total'));

				// Sort clients by total amount (highest first)
				uasort($client_data, function($a, $b) {
					return $b['total'] <=> $a['total'];
				});
			?>
			<div class="client-list">
				<?php foreach ($client_data as $client_id => $client) :
					$percentage = $total_revenue > 0 ? ($client['total'] / $total_revenue) * 100 : 0;
				?>
				<div class="client-item">
					<div class="client-header">
						<div class="client-name"><?php echo esc_html($client['name']); ?></div>
						<div class="client-percentage"><?php echo esc_html( number_format($percentage, 1) ); ?>%</div>
					</div>
					<?php if ($percentage > 0) : ?>
					<div class="percentage-bar" role="img" aria-label="<?php echo esc_attr( sprintf( __('%s%% of total revenue', 'ppi-invoicing'), number_format($percentage, 1) ) ); ?>">
						<div class="percentage-fill" style="width: <?php echo esc_attr( $percentage ); ?>%" aria-hidden="true"></div>
					</div>
					<?php endif; ?>
					<div class="client-stats">
						<div class="stat-row">
							<span class="stat-label"><?php _e( 'Invoices:', 'ppi-invoicing' ); ?></span>
							<span class="stat-value"><?php echo esc_html( $client['count'] ); ?></span>
						</div>
						<?php foreach ( $configured_statuses as $status_config ) : ?>
							<?php if ( isset( $client['status_totals'][ $status_config['value'] ] ) && $client['status_totals'][ $status_config['value'] ] > 0 ) : ?>
								<div class="stat-row">
									<span class="stat-label"><i class="<?php echo esc_attr( $status_config['icon'] ); ?>" aria-hidden="true"></i> <?php echo esc_html( $status_config['label'] ); ?>:</span>
									<span class="stat-value">$<?php echo esc_html( number_format( $client['status_totals'][ $status_config['value'] ], 2 ) ); ?></span>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
						<div class="stat-row total">
							<span class="stat-label font-weight-bold"><?php _e( 'Total:', 'ppi-invoicing' ); ?></span>
							<span class="stat-value font-weight-bold">$<?php echo esc_html( number_format($client['total'], 2) ); ?></span>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
			<?php else : ?>
			<p class="text-muted"><?php _e( 'No client data available for 2025.', 'ppi-invoicing' ); ?></p>
			<?php endif; ?>
			</div>
		</div>
	</div>
</div>
</div>

<?php get_footer(); ?>
