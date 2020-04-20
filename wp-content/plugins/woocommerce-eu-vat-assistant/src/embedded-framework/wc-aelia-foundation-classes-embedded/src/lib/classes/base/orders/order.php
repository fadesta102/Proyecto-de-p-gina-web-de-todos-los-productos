<?php
namespace Aelia\WC;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use \WC_Cache_Helper;

/**
 * Aelia Order. Extends standard WC_Order, providing convenience methods to
 * handle multi-currency environments.
 *
 */
class Order extends \WC_Order {
	/**
	 * Returns the a logger instance.
	 *
	 * @return Aelia\WC\Logger
	 * @since 2.0.5.190301
	 */
	protected function get_logger() {
		return WC_AeliaFoundationClasses::instance()->get_logger();
	}

	/**
	 * Get the order if ID is passed, otherwise the order is new and empty.
	 *
	 * @see WC_Order::__construct()
	 */
	public function __construct($id = '') {
		try {
			parent::__construct($id);
		}
		catch(\Exception $e) {
			// Handle the condition in which an invalid order is requested, without crashing
			// @since 2.0.5.190301
			$this->get_logger()->error(__('Exception occurred while attempting to load an order.', WC_AeliaFoundationClasses::$text_domain), array(
				'Order ID' => $id,
				'Exception Code' => $e->getCode(),
				'Exception Message' => $e->getMessage(),
			));
		}
	}

	/**
	 * Sets the value of a meta attribute for the order.
	 *
	 * @param string meta_key The key of the meta value to set.
	 * @param mixed value The value to set.
	 */
	public function set_meta($meta_key, $value) {
		update_post_meta($this->get_id(), $meta_key, $value);
	}

	/**
	 * Returns the value of a meta attribute for the order.
	 *
	 * Updated for compatibilit with WC 2.7 in version 1.8.2.161216.
	 *
	 * @param string meta_key The key of the meta value to set.
	 * @param bool $single return first found meta with key, or all with $key
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @param mixed value The value to set.
	 */
	public function get_meta($key = '', $single = true, $context = 'view') {
		if(method_exists('\WC_Order', __FUNCTION__)) {
			return parent::get_meta($key, $single, $context);
		}

		return get_post_meta($this->get_id(), $key, $single);
	}

	/**
	 * Sets the currency for the order.
	 *
	 * @param string currency The currency to set against the order.
	 * @return string
	 */
	public function set_order_currency($currency) {
		$original_order_currency = $this->get_currency();
		// WC 2.7 compatibility. Use parent method, if it exists.
		// @since 1.8.2.161216
		if(method_exists('\WC_Order', 'set_currency')) {
			$this->set_currency($currency);
		}
		else {
			$this->set_meta('_order_currency', $currency);
		}

		if(!empty($original_order_currency) && ($currency != $original_order_currency)) {
			// TODO If order had a different currency, recalculate totals in base currency
		}
	}

	/**
	 * Returns the currency in which an order was placed.
	 *
	 * @param string context The context for which the property is returned
	 * have a currency.
	 * @return string
	 * @since 1.8.2.161216
	 * @since WC 2.7
	 */
	public function get_currency($context = 'view') {
		if(method_exists('\WC_Order', __FUNCTION__)) {
			return parent::get_currency($context);
		}

		return $this->get_order_currency();
	}

	/**
	 * Gets order total in base currency.
	 *
	 * @return float
	 */
	public function get_total_in_base_currency() {
		return apply_filters('woocommerce_order_amount_total_base_currency',
												 number_format((double)$this->get_meta('_order_total_base_currency'), 2, '.', ''),
												 $this);
	}

	/**
	 * Alias for get_total_in_base_currency().
	 *
	 * @return float
	 */
	public function get_order_total_in_base_currency() {
		return $this->get_total_in_base_currency();
	}

	/**
	 * Gets shipping and product tax in base currency.
	 *
	 * @return float
	 */
	public function get_total_tax_in_base_currency() {
		return apply_filters('woocommerce_order_amount_total_tax_base_currency',
												 number_format((double)$this->get_meta('_order_tax_base_currency') + (double)$this->get_meta('_order_shipping_tax_base_currency'), 2, '.', ''),
												 $this);
	}

	/**
	 * Gets the total (product) discount amount  in base currency..
	 *
	 * @return float
	 */
	public function get_cart_discount_in_base_currency() {
		return apply_filters('woocommerce_order_amount_cart_discount_base_currency',
												 number_format((double)$this->get_meta('_cart_discount_base_currency'), 2, '.', ''),
												 $this);
	}

	/**
	 * Gets the total (product) discount amount in base currency.
	 *
	 * @return float
	 */
	public function get_order_discount_in_base_currency() {
		return apply_filters('woocommerce_order_amount_order_discount_base_currency',
												 number_format((double)$this->get_meta('_order_discount_base_currency'), 2, '.', ''),
												 $this);
	}

	/**
	 * Gets the total discount amount in base currency.
	 *
	 * @return float
	 */
	public function get_total_discount_in_base_currency() {
		$order_discount_base_currency = $this->get_meta('_order_discount_base_currency');
		$cart_discount_in_base_currency = $this->get_meta('_cart_discount_base_currency');

		if($order_discount_base_currency || $cart_discount_in_base_currency)
			return apply_filters('woocommerce_order_amount_total_discount_base_currency',
													 number_format((double)$order_discount_base_currency + (double)$cart_discount_in_base_currency, 2, '.', ''),
													 $this);
	}

	/**
	 * Gets shipping total in base currency.
	 *
	 * @return float
	 */
	public function get_shipping_in_base_currency() {
		return apply_filters('woocommerce_order_amount_shipping_base_currency',
												 number_format((double)$this->get_meta('_order_shipping_base_currency'), 2, '.', ''),
												 $this);
	}

	/**
	 * Gets shipping tax amount in base currency.
	 *
	 * @return float
	 */
	public function get_shipping_tax_in_base_currency() {
		return apply_filters('woocommerce_order_amount_shipping_tax_base_currency',
												 number_format((double)$this->get_meta('_order_shipping_tax_base_currency'), 2, '.', ''),
												 $this);
	}

	/**
	 * Retrieves the order containing the item with the specified ID.
	 *
	 * @param int item_id The item ID.
	 * @return Aelia_Order
	 */
	public static function get_by_item_id($item_id) {
		global $wpdb;

		$SQL = "
			SELECT
				OI.order_id
			FROM
				{$wpdb->prefix}woocommerce_order_items OI
			WHERE
				(OI.order_item_id = %d)
		";

		$order_id = $wpdb->get_var($wpdb->prepare($SQL, $item_id));
		$class = get_called_class();
		$order = new $class($order_id);

		return $order;
	}

	/**
	 * Returns the VAT number entered by the customer. This method assumes that the
	 * VAT number is stored in a meta field called "vat_number", which is the field
	 * use by the Aelia EU VAT Complicance plugin. A filter allows to override the
	 * field name, so that another meta field can be used.
	 *
	 * @return string A VAT number, or an empty string if none is found.
	 */
	public function get_customer_vat_number() {
		// Get the meta_key that contains the order number
		$meta_key = apply_filters('wc_aelia_vat_number_meta_key', 'vat_number', $this);
		return get_post_meta($this->get_id(), $meta_key, true);
	}

	/**
	 * Returns the order ID.
	 *
	 * @since 1.8.2.161216
	 * @since WooCommerce 2.7.
	 */
	public function get_id() {
		// If parent has this method, let's use it. It means we are in WooCommerce 2.7+
		if(method_exists('\WC_Order', __FUNCTION__)) {
			return parent::get_id();
		}

		// Fall back to legacy method in WooCommerce 2.6 and earlier
		return $this->id;
	}

	/**
	 * Returns an order's property. This method is used to access "native" order
	 * properties, such as billing country, shipping country, and so on. For
	 * other properties, the get_meta() method should be used.
	 *
	 * @param string property The property name.
	 * @param string context
	 * @return string
	 * @since 1.8.6.170405
	 */
	public function get_property($property, $context) {
		if(method_exists('\WC_Order', 'get_' . $property)) {
			$method = "get_{$property}";
			return parent::$method();
		}
		return $this->$property;
	}

	/**
	 * Get customer_ip_address.
	 *
	 * @param string context
	 * @return string
	 * @since 1.8.6.170405
	 */
	public function get_customer_ip_address($context = 'view') {
		return $this->get_property('customer_ip_address', $context);
	}

	/**
	 * Get billing_country.
	 *
	 * @param string context
	 * @return string
	 * @since 1.8.6.170405
	 */
	public function get_billing_country($context = 'view') {
		return $this->get_property('billing_country', $context);
	}

	/**
	 * Get shipping_country.
	 *
	 * @param string context
	 * @return string
	 * @since 1.8.6.170405
	 */
	public function get_shipping_country($context = 'view') {
		return $this->get_property('shipping_country', $context);
	}

	/**
	 * Gets refunded total in base currency.
	 *
	 * @return float
	 * @since 2.0.1.180821
	 */
	public function get_total_refunded_in_base_currency() {
		$cache_key = WC_Cache_Helper::get_cache_prefix('orders') . 'total_refunded_base_currency' . $this->get_id();
		$cached_data = wp_cache_get($cache_key, $this->cache_group);

		if($cached_data !== false) {
			return $cached_data;
		}

		$total_refunded_base_currency = 0;
		foreach($this->get_refunds() as $refund) {
			$refund_total = $refund->get_meta('_refund_amount_base_currency');
			$total_refunded_base_currency += $refund_total;
		}

		wp_cache_set($cache_key, $total_refunded_base_currency, $this->cache_group);

		return $total_refunded_base_currency;
	}
}