<?php
namespace Aelia\WC\EU_VAT_Assistant\Reports\WC21;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Renders the INTRASTAT report.
 *
 * @since 1.4.1.150407
 */
class INTRASTAT_Report extends \Aelia\WC\EU_VAT_Assistant\Reports\Base_INTRASTAT_Report {
	/**
	 * Returns the sales data that will be included in the report.
	 *
	 * @return array
	 */
	protected function get_sales_data() {
		global $wpdb;

		$px = $wpdb->prefix;
		$SQL = sprintf("
			SELECT
				ORDERS.ID AS order_id
				,DATE(ORDERS.post_date) AS order_date
				,ORDER_META1.meta_value AS eu_vat_evidence
				,ORDER_META3.meta_value AS eu_vat_data
				,ORDER_META4.meta_value AS order_currency
				,ORDER_META2.meta_value AS order_total
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
				-- Fetch orders meta
				INNER JOIN
				{$px}postmeta AS ORDER_META1 ON
					(ORDER_META1.post_id = ORDERS.ID) AND
					(ORDER_META1.meta_key = '_eu_vat_evidence')
				INNER JOIN
				{$px}postmeta AS ORDER_META2 ON
					(ORDER_META2.post_id = ORDERS.ID) AND
					(ORDER_META2.meta_key = '_order_total')
				INNER JOIN
				{$px}postmeta AS ORDER_META3 ON
					(ORDER_META3.post_id = ORDERS.ID) AND
					(ORDER_META3.meta_key = '_eu_vat_data')
				INNER JOIN
				{$px}postmeta AS ORDER_META4 ON
					(ORDER_META4.post_id = ORDERS.ID) AND
					(ORDER_META4.meta_key = '_order_currency')
			WHERE
				(ORDERS.post_type IN ('shop_order')) AND
				(ORDERS.post_date >= '" . date('Y-m-d', $this->start_date) . "') AND
				(ORDERS.post_date < '" . date('Y-m-d', strtotime('+1 DAY', $this->end_date)) . "')
		",
		implode("', '", $this->order_statuses_to_include(false)));

		// Debug
		//var_dump($SQL);
		$dataset = $wpdb->get_results($SQL);

		// Debug
		//var_dump("SALES DATA", $dataset);die();
		return $dataset;
	}
}
