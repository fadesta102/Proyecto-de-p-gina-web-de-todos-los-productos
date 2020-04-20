<?php
namespace Aelia\WC\EU_VAT_Assistant\WCPDF;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use Aelia\WC\EU_VAT_Assistant\WC_Aelia_EU_VAT_Assistant;
use Aelia\WC\EU_VAT_Assistant\Settings;
use Aelia\WC\EU_VAT_Assistant\Definitions;

/**
 * Alters the rendering of invoices produced by the PDF Invoices and Packing Slips
 * plugin, allowing to render multiple copies of the same invoice in different
 * currencies.
 *
 * @link https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/
 */
class EU_Invoice_Helper {
	// @var int The amount of decimals to use when printing prices
	protected $price_decimals;

	// @var string The currency of the order being processed
	public $order_currency;

	/**
	 * Returns the instance of the EU VAT Assistant plugin.
	 *
	 * @return \Aelia\WC\EU_VAT_Assistant\WC_Aelia_EU_VAT_Assistant
	 */
	protected function EUVA() {
		return WC_Aelia_EU_VAT_Assistant::instance();
	}

	/**
	 * Returns the name of a currency, if present amongst WooCommerce currencies.
	 *
	 * @param string currency A currency code.
	 * @return string|false The currency name, or false if the currency is not
	 * among WooCommerce currencies.
	 */
	protected function get_currency_name($currency) {
		if(empty($this->woocommerce_currencies)) {
			$this->woocommerce_currencies = get_woocommerce_currencies();
		}
		if(!empty($this->woocommerce_currencies[$currency])) {
			return $this->woocommerce_currencies[$currency];
		}
		return false;
	}

	/**
	 * Returns a list of target currencies for the invoice. A separate invoice
	 * will be generated for each of the target currencies.
	 *
	 * @return array
	 */
	public function target_currencies() {
		return apply_filters('wc_aelia_euva_invoice_target_currencies', array_unique(array($this->order_currency, $this->vat_currency)));
	}

	/**
	 * Constructor.
	 *
	 * @param EU_Invoice_Order order The order for which the invoice will be printed.
	 */
	public function __construct(EU_Invoice_Order $order) {
		$this->text_domain = WC_Aelia_EU_VAT_Assistant::$text_domain;
		$this->price_decimals = absint(get_option('woocommerce_price_num_decimals'));
		$this->order = $order;
		$this->order_currency = $this->order->get_currency();

		$this->vat_currency = WC_Aelia_EU_VAT_Assistant::settings()->get(Settings::FIELD_VAT_CURRENCY);
		$this->vat_currency_symbol = get_woocommerce_currency_symbol($this->vat_currency);

		$this->vat_currency_exchange_rate = $this->EUVA()->get_order_vat_exchange_rate($order->get_id());
		if(empty($this->vat_currency_exchange_rate)) {
			$this->vat_currency_exchange_rate = $this->EUVA()->convert(1, $this->order_currency, $this->vat_currency, 4);
		}

		$this->set_hooks();
	}

	/**
	 * Factory method.
	 *
	 * @return Aelia\WC\EU_VAT_Assistant\WCPDF\EU_Invoice_Helper
	 */
	public static function factory(EU_Invoice_Order $order) {
		return new self($order);
	}

	/**
	 * Sets the hooks required by the class.
	 */
	protected function set_hooks() {
		if($this->target_currencies() >= 2) {
			add_filter('raw_woocommerce_price', array($this, 'raw_woocommerce_price'), 10, 1);
			add_filter('wpo_wcpdf_billing_address', array($this, 'wpo_wcpdf_billing_address'), 10, 1);
		}
	}

	/**
	 * Removes the hooks created by the class, to prevent clashes.
	 */
	public function clear_hooks() {
		remove_filter('raw_woocommerce_price', array($this, 'raw_woocommerce_price'));
	}

	/**
	 * Returns a label for the currency used on the invoice.
	 */
	public function invoice_currency() {
		$currency = $this->order->get_currency();
		$result = $currency;

		$currency_name = $this->get_currency_name($currency);
		if(!empty($currency_name)) {
			$result .= " ({$currency_name})";
		}
		return $result;
	}

	/**
	 * Indicates if the reverse charge applies to the invoice. A reverse charge
	 * applies to all EU sales when customer entered a valid EU VAT number, as long
	 * as the customer resides in a country different from shop's base country.
	 *
	 * @return bool
	 */
	public function reverse_charge() {
		// Check if customer entered a valid VAT number. In such case, display a "Reverse charge" label.
		$eu_vat_evidence = $this->order->get_vat_evidence();
		if(!empty($eu_vat_evidence['location'])) {
			if($eu_vat_evidence['location']['is_eu_country'] &&
				 ($eu_vat_evidence['exemption']['vat_number_validated'] == Definitions::VAT_NUMBER_VALIDATION_VALID) &&
				 ($eu_vat_evidence['location']['billing_country'] != wc()->countries->get_base_country())) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Recalculates order amounts on the fly, applying the exchange rate used for
	 * VAT purposes. This method allows to print multiple copies of the same invoice
	 * in different currencies.
	 *
	 * @param float price The price to convert.
	 */
	public function raw_woocommerce_price($price) {
		$target_currency = $this->order->get_currency();

		switch($target_currency) {
			case $this->order_currency:
				break;
			case $this->vat_currency:
				$price = $price * $this->vat_currency_exchange_rate;
				break;
			default:
				$price = apply_filters('wc_aelia_eu_vat_assistant_convert', 1, $this->order_currency, $this->vat_currency, 4);
		}
		return $price;
	}

	/**
	 * Alters the billing address on the invoice by adding extra fields.
	 *
	 * @param string formatted_billing_address The original billing address, already
	 * formatted as HTML.
	 * @return string The processed billing address, with extra information.
	 */
	public function wpo_wcpdf_billing_address($formatted_billing_address) {
		$vat_number_label = __('VAT #:', $this->text_domain);
		$formatted_billing_address = str_replace($vat_number_label,
																						 '<span class="vat_number_label">' . $vat_number_label . '</span>',
																						 $formatted_billing_address);
		return $formatted_billing_address;
	}
}
