<?php if(!defined('ABSPATH')) exit; // Exit if accessed directly
/*
Plugin Name: Aelia Foundation Classes for WooCommerce
Description: This plugin implements common classes for other WooCommerce plugins developed by Aelia.
Author: Aelia
Author URI: https://aelia.co
Version: 2.0.16.200317
Plugin URI: https://aelia.co/shop/product-category/woocommerce/
Text Domain: wc-aelia-foundation-classes
Domain Path: /languages
WC requires at least: 2.6
WC tested up to: 4.0
*/

require_once(dirname(__FILE__) . '/src/lib/classes/install/aelia-wc-afc-requirementscheck.php');

// If requirements are not met, deactivate the plugin
if(Aelia_WC_AFC_RequirementsChecks::factory()->check_requirements()) {
	require_once dirname(__FILE__) . '/src/plugin-main.php';

	// Set the path and name of the main plugin file (i.e. this file), for update
	// checks. This is needed because this is the main plugin file, but the updates
	// will be checked from within plugin-main.php
	$GLOBALS['wc-aelia-foundation-classes']->set_main_plugin_file(__FILE__);

	register_activation_hook(__FILE__, array($GLOBALS['wc-aelia-foundation-classes'], 'setup'));
}
