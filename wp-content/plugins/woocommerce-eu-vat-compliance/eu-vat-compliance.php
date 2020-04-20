<?php
/*
Plugin Name: EU VAT Compliance for WooCommerce (Free)
Plugin URI: https://www.simbahosting.co.uk/s3/product/woocommerce-eu-vat-compliance/
Description: Provides features to assist WooCommerce with EU VAT compliance
Version: 1.14.6
Text Domain: woocommerce-eu-vat-compliance
Domain Path: /languages
Author: David Anderson
Author URI: https://www.simbahosting.co.uk/s3/shop/
Requires at least: 4.4
Tested up to: 5.4
License: GNU General Public License v3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html
WC requires at least: 3.4.0
WC tested up to: 4.0.0
// N.B. WooCommerce doesn't check the minor version. So, '3.9.0' means 'the entire 3.9 series'
Copyright: 2014- David Anderson
Portions licenced under the GPL v3 from other authors
*/

// The present file has minimal content to avoid needing to duplicate code changes in the free/premium versions (whose headers, above, can differ), whilst keeping both in the same source control

if (!defined('ABSPATH')) die('Access denied.');
require(dirname(__FILE__).'/bootstrap.php');
