<?php
namespace Aelia\WC\EU_VAT_Assistant\WCPDF;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use Aelia\WC\EU_VAT_Assistant\WC_Aelia_EU_VAT_Assistant;

/**
 * Extends Aelia\WC\EU_VAT_Assistant\Order class by adding some tweakings that
 * simplify the rendering of an invoice in multiple currencies using the PDF
 * Invoices and Packing Slips plugin.
 */
class EU_Invoice_Order extends \Aelia\WC\EU_VAT_Assistant\Order {
	protected $currency;

	/**
	 * Constructor.
	 *
	 * @param int order_id The order ID.
	 */
	public function __construct($order_id) {
		parent::__construct($order_id);
		$this->original_order_currency = parent::get_currency();
	}

	/**
	 * Allows to override the order currency. This can be used as a trick when
	 * printing an invoice in a different currency.
	 *
	 * @param string currency Sets the currency against the order.
	 */
	public function override_currency($currency) {
		$this->currency = $currency;
	}

	/**
	 * Returns the order currency. This method has been tweaked to allow passing
	 * a currency different from the one in which the order was actually placed.
	 * This is necessary to print invoices in multple currencies, because order
	 * amounts will be converted to the appropriate values by the
	 * EU_Invoice_Helper, but the formatting itself will occur within the
	 * order class, calling the get_order_currency() method (i.e. it's not possible
	 * to alter the currency from the outside). By overriding this method, we can
	 * change the currency to the appropriate one and ensure that the correct
	 * symbol is displayed.
	 *
	 * @return string A currency code.
	 */
	public function get_currency($context = 'view') {
		if(empty($this->currency)) {
			$this->currency = parent::get_currency($context);
		}
		return $this->currency;
	}
}
