<?php
namespace Aelia\WC\EU_VAT_Assistant\Reports\WC30;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use Aelia\WC\EU_VAT_Assistant\Definitions;

/**
 * Renders the VIES Report.
 *
 * @since 1.11.0.191108
 */
class VIES_Report extends \Aelia\WC\EU_VAT_Assistant\Reports\Base_VIES_Report {
	/**
	 * Returns the sales data that will be included in the report.
	 *
	 * @return array
	 * @since 1.11.0.191108
	 */
	protected function get_sales_data() {
		global $wpdb;

		$meta_keys = $this->get_order_items_meta_keys();
		$px = $wpdb->prefix;
		$SQL = sprintf("
			SELECT
				ORDERS.ID AS order_id
				,DATE(ORDERS.post_date) AS order_date
				,ORDER_META1.meta_value AS eu_vat_evidence
				,ORDER_META3.meta_value AS eu_vat_data
				,ORDER_META4.meta_value AS order_currency
				-- Debug information
				-- ,ORDER_META2.meta_value AS vat_number_validated
				,OI.order_item_id
				,OIM.meta_key AS line_item_key
				,OIM2.meta_value as product_id
				,OIM.meta_value AS line_total
				,CASE
					-- Shipping should always be considered a service
					WHEN OI.order_item_type = 'shipping' THEN 1
					-- For products, check if they have been set up to be a
					-- service. If not, use a default value
					ELSE IF(COALESCE(VARIATION_META.meta_value, PROD_META.meta_value, 'no') = 'yes', 1, 0)
				END AS is_service
				,0 AS is_triangulation
			FROM
				{$px}posts AS ORDERS
				INNER JOIN
				{$px}woocommerce_order_items AS OI ON
					(OI.order_id = ORDERS.ID)
				INNER JOIN
				{$px}woocommerce_order_itemmeta AS OIM ON
					(OIM.order_item_id = OI.order_item_id) AND
					(OIM.meta_key in ('%s'))
				-- Get product data for simple (non variable) products
				LEFT JOIN
				{$px}woocommerce_order_itemmeta AS OIM2 ON
					(OIM2.order_item_id = OI.order_item_id) AND
					(OIM2.meta_key = '_product_id')
				LEFT JOIN
				{$px}postmeta AS PROD_META ON
					(PROD_META.post_id = OIM2.meta_value) AND
					(PROD_META.meta_key = '". Definitions::FIELD_VIES_PRODUCT_IS_SERVICE . "')
				-- Get product data for variable products
				LEFT JOIN
				{$px}woocommerce_order_itemmeta AS OIM3 ON
					(OIM3.order_item_id = OI.order_item_id) AND
					(OIM3.meta_key = '_variation_id')
				LEFT JOIN
				{$px}postmeta AS VARIATION_META ON
					(VARIATION_META.post_id = OIM3.meta_value) AND
					(VARIATION_META.meta_key = '". Definitions::FIELD_VIES_PRODUCT_IS_SERVICE . "')
				-- Fetch orders meta
				INNER JOIN
				{$px}postmeta AS ORDER_META1 ON
					(ORDER_META1.post_id = ORDERS.ID) AND
					(ORDER_META1.meta_key = '_eu_vat_evidence')
				INNER JOIN
				{$px}postmeta AS ORDER_META2 ON
					(ORDER_META2.post_id = ORDERS.ID) AND
					(ORDER_META2.meta_key = '_vat_number_validated') AND
					(ORDER_META2.meta_value = '%s')
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
		Definitions::VAT_NUMBER_VALIDATION_VALID,
		implode("', '", $this->order_statuses_to_include(true)));

		// Debug
		//var_dump($SQL);
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
				,DATE(REFUNDS.post_date) AS refund_date
				,REFUNDS.post_parent AS order_id
				,ORDER_META1.meta_value AS eu_vat_evidence
				,ORDER_META3.meta_value AS eu_vat_data
				,ORDER_META4.meta_value AS order_currency
				,RI.order_item_id
				,RIM1.meta_key AS line_item_key
				,RIM1.meta_value AS product_id
				,RIM2.meta_value AS line_total
				,CASE
					-- Shipping should always be considered a service
					WHEN RI.order_item_type = 'shipping' THEN 1
					-- For products, check if they have been set up to be a
					-- service. If not, use a default value
					ELSE IF(COALESCE(VARIATION_META.meta_value, PROD_META.meta_value, 'no') = 'yes', 1, 0)
				END AS is_service
				,0 AS is_triangulation
			FROM
				{$px}posts AS REFUNDS
				JOIN
				{$px}posts AS ORDERS ON
					(ORDERS.ID = REFUNDS.post_parent)
				JOIN
				-- Order Meta
				{$px}postmeta AS ORDER_META1 ON
					(ORDER_META1.post_id = REFUNDS.post_parent) AND
					(ORDER_META1.meta_key = '_eu_vat_evidence')
				-- Only include refunds related to orders with a valid EU VAT number
				INNER JOIN
				{$px}postmeta AS ORDER_META2 ON
					(ORDER_META2.post_id = REFUNDS.post_parent) AND
					(ORDER_META2.meta_key = '_vat_number_validated') AND
					(ORDER_META2.meta_value = '%s')
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
				-- Get product data for simple (non-variable) products
				{$px}woocommerce_order_itemmeta RIM1 ON
					(RIM1.order_item_id = RI.order_item_id) AND
					(RIM1.meta_key = '_product_id')
				LEFT JOIN
				-- Determine if the product is to be considered a service for VIES purposes
				{$px}postmeta AS PROD_META ON
					(PROD_META.post_id = RIM1.meta_value) AND
					(PROD_META.meta_key = '". Definitions::FIELD_VIES_PRODUCT_IS_SERVICE . "')
				-- Get product data for variable products
				LEFT JOIN
				{$px}woocommerce_order_itemmeta AS RIM3 ON
					(RIM3.order_item_id = RI.order_item_id) AND
					(RIM3.meta_key = '_variation_id')
				LEFT JOIN
				-- Determine if the variation is to be considered a service for VIES purposes
				{$px}postmeta AS VARIATION_META ON
					(VARIATION_META.post_id = RIM3.meta_value) AND
					(VARIATION_META.meta_key = '". Definitions::FIELD_VIES_PRODUCT_IS_SERVICE . "')
				JOIN
				-- Refund items meta - Find item/shipping refund amounts
				{$px}woocommerce_order_itemmeta RIM2 ON
					(RIM2.order_item_id = RI.order_item_id) AND
					(RIM2.meta_key IN ('%s'))
			WHERE
				(REFUNDS.post_type IN ('shop_order_refund')) AND
				-- The statuses to include always refer to the original orders. Refunds
				-- are always in status 'wc-completed'
				(ORDERS.post_status IN ('%s')) AND
				(REFUNDS.post_date >= '" . date('Y-m-d', $this->start_date) . "') AND
				(REFUNDS.post_date < '" . date('Y-m-d', strtotime('+1 DAY', $this->end_date)) . "')
		",
		Definitions::VAT_NUMBER_VALIDATION_VALID,
		implode("', '", $meta_keys),
		implode("', '", $this->order_statuses_to_include(true)));

		// Debug
		//var_dump($SQL);
		$dataset = $wpdb->get_results($SQL);

		// Debug
		//var_dump("REFUNDS RESULT", $dataset);
		return $dataset;
	}
}
