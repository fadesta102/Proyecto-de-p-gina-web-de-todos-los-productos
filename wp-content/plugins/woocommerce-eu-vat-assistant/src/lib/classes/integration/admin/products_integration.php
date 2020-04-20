<?php
namespace Aelia\WC\EU_VAT_Assistant;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Implements an integration with WooCommerce Tax Settings to allow additional
 * information to be recorded an maintained.
 */
class Products_Integration extends \Aelia\WC\Base_Class {
	public static $instance;

	public static function init() {
		self::$instance = new self();
	}

	public function __construct() {
		parent::__construct();
		$this->set_hooks();
	}

	protected function EUVA() {
		return WC_Aelia_EU_VAT_Assistant::instance();
	}

	/**
	 * Adds localisation data for the JavaScript that extend the Tax Settings pages.
	 *
	 * @param array $admin_scripts_params The array of parameters to extend.
	 * @return array The array of parameters with additional data.
	 */
	public static function localize_admin_scripts(array $admin_scripts_params) {
		$euva = WC_Aelia_EU_VAT_Assistant::instance();
		$text_domain = WC_Aelia_EU_VAT_Assistant::$text_domain;

		$admin_scripts_params['product_settings'] = array(
		);
		return $admin_scripts_params;
	}

	protected function set_hooks() {
		// Product Edit UI
		add_action('woocommerce_product_write_panel_tabs', array($this, 'woocommerce_product_write_panel_tabs'));
		add_action('woocommerce_product_data_panels', array($this, 'woocommerce_product_data_panels'));

		// Variable products
		add_action('woocommerce_product_after_variable_attributes', array($this, 'woocommerce_product_after_variable_attributes'), 10, 3);

		// Product srttings
		add_action('save_post', array($this, 'save_post'), 1, 2);
		// WooCommerce 2.4+
		// @since 1.7.4.170330
		add_action('woocommerce_ajax_save_product_variations', array($this, 'woocommerce_ajax_save_product_variations'));
	}

	/**
	 * Adds a tab on the product edit page, which will allow to display the "prices
	 * by country" section.
	 */
	public function woocommerce_product_write_panel_tabs() {
		?>
		<li class="euva_tab hide_if_variable show_if_subscription show_if_simple">
			<a href="#euva_data" class="wcicon-globe"><?php
				echo __('EU VAT', Definitions::TEXT_DOMAIN);
			?></a>
		</li>
		<?php
	}

	/**
	 * Renders additional panels in the Product Edit page.
	 */
	public function woocommerce_product_data_panels() {
		global $post;

		// Prepare the data for the views
		$product_id = $post->ID;

		// Debug
		//var_dump(
		//	$product_euva,
		//	$product_euva_fields
		//);die();

		// VIES - Define is the product is a service
		$vies_product_is_service = get_post_meta($product_id, Definitions::FIELD_VIES_PRODUCT_IS_SERVICE, true);

		include($this->EUVA()->path('views') . '/admin/product_euva_panel.php');
	}

	/**
	 * Renders the fields to allow entering prices by country for product variations.
	 *
	 * @param int variation_index The variation inde (a progressive number within
	 * the loop used to render the UI for the variations).
	 * @param array variation_data The variation data.
	 * @param WP_Post variation The variation object.
	 */
	public function woocommerce_product_after_variable_attributes($variation_index, $variation_data, $variation) {
		// The variation is a WP_Post object, we can take its ID directly
		$product_id = $variation->ID;
		$variation_id = $variation->ID;

		// VIES - Define is the product is a service
		$vies_product_is_service = get_post_meta($product_id, Definitions::FIELD_VIES_PRODUCT_IS_SERVICE, true);

		// Debug
		//var_dump(
		//	$product_prices_by_country
		//);//die();

		include($this->EUVA()->path('views') . '/admin/product_euva_variation.php');
	}

	/**
	 * Adds a checkbox to the product admin page to indicate that the product is a
	 * service, for VIES purposes.
	 *
	 * @param array $product_type_options
	 * @return array $product_type_options
	 */
	public function easy_booking_add_product_option_pricing($product_type_options) {
		$product_type_options['booking_option'] = array(
			'id' => '_vies_service',
			'wrapper_class' => 'show_if_simple show_if_variable',
			'label' => __('', Definitions::TEXT_DOMAIN),
			'description' => __( 'Bookable products can be rent or booked on a daily schedule', 'easy_booking' ),
			'default' => 'no'
		);

		return $product_type_options;
	}

	/**
	 * Processes the meta for a product being saved, adding the settings for the
	 * EU VAT Assistant.
	 *
	 * @param string product_id The ID of the product being saved.
	 * @param mixed post The post being saved.
	 * @since 1.3.21.150405
	 */
	public function save_post($product_id, $post = null) {
		if(!isset($_POST['variable_post_id'])) {
			// Simple or external product

			// VIES Settings
			// Define if product is a service for VIES purposes
			if(isset($_POST[Definitions::FIELD_VIES_PRODUCT_IS_SERVICE]) &&
				 isset($_POST[Definitions::FIELD_VIES_PRODUCT_IS_SERVICE][$product_id])) {
				update_post_meta($product_id, Definitions::FIELD_VIES_PRODUCT_IS_SERVICE, $_POST[Definitions::FIELD_VIES_PRODUCT_IS_SERVICE][$product_id]);
			}
		}
		else {
			// Variable product

			// Get all variation IDs. They will be used to retrieve the settings for
			// each variation
			$all_variation_ids = $_POST['variable_post_id'];

			// VIES Settings
			// Define if product is a service for VIES purposes
			if(!empty($_POST[Definitions::FIELD_VIES_PRODUCT_IS_SERVICE])) {
				$this->save_variations_attribute($all_variation_ids, Definitions::FIELD_VIES_PRODUCT_IS_SERVICE);
			}
		}
	}

	/**
	 * Handles the saving of variations data using the new logic introduced in
	 * WooCommerce 2.4.
	 *
	 * @param int product_id The ID of the variable product whose variations are
	 * being saved.
	 * @since 1.7.4.170330
	 */
	public function woocommerce_ajax_save_product_variations($product_id) {
		$this->save_post($product_id);
	}

	/**
	 * Saves the attributes POSTed with a request against the variations to which
	 * they belong.
	 *
	 * @param array variation_ids An array of variation IDs.
	 * @param string field_name The name of the POSTed field from which the values
	 * should be retrieved.
	 * @since 1.3.21.150405
	 */
	protected function save_variations_attribute(array $variation_ids, $field_name) {
		foreach($variation_ids as $variation_idx => $variation_id) {
			if(!empty($_POST[$field_name][$variation_id])) {
				update_post_meta($variation_id, $field_name, $_POST[$field_name][$variation_id]);
			}
		}
	}
}
