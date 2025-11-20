<?php
/**
 * Invoice Header Template
 *
 * @package PPI_Invoicing
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> lang="<?php echo esc_attr( get_bloginfo('language') ); ?>" class="no-js">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="stylesheet" href="https://use.typekit.net/cph7oqg.css">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php do_action( 'wp_body_open' ); ?>
<a href="#main-content" class="skip-link screen-reader-text">
	<?php _e( 'Skip to content', 'ppi-invoicing' ); ?>
</a>
