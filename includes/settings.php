<?php
/**
 * Settings Page
 *
 * @package PPI_Invoicing
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add settings page to admin menu
 */
function ppi_add_settings_page() {
	add_submenu_page(
		'edit.php?post_type=ppi_invoice',
		__( 'Invoice Settings', 'ppi-invoicing' ),
		__( 'Settings', 'ppi-invoicing' ),
		'manage_options',
		'ppi-invoice-settings',
		'ppi_render_settings_page'
	);
}
add_action( 'admin_menu', 'ppi_add_settings_page' );

/**
 * Enqueue media uploader scripts
 */
function ppi_enqueue_settings_scripts( $hook ) {
	if ( 'ppi_invoice_page_ppi-invoice-settings' !== $hook ) {
		return;
	}
	wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'ppi_enqueue_settings_scripts' );

/**
 * Register settings
 */
function ppi_register_settings() {
	register_setting( 'ppi_invoice_settings', 'ppi_invoice_logo', array(
		'sanitize_callback' => 'absint',
		'default' => 0
	));

	register_setting( 'ppi_invoice_settings', 'ppi_invoice_info_line', array(
		'sanitize_callback' => 'sanitize_text_field',
		'default' => 'Graphic Designer & Web Developer'
	));

	register_setting( 'ppi_invoice_settings', 'ppi_invoice_email_1', array(
		'sanitize_callback' => 'sanitize_email',
		'default' => ''
	));

	register_setting( 'ppi_invoice_settings', 'ppi_invoice_email_2', array(
		'sanitize_callback' => 'sanitize_email',
		'default' => ''
	));

	register_setting( 'ppi_invoice_settings', 'ppi_invoice_phone', array(
		'sanitize_callback' => 'sanitize_text_field',
		'default' => ''
	));

	register_setting( 'ppi_invoice_settings', 'ppi_invoice_footer_text', array(
		'sanitize_callback' => 'sanitize_text_field',
		'default' => 'Made with love and maple syrup in Hillsborough, NH'
	));

	register_setting( 'ppi_invoice_settings', 'ppi_invoice_from_address', array(
		'sanitize_callback' => 'wp_kses_post',
		'default' => "Matthew Cowan<br/>\n60 Meeting Hill Rd<br/>\nHillsborough, NH 03244"
	));

	register_setting( 'ppi_invoice_settings', 'ppi_invoice_statuses', array(
		'sanitize_callback' => 'ppi_sanitize_invoice_statuses',
		'default' => ppi_get_default_statuses()
	));
}
add_action( 'admin_init', 'ppi_register_settings' );

/**
 * Get default invoice statuses
 */
function ppi_get_default_statuses() {
	return array(
		array(
			'value' => 'progress',
			'label' => __( 'In Progress', 'ppi-invoicing' ),
			'icon' => 'ppi-atom',
			'color' => 'primary',
			'count_towards_total' => false
		),
		array(
			'value' => 'issued',
			'label' => __( 'Issued', 'ppi-invoicing' ),
			'icon' => 'ppi-rocket',
			'color' => 'secondary',
			'count_towards_total' => true
		),
		array(
			'value' => 'paid',
			'label' => __( 'Paid', 'ppi-invoicing' ),
			'icon' => 'ppi-check-circle',
			'color' => 'success',
			'count_towards_total' => true
		),
		array(
			'value' => 'abandoned',
			'label' => __( 'Abandoned', 'ppi-invoicing' ),
			'icon' => 'ppi-x',
			'color' => 'muted',
			'count_towards_total' => false
		)
	);
}

/**
 * Get invoice statuses from options, with fallback to defaults
 */
function ppi_get_invoice_statuses() {
	$statuses = get_option( 'ppi_invoice_statuses', ppi_get_default_statuses() );

	// Ensure we always have valid data
	if ( ! is_array( $statuses ) || empty( $statuses ) ) {
		$statuses = ppi_get_default_statuses();
	}

	return $statuses;
}

/**
 * Sanitize invoice statuses
 */
function ppi_sanitize_invoice_statuses( $statuses ) {
	if ( ! is_array( $statuses ) ) {
		return ppi_get_default_statuses();
	}

	$sanitized = array();

	foreach ( $statuses as $status ) {
		if ( ! is_array( $status ) || empty( $status['value'] ) || empty( $status['label'] ) ) {
			continue;
		}

		$sanitized[] = array(
			'value' => sanitize_key( $status['value'] ),
			'label' => sanitize_text_field( $status['label'] ),
			'icon' => sanitize_text_field( $status['icon'] ?? 'ppi-circle' ),
			'color' => sanitize_text_field( $status['color'] ?? 'primary' ),
			'count_towards_total' => ! empty( $status['count_towards_total'] )
		);
	}

	// Ensure we have at least one status
	if ( empty( $sanitized ) ) {
		return ppi_get_default_statuses();
	}

	return $sanitized;
}

/**
 * Render settings page
 */
function ppi_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Handle form submission
	if ( isset( $_POST['ppi_settings_nonce'] ) && wp_verify_nonce( $_POST['ppi_settings_nonce'], 'ppi_save_settings' ) ) {
		update_option( 'ppi_invoice_logo', absint( $_POST['ppi_invoice_logo'] ?? 0 ) );
		update_option( 'ppi_invoice_info_line', sanitize_text_field( $_POST['ppi_invoice_info_line'] ?? '' ) );
		update_option( 'ppi_invoice_email_1', sanitize_email( $_POST['ppi_invoice_email_1'] ?? '' ) );
		update_option( 'ppi_invoice_email_2', sanitize_email( $_POST['ppi_invoice_email_2'] ?? '' ) );
		update_option( 'ppi_invoice_phone', sanitize_text_field( $_POST['ppi_invoice_phone'] ?? '' ) );
		update_option( 'ppi_invoice_footer_text', sanitize_text_field( $_POST['ppi_invoice_footer_text'] ?? '' ) );
		update_option( 'ppi_invoice_from_address', wp_kses_post( $_POST['ppi_invoice_from_address'] ?? '' ) );

		// Handle statuses
		if ( isset( $_POST['ppi_invoice_statuses'] ) ) {
			update_option( 'ppi_invoice_statuses', ppi_sanitize_invoice_statuses( $_POST['ppi_invoice_statuses'] ) );
		}

		echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Settings saved.', 'ppi-invoicing' ) . '</p></div>';
	}

	// Get current values
	$logo_id = get_option( 'ppi_invoice_logo', 0 );
	$info_line = get_option( 'ppi_invoice_info_line', 'Graphic Designer & Web Developer' );
	$email_1 = get_option( 'ppi_invoice_email_1', '' );
	$email_2 = get_option( 'ppi_invoice_email_2', '' );
	$phone = get_option( 'ppi_invoice_phone', '' );
	$footer_text = get_option( 'ppi_invoice_footer_text', 'Made with love and maple syrup in Hillsborough, NH' );
	$from_address = get_option( 'ppi_invoice_from_address', "Matthew Cowan<br/>\n60 Meeting Hill Rd<br/>\nHillsborough, NH 03244" );
	$statuses = ppi_get_invoice_statuses();

	$logo_url = $logo_id ? wp_get_attachment_url( $logo_id ) : '';
	?>

	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<form method="post" action="">
			<?php wp_nonce_field( 'ppi_save_settings', 'ppi_settings_nonce' ); ?>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="ppi_invoice_logo"><?php _e( 'Logo', 'ppi-invoicing' ); ?></label>
					</th>
					<td>
						<div class="ppi-logo-upload">
							<input type="hidden" name="ppi_invoice_logo" id="ppi_invoice_logo" value="<?php echo esc_attr( $logo_id ); ?>">
							<div class="ppi-logo-preview" style="margin-bottom: 10px;">
								<?php if ( $logo_url ) : ?>
									<img src="<?php echo esc_url( $logo_url ); ?>" style="max-width: 300px; height: auto;">
								<?php endif; ?>
							</div>
							<button type="button" class="button ppi-upload-logo"><?php _e( 'Upload Logo', 'ppi-invoicing' ); ?></button>
							<?php if ( $logo_id ) : ?>
								<button type="button" class="button ppi-remove-logo"><?php _e( 'Remove Logo', 'ppi-invoicing' ); ?></button>
							<?php endif ; ?>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="ppi_invoice_info_line"><?php _e( 'Info Line', 'ppi-invoicing' ); ?></label>
					</th>
					<td>
						<input type="text" name="ppi_invoice_info_line" id="ppi_invoice_info_line" value="<?php echo esc_attr( $info_line ); ?>" class="regular-text">
						<p class="description"><?php _e( 'Text displayed below the logo (e.g., "Graphic Designer & Web Developer")', 'ppi-invoicing' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="ppi_invoice_email_1"><?php _e( 'Email Address 1', 'ppi-invoicing' ); ?></label>
					</th>
					<td>
						<input type="email" name="ppi_invoice_email_1" id="ppi_invoice_email_1" value="<?php echo esc_attr( $email_1 ); ?>" class="regular-text">
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="ppi_invoice_email_2"><?php _e( 'Email Address 2', 'ppi-invoicing' ); ?></label>
					</th>
					<td>
						<input type="email" name="ppi_invoice_email_2" id="ppi_invoice_email_2" value="<?php echo esc_attr( $email_2 ); ?>" class="regular-text">
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="ppi_invoice_phone"><?php _e( 'Phone Number', 'ppi-invoicing' ); ?></label>
					</th>
					<td>
						<input type="text" name="ppi_invoice_phone" id="ppi_invoice_phone" value="<?php echo esc_attr( $phone ); ?>" class="regular-text">
						<p class="description"><?php _e( 'Include country code if applicable (e.g., 573-424-0416)', 'ppi-invoicing' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="ppi_invoice_footer_text"><?php _e( 'Footer Text', 'ppi-invoicing' ); ?></label>
					</th>
					<td>
						<input type="text" name="ppi_invoice_footer_text" id="ppi_invoice_footer_text" value="<?php echo esc_attr( $footer_text ); ?>" class="large-text">
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="ppi_invoice_from_address"><?php _e( 'From Address', 'ppi-invoicing' ); ?></label>
					</th>
					<td>
						<?php
						wp_editor( $from_address, 'ppi_invoice_from_address', array(
							'textarea_name' => 'ppi_invoice_from_address',
							'textarea_rows' => 10,
							'media_buttons' => false,
							'teeny' => true,
							'quicktags' => array( 'buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,close' ),
						));
						?>
						<p class="description"><?php _e( 'This address appears in the "From" section of invoices. Can be overridden per invoice for audit purposes.', 'ppi-invoicing' ); ?></p>
					</td>
				</tr>
			</table>

			<h2><?php _e( 'Invoice Statuses', 'ppi-invoicing' ); ?></h2>
			<p class="description"><?php _e( 'Customize invoice statuses. The "Count Towards Total" option determines if this status should be included in the main totals on the archive page.', 'ppi-invoicing' ); ?></p>

			<div id="ppi-statuses-container">
				<?php foreach ( $statuses as $index => $status ) : ?>
					<div class="ppi-status-row" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; background: #f9f9f9;">
						<div style="display: grid; grid-template-columns: 2fr 2fr 2fr 2fr 1fr 100px; gap: 10px; align-items: start;">
							<div>
								<label><?php _e( 'Value (slug)', 'ppi-invoicing' ); ?></label>
								<input type="text" name="ppi_invoice_statuses[<?php echo esc_attr( $index ); ?>][value]" value="<?php echo esc_attr( $status['value'] ); ?>" class="regular-text" required>
							</div>
							<div>
								<label><?php _e( 'Label', 'ppi-invoicing' ); ?></label>
								<input type="text" name="ppi_invoice_statuses[<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $status['label'] ); ?>" class="regular-text" required>
							</div>
							<div>
								<label><?php _e( 'Icon Class', 'ppi-invoicing' ); ?></label>
								<input type="text" name="ppi_invoice_statuses[<?php echo esc_attr( $index ); ?>][icon]" value="<?php echo esc_attr( $status['icon'] ); ?>" class="regular-text" placeholder="ppi-circle">
							</div>
							<div>
								<label><?php _e( 'Color Class', 'ppi-invoicing' ); ?></label>
								<input type="text" name="ppi_invoice_statuses[<?php echo esc_attr( $index ); ?>][color]" value="<?php echo esc_attr( $status['color'] ); ?>" class="regular-text" placeholder="primary">
							</div>
							<div>
								<label><?php _e( 'Count Towards Total', 'ppi-invoicing' ); ?></label><br>
								<input type="checkbox" name="ppi_invoice_statuses[<?php echo esc_attr( $index ); ?>][count_towards_total]" value="1" <?php checked( $status['count_towards_total'], true ); ?>>
							</div>
							<div style="padding-top: 20px;">
								<button type="button" class="button ppi-remove-status"><?php _e( 'Remove', 'ppi-invoicing' ); ?></button>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<button type="button" id="ppi-add-status" class="button"><?php _e( '+ Add Status', 'ppi-invoicing' ); ?></button>

			<?php submit_button(); ?>
		</form>
	</div>

	<script type="text/template" id="ppi-status-template">
		<div class="ppi-status-row" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; background: #f9f9f9;">
			<div style="display: grid; grid-template-columns: 2fr 2fr 2fr 2fr 1fr 100px; gap: 10px; align-items: start;">
				<div>
					<label><?php _e( 'Value (slug)', 'ppi-invoicing' ); ?></label>
					<input type="text" name="ppi_invoice_statuses[{{INDEX}}][value]" value="" class="regular-text" required>
				</div>
				<div>
					<label><?php _e( 'Label', 'ppi-invoicing' ); ?></label>
					<input type="text" name="ppi_invoice_statuses[{{INDEX}}][label]" value="" class="regular-text" required>
				</div>
				<div>
					<label><?php _e( 'Icon Class', 'ppi-invoicing' ); ?></label>
					<input type="text" name="ppi_invoice_statuses[{{INDEX}}][icon]" value="ppi-circle" class="regular-text" placeholder="ppi-circle">
				</div>
				<div>
					<label><?php _e( 'Color Class', 'ppi-invoicing' ); ?></label>
					<input type="text" name="ppi_invoice_statuses[{{INDEX}}][color]" value="primary" class="regular-text" placeholder="primary">
				</div>
				<div>
					<label><?php _e( 'Count Towards Total', 'ppi-invoicing' ); ?></label><br>
					<input type="checkbox" name="ppi_invoice_statuses[{{INDEX}}][count_towards_total]" value="1">
				</div>
				<div style="padding-top: 20px;">
					<button type="button" class="button ppi-remove-status"><?php _e( 'Remove', 'ppi-invoicing' ); ?></button>
				</div>
			</div>
		</div>
	</script>

	<script>
	jQuery(document).ready(function($) {
		var mediaUploader;
		var statusIndex = <?php echo count( $statuses ); ?>;

		// Add new status
		$('#ppi-add-status').on('click', function() {
			var template = $('#ppi-status-template').html();
			var html = template.replace(/\{\{INDEX\}\}/g, statusIndex);
			$('#ppi-statuses-container').append(html);
			statusIndex++;
		});

		// Remove status
		$(document).on('click', '.ppi-remove-status', function() {
			if ($('.ppi-status-row').length > 1) {
				$(this).closest('.ppi-status-row').remove();
			} else {
				alert('<?php _e( 'You must have at least one status.', 'ppi-invoicing' ); ?>');
			}
		});

		$('.ppi-upload-logo').on('click', function(e) {
			e.preventDefault();

			if (mediaUploader) {
				mediaUploader.open();
				return;
			}

			mediaUploader = wp.media({
				title: '<?php _e( 'Choose Logo', 'ppi-invoicing' ); ?>',
				button: {
					text: '<?php _e( 'Use this logo', 'ppi-invoicing' ); ?>'
				},
				multiple: false
			});

			mediaUploader.on('select', function() {
				var attachment = mediaUploader.state().get('selection').first().toJSON();
				$('#ppi_invoice_logo').val(attachment.id);
				$('.ppi-logo-preview').html('<img src="' + attachment.url + '" style="max-width: 300px; height: auto;">');
				if (!$('.ppi-remove-logo').length) {
					$('.ppi-upload-logo').after('<button type="button" class="button ppi-remove-logo"><?php _e( 'Remove Logo', 'ppi-invoicing' ); ?></button>');
				}
			});

			mediaUploader.open();
		});

		$(document).on('click', '.ppi-remove-logo', function(e) {
			e.preventDefault();
			$('#ppi_invoice_logo').val('');
			$('.ppi-logo-preview').html('');
			$(this).remove();
		});
	});
	</script>
	<?php
}
