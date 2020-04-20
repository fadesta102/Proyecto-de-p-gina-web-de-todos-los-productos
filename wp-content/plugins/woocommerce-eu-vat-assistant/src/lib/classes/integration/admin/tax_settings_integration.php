<?php
namespace Aelia\WC\EU_VAT_Assistant;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Implements an integration with WooCommerce Tax Settings to allow additional
 * information to be recorded an maintained.
 */
class Tax_Settings_Integration extends \Aelia\WC\Base_Class {
	/**
	 * Returns the tax class corresponding to the tax section currently open.
	 *
	 * @return string
	 */
	protected static function get_current_tax_class() {
		$current_section = get_value('section', $_REQUEST, '');
		$tax_classes = array_filter(array_map('trim', explode("\n", get_option('woocommerce_tax_classes'))));
		$current_class = '';
		foreach($tax_classes as $class) {
			if(sanitize_title($class) == $current_section) {
				$current_class = $class;
			}
		}
		return $current_class;
	}

	/**
	 * Returns the data of the various tax rates displayed on a Tax Settings page.
	 *
	 * @return array
	 */
	public static function get_tax_rates_data() {
		global $wpdb;

		$page = absint(get_value('p', $_GET, 1));
		$limit = 100;
		$current_class = self::get_current_tax_class();

		$SQL = "
			SELECT
				TR.tax_rate_id
				,TR.tax_payable_to_country
			FROM
				{$wpdb->prefix}woocommerce_tax_rates TR
			WHERE
				(tax_rate_class = %s)
			LIMIT
				%d, %d;
		";

		$query = $wpdb->prepare(
			$SQL,
			sanitize_title($current_class),
			($page - 1) * $limit,
			$limit
		);

		$tax_rates = $wpdb->get_results($query, OBJECT_K);
		return $tax_rates;
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

		$admin_scripts_params['tax_settings'] = array(
			'eu_vat_rates' => $euva->get_eu_vat_rates(),
			'eu_vat_rate_types' => $euva->get_eu_vat_rate_types(),
			'tax_rates_data' => self::get_tax_rates_data(),
			'user_interface' => array(
				'update_eu_vat_rates_button_label' => __('Update EU VAT Rates', $text_domain),
				'eu_vat_rates_using_text' => __('using', $text_domain),
				'vat_label' => __('VAT', $text_domain),
				'vat_updated_message' => __('VAT rates have been updated. Please review them, to ' .
																		'ensure that they are correct, then click on "Save ' .
																		'changes" to save the new rates.', $text_domain),
				'invalid_vat_rates' => __('An error occurred retrieving the VAT rates, therefore ' .
																	'this function cannot be used at the moment. Please try ' .
																	'again by reloading the page later.', $text_domain),
				'tax_payable_to_country' => array(
					'header_label' => __('Tax payable to Country', $text_domain),
					'header_tooltip' => __('Specify to which country this tax should be paid. Leave empty ' .
																 'to use the country to which the tax is applied (i.e. ' .
																 'the one in the Country Code column).', $text_domain),
					'field_placeholder' => __('Auto', $text_domain),
				)
			),
		);
		return $admin_scripts_params;
	}

	/**
	 * Sets the hooks required by the class.
	 */
	public static function set_hooks() {
		add_action('woocommerce_settings_save_tax', array(__CLASS__, 'woocommerce_settings_save_tax'), 1);
		add_action('woocommerce_tax_rate_added', array(__CLASS__, 'woocommerce_tax_rate_added'), 10, 2);
		add_action('woocommerce_tax_rate_updated', array(__CLASS__, 'woocommerce_tax_rate_updated'), 10, 2);
	}

	/**
	 * Fired when the tax settings are saved.
	 * Intercepts the new tax rates added by the Admin and associates them with the
	 * extra data added by the EU VAT plugin.
	 */
	public static function woocommerce_settings_save_tax() {
		if(empty($_POST['tax_rate_country']) || empty($_POST['tax_rate_country']['new'])) {
			return;
		}

		/* Every new row has a unique ID. However, the "after tax rate insert" event
		 * doesn't pass such ID, so we use a trick: we append the row ID to the country
		 * code, so that we can extract it later.
		 */
		foreach($_POST['tax_rate_country']['new'] as $row_id => $country) {
			$_POST['tax_rate_country']['new'][$row_id] = $country . '_' . $row_id;
		}

		// Debug
		//var_dump($_POST['tax_rate_country']['new']);die();
	}

	protected static function get_tax_payable_to_country($default = '') {
		$tax_payable_to_country = $default;
		if(isset($_POST['tax_payable_to_country'])) {
			$tax_payable_to_country = $_POST['tax_payable_to_country']['new'][$row_id];
		}
		return $tax_payable_to_country;
	}

	/**
	 * Adds extra information to a newly added tax rate.
	 *
	 * @param int tax_rate_id The ID of the new tax rate.
	 * @param array A list with the details of the tax rate
	 */
	public static function woocommerce_tax_rate_added($tax_rate_id, $tax_rate_info) {
		global $wpdb;
		$country_parts = explode('_', $tax_rate_info['tax_rate_country']);
		$country = array_shift($country_parts);
		$row_id = array_shift($country_parts);

		if(!is_numeric($row_id)) {
			return;
		}

		// Debug
		//var_dump($country, $row_id, $_POST['tax_payable_to_country']['new'][$row_id]);die();

		// Retrieve the country to which the tax will be payable
		$tax_payable_to_country = self::get_tax_payable_to_country();

		// Update the row
		$tax_info = array(
			'tax_rate_country' => $country,
			'tax_payable_to_country' => $tax_payable_to_country,
		);

		$result = $wpdb->update(
			$wpdb->prefix . 'woocommerce_tax_rates',
			$tax_info,
			array(
				'tax_rate_id' => $tax_rate_id,
			)
		);
	}

	/**
	 * Adds extra information to a tax rate that has been updated.
	 *
	 * @param int tax_rate_id The ID of the new tax rate.
	 * @param array A list with the details of the tax rate
	 */
	public static function woocommerce_tax_rate_updated($tax_rate_id, $tax_rate_info) {
		global $wpdb;
		$wpdb->show_errors();

		// Retrieve the country to which the tax will be payable
		$tax_payable_to_country = self::get_tax_payable_to_country();

		// Debug
		//var_dump($tax_rate_id, $tax_rate_info, $_POST['tax_payable_to_country'][$tax_rate_id]);die();

		// Update the row
		$tax_info = array(
			'tax_payable_to_country' => $tax_payable_to_country,
		);

		$result = $wpdb->update(
			$wpdb->prefix . 'woocommerce_tax_rates',
			$tax_info,
			array(
				'tax_rate_id' => $tax_rate_id,
			)
		);
	}
}
