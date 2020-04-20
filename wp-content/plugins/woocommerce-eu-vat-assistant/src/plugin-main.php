<?php
namespace Aelia\WC\EU_VAT_Assistant;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

//define('SCRIPT_DEBUG', 1);
//error_reporting(E_ALL);

require_once('lib/classes/definitions/definitions.php');

use Aelia\WC\Aelia_Plugin;
use Aelia\WC\IP2Location;
use Aelia\WC\EU_VAT_Assistant\Settings;
use Aelia\WC\EU_VAT_Assistant\Settings_Renderer;
use Aelia\WC\Messages;
use Aelia\WC\EU_VAT_Assistant\Logger as Logger;
use \Exception;
use \InvalidArgumentException;

/**
 * EU VAT Assistant plugin.
 */
class WC_Aelia_EU_VAT_Assistant extends Aelia_Plugin {
	public static $version = '1.13.3.200325';

	public static $plugin_slug = Definitions::PLUGIN_SLUG;
	public static $text_domain = Definitions::TEXT_DOMAIN;
	public static $plugin_name = 'WooCommerce EU VAT Assistant';

	/**
	 * The slug used to check for updates.
	 *
	 * @var string
	 * @since 1.9.1.181209
	 */
	public static $slug_for_update_check = Definitions::PLUGIN_SLUG_FOR_UPDATES;

	/**
	 * A list of countries to which sales are allowed. The list is altered by the
	 * plugin if the admin populated the list of countries to which the sale is
	 * not allowed.
	 * @var array
	 */
	protected $allowed_sale_countries;

	// @var string The country to which a VAT number applies (if any).
	protected $vat_country;
	// @var string The VAT number entered by the customer at checkout.
	protected $vat_number;
	// @var bool Indicates if the VAT number was validated.
	protected $vat_number_validated;
	// @var EU_VAT_Validation The instance of the EU VAT Number validator.
	protected $_eu_vat_validation;
	// @var array The raw validation response returned by the VAT validation service.
	protected $raw_vat_validation_response;

	/** Shop's base country. Used to determine if a VAT exemption can be applied
	 * (usually, exemption cannot be applied to customers located in shop's base
	 * country).
	 * @var string
	 * @since 1.4.12.150923
	 */
	protected $shop_base_country;

	/**
	 * Initialises and returns the plugin instance.
	 *
	 * @return Aelia\WC\EU_VAT_Assistant\WC_Aelia_EU_VAT_Assistant
	 */
	public static function factory() {
		// Load Composer autoloader
		require_once(__DIR__ . '/vendor/autoload.php');

		// Example on how to initialise a settings controller and a messages controller
		$settings_page_renderer = new Settings_Renderer();
		$settings_controller = new Settings(Settings::SETTINGS_KEY,
																				self::$text_domain,
																				$settings_page_renderer);
		$messages_controller = new Messages(self::$text_domain);

		$plugin_instance = new self($settings_controller, $messages_controller);
		return $plugin_instance;
	}

	/**
	 * Constructor.
	 *
	 * @param \Aelia\WC\Settings settings_controller The controller that will handle
	 * the plugin settings.
	 * @param \Aelia\WC\Messages messages_controller The controller that will handle
	 * the messages produced by the plugin.
	 */
	public function __construct($settings_controller = null,
															$messages_controller = null) {
		// Load Composer autoloader
		require_once(__DIR__ . '/vendor/autoload.php');

		parent::__construct($settings_controller, $messages_controller);

		// The commented line below is needed for Codestyling Localization plugin to
		// understand what text domain is used by this plugin
		//load_plugin_textdomain('wc-aelia-eu-vat-assistant', false, $this->path('languages') . '/');
	}

	/**
	 * Indicates if debug mode is active.
	 *
	 * @return bool
	 */
	public function debug_mode() {
		return self::settings()->get(Settings::FIELD_DEBUG_MODE);
	}

	/**
	 * Returns an instance of the EU VAT numbers validator.
	 *
	 * @return EU_VAT_Validation
	 * @since 1.3.20.150330
	 */
	protected function eu_vat_validation() {
		if(empty($this->_eu_vat_validation)) {
		// Instantiate the the EU VAT numbers validator
			$this->_eu_vat_validation = EU_VAT_Validation::factory();
		}
		return $this->_eu_vat_validation;
	}

	/**
	 * Returns the country corresponding to visitor's IP address.
	 *
	 * @return string
	 */
	protected function get_ip_address_country() {
		if(empty($this->ip_address_country)) {
			$this->ip_address_country = apply_filters('wc_aelia_eu_vat_assistant_ip_address_country', IP2Location::factory()->get_visitor_country());
		}
		return $this->ip_address_country;
	}

	/**
	 * Returns an array with all the tax types in use in the European Union, together
	 * with their descriptions.
	 *
	 * @return array
	 */
	public function get_eu_vat_rate_types() {
		return array(
			'standard_rate' => __('Standard rates', self::$text_domain),
			'reduced_rate' => __('Reduced rates', self::$text_domain),
			'reduced_rate_alt' => __('Reduced rates (alternative)', self::$text_domain),
			'super_reduced_rate' => __('Super reduced rates', self::$text_domain),
			'parking_rate' => __('Parking rates', self::$text_domain),
		);
	}

	/**
	 * Returns a list of countries to which EU VAT applies. This method takes into
	 * account countries such as Monaco and Isle of Man, which are not returned as
	 * part of EU countries by WooCommerce.
	 *
	 * @return array
	 */
	public function get_eu_vat_countries() {
		if(empty($this->eu_vat_countries)) {
			$this->eu_vat_countries = $this->wc()->countries->get_european_union_countries();
			// Add countries that are not strictly EU countries, but to which EU VAT rules apply
			$this->eu_vat_countries[] = 'MC';
			$this->eu_vat_countries[] = 'IM';
			// Add "GB", in case it's missing. This is needed in WooCommerce 4.0 and later, which
			// removed the UK from the list of the EU countries
			// @since 1.13.3.200325
			// @since WooCommerce 4.0
			$this->eu_vat_countries[] = 'GB';

			// Remove duplicates
			$this->eu_vat_countries = array_unique($this->eu_vat_countries);
		}
		return apply_filters('wc_aelia_eu_vat_assistant_eu_vat_countries', $this->eu_vat_countries);
	}

	/**
	 * Indicates if the plugin has been configured.
	 *
	 * @return bool
	 */
	public function plugin_configured() {
		$vat_currency = $this->settings_controller()->get(Settings::FIELD_VAT_CURRENCY);
		return !empty($vat_currency);
	}

	/**
	 * Checks if the VAT rates retrieved by the EU VAT Assistant are valid. Rates
	 * are valid when, for each country, they contain at least a standard rate
	 * (invalid rates often have a "null" object associated to them).
	 *
	 * @param array vat_rates An array containing the VAT rates for all EU countries.
	 * @return bool
	 */
	protected function valid_eu_vat_rates($vat_rates) {
		foreach($vat_rates as $country_code => $rates) {
			if(empty($rates['standard_rate']) ||
				 !is_numeric($rates['standard_rate'])) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Retrieves the EU VAT rats from https://euvatrates.com website.
	 *
	 * @return array|null An array with the details of VAT rates, or null on failure.
	 * @link https://euvatrates.com
	 */
	public function get_eu_vat_rates() {
		$vat_rates = get_transient(Definitions::TRANSIENT_EU_VAT_RATES);
		if(!empty($vat_rates) && is_array($vat_rates)) {
			return $vat_rates;
		}

		$eu_vat_source_urls = array(
			// Forked Github repository at https://github.com/aelia-co/euvatrates.com
			// @since 1.7.16.171215
			'https://raw.githubusercontent.com/aelia-co/euvatrates.com/master/rates.json',
			// Original VAT rate service. It should work, although it might contain
			// obsolete VAT rates
			'http://euvatrates.com/rates.json',
		);

		// Go through the available URLs to get the rates
		foreach($eu_vat_source_urls as $eu_vat_url) {
			$eu_vat_response = wp_remote_get($eu_vat_url, array(
				'timeout' => 5,
			));

			// Stop as soon as we get a valid response (i.e. NOT a WP Error)
			if(!is_wp_error($eu_vat_response)) {
				// We got a valid response. Now ensure that the VAT rates are in the
				// correct format
				$vat_rates = json_decode(get_value('body', $eu_vat_response), true);
				if(($vat_rates === null) || !is_array($vat_rates) || !isset($vat_rates['rates'])) {
					// If VAT rates are in the wrong format, log the error and move to the
					// next source
					$this->get_logger()->notice(__('Unexpected response returned by EU VAT rates site.', self::$text_domain), array(
						'Remote Site' => $eu_vat_url,
						'Returned Data' => $eu_vat_response['body'],
					));
					// Force the $vat_rates to null, so we will know later on that the data
					// was in the wrong format
					$vat_rates = null;
				}
				else {
					// If we reach this point, we got a valid response and the VAT rates
					// are most likely in the correct format. We can stop the loop here
					break;
				}
			}
			else {
				// If we reach this point, the response is a "WP error". We can log it,
				// for debugging purposes
				$this->get_logger()->notice(__('Could not fetch EU VAT rates from remote site.', self::$text_domain), array(
					'Remote Site' => $eu_vat_url,
					'Error Messages' => $eu_vat_response->get_error_messages(),
				));
			}
		}

		// If we reach this point and the VAT rates are still null, then the rates
		// could not be retrieved
		// @since 1.7.16.171215
		if($vat_rates === null) {
			return null;
		}

		// Add rates for countries that use other countries' tax rates
		// Monaco uses French VAT
		$vat_rates['rates']['MC'] = $vat_rates['rates']['FR'];
		$vat_rates['rates']['MC']['country'] = 'Monaco';

		// Isle of Man uses UK's VAT
		$vat_rates['rates']['IM'] = $vat_rates['rates']['UK'];
		$vat_rates['rates']['IM']['country'] = 'Isle of Man';

		// Fix the country codes received from the feed. Some country codes are
		// actually the VAT country code. We need the ISO Code instead.
		$country_codes_to_fix = array(
			'EL' => 'GR',
			'UK' => 'GB',
		);
		foreach($country_codes_to_fix as $code => $correct_code) {
			$vat_rates['rates'][$correct_code] = $vat_rates['rates'][$code];
			unset($vat_rates['rates'][$code]);
		}

		/* Fix the VAT rates for countries that don't have a reduced VAT rate. For
		 * those countries, the standard rate should be used as the "reduced" rate.
		 *
		 * @since 1.4.15.151029
		 */
		foreach($vat_rates['rates'] as $country_code => $rates) {
			if(!is_numeric($rates['reduced_rate'])) {
				$rates['reduced_rate'] = $rates['standard_rate'];
			}
			$vat_rates['rates'][$country_code] = $rates;
		}

		ksort($vat_rates['rates']);

		// Debug
		//var_dump($vat_rates);die();

		// Ensure that the VAT rates are valid before caching them
		if($this->valid_eu_vat_rates($vat_rates['rates'])) {
			// Cache the VAT rates, to prevent unnecessary calls to the remote site
			set_transient(Definitions::TRANSIENT_EU_VAT_RATES, $vat_rates, 2 * HOUR_IN_SECONDS);
		}
		return $vat_rates;
	}

	/**
	 * Stores the list of countries to which sales are allowed, before the plugin
	 * alters it.
	 */
	protected function store_allowed_sale_countries() {
		$this->allowed_sale_countries = wc()->countries->get_allowed_countries();
	}

	/**
	 * Returns the list of countries to which sales are not allowed.
	 * @return array
	 */
	protected function get_sale_disallowed_countries() {
		return $this->_settings_controller->get(Settings::FIELD_SALE_DISALLOWED_COUNTRIES, array());
	}

	/**
	 * Performs operation when woocommerce has been loaded.
	 */
	public function woocommerce_loaded() {
		if(!is_admin()) {
			// Add logic to filter the countries to which sales are allowed
			$this->store_allowed_sale_countries();
			$restricted_countries = $this->get_sale_disallowed_countries();
			if(!empty($restricted_countries)) {
				add_filter('pre_option_woocommerce_allowed_countries', array($this, 'pre_option_woocommerce_allowed_countries'), 10, 1);
				add_filter('woocommerce_countries_allowed_countries', array($this, 'woocommerce_countries_allowed_countries'), 10, 1);
			}
		}

		// Store shop base country for later use. This is necessary to prevent a
		// conflict with the Tax Display plugin, which may override the base country
		// at checkout
		$this->shop_base_country = $this->wc()->countries->get_base_country();

		// Show admin messages in the backend
		// @since 1.10.1.191108
		if(!self::is_frontend()) {
			$this->show_admin_messages();
		}
	}

	/**
	 * Shows admin messages in the backend.
	 *
	 * @since 1.10.1.191108
	 */
	public function show_admin_messages() {
		if((@$_GET['page'] === 'wc-reports') &&
			 (@$_GET['tab'] === 'taxes') &&
			 (@$_GET['report'] === 'sales_summary_report')) {
			// When on the Sales Summary (VAT RTD) page, inform customers of the new VAT RTD report
			// @since 1.10.1.191108
			Messages::admin_message(
				implode(' ', array(
					__('The Sales Summary (VAT RTD) report has been rewritten, to improve performance and accuracy.', self::$text_domain),
					__('We tested the new report on as many different environments as we could, to ensure that the results are consistent with other EU VAT Assistant reports.', self::$text_domain),
					__('If you come across any glitch, or a case which our tests did not cover, please feel free to contact us by using one of the links below.', self::$text_domain),
					'<ul>',
					'<li>',
					 sprintf(__('If you purchased a premium support service, or you wish to send us confidential information, please <a href="%1$s">use our contact form</a>.', self::$text_domain), Definitions::URL_CONTACT_FORM),
					 '</li>',
					'<li>',
					sprintf(__('If you have not purchased a premium support service, please <a href="%1$s">use the public support forum</a>.', self::$text_domain), Definitions::URL_PUBLIC_SUPPORT_FORUM),
					'</li>',
					'</ul>',
				)),
				array(
					'sender_id' => self::$plugin_slug,
					'level' => E_USER_NOTICE,
					'code' => Definitions::NOTICE_NEW_SALES_SUMMARY_REPORT,
					'dismissable' => true,
					'permissions' => 'manage_woocommerce',
					'message_header' => __('Important', $this->text_domain),
			));
		}
	}

	/**
	 * Sets the hooks required by the plugin.
	 */
	protected function set_hooks() {
		parent::set_hooks();

		// Allow 3rd parties to validate a VAT number. This filter must be
		// made available even when the EU VAT Assistant has not yet been
		// configured, as the call can be used while saving the settings,
		// to validate the seller's VAT number.
		// @since 1.8.2.180919
		add_filter('wc_aelia_eu_vat_assistant_validate_vat_number', array($this, 'wc_aelia_eu_vat_assistant_validate_vat_number'), 10, 6);

		if(is_admin() && !$this->plugin_configured()) {
			add_action('admin_notices', array($this, 'settings_notice'));
			// Don't set any hook until the plugin has been configured
			return;
		}

		if(!is_admin() || self::doing_ajax()) {
			// Set hooks that should be used on frontend only
			Frontend_Integration::init();
		}

		if(is_admin()) {
			// Set hooks that should be used on backend only
			$this->set_admin_hooks();
		}

		// Order actions
		add_action('woocommerce_checkout_update_order_meta', array($this, 'woocommerce_checkout_update_order_meta'), 20, 2);
		add_action('woocommerce_checkout_update_user_meta', array($this, 'woocommerce_checkout_update_user_meta'), 20, 2);
		// Add the VAT number and self certification fields to the billing fields
		// @since 1.8.4.181009
		add_action('woocommerce_checkout_fields', array($this, 'woocommerce_checkout_fields'), 40, 1);

		// The output of VAT number and self certification fields is now disabled.
		// @deprecated 1.8.4.181009
		//add_action('woocommerce_checkout_billing', array($this, 'woocommerce_checkout_billing'), 40, 1);

		// Update VAT data for subscription renewals
		add_action('woocommerce_subscriptions_renewal_order_created', array($this, 'woocommerce_subscriptions_renewal_order_created'), 20, 2);

		// Checkout hooks
		add_action('woocommerce_checkout_update_order_review', array($this, 'woocommerce_checkout_update_order_review'));
		add_action('woocommerce_checkout_process', array($this, 'woocommerce_checkout_process'));

		// Ajax hooks
		add_action('wp_ajax_validate_eu_vat_number', array($this, 'wp_ajax_validate_eu_vat_number'));
		add_action('wp_ajax_nopriv_validate_eu_vat_number', array($this, 'wp_ajax_validate_eu_vat_number'));
		add_action('wp_ajax_collect_order_vat_info', array($this, 'wp_ajax_collect_order_vat_info'));

		// Cron hooks
		// Add hooks to automatically update exchange rates
		add_action($this->_settings_controller->exchange_rates_update_hook(),
							 array($this->_settings_controller, 'scheduled_update_exchange_rates'));

		// Integration with Currency Switcher
		add_action('wc_aelia_currencyswitcher_settings_saved', array($this, 'wc_aelia_currencyswitcher_settings_saved'));

		// UI
		add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
		add_filter('woocommerce_order_formatted_billing_address', array($this, 'woocommerce_order_formatted_billing_address'), 10, 2);
		add_filter('woocommerce_formatted_address_replacements', array($this, 'woocommerce_formatted_address_replacements'), 10, 2);
		add_filter('woocommerce_localisation_address_formats', array($this, 'woocommerce_localisation_address_formats'), 10, 1);
		// Order Edit page - Add the VAT number to the billing fields
		// @since 1.6.2.160315
		add_filter('woocommerce_admin_billing_fields', array('\Aelia\WC\EU_VAT_Assistant\Orders_Integration', 'woocommerce_admin_billing_fields'), 10, 1);

		// Fix to ensure that WooCommerce saves the EU VAT Assistant order meta on the Edit Order page
		// @since 1.9.6.190208
		add_filter('woocommerce_process_shop_order_meta', array('\Aelia\WC\EU_VAT_Assistant\Orders_Integration', 'woocommerce_process_shop_order_meta'), 45, 1);

		// Add the VAT number to customer's billing details on the Edit Order page
		// @since 1.9.6.190208
		add_filter('woocommerce_ajax_get_customer_details', array('\Aelia\WC\EU_VAT_Assistant\Orders_Integration', 'woocommerce_ajax_get_customer_details'), 10, 3);

		// User profile page
		// @since 1.7.17.180106
		add_filter('woocommerce_customer_meta_fields', array($this, 'woocommerce_customer_meta_fields'), 10, 1);

		// Hooks to be called by 3rd party
		// Allow 3rd parties to convert a value from one currency to another
		add_filter('wc_aelia_eu_vat_assistant_convert', array($this, 'convert'), 10, 4);
		add_filter('wc_aelia_eu_vat_assistant_get_order_exchange_rate', array($this, 'wc_aelia_eu_vat_assistant_get_order_exchange_rate'), 10, 2);
		add_filter('wc_aelia_eu_vat_assistant_get_setting', array($this, 'wc_aelia_eu_vat_assistant_get_setting'), 10, 2);

		// Determines if an order is VAT exempt, following EU VAT rules
		// @since 1.8.0.180604
		add_filter('woocommerce_order_is_vat_exempt', array($this, 'woocommerce_order_is_vat_exempt'), 10, 2);

		// Store VAT data is against a refund when it's saved
		//
		// NOTE
		// This features has been deliberately disabled because it might not be needed
		// and could increase the size of the order meta stored in the database.
		// @since 1.10.1.191108
		//add_filter('woocommerce_refund_created', array('\Aelia\WC\EU_VAT_Assistant\Orders_Integration', 'woocommerce_refund_created'), 10, 2);

		// Reports
		ReportsManager::init();
	}

	protected function set_admin_hooks() {
		Tax_Settings_Integration::set_hooks();
		Products_Integration::init();
	}

	/**
	 * Determines if one of plugin's admin pages is being rendered. Override it
	 * if plugin implements pages in the Admin section.
	 *
	 * @return bool
	 */
	protected function rendering_plugin_admin_page() {
		$screen = get_current_screen();
		$page_id = is_object($screen) ? $screen->id : '';

		return ($page_id == 'woocommerce_page_' . Definitions::MENU_SLUG);
	}

	/**
	 * Registers the script and style files needed by the admin pages of the
	 * plugin. Extend in descendant plugins.
	 */
	protected function register_plugin_admin_scripts() {
		// Scripts
		wp_register_script('chosen',
											 '//cdnjs.cloudflare.com/ajax/libs/chosen/1.1.0/chosen.jquery.min.js',
											 array('jquery'),
											 null,
											 true);

		// Styles
		wp_register_style('chosen',
												'//cdnjs.cloudflare.com/ajax/libs/chosen/1.1.0/chosen.min.css',
												array(),
												null,
												'all');
		// WordPress already includes jQuery UI script, but no CSS for it. Therefore,
		// we need to load it from an external source
		wp_register_style('jquery-ui',
											'//code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css',
											array(),
											null,
											'all');

		wp_enqueue_style('jquery-ui');
		wp_enqueue_style('chosen');

		wp_enqueue_script('jquery-ui-tabs');
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script('chosen');

		parent::register_plugin_admin_scripts();
	}

	/**
	 * Loads the scripts required in the Admin section.
	 */
	public function load_admin_scripts() {
		parent::load_admin_scripts();
		$this->localize_admin_scripts();
	}

	/**
	 * Loads the settings that will be used by the admin scripts.
	 */
	protected function localize_admin_scripts() {
		// Prepare parameters for common admin scripts
		$admin_scripts_params = array();

		// Prepare parameters for Tax Settings pages
		if((get_value('page', $_GET) == 'wc-settings') &&
			 (get_value('tab', $_GET) == 'tax')) {
			$admin_scripts_params = Tax_Settings_Integration::localize_admin_scripts($admin_scripts_params);
		}

		// Prepare parameters for Orders pages
		// @since 1.6.1.160201
		if(Orders_Integration::editing_order()) {
			$admin_scripts_params = Orders_Integration::localize_admin_scripts($admin_scripts_params);
		}

		// Add localization parameters for EU VAT plugin settings page
		if($this->rendering_plugin_admin_page()) {
			$admin_scripts_params = array_merge($admin_scripts_params, array(
				'eu_vat_countries' => $this->get_eu_vat_countries(),
				'user_interface' => array(
					'add_eu_countries_trigger' => __('Add European Union countries', self::$text_domain),
				),
			));
		}

		wp_localize_script(static::$plugin_slug . '-admin-common',
											 'aelia_eu_vat_assistant_admin_params',
											 $admin_scripts_params);
	}

	/**
	 * Loads Styles and JavaScript for the frontend. Extend as needed in
	 * descendant classes.
	 */
	public function load_frontend_scripts() {
		// Enqueue the required Frontend stylesheets
		wp_enqueue_style(static::$plugin_slug . '-frontend');

		// JavaScript
		wp_enqueue_script(static::$plugin_slug . '-frontend');

		$this->localize_frontend_scripts();
	}

	/**
	 * Loads the settings that will be used by the frontend scripts.
	 */
	protected function localize_frontend_scripts() {
		// Prepare parameters for common admin scripts

		// Build the list of countries for which the EU VAT field will be displayed
		// at checkout
		$eu_vat_countries = $this->get_eu_vat_countries();

		$frontend_scripts_params = array(
			'tax_based_on' => get_option('woocommerce_tax_based_on'),
			'eu_vat_countries' => $eu_vat_countries,
			'ajax_url' => admin_url('admin-ajax.php', 'relative'),
			'show_self_cert_field' => self::settings()->get(Settings::FIELD_SHOW_SELF_CERTIFICATION_FIELD),
			'eu_vat_field_required' => self::settings()->get(Settings::FIELD_EU_VAT_NUMBER_FIELD_REQUIRED),
			'hide_self_cert_field_with_valid_vat' => self::settings()->get(Settings::FIELD_HIDE_SELF_CERTIFICATION_FIELD_VALID_VAT_NUMBER),
			'ip_address_country' => $this->get_ip_address_country(),
			'use_shipping_as_evidence' => self::settings()->get(Settings::FIELD_USE_SHIPPING_ADDRESS_AS_EVIDENCE),
			'user_interface' => array(
				'self_certification_field_title' => __(self::settings()->get(Settings::FIELD_SELF_CERTIFICATION_FIELD_TITLE), self::$text_domain),
			),
			'show_eu_vat_number_for_base_country' => (bool)self::settings()->get(Settings::FIELD_SHOW_EU_VAT_FIELD_IF_CUSTOMER_IN_BASE_COUNTRY),
			'shop_base_country' => $this->shop_base_country,
			// Use a nonce to validate Ajax requests coming from the frontend
			// @since 1.9.0.181022
			'_ajax_nonce' => wp_create_nonce('aelia-euva-frontend')
		);

		wp_localize_script(static::$plugin_slug . '-frontend',
											 'aelia_eu_vat_assistant_params',
											 $frontend_scripts_params);
	}

	/**
	 * Returns a list of the report IDs introduced by this plugin.
	 *
	 * @return array A list of report IDs.
	 * @since 1.5.8.160112
	 */
	protected function get_available_reports() {
		return array(
			'eu_vat_by_country_report',
			'vies_report',
			'intrastat_report',
			'sales_summary_report',
		);
	}

	/**
	 * Registers the script and style files required in the backend (even outside
	 * of plugin's pages). Extend in descendant plugins.
	 */
	protected function register_common_admin_scripts() {
		parent::register_common_admin_scripts();

		// The admin styles of this plugin are required throughout the WooCommerce
		// Administration, not just on the plugin settings page
		wp_register_style(static::$plugin_slug . '-admin',
											$this->url('plugin') . '/design/css/admin.css',
											array(),
											null,
											'all');
		wp_enqueue_style(static::$plugin_slug . '-admin');

		// Load common JavaScript for the Admin section
		wp_enqueue_script(static::$plugin_slug . '-admin-common',
											$this->url('js') . '/admin/admin-common.js',
											array('jquery'),
											null,
											true);

		if((get_value('page', $_GET) == 'wc-reports') &&
			 in_array(get_value('report', $_GET), $this->get_available_reports())) {
			// Load JavaScript for reports
			wp_enqueue_script(static::$plugin_slug . '-jquery-bbq',
												$this->url('js') . '/admin/jquery.ba-bbq.min.js',
												array('jquery'),
												null,
												true);
			wp_enqueue_script(static::$plugin_slug . '-admin-reports',
												$this->url('js') . '/admin/admin-reports.js',
												array('jquery'),
												null,
												true);
		}
	}

	/**
	 * Adds an error to WooCommerce, so that it can be displayed to the customer.
	 *
	 * @param string error_message The error message to display.
	 */
	protected function add_woocommerce_error($error_message) {
		wc_add_notice($error_message, 'error');
	}

	/**
	 * Converts an amount from a Currency to another.
	 *
	 * @param float amount The amount to convert.
	 * @param string from_currency The source Currency.
	 * @param string to_currency The destination Currency.
	 * @param int price_decimals The amount of decimals to use when rounding the
	 * converted result.
	 * @return float The amount converted in the destination currency.
	 */
	public function convert($amount, $from_currency, $to_currency, $price_decimals = null) {
		// No need to try converting an amount that is not numeric. This can happen
		// quite easily, as "no value" is passed as an empty string
		if(!is_numeric($amount)) {
			return $amount;
		}

		// No need to spend time converting a currency to itself
		if($from_currency == $to_currency) {
			return $amount;
		}

		if(empty($price_decimals)) {
			$price_decimals = absint(get_option('woocommerce_price_num_decimals'));
		}

		// Retrieve exchange rates from the configuration
		$exchange_rates = $this->_settings_controller->get_exchange_rates();
		//var_dump($exchange_rates);
		try {
			$error_message_template = __('Currency conversion - %s currency not valid or exchange rate ' .
																	 'not found for: "%s". Please make sure that the EU VAT assistant '.
																	 'plugin is configured correctly and that an Exchange ' .
																	 'Rate has been specified for each of the available currencies.',
																	 self::$text_domain);
			$from_currency_rate = get_value($from_currency, $exchange_rates, null);
			if(empty($from_currency_rate)) {
				$error_message = sprintf($error_message_template,
																 __('Source', self::$text_domain),
																 $from_currency);
				$this->get_logger()->error($error_message);
				if($this->debug_mode()) {
					throw new \InvalidArgumentException($error_message);
				}
			}

			$to_currency_rate = get_value($to_currency, $exchange_rates, null);
			if(empty($to_currency_rate)) {
				$error_message = sprintf($error_message_template,
																 __('Destination', self::$text_domain),
																 $from_currency);
				$this->get_logger()->error($error_message);
				if($this->debug_mode()) {
					throw new InvalidArgumentException($error_message);
				}
			}

			$exchange_rate = $to_currency_rate / $from_currency_rate;
		}
		catch(Exception $e) {
			$full_message = $e->getMessage() .
											sprintf(__('Stack trace: %s', Definitions::TEXT_DOMAIN),
															$e->getTraceAsString());
			$this->get_logger()->error($full_message);
			trigger_error($full_message, E_USER_ERROR);
		}
		return round($amount * $exchange_rate, $price_decimals);
	}

	/**
	 * Returns the exchange rate used to calculate the VAT for an order in the
	 * currency that has to be used for VAT returns.
	 *
	 * @param int order_id The ID of the order from which to retrieve the exchange
	 * rate.
	 * @return float|false The exchange rate, if present, or false if it was not
	 * saved against the order.
	 */
	public function get_order_vat_exchange_rate($order_id) {
		$order = new Order($order_id);
		$vat_exchange_rate = $order->get_vat_data('vat_currency_exchange_rate');
		return $vat_exchange_rate;
	}

	/**
	 * Returns a full VAT number, inclusive of country prefix.
	 *
	 * @param string country A country code.
	 * @param string vat_number A VAT number.
	 * @param string A full VAT number, with the country prefix (e.g. IE1234567X)
	 * @since 1.3.20.150330
	 */
	public function get_full_vat_number($country, $vat_number) {
		$vat_number = $this->eu_vat_validation()->parse_vat_number($vat_number);
		// If an invalid VAT number was passed, return an empty string
		if(!$vat_number) {
			return false;
		}
		$vat_prefix = $this->eu_vat_validation()->get_vat_prefix($country);
		return $vat_prefix . $vat_number;
	}

	/**
	 * Stores details of the VIES response returned for a VAT number
	 * validation request.
	 *
	 * @param int $order_id
	 * @param array $raw_vies_response
	 * @since 1.9.0.181022
	 */
	protected function store_vies_response($order_id, $raw_vies_response) {
		update_post_meta($order_id, 'vies_response', $raw_vies_response);

		if(is_array($raw_vies_response) && isset($raw_vies_response['raw_response']) && is_array($raw_vies_response['raw_response'])) {
			// Change all the keys to lower-case. This is to avoid issues with keys being
			// returned like "requestIdentifier", "RequestIdentifier", "requestidentifier" by different versions
			// of the VIES service
			// @since 1.10.0.191023
			// @link https://wordpress.org/support/topic/vat-validated-but-no-consultation-numbers-any-more/#post-12059080
			$raw_vies_response['raw_response'] = array_change_key_case($raw_vies_response['raw_response'], CASE_LOWER);
		}

		if(!empty($raw_vies_response['raw_response']['requestidentifier'])) {
			update_post_meta($order_id, 'vies_consultation_number', $raw_vies_response['raw_response']['requestidentifier']);
		}
		else {
			update_post_meta($order_id, 'vies_consultation_number', __('Not returned', self::$text_domain));
		}
	}

	/**
	 * Saves the evidence used to determine the VAT rate to apply.
	 *
	 * @param Aelia_Order order The order to process.
	 */
	protected function save_eu_vat_data($order_id) {
		// Save the VAT number details
		$order_vat_number = $this->get_full_vat_number($this->vat_country, $this->vat_number);
		if($order_vat_number == false) {
			$this->get_logger()->debug(__('Invalid VAT number specified for order.', self::$text_domain), array(
				'Order ID' => $order_id,
				'VAT Number' => $order_vat_number,
			));
			$order_vat_number = '';
		}
		update_post_meta($order_id, 'vat_number', $order_vat_number);
		update_post_meta($order_id, '_vat_country', $this->vat_country);
		update_post_meta($order_id, '_vat_number_validated', $this->vat_number_validated);

		// Store additional information about the VIES response
		// @since 1.9.0.181022
		$this->store_vies_response($order_id, $this->raw_vat_validation_response);

		// Store customer's self-certification flag
		if(get_value(Definitions::ARG_LOCATION_SELF_CERTIFICATION, $_POST, 0)) {
			$location_self_certified = Definitions::YES;
		}
		else {
			$location_self_certified = Definitions::NO;
		}
		update_post_meta($order_id, '_customer_location_self_certified', $location_self_certified);

		// Use plugins' internal Order class, which implements convenience methods
		// to handle EU VAT reguirements
		$order = new Order($order_id);
		// Generate and store details about order VAT
		$order->update_vat_data();
		// Save EU VAT compliance evidence
		$order->store_vat_evidence();
	}

	/**
	 * Renders the EU VAT field using the appropriate view.
	 *
	 * @deprecated 1.8.4.181009
	 */
	// protected function render_eu_vat_field() {
	// 	wc_get_template('checkout-eu-vat-field.php', array(), 'woocommerce/eu-vat-assistant', $this->path('views') . '/frontend/');
	// }

	/**
	 * Renders the self-certification field using the appropriate view.
	 *
	 * @deprecated 1.8.4.181009
	 */
	// protected function render_self_certification_field() {
	// 	wc_get_template('checkout-self-certification-field.php', array(), 'woocommerce/eu-vat-assistant', $this->path('views') . '/frontend/');
	// }

	/**
	 * Validates an EU VAT number.
	 *
	 * @param string country A country code.
	 * @param string vat_number A VAT number.
	 * @param string $requester_country
	 * @param string $requester_vat_number
	 * @return array An array with the result of the validation.
	 */
	protected function validate_eu_vat_number($country, $vat_number, $requester_country = null, $requester_vat_number = null) {
		// If the requester country and VAT number have been passed explicitly, take
		// them as they are
		// @since 1.10.1.191108
		if(($requester_country !== null) && ($requester_vat_number !== null)) {
			$vies_requester_vat_number = array(
				'vat_country' => $requester_country,
				'vat_number' => $requester_vat_number,
			);
		}
		else {
			// If the requester country and VAT number have not been passed, take them
			// from the settings
			// @since 1.9.0.181022
			$vies_requester_vat_number = self::settings()->get_vies_requester_vat_number();
		}

		// Validate the VAT number
		$validation_result = $this->eu_vat_validation()->validate_vat_number(
			$country,
			$vat_number,
			$vies_requester_vat_number['vat_country'],
			$vies_requester_vat_number['vat_number']
		);
		// TODO Refactor the communication with the EU_VAT_Validation class to make it easier to replace it with other classes

		// Check if the validation should be attempted again, following an "invalid requester" error
		// @since 1.11.0.191108
		if(!empty($validation_result['errors']) && is_array($validation_result['errors'])) {
			// Extract the error message, if any
			$validation_error = $validation_result['errors'][0];

			// If the response returned by the VIES service is "invalid requester", check if the plugin
			// is configured to retry the validation without requester data
			if((strcasecmp($validation_error, 'INVALID_REQUESTER_INFO') == 0) &&
				 self::settings()->get(Settings::FIELD_RETRY_VALIDATION_WHEN_REQUESTER_VAT_NUMBER_INVALID, true)) {
					$this->get_logger()->info(__('Remote VIES service returned an "invalid requester" error. Retrying validation without requester information.', self::$text_domain));

					// If needed, retry the validation without requester data
					$validation_result = $this->eu_vat_validation()->validate_vat_number(
						$country,
						$vat_number,
						'',
						''
					);
			}
		}

		// A validation result of "null" indicates a failure in sending the SOAP
		// request, therefore we can't process it
		if($validation_result['valid'] !== null) {
			// Extract the error message, if any
			$validation_error = $validation_result['errors'][0];
			if(!empty($validation_error)) {
				// If the remote server was busy, we may have to accept the VAT number as valid,
				// depending on plugin settings
				if(strcasecmp($validation_error, 'SERVER_BUSY') == 0) {
					// Check if the VAT number should be accepted when the remote service is busy
					$validation_result['valid'] = self::settings()->get(Settings::FIELD_ACCEPT_VAT_NUMBER_WHEN_VALIDATION_SERVER_BUSY, true);

					if($validation_result['valid']) {
						// Log every VAT number accepted with a SERVER_BUSY response
						// @since 1.11.0.191108
						$this->get_logger()->warning(__('VAT Number considered valid, despite the server returning an error, due to plugin settings.', self::$text_domain), array(
							'VAT Country' => $country,
							'VAT Number' => $vat_number,
							'Requested VAT Country' => $requester_country,
							'Requester VAT Number' => $requester_vat_number,
							'Validation Response' => $validation_result,
							'Error returned by VIES service' => $validation_error,
							'VAT Number accepted due to setting' => Settings::FIELD_ACCEPT_VAT_NUMBER_WHEN_VALIDATION_SERVER_BUSY,
							'Setting Value' => self::settings()->get(Settings::FIELD_ACCEPT_VAT_NUMBER_WHEN_VALIDATION_SERVER_BUSY, true),
						));
					}
				}

				// If the remote service was unavailable, we may have to accept the VAT number as valid,
				// depending on plugin settings
				// @since 1.11.0.191108
				if((strcasecmp($validation_error, 'SERVICE_UNAVAILABLE') == 0) ||
					 (strcasecmp($validation_error, 'MS_UNAVAILABLE') == 0)) {
					// Check if the VAT number should be accepted when the remote service is unavailable
					$validation_result['valid'] = self::settings()->get(Settings::FIELD_ACCEPT_VAT_NUMBER_WHEN_SERVICE_UNAVAILABLE, false);

					if($validation_result['valid']) {
						// Log every VAT number accepted with a SERVICE_UNAVAILABLE or MS_UNAVAILABLE response
						// @since 1.11.0.191108
						$this->get_logger()->warning(__('VAT Number considered valid, despite the server returning an error, due to plugin settings.', self::$text_domain), array(
							'VAT Country' => $country,
							'VAT Number' => $vat_number,
							'Requested VAT Country' => $requester_country,
							'Requester VAT Number' => $requester_vat_number,
							'Validation Response' => $validation_result,
							'Error returned by VIES service' => $validation_error,
							'VAT Number accepted due to setting' => Settings::FIELD_ACCEPT_VAT_NUMBER_WHEN_SERVICE_UNAVAILABLE,
							'Setting Value' => self::settings()->get(Settings::FIELD_ACCEPT_VAT_NUMBER_WHEN_SERVICE_UNAVAILABLE, false),
						));
					}
				}

				// If the remote service is busy due to too many requests, we may have to accept the VAT number as valid,
				// depending on plugin settings
				// @since 1.11.0.191108
				if((strcasecmp($validation_error, 'GLOBAL_MAX_CONCURRENT_REQ') == 0) ||
					 (strcasecmp($validation_error, 'MS_MAX_CONCURRENT_REQ') == 0)) {
					// Check if the VAT number should be accepted when the remote service is unavailable
					$validation_result['valid'] = self::settings()->get(Settings::FIELD_ACCEPT_VAT_NUMBER_WHEN_SERVICE_UNAVAILABLE, false);

					if($validation_result['valid']) {
						// Log every VAT number accepted with a SERVICE_UNAVAILABLE or MS_UNAVAILABLE response
						// @since 1.11.0.191108
						$this->get_logger()->warning(__('VAT Number considered valid, despite the server returning an error, due to plugin settings.', self::$text_domain), array(
							'VAT Country' => $country,
							'VAT Number' => $vat_number,
							'Requested VAT Country' => $requester_country,
							'Requester VAT Number' => $requester_vat_number,
							'Validation Response' => $validation_result,
							'Error returned by VIES service' => $validation_error,
							'VAT Number accepted due to setting' => Settings::FIELD_ACCEPT_VAT_NUMBER_WHEN_SERVICE_UNAVAILABLE,
							'Setting Value' => self::settings()->get(Settings::FIELD_ACCEPT_VAT_NUMBER_WHEN_SERVICE_UNAVAILABLE, false),
						));
					}
				}
			}
		}

		return apply_filters('wc_aelia_euva_eu_vat_number_raw_validation_result', $validation_result, $country, $vat_number);
	}

	/**
	 * Determines if a country is part of the EU.
	 *
	 * @param string country The country code to check.
	 * @return bool
	 */
	public function is_eu_country($country) {
		return in_array($country, $this->get_eu_vat_countries());
	}

	/**
	 * Determines if there is enough evidence about customer's location to satisfy
	 * EU requirements. The method collects all the country codes that can be derived
	 * from the data posted at checkout, and looks for one that occurs twice or
	 * more. As per EU regulations, we only need two matching pieces of evidence.
	 *
	 * @param array posted_data The data posted at checkout.
	 * @return string|bool The code of the country with the most entries, if one
	 * with at least two occurrences is found, or false if no country appears more
	 * than once.
	 */
	protected function sufficient_location_evidence($posted_data) {
		// Collect all the countries we can get from the posted data
		$countries = array();

		$countries[] = $billing_country = get_value('billing_country', $posted_data);
		$countries[] = $this->get_ip_address_country();

		// Take shipping country as evidence only if explicitly told so
		if(self::settings()->get(Settings::FIELD_USE_SHIPPING_ADDRESS_AS_EVIDENCE)) {
			if(get_value('ship_to_different_address', $posted_data)) {
				$countries[] = get_value('shipping_country', $posted_data);
			}
			else {
				// If shipping to the same address as billing, add the billing country again
				// as a further proof of location
				$countries[] = $billing_country;
			}
		}

		$countries = array_filter(apply_filters('wc_aelia_eu_vat_assistant_location_evidence_countries', $countries));

		// Calculate how many times each country appears in the list and sort the
		// array by count, in descending order, so that the country with most matches
		// is at the top
		$country_count = array_count_values($countries);
		arsort($country_count);
		reset($country_count);

		// We only need at least two matching entries in the array. If that is the
		// case, return the country code (it may be useful later, and it's better than
		// a simple "true")
		$top_country = key($country_count);
		if($country_count[$top_country] >= 2) {
			return $top_country;
		}
		// If the top country doesn't have at least a count of two, then the other
		// entries won't have it either. In such case, inform the caller that we
		// don't have sufficient evidence
		return false;
	}

	/**
	 * Sets customer's VAT exemption, depending on his country and the VAT number
	 * he entered.
	 *
	 * @param string country The country against which the VAT exemption will be checked.
	 * @param string vat_number The VAT number entered by the customer.
	 * @return int A numeric result code indicating if the check succeeded, whether
	 * the customer is VAT exempt or not, or failed (i.e. when an EU customer
	 * entered an invalid EU VAT number).
	 */
	protected function set_customer_vat_exemption($customer_country, $vat_number) {
		$this->get_logger()->info(__('Setting customer VAT exemption.', self::$text_domain), array(
			'Country' => $customer_country,
			'VAT Number' => $vat_number,
		));

		$result = Definitions::RES_OK;
		// Assume that customer is not VAT exempt. This is a sensible default, as
		// exemption applies only in specific cases, and only for EU customers.
		// Customers outside the EU are not technically "VAT exempt". To them, a
		// special "Zero VAT" rate applies
		$customer_vat_exemption = false;
		// Clear VAT information before validation
		$this->vat_country = '';
		$this->vat_number = '';
		$this->vat_number_validated = Definitions::VAT_NUMBER_VALIDATION_NO_NUMBER;
		$this->raw_vat_validation_response = array();

		// If VAT number was hidden, customer cannot be made VAT exempt. We can skip
		// the checks, in such case
		if(self::settings()->get(Settings::FIELD_EU_VAT_NUMBER_FIELD_REQUIRED) != Settings::OPTION_EU_VAT_NUMBER_FIELD_HIDDEN) {
			// No need to check the VAT number if either the country or the number are empty
			if(!empty($customer_country) && !empty($vat_number)) {
				// Store the VAT information. They will be used later, to update the order
				// when it's finalised
				$this->vat_country = $customer_country;
				$this->vat_number = $vat_number;

				// Customer is based in the European Union, therefore EU VAT rules and validations apply
				if($this->is_eu_country($customer_country)) {
					// Validate the VAT number for EU customers
					$this->raw_vat_validation_response = $this->validate_eu_vat_number($customer_country, $vat_number);

					// Debug
					//var_dump($this->raw_vat_validation_response);

					// If the EU VAT number is valid, we must determine if the customer should
					// be considered exempt from VAT
					if($this->raw_vat_validation_response['valid'] == true) {
						/* An EU customer will be considered exempt from VAT if:
						 * - He is located in a country different from shop's base country.
						 * - He is located in the same country as the shop, and option "remove VAT
						 *   when customer in located in shop's base country" is enabled.
						 */
						if(($customer_country != $this->shop_base_country) ||
							 self::settings()->get(Settings::FIELD_REMOVE_VAT_IF_CUSTOMER_IN_BASE_COUNTRY)) {
							$customer_vat_exemption = true;
						}
						$this->vat_number_validated = Definitions::VAT_NUMBER_VALIDATION_VALID;
					}
					else {
						// A "null" response means that nothing was returned by the server.
						// In such case, log an error
						if($this->raw_vat_validation_response['valid'] === null) {
							$this->get_logger()->error(__('EU VAT Number could not be validated due to errors in ' .
																						'the communication with the remote service.', self::$text_domain), array(
								'Errors' => $this->raw_vat_validation_response['errors'],
							));
							$this->vat_number_validated = Definitions::VAT_NUMBER_COULD_NOT_BE_VALIDATED;
							$result = Definitions::ERR_COULD_NOT_VALIDATE_VAT_NUMBER;
						}
						else {
							$this->vat_number_validated = Definitions::VAT_NUMBER_VALIDATION_NOT_VALID;
							$result = Definitions::ERR_INVALID_EU_VAT_NUMBER;
						}
					}
				}
				else {
					$this->vat_number_validated = Definitions::VAT_NUMBER_VALIDATION_NON_EU;
				}
			}
		}
		else {
			$this->get_logger()->info(__('EU VAT Number field was hidden by plugin configuration. Customer ' .
																	 'cannot be made VAT exempt.', self::$text_domain));
		}
		$this->get_logger()->debug(__('VAT exemption check completed.', self::$text_domain), array(
			'Customer Exemption' => $customer_vat_exemption,
			'Result' => $result,
		));

		$customer_vat_exemption = apply_filters('wc_aelia_eu_vat_assistant_customer_vat_exemption', $customer_vat_exemption, $this->vat_country, $this->vat_number, $this->vat_number_validated, $this->raw_vat_validation_response);

		$this->wc()->customer->set_is_vat_exempt($customer_vat_exemption);
		return $result;
	}

	/**
	 * Updates the order meta after it has been created, or updated, at checkout.
	 *
	 * @param int order_id The order just created, or updated.
	 * @param array posted_data The data posted to create the order.
	 */
	public function woocommerce_checkout_update_order_meta($order_id, $posted_data) {
		$this->save_eu_vat_data($order_id);
	}

	/**
	 * Updates the metadata of a renewal order created by the Subscriptions plugin.
	 *
	 * @param WC_Order $renewal_order The renewal order.
	 * @param WC_Order $original_order The original order used to create the renewal order.
	 */
	public function woocommerce_subscriptions_renewal_order_created($renewal_order, $original_order) {
		// If we are processing checkout, no action needs to be taken. Handler for
		// "woocommerce_checkout_update_order_meta" hook will take care of saving the
		// necessary data
		if(is_checkout()) {
			return;
		}

		// Use the internal Order classes
		$original_order = new Order(aelia_get_order_id($original_order));
		$renewal_order = new Order(aelia_get_order_id($renewal_order));

		// Copy the VAT evidence from the original subscription order. A renewal order
		// is created without customer's intervention, therefore
		$original_order_vat_info = array(
			Order::META_EU_VAT_EVIDENCE => $original_order->eu_vat_evidence,
			'vat_number' => $original_order->get_customer_vat_number(),
			'_vat_country' => $original_order->vat_country,
			'_vat_number_validated' => $original_order->vat_number_validated,
			'_customer_location_self_certified' => $original_order->customer_location_self_certified,
		);
		$original_order_vat_info = apply_filters('wc_aelia_eu_vat_assistant_subscription_original_order_vat_info', $original_order_vat_info, $original_order, $renewal_order);
		foreach($original_order_vat_info as $meta_key => $value) {
			$renewal_order->set_meta($meta_key, $value)	;
		}
		// Generate and store details about order VAT
		$renewal_order->update_vat_data();
	}

	/**
	 * Updates the customer meta at checkout.
	 *
	 * @param int user_id The user ID who placed the order.
	 * @param array posted_data The data posted to create the order.
	 */
	public function woocommerce_checkout_update_user_meta($user_id, $posted_data) {
		update_user_meta($user_id, 'vat_number', $this->get_full_vat_number($this->vat_country, $this->vat_number));
		update_user_meta($user_id, '_vat_country', $this->vat_country);
		update_user_meta($user_id, '_vat_number_validated', $this->vat_number_validated);
	}

	/**
	 * Renders the EU VAT field on the checkout form.
	 *
	 * @deprecated 1.8.4.181009
	 */
	// public function woocommerce_checkout_billing() {
	// 	// Allow 3rd parties to hide the default VAT number field
	// 	// @since 1.7.13.171025
	// 	$is_vat_field_required = self::settings()->get(Settings::FIELD_EU_VAT_NUMBER_FIELD_REQUIRED);
	// 	$show_vat_field = apply_filters('wc_aelia_eu_vat_assistant_show_vat_field', $is_vat_field_required != Settings::OPTION_EU_VAT_NUMBER_FIELD_HIDDEN, $is_vat_field_required);

	// 	// Render EU VAT Number element, unless it's hidden
	// 	if($show_vat_field) {
	// 		$this->render_eu_vat_field();
	// 	}

	// 	// Allow 3rd parties to hide the default self-certification field
	// 	// @since 1.7.13.171025
	// 	$show_self_certification_field_setting = self::settings()->get(Settings::FIELD_SHOW_SELF_CERTIFICATION_FIELD);
	// 	$show_self_certification_field = apply_filters('wc_aelia_eu_vat_assistant_show_self_certification_field', $show_self_certification_field_setting != Settings::OPTION_SELF_CERTIFICATION_FIELD_NO, $show_self_certification_field_setting);

	// 	// Render self-certification element, unless it's hidden
	// 	if($show_self_certification_field) {
	// 		$this->render_self_certification_field();
	// 	}
	// }

	/**
	 * Renders the VAT number and self certification fields at checkout.
	 *
	 * @param array $fields
	 * @return array
	 * @since 1.8.4.181009
	 */
	public function woocommerce_checkout_fields($fields) {
		$current_user = wp_get_current_user();

		$is_vat_field_required = self::settings()->get(Settings::FIELD_EU_VAT_NUMBER_FIELD_REQUIRED);
		// Allow 3rd parties to show or hide the VAT number field
		if(apply_filters('wc_aelia_eu_vat_assistant_show_checkout_field_vat_number', $is_vat_field_required != Settings::OPTION_EU_VAT_NUMBER_FIELD_HIDDEN, $is_vat_field_required)) {
			// Add the new VAT number field
			$fields['billing']['vat_number'] = array(
				'type'  => 'text',
				'default'  => is_object($current_user) ? $current_user->vat_number : '',
				'label' => '<strong>' . __(self::settings()->get(Settings::FIELD_EU_VAT_FIELD_TITLE), self::$text_domain) . '</strong>',
				'placeholder' => __(self::settings()->get(Settings::FIELD_EU_VAT_FIELD_TITLE), self::$text_domain),
				'description' => __(self::settings()->get(Settings::FIELD_EU_VAT_FIELD_DESCRIPTION), self::$text_domain),
				'validate' => array(),
				// If the VAT number field is configured to be "always required", set the attribute accordingly. All other
				// settings need to leave the field as "optional", because it's required only in specific circumstances.
				// @since 1.10.0.191023
				'required' => ($is_vat_field_required === Settings::OPTION_EU_VAT_NUMBER_FIELD_REQUIRED),
				'wrapper_class' => array('aelia_eu_vat_assistant checkout_field'),
				'class' => array('aelia_wc_eu_vat_assistant vat_number update_totals_on_change address-field form-row-wide'),
				'custom_attributes' => array(
					'valid' => 0,
				),
				'priority' => self::settings()->get(Settings::FIELD_VAT_NUMBER_FIELD_POSITION),
			);
		}

		$show_self_certification_field_setting = self::settings()->get(Settings::FIELD_SHOW_SELF_CERTIFICATION_FIELD);
		// Allow 3rd parties to show or hide the self certification field
		if(apply_filters('wc_aelia_eu_vat_assistant_show_checkout_field_self_certification', $show_self_certification_field_setting != Settings::OPTION_SELF_CERTIFICATION_FIELD_NO, $show_self_certification_field_setting)) {
			// Add the new VAT number field
			$fields['billing']['customer_location_self_certified'] = array(
				'type'  => 'checkbox',
				'default'  => is_object($current_user) ? $current_user->vat_number : '',
				'label' => '<span class="self_certification_label">' . __(self::settings()->get(Settings::FIELD_SELF_CERTIFICATION_FIELD_TITLE), self::$text_domain) . '</span>',
				'description' => __('Due to European regulations, we have to ask you to confirm your location.', self::$text_domain),
				'validate' => array(),
				'required' => false,
				'wrapper_class' => array('aelia_eu_vat_assistant checkout_field'),
				'class' => array(
					'aelia_wc_eu_vat_assistant',
					'location_self_certification',
					'update_totals_on_change',
					'address-field form-row-wide'
				),
				'custom_attributes' => array(
					'valid' => 0,
				),
				'priority' => self::settings()->get(Settings::FIELD_SELF_CERTIFICATION_FIELD_POSITION),
			);
		}

		return $fields;
	}

	/**
	 * Ajax handler. Validates an EU VAT number using VIES service and outputs
	 * a JSON response with the validation result.
	 */
	public function wp_ajax_validate_eu_vat_number() {
		// Validate the nonce before processing the request
		// @since 1.9.0.181022
		check_ajax_referer('aelia-euva-frontend', '_ajax_nonce', true);

		// Fetch the raw validation response from the VIES service
		$validation_response = $this->validate_eu_vat_number(
			get_value(Definitions::ARG_COUNTRY, $_GET),
			get_value(Definitions::ARG_VAT_NUMBER, $_GET)
		);

		// Extract the validation status to return to the client. The rest of the
		// information is not important
		$response = array(
			'valid' => isset($validation_response['valid']) ? $validation_response['valid'] : false,
		);

		// Validate EU VAT number and return the result as JSON
		wp_send_json($response);
	}

	/**
	 * Performs VAT validation operations during order review.
	 *
	 * @param array form_data The data posted from the order review page.
	 */
	public function woocommerce_checkout_update_order_review($form_data) {
		// $form_data contains an HTTP query string. Let's "explode" it into an
		// array
		parse_str($form_data, $posted_data);

		// Debug
		//var_dump("POSTED DATA", $posted_data);die();

		// Cannot continue without the billing country
		if(empty($posted_data['billing_country'])) {
			return;
		}

		// Determine which country will be used to calculate taxes
		switch(get_option('woocommerce_tax_based_on')) {
			case 'billing' :
			case 'base' :
				$country = !empty($posted_data['billing_country']) ? $posted_data['billing_country'] : '';
			break;
			case 'shipping' :
				if(get_value('ship_to_different_address', $posted_data) && !empty($posted_data['shipping_country'])) {
					$country = $posted_data['shipping_country'];
				}
				else {
					$country = $posted_data['billing_country'];
				}
			break;
		}

		// Check if customer is VAT exempt
		$country = wc_clean($country);
		if(empty($country)) {
			$this->get_logger()->error(__('Unexpected condition occurred: no customer country was posted ' .
																		'to "checkout update order review" event.', self::$text_domain), array(
				'Posted Data' => $posted_data,
			));
		}
		$vat_number = wc_clean(get_value('vat_number', $posted_data));
		$this->set_customer_vat_exemption($country, $vat_number);
	}

	/**
	 * Indicates if a customers is VAT exempt.
	 *
	 * @return bool
	 * @since 1.7.5.170405
	 */
	protected function customer_is_vat_exempt() {
		// Use the new WC 3.0 method, if available. If not, default to the object
		// property
		return method_exists($this->wc()->customer, 'get_is_vat_exempt') ? $this->wc()->customer->get_is_vat_exempt() : $this->wc()->customer->is_vat_exempt;
	}

	/**
	 * Performs validations to make sure that either there is enough evidence to
	 * determine customer's location, or customer had self-certified his location.
	 * This method automatically adds a checkout error when needed.
	 *
	 * @return void
	 */
	protected function validate_self_certification() {
		// If the self-certification element was forcibly hidden, it doesn't make
		// sense to perform this validation
		if(self::settings()->get(Settings::FIELD_SHOW_SELF_CERTIFICATION_FIELD) == Settings::OPTION_SELF_CERTIFICATION_FIELD_NO) {
			return;
		}

		/* We need to check the available gelocation evidence in two cases:
		 * - When customer is not VAT exempt.
		 * - Regardless of the VAT number, if option "hide self certification field
		 *   with valid VAT number" is DISABLED.
		 *
		 * In all other cases, there must be sufficient evidence for the order to
		 * go through, or customer must self-certify.
		 */
		if(($this->customer_is_vat_exempt() == false) ||
			 (self::settings()->get(Settings::FIELD_HIDE_SELF_CERTIFICATION_FIELD_VALID_VAT_NUMBER) == false)) {
			// Check if we have sufficient evidence about customer's location
			$sufficient_location_evidence_result = $this->sufficient_location_evidence($_POST);
			if($sufficient_location_evidence_result == false) {
				// Convenience variable, to better understand the check below
				$ignore_insufficient_evidence = ($this->vat_number_validated == Definitions::VAT_NUMBER_VALIDATION_VALID);
				/* If insufficient location evidence has been provided, check if self-certification
				 * is required to accept the order. If it is required, and it was not provided,
				 * stop the checkout and inform the customer.
				 */
				if(!$ignore_insufficient_evidence &&
					 self::settings()->get(Settings::FIELD_SELF_CERTIFICATION_FIELD_REQUIRED_WHEN_CONFLICT) &&
					(get_value(Definitions::ARG_LOCATION_SELF_CERTIFICATION, $_POST) == false)) {
					// Inform the customer that he must self-certify to proceed with the purchase
					$error = __('Unfortunately, we could not collect sufficient information to confirm ' .
											'your location. To proceed with the order, please tick the box below the ' .
											'billing details to confirm that you will be using the product(s) in ' .
											'country you selected.',
											self::$text_domain);
					$this->add_woocommerce_error($error);
				}
			}
		}
	}

	/**
	 * Indicates if a valid EU VAT number is required to complete checkout. A VAT
	 * number is required in the following cases:
	 * - When the requirement setting is "always required".
	 * - When the requirement setting is "required for EU only" and customer
	 *   selected a billing country that is part of the EU.
	 *
	 * @param string customer_country The country for which the check should be
	 * performed.
	 * @return bool
	 */
	protected function is_eu_vat_number_required($customer_country) {
		$result = false;
		/* If the VAT field is visible for shop's base country, or the customer is
		 * not in shop's base country, then we can check if it should be required.
		 */
		if(self::settings()->get(Settings::FIELD_SHOW_EU_VAT_FIELD_IF_CUSTOMER_IN_BASE_COUNTRY) ||
			 ($customer_country != $this->shop_base_country)) {
			$eu_vat_number_required = self::settings()->get(Settings::FIELD_EU_VAT_NUMBER_FIELD_REQUIRED);
			/* The EU VAT field is required in one of the following cases
			 * 1- If the related option is set to "required".
			 * 2- If the option is set to "only if company name is filled" and the
			 *    customer entered a company name.
			 * 3- If the option is set "only for EU customers" and the customer selected
			 *    a EU country.
			 */
			$result = ($eu_vat_number_required == Settings::OPTION_EU_VAT_NUMBER_FIELD_REQUIRED) ||
								// Check if option is "required only if company has been entered"
								// and the company field is not empty
								(($eu_vat_number_required == Settings::OPTION_EU_VAT_NUMBER_FIELD_REQUIRED_IF_COMPANY_FILLED) &&
									!empty($_POST['billing_company']) && (trim($_POST['billing_company']) != '')) ||
								// Check if option is "required only if company has been entered
								// and address is in the EU", and the company field is not empty
								(($eu_vat_number_required == Settings::OPTION_EU_VAT_NUMBER_FIELD_REQUIRED_IF_COMPANY_FILLED_EU_ONLY) &&
									$this->is_eu_country($customer_country) &&
									!empty($_POST['billing_company']) && (trim($_POST['billing_company']) != '')) ||
								// Check if option is "required only if company is in EU" and the
								// company is in the EU
								(($eu_vat_number_required == Settings::OPTION_EU_VAT_NUMBER_FIELD_REQUIRED_EU_ONLY) && $this->is_eu_country($customer_country));
		}

		return apply_filters('wc_aelia_euva_order_is_eu_vat_number_required', $result, $customer_country);
	}

	/**
	 * Performs validations related to the EU VAT Number, to ensure that customer
	 * has entered all the information required to complete the checkout.
	 * This method automatically adds a checkout error when needed.
	 *
	 * @return void
	 */
	protected function validate_vat_exemption() {
		// Check if customer is VAT exempt and set him as such
		if(aelia_wc_version_is('>=', '2.7')) {
			$customer_country = $this->wc()->customer->get_billing_country();
		}
		else {
			$customer_country = $this->wc()->customer->get_country();
		}

		// If customer's country is empty on customer object, take it from the data
		// posted at checkout
		if(empty($customer_country)) {
			$this->get_logger()->info(__('Billing country on Customer object is empty. Retrieving ' .
																	 'it from posted data.', self::$text_domain));
			$customer_country = !empty($_POST['billing_country']) ? $_POST['billing_country'] : '';

			if(empty($customer_country)) {
				$this->get_logger()->error(__('Unexpected condition occurred: no customer country was posted ' .
																			'during checkout. VAT exemption cannot be applied correctly.',
																			self::$text_domain), array(
					'Posted Data' => $_POST,
				));
			}
		}

		$vat_number = get_value(Definitions::ARG_VAT_NUMBER, $_POST, '');
		// Check if customer is VAT exempt and set his exemption accordingly
		$exemption_check_result = $this->set_customer_vat_exemption($customer_country, $vat_number);

		// If VAT Number is required, but it was not entered or it's not valid,
		// display the appropriate error message and stop the checkout
		if($this->is_eu_vat_number_required($customer_country) &&
			 ($this->vat_number_validated != Definitions::VAT_NUMBER_VALIDATION_VALID)) {
			$error = sprintf(__('You must enter a valid EU VAT number to complete the purchase.', self::$text_domain), $vat_number);
			$this->add_woocommerce_error($error);
			return;
		}

		// If VAT number is optional, check if customer may be allowed to complete
		// the purchase anyway
		if($exemption_check_result == Definitions::ERR_INVALID_EU_VAT_NUMBER &&
			 // If "store invalid VAT numbers" option is enabled, don't show any error
			 // The VAT number will be recorded with the order, but the customer won't
			 // be made exempt from VAT
			 self::settings()->get(Settings::FIELD_STORE_INVALID_VAT_NUMBERS) == false) {
			// Show an error when VAT number validation fails at checkout
			$error = sprintf(__('VAT number "%s" is not valid for your country.', self::$text_domain), $vat_number);
			$this->add_woocommerce_error($error);
		}
	}

	/**
	 * Performs VAT validation operations during checkout.
	 *
	 * @param array posted_data The data posted from the order review page.
	 */
	public function woocommerce_checkout_process() {
		$this->validate_vat_exemption();
		$this->validate_self_certification();
	}

	/**
	 * Triggered when the Currency Switcher settings are updated. It updates
	 * exchange rates automatically, so that they will include any new currency
	 * that might have been added.
	 */
	public function wc_aelia_currencyswitcher_settings_saved() {
		self::settings()->scheduled_update_exchange_rates();
	}

	/**
	 * Adds meta boxes to the admin interface.
	 *
	 * @see add_meta_boxes().
	 */
	public function add_meta_boxes() {
		add_meta_box('wc_aelia_eu_vat_assistant_order_vat_info_box',
								 __('VAT information', self::$text_domain),
								 array($this, 'render_order_vat_info_box'),
								 'shop_order',
								 'side',
								 'default');
	}

	/**
	 * Renders the box with the VAT information associated to an order.
	 *
	 * @param int order_id The ID of the target order. If empty, the ID of the
	 * current post is taken.
	 */
	public function render_order_vat_info_box($order_id = null) {
		if(empty($order_id)) {
			global $post;
			$order_id = $post->ID;
		}

		$order = new Order($order_id);
		include_once($this->path('views') . '/admin/order-vat-info-box.php');
	}

	/**
	 * Handler for "wc_aelia_eu_vat_assistant_get_order_exchange_rate" hook. It
	 * just acts as a wrapper for WC_Aelia_EU_VAT_Assistant::get_order_vat_exchange_rate()
	 * method.
	 *
	 * @param float default_rate The default exchange rate to return if the order
	 * doesn't have any.
	 * @param int order_id The ID of the order from which the exchange rate should
	 * be retrieved.
	 * @return float
	 */
	public function wc_aelia_eu_vat_assistant_get_order_exchange_rate($default_rate, $order_id) {
		$vat_exchange_rate = $this->get_order_vat_exchange_rate($order_id);
		if($vat_exchange_rate === false) {
			$vat_exchange_rate = $default_rate;
		}
		return $vat_exchange_rate;
	}

	/**
	 * Retrieves the value of a plugin's setting.
	 *
	 * @param mixed default_value The default value to return if the setting is
	 * not found.
	 * @param string setting_key The setting to retrieved.
	 * @return mixed
	 */
	public function wc_aelia_eu_vat_assistant_get_setting($defaut_value, $setting_key) {
		return self::settings()->get($setting_key, $defaut_value);
	}

	/**
	 * Filter for hook "wc_aelia_eu_vat_assistant_validate_vat_number".
	 * Validates a VAT number and returns the validation result. The function can return
	 * the raw validation response from the validation service, or just an integer indicating
	 * if the VAT number is valid or not.
	 *
	 * @param string $country
	 * @param string $vat_number
	 * @param bool $return_raw_validation_response
	 * @param string $requester_country
	 * @param string $requester_vat_number
	 * @return int|array
   * @since 1.8.2.180919
	 */
	public function wc_aelia_eu_vat_assistant_validate_vat_number($vat_number_validated, $country, $vat_number, $return_raw_validation_response = false, $requester_country = null, $requester_vat_number = null) {
		$vat_number_validated = Definitions::VAT_NUMBER_COULD_NOT_BE_VALIDATED;

		if(!empty($country) && !empty($vat_number)) {
			if($this->is_eu_country($country)) {
				$raw_validation_response = $this->validate_eu_vat_number($country, $vat_number, $requester_country, $requester_vat_number);

				// TODO The code below is almost identical to the one in method set_customer_vat_exemption(). Refactor and reuse the code

				if($raw_validation_response['valid'] == true) {
					$vat_number_validated = Definitions::VAT_NUMBER_VALIDATION_VALID;
				}
				else {
					// A "null" response means that nothing was returned by the server.
					// In such case, log an error
					if($raw_validation_response['valid'] === null) {
						$this->get_logger()->error(__('EU VAT Number could not be validated due to errors in the communication with the remote service.', self::$text_domain),
																			array(
																				'Raw response' => $raw_validation_response
																			));
						$vat_number_validated = Definitions::VAT_NUMBER_COULD_NOT_BE_VALIDATED;
					}
					else {
						$vat_number_validated = Definitions::VAT_NUMBER_VALIDATION_NOT_VALID;
					}
				}
			}
			else {
				$vat_number_validated = Definitions::VAT_NUMBER_VALIDATION_NON_EU;
			}
		}
		else {
			$this->get_logger()->notice(__('VAT number validation failed. Either the country or the VAT number is empty.', self::$text_domain), array(
				'Country' => $country,
				'VAT Number' => $vat_number,
			));
		}

		// If we have to return the raw response, we can just add the validation result to it
		// and return it
		// @since 1.10.1.191108
		if($return_raw_validation_response) {
			$raw_validation_response['euva_validation_result'] = $vat_number_validated;
			return $raw_validation_response;
		}

		return $vat_number_validated;
	}

	/**
	 * Displays a notice when the plugin has not yet been configured.
	 */
	public function settings_notice() {
	?>
		<div id="message" class="updated woocommerce-message">
			<p><?php echo __('<strong>EU VAT Assistant</strong> is almost ready! Please go to ' .
											 '<code>WooCommerce > EU VAT Assistant</code> settings page to complete the ' .
											 'configuration and start collecting the information required for ' .
											 'EU VAT compliance.', self::$text_domain); ?></p>
			<p class="submit">
				<a href="<?php echo admin_url('admin.php?page=' . Definitions::MENU_SLUG); ?>"
					 class="button-primary"><?php echo __('Go to EU VAT Assistant settings', self::$text_domain); ?></a>
			</p>
		</div>
	<?php
	}

	/**
	 * Adds elements to the billing address.
	 *
	 * @param array address_parts The various parts of the address (name, company,
	 * address, etc).
	 * @param WC_Order order The order from which the address was taken.
	 * @return array
	 * @see \WC_Order::get_formatted_billing_address()
	 */
	public function woocommerce_order_formatted_billing_address($address_parts, $order) {
		$order = new Order(aelia_get_order_id($order));
		$address_parts['vat_number'] = $order->get_customer_vat_number();
		return $address_parts;
	}

	/**
	 * Adds tags that will be replaced with additional information on the address,
	 * such as customers' VAT number.
	 *
	 * @param array replacements The replacement tokens passed by WooCommerce.
	 * @param array values The values from the billing address.
	 * @return array
	 * @see \WC_Countries::get_formatted_address()
	 */
	public function woocommerce_formatted_address_replacements($replacements, $values) {
		if(!empty($values['vat_number'])) {
			$replacements['{vat_number}'] = __('VAT #:', self::$text_domain) . ' ' . $values['vat_number'];
		}
		else {
			$replacements['{vat_number}'] = '';
		}
		return $replacements;
	}

	/**
	 * Alters the address formats and adds new tokens, such as the VAT number.
	 *
	 * @param array formats An array of address formats.
	 * @return array
	 * @see \WC_Countries::get_address_formats()
	 */
	public function woocommerce_localisation_address_formats($formats) {
		foreach($formats as $format_idx => $address_format) {
			$formats[$format_idx] .= "\n{vat_number}";
		}
		return $formats;
	}

	/**
	 * Adds the VAT number to the User Profile page in the Admin section.
	 *
	 * @param array fields The user profile fields.
	 * @return array
	 * @since 1.7.17.180106
	 */
	public function woocommerce_customer_meta_fields($fields) {
		$fields['billing']['fields']['vat_number'] = array(
			'label' => __('VAT Number', self::$text_domain),
			'description' => '',
		);
		return $fields;
	}

	/**
	 * Overrides the value of the "woocommerce_allowed_countries" setting when
	 * admin entered a list of countries to which sales are disallowed.
	 *
	 * @param mixed value The original value of the parameter.
	 * @return string
	 */
	public function pre_option_woocommerce_allowed_countries($value) {
		/* If we reach this point, it means that the Admin placed some restrictions
		 * on the list of countries to which he wishes to sell. In such case, the
		 * "allowed countries" option must be forced to "specific", so that the list
		 * of available countries can be filtered.
		 */
		return 'specific';
	}

	/**
	 * Filters the list of countries to which sale is allowed, taking into account
	 * the ones explicitly disallowed by the administrator.
	 *
	 * @param array countries A list of countries passed by WooCommerce.
	 * @return array The list of countries, with the disallowed ones removed from
	 * it.
	 */
	public function woocommerce_countries_allowed_countries($countries) {
		$allowed_sale_countries = $this->allowed_sale_countries;
		$disallowed_sale_countries = $this->get_sale_disallowed_countries();

		if(!empty($disallowed_sale_countries)) {
			foreach($disallowed_sale_countries as $disallowed_country) {
				if(isset($allowed_sale_countries[$disallowed_country])) {
					unset($allowed_sale_countries[$disallowed_country]);
				}
			}
		}
		// Debug
		//var_dump($allowed_sale_countries);
		return $allowed_sale_countries;
	}

	/**
	 * Ajax handler. Handles the request to collect and store the VAT data related
	 * to an order.
	 *
	 * @since 1.6.2.160210
	 */
	public function wp_ajax_collect_order_vat_info() {
		if(WC_Aelia_EU_VAT_Assistant::settings()->get(Settings::FIELD_COLLECT_VAT_DATA_FOR_MANUAL_ORDERS, false) &&
			 Orders_Integration::validate_ajax_request('edit_shop_orders')) {
			$order_id = $_REQUEST[Definitions::ARG_COLLECT_ORDER_ID];
			$this->collect_order_vat_info($order_id);

			ob_start();
			$this->render_order_vat_info_box($order_id);
			$vat_info_box_html = ob_get_contents();
			@ob_end_clean();

			wp_send_json(array(
				'result' => Definitions::RES_OK,
				'vat_info_box_html' => $vat_info_box_html,
			));
		}
	}

	/**
	 * Collects and saves the VAT data related to an order.
	 *
	 * @param int order_id An order ID.
	 * @since 1.6.2.160210
	 */
	protected function collect_order_vat_info($order_id) {
		$order = new Order($order_id);

		$this->vat_number = $order->get_meta('vat_number');
		$this->vat_country = $order->get_billing_country();
		$this->vat_number_validated = Definitions::VAT_NUMBER_ENTERED_MANUALLY_NOT_VALIDATED;

		$this->save_eu_vat_data($order_id);
	}

	/**
	 * Determines if an order should be exempt from EU VAT, based on the billing
	 * information and the presence of a valid EU VAT number.
	 *
	 * @param bool is_vat_exempt
	 * @param WC_Order order
	 * @return bool
	 * @since 1.8.0.180604
	 */
	public function woocommerce_order_is_vat_exempt($is_vat_exempt, $order = null) {
		if(!$is_vat_exempt && !empty($order) && ($order instanceof \WC_Order)) {
			$euva_order = new Order(aelia_get_order_id($order));
			$is_vat_exempt = $euva_order->is_order_eu_vat_exempt();
		}

		return $is_vat_exempt;
	}
	// Premium feature removed
}

$GLOBALS[WC_Aelia_EU_VAT_Assistant::$plugin_slug] = WC_Aelia_EU_VAT_Assistant::factory();
