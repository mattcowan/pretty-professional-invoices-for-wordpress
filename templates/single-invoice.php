<?php
/**
 * Single Invoice Template
 *
 * @package PPI_Invoicing
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

ppi_get_invoice_header();

$client_post = ppi_get_field('client');
$client = $client_post ? $client_post->ID : 0;
$rate = $client ? ppi_get_field('standard_rate', $client) : 0;

$final_total_override = ppi_get_field('total');

$total = 0;
$total_hours = 0;

// Get settings
$logo_id = get_option( 'ppi_invoice_logo', 0 );
$logo_url = $logo_id ? wp_get_attachment_url( $logo_id ) : PPI_INVOICING_PLUGIN_URL . 'assets/img/mnc4-logo.jpg';
$info_line = get_option( 'ppi_invoice_info_line', 'Graphic Designer & Web Developer' );
$email_1 = get_option( 'ppi_invoice_email_1', 'matt@mnc4.com' );
$email_2 = get_option( 'ppi_invoice_email_2', 'matthewneilcowan@gmail.com' );
$phone = get_option( 'ppi_invoice_phone', '573-424-0416' );
$phone_link = preg_replace('/[^0-9]/', '', $phone); // Strip formatting for tel: link
?>
	<header role="banner">
		<div class="logo">
			<img src="<?php echo esc_url( $logo_url ); ?>" class="logo-placeholder" alt="<?php echo esc_attr( $info_line ); ?>">
		</div>
		<div class="top-info"><?php echo esc_html( $info_line ); ?></div>
		<div class="bottom-info">
			<?php
			$contact_parts = array();
			if ( $email_1 ) {
				$contact_parts[] = '<a href="mailto:' . esc_attr( $email_1 ) . '" aria-label="' . esc_attr( sprintf( __( 'Email %s', 'ppi-invoicing' ), $email_1 ) ) . '">' . esc_html( $email_1 ) . '</a>';
			}
			if ( $email_2 ) {
				$contact_parts[] = '<a href="mailto:' . esc_attr( $email_2 ) . '" aria-label="' . esc_attr( sprintf( __( 'Email %s', 'ppi-invoicing' ), $email_2 ) ) . '">' . esc_html( $email_2 ) . '</a>';
			}
			if ( $phone ) {
				$contact_parts[] = '<a href="tel:' . esc_attr( $phone_link ) . '" aria-label="' . esc_attr( sprintf( __( 'Call %s', 'ppi-invoicing' ), $phone ) ) . '">' . esc_html( $phone ) . '</a>';
			}
			echo implode( ' &bull; ', $contact_parts );
			?>
		</div>
	</header>
<main id="main-content" role="main">
<div class="invoice-body">

	<h1 class="invoice-number">Invoice N<span class="fancy-o">o</span><span class="fancy-period">.</span> <?php echo esc_html( ppi_get_field('invoice_number') ); ?></h1>
	<div class="invoice-info row">
		<div class="col-4">
			<div class="invoice-lines">
				<h2 class="h5"><?php _e( 'Dates:', 'ppi-invoicing' ); ?></h2>
			<strong><?php _e( 'Period of Work:', 'ppi-invoicing' ); ?></strong> <?php echo esc_html( ppi_get_field('period_of_work') ); ?><br/>
			<strong><?php _e( 'Date of Invoice:', 'ppi-invoicing' ); ?></strong> <?php echo esc_html( ppi_get_field('date_of_invoice') ); ?><br/>
			<strong><?php _e( 'Payment Due:', 'ppi-invoicing' ); ?></strong> <?php echo esc_html( ppi_get_field('payment_due') ); ?>
			</div>
		</div>
		<div class="col-8">
			<div class="addresses row">
				<div class="to col-6">
					<h2 class="h5"><?php _e( 'To:', 'ppi-invoicing' ); ?></h2>
					<?php
					$address = ppi_get_field('address', $client);
					if ( $address ) {
						echo wp_kses_post( $address );
					}
					?>
				</div>
				<div class="from col-6">
					<h2 class="h5"><?php _e( 'From:', 'ppi-invoicing' ); ?></h2>
					<?php
					// Use invoice-specific address if set, otherwise fall back to global setting
					$from_address = ppi_get_field('from_address');
					if ( empty( $from_address ) ) {
						$from_address = get_option( 'ppi_invoice_from_address', "Matthew Cowan<br/>\n60 Meeting Hill Rd<br/>\nHillsborough, NH 03244" );
					}
					echo wp_kses_post( $from_address );
					?>
				</div>
			</div>
		</div>
	</div>

	<?php
	if ( ppi_get_field('invoice_type') == 'project' ) {
	// projects start
	$project_count = count( ppi_get_field('project') ?: array() );

	if( ppi_have_rows('project') ) {
	 	while ( ppi_the_row() ) {

	 		$project_hours = 0;
	 		$project_total = 0;

	 		if ( ppi_get_sub_field('project_rate') != '' ) {
	 			$project_rate = ppi_get_sub_field('project_rate');
	 		} else {
	 			$project_rate = $rate;
	 		}

	 		?>
			<div class="project-rows">
				<?php if ( ppi_get_sub_field('display_project_name') ) {
					$pnd = ppi_get_sub_field('project_name_dropdown');
					// Handle case where project_name_dropdown might be an array
					if ( is_array( $pnd ) ) {
						$pnd = isset( $pnd[0] ) ? $pnd[0] : 'none';
					}
					?>
					<?php if ( $pnd != 'none' ) {
					?><h3 class="h4 pt-3"><?php echo esc_html( ucfirst( $pnd ) ); ?></h3>
					<?php } else { ?>
					<h3 class="h4 pt-3"><?php echo esc_html( ppi_get_sub_field('project_name') ); ?></h3>
					<?php }
				}?>
				<table class="project-table table">
					<caption class="screen-reader-text">
						<?php
						if ( ppi_get_sub_field('display_project_name') ) {
							echo esc_html( ppi_get_sub_field('project_name') );
						} else {
							_e( 'Project Time Entries', 'ppi-invoicing' );
						}
						?>
					</caption>
					<thead>
						<tr class="project-header-row">
							<th scope="col" class="col-3"><?php _e( 'Date', 'ppi-invoicing' ); ?></th>
							<th scope="col" class="col-5"><?php _e( 'Description of Tasks', 'ppi-invoicing' ); ?></th>
							<th scope="col" class="col-2 text-right"><?php _e( 'Hours', 'ppi-invoicing' ); ?></th>
							<th scope="col" class="col-2 text-right"><?php _e( 'Total', 'ppi-invoicing' ); ?></th>
						</tr>
					</thead>
					<tbody>
				<?php
				// Store current row for nested repeater
				global $ppi_current_row, $ppi_row_index, $ppi_current_rows;
				$saved_row = $ppi_current_row;
				$saved_index = $ppi_row_index;
				$saved_rows = $ppi_current_rows;

				if( isset( $saved_row['time_entries'] ) && is_array( $saved_row['time_entries'] ) && ! empty( $saved_row['time_entries'] ) ) {
					$ppi_row_index = -1;
					$ppi_current_rows = $saved_row['time_entries'];

				 	while ( ppi_the_row() ) {
				 		?>
				 		<tr class="time-row">
					 		<td class="col-3">
					 			<?php if ( ppi_get_sub_field('standard_date_format') ) {
					 				echo esc_html( ppi_get_sub_field('date') );
					 			} else {
					 				echo esc_html( ppi_get_sub_field('freeform_date') );
					 			}
								?>
							</td>
							<td class="col-5">
								<?php echo wp_kses_post( ppi_get_sub_field('description_of_tasks') ); ?>
							</td>
							<td class="col-2 text-right font-monospace">
								<?php
								$hours = number_format( ppi_get_sub_field('hours'), 2 );
								if ( $hours != '' ) {
									$project_hours += $hours;
									echo esc_html( $hours );

								} else {
									echo esc_html( 'N/A' );
								}
								?>
							</td>
							<td class="col-2 text-right font-monospace">
								<?php if ( ppi_get_sub_field('total') ) {
									$project_total += ppi_get_sub_field('total');
									$total += ppi_get_sub_field('total');
									echo esc_html( number_format( ppi_get_sub_field('total'), 2 ) );
								} else {
									$day_total = $hours * $project_rate;
									$total += $day_total;
									$project_total += $day_total;
									echo esc_html( number_format( $day_total, 2 ) );
								} ?>
							</td>
						</tr>

				 <?php }

				// Restore parent row
				$ppi_current_row = $saved_row;
				$ppi_row_index = $saved_index;
				$ppi_current_rows = $saved_rows;
				} ?>
					</tbody>
					<tfoot>
						<tr class="project-header-row">
							<th scope="row" colspan="2" class="col-8 text-right"><?php _e( 'Project Total:', 'ppi-invoicing' ); ?></th>
							<td class="col-2 text-right font-monospace">
								<?php echo esc_html( number_format($project_hours,2) ); ?>
								<?php $total_hours += $project_hours; ?>
							</td>
							<td class="col-2 text-right font-monospace">
								$<?php echo esc_html( number_format($project_total,2) ); ?>
							</td>
						</tr>
					</tfoot>
				</table>
			</div>
		<?php } ?>

		<?php if ($project_count > 1) { ?>
					<h3 class="h4 pt-3 text-right"><?php _e( 'Total Hours:', 'ppi-invoicing' ); ?> <?php echo esc_html( number_format( $total_hours, 2 ) ); ?></h3>
		<?php } ?>
	<?php }
		// projects end
} else if ( ppi_get_field('invoice_type') == 'itemized' ) {
	$total = 0;
	?>
	<div class="project-rows">
		<table class="project-table table">
			<caption class="screen-reader-text"><?php _e( 'Itemized Invoice Entries', 'ppi-invoicing' ); ?></caption>
			<thead>
				<tr class="project-header-row">
					<th scope="col" class="col-3"><?php _e( 'Date(s)', 'ppi-invoicing' ); ?></th>
					<th scope="col" class="col-5"><?php _e( 'Description', 'ppi-invoicing' ); ?></th>
					<th scope="col" class="col-2 text-right"><?php _e( 'Hours', 'ppi-invoicing' ); ?></th>
					<th scope="col" class="col-2 text-right"><?php _e( 'Total', 'ppi-invoicing' ); ?></th>
				</tr>
			</thead>
			<tbody>

		<?php if ( ppi_have_rows('itemized') ) {

			$project_hours = 0;

              while( ppi_the_row() ) {
               ?>
               	<tr class="time-row">
               		<td class="col-3">
               			<?php echo esc_html( ppi_get_sub_field('dates') ); ?>
               		</td>
               		<td class="col-5">
               			<?php echo esc_html( ppi_get_sub_field('description') ); ?>
               		</td>
               		<td class="col-2 text-right font-monospace">
               			<?php
               				echo esc_html( number_format( ppi_get_sub_field('hours'), 2 ) );
               				$project_hours += floatval( ppi_get_sub_field('hours') );
               			?>
               		</td>
               		<td class="col-2 text-right font-monospace">
               			<?php if ( ppi_get_sub_field('amount_override') != '' ) {
               				echo esc_html( number_format( ppi_get_sub_field('amount_override'), 2 ) );
               				$total += ppi_get_sub_field('amount_override');
               			} else {
               				$line_total = ppi_get_sub_field('hours') * $rate;
               				echo esc_html( number_format( $line_total, 2 ) );
               				$total += floatval( $line_total );
               			} ?>
               		</td>
               	</tr>
               <?php
              }
              ?>
			</tbody>
			<tfoot>
              <tr class="project-header-row">
              	<th scope="row" colspan="2" class="col-8 text-right"><?php _e( 'Total:', 'ppi-invoicing' ); ?></th>
              	<td class="col-2 text-right font-monospace"><?php echo esc_html( number_format($project_hours,2) ); ?></td>
              	<td class="col-2 text-right font-monospace"><?php echo esc_html( number_format($total,2) ); ?></td>
              </tr>
			</tfoot>
              <?php
		} ?>
		</table>
	</div>
	<?php
} else if ( ppi_get_field('invoice_type') == 'external' ) {
	// External invoice - use post content
	the_content();
}
	?>


	<?php if ( $final_total_override != '' ) { ?>
		<h2 class="h3 <?php if ( $final_total_override != $total ) { ?>screen-red <?php } ?> final-total"><?php _e( 'Total:', 'ppi-invoicing' ); ?> $<?php echo esc_html( number_format( $final_total_override, 2 ) ); ?></h2>
	<?php } else { ?>
		<h2 class="h3 final-total"><?php _e( 'Total:', 'ppi-invoicing' ); ?> $<?php echo esc_html( number_format( $total, 2 ) ); ?></h2>
	<?php } ?>

	<?php if ( ppi_get_field('notes') ) {
		?><div class="notes"><?php echo esc_html( ppi_get_field('notes') ); ?></div><?php
	} ?>
</div>
</main>

<footer role="contentinfo">
	<p class="made"><?php echo esc_html( get_option( 'ppi_invoice_footer_text', 'Made with love and maple syrup in Hillsborough, NH' ) ); ?></p>
</footer>

<?php wp_footer(); ?>
</body>
</html>
