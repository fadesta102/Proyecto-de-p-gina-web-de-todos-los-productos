<?php
namespace Aelia\WC\EU_VAT_Assistant\Reports\WC30;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use Aelia\WC\EU_VAT_Assistant\Definitions;

/**
 * Renders the report containing the EU VAT for each country in a specific
 * period.
 *
 * @since 1.11.0.191108
 */
class EU_VAT_By_Country_Report extends \Aelia\WC\EU_VAT_Assistant\Reports\Base_EU_VAT_By_Country_Report {

	/**
	 * Merges the VAT refunds with the tax data.
	 *
	 * @param array tax_data The tax data produced by EU_VAT_By_Country_Report::get_tax_data().
	 * @return array The tax data, including the refunds.
	 * @see \Aelia\WC\EU_VAT_Assistant\Base_EU_VAT_By_Country_Report::get_tax_data()
	 */
	protected function get_tax_refunds_data($tax_data) {
		global $wpdb;
		$px = $wpdb->prefix;

		if(get_arr_value(Definitions::ARG_REFUNDS_PERIOD, $_REQUEST) === Definitions::REFUNDS_IN_PERIOD) {
			$refunds_date_range_where	= "
				-- Gather refunds granted in the selected period. This option is useful
				-- to prepare domestic VAT returns.
				(REFUNDS.post_date >= '" . date('Y-m-d', $this->start_date) . "') AND
				(REFUNDS.post_date < '" . date('Y-m-d', strtotime('+1 DAY', $this->end_date)) . "')
			";
		}
		else {
			$refunds_date_range_where	= "
				-- Gather refunds applied to orders placed within the quarter even if
				-- those refunds were granted in a later quarter.
				--
				-- As per official MOSS instructions:
				-- 7.10 How are credit notes dealt with?
				-- If you raise a credit note in a subsequent calendar quarter you must
				-- amend the original MOSS VAT return. The exchange rate applicable will
				-- be the European Central Bank rate applicable on the last day of the
				-- calendar quarter in which the supply to which the credit note relates
				-- was made.
				--
				-- Original document: http://www.revenue.ie/en/tax/vat/leaflets/mini-one-stop-shop.html
				(ORDERS.post_date >= '" . date('Y-m-d', $this->start_date) . "') AND
				(ORDERS.post_date < '" . date('Y-m-d', strtotime('+1 DAY', $this->end_date)) . "')
			";
		}

		$SQL = sprintf("
			SELECT
				REFUNDS.ID
				,REFUNDS.post_date
				,OM.meta_value AS order_vat_data
				-- ,meta__eu_vat_data.meta_value
				-- Refund items
				,RI.order_item_id AS refund_item_id
				,RI.order_item_name AS refund_item_name
				,RI.order_item_type AS refund_item_type
				-- Item/shipping tax refund data
				,RIM2.meta_key AS tax_refund_data_type
				,RIM2.meta_value AS tax_refund_data
				-- Item/shipping price refund data
				,RIM3.meta_key AS price_refund_data_type
				,RIM3.meta_value AS price_refund_data
			FROM
				{$px}posts AS REFUNDS
				JOIN
				{$px}posts AS ORDERS ON
					(ORDERS.ID = REFUNDS.post_parent)
				JOIN
				-- Order Meta
				{$px}postmeta AS OM ON
					(OM.post_id = ORDERS.ID) AND
					(OM.meta_key = '_eu_vat_data')
				JOIN
				-- Refund items
				{$px}woocommerce_order_items RI ON
					(RI.order_id = REFUNDS.ID) AND
					(RI.order_item_type IN ('line_item', 'shipping'))
				JOIN
				-- Refund items meta - Find refund items
				{$px}woocommerce_order_itemmeta RIM1 ON
					(RIM1.order_item_id = RI.order_item_id) AND
					(RIM1.meta_key = '_refunded_item_id') AND
					(RIM1.meta_value > 0)
				JOIN
				-- Refund items meta - Find item/shipping tax refund data
				{$px}woocommerce_order_itemmeta RIM2 ON
					(RIM2.order_item_id = RI.order_item_id) AND
					(RIM2.meta_key IN ('_line_tax_data', 'taxes'))
				LEFT JOIN
				-- Refund items meta - Find item/shipping price refund data
				{$px}woocommerce_order_itemmeta RIM3 ON
					(RIM3.order_item_id = RI.order_item_id) AND
					(RIM3.meta_key IN ('cost', '_line_total'))
			WHERE
				(REFUNDS.post_type IN ('shop_order_refund')) AND
				-- The statuses to include always refer to the original orders. Refunds
				-- are always in status 'wc-completed'
				(ORDERS.post_status IN ('%s')) AND
			" . $refunds_date_range_where,
			implode("', '", $this->order_statuses_to_include(true)));
		// Debug
		//var_dump($SQL);

		$dataset = $wpdb->get_results($SQL);

		// Debug
		//var_dump("REFUNDS RESULT", $dataset);

		// Initialise totals
		foreach($tax_data as $tax_id => $tax_details) {
			$tax_data[$tax_id]->refunded_items_tax_amount = 0;
			$tax_data[$tax_id]->refunded_shipping_tax_amount = 0;
			$tax_data[$tax_id]->refunded_items_total = 0;
			$tax_data[$tax_id]->refunded_shipping_total = 0;
		}

		// Debug
		//var_dump($dataset);

		foreach($dataset as $data) {
			$order_vat_data = maybe_unserialize($data->order_vat_data);
			$vat_currency_exchange_rate = $order_vat_data['vat_currency_exchange_rate'];

			$refund_data = maybe_unserialize($data->tax_refund_data);

			switch($data->refund_item_type) {
				case 'shipping':
					$tax_refund_data = $refund_data['total'];
					$tax_refund_type = 'refunded_shipping_tax_amount';
					$item_total_to_update = 'refunded_shipping_total';
				break;
				default:
					$tax_refund_data = $refund_data['total'];
					$tax_refund_type = 'refunded_items_tax_amount';
					$item_total_to_update = 'refunded_items_total';
				break;
			}

			// Debug
			//var_dump($data->refund_item_type, $tax_refund_data);

			// Update the totals for each tax ID
			foreach($tax_refund_data as $tax_id => $tax_amount) {
				if(isset($tax_data[$tax_id])) {
					$vat_rate = $tax_data[$tax_id]->tax_rate_data->tax_rate;
					$tax_data[$tax_id]->$tax_refund_type += wc_round_tax_total($tax_amount * $vat_currency_exchange_rate);
					$tax_data[$tax_id]->$item_total_to_update += ($data->price_refund_data * $vat_currency_exchange_rate);
				}
			}
		}
		return $tax_data;
	}
}
