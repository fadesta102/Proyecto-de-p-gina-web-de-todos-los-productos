<?php
namespace Aelia\WC\EU_VAT_Assistant\Reports\WC22;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Renders the report containing the EU VAT for each country in a specific
 * period.
 */
class Sales_List_Report extends \Aelia\WC\EU_VAT_Assistant\Reports\Base_Sales_Report {
	/**
	 * Returns the refunds data.
	 *
	 * @param array tax_data The tax data produced by EU_VAT_By_Country_Report::get_tax_data().
	 * @return array The tax data, including the refunds.
	 * @see \Aelia\WC\EU_VAT_Assistant\Base_EU_VAT_By_Country_Report::get_tax_data()
	 */
	protected function get_refunds_data() {
		global $wpdb;

		$px = $wpdb->prefix;
		$SQL = "
			SELECT
				REFUNDS.ID
				,REFUNDS.post_date
				,ORDERS.ID AS order_id
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
				(REFUNDS.post_status IN ('wc-processing','wc-completed')) AND
				(REFUNDS.post_date >= '" . date('Y-m-d', $this->start_date) . "') AND
				(REFUNDS.post_date < '" . date('Y-m-d', strtotime('+1 DAY', $this->end_date)) . "')
		";
		// Debug
		if($this->debug) {
			var_dump($SQL);
		}
		$dataset = $wpdb->get_results($SQL);

		// Debug
		//var_dump("REFUNDS RESULT", $dataset);
		return $dataset;
	}
}
