<?php
namespace Aelia\WC\EU_VAT_Assistant\Reports\WC30;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Renders the report containing the details of all sales for each country in a
 * specific period.
 *
 * @since 1.11.0.191108
 */
class Sales_Summary_Report extends \Aelia\WC\EU_VAT_Assistant\Reports\Base_Sales_Summary_Report {
	/**
	 * Returns the sales data that will be included in the report.
	 *
	 * @return array
	 * @since 1.11.0.191108
	 */
	protected function prepare_sales_data() {
		global $wpdb;

		$meta_keys = $this->get_order_items_meta_keys();
		$px = $wpdb->prefix;
		$SQL = sprintf("
			SELECT
				ORDERS.ID AS order_id
				,ORDERS.post_type AS post_type
				,DATE(ORDERS.post_date) AS order_date
				,ORDER_META1.meta_value AS eu_vat_evidence
				,ORDER_META3.meta_value AS eu_vat_data
				,ORDER_META4.meta_value AS order_currency
				,OI.order_item_id AS order_item_id
				,OIM.meta_key AS line_item_key
				,OIM.meta_value AS line_total
				,OIM2.meta_value as line_tax_data
				,IF((OIM.meta_value <> 0) AND (OIM2.meta_value <> 0), ROUND(OIM2.meta_value / OIM.meta_value * 100, 2), 0) AS tax_rate
			FROM
				{$px}posts AS ORDERS
				INNER JOIN
				{$px}woocommerce_order_items AS OI ON
					(OI.order_id = ORDERS.ID)
				INNER JOIN
				{$px}woocommerce_order_itemmeta AS OIM ON
					(OIM.order_item_id = OI.order_item_id) AND
					(OIM.meta_key in ('%s'))
				LEFT JOIN
				{$px}woocommerce_order_itemmeta AS OIM2 ON
					(OIM2.order_item_id = OI.order_item_id) AND
					(OIM2.meta_key IN ('_line_tax_data', 'taxes'))
				-- Fetch orders meta
				LEFT JOIN
				{$px}postmeta AS ORDER_META1 ON
					(ORDER_META1.post_id = ORDERS.ID) AND
					(ORDER_META1.meta_key = '_eu_vat_evidence')
				INNER JOIN
				{$px}postmeta AS ORDER_META3 ON
					(ORDER_META3.post_id = ORDERS.ID) AND
					(ORDER_META3.meta_key = '_eu_vat_data')
				INNER JOIN
				{$px}postmeta AS ORDER_META4 ON
					(ORDER_META4.post_id = ORDERS.ID) AND
					(ORDER_META4.meta_key = '_order_currency')
			WHERE
				(ORDERS.post_type = 'shop_order') AND
				(ORDERS.post_status IN ('%s')) AND
				(ORDERS.post_date >= '" . date('Y-m-d', $this->start_date) . "') AND
				(ORDERS.post_date < '" . date('Y-m-d', strtotime('+1 DAY', $this->end_date)) . "')
		",
		implode("', '", $meta_keys),
		implode("', '", $this->order_statuses_to_include(true)));

		// Debug
		if($this->debug) {
			var_dump("SALES DATA QUERY", $SQL);
		}
		$dataset = $wpdb->get_results($SQL);

		// Debug
		//var_dump("SALES DATA", $dataset);die();
		return $this->store_sales_data($dataset);
	}

	/**
	 * Extracts the tax information from the orders data and stores it into
	 * a temporary table, for the generation of the VAT RTD report.
	 *
	 * @param array dataset An array containing the data for the report.
	 * @return bool True if the data was stored correctly, false otherwise.
	 * @since 1.11.0.191108
	 */
	protected function store_sales_data($dataset) {
		foreach($dataset as $index => $entry) {
			$entry->eu_vat_data = maybe_unserialize($entry->eu_vat_data);
			$entry->eu_vat_evidence = maybe_unserialize($entry->eu_vat_evidence);

			if(!$this->should_skip($entry)) {
				// Select between the the exchange rate associated with each order, or the
				// ECB rate for the quarter
				// @since 1.12.4.200131
				// @link https://wordpress.org/support/topic/sales-summary-report-always-uses-the-exchange-rates-saved-with-each-order/
				if($this->should_use_orders_exchange_rates()) {
					$vat_currency_exchange_rate = (float)get_value('vat_currency_exchange_rate', $entry->eu_vat_data);
				}
				else {
					// Get the ECB exchange rates for the last day of the quarter in which the order
					// was placed
					$last_day_of_quarter = $this->get_last_day_of_quarter($entry->order_date);

					$vat_currency_exchange_rate = $this->get_vat_currency_exchange_rate($entry->order_currency, $last_day_of_quarter);

					// If the exchange rate is not available, fall back to the one used with
					// the order
					if(!is_numeric($vat_currency_exchange_rate)) {
						$vat_currency_exchange_rate = (float)$entry->eu_vat_data['vat_currency_exchange_rate'];
					}
				}

				if(!is_numeric($vat_currency_exchange_rate) || ($vat_currency_exchange_rate <= 0)) {
					$this->log(sprintf(__('VAT currency exchange rate not found for order id "%s". ' .
																'Fetching exchange rate from FX provider.', $this->text_domain),
														 $entry->order_id));
					$vat_currency_exchange_rate = $this->get_vat_currency_exchange_rate($entry->order_currency,
																																							$entry->order_date);
				}

				// Debug
				//var_dump($entry->order_id . ', ' . $entry->order_item_id . ',' . $entry->line_total . ',' . $entry->line_tax . ',' . $vat_currency_exchange_rate);

				$fields = array(
					'order_id' => $entry->order_id,
					'post_type' => $entry->post_type,
					'is_eu_country' => $this->is_eu_country($entry->eu_vat_evidence['location']['billing_country']) ? 'eu' : 'non-eu',
					'billing_country' => $entry->eu_vat_evidence['location']['billing_country'],
					'order_item_id' => $entry->order_item_id,
					'exchange_rate' => $vat_currency_exchange_rate,
				);

				//var_dump($entry->eu_vat_data);

				// Extract the tax data, which contains the tax charged for each tax ID
				// @since 1.11.0.191108
				$line_tax_data = maybe_unserialize($entry->line_tax_data);
				if(!is_array($line_tax_data)) {
					$line_tax = 0;
				} else {
					if(is_array($line_tax_data['total'])) {
						$line_tax = array_sum($line_tax_data['total']);
					}
					else {
						$line_tax = 0;
						// Log a warning if the line tax data is not in the expected format
						$this->logger->warning(__('Sales Summary Report', $this->text_domain) . ' - ' .
						__('Line tax data "totals" are not present for order item.', $this->text_domain) . ' ' .
						__('The report might not be accurate.', $this->text_domain), array(
							'Line Tax Data' => $line_tax_data ,
							'Entry' => $entry,
							));
					}

				}

				if($line_tax == 0) {
					// Entries that have zero tax can be added to the temporary table as they
					// are. They will fall under the zero-rated transactions in the report
					$fields['line_total'] = is_numeric($entry->line_total) ? wc_round_tax_total($entry->line_total * $vat_currency_exchange_rate) : 0;
					$fields['line_tax'] = $line_tax ;
					$fields['tax_rate'] = 0;
				}
				else {
					// When an entry has taxes, we need to find out the VAT rate that applied to them. We can do that by finding
					// the tax rate ID applied to each item, and fetching the rate from the data stored by the EU VAT Assistant
					// against that ID
					//
					// IMPORTANT
					// We can't just reverse-calculate the VAT rate from the price and the VAT, like it was done before, because
					// WooCommerce could store too few decimals for a reliable calculation.
					//
					// EXAMPLE
					// - Price: 4.5
					// - VAT: 21% = 0.945
					// - VAT stored in the database by WooCommerce: 0.95
					// - VAT RATE calculates in reverse: 0.95 / 4.5 = 21.111% (instead of the correct 21%)
					// @link https://wordpress.org/support/topic/reports-not-showing-correct-tax-rate/
					reset($line_tax_data['total']);
					$item_tax_id = key($line_tax_data['total']);

					//var_dump($item_tax_id, $entry->eu_vat_data['taxes'][$item_tax_id]);

					// If a VAT rate is found, we can take it. If not, we can assume it's zero
					$fields['tax_rate'] = isset($entry->eu_vat_data['taxes'][$item_tax_id]) ? $entry->eu_vat_data['taxes'][$item_tax_id]['vat_rate'] : 0;
					$fields['line_tax'] = wc_round_tax_total($line_tax  * $vat_currency_exchange_rate);

					// If the tax rate is zero, we can't calculate the line total
					// @since 1.11.0.191108
					if($fields['tax_rate'] != 0) {
						// Important
						// Don't use $fields['line_tax'] here. That value has already been converted using
						// the exchange rate, and using it would cause a double conversion
						$fields['line_total'] = ($line_tax / ($fields['tax_rate'] / 100)) * $vat_currency_exchange_rate;
					}
				}

				// Extra check, for easier debugging. If the tax rate is zero, but the tax amount is not,
				// something could be wrong in the order data. In such case, it's better to log the issue,
				// to allow an investigation
				// @since 1.11.0.191108
				if(($fields['tax_rate'] == 0) && ($fields['line_tax'] != 0)) {
					$this->logger->warning(__('Sales Summary Report', $this->text_domain) . ' - ' .
																 __('Found entry with a zero tax rate, but a non-zero tax amount.', $this->text_domain), array(
						'Fields' => $fields,
						'Entry' => $entry,
					));
				}

				// The last step is storing the data for the report
				if(!$this->store_temp_data($fields)) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Returns the refunds data that will be included in the report.
	 *
	 * @return array
	 * @since 1.11.0.191108
	 */
	protected function prepare_refunds_data() {
		global $wpdb;

		$meta_keys = $this->get_order_items_meta_keys();

		$px = $wpdb->prefix;
		$SQL = sprintf("
			SELECT
				REFUNDS.ID AS refund_id
				,REFUNDS.post_type AS post_type
				,DATE(REFUNDS.post_date) AS refund_date
				,REFUNDS.post_parent AS order_id
				,ORDER_META1.meta_value AS eu_vat_evidence
				,ORDER_META3.meta_value AS eu_vat_data
				,ORDER_META4.meta_value AS order_currency
				,RI.order_item_id AS order_item_id
				,RIM1.meta_key AS line_item_key
				,RIM1.meta_value AS line_total
				,RIM2.meta_value AS line_tax
				,RIM3.meta_value AS refunded_order_item_id
				,SALES.tax_rate AS tax_rate
			FROM
				{$px}posts AS REFUNDS
				JOIN
				{$px}posts AS ORDERS ON
					(ORDERS.ID = REFUNDS.post_parent)
				LEFT JOIN
				-- EU VAT evidence, from original order
				{$px}postmeta AS ORDER_META1 ON
					(ORDER_META1.post_id = REFUNDS.post_parent) AND
					(ORDER_META1.meta_key = '_eu_vat_evidence')
				INNER JOIN
				-- EU VAT data (e.g. rates), from original order
				{$px}postmeta AS ORDER_META3 ON
					(ORDER_META3.post_id = REFUNDS.post_parent) AND
					(ORDER_META3.meta_key = '_eu_vat_data')
				INNER JOIN
				-- Order currency, from original order
				{$px}postmeta AS ORDER_META4 ON
					(ORDER_META4.post_id = REFUNDS.post_parent) AND
					(ORDER_META4.meta_key = '_order_currency')
				-- Refund items
				JOIN
				-- Refunded items, from the refund
				{$px}woocommerce_order_items RI ON
					(RI.order_id = REFUNDS.ID) AND
					(RI.order_item_type IN ('line_item', 'shipping'))
				JOIN
				-- Refund items meta - Find item/shipping refund amounts
				{$px}woocommerce_order_itemmeta RIM1 ON
					(RIM1.order_item_id = RI.order_item_id) AND
					(RIM1.meta_key IN ('%s'))
				LEFT JOIN
				-- Refunded items tax, from the refund
				{$px}woocommerce_order_itemmeta AS RIM2 ON
					(RIM2.order_item_id = RI.order_item_id) AND
					(RIM2.meta_key IN ('_line_tax', 'total_tax'))
				LEFT JOIN
				-- ID of the order item that was refunded
				{$px}woocommerce_order_itemmeta AS RIM3 ON
					(RIM3.order_item_id = RI.order_item_id) AND
					(RIM3.meta_key = '_refunded_item_id')
				-- Join with the temporary table populated with sales data
				-- This will be used to fetch the tax rate, without having
				-- to calculate it again.
				-- @see Sales_Summary_Report::store_sales_data()
				LEFT JOIN
				{$px}" . self::SALES_SUMMARY_REPORT_TEMP_TABLE . " AS SALES ON
					(SALES.order_item_id = RIM3.meta_value)
			WHERE
				(REFUNDS.post_type IN ('shop_order_refund')) AND
				-- The statuses to include always refer to the original orders. Refunds
				-- are always in status 'wc-completed'
				(ORDERS.post_status IN ('%s')) AND
				(REFUNDS.post_date >= '" . date('Y-m-d', $this->start_date) . "') AND
				(REFUNDS.post_date < '" . date('Y-m-d', strtotime('+1 DAY', $this->end_date)) . "')
		",
		implode("', '", $meta_keys),
		implode("', '", $this->order_statuses_to_include(true)));

		// Debug
		if($this->debug) {
			var_dump("REFUNDS DATA QUERY", $SQL);
		}
		$dataset = $wpdb->get_results($SQL);

		// Debug
		//var_dump("REFUNDS RESULT", $dataset);

		return $this->store_refunds_data($dataset);
	}

	/**
	 * Extracts the tax information from the refunds data and stores it into
	 * a temporary table, for the generation of the VAT RTD report.
	 *
	 * @param array dataset An array containing the data for the report.
	 * @return bool True if the data was stored correctly, false otherwise.
	 * @since 1.10.1.191108
	 */
	protected function store_refunds_data($dataset) {
		foreach($dataset as $index => $entry) {
			$entry->eu_vat_data = maybe_unserialize($entry->eu_vat_data);
			$entry->eu_vat_evidence = maybe_unserialize($entry->eu_vat_evidence);

			if(!$this->should_skip($entry)) {
				$vat_currency_exchange_rate = (float)get_value('vat_currency_exchange_rate', $entry->eu_vat_data);
				if(!is_numeric($vat_currency_exchange_rate) || ($vat_currency_exchange_rate <= 0)) {
					$this->log(sprintf(__('VAT currency exchange rate not found for order id "%s". ' .
																'Fetching exchange rate from FX provider.', $this->text_domain),
														 $entry->order_id));
					$vat_currency_exchange_rate = $this->get_vat_currency_exchange_rate($entry->order_currency,
																																							$entry->order_date);
				}

				// Debug
				//var_dump($entry->order_id . ', ' . $entry->order_item_id . ',' . $entry->line_total . ',' . $entry->line_tax . ',' . $vat_currency_exchange_rate);

				$fields = array(
					'order_id' => $entry->refund_id,
					'post_type' => $entry->post_type,
					'is_eu_country' => $this->is_eu_country($entry->eu_vat_evidence['location']['billing_country']) ? 'eu' : 'non-eu',
					'billing_country' => $entry->eu_vat_evidence['location']['billing_country'],
					'order_item_id' => $entry->order_item_id,
					'exchange_rate' => $vat_currency_exchange_rate,
				);

				// Add the refund data as it is. The line total, tax and rate are linked to the original
				// order item that has been refunded, therefore they don't need to be extracted from
				// the VAT data
				$fields['line_total'] = is_numeric($entry->line_total) ? wc_round_tax_total($entry->line_total * $vat_currency_exchange_rate) : 0;
				$fields['line_tax'] = is_numeric($entry->line_tax) ? wc_round_tax_total($entry->line_tax * $vat_currency_exchange_rate) : 0;
				$fields['tax_rate'] = is_numeric($entry->tax_rate) ? $entry->tax_rate : 0;
				if(!$this->store_temp_data($fields)) {
					return false;
				}
			}
		}

		return true;
	}
}
