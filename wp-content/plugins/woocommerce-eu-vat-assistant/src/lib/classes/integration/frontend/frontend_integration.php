<?php
namespace Aelia\WC\EU_VAT_Assistant;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Implements the integration with the frontend user interface.
 *
 * @since 1.4.10.150720
 */
class Frontend_Integration extends \Aelia\WC\Base_Class {
	protected static $instance;
	protected $text_domain;

	public static function init() {
		self::$instance = new self();
	}

	public function __construct() {
		parent::__construct();
		$this->text_domain = WC_Aelia_EU_VAT_Assistant::$text_domain;
		$this->set_hooks();
	}

	protected function EUVA() {
		return WC_Aelia_EU_VAT_Assistant::instance();
	}

	/**
	 * Adds localisation data for the JavaScript used on the frontend.
	 *
	 * @param array $frontend_scripts_params The array of parameters to extend.
	 * @return array The array of parameters with additional data.
	 */
	//public static function localize_frontend_scripts(array $frontend_scripts_params) {
	//	$euva = WC_Aelia_EU_VAT_Assistant::instance();
	//
	//	$frontend_scripts_params['some_group'] = array(
	//	);
	//	return $frontend_scripts_params;
	//}

	protected function set_hooks() {
		// My Account
		add_action('woocommerce_billing_fields', array($this, 'woocommerce_billing_fields'), 10, 1);
		add_action('woocommerce_my_account_my_address_formatted_address', array($this, 'woocommerce_my_account_my_address_formatted_address'), 10, 3);
	}

	/**
	 * Shows additional fields in the My Account > Edit > Billing address section.
	 *
	 * @param array fields The array of fields to display.
	 * @return array The fields array, with extra fields added to it.
	 */
	public function woocommerce_billing_fields($fields)  {
		// Show the VAT number on the "My Account" page
		if(is_account_page()) {

			$fields['vat_number'] = array(
				'label' => __('VAT Number', $this->text_domain),
				'placeholder' => _x('VAT Number', 'placeholder', $this->text_domain),
				'required' => false,
				'class' => array('form-row-wide'),
				'clear' => false
			);
		}
		return $fields;
	}

	/**
	 * Shows additional fields on the My Account > Billing address section.
	 *
	 * @param array fields The array of fields to display.
	 * @return array The fields array, with extra fields added to it.
	 */
	public function woocommerce_my_account_my_address_formatted_address($fields, $customer_id, $address_name) {
		if(strcasecmp($address_name, 'billing') === 0) {
			$fields['vat_number'] = get_user_meta($customer_id, Definitions::FIELD_VAT_NUMBER, true);
		}
		return $fields;
	}
}
