<?php
namespace Aelia\WC\EU_VAT_Assistant;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use \Exception;
use \stdClass;

/**
 * Handles the settings for the Blacklister plugin and provides convenience
 * methods to read and write them.
 */
class Settings extends \Aelia\WC\Settings {
	/*** Settings Key ***/
	// @var string The key to identify plugin settings amongst WP options.
	const SETTINGS_KEY = 'wc_aelia_eu_vat_assistant';

	/*** Settings fields ***/
	// Checkout
	const FIELD_EU_VAT_NUMBER_FIELD_REQUIRED = 'eu_vat_number_field_required';
	const FIELD_EU_VAT_FIELD_TITLE = 'eu_vat_field_title';
	const FIELD_EU_VAT_FIELD_DESCRIPTION = 'eu_vat_field_description';
	const FIELD_SHOW_EU_VAT_FIELD_IF_CUSTOMER_IN_BASE_COUNTRY = 'show_eu_vat_field_number_when_customer_in_base_country';
	const FIELD_REMOVE_VAT_IF_CUSTOMER_IN_BASE_COUNTRY = 'remove_vat_when_customer_in_base_country';
	const FIELD_STORE_INVALID_VAT_NUMBERS = 'store_invalid_vat_numbers';
	// @since 1.11.0.191108
	const FIELD_VAT_NUMBER_FIELD_POSITION = 'vat_number_position';

	// Self-certification
	const FIELD_SHOW_SELF_CERTIFICATION_FIELD = 'show_self_certification_field';
	// @since 1.11.0.191108
	const FIELD_SELF_CERTIFICATION_FIELD_POSITION = 'self_certification_field_position';
	const FIELD_HIDE_SELF_CERTIFICATION_FIELD_VALID_VAT_NUMBER = 'hide_self_certification_field_valid_vat_number';
	const FIELD_SELF_CERTIFICATION_FIELD_REQUIRED_WHEN_CONFLICT = 'self_certification_field_required_when_conflict';
	const FIELD_USE_SHIPPING_ADDRESS_AS_EVIDENCE = 'use_shipping_address_as_evidence';
	const FIELD_SELF_CERTIFICATION_FIELD_TITLE = 'self_certification_field_title';

	// Currency
	const FIELD_VAT_CURRENCY = 'vat_currency';
	const FIELD_EXCHANGE_RATES = 'exchange_rates';

	// Exchange rates
	const FIELD_EXCHANGE_RATES_UPDATE_ENABLE = 'exchange_rates_update_enable';
	const FIELD_EXCHANGE_RATES_UPDATE_SCHEDULE = 'exchange_rates_update_schedule';
	const FIELD_EXCHANGE_RATES_LAST_UPDATE = 'exchange_rates_last_update';
	const FIELD_EXCHANGE_RATES_PROVIDER = 'exchange_rate_provider';

	// Sales
	const FIELD_SALE_DISALLOWED_COUNTRIES = 'sale_disallowed_countries';

	// Reports
	const FIELD_TAX_CLASSES_EXCLUDED_FROM_MOSS = 'tax_classes_excluded_from_moss';

	// Options
	const FIELD_VAT_ROUNDING_DECIMALS = 'vat_rounding_decimals';
	const FIELD_DEBUG_MODE = 'debug_mode';
	const FIELD_COLLECT_VAT_DATA_FOR_MANUAL_ORDERS = 'collect_vat_data_for_manual_orders';

	// VAT Number validation
	const FIELD_ACCEPT_VAT_NUMBER_WHEN_VALIDATION_SERVER_BUSY = 'accept_vat_number_when_validation_server_busy';
	// Accept VAT Numbers that could not be validated when the service is unavailable
	// @since 1.11.0.191108
	const FIELD_ACCEPT_VAT_NUMBER_WHEN_SERVICE_UNAVAILABLE = 'accept_vat_number_when_service_unavailable';
	// Accept VAT Numbers that could not be validated when the service rejects the call due to too many requests
	// @since 1.11.0.191108
	const FIELD_ACCEPT_VAT_NUMBER_WHEN_SERVICE_REJECTS_TOO_MANY_REQUESTS = 'accept_vat_number_when_service_rejects_too_many_requests';

	// Retry the validation of a VAT Number that was rejected due to an invalid Requester VAT Number
	// @since 1.11.0.191108
	const FIELD_RETRY_VALIDATION_WHEN_REQUESTER_VAT_NUMBER_INVALID = 'retry_validation_when_requester_vat_number_invalid';

	// The shop VAT country and number, used to call VIES validation
	// @since 1.9.0.181022
	const FIELD_VIES_REQUESTER_VAT_COUNTRY = 'vies_requester_vat_country';
	const FIELD_VIES_REQUESTER_VAT_NUMBER = 'vies_requester_vat_number';

	// Defaults
	// @var string The default Exchange Rates Model class to use when the configured one is not valid.
	const DEFAULT_EXCHANGE_RATES_PROVIDER = 'Exchange_Rates_BitPay_Model';
	const DEFAULT_VAT_ROUNDING_DECIMALS = 4;
	const DEFAULT_SHOW_SELF_CERTIFICATION_FIELD = self::OPTION_SELF_CERTIFICATION_FIELD_NO;
	const DEFAULT_FIELD_EU_VAT_NUMBER_FIELD_REQUIRED = self::OPTION_EU_VAT_NUMBER_FIELD_OPTIONAL;
	// @since 1.11.0.191108
	const DEFAULT_VAT_NUMBER_FIELD_POSITION = 250;
	const DEFAULT_SELF_CERTIFICATION_FIELD_POSITION = 300;

	// Field values
	const OPTION_SELF_CERTIFICATION_FIELD_YES = 'yes';
	const OPTION_SELF_CERTIFICATION_FIELD_NO = 'no';
	const OPTION_SELF_CERTIFICATION_FIELD_CONFLICT_ONLY = 'conflict-only';

	const OPTION_EU_VAT_NUMBER_FIELD_OPTIONAL = 'optional';
	const OPTION_EU_VAT_NUMBER_FIELD_REQUIRED = 'required';
	const OPTION_EU_VAT_NUMBER_FIELD_REQUIRED_EU_ONLY = 'required_for_eu_only';
	const OPTION_EU_VAT_NUMBER_FIELD_REQUIRED_IF_COMPANY_FILLED = 'required_if_company_field_filled';
	const OPTION_EU_VAT_NUMBER_FIELD_REQUIRED_IF_COMPANY_FILLED_EU_ONLY = 'required_if_company_field_filled_eu_only';
	const OPTION_EU_VAT_NUMBER_FIELD_HIDDEN = 'hidden';

	// @var array The definition of the hook that will be called to update the Exchange Rates on a scheduled basis.
	protected $_exchange_rates_update_hook = 'aelia_wc_eu_vat_assistant_exchange_rates_update_hook';
	// @var array A list of the available exchange rates models.
	protected $_exchange_rates_models;

	// @var array A list of validation errors.
	protected $validation_errors;

	/**
	 * Getter for private "_exchange_rates_update_hook" property.
	 *
	 * @return string Value of "_exchange_rates_update_hook" property.
	 */
	public function exchange_rates_update_hook() {
		return $this->_exchange_rates_update_hook;
	}

	/**
	 * Returns the currency settings to apply when a currency is selected for
	 * the first time and has no settings.
	 *
	 * @return array An array of settings.
	 */
	public function default_currency_settings() {
		return array(
			'rate' => '',
			//'rate_markup' => '',
			'set_manually' => 0,
		);
	}

	/**
	 * Returns the default settings for the plugin. Used mainly at first
	 * installation.
	 *
	 * @param string key If specified, method will return only the setting identified
	 * by the key.
	 * @param mixed default The default value to return if the setting requested
	 * via the "key" argument is not found.
	 * @return array|mixed The default settings, or the value of the specified
	 * setting.
	 *
	 * @see WC_Aelia_Settings:default_settings().
	 */
	public function default_settings($key = null, $default = null) {
		$default_options = array(
			// Checkout
			self::FIELD_EU_VAT_NUMBER_FIELD_REQUIRED => self::DEFAULT_FIELD_EU_VAT_NUMBER_FIELD_REQUIRED,
			self::FIELD_EU_VAT_FIELD_TITLE => __('EU VAT Number', $this->textdomain),
			self::FIELD_EU_VAT_FIELD_DESCRIPTION => __('Enter your EU VAT Number (if any). Country prefix is not required.', $this->textdomain),
			self::FIELD_SHOW_EU_VAT_FIELD_IF_CUSTOMER_IN_BASE_COUNTRY => true,
			self::FIELD_REMOVE_VAT_IF_CUSTOMER_IN_BASE_COUNTRY => false,
			self::FIELD_STORE_INVALID_VAT_NUMBERS => true,
			// @since 1.11.0.191108
			self::FIELD_VAT_NUMBER_FIELD_POSITION => 250,

			self::FIELD_ACCEPT_VAT_NUMBER_WHEN_VALIDATION_SERVER_BUSY => true,
			// @since 1.11.0.191108
			self::FIELD_ACCEPT_VAT_NUMBER_WHEN_SERVICE_UNAVAILABLE => false,
			// @since 1.11.0.191108
			self::FIELD_ACCEPT_VAT_NUMBER_WHEN_SERVICE_REJECTS_TOO_MANY_REQUESTS => false,
			// @since 1.11.0.191108
			self::FIELD_RETRY_VALIDATION_WHEN_REQUESTER_VAT_NUMBER_INVALID => true,

			// Self-certification
			self::FIELD_SHOW_SELF_CERTIFICATION_FIELD => self::DEFAULT_SHOW_SELF_CERTIFICATION_FIELD,
			self::FIELD_HIDE_SELF_CERTIFICATION_FIELD_VALID_VAT_NUMBER => true,
			self::FIELD_SELF_CERTIFICATION_FIELD_REQUIRED_WHEN_CONFLICT => false,
			self::FIELD_USE_SHIPPING_ADDRESS_AS_EVIDENCE => false,
			self::FIELD_SELF_CERTIFICATION_FIELD_TITLE => __('I am established, have my permanent address, or usually reside within <strong>{billing_country}</strong>.', $this->textdomain),
			// @since 1.11.0.191108
			self::FIELD_SELF_CERTIFICATION_FIELD_POSITION => self::DEFAULT_SELF_CERTIFICATION_FIELD_POSITION,

			// Currency
			self::FIELD_VAT_CURRENCY => get_option('woocommerce_currency'),

			// Exchange rates
			self::FIELD_EXCHANGE_RATES_UPDATE_ENABLE => false,
			self::FIELD_EXCHANGE_RATES_UPDATE_SCHEDULE => 'weekly',
			self::FIELD_EXCHANGE_RATES_LAST_UPDATE => null,
			self::FIELD_EXCHANGE_RATES_PROVIDER => self::DEFAULT_EXCHANGE_RATES_PROVIDER,

			// Reports
			self::FIELD_TAX_CLASSES_EXCLUDED_FROM_MOSS => array(),

			// Options
			self::FIELD_VAT_ROUNDING_DECIMALS => self::DEFAULT_VAT_ROUNDING_DECIMALS,
			self::FIELD_DEBUG_MODE => false,
			self::FIELD_COLLECT_VAT_DATA_FOR_MANUAL_ORDERS => false,

			// VIES service
			// @since 1.9.0.181022
			self::FIELD_VIES_REQUESTER_VAT_COUNTRY => WC()->countries->get_base_country(),
			self::FIELD_VIES_REQUESTER_VAT_NUMBER => '',
		);

		if(empty($key)) {
			return $default_options;
		}
		else {
			return get_value($key, $default_options, $default);
		}
	}

	/**
	 * Returns the currency used for VAT reports.
	 *
	 * @return string
	 */
	public function vat_currency() {
		return $this->get(self::FIELD_VAT_CURRENCY, $this->default_settings(self::FIELD_VAT_CURRENCY));
	}

	/**
	 * Returns a list of the currencies enabled in the shop.
	 *
	 * @return array
	 */
	public function enabled_currencies() {
		return array_unique(apply_filters('wc_aelia_cs_enabled_currencies',
																			array(get_option('woocommerce_currency'), $this->vat_currency())));
	}

	/**
	 * Returns the exchange rates configured for the plugin. All the rates are
	 * relative to the VAT currency.
	 *
	 * @return array An array of currency => exchange rate value.
	 * @see Aelia\WC\EU_VAT_Assistant\Settings::vat_currency()
	 */
	public function get_exchange_rates() {
		$result = array();
		$exchange_rates_settings = $this->current_settings(self::FIELD_EXCHANGE_RATES);

		if(!is_array($exchange_rates_settings)) {
			$exchange_rates_settings = array();
		}

		$vat_currency = $this->vat_currency();
		// Return all exchange rates, excluding the invalid ones
		foreach($exchange_rates_settings as $currency => $settings) {
			// Skip VAT currency, its exchange rate will be forced to 1 anmyway
			if($currency == $vat_currency) {
				continue;
			}

			if(is_numeric(get_value('rate', $settings))) {
				$exchange_rate = (float)$settings['rate'];
			}
			else {
				$exchange_rate = 0;
			}

			//// Add the markup to the exchange rate, if one was specified
			//if($include_markup && is_numeric(get_value('rate_markup', $settings))) {
			//	$exchange_rate += (float)$settings['rate_markup'];
			//}

			$result[$currency] = $exchange_rate;
		}
		// Exchange rate for VAT currency base currency is always 1
		$result[$vat_currency] = 1;

		return $result;
	}

	/**
	 * Returns a list of schedule options, retrieved from WordPress list.
	 *
	 * @return array An array of Schedule ID => Schedule Name pairs.
	 */
	public function get_schedule_options() {
		$wp_schedules = wp_get_schedules();
		uasort($wp_schedules, array($this, 'sort_schedules'));

		$result = array();
		foreach($wp_schedules as $schedule_id => $settings) {
			$result[$schedule_id] = $settings['display'];
		}
		return $result;
	}

	/**
	 * Callback method, used with uasort() function.
	 * Sorts WordPress scheduling options by interval (ascending). In case of two
	 * identical intervals, it sorts them by label (comparison is case-insensitive).
	 *
	 * @param array a First schedule Option.
	 * @param array b Second schedule Option.
	 * @return int Zero if (a == b), -1 if (a < b), 1 if (a > b).
	 *
	 * @see uasort().
	 */
	protected function sort_schedules($a, $b) {
		if($a['interval'] == $b['interval']) {
			return strcasecmp($a['display'], $b['display']);
		}
		return ($a['interval'] < $b['interval']) ? -1 : 1;
	}

	/**
	 * Returns information about the schedule of the automatic updates of exchange
	 * rates.
	 *
	 * @return array An array with the next and last update of the exchange rates.
	 */
	public function get_exchange_rates_schedule_info() {
		$schedule_options_field_id = self::FIELD_EXCHANGE_RATES_UPDATE_SCHEDULE;
		$current_schedule = $this->current_settings($schedule_options_field_id);

		// Retrieve the timestamp of next scheduled exchange rates update
		if(wp_get_schedule($this->_exchange_rates_update_hook) === false) {
			$next_update_schedule = __('Not Scheduled', $this->textdomain);
		}
		else {
			$next_update_schedule = date_i18n(get_datetime_format(), wp_next_scheduled($this->_exchange_rates_update_hook));
		}

		// Retrieve the timestamp of last update
		if(($last_update_timestamp = $this->current_settings(self::FIELD_EXCHANGE_RATES_LAST_UPDATE)) != null) {
			$last_update_timestamp_fmt = date_i18n(get_datetime_format(), $last_update_timestamp);
		}
		else {
			$last_update_timestamp_fmt = __('Never updated', $this->textdomain);
		}

		return array(
			'next_update' => $next_update_schedule,
			'last_update' => $last_update_timestamp_fmt,
		);
	}

	/**
	 * Updates the plugin settings received as an argument with the latest exchange
	 * rates, adding a settings error if the operation fails.
	 *
	 * @param array settings Current plugin settings.
	 * @return bool
	 */
	public function update_exchange_rates(array &$settings, &$errors = array()) {
		// Keep track of the VAT currency, it will be used as a reference for conversion
		$vat_currency = get_value(self::FIELD_VAT_CURRENCY, $settings, $this->vat_currency());
		// Get the latest exchange rates from the provider
		$exchange_rates_model = $this->get_exchange_rates_model(get_value(self::FIELD_EXCHANGE_RATES_PROVIDER, $settings, null));
		$latest_exchange_rates = $exchange_rates_model->get_exchange_rates($vat_currency,
																																			 $this->enabled_currencies());
		// Debug
		//var_dump($exchange_rates, $exchange_rates_to_update, $latest_exchange_rates);die();

		$exchange_rates_model_errors = $exchange_rates_model->get_errors();
		if(($latest_exchange_rates === null) ||
			 !empty($exchange_rates_model_errors)) {
			$result = empty($exchange_rates_model_errors);

			foreach($exchange_rates_model_errors as $code => $message) {
				$errors['exchange-rates-error-' . $code] = $message;
			}
			return false;
		}

		$exchange_rates = get_value(self::FIELD_EXCHANGE_RATES, $settings, array());
		// Update the exchange rates and add them to the settings to be saved
		$settings[self::FIELD_EXCHANGE_RATES] = $this->merge_exchange_rates($exchange_rates,
																																				$latest_exchange_rates,
																																				$vat_currency);

		return true;
	}

	/**
	 * Updates a list of exchange rates settings by replacing the rates with new
	 * ones passed as a parameter.
	 *
	 * @param array exchange_rates The list of exchange rate settings to be updated.
	 * @param array new_exchange_rates The new exchange rates as a simple list of
	 * currency => rate pairs.
	 * @param string vat_currency The currency used for VAT reports. It will have
	 * an exchange rate of "1".
	 * @return array The updated exchange rate settings.
	 */
	protected function merge_exchange_rates($exchange_rates, array $new_exchange_rates, $vat_currency) {
		$exchange_rates = empty($exchange_rates) ? array() : $exchange_rates;

		foreach($new_exchange_rates as $currency => $rate) {
			// Base VAT currency has a fixed exchange rate of 1 (it doesn't need to be
			// converted)
			if($currency == $vat_currency) {
				$exchange_rates[$currency]['rate'] = 1;
				continue;
			}

			$currency_settings = get_value($currency, $exchange_rates, $this->default_currency_settings());

			// Update the exchange rate unless the currency is set to "set manually"
			// to prevent automatic updates
			if(get_value('set_manually', $currency_settings, 0) == 0) {
				$currency_settings['rate'] = $rate;
			}
			$exchange_rates[$currency] = $currency_settings;
		}
		return $exchange_rates;
	}

	/**
	 * Get the instance of the exchange rate model to use to retrieve the rates.
	 *
	 * @param string key The key identifying the exchange rate model class.
	 * @param array An array of settings that can be used to override the ones
	 * currently saved in the configuration.
	 * @param string default_class The exchange rates model class to use as a default.
	 * @return \Aelia\WC\ExchangeRatesModel.
	 */
	protected function get_exchange_rates_model_instance($key,
																											 array $settings = null,
																											 $default_class = self::DEFAULT_EXCHANGE_RATES_PROVIDER) {
		$model_info = get_value($key, $this->_exchange_rates_models);
		$model_class = get_value('class_name', $model_info, $default_class);
		return new $model_class($settings);
	}

	/**
	 * Returns the label of the provider used to retrieve current exchange rates
	 * for VAT currency.
	 *
	 * @return string
	 */
	public function get_current_exchange_rates_provider_label() {
		$model_info = get_value($this->get(self::FIELD_EXCHANGE_RATES_PROVIDER), $this->_exchange_rates_models);
		return get_value('label', $model_info, __('Not available', $this->textdomain));
	}

	/**
	 * Returns the instance of the exchange rate model.
	 *
	 * @param string exchange_rates_model_key The key to retrieve the exchange
	 * rates model class.
	 * @param array settings The settings to pass to the exchange rates model instance.
	 * @return \Aelia\WC\ExchangeRatesModel.
	 */
	protected function get_exchange_rates_model($exchange_rates_model_key, $settings = null) {
		if(empty($this->_exchange_rates_model)) {
			$this->_exchange_rates_model = $this->get_exchange_rates_model_instance($exchange_rates_model_key,
																																							$settings);
		}
		return $this->_exchange_rates_model;
	}

	/**
	 * Validates the settings specified via the Options page.
	 *
	 * @param array settings An array of settings.
	 */
	public function validate_settings($settings) {
		// Merge the new settings with some defaults. This is especially important
		// for multi-select options. If the user empties those fields (i.e. doesn't
		// select anything), the fields are not passed with the $_POST data.
		// Due to that, a multi-select option that was previously populated would
		// not be emptied. By setting the default as an empty array, even if the
		// field is missing we can save it as "nothing selected"
		$settings = array_merge(array(
			self::FIELD_SALE_DISALLOWED_COUNTRIES => array(),
			self::FIELD_TAX_CLASSES_EXCLUDED_FROM_MOSS => array(),
		), $settings);

		// Debug
		//var_dump($settings);die();
		$this->validation_errors = array();
		$processed_settings = $this->current_settings();

		// Save the schedule for automatic update of exchange rates
		//
		// IMPORTANT
		// This step must be performed before the storing of the new settings
		// in the $processed_settings variable. This is necessary because the
		// old settings and the new settings have to be compared to determine
		// if the schedule has to be updated
		// @since 1.12.1.191217
		$this->set_exchange_rates_update_schedule($processed_settings, $settings);

		// Prepare a list of settings that must be validated, before being saved
		// @since 1.11.0.191108
		$settings_to_validate = array(
			self::FIELD_EXCHANGE_RATES,
			self::FIELD_VIES_REQUESTER_VAT_COUNTRY,
			self::FIELD_VIES_REQUESTER_VAT_NUMBER,
		);
		// Take the new value of any setting that can be saved as it is,
		// skipping the ones that need to be validated first
		// @since 1.11.0.191108
		foreach($settings as $field_name => $field_value) {
			if(in_array($field_name, $settings_to_validate)) {
				continue;
			}
			$processed_settings[$field_name] = $settings[$field_name];
		}

		// Validate exchange rates
		$exchange_rates = get_value(self::FIELD_EXCHANGE_RATES, $settings, array());
		$exchange_rates_to_update = array();
		if($this->validate_exchange_rates($exchange_rates, $exchange_rates_to_update) === true) {
			$settings[self::FIELD_EXCHANGE_RATES] = $exchange_rates;
		}

		// Debug
		//var_dump($exchange_rates, $exchange_rates_to_update);die();

		// We can update exchange rates only if an exchange rates provider has been
		// configured correctly
		if($this->validate_exchange_rates_provider_settings($settings) === true) {
			// Update exchange rates in three cases:
			// - If none is present
			// - If the list of exchange rates to update is not empty
			// - If button "Save and update Exchange Rates" has been clicked
			if(empty($settings[self::FIELD_EXCHANGE_RATES]) || !empty($exchange_rates_to_update) ||
				 (isset($_POST[self::SETTINGS_KEY]) && get_value('update_exchange_rates_button', $_POST[self::SETTINGS_KEY]))) {

				// Fetch the latest exchange rates and merge them with the one entered manually
				if($this->update_exchange_rates($settings, $errors) === false) {
					$this->add_multiple_settings_errors($errors);
				}
				else {
					$settings[self::FIELD_EXCHANGE_RATES_LAST_UPDATE] = current_time('timestamp');
					// This is not an "error", but a confirmation message. Unfortunately,
					// WordPress only has "add_settings_error" to add messages of any type
					add_settings_error(self::SETTINGS_KEY,
														 'exchange-rates-updated',
														 __('Settings saved. Exchange rates have been updated.', $this->textdomain),
														 'updated');
				}
			}
		}

		// Validate the VAT number to be used for VIES requests
		// @since 1.9.0.181022
		$settings[self::FIELD_VIES_REQUESTER_VAT_NUMBER] = isset($settings[self::FIELD_VIES_REQUESTER_VAT_NUMBER]) ? trim($settings[self::FIELD_VIES_REQUESTER_VAT_NUMBER]) : '';
		if($this->validate_vies_requester_vat_number($settings[self::FIELD_VIES_REQUESTER_VAT_COUNTRY], $settings[self::FIELD_VIES_REQUESTER_VAT_NUMBER])) {
			// VIES validation settings
			// @since 1.9.0.181022
			$processed_settings[self::FIELD_VIES_REQUESTER_VAT_COUNTRY] = $settings[self::FIELD_VIES_REQUESTER_VAT_COUNTRY];
			$processed_settings[self::FIELD_VIES_REQUESTER_VAT_NUMBER] = $settings[self::FIELD_VIES_REQUESTER_VAT_NUMBER];
		}

		if(!empty($this->validation_errors)) {
			$this->show_validation_errors();
		}

		// Return the array processing any additional functions filtered by this action.
		return apply_filters('wc_aelia_eu_vat_assistant_settings', $processed_settings, $settings);
	}

	/**
	 * Class constructor.
	 */
	public function __construct($settings_key = self::SETTINGS_KEY,
															$textdomain = '',
															\Aelia\WC\Settings_Renderer $renderer = null) {
		if(empty($renderer)) {
			// Instantiate the render to be used to generate the settings page
			$renderer = new \Aelia\WC\Settings_Renderer();
		}
		parent::__construct($settings_key, $textdomain, $renderer);

		// Register available exchange rates models
		$this->register_exchange_rates_models();

		add_action('admin_init', array($this, 'init_settings'));

		// If no settings are registered, save the default ones
		if($this->load() === null) {
			$this->save();
		}
	}

	/**
	 * Factory method.
	 *
	 * @param string settings_key The key used to store and retrieve the plugin settings.
	 * @param string textdomain The text domain used for localisation.
	 * @return \Aelia\WC\Settings
	 */
	public static function factory($settings_key = self::SETTINGS_KEY,
																 $textdomain = '') {
		$class = get_called_class();
		$settings_manager = new $class($settings_key, $textdomain);

		return $settings_manager;
	}

	/**
	 * Registers a model used to retrieve Exchange Rates.
	 */
	// TODO Refactor logic to share the exchange rates models with the ones provided by the Currency Switcher, when installed
	protected function register_exchange_rates_model($class_name, $label) {
		if(!class_exists($class_name) ||
			 !in_array('Aelia\WC\IExchangeRatesModel', class_implements($class_name))) {
			throw new Exception(sprintf(__('Attempted to register class "%s" as an Exchange Rates ' .
																		 'model, but the class does not exist, or does not implement '.
																		 'Aelia\WC\IExchangeRatesModel interface.', $this->textdomain),
																	$class_name));
		}

		$model_id = md5($class_name);
		$model_info = new stdClass();
		$model_info->class_name = $class_name;
		$model_info->label = $label;
		$this->_exchange_rates_models[$model_id] = $model_info;
	}

	/**
	 * Registers all the available models to retrieve Exchange Rates.
	 */
	// TODO Refactor logic to share the exchange rates models with the ones provided by the Currency Switcher, when installed
	protected function register_exchange_rates_models() {
		$namespace_prefix = '\\' . __NAMESPACE__ . '\\';
		// Allow 3rd parties to add their own models
		$exchange_rates_models = apply_filters('aelia_wc_exchange_rates_models', array(
			$namespace_prefix . 'Exchange_Rates_BitPay_Model' => __('BitPay', $this->textdomain),
			$namespace_prefix . 'Exchange_Rates_ECB_Model' => __('ECB', $this->textdomain),
			$namespace_prefix . 'Exchange_Rates_HMRC_Model' => __('HMRC (UK)', $this->textdomain),
			$namespace_prefix . 'Exchange_Rates_IrishRevenueHTML_Model' => __('Irish Revenue (HTML) - WARNING: experimental, may not always work!', $this->textdomain),
			$namespace_prefix . 'Exchange_Rates_DNB_Model' => __('Danish National Bank', $this->textdomain),
			// The Exchange_Rates_ECB_Historical_Model is used by reports. It's added to this list, but
			// commented out, so that it can enabled and used for testing as needed
			//$namespace_prefix . 'Exchange_Rates_ECB_Historical_Model' => __('ECB - Historical', $this->textdomain),
		));
		asort($exchange_rates_models);

		foreach($exchange_rates_models as $model_class => $model_lanel) {
			$this->register_exchange_rates_model($model_class, $model_lanel);
		}
	}

	/**
	 * Returns a list of the available exchange rates models.
	 *
	 * @return array
	 */
	public function exchange_rates_providers_options() {
		$result = array();
		foreach($this->_exchange_rates_models as $key => $properties) {
			$result[$key] = __(get_value('label', $properties), $this->textdomain);
		}
		return $result;
	}

	/**
	 * Configures the schedule to automatically update the exchange rates.
	 *
	 * @param array current_settings An array containing current plugin settings.
	 * @param array new_settings An array containing new plugin settings.
	 */
	protected function set_exchange_rates_update_schedule(array $current_settings, array $new_settings) {
		// Clear exchange rates update schedule, if it was disabled
		$new_schedule_enabled = isset($new_settings[self::FIELD_EXCHANGE_RATES_UPDATE_ENABLE]) ? $new_settings[self::FIELD_EXCHANGE_RATES_UPDATE_ENABLE] : 0;

		if($new_schedule_enabled != self::ENABLED_YES) {
			wp_clear_scheduled_hook($this->_exchange_rates_update_hook);
		}
		else {
			$current_schedule_enabled = isset($current_settings[self::FIELD_EXCHANGE_RATES_UPDATE_ENABLE]) ? $current_settings[self::FIELD_EXCHANGE_RATES_UPDATE_ENABLE] : 0;
			$current_schedule = isset($current_settings[self::FIELD_EXCHANGE_RATES_UPDATE_SCHEDULE]) ? $current_settings[self::FIELD_EXCHANGE_RATES_UPDATE_SCHEDULE] : '';
			$new_schedule = isset($new_settings[self::FIELD_EXCHANGE_RATES_UPDATE_SCHEDULE]) ? $new_settings[self::FIELD_EXCHANGE_RATES_UPDATE_SCHEDULE] : '';

			// If exchange rates update is still scheduled, check if its schedule changed.
			// If it changed, remove old schedule and set a new one.
			if(($current_schedule != $new_schedule) ||
				 ($current_schedule_enabled != $new_schedule_enabled)) {
				wp_clear_scheduled_hook($this->_exchange_rates_update_hook);
				//var_dump($new_schedule);die();
				wp_schedule_event(current_time('timestamp'), $new_schedule, $this->_exchange_rates_update_hook);
			}
		}
	}

	/**
	 * Displays the validation errors (if any).
	 */
	protected function show_validation_errors() {
		foreach($this->validation_errors as $error_key => $error_message) {
			add_settings_error(self::SETTINGS_KEY, $error_key, $error_message);
		}
	}

	/**
	 * Updates the exchange rates. Triggered by a scheduled task.
	 */
	public function scheduled_update_exchange_rates() {
		$settings = $this->current_settings();
		if($this->update_exchange_rates($settings, $errors) === true) {
			// Save the timestamp of last update
			$settings[self::FIELD_EXCHANGE_RATES_LAST_UPDATE] = current_time('timestamp');
		}
		$this->save($settings);
	}

	/*** Validation methods ***/
	/**
	 * Validates a list of exchange rates.
	 *
	 * @param array A list of exchange rates.
	 * @return bool True, if the validation succeeds, false otherwise.
	 */
	protected function validate_exchange_rates($exchange_rates, &$exchange_rates_to_update = array()) {
		$currency_with_invalid_rates = array();
		foreach($exchange_rates as $currency => $settings) {
			$exchange_rate = get_value('rate', $settings);
			if(!is_numeric($exchange_rate) || empty($exchange_rate)) {
				if((get_value('set_manually', $settings, 0) === 1)) {
					// Exchange rate is invalid and it was set manually. Add it to the error list
					$currency_with_invalid_rates[] = $currency;
				}
				else {
					// Exchange rate is invalid and it was set to update automatically.
					// Add it to the update list
					$exchange_rates_to_update[] = $currency;
				}
			}
		}
		if(!empty($currency_with_invalid_rates)) {
			$this->validation_errors['invalid-rate'] = sprintf(__('Some exchange rates entered manually are invalid. ' .
																														'Please review the rates for the following ' .
																														'currencies: %s.',
																														$this->textdomain),
																												 implode(', ', $currency_with_invalid_rates));
		}
		return empty($currency_with_invalid_rates);
	}

	/**
	 * Validates settings for the selected exchange rates provider.
	 *
	 * @param array settings An array of settings.
	 * @return bool
	 */
	protected function validate_exchange_rates_provider_settings($settings) {
		// TODO Implement validation as needed
		return true;
	}

	/**
	 * Validates the VIES Requester VAT number, if one was entered.
	 *
	 * @param string $country
	 * @param string $vat_number
	 * @return string
	 * @since 1.9.0.181022
	 */
	protected function validate_vies_requester_vat_number($country, $vat_number) {
		$result = true;
		if(!empty($vat_number)) {
			// Validate the requested VAT number
			//
			// IMPORTANT
			// The validation must be performed WITHOUT passing the "requester VAT number" (last two argument),
			// for the following reasons:
			// - The VAT number we are validating IS the requester VAT number.
			// - We don't need a consultation number, for this validation.
			// - If the "original" requester VAT number is not valid, it would cause the validation of the new
			//   requester VAT number to fail. This would make it impossible to save the new number.
			// @since 1.10.1.191108
			$validation_response = apply_filters('wc_aelia_eu_vat_assistant_validate_vat_number', false, $country, $vat_number, true, '', '');

			if($validation_response['euva_validation_result'] !== Definitions::VAT_NUMBER_VALIDATION_VALID) {
				// Get the country name, to make the error message clearer
				// @since 1.13.2.200319
				$country_name = WC()->countries->countries[$country] ? WC()->countries->countries[$country] : $country;

				$message = implode(' ', array(
					__('Invalid requester VAT number entered in section <em>Options > VIES Validation</em>.', $this->textdomain),
					__('The new requester VAT number has NOT been saved, because it would cause the VAT validation to fail at checkout.', $this->textdomain),
					'<br /><br />',
					sprintf(__('Selected VAT Country: "%1$s".', $this->textdomain), $country_name),
					sprintf(__('Entered VAT Number: "%1$s".', $this->textdomain), $vat_number),
					sprintf(__('Raw validation response (JSON): %s', $this->textdomain), '<pre>' . json_encode($validation_response, JSON_PRETTY_PRINT) . '</pre>'),
				));

				$this->validation_errors['invalid-vies-requester-vat-number'] = $message;
				$result = false;
			}
		}
		return $result;
	}

	/**
	 * Returns the VIES Requester VAT number.
	 *
	 * @return array
	 * @since 1.9.0.181022
	 */
	public function get_vies_requester_vat_number() {
		$vat_number = $this->get(self::FIELD_VIES_REQUESTER_VAT_NUMBER);
		if(!empty($vat_number)) {
			return array(
				'vat_country' => $this->get(self::FIELD_VIES_REQUESTER_VAT_COUNTRY),
				'vat_number' => $vat_number,
			);
		}
		return array(
			'vat_country' => '',
			'vat_number' => '',
		);
	}
}
