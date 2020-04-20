<?php
namespace Aelia\WC\EU_VAT_Assistant;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use \WC_Tax;

/**
 * Implements a class that will render the settings page.
 */
class Settings_Renderer extends \Aelia\WC\Settings_Renderer {
	// @var string The URL to the support portal.
	const SUPPORT_URL = 'https://aelia.freshdesk.com/support/home';
	// @var string The URL to the contact form for general enquiries.
	const CONTACT_URL = 'https://aelia.co/contact/';

	/*** Settings Tabs ***/
	const TAB_CHECKOUT = 'checkout';
	const TAB_SELF_CERTIFICATION = 'self-certification';
	const TAB_CURRENCY = 'currency';
	const TAB_SALES = 'sales';
	const TAB_REPORTS = 'reports';
	const TAB_OPTIONS = 'options';
	const TAB_LINKS = 'links';
	const TAB_SUPPORT = 'support';
	// @since 1.11.0.191108
	const TAB_VAT_NUMBER_VALIDATION = 'var-number-validation';

	/*** Settings sections ***/
	const SECTION_CHECKOUT = 'checkout';
	const SECTION_SELF_CERTIFICATION = 'self_certification';
	const SECTION_CURRENCY = 'currency';
	const SECTION_EXCHANGE_RATES_UPDATE = 'exchange_rates_update';
	const SECTION_EXCHANGE_RATES = 'exchange_rates';
	const SECTION_REPORTS = 'reports';
	const SECTION_SALE_RESTRICTIONS = 'sale_restrictions';
	const SECTION_VIES_VALIDATION = 'vies_validation';
	const SECTION_OPTIONS = 'options';
	const SECTION_LINKS = 'links';
	const SECTION_DEBUG = 'debug';
	const SECTION_SUPPORT = 'support_section';

	// @since 1.11.0.191108
	const SECTION_VIES_VALIDATION_OPTIONS = 'vies_validation_options';

	/**
	 * Transforms an array of currency codes into an associative array of
	 * currency code => currency description entries. Currency labels are retrieved
	 * from the list of currencies available in WooCommerce.
	 *
	 * @param array currencies An array of currency codes.
	 * @return array
	 */
	protected function add_currency_labels(array $currencies) {
		$woocommerce_currencies = get_woocommerce_currencies();

		// Add the VAT currency to the list, in case it's not already there
		$currencies[] = $this->_settings_controller->vat_currency();

		$result = array();
		foreach($currencies as $currency_code) {
			$result[$currency_code] = get_value($currency_code,
																					$woocommerce_currencies,
																					sprintf(__('Label not found for currency "%s"', $this->_textdomain),
																									$currency_code));
		}

		return $result;
	}

	/**
	 * Sets the tabs to be used to render the Settings page.
	 */
	protected function add_settings_tabs() {
		// Checkout settings
		$this->add_settings_tab($this->_settings_key,
														self::TAB_CHECKOUT,
														__('Checkout', $this->_textdomain));
		// VAT Number validation settings
		// @since 1.11.0.191108
		$this->add_settings_tab($this->_settings_key,
														self::TAB_VAT_NUMBER_VALIDATION,
														__('VAT Number Validation', $this->_textdomain));
		// Self-certification
		$this->add_settings_tab($this->_settings_key,
														self::TAB_SELF_CERTIFICATION,
														__('Self-certification', $this->_textdomain));
		// Currency
		$this->add_settings_tab($this->_settings_key,
														self::TAB_CURRENCY,
														__('Currency', $this->_textdomain));
		// Sales
		$this->add_settings_tab($this->_settings_key,
														self::TAB_SALES,
														__('Sales', $this->_textdomain));
		// Reports
		$this->add_settings_tab($this->_settings_key,
														self::TAB_REPORTS,
														__('Reports', $this->_textdomain));
		// Options
		$this->add_settings_tab($this->_settings_key,
														self::TAB_OPTIONS,
														__('Options', $this->_textdomain));
		// Shortcuts
		//$this->add_settings_tab($this->_settings_key,
		//												self::TAB_LINKS,
		//												__('Shortcuts', $this->_textdomain));
		// Support tab
		$this->add_settings_tab($this->_settings_key,
														self::TAB_SUPPORT,
														__('Support', $this->_textdomain));
	}

	/**
	 * Configures the plugin settings sections.
	 */
	protected function add_settings_sections() {
		// Add checkout settings section
		$this->add_settings_section(
				self::SECTION_CHECKOUT,
				__('Checkout settings', $this->_textdomain),
				array($this, 'checkout_section_callback'),
				$this->_settings_key,
				self::TAB_CHECKOUT
		);
		// Add self-certification settings section
		$this->add_settings_section(
				self::SECTION_SELF_CERTIFICATION,
				__('Self-certification settings', $this->_textdomain),
				array($this, 'self_certification_section_callback'),
				$this->_settings_key,
				self::TAB_SELF_CERTIFICATION
		);
		// Add currency section
		$this->add_settings_section(
				self::SECTION_CURRENCY,
				__('Currency', $this->_textdomain),
				array($this, 'currency_section_callback'),
				$this->_settings_key,
				self::TAB_CURRENCY
		);
		$this->add_settings_section(
				self::SECTION_EXCHANGE_RATES_UPDATE,
				__('Automatic update of exchange rates', $this->_textdomain),
				array($this, 'exchange_rates_update_section_callback'),
				$this->_settings_key,
				self::TAB_CURRENCY
		);
		$this->add_settings_section(
				self::SECTION_EXCHANGE_RATES,
				__('Exchange rates', $this->_textdomain),
				array($this, 'exchange_rates_section_callback'),
				$this->_settings_key,
				self::TAB_CURRENCY
		);
		// Add sales section
		$this->add_settings_section(
				self::SECTION_SALE_RESTRICTIONS,
				__('Sale restrictions', $this->_textdomain),
				array($this, 'sale_restrictions_section_callback'),
				$this->_settings_key,
				self::TAB_SALES
		);
		// Add reports section
		$this->add_settings_section(
				self::SECTION_REPORTS,
				__('Reports settings', $this->_textdomain),
				array($this, 'reports_section_callback'),
				$this->_settings_key,
				self::TAB_REPORTS
		);
		// Add VIES Validation section
		// @since 1.9.0.181022
		$this->add_settings_section(
				self::SECTION_VIES_VALIDATION,
				__('VIES Validation', $this->_textdomain),
				array($this, 'vies_validation_section_callback'),
				$this->_settings_key,
				self::TAB_VAT_NUMBER_VALIDATION
		);
		// Add VIES Validation Tweaks section
		// @since 1.11.0.191108
		$this->add_settings_section(
				self::SECTION_VIES_VALIDATION_OPTIONS,
				__('Validation options', $this->_textdomain),
				array($this, 'vies_validation_tweaks_section_callback'),
				$this->_settings_key,
				self::TAB_VAT_NUMBER_VALIDATION
		);
		// Add options section
		$this->add_settings_section(
				self::SECTION_OPTIONS,
				__('Options', $this->_textdomain),
				array($this, 'options_section_callback'),
				$this->_settings_key,
				self::TAB_OPTIONS
		);
		// Add Links section
		$this->add_settings_section(
				self::SECTION_LINKS,
				__('Shortcuts', $this->_textdomain),
				array($this, 'links_section_callback'),
				$this->_settings_key,
				self::TAB_REPORTS
		);
		// Add Support section
		$this->add_settings_section(
				self::SECTION_SUPPORT,
				__('Support Information', $this->_textdomain),
				array($this, 'support_section_callback'),
				$this->_settings_key,
				self::TAB_SUPPORT
		);
		// Add Debug section
		$this->add_settings_section(
				self::SECTION_DEBUG,
				__('Debug', $this->_textdomain),
				array($this, 'debug_section_callback'),
				$this->_settings_key,
				self::TAB_SUPPORT
		);
	}

	/**
	 * Configures the plugin settings fields.
	 */
	protected function add_settings_fields() {
		// Checkout settings
		$eu_vat_number_field_handling_options = array(
			Settings::OPTION_EU_VAT_NUMBER_FIELD_OPTIONAL => __('Optional', $this->_textdomain),
			Settings::OPTION_EU_VAT_NUMBER_FIELD_REQUIRED => __('Always required',
																													$this->_textdomain),
			Settings::OPTION_EU_VAT_NUMBER_FIELD_REQUIRED_EU_ONLY => __('Required only for EU addresses',
																													$this->_textdomain),
			Settings::OPTION_EU_VAT_NUMBER_FIELD_REQUIRED_IF_COMPANY_FILLED => __('Required if customer enters a company name',
																													$this->_textdomain),
			Settings::OPTION_EU_VAT_NUMBER_FIELD_REQUIRED_IF_COMPANY_FILLED_EU_ONLY => __('Required if customer enters a company name (EU addresses only)',
																													$this->_textdomain),
			Settings::OPTION_EU_VAT_NUMBER_FIELD_HIDDEN => __('Hidden',
																												$this->_textdomain),
		);
		$this->render_dropdown_field(self::SECTION_CHECKOUT,
																 Settings::FIELD_EU_VAT_NUMBER_FIELD_REQUIRED,
																 __('EU VAT number field will be', $this->_textdomain),
																 $eu_vat_number_field_handling_options,
																 __('Choose if you would like to display the EU VAT Number field, and ' .
																		'how you would like to handle it.'.
																		'<ul class="description">' .
																		'<li><strong>Optional</strong> - Customers can enter a EU VAT ' .
																		'number to get VAT exemption.</li>' .
																		'<li><strong>Always required</strong> - Customers must enter a ' .
																		'valid EU VAT number to complete a purchase. This means that ' .
																		'only B2B sales with EU businesses can be completed.</li>' .
																		'<li><strong>Required only for EU addresses</strong> - Customers ' .
																		'who select a billing country that is part of the EU must enter a ' .
																		'valid EU VAT number to complete a purchase. Customer who select ' .
																		'a non-EU country can proceed without entering the VAT number.</li>' .
																		'<li><strong>Hidden</strong> - Customers will not be able ' .
																		'to enter a EU VAT number. This option is useful if you do not ' .
																		'plan to sell to EU businesses.</li>' .
																		'</ul>',
																		$this->_textdomain),
																 '');
		// Label for EU VAT field
		$this->render_text_field(self::SECTION_CHECKOUT,
														 Settings::FIELD_EU_VAT_FIELD_TITLE,
														 __('EU VAT field label', $this->_textdomain),
														 __('The label that will be displayed above the EU VAT field at checkout.',
																$this->_textdomain),
														 'title');
		// Description for EU VAT field
		$this->render_text_field(self::SECTION_CHECKOUT,
														 Settings::FIELD_EU_VAT_FIELD_DESCRIPTION,
														 __('EU VAT field description', $this->_textdomain),
														 __('A description that will be displayed above the EU VAT field at checkout.',
																$this->_textdomain),
														 '');
		// VAT Number field position
		// @since 1.11.0.191108
		$this->render_text_field(self::SECTION_CHECKOUT,
														 Settings::FIELD_VAT_NUMBER_FIELD_POSITION,
														 __('EU VAT field position (index)', $this->_textdomain),
														 implode(' ', array(
															__('This numeric value will determine where the VAT number field will be displayed on the checkout page.', $this->_textdomain),
															__('The higher the number, the lower the field will appear on the page.', $this->_textdomain),
															__('For example, if you wish to show it just below the Company Name field, you will have to enter a value higher ' .
																 'than the index for that field (try with "35").', $this->_textdomain),
														 )),
														 'field_index',
														 array(),
														 'number');
		$this->render_checkbox_field(self::SECTION_CHECKOUT,
																 Settings::FIELD_SHOW_EU_VAT_FIELD_IF_CUSTOMER_IN_BASE_COUNTRY,
																 __('Show EU VAT field when customer is located in base country', $this->_textdomain),
																 __('Show the EU VAT field when customer address is located in any European ' .
																		'country, including your shop\'s base country. If this option is <strong>not</strong> selected, ' .
																		'the EU VAT field will be hidden when the customer is from the same country specified ' .
																		'as your shop\'s base country.',
																		$this->_textdomain),
																 '');
		$this->render_checkbox_field(self::SECTION_CHECKOUT,
																 Settings::FIELD_REMOVE_VAT_IF_CUSTOMER_IN_BASE_COUNTRY,
																 __('Deduct VAT if customer is located in base country', $this->_textdomain),
																 __('Enable this option to deduct VAT from orders placed by customers who are located ' .
																		'in your shop\'s base country, if they enter a valid EU VAT number.',
																		$this->_textdomain),
																 '');
		$this->render_checkbox_field(self::SECTION_CHECKOUT,
																 Settings::FIELD_STORE_INVALID_VAT_NUMBERS,
																 __('Store invalid VAT numbers', $this->_textdomain),
																 __('Enable this option to store VAT numbers that don\'t pass ' .
																		'validation. Note: when an invalid VAT number is detected, ' .
																		'VAT will still be applied.',
																		$this->_textdomain),
																 '');

		// Self-certification
		$self_certification_show_options = array(
			Settings::OPTION_SELF_CERTIFICATION_FIELD_NO => __('No (never show it)', $this->_textdomain),
			Settings::OPTION_SELF_CERTIFICATION_FIELD_YES => __('Yes (always show it)', $this->_textdomain),
			Settings::OPTION_SELF_CERTIFICATION_FIELD_CONFLICT_ONLY => __('Only when there is insufficient ' .
																																		'evidence about customer\'s location', $this->_textdomain),
		);
		$this->render_dropdown_field(self::SECTION_SELF_CERTIFICATION,
																 Settings::FIELD_SHOW_SELF_CERTIFICATION_FIELD,
																 __('Allow customers to self-certify their location of residence', $this->_textdomain),
																 $self_certification_show_options,
																 __('Choose if you would like to display a "self-certification" field at ' .
																		'checkout. By ticking the self-certification box, customers will be ' .
																		'allowed to certify that the country entered as part of the billing ' .
																		'address is their country of residence. Such declaration will be ' .
																		'recorded with the completed order as part of the EU VAT evidence.',
																		$this->_textdomain),
																 '');
		// Self-certification field position
		// @since 1.11.0.191108
		$this->render_text_field(self::SECTION_SELF_CERTIFICATION,
														 Settings::FIELD_SELF_CERTIFICATION_FIELD_POSITION,
														 __('Self-certification field position (index)', $this->_textdomain),
														 implode(' ', array(
															__('This numeric value will determine where the VAT number field will be displayed on the checkout page.', $this->_textdomain),
															__('The higher the number, the lower the field will appear on the page.', $this->_textdomain),
														 )),
														 'field_index',
														 array(),
														 'number');
		$this->render_checkbox_field(self::SECTION_SELF_CERTIFICATION,
																 Settings::FIELD_SELF_CERTIFICATION_FIELD_REQUIRED_WHEN_CONFLICT,
																 __('Require self-certification when the evidence about location is insufficient', $this->_textdomain),
																 __('<a href="http://en.wikipedia.org/wiki/European_Union_value_added_tax#EU_VAT_area">' .
																		'EU regulations require at least two pieces of non-conflicting ' .
																		'evidence</a> to prove a customer\'s location (e.g. billing address, shipping ' .
																		'address, IP address). If you enable this option, the self-certification ' .
																		'will become mandatory unless at least two of these information will ' .
																		'match the same country. <strong>Important</strong>: this rule ' .
																		'applies only when the self-certification field is visible to the ' .
																		'customer (see visibility options, above).',
																		$this->_textdomain),
																 '');
		$this->render_checkbox_field(self::SECTION_SELF_CERTIFICATION,
																 Settings::FIELD_USE_SHIPPING_ADDRESS_AS_EVIDENCE,
																 __('Consider the shipping address as valid location evidence', $this->_textdomain),
																 __('Tick this box if you would like to use the shipping address ' .
																		'as evidence to validate customer\'s location. When this ' .
																		'option is enabled, and the customer enters the same country ' .
																		'in both billing and shipping address, the plugin will consider ' .
																		'them two pieces of non contradictory evidence and it will no ' .
																		'longer ask for self-certification. ' .
																		'We would recommend that you discuss with your Revenue office ' .
																		'the possibility of using the shipping address as evidence ' .
																		'before enabling this option.',
																		$this->_textdomain),
																 '');
		$this->render_checkbox_field(self::SECTION_SELF_CERTIFICATION,
																 Settings::FIELD_HIDE_SELF_CERTIFICATION_FIELD_VALID_VAT_NUMBER,
																 __('Hide the self-certification field when a valid VAT number is entered', $this->_textdomain),
																 __('Enable this option if you would like to hide the self-certification ' .
																		'field at checkout even when the customer enters a valid VAT number. ' .
																		'<strong>Important</strong>: when this option is enabled, if the ' .
																		'customer will enter a valid VAT number the self-certification ' .
																		'requirement above will be ignored.',
																		$this->_textdomain),
																 '');
		$this->render_text_field(self::SECTION_SELF_CERTIFICATION,
																 Settings::FIELD_SELF_CERTIFICATION_FIELD_TITLE,
																 __('Self-certification field label', $this->_textdomain),
																 __('The label that will be displayed above the self-certification ' .
																		'field at checkout. You can use the <code>{billing_country}</code> ' .
																		'placeholder to automatically show the billing country chosen ' .
																		'by the customer.',
																		$this->_textdomain),
																 'title');
		// Currency
		$this->render_dropdown_field(self::SECTION_CURRENCY,
																 Settings::FIELD_VAT_CURRENCY,
																 __('VAT Currency', $this->_textdomain),
																 get_woocommerce_currencies(),
																 __('EU regulations require that all payment and VAT amounts ' .
																		'are indicated in the currency where your business is based. ' .
																		'If you file your VAT reports in a currency different from ' .
																		'shop\'s base currency, you can choose it here. ' .
																		'<strong>Important:</strong> all VAT data will be calculated and ' .
																		'stored with the orders as they are created. We strongly recommend ' .
																		'to double check the currency you have selected, as changing it later ' .
																		'could result in incorrect VAT reports being generated.',
																		$this->_textdomain),
																 'title');

		// Exchange rates settings
		$this->render_checkbox_field(self::SECTION_EXCHANGE_RATES_UPDATE,
																 Settings::FIELD_EXCHANGE_RATES_UPDATE_ENABLE,
																 __('Tick this box to enable automatic updating of exchange rates.', $this->_textdomain),
																 '', // No description required
																 '');

		// Add "Exchange Rates Schedule" field
		// Render the dropdown to allow choosing how often to update the Exchange Rates
		$schedule_info = $this->_settings_controller->get_exchange_rates_schedule_info();
		$this->render_dropdown_field(self::SECTION_EXCHANGE_RATES_UPDATE,
																 Settings::FIELD_EXCHANGE_RATES_UPDATE_SCHEDULE,
																 __('Select how often you would like to update the exchange rates.', $this->_textdomain),
																 $this->_settings_controller->get_schedule_options(),
																 sprintf(__('<p>Last update: <span id="last_exchange_rates_update">%s</span>.</p>' .
																						'<p>Next update: <span id="next_exchange_rates_update">%s</span>.</p>',
																						$this->_textdomain),
																				 $schedule_info['last_update'],
																				 $schedule_info['next_update']),
																 'exchange_rates_schedule');
		// Render the dropdown to choose the exchange rates provider
		$this->render_dropdown_field(self::SECTION_EXCHANGE_RATES_UPDATE,
																 Settings::FIELD_EXCHANGE_RATES_PROVIDER,
																 __('Exchange rates provider', $this->_textdomain),
																 $this->_settings_controller->exchange_rates_providers_options(),
																 __('Select the Provider from which the exchange rates will be fetched.',
																		$this->_textdomain),
																 'exchange_rates_provider');
		// Render the exchange rates table
		$this->render_exchange_rates_fields();

		// Sales
		$this->render_dropdown_field(self::SECTION_SALE_RESTRICTIONS,
																 Settings::FIELD_SALE_DISALLOWED_COUNTRIES,
																 __('Prevent sales to these countries', $this->_textdomain),
																 wc()->countries->countries,
																 __('Here you can add a list of countries to which you do not want ' .
																		'to sell. The countries in this list will not appear to the customer ' .
																		'at any stage, thus preventing him from completing an order. Leave ' .
																		'this field empty to allow sales to all countries configured in ' .
																		'<strong>WooCommerce > Settings</strong> section.',
																		$this->_textdomain),
																 'multi_country_selector',
																 array('multiple' => 'multiple'));

		// Get a list of the available tax classes, to allow excluding one or more of
		// them from the VAT MOSS reports. Using WC_Tax::get_tax_classes() should prevent
		// compatibility issues in the future.
		//
		// @since 1.12.4.200131
		// @link https://aelia.freshdesk.com/a/tickets/84141
		$woocommerce_tax_classes = array_filter(array_map('trim', WC_Tax::get_tax_classes()));
		$available_tax_classes = array(
			// The "Standard" tax class actually has a blank key
			'' => 'Standard',
		);
		foreach($woocommerce_tax_classes as $tax_class_name) {
			$available_tax_classes[sanitize_title($tax_class_name)] = $tax_class_name;
		}
		$this->render_dropdown_field(self::SECTION_REPORTS,
																 Settings::FIELD_TAX_CLASSES_EXCLUDED_FROM_MOSS,
																 __('The following tax classes should not be included in MOSS reports',
																		$this->_textdomain),
																 $available_tax_classes,
																 __('Here you can select one or more tax classes whose rates should ' .
																		'be excluded from MOSS reports. The tax information for ' .
																		'those rates will still be tracked, this setting will just ' .
																		'be used to filter the data using the report filters ' .
																		'in the <strong>Reports</strong> interface.',
																		$this->_textdomain),
																 'moss_excluded_tax_classes',
																 array('multiple' => 'multiple'));
		// Options
		$this->render_text_field(self::SECTION_OPTIONS,
														 Settings::FIELD_VAT_ROUNDING_DECIMALS,
														 __('Decimals for VAT rounding', $this->_textdomain),
														 __('The amount of decimals to use when rounding VAT. This setting ' .
																'applies when the VAT evidence is generated (for example during ' .
																'the conversion of VAT totals to the appropriate VAT currency). ' .
																'If you are not sure of how many decimals you should use, please ' .
																'contact your Revenue office.',
																$this->_textdomain),
														 'numeric');
		$this->render_checkbox_field(self::SECTION_OPTIONS,
																 Settings::FIELD_COLLECT_VAT_DATA_FOR_MANUAL_ORDERS,
																 __('Collect VAT data for orders entered or modified manually', $this->_textdomain),
																 __('When this option is selected, the EU VAT Assistant will ' .
																		'collect VAT data for orders that are added or modified manually by ' .
																		'going to <em>WooCommerce > Orders > Add new order</em>. ' .
																		'The data will be collected automatically, when the ' .
																		'"Calculate Totals" or "Recalculate" button is clicked.',
																		$this->_textdomain) .
																 '<br/><br/> ' .
																 '<strong>' . __('Important', $this->_textdomain) . '</strong>: ' .
																 __('The VAT data collected from orders created or modified manually will be the same ' .
																		'collected from orders placed by customers ' .
																		'and it will appear on the tax reports.', $this->_textdomain) .
																 ' ' .
																 __('Since the VAT MOSS scheme explicitly covers sales that do ' .
																		'not require manual intervention, orders entered manually ' .
																		'should fall outside the scope of such scheme.', $this->_textdomain) .
																 ' ' .
																 __('For this reason, this option is disabled by default. ' .
																		'We recommend that you consult your accountant before enabling ' .
																		'this option.', $this->_textdomain),
																 '');

		// Requester VAT Country for the VIES Validation service
		// @since 1.9.0.181022
		$this->render_dropdown_field(self::SECTION_VIES_VALIDATION,
																 Settings::FIELD_VIES_REQUESTER_VAT_COUNTRY,
																 __('Requester country code for the VAT validation service',
																		$this->_textdomain),
																 WC()->countries->get_countries(),
																 '',
																 'shop_vat_country',
																 array());

		// Requester VAT Number for the VIES Validation service
		// @since 1.9.0.181022
		$this->render_text_field(self::SECTION_VIES_VALIDATION,
														Settings::FIELD_VIES_REQUESTER_VAT_NUMBER,
														__('Requester VAT number for the VAT validation service', $this->_textdomain),
														__('If you enter a valid VAT number, it will be passed to the ' .
															'VIES service with each VAT number validation request.',
															$this->_textdomain) .
														' ' .
														__('With this information, the VIES service will return a ' .
															'a "consultation number", which can be used as proof of ' .
															'the validity of a VAT number, should your revenue office ' .
															'ask for one.',
															$this->_textdomain) .
														'<br />' .
														'<strong>' .
														__('Important', $this->_textdomain) . ': ' .
														'</strong>' .
														__('Please make sure that you enter you VAT number without the country ' .
															'code prefix. The country code will be added automatically, by the EU VAT ' .
															'Assistant, using the country you selected above.',
															$this->_textdomain) .
														'');

		// VAT Number Validation section
		// @since 1.11.0.191108
		$this->render_checkbox_field(self::SECTION_VIES_VALIDATION,
																 Settings::FIELD_RETRY_VALIDATION_WHEN_REQUESTER_VAT_NUMBER_INVALID,
																 __('Retry the validation of a VAT number when it fails due to the requested VAT number being invalid', $this->_textdomain),
																 implode(' ', array(
																	__('The remote VIES service may return an "invalid requester" response when it cannot validate the requester VAT number sent with a validation request.', $this->_textdomain),
																	__('When you enable this option, the EU VAT Assistant will try to validate the VAT number from the customer again, without sending the requester details.', $this->_textdomain),
																	__('In this second case, the VIES service will not return a consultation number upon a successful validation.', $this->_textdomain),
																	sprintf(__('For more information about the purpose of the consultation number, <a href="%1$s">please refer to the documentation of the VIES service</a>.', $this->_textdomain),
																					'http://ec.europa.eu/taxation_customs/vies/help.html'),
																 )),
																 '');
		$this->render_checkbox_field(self::SECTION_VIES_VALIDATION_OPTIONS,
																 Settings::FIELD_ACCEPT_VAT_NUMBER_WHEN_VALIDATION_SERVER_BUSY,
																 __('Accept VAT numbers as valid when the remote service is busy', $this->_textdomain),
																 implode(' ', array(
																	__('The remote VIES service may return a "busy" response when it cannot process a validation request.', $this->_textdomain),
																	__('In such case, the VAT number validation request was not processed and its validity is unknown.', $this->_textdomain),
																	__('If you enable this option, the VAT number will be considered valid anyway, and a VAT exemption will be applied at checkout.', $this->_textdomain),
																 )),
																 '');
		$this->render_checkbox_field(self::SECTION_VIES_VALIDATION_OPTIONS,
																 Settings::FIELD_ACCEPT_VAT_NUMBER_WHEN_SERVICE_UNAVAILABLE,
																 __('Accept VAT numbers as valid when the remote service is unavailable', $this->_textdomain),
																 implode(' ', array(
																		__('The remote VIES service may return an "unavailable" response when the main service, or the service from a specific member state, is unavailable.', $this->_textdomain),
																		__('In such case, the VAT number validation request was not processed and its validity is unknown.', $this->_textdomain),
																		__('If you enable this option, the VAT number will be considered valid anyway, and a VAT exemption will be applied at checkout.', $this->_textdomain),
																 )),
																 '');
		$this->render_checkbox_field(self::SECTION_VIES_VALIDATION_OPTIONS,
																 Settings::FIELD_ACCEPT_VAT_NUMBER_WHEN_SERVICE_REJECTS_TOO_MANY_REQUESTS,
																 __('Accept VAT numbers as valid when the remote service is handling too many requests', $this->_textdomain),
																 implode(' ', array(
																		__('The remote VIES service may return a "too many requests" response, when the main service, or a member state, is overloaded.', $this->_textdomain),
																		__('In such case, the VAT number validation request was not processed and its validity is unknown.', $this->_textdomain),
																		__('If you enable this option, the VAT number will be considered valid anyway, and a VAT exemption will be applied at checkout.', $this->_textdomain),
																		'<br />',
																		'<strong>' . __('Important', $this->_textdomain) . '</strong>:',
																		__('A "too many requests" error does NOT indicate that your site is making too many calls.', $this->_textdomain),
																		__('That error refers to the global amount of requests received by the VIES service, or member state, not just from your site.', $this->_textdomain),
																 )),
																 '');


		// Get the file name from the logger class
		$log_file_path = \Aelia\WC\Logger::get_log_file_name(Definitions::PLUGIN_SLUG);

		$this->render_checkbox_field(self::SECTION_DEBUG,
																 Settings::FIELD_DEBUG_MODE,
																 __('Enable debug mode.', $this->_textdomain),
																 sprintf(__('When debug mode is enabled, the plugin will log ' .
																						'events to file <code>%s</code>.', $this->_textdomain),
																				 $log_file_path),
																 '');
	}

	/**
	 * Returns the title for the menu item that will bring to the plugin's
	 * settings page.
	 *
	 * @return string
	 */
	protected function menu_title() {
		return __('EU VAT Assistant', $this->_textdomain);
	}

	/**
	 * Returns the slug for the menu item that will bring to the plugin's
	 * settings page.
	 *
	 * @return string
	 */
	protected function menu_slug() {
		return Definitions::MENU_SLUG;
	}

	/**
	 * Returns the title for the settings page.
	 *
	 * @return string
	 */
	protected function page_title() {
		return __('EU VAT Assistant - Settings', $this->_textdomain) .
					 sprintf('&nbsp;(v. %s)', WC_Aelia_EU_VAT_Assistant::$version);
	}

	/**
	 * Returns the description for the settings page.
	 *
	 * @return string
	 */
	protected function page_description() {
		// TODO Restore link to documentation
		return __('In this page you can configure the settings for the EU VAT Assistant plugin.',
							$this->_textdomain);
	}

	/*** Settings sections callbacks ***/
	public function checkout_section_callback() {
		echo __('Here you can configure various parameters related to checkout.', $this->_textdomain);

		echo '<noscript>';
		echo __('This page requires JavaScript to work properly. Please enable JavaScript ' .
						'in your browser and refresh the page.</u>.',
						$this->_textdomain);
		echo '</noscript>';
	}

	public function self_certification_section_callback() {
		echo __('Here you can decide to display an additional self-certification field that ' .
						'customers can use to confirm their country of residence.', $this->_textdomain);
	}

	public function currency_section_callback() {
		// Dummy
	}

	public function exchange_rates_update_section_callback() {
		// Dummy
	}

	public function exchange_rates_section_callback() {
		echo __('These exchange rates will be used to convert ' .
						'the order amounts (totals, VAT, etc) in the currency you use to file your VAT returns. ' .
						'<strong>Important</strong>: you can enter the exchange rates manually, if you wish, but ' .
						'please make sure that they are in line with the values that your Revenue office would ' .
						'consider acceptable. The responsibility of ensuring that the rates are correct lies ' .
						'upon you. If you are in doubt, please contact your revenue office to determine which ' .
						'exchange rates they would consider acceptable. Most revenue offices have their own ' .
						'list of exchange rates, which you can enter here.', $this->_textdomain);
		echo '<br /><br />';
		echo __('To set an exchange rate manually, tick the box next to the exchange rate field ' .
						'for the desired currency and enter the rate in the exchange rate field itself. ' .
						'The checkbox next to the "<strong>Set manually</strong>" label will select/deselect the checkboxes ' .
						'for all the currencies. <strong>Important</strong>: ensure that all exchange rates ' .
						'you flagged to be entered manually are filled. An empty exchange rate could lead to ' .
						'unpredictable results, as it\'s intepreted as zero.', $this->_textdomain);
	}

	public function sale_restrictions_section_callback() {
		// Dummy
	}

	public function reports_section_callback() {
		// Dummy
	}

	/**
	 * VIES validation settings.
	 *
	 * @since 1.9.0.181022
	 */
	public function vies_validation_section_callback() {
		echo __('Here you can specify the parameters that will be used to communicate with ' .
						'the VIES service, used to validate EU VAT numbers.', $this->_textdomain);
	}

	/**
	 * VIES validation tweaks settings.
	 *
	 * @since 1.11.0.191108
	 */
	public function vies_validation_tweaks_section_callback() {
		echo '<strong>' . __('Important', $this->_textdomain) . '</strong>';
		echo implode(' ', array(
			__('We strongly recommend to consult your tax advisor before enabling any of the options ' .
				 'that allow to accept VAT numbers that could not be validated due to a VIES service error.', $this->_textdomain),
			__('While accepting potentially invalid VAT number can help customers to complete orders when the validation service ' .
				 'is not working, it also exposes you to the possibility of granting a VAT exmemption to customers who are not entitled ' .
				 'to it.', $this->_textdomain),
			__('The responsibility of applying a VAT exemption only when needed, providing evidence in relation to such exemption '.
				 'to the Revenue Office, and rectify exemptions granted when not due, remains exclusively yours.', $this->_textdomain),
		));
	}

	public function options_section_callback() {
		echo __('Miscellaneous options.', $this->_textdomain);
	}

	public function links_section_callback() {
		?>
		<div class="links">
			<p><?php
				echo __('This section contains some convenient links to the ' .
								'sections of WooCommerce relevant to EU VAT compliance', $this->_textdomain);
			?></p>
			<div class="settings">
				<h4 class="title"><?php
					echo __('Settings', $this->_textdomain);
				?></h4>
				<ul>
					<li class="tax">
						<a href="<?php echo admin_url('admin.php?page=wc-settings&tab=tax'); ?>"><?php
							echo __('Tax Settings', $this->_textdomain);
						?></a>
					</li>
				</ul>
			</div>
			<div class="reports">
				<h4 class="title"><?php
					echo __('Reports', $this->_textdomain);
				?></h4>
				<ul>
					<li class="eu_vat_report">
						<a href="<?php echo admin_url('admin.php?page=wc-reports&tab=taxes&report=eu_vat_by_country_report'); ?>"><?php
							echo __('EU VAT By Country', $this->_textdomain);
						?></a>
					</li>
					<li class="vies_report">
						<a href="<?php echo admin_url('admin.php?page=wc-reports&tab=taxes&report=vies_report'); ?>"><?php
							echo __('VIES', $this->_textdomain);
						?></a>
					</li>
					<li class="intrastat_report">
						<a href="<?php echo admin_url('admin.php?page=wc-reports&tab=taxes&report=intrastat_report'); ?>"><?php
							echo __('INTRASTAT', $this->_textdomain);
						?></a>
					</li>
					<li class="sales_summary_report">
						<a href="<?php echo admin_url('admin.php?page=wc-reports&tab=taxes&report=sales_summary_report'); ?>"><?php
							echo __('Sales Summary (VAT RTD)', $this->_textdomain);
						?></a>
					</li>
				</ul>
			</div>
		</div>
		<?php
	}

	public function support_section_callback() {
		echo '<div class="support_information">';
		echo '<p>';
		echo __('We designed this plugin to be robust and effective, ' .
						'as well as intuitive and easy to use. However, we are aware that, despite ' .
						'all best efforts, issues can arise and that there is always room for ' .
						'improvement.',
						$this->_textdomain);
		echo '</p>';
		echo '<p>';
		echo __('Should you need assistance, or if you just would like to get in touch ' .
						'with us, please use one of the links below.',
						$this->_textdomain);
		echo '</p>';

		// Support links
		echo '<ul id="contact_links">';
		echo '<li>' . sprintf(__('<span class="label">To request support</span>, please use our <a href="%s">Support portal</a>. ' .
														 'The portal also contains a Knowledge Base, where you can find the ' .
														 'answers to the most common questions related to our products.',
														 $this->_textdomain),
													self::SUPPORT_URL) . '</li>';
		echo '<li>' . sprintf(__('<span class="label">To send us general feedback</span>, suggestions, or enquiries, please use ' .
														 'the <a href="%s">contact form on our website.</a>',
														 $this->_textdomain),
													self::CONTACT_URL) . '</li>';
		echo '</ul>';

		echo '</div>';
	}

	public function debug_section_callback() {
	}

	/*** Rendering methods ***/
	/**
	 * Renders the buttons at the bottom of the settings page.
	 */
	protected function render_buttons() {
		parent::render_buttons();
		submit_button(__('Save and update exchange rates', $this->_textdomain),
									'secondary',
									$this->_settings_key . '[update_exchange_rates_button]',
									false);
	}

	protected function render_exchange_rates_fields() {
		// Prepare fields to display the Exchange Rate options for each selected currency
		$exchange_rates_field_id = Settings::FIELD_EXCHANGE_RATES;
		$exchange_rates = $this->current_settings($exchange_rates_field_id, $this->default_settings($exchange_rates_field_id, array()));
		// Add "Exchange Rates" table
		add_settings_field(
			$exchange_rates_field_id,
			'', // No label needed
			array($this, 'render_exchange_rates_options'),
			$this->_settings_key,
			self::SECTION_EXCHANGE_RATES,
			array(
				'settings_key' => $this->_settings_key,
				'exchange_rates' => $exchange_rates,
				'id' => $exchange_rates_field_id,
				'label_for' => $exchange_rates_field_id,
				// Input field attributes
				'attributes' => array(
					'class' => $exchange_rates_field_id,
				),
			)
		);
	}

	/**
	 * Renders a table containing several fields that admins can use to configure
	 * the Exchange Rates for the various currencies.
	 *
	 * @param array args An array of arguments passed by add_settings_field().
	 * @see add_settings_field().
	 */
	public function render_exchange_rates_options($args) {
		/* Generate the base field ID and field name that will be used to dynamically
		 * create the hierarchy of fields for the exchange rates. Every field will
		 * have a name like "base_field_id[currency]", so that PHP will automatically
		 * build a hierarchy out of them when the settings will be saved.
		 *
		 * Note: $base_field_id and $base_field_name are output parameters, they will
		 * be populated by the method.
		 */
		$this->get_field_ids($args, $base_field_id, $base_field_name);

		//var_dump($args);die();
		// Retrieve the enabled currencies
		$currencies = $this->add_currency_labels($this->_settings_controller->enabled_currencies());
		// Retrieve the exchange rates
		$exchange_rates = get_value(Settings::FIELD_EXCHANGE_RATES, $args, array());
		if(!is_array($exchange_rates)) {
			//var_dump($exchange_rates);return;
			throw new InvalidArgumentException(__('Argument "exchange_rates" must be an array.', $this->_textdomain));
		}

		// Retrieve the Currency used internally by WooCommerce
		$vat_currency = $this->_settings_controller->vat_currency();

		$html = '<table id="exchange_rates_settings">';
		// Table header
		$html .= '<thead>';
		$html .= '<tr>';
		$html .= '<th class="currency_name">' . __('Currency', $this->_textdomain) . '</th>';
		$html .= '<th class="exchange_rate">' . __('Exchange Rate', $this->_textdomain) . '</th>';
		$html .= '<th class="set_manually">' .
						 __('Set Manually', $this->_textdomain) .
						 '<span class="help-icon" title="' .
						 __('Tick the box next to a currency if you would like to enter its ' .
								'exchange rate manually. By doing that, the rate you enter for ' .
								'that currency will not change, even if you have enabled the automatic ' .
								'update of exchange rates, below',
								$this->_textdomain) .
						 '"></span>' .
						 '<div class="selectors">' .
						 '<span class="select_all">' . __('Select', $this->_textdomain) . '</span>' .
						 '/' .
						 '<span class="deselect_all">' . __('Deselect', $this->_textdomain) . '</span>' .
						 __('all', $this->_textdomain) .
						 '</div>' .
						 '</th>';
		$html .= '</th>';

		$html .= '</tr>';
		$html .= '</thead>';
		$html .= '<tbody>';

		// Render one line to display settings for base currency
		$html .= $this->render_settings_for_vat_currency($vat_currency,
																										 $currencies[$vat_currency],
																										 $exchange_rates,
																										 $base_field_id,
																										 $base_field_name);

		foreach($currencies as $currency => $currency_name) {
			// No need to render an Exchange Rate for main currency, as it would be 1:1
			if($currency == $vat_currency) {
				continue;
			}

			$currency_field_id = $this->group_field($currency, $base_field_id);
			$currency_field_name = $this->group_field($currency, $base_field_name);
			$html .= '<tr>';
			// Output currency label
			$html .= '<td class="currency_name">';
			$html .= "<span>$currency_name ($currency)</span>";
			$html .= '</td>';

			$currency_settings = get_value($currency, $exchange_rates, array());
			$currency_settings = array_merge($this->_settings_controller->default_currency_settings(), $currency_settings);
			//var_dump($currency_settings);

			// Render exchange rate field
			$html .= '<td class="exchange_rate">';
			$field_args = array(
				'id' => $currency_field_id . '[rate]',
				'value' => get_value('rate', $currency_settings, ''),
				'attributes' => array(
					'class' => 'numeric',
				),
			);
			ob_start();
			$this->render_textbox($field_args);
      $field_html = ob_get_contents();
      ob_end_clean();
			$html .= $field_html;
			$html .= '</td>';

			//var_dump($currency_settings);
			// Render "Set Manually" checkbox
			$html .= '<td class="set_manually">';
			$field_args = array(
				'id' => $currency_field_id . '[set_manually]',
				'value' => 1,
				'attributes' => array(
					'class' => 'exchange_rate_set_manually',
					'checked' => get_value('set_manually', $currency_settings),
				),
			);
			ob_start();
			$this->render_checkbox($field_args);
			$field_html = ob_get_contents();
			ob_end_clean();
			$html .= $field_html;
			$html .= '</td>';

			// Render exchange rate markup field
			//$html .= '<td>';
			//$field_args = array(
			//	'id' => $currency_field_id . '[rate_markup]',
			//	'value' => get_value('rate_markup', $currency_settings, ''),
			//	'attributes' => array(
			//		'class' => 'numeric',
			//	),
			//);
			//ob_start();
			//$this->render_textbox($field_args);
			//$field_html = ob_get_contents();
			//ob_end_clean();
			//$html .= $field_html;
			//$html .= '</td>';
			$html .= '</tr>';
		}

		$html .= '</tbody>';
		$html .= '</table>';

		echo $html;
	}

	/**
	 * Renders a "special" row on the exchange rates table, which contains the
	 * settings for the base currency.
	 *
	 * @param string currency The currency to display on the row.
	 * @param string exchange_rates An array of currency settings.
	 * @param string base_field_id The base ID that will be assigned to the
	 * fields in the row.
	 * @param string base_field_id The base name that will be assigned to the
	 * fields in the row.
	 * @return string The HTML for the row.
	 */
	protected function render_settings_for_vat_currency($currency, $currency_name, $exchange_rates, $base_field_id, $base_field_name) {
		$currency_field_id = $this->group_field($currency, $base_field_id);
		$currency_field_name = $this->group_field($currency, $base_field_name);

		$html = '<tr>';
		// Output currency label
		$html .= '<td class="currency_name">';
		$html .= "<span>$currency_name ($currency)</span>";
		$html .= '</td>';

		$currency_settings = get_value($currency, $exchange_rates, array());
		$currency_settings = array_merge($this->_settings_controller->default_currency_settings(), $currency_settings);
		//var_dump($currency_settings);

		// Render exchange rate field
		$html .= '<td class="exchange_rate numeric">';
		$html .= '1'; // Exchange rate for base currency is always 1
		$html .= '</td>';

		// Render "Set Manually" checkbox
		$html .= '<td>';
		$html .= '</td>';

		// Render exchange rate markup field
		//$html .= '<td>';
		//$html .= '</td>';
		$html .= '</tr>';

		return $html;
	}
}
