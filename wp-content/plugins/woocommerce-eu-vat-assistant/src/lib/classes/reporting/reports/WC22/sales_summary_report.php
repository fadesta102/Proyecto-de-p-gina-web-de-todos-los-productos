<?php
namespace Aelia\WC\EU_VAT_Assistant\Reports\WC22;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use Aelia\WC\EU_VAT_Assistant\WC_Aelia_EU_VAT_Assistant;
use Aelia\WC\EU_VAT_Assistant\Settings;
use Aelia\WC\EU_VAT_Assistant\Definitions;

/**
 * Renders the report containing the details of all sales for each country in a
 * specific period.
 *
 * @since 1.5.8.160112
 */
class Sales_Summary_Report extends \Aelia\WC\EU_VAT_Assistant\Reports\Base_Sales_Summary_Report {
	/**
	 * Returns the sales data that will be included in the report.
	 *
	 * @return array
	 * @since 1.3.20.150402
	 */
	protected function get_sales_data() {
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
				-- Debug information
				,ORDER_META2.meta_value AS vat_number_validated
				,OI.order_item_id AS order_item_id
				,OIM.meta_key AS line_item_key
				,OIM.meta_value AS line_total
				,OIM2.meta_value as line_tax
				,IF((OIM.meta_value > 0) AND (OIM2.meta_key <> 'taxes'), ROUND(OIM2.meta_value / OIM.meta_value * 100, 2), -1) AS tax_rate
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
					(OIM2.meta_key IN ('_line_tax', 'taxes'))
				-- Fetch orders meta
				LEFT JOIN
				{$px}postmeta AS ORDER_META1 ON
					(ORDER_META1.post_id = ORDERS.ID) AND
					(ORDER_META1.meta_key = '_eu_vat_evidence')
				LEFT JOIN
				{$px}postmeta AS ORDER_META2 ON
					(ORDER_META2.post_id = ORDERS.ID) AND
					(ORDER_META2.meta_key = '_vat_number_validated')
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
		return $dataset;
	}

	/**
	 * Returns the refunds data that will be included in the report.
	 *
	 * @return array
	 * @since 1.3.20.150330
	 */
	protected function get_refunds_data() {
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
				-- Debug information
				,ORDER_META2.meta_value AS vat_number_validated
				,RI.order_item_id AS order_item_id
				,RIM1.meta_key AS line_item_key
				,RIM1.meta_value AS product_id
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
				-- Order Meta
				{$px}postmeta AS ORDER_META1 ON
					(ORDER_META1.post_id = REFUNDS.post_parent) AND
					(ORDER_META1.meta_key = '_eu_vat_evidence')
				LEFT JOIN
				{$px}postmeta AS ORDER_META2 ON
					(ORDER_META2.post_id = REFUNDS.post_parent) AND
					(ORDER_META2.meta_key = '_vat_number_validated')
				INNER JOIN
				{$px}postmeta AS ORDER_META3 ON
					(ORDER_META3.post_id = REFUNDS.post_parent) AND
					(ORDER_META3.meta_key = '_eu_vat_data')
				INNER JOIN
				{$px}postmeta AS ORDER_META4 ON
					(ORDER_META4.post_id = REFUNDS.post_parent) AND
					(ORDER_META4.meta_key = '_order_currency')
				-- Refund items
				JOIN
				{$px}woocommerce_order_items RI ON
					(RI.order_id = REFUNDS.ID) AND
					(RI.order_item_type IN ('line_item', 'shipping'))
				JOIN
				-- Refund items meta - Find item/shipping refund amounts
				{$px}woocommerce_order_itemmeta RIM1 ON
					(RIM1.order_item_id = RI.order_item_id) AND
					(RIM1.meta_key IN ('%s'))
				LEFT JOIN
				{$px}woocommerce_order_itemmeta AS RIM2 ON
					(RIM2.order_item_id = RI.order_item_id) AND
					(RIM2.meta_key IN ('_line_tax', 'taxes'))
				LEFT JOIN
				{$px}woocommerce_order_itemmeta AS RIM3 ON
					(RIM3.order_item_id = RI.order_item_id) AND
					(RIM3.meta_key = '_refunded_item_id')
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
		return $dataset;
	}
}
