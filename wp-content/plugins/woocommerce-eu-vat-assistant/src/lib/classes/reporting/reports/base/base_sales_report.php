<?php
namespace Aelia\WC\EU_VAT_Assistant\Reports;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use Aelia\WC\EU_VAT_Assistant\WC_Aelia_EU_VAT_Assistant;
use Aelia\WC\EU_VAT_Assistant\Settings;
use Aelia\WC\EU_VAT_Assistant\Definitions;

/**
 * Renders the report containing the EU VAT for each country in a specific
 * period.
 */
abstract class Base_Sales_Report extends \Aelia\WC\EU_VAT_Assistant\Reports\Base_Report {
	// @var string Indicates which salkes region should be inclued (EU, non-EU, all)
	protected $sales_region_visible;
	// @var string Indicates which exchange rates should be used (stored with order or ECB).
	protected $exchange_rates_to_use;

	/**
	 * Indicates if the tax passed as a parameter should be skipped (i.e. excluded
	 * from the report).
	 *
	 * @param array tax_details An array of data describing a tax.
	 * @return bool True (tax should be excluded from the report) or false (tax
	 * should be displayed on the report).
	 */
	protected function should_skip($sale_data) {
		return false;

		// If all taxes should be displayed, just return "false" (i.e. don't skip)
		if(($this->sales_region_visible === Definitions::ALL) &&
			 ($this->sales_with_vat_visible === Definitions::ALL)) {
			return false;
		}

		$eu_vat_evidence = maybe_unserialize($data->_eu_vat_evidence);

		// If the tax is a "non-EU" one, it should be skipped when the sales region
		// to display are "EU only"
		if($eu_vat_evidence['location']['is_eu_country'] == false) {
			return ($this->sales_region_visible === Definitions::SALES_EU_ONLY);
		}
		else {
			return ($this->sales_region_visible === Definitions::SALES_NON_EU_ONLY);
		}
	}

	/**
	 * Indicates if reports should be rendered using the exchange rates associated
	 * with the orders.
	 *
	 * @return bool
	 */
	protected function should_use_orders_exchange_rates() {
		return ($this->exchange_rates_to_use === Definitions::FX_SAVED_WITH_ORDER);
	}

	public function __construct() {
		parent::__construct();

		// Store which tax types should be shown
		$this->taxes_to_show = get_value(Definitions::ARG_TAX_TYPE, $_REQUEST, Definitions::TAX_MOSS_ONLY);
		// Store which exchange rates should be used
		$this->exchange_rates_to_use = get_value(Definitions::ARG_EXCHANGE_RATES_TYPE, $_REQUEST, Definitions::FX_SAVED_WITH_ORDER);
	}

	/**
	 * Returns the meta keys of the order items that should be loaded by the report.
	 * For this report, line totals and cost indicate the price of products and
	 * the price of shipping, respectively.
	 *
	 * @return array
	 */
	protected function get_order_items_meta_keys() {
		return array(
			// _line_total: total charged for order items
			'_line_total',
			// cost: total charged for shipping
			'cost',
		);
	}

	protected function get_order_items_meta() {
		global $wpdb;

		$meta_keys = $this->get_order_items_meta_keys();

		$px = $wpdb->prefix;
		$SQL = sprintf("
			SELECT
				ORDERS.ID AS order_id
				,OI.order_item_id
				,OIM.meta_key
				,OIM.meta_value
			FROM
				{$px}posts AS ORDERS
				INNER JOIN
				{$px}woocommerce_order_items AS OI ON
					(OI.order_id = ORDERS.ID)
				INNER JOIN
				{$px}woocommerce_order_itemmeta AS OIM ON
					(OIM.order_item_id = OI.order_item_id) AND
					(OIM.meta_key in ('%s'))
			WHERE
				(ORDERS.post_type IN ('shop_order')) AND
				(ORDERS.post_status IN ('wc-processing', 'wc-completed')) AND
				(ORDERS.post_date >= '" . date('Y-m-d', $this->start_date) . "') AND
				(ORDERS.post_date < '" . date('Y-m-d', strtotime('+1 DAY', $this->end_date)) . "')
		", implode("', '", $meta_keys));

		// Debug
		if($this->debug) {
			var_dump($SQL);
		}
		$dataset = $wpdb->get_results($SQL);

		// Debug
		//var_dump("ORDER ITEMS META", $dataset);
		return $dataset;
	}

	protected function get_orders_meta_keys() {
		return array(
			'_billing_country',
			'_billing_first_name',
			'_billing_last_name',
			'_billing_company',
			'_billing_address_1',
			'_billing_address_2',
			'_billing_city',
			'_billing_state',
			'_billing_postcode',
			'_billing_email',
			'_billing_phone',
			'vat_number',
			'_eu_vat_data',
			'_eu_vat_evidence',
		);
	}

	protected function get_orders_meta() {
		global $wpdb;

		$meta_keys = $this->get_orders_meta_keys();

		$px = $wpdb->prefix;
		$SQL = sprintf("
			SELECT
				ORDERS.ID AS order_id
				,DATE(ORDERS.post_date) AS order_date
				,OM.meta_key
				,OM.meta_value
			FROM
				{$px}posts AS ORDERS
				LEFT JOIN
				{$px}postmeta AS OM ON
					(OM.post_id = ORDERS.ID) AND
					(OM.meta_key IN ('%s'))
			WHERE
				(ORDERS.post_type IN ('shop_order')) AND
				(ORDERS.post_status IN ('%s')) AND
				(ORDERS.post_date >= '" . date('Y-m-d', $this->start_date) . "') AND
				(ORDERS.post_date < '" . date('Y-m-d', strtotime('+1 DAY', $this->end_date)) . "')
		",
		implode("', '", $meta_keys),
		implode("', '", $this->order_statuses_to_include(true)));

		// Debug
		if($this->debug) {
			var_dump($SQL);
		}
		$dataset = $wpdb->get_results($SQL);

		// Debug
		//var_dump("ORDERS META", $dataset);
		return $dataset;
	}

	protected function consolidate_items_meta(&$order_items_meta) {
		$result = array();

		foreach($order_items_meta as $entry) {
			/* We add an underscore to force the order id to be a string. This is
			 * needed because we will have to merge the result of this method in
			 * the final processing step. If the key stays numeric, the merge will
			 * append the various arrays, rather than merging them.
			 * @link http://php.net/manual/en/function.array-merge-recursive.php
			 */
			$order_id = '_' . $entry->order_id;

			if(!isset($result[$order_id])) {
				$result[$order_id] = array(
					'order_items' => array(),
				);
			}

			$order_item_id = $entry->order_item_id;
			if(!isset($result[$order_id]['order_items'][$order_item_id])) {
				$result[$order_id]['order_items'][$order_item_id] = array();
			}

			$result[$order_id]['order_items'][$order_item_id][$entry->meta_key] = maybe_unserialize($entry->meta_value);
		}
		return $result;
	}

	protected function consolidate_orders_meta(&$orders_meta) {
		$result = array();

		foreach($orders_meta as $entry) {
			/* We add an underscore to force the order id to be a string. This is
			 * needed because we will have to merge the result of this method in
			 * the final processing step. If the key stays numeric, the merge will
			 * append the various arrays, rather than merging them.
			 * @link http://php.net/manual/en/function.array-merge-recursive.php
			 */
			$order_id = '_' . $entry->order_id;

			if(!isset($result[$order_id])) {
				$result[(string)$order_id] = array(
					'order_meta' => array(),
				);
			}
			$result[$order_id]['order_meta'][$entry->meta_key] = maybe_unserialize($entry->meta_value);
		}
		return $result;
	}

	protected function consolidate_sales_data(&$order_items_meta, &$orders_meta) {
		$result = array_merge_recursive(
			$this->consolidate_items_meta($order_items_meta),
			$this->consolidate_orders_meta($orders_meta)
		);
		return $result;
	}

	/**
	 * Returns the tax data for the report.
	 *
	 * @return array The tax data.
	 */
	protected function get_sales_data() {
		global $wpdb;
		$wpdb->show_errors();

		//$startMemory = memory_get_usage();
		$order_items_meta = $this->get_order_items_meta();
		$orders_meta = $this->get_orders_meta();

		$sales_data = $this->consolidate_sales_data($order_items_meta, $orders_meta);
		//var_dump("MEMORY: " . (string)((memory_get_usage() - $startMemory) / 1024) . ' kB');

		foreach($sales_data as $order_id => $order_data) {
			if($this->should_skip($order_data)) {
				unset($sales_data[$order_id]);
			}
		}

		// Debug
		//var_dump($sales_data);

		return $sales_data;

		//$sales_data = array();
		//foreach($dataset as $data) {
		//	$data->_eu_vat_data = maybe_unserialize($data->_eu_vat_data);
		//
		//	// Take the order number from the Sequential Order Number plugin, if
		//	// available. If such information is not available, use the order id.
		//	if(empty($data->order_number)) {
		//		$data->order_number = $data->post_id;
		//	}
		//
		//	// Skip rows that should not appear on the report
		//	if($this->should_skip($data)) {
		//		continue;
		//	}
		//
		//	// Select between the the exchange rate associated with each order, or the
		//	// ECB rate for the quarter
		//	if(!$this->should_use_orders_exchange_rates()) {
		//		// Get exchange rates for the last day of the quarter in which the order
		//		// was placed
		//		$last_day_of_quarter = $this->get_last_day_of_quarter($data->order_date);
		//
		//		$vat_currency_exchange_rate = $this->get_vat_currency_exchange_rate($data->order_currency, $last_day_of_quarter);
		//
		//		// If the exchange rate is not available, fall back to the one used with
		//		// the order
		//		if(is_numeric($vat_currency_exchange_rate)) {
		//			$data->_eu_vat_data['vat_currency_exchange_rate'] = $vat_currency_exchange_rate;
		//		}
		//	}
		//
		//	$taxes_recorded = get_value('taxes', $data->_eu_vat_data, array());
		//	foreach($taxes_recorded as $rate_id => $tax_details) {
		//		// Tag the tax record to indicate if it's part of MOSS or not
		//		$tax_details['is_moss'] = ($tax_details['country'] !== $this->base_country()) &&
		//															(!in_array($tax_details['tax_rate_class'], $this->non_moss_tax_classes));
		//
		//		$data->_eu_vat_data['taxes'][$rate_id] = $tax_details;
		//	}
		//}
		//
		///* NOTES
		// * - Tax rate can be calculated by dividing the line_tax by the tax_total
		// * - Sale totals must be calculated in a second pass. If we add line_total and tax_total to
		// *   the query, it will return one line per order product. However, each line will also contain
		// *   ALL the taxes for the entire order, which would be processed multiple times.
		// */
		//return $tax_data;
	}

	/**
	 * Retrieves the refunds for the specified period and adds them to the tax
	 * data.
	 *
	 * @param array tax_data The tax data to which refund details should be added.
	 * @return array The tax data including the refunds applied in the specified
	 * period.
	 */
	protected function get_refunds_data() {
		// This method must be implemented by descendant classes
		return array();
	}

	/**
	 * Get the data for the report.
	 *
	 * @return string
	 */
	public function get_main_chart() {
		$sales_data = $this->get_sales_data();
		// Add the refunds to the sales data
		$refunds_data = $this->get_refunds_data();

		// Store the list of EU countries. It will be used to remove non-EU tax from
		// the report
		$eu_countries = WC_Aelia_EU_VAT_Assistant::instance()->get_eu_vat_countries();
		// Keep track of the report columns. This information will be used to adjust
		// the "colspan" property
		$report_columns = 13;
		?>
		<div id="eu_vat_report" class="wc_aelia_eu_vat_assistant report">
			<table class="widefat">
				<thead>
					<tr class="report_information">
						<th colspan="<?php echo $report_columns; ?>">
							<ul>
								<li>
									<span class="label"><?php
										echo __('Currency for VAT returns:', $this->text_domain);
									?></span>
									<span><?php echo $this->vat_currency(); ?></span>
								</li>
								<li>
									<span class="label"><?php
										echo __('Exchange rates used:', $this->text_domain);
									?></span>
									<span><?php
										if($this->should_use_orders_exchange_rates()) {
											echo __('Rates saved with each order', $this->text_domain);
										}
										else {
											echo __('ECB rates for each quarter', $this->text_domain);
										}
									?></span>
								</li>
							</ul>
						</th>
					</tr>
					<tr>
						<th colspan="3" class="column_group left"></th>
						<th colspan="4" class="column_group header"><?php echo __('Items', $this->text_domain); ?></th>
						<th colspan="4" class="column_group header"><?php echo __('Shipping', $this->text_domain); ?></th>
						<th colspan="2" class="column_group right">&nbsp;</th>
					</tr>
					<tr class="column_headers">
						<th class="country_name"><?php echo __('Customer Country', $this->text_domain); ?></th>
						<th class="country_code"><?php echo __('Country Code', $this->text_domain); ?></th>
						<th class="tax_rate"><?php echo __('Tax Rate', $this->text_domain); ?></th>

						<!-- Items -->
						<th class="total_row column_group left"><?php echo __('Sales', $this->text_domain); ?></th>
						<th class="total_row column_group "><?php echo __('Refunds', $this->text_domain); ?></th>
						<th class="total_row column_group "><?php echo __('VAT Charged', $this->text_domain); ?></th>
						<th class="total_row column_group right"><?php echo __('VAT Refunded', $this->text_domain); ?></th>

						<!-- Shipping -->
						<th class="total_row column_group left"><?php echo __('Shipping charged', $this->text_domain); ?></th>
						<th class="total_row column_group "><?php echo __('Shipping refunded', $this->text_domain); ?></th>
						<th class="total_row column_group "><?php echo __('VAT Charged', $this->text_domain); ?></th>
						<th class="total_row column_group right"><?php echo __('VAT Refunded', $this->text_domain); ?></th>

						<!-- Totals -->
						<th class="total_row column_group left"><?php echo __('Total charged', $this->text_domain); ?></th>
						<th class="total_row column_group right"><?php echo __('Final VAT Total', $this->text_domain); ?></th>
					</tr>
				</thead>
				<?php if(empty($taxes_by_country)) : ?>
					<tbody>
						<tr>
							<td colspan="<?php echo $report_columns; ?>"><?php echo __('No VAT has been processed in this period', $this->text_domain); ?></td>
						</tr>
					</tbody>
				<?php else : ?>
					<tbody>
						<?php
						$tax_grand_totals = array(
							'items_total' => 0,
							'refunded_items_total' => 0,
							'shipping_total' => 0,
							'refunded_shipping_total' => 0,

							'items_tax_amount' => 0,
							'refunded_items_tax_amount' => 0,
							'shipping_tax_amount' => 0,
							'refunded_shipping_tax_amount' => 0,
						);


						// First loop - Tax groups (MOSS and non-MOSS)
						foreach($taxes_by_country as $moss_group => $group_taxes) {
							if(empty($group_taxes)) {
								continue;
							}
							// Render a sub-header to make it easier to read the report
							$this->render_group_header($moss_group, $report_columns);

							// Second loop - Taxes by country
							foreach($group_taxes as $country_code => $tax_data) {
								// Third loop taxes for a specific country
								$row_index = 0;
								foreach($tax_data as $rate_id => $tax_row) {
									$rate = $tax_row->tax_rate_data;
									if(!empty($tax_row->tax_rate_country) && !in_array($rate->tax_rate_country, $eu_countries)) {
										continue;
									}

									$tax_payable_to_country = get_value('tax_payable_to_country', $rate, $rate->tax_rate_country);
									// Add CSS to allow highlighting odd and even numbers, for readability
									// @since 1.7.18.180114
									$row_class = ($row_index % 2) ? 'odd' : 'even';
									?>
									<tr class="<?php echo $row_class; ?>">
										<th class="country_name" scope="row"><?php echo esc_html(WC()->countries->countries[$rate->tax_rate_country]); ?></th>
										<th class="country_code" scope="row"><?php echo esc_html($rate->tax_rate_country); ?></th>
										<td class="tax_rate"><?php echo number_format(apply_filters('woocommerce_reports_taxes_rate', $rate->tax_rate, $rate_id, $tax_row), 2); ?>%</td>

										<!-- Items -->
										<td class="total_row column_group left"><?php echo $this->format_price($tax_row->items_total); ?></td>
										<td class="total_row column_group "><?php echo $this->format_price($tax_row->refunded_items_total); ?></td>
										<td class="total_row column_group "><?php echo $this->format_price($tax_row->items_tax_amount); ?></td>
										<td class="total_row column_group right"><?php echo $this->format_price($tax_row->refunded_items_tax_amount * -1); ?></td>

										<!-- Shipping -->
										<td class="total_row column_group left"><?php echo $this->format_price($tax_row->shipping_total); ?></td>
										<td class="total_row column_group "><?php echo $this->format_price($tax_row->refunded_shipping_total); ?></td>
										<td class="total_row column_group "><?php echo $this->format_price($tax_row->shipping_tax_amount); ?></td>
										<td class="total_row column_group right"><?php echo $this->format_price($tax_row->refunded_shipping_tax_amount * -1); ?></td>

										<!-- Total -->
										<td class="total_row column_group left"><?php
											echo $this->format_price($tax_row->items_total
																							 + $tax_row->shipping_total
																							 - $tax_row->refunded_items_total
																							 - $tax_row->refunded_shipping_total);
										?></td>
										<td class="total_row column_group right"><?php
											echo $this->format_price($tax_row->items_tax_amount
																							 + $tax_row->shipping_tax_amount
																							 - $tax_row->refunded_items_tax_amount
																							 - $tax_row->refunded_shipping_tax_amount);
										?></td>
									</tr>
									<?php
									$row_index++;

									// Calculate grand totals
									$tax_grand_totals['items_tax_amount'] += $tax_row->items_tax_amount;
									$tax_grand_totals['refunded_items_tax_amount'] += $tax_row->refunded_items_tax_amount;
									$tax_grand_totals['shipping_tax_amount'] += $tax_row->shipping_tax_amount;
									$tax_grand_totals['refunded_shipping_tax_amount'] += $tax_row->refunded_shipping_tax_amount;

									// Sales data
									$tax_grand_totals['items_total'] += $tax_row->items_total;
									$tax_grand_totals['refunded_items_total'] += $tax_row->refunded_items_total;
									$tax_grand_totals['shipping_total'] += $tax_row->shipping_total;
									$tax_grand_totals['refunded_shipping_total'] += $tax_row->refunded_shipping_total;
								} // Third loop - END
							} // Second loop - END
						} // First loop - END
						?>
					</tbody>
					<!--- VAT Totals --->
					<tfoot id="vat-grand-totals">
						<tr>
							<th class="label" colspan="3"><?php echo __('Totals', $this->text_domain); ?></th>
							<!-- Items -->
							<td class="total_row column_group left"><?php echo $this->format_price($tax_grand_totals['items_total']); ?></td>
							<td class="total_row column_group "><?php echo $this->format_price($tax_grand_totals['refunded_items_total']); ?></td>
							<td class="total_row column_group "><?php echo $this->format_price($tax_grand_totals['items_tax_amount']); ?></td>
							<td class="total_row column_group right"><?php echo $this->format_price($tax_grand_totals['refunded_items_tax_amount'] * -1); ?></td>

							<!-- Shipping -->
							<td class="total_row column_group left"><?php echo $this->format_price($tax_grand_totals['shipping_total']); ?></td>
							<td class="total_row column_group "><?php echo $this->format_price($tax_grand_totals['refunded_shipping_total']); ?></td>
							<td class="total_row column_group "><?php echo $this->format_price($tax_grand_totals['shipping_tax_amount']); ?></td>
							<td class="total_row column_group right"><?php echo $this->format_price($tax_grand_totals['refunded_shipping_tax_amount'] * -1); ?></td>

							<!-- Totals -->
							<td class="total_row column_group left"><?php
								echo $this->format_price($tax_grand_totals['items_total']
																				 + $tax_grand_totals['shipping_total']
																				 - $tax_grand_totals['refunded_items_total']
																				 - $tax_grand_totals['refunded_shipping_total']);
							?></td>
							<td class="total_row column_group right"><?php
								echo $this->format_price($tax_grand_totals['items_tax_amount']
																				 + $tax_grand_totals['shipping_tax_amount']
																				 - $tax_grand_totals['refunded_items_tax_amount']
																				 - $tax_grand_totals['refunded_shipping_tax_amount']);
							?></td>
						</tr>
					</tfoot>
				<?php endif; ?>
			</table>
		</div>
		<?php
	}

	protected function render_group_header($moss_group, $report_columns) {
		$group_header_content = array(
			'moss' => array(
				'title' => __('MOSS VAT Details', $this->text_domain),
				'description' => __('This section shows the data to be used to file the VAT MOSS return.', $this->text_domain),
			),
			'non-moss' => array(
				'title' => __('Domestic/non-MOSS VAT Details', $this->text_domain),
				'description' => __('This section shows the data for the domestic VAT return.', $this->text_domain),
			),
		);

		$content = get_value($moss_group, $group_header_content);
		if(empty($content)) {
			return;
		}
		?>
		<tr class="group_header">
			<th class="" colspan="<?php echo $report_columns; ?>">
				<div class="title"><?php
					echo $content['title'];
				?></div>
				<div class="description"><?php
					echo $content['description'];
				?></div>
			</th>
		</tr>
		<?php
	}

	/**
	 * Renders a header on top of the standard reporting UI.
	 */
	protected function render_ui_header() {
		include(WC_Aelia_EU_VAT_Assistant::instance()->path('views') . '/admin/reports/sales-report-header.php');
	}
}
