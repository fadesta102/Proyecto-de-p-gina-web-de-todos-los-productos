<?php
namespace Aelia\WC\EU_VAT_Assistant\Reports\WC21;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use Aelia\WC\EU_VAT_Assistant\Definitions;

/**
 * Renders the VIES Report.
 *
 * @since 1.4.1.150407
 */
class VIES_Report extends \Aelia\WC\EU_VAT_Assistant\Reports\Base_VIES_Report {
	/**
	 * Returns the sales data that will be included in the report.
	 *
	 * @return array
 * @since 1.4.1.150407
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
					{$px}term_relationships AS rel ON
						(rel.object_ID = orders.ID)
				INNER JOIN
					{$px}term_taxonomy AS taxonomy ON
						(taxonomy.term_taxonomy_id = rel.term_taxonomy_id) AND
						(taxonomy.taxonomy = 'shop_order_status')
				INNER JOIN
					{$px}terms AS term ON
						(term.term_id = taxonomy.term_id) AND
						(term.slug  IN ('%s'))
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
				(ORDERS.post_status = 'publish') AND
				(ORDERS.post_date >= '" . date('Y-m-d', $this->start_date) . "') AND
				(ORDERS.post_date < '" . date('Y-m-d', strtotime('+1 DAY', $this->end_date)) . "')
		",
		implode("', '", $this->order_statuses_to_include(false)),
		implode("', '", $meta_keys),
		Definitions::VAT_NUMBER_VALIDATION_VALID
		);

		// Debug
		//var_dump($SQL);
		$dataset = $wpdb->get_results($SQL);

		// Debug
		//var_dump("SALES DATA", $dataset);die();
		return $dataset;
	}
}
