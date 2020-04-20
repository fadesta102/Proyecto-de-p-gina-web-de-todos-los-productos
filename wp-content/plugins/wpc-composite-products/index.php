<?php
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'activate_plugin' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$old_slug = 'wpc-composite-products/index.php';
$new_slug = 'wpc-composite-products/wpc-composite-products.php';

if ( ! is_plugin_active( $new_slug ) ) {
	// activate new slug
	activate_plugin( $new_slug );
	// deactivate old slug
	deactivate_plugins( $old_slug );
}