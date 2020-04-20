<?php
namespace Aelia\WC\EU_VAT_Assistant;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use Aelia\WC\IP2Location;
use \Exception;

/**
 * Extends the Aelia Order class to add convenience methods to store and retrieve
 * EU VAT evidence.
 */
class Order extends \Aelia\WC\Order {
	// @var string The meta key that will hold the data about the VAT applied to the order.
	const META_EU_VAT_DATA = '_eu_vat_data';
	// @var string The meta key that will hold the VAT evidence required by EU regulations.
	const META_EU_VAT_EVIDENCE = '_eu_vat_evidence';

	/*
	 * Convenience method to access the settings controller.
	 *
	 * @return \Aelia\WC\EU_VAT_Assistant\Settings
	 */
	public static function settings() {
		return WC_Aelia_EU_VAT_Assistant::settings();
	}

	/**
	 * Stores the evidence required by EU VAT compliance regulations.
	 *
	 * @return array The VAT evidence stored against the order.
	 */
	public function store_vat_evidence() {
		$euva_instance = WC_Aelia_EU_VAT_Assistant::instance();

		// Refresh the meta data. This is needed in WC 3.0.7 and later, because those
		// versions can cache the order meta and prevent the "freshly added" VAT
		// information from being retrieved.
		// @since 1.7.9.170602
		if(aelia_wc_version_is('>=', '3.0')) {
			$this->read_meta_data(true);
		}

		$vat_evidence = array(
			// "Sign" the VAT data, so that we can determine which plugin version created
			// it and how to update it, if needed
			'eu_vat_assistant_version' => WC_Aelia_EU_VAT_Assistant::$version,
			'location' => array(
				'is_eu_country' => (int)$euva_instance->is_eu_country($this->get_billing_country()),
				'billing_country' => $this->get_billing_country(),
				'shipping_country' => $this->get_shipping_country(),
				'customer_ip_address' => $this->get_customer_ip_address(),
				'customer_ip_address_country' => IP2Location::factory()->get_country_code($this->get_customer_ip_address()),
				'self_certified' => ($this->get_meta('_customer_location_self_certified')) == 'yes' ? 'yes' : 'no',
			),
			'exemption' => array(
				'vat_number' => $this->get_customer_vat_number(),
				'vat_country' => $this->get_meta('_vat_country'),
				'vat_number_validated' => $this->get_meta('_vat_number_validated'),
				// VIES VAT Number validation data
				// @since 1.9.0.181022
				'vies_response' => $this->get_meta('vies_response'),
				'vies_consultation_number' => $this->get_meta('vies_consultation_number'),
			),
		);

		$vat_evidence = apply_filters('wc_aelia_eu_vat_assistant_store_vat_evidence', $vat_evidence, $this);

		// Debug
		//var_dump($vat_evidence);die();

		$this->set_meta(self::META_EU_VAT_EVIDENCE, $vat_evidence);

		return $vat_evidence;
	}

	/**
	 * Returns the EU VAT evidence stored against the order.
	 *
	 * @return array The VAT evidence stored against the order.
	 */
	public function get_vat_evidence() {
		return $this->get_meta(self::META_EU_VAT_EVIDENCE);
	}

	/**
	 * Populates an array of tax descriptors with additional information about the
	 * tax, such as the rate applied, the class, etc.
	 *
	 * @param array taxes An array of tax descriptors, as returned by WC_Order::get_taxes().
	 * @return array The array of tax descriptors, with the additional tax information.
	 * @see \WC_Order::get_taxes()
	 * @deprecated since WC 3.0
	 */
	protected function legacy_add_tax_rates_details(array $taxes) {
		global $wpdb;

		if(empty($taxes)) {
			return $taxes;
		}

		// Debug
		//var_dump($taxes);

		$tax_rate_ids = array();
		foreach($taxes as $order_tax_id => $tax) {
			// Keep track of which tax ID corresponds to which ID within the order.
			// This information will be used to add the new information to the correct
			// elements in the $taxes array
			$tax_rate_ids[(int)$tax['rate_id']] = $order_tax_id;
		}

		$SQL = "
			SELECT
				TR.tax_rate_id
				,TR.tax_rate
				,TR.tax_rate_class
				,TR.tax_rate_country
				,COALESCE(TR.tax_payable_to_country, TR.tax_rate_country) AS tax_payable_to_country
			FROM
				{$wpdb->prefix}woocommerce_tax_rates TR
			WHERE
				(TR.tax_rate_id IN (%s))
		";
		// We cannot use $wpdb::prepare(). We need the result of the implode()
		// call to be injected as is, while the prepare() method would wrap it in quotes.
		$SQL = sprintf($SQL, implode(',', array_keys($tax_rate_ids)));

		// Populate the original tax array with the tax details
		$tax_rates_info = $wpdb->get_results($SQL, ARRAY_A);
		foreach($tax_rates_info as $tax_rate_info) {
			// Find to which item the details belong, amongst the order taxes
			$order_tax_id = (int)$tax_rate_ids[$tax_rate_info['tax_rate_id']];
			$taxes[$order_tax_id]['tax_rate'] = $tax_rate_info['tax_rate'];
			// Note: an empty tax rate class is not an error. It simply represents the
			// "Standard" class
			$taxes[$order_tax_id]['tax_rate_class'] = $tax_rate_info['tax_rate_class'];
			$taxes[$order_tax_id]['tax_rate_country'] = $tax_rate_info['tax_rate_country'];
			$taxes[$order_tax_id]['tax_payable_to_country'] = $tax_rate_info['tax_payable_to_country'];

			// Attach the rest tax information to the original array, for convenience
			$taxes[$order_tax_id]['tax_info'] = $tax_rate_info;
		}
		// Debug
		//var_dump($taxes);die();
		return $taxes;
	}

	/**
	 * Populates an array of tax descriptors with additional information about the
	 * tax, such as the rate applied, the class, etc.
	 *
	 * @param array taxes An array of tax descriptors, as returned by WC_Order::get_taxes().
	 * @return array The array of tax descriptors, with the additional tax information.
	 * @see \WC_Order::get_taxes()
	 * @since WC 3.0
	 */
	protected function add_tax_rates_details(array $taxes) {
		global $wpdb;

		if(empty($taxes)) {
			return $taxes;
		}

		// Debug
		//var_dump($taxes);

		$tax_rate_ids = array();
		$result = array();
		foreach($taxes as $order_tax_id => $tax) {
			// Keep track of which tax ID corresponds to which ID within the order.
			// This information will be used to add the new information to the correct
			// elements in the $taxes array
			$tax_rate_ids[(int)$tax->get_rate_id()] = $order_tax_id;
		}

		$SQL = "
			SELECT
				TR.tax_rate_id
				,TR.tax_rate
				,TR.tax_rate_class
				,TR.tax_rate_country
				,COALESCE(TR.tax_payable_to_country, TR.tax_rate_country) AS tax_payable_to_country
			FROM
				{$wpdb->prefix}woocommerce_tax_rates TR
			WHERE
				(TR.tax_rate_id IN (%s))
		";
		// We cannot use $wpdb::prepare(). We need the result of the implode()
		// call to be injected as is, while the prepare() method would wrap it in quotes.
		$SQL = sprintf($SQL, implode(',', array_keys($tax_rate_ids)));

		// Populate the original tax array with the tax details
		$tax_rates_info = $wpdb->get_results($SQL, ARRAY_A);
		foreach($tax_rates_info as $tax_rate_info) {
			// Find to which item the details belong, amongst the order taxes
			$order_tax_id = (int)$tax_rate_ids[$tax_rate_info['tax_rate_id']];

			// Get the tax item, to fetch its label and ID
			// @since 1.9.10.190508
			$tax_item = $taxes[$order_tax_id];

			$result[$order_tax_id] = array(
				'rate_id' => $tax_item->get_rate_id(),
				'label' => $tax_item->get_label(),
				'tax_rate' => $tax_rate_info['tax_rate'],
				// Note: an empty tax rate class is not an error. It simply represents the
				// "Standard" class
				'tax_rate_class' => $tax_rate_info['tax_rate_class'],
				'tax_rate_country' => $tax_rate_info['tax_rate_country'],
				'tax_payable_to_country' => $tax_rate_info['tax_payable_to_country'],
				// Attach the rest tax information to the original array, for convenience
				'tax_info' => $tax_rate_info,
			);
		}
		// Debug
		//var_dump($result);die();
		return $result;
	}

	/**
	 * Adds tax rate details to the tax data associated with the order. This method
	 * takes a "snapshot" of the tax details active at the moment in which the order
	 * was placed, so that further changes to them, made after the order, won't
	 * impact on the reports.
	 *
	 * @param array vat_data An array of data produced by Order::update_vat_data()
	 * @return array The array of VAT data including the details of the tax rates.
	 * @since 0.9.9.141223
	 * @see Order::update_vat_data()
	 */
	public function add_tax_rates_data(array $vat_data) {
		// Get order taxes details
		if(aelia_wc_version_is('>=', '3.0')) {
			$taxes = $this->add_tax_rates_details($this->get_taxes());
		}
		else {
			$taxes = $this->legacy_add_tax_rates_details($this->get_taxes());
		}

		$taxes_data = array();
		// Debug
		//var_dump($taxes);die();
		// Save the information about each VAT rate applied to the order
		foreach($taxes as $tax) {
			if(!$this->is_valid_vat($tax)) {
				continue;
			}

			$tax_rate_id = $tax['rate_id'];
			if(!isset($taxes_data[$tax_rate_id])) {
				$taxes_data[$tax_rate_id] = array(
					'label' => $tax['label'],
					'vat_rate' => $tax['tax_rate'],
					'country' => $tax['tax_rate_country'],
					'tax_rate_class' => $tax['tax_rate_class'],
					'tax_payable_to_country' => $tax['tax_payable_to_country'],
				);
			}
		}
		$vat_data['taxes'] = $taxes_data;
		return $vat_data;
	}

	/**
	 * Stores some basic VAT details, which don't depend on the fact that VAT was
	 * actually applied to the order. Such information will be useful for reporting
	 * purposes.
	 *
	 * @return array The VAT data associated to the order.
	 */
	protected function update_basic_vat_data() {
		$rounding_decimals = self::settings()->get(Settings::FIELD_VAT_ROUNDING_DECIMALS);
		$order_currency = $this->get_currency();
		$vat_currency = self::settings()->vat_currency();

		$vat_data = $this->get_vat_data();
		// If VAT data is lacking an exchange rate, then it was not populated yet
		// In sucj case, populate it with some defaults. If already populated,
		// leave the data that was already there
		if(empty($vat_data['vat_currency_exchange_rate'])) {
			$vat_data = array(
				'invoice_currency' => $order_currency,
				'vat_currency' => $vat_currency,
				'vat_currency_exchange_rate' => apply_filters('wc_aelia_eu_vat_assistant_convert',
																											(float)1.00,
																											$order_currency,
																											$vat_currency,
																											$rounding_decimals),
				'vat_currency_exchange_rate_timestamp' => $this->settings()->get(Settings::FIELD_EXCHANGE_RATES_LAST_UPDATE),
				'exchange_rates_provider_label' => $this->settings()->get_current_exchange_rates_provider_label(),
			);
		}
		return $vat_data;
	}

	/**
	 * Extracts the tax data from an instance of WC_Order_Item_Tax.
	 *
	 * @param WC_Order_Item_Tax tax A tax item instance.
	 * @return array
	 * @since 1.7.6.170415
	 */
	protected function extract_tax_data($tax) {
		return array(
			'rate_id' => $tax->get_rate_id(),
			'tax_amount' => $tax->get_tax_total(),
			'shipping_tax_amount' => $tax->get_shipping_tax_total(),
		);
	}

	/**
	 * Processes the order information to generate the data about the VAT applied
	 * to the order, and stores it in order's metadata.
	 *
	 * @return array An array with the details of the VAT applied to the order.
	 */
	public function update_vat_data() {
		// Store basic VAT information that apply whether VAT was added or not to the
		// order
		$vat_data = $this->update_basic_vat_data();

		$taxes = $this->get_taxes();
		if(is_array($taxes)) {
			/* Add tax details, such as labels and rates. These details must be saved
			 * with the order because they can change later on. Admins can change tax
			 * rates and labels in WooCommerce > Tax Settings, thus making the tax rate
			 * ID a useless information.
			 */
			$vat_data = $this->add_tax_rates_data($vat_data);

			// Calculate totals, in order currency, for each VAT rate
			foreach($taxes as $tax) {
				// WooCommerce 3.0 passes an instance of WC_Order_Item_Tax instead of an
				// array, so we need to extract the required data from it
				if(aelia_wc_version_is('>=', '3.0')) {
					$tax = $this->extract_tax_data($tax);
				}

				$tax_rate_id = $tax['rate_id'];
				if(!isset($vat_data['taxes'][$tax_rate_id]['amounts'])) {
					$vat_data['taxes'][$tax_rate_id]['amounts'] = array(
						'items_total' => 0,
						'shipping_total' => 0,
					);
				}

				// Debug
				//var_dump("TAX DATA", $tax);die();

				$vat_data['taxes'][$tax_rate_id]['amounts']['items_total'] += get_value('tax_amount', $tax, 0);
				$vat_data['taxes'][$tax_rate_id]['amounts']['shipping_total'] += get_value('shipping_tax_amount', $tax, 0);
			}
		}
		// "Sign" the VAT data, so that we can determine which plugin version created
		// it and how to update it, if needed
		$vat_data['eu_vat_assistant_version'] = WC_Aelia_EU_VAT_Assistant::$version;
		// Remove old version signature
		if(isset($vat_data['aelia_euva_version'])) {
			unset($vat_data['aelia_euva_version']);
		}
		// Sort VAT data by key. It's easier to debug discrepancies when the fields
		// are always in the same order
		ksort($vat_data);

		// Debug
		//var_dump($vat_data);die();

		// Allow 3rd parties to add more data, if needed
		$vat_data = apply_filters('wc_aelia_eu_vat_assistant_set_order_vat_data', $vat_data, $this);
		$this->set_meta(self::META_EU_VAT_DATA, $vat_data);

		return $vat_data;
	}

	/**
	 * Indicates if the tax is a VAT.
	 *
	 * @param array tax A tax descriptor.
	 * @return bool True if the tax is a VAT, false if it's another type of tax.
	 */
	protected function tax_is_vat($tax) {
		// TODO Implement logic to determine if a tax is a VAT.
		/* Suggestion by D.Anderson
		 * The logic used to determine it could simply be comparison of the tax label
		 * with a list of labels that indicate VATs. If the tax label matches, then
		 * it's considered a VAT. If not, then it's considered another tax type.
		 */
		return true;
	}

	/**
	 * Indicates if the tax is a valid VAT.
	 *
	 * @param array tax A tax descriptor.
	 * @return bool
	 */
	protected function is_valid_vat($tax) {
		return is_array($tax) && !empty($tax['label']) && $this->tax_is_vat($tax);
	}

	/**
	 * Returns the VAT totals paid with the order. This method relies on the __get()
	 * magic method introduced in WooCommerce 2.1, which retrieves post meta automatically.
	 *
	 * @param string key If specified, the method will only return the VAT data
	 * identified by the key. If left empty, the whole VAT data object is returned.
	 * @return array|false
	 * @see \WC_Order::__get()
	 */
	public function get_vat_data($key = null) {
		// Get the tax data
		$vat_data = $this->get_meta(self::META_EU_VAT_DATA);

		if(empty($vat_data)) {
			return $vat_data;
		}

		// Step 1 - Retrieve VAT refunds, they will be added to the totals
		$vat_refunds = $this->get_vat_refunds(array_keys(get_value('taxes', $vat_data, array())));

		// Step 2 - Calculate the totals for each VAT rate and the grand totals
		$vat_grand_totals = array(
			'items_total' => 0,
			'shipping_total' => 0,
			'items_refund' => 0,
			'shipping_refund' => 0,
			'total' => 0,
		);
		if(empty($vat_data['taxes'])) {
			$vat_data['taxes'] = array();
		}
		foreach($vat_data['taxes'] as $tax_rate_id => $vat_info) {
			if(!empty($vat_refunds[$tax_rate_id])) {
				$vat_amounts = array_merge($vat_info['amounts'], $vat_refunds[$tax_rate_id]);
			}
			else {
				$vat_amounts = $vat_info['amounts'];
			}

			$vat_amounts['total'] = $vat_amounts['items_total'] + $vat_amounts['shipping_total'];

			if(!empty($vat_refunds)) {
				$vat_amounts['total'] = $vat_amounts['total']
																- $vat_amounts['items_refund']
																- $vat_amounts['shipping_refund'];
			}

			// Attach the totals to the VAT item
			$vat_data['taxes'][$tax_rate_id]['amounts'] = $vat_amounts;

			// Update the grand totals as well
			foreach($vat_amounts as $vat_key => $vat_amount) {
				$vat_grand_totals[$vat_key] += $vat_amount;
			}
		}
		$vat_data['totals'] = $vat_grand_totals;

		// Debug
		//var_dump($vat_data);die();

		// Allow 3rd parties to add more data, if needed
		$vat_data = apply_filters('wc_aelia_eu_vat_assistant_get_order_vat_data', $vat_data, $this);

		// If a key was specified, return only the VAT data associated to that key
		if(!empty($key)) {
			return get_value($key, $vat_data, false);
		}
		return $vat_data;
	}

	/**
	 * Returns the refunds stored against the orders for a given set of tax IDs.
	 *
	 * @param array tax_ids A list of tax ids.
	 * @ return array A list of tax id => refund total pairs.
	 */
	public function get_vat_refunds(array $tax_ids) {
		// Refunds are supported only from WooCommerce 2.2 onwards
		if(!method_exists($this, 'get_refunds')) {
			return array();
		}

		$totals = array(
			'totals' => array(
				'items_refund' => 0,
				'shipping_refund' => 0,
			),
		);
		// No point in doing any work if there are no Tax IDs to process
		if(empty($tax_ids)) {
			return $totals;
		}

		foreach($tax_ids as $tax_id) {
			$totals[$tax_id] = array(
				'items_refund' => 0,
				'shipping_refund' => 0,
			);
		}

		$item_types = array('line_item', 'shipping');
		foreach($this->get_refunds() as $refund) {
			foreach($refund->get_items($item_types) as $refunded_item) {
				if(isset($refunded_item['refunded_item_id'])) {
					switch($refunded_item['type']) {
						case 'shipping':
							$tax_data = maybe_unserialize($refunded_item['taxes']);
							$tax_data = $tax_data['total'];
							$refund_type = 'shipping_refund';
						break;
						default:
							$tax_data = maybe_unserialize($refunded_item['line_tax_data']);
							$tax_data = $tax_data['total'];
							$refund_type = 'items_refund';
						break;
					}
				}
				// Update the totals for each tax ID
				foreach($tax_data as $tax_id => $tax_amount) {
					if(isset($totals[$tax_id])) {
						$totals[$tax_id][$refund_type] += $tax_amount;
					}
				}
			}
		}

		// Make the totals positive (they are stored as negative numbers)
		foreach($totals as $tax_id => $amounts) {
			foreach($amounts as $refund_type => $value) {
				$totals[$tax_id][$refund_type] = $value * -1;
				$totals['totals'][$refund_type] += $totals[$tax_id][$refund_type];
			}
		}
		return $totals;
	}

	/**
	 * Indicates if the order should be exempt from EU VAT.
	 *
	 * @return bool
	 * @since 1.8.0.180604
	 */
	public function is_order_eu_vat_exempt() {
		$is_eu_vat_exempt = false;

		$eu_vat_evidence = $this->get_vat_evidence();
		// Check if a valid VAT number was entered with the order
		$vat_number_validated = !empty($eu_vat_evidence['exemption']['vat_number_validated']) ?
														($eu_vat_evidence['exemption']['vat_number_validated'] == Definitions::VAT_NUMBER_VALIDATION_VALID) : false;

		if(!empty($eu_vat_evidence['location'])) {
			// The order is EU VAT exempt if the following conditions are satisfied
			// - The billing country is in the EU
			// - A valid EU VAT number was entered
			// - The billing country is different from shop's base country
			if(!empty($eu_vat_evidence['location']['is_eu_country']) &&
				 ($vat_number_validated) &&
				 ($eu_vat_evidence['location']['billing_country'] != wc()->countries->get_base_country())) {
				$is_vat_exempt = true;
			}
		}
		return $is_eu_vat_exempt;
	}
}
