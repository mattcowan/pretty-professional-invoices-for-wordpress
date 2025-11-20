<?php
/**
 * Register Custom Post Types
 *
 * @package PPI_Invoicing
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Invoice post type
 */
function ppi_register_invoice_post_type() {
	$labels = array(
		'name'                  => __( 'Invoices', 'ppi-invoicing' ),
		'singular_name'         => __( 'Invoice', 'ppi-invoicing' ),
		'menu_name'             => __( 'Invoicing', 'ppi-invoicing' ),
		'archives'              => __( 'Invoices', 'ppi-invoicing' ),
		'add_new'               => __( 'Add New', 'ppi-invoicing' ),
		'add_new_item'          => __( 'Add New Invoice', 'ppi-invoicing' ),
		'edit_item'             => __( 'Edit Invoice', 'ppi-invoicing' ),
		'new_item'              => __( 'New Invoice', 'ppi-invoicing' ),
		'view_item'             => __( 'View Invoice', 'ppi-invoicing' ),
		'view_items'            => __( 'View Invoices', 'ppi-invoicing' ),
		'search_items'          => __( 'Search Invoices', 'ppi-invoicing' ),
		'not_found'             => __( 'No invoices found', 'ppi-invoicing' ),
		'not_found_in_trash'    => __( 'No invoices found in trash', 'ppi-invoicing' ),
		'all_items'             => __( 'Invoices', 'ppi-invoicing' ),
	);

	$args = array(
		'label'                 => __( 'Invoices', 'ppi-invoicing' ),
		'labels'                => $labels,
		'description'           => __( 'Manage Invoices', 'ppi-invoicing' ),
		'public'                => true,
		'publicly_queryable'    => true,
		'show_ui'               => true,
		'show_in_rest'          => true,
		'rest_base'             => 'invoices',
		'rest_controller_class' => 'WP_REST_Posts_Controller',
		'has_archive'           => true,
		'show_in_menu'          => true,
		'show_in_nav_menus'     => true,
		'delete_with_user'      => false,
		'exclude_from_search'   => false,
		'capability_type'       => 'post',
		'map_meta_cap'          => true,
		'hierarchical'          => false,
		'rewrite'               => array( 'slug' => 'invoice', 'with_front' => true ),
		'query_var'             => true,
		'menu_icon'             => 'dashicons-media-spreadsheet',
		'supports'              => array( 'title', 'editor', 'thumbnail' ),
	);

	register_post_type( 'ppi_invoice', $args );
}
add_action( 'init', 'ppi_register_invoice_post_type' );

/**
 * Register Client post type
 */
function ppi_register_client_post_type() {
	$labels = array(
		'name'                  => __( 'Clients', 'ppi-invoicing' ),
		'singular_name'         => __( 'Client', 'ppi-invoicing' ),
		'menu_name'             => __( 'Clients', 'ppi-invoicing' ),
		'add_new'               => __( 'Add New', 'ppi-invoicing' ),
		'add_new_item'          => __( 'Add New Client', 'ppi-invoicing' ),
		'edit_item'             => __( 'Edit Client', 'ppi-invoicing' ),
		'new_item'              => __( 'New Client', 'ppi-invoicing' ),
		'view_item'             => __( 'View Client', 'ppi-invoicing' ),
		'view_items'            => __( 'View Clients', 'ppi-invoicing' ),
		'search_items'          => __( 'Search Clients', 'ppi-invoicing' ),
		'not_found'             => __( 'No clients found', 'ppi-invoicing' ),
		'not_found_in_trash'    => __( 'No clients found in trash', 'ppi-invoicing' ),
		'all_items'             => __( 'All Clients', 'ppi-invoicing' ),
	);

	$args = array(
		'label'                 => __( 'Clients', 'ppi-invoicing' ),
		'labels'                => $labels,
		'description'           => __( 'Invoice Clients', 'ppi-invoicing' ),
		'public'                => true,
		'publicly_queryable'    => true,
		'show_ui'               => true,
		'show_in_rest'          => true,
		'rest_base'             => 'clients',
		'rest_controller_class' => 'WP_REST_Posts_Controller',
		'has_archive'           => false,
		'show_in_menu'          => 'edit.php?post_type=ppi_invoice',
		'show_in_nav_menus'     => false,
		'delete_with_user'      => false,
		'exclude_from_search'   => true,
		'capability_type'       => 'post',
		'map_meta_cap'          => true,
		'hierarchical'          => false,
		'rewrite'               => array( 'slug' => 'client', 'with_front' => true ),
		'query_var'             => true,
		'menu_icon'             => 'dashicons-groups',
		'supports'              => array( 'title' ),
	);

	register_post_type( 'ppi_client', $args );
}
add_action( 'init', 'ppi_register_client_post_type' );
