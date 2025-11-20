<?php
/**
 * Plugin Name: Pretty Professional Invoices
 * Plugin URI: https://prettyprofessionalinvoices.com
 * Description: Self-contained invoicing system with client management, projects, and time tracking. Migrated from theme with no external dependencies.
 * Version: 1.0.0
 * Author: Matthew Cowan
 * Author URI: https://mnc4.com
 * License: GPL v2 or later
 * Text Domain: ppi-invoicing
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'PPI_INVOICING_VERSION', '1.0.0' );
define( 'PPI_INVOICING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PPI_INVOICING_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class
 */
class PPI_Invoicing_System {

	/**
	 * Single instance of the class
	 */
	private static $instance = null;

	/**
	 * Get single instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required files
	 */
	private function load_dependencies() {
		require_once PPI_INVOICING_PLUGIN_DIR . 'includes/post-types.php';
		require_once PPI_INVOICING_PLUGIN_DIR . 'includes/meta-boxes.php';
		require_once PPI_INVOICING_PLUGIN_DIR . 'includes/ajax-handlers.php';
		require_once PPI_INVOICING_PLUGIN_DIR . 'includes/template-loader.php';
		require_once PPI_INVOICING_PLUGIN_DIR . 'includes/settings.php';

		// TEMPORARY: Remove after ACF migration is complete
		require_once PPI_INVOICING_PLUGIN_DIR . 'includes/migrate-acf.php';
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Load plugin text domain
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'ppi-invoicing', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_frontend_assets() {
		// Only load on invoice pages
		if ( is_singular( 'ppi_invoice' ) || is_post_type_archive( 'ppi_invoice' ) ) {
			wp_enqueue_style(
				'ppi-invoice-styles',
				PPI_INVOICING_PLUGIN_URL . 'assets/css/invoice-styles.css',
				array(),
				PPI_INVOICING_VERSION
			);

			// Archive page scripts
			if ( is_post_type_archive( 'ppi_invoice' ) && is_user_logged_in() ) {
				wp_enqueue_script(
					'ppi-invoice-archive',
					PPI_INVOICING_PLUGIN_URL . 'assets/js/invoice-archive.js',
					array( 'jquery' ),
					PPI_INVOICING_VERSION,
					true
				);

				// Localize script
				wp_localize_script( 'ppi-invoice-archive', 'ppiInvoicing', array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'invoice_status_nonce' )
				));
			}
		}
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on invoice/client edit screens
		global $post_type;
		if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) &&
		     ( 'ppi_invoice' === $post_type || 'ppi_client' === $post_type ) ) {

			wp_enqueue_style(
				'ppi-invoice-admin',
				PPI_INVOICING_PLUGIN_URL . 'assets/css/admin-styles.css',
				array(),
				PPI_INVOICING_VERSION
			);

			wp_enqueue_script(
				'ppi-invoice-admin',
				PPI_INVOICING_PLUGIN_URL . 'assets/js/invoice-admin.js',
				array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker' ),
				PPI_INVOICING_VERSION,
				true
			);
		}
	}
}

/**
 * Initialize the plugin
 */
function ppi_invoicing_init() {
	return PPI_Invoicing_System::get_instance();
}

// Start the plugin
ppi_invoicing_init();
