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
abstract class Base_EU_VAT_By_Country_Report extends \Aelia\WC\EU_VAT_Assistant\Reports\Base_Report {
	// @var string Indicates which tax types should be shown (MOSS, non-MOSS or all)
	protected $taxes_to_show;
	// @var string Indicates which exchange rates should be used (stored with order or ECB).
	protected $exchange_rates_to_use;
	// @var string A list of the tax classes that are not part of MOSS
	protected $non_moss_tax_classes;

	/**
	 * Indicates if the tax passed as a parameter should be skipped (i.e. excluded
	 * from the report).
	 *
	 * @param array tax_details An array of data describing a tax.
	 * @return bool True (tax should be excluded from the report) or false (tax
	 * should be displayed on the report).
	 */
	protected function should_skip(array $tax_details) {
		// If all taxes should be displayed, just return "false" (i.e. don't skip)
		if($this->taxes_to_show === Definitions::TAX_ALL) {
			return false;
		}

		// If the tax is a "non-MOSS" one, it should be skipped when the tax types
		// to display are "MOSS only"
		if($tax_details['is_moss'] == false) {
			return ($this->taxes_to_show === Definitions::TAX_MOSS_ONLY);
		}
		else {
			return ($this->taxes_to_show === Definitions::TAX_NON_MOSS_ONLY);
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
		// Keep a list of the tax classes that are not part of MOSS
		$this->non_moss_tax_classes = WC_Aelia_EU_VAT_Assistant::settings()->get(Settings::FIELD_TAX_CLASSES_EXCLUDED_FROM_MOSS, array());
	}

	/**
	 * Returns the tax data for the report.
	 *
	 * @return array The tax data.
	 */
	protected function get_tax_data() {
		global $wpdb;
		$wpdb->show_errors();

		$dataset = $this->get_order_report_data(array(
			'data' => array(
				'post_date' => array(
					'type'     => 'post_data',
					'function' => 'DATE',
					'name'     => 'order_date'
				),
				'ID' => array(
					'type'     => 'post_data',
					// The "function" element is always required, even though
					// this is not clearly indicated in WC documentation
					// @since 1.13.0.200317
					'function' => '',
					'name'     => 'order_id',
				),
				'_order_currency' => array(
					'type' => 'meta',
					'order_item_type' => '',
					'function' => '',
					'name' => '_order_currency'
				),
				'_eu_vat_data' => array(
					'type' => 'meta',
					'order_item_type' => '',
					'function' => '',
					'name' => '_eu_vat_data'
				),
			),
			'query_type' => 'get_results',
			'group_by' => '',
			'order_types' => array('shop_order'),
			'order_status' => $this->order_statuses_to_include(),
		));

		$tax_data = array();
		foreach($dataset as $data) {
			$eu_vat_data = maybe_unserialize($data->_eu_vat_data);

			// Skip orders on which no taxes were paid
			if(empty($eu_vat_data['taxes'])) {
				continue;
			}

			// Select between the the exchange rate associated with each order, or the
			// ECB rate for the quarter
			if($this->should_use_orders_exchange_rates()) {
				$vat_currency_exchange_rate = $eu_vat_data['vat_currency_exchange_rate'];
			}
			else {
				// Get exchange rates for the last day of the quarter in which the order
				// was placed
				$last_day_of_quarter = $this->get_last_day_of_quarter($data->order_date);

				$vat_currency_exchange_rate = $this->get_vat_currency_exchange_rate($data->_order_currency, $last_day_of_quarter);

				// If the exchange rate is not available, fall back to the one used with
				// the order
				if(!is_numeric($vat_currency_exchange_rate)) {
					$vat_currency_exchange_rate = $eu_vat_data['vat_currency_exchange_rate'];
				}
			}

			$taxes_recorded = get_value('taxes', $eu_vat_data, array());
			foreach($taxes_recorded as $rate_id => $tax_details) {
				// If the record doesn't contain the necessary information, log the issue
				// and skip the row.
				// @since 1.7.18.180114
				if(!isset($tax_details['country']) || !isset($tax_details['tax_rate_class'])) {
					$this->logger->notice(__('Tax record is missing country or tax rate class. Skipping.', $this->text_domain), array(
						'Rate ID' => $rate_id,
						'Tax Details' => $tax_details,
						'EU VAT Data record' => $eu_vat_data,
					));
					continue;
				}

				// Tag the tax record to indicate if it's part of MOSS or not
				$tax_details['is_moss'] = $this->is_tax_moss(@$tax_details['country'],
																										 @$tax_details['tax_rate_class']);

				// Skip taxes that should not appear on the report
				if($this->should_skip($tax_details)) {
					continue;
				}

				if(!isset($tax_data[$rate_id])) {
					$tax_data[$rate_id] = (object)array(
						'items_tax_amount' => 0,
						'shipping_tax_amount' => 0,
						'refunded_items_tax_amount' => 0,
						'refunded_shipping_tax_amount' => 0,
						// Sales information
						'items_total' => 0,
						'shipping_total' => 0,
						'refunded_items_total' => 0,
						'refunded_shipping_total' => 0,

						'tax_rate_data' => (object)array(
							'label' => $tax_details['label'],
							'tax_rate' => $tax_details['vat_rate'],
							'tax_rate_country' => $tax_details['country'],
							'tax_rate_class' => $tax_details['tax_rate_class'],
							'is_moss' => $tax_details['is_moss'],
						),
					);
				}

				$tax_amounts = $tax_details['amounts'];
				$tax_data[$rate_id]->items_tax_amount += wc_round_tax_total($tax_amounts['items_total']  * $vat_currency_exchange_rate);
				$tax_data[$rate_id]->shipping_tax_amount += wc_round_tax_total($tax_amounts['shipping_total']  * $vat_currency_exchange_rate);

				$items_tax_refund = get_value('items_refund', $tax_amounts, 0);
				$shipping_tax_refund = get_value('shipping_refund', $tax_amounts, 0);
				$tax_data[$rate_id]->refunded_items_tax_amount += wc_round_tax_total($items_tax_refund * $vat_currency_exchange_rate);
				$tax_data[$rate_id]->refunded_shipping_tax_amount += wc_round_tax_total($shipping_tax_refund * $vat_currency_exchange_rate);

				// Sales totals. The sales totals will be rounded once, at the end of
				// the calculations
				$vat_rate = $tax_details['vat_rate'] / 100;
				if($vat_rate != 0) {
					$tax_data[$rate_id]->items_total += $tax_amounts['items_total'] / $vat_rate * $vat_currency_exchange_rate;
					$tax_data[$rate_id]->shipping_total += $tax_amounts['shipping_total'] / $vat_rate * $vat_currency_exchange_rate;
					$tax_data[$rate_id]->refunded_items_total += $items_tax_refund / $vat_rate* $vat_currency_exchange_rate;
					$tax_data[$rate_id]->refunded_shipping_total += $shipping_tax_refund / $vat_rate * $vat_currency_exchange_rate;
				}
			}
		}

		// Round totals, for readability. VAT totals have already been rounded at
		// this stage
		$decimals = wc_get_price_decimals();
		foreach($tax_data as $rate_id => $tax_details) {
			$tax_data[$rate_id]->items_total = round($tax_data[$rate_id]->items_total, $decimals);
			$tax_data[$rate_id]->shipping_total = round($tax_data[$rate_id]->shipping_total, $decimals);
			$tax_data[$rate_id]->refunded_items_total = round($tax_data[$rate_id]->refunded_items_total, $decimals);
			$tax_data[$rate_id]->refunded_shipping_total = round($tax_data[$rate_id]->refunded_shipping_total, $decimals);
		}

		/* NOTES
		 * - Tax rate can be calculated by dividing the line_tax by the tax_total
		 * - Sale totals must be calculated in a second pass. If we add line_total and tax_total to
		 *   the query, it will return one line per order product. However, each line will also contain
		 *   ALL the taxes for the entire order, which would be processed multiple times.
		 */
		return $tax_data;
	}

	/**
	 * Retrieves the refunds for the specified period and adds them to the tax
	 * data.
	 *
	 * @param array tax_data The tax data to which refund details should be added.
	 * @return array The tax data including the refunds applied in the specified
	 * period.
	 */
	protected function get_tax_refunds_data($tax_data) {
		// This method must be implemented by descendant classes
		return $tax_data;
	}

	/**
	 * Reorganises the tax data, grouping and sorting it by country.
	 *
	 * @param array tax_data The tax data.
	 */
	protected function sort_taxes($tax_data) {
		$result = array(
			// MOSS taxes
			'moss' => array(),
			// Non-MOSS taxes
			'non-moss' => array(),
		);
		foreach($tax_data as $rate_id => $data) {
			$target_group = &$result[$data->tax_rate_data->is_moss ? 'moss' : 'non-moss'];

			/* The tax should be paid to the country specified in "tax payable to
			 * country" field. If that field is empty, then take the tax country instead
			 */
			$country_code = get_value('tax_payable_to_country', $data->tax_rate_data, $data->tax_rate_data->tax_rate_country);
			if(empty($target_group[$country_code])) {
				$target_group[$country_code] = array();
			}
			$target_group[$country_code][$rate_id] = $data;
		}
		foreach($result as $moss_group => &$taxes) {
			ksort($taxes);
		}

		return $result;
	}

	/**
	 * Get the data for the report.
	 *
	 * @return string
	 */
	public function get_main_chart() {
		$tax_data = $this->get_tax_data();
		// Add the refunds to the tax data
		$tax_data = $this->get_tax_refunds_data($tax_data);


		// Debug
		//var_dump($tax_data);

		// Reorganise VAT information, associating it to each country and sorting it
		// by country code
		$taxes_by_country = $this->sort_taxes($tax_data);

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

							$row_index = 0;
							// Second loop - Taxes by country
							foreach($group_taxes as $country_code => $tax_data) {
								// Third loop taxes for a specific country
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
										<th class="country_name" scope="row"><?php
											if(isset(WC()->countries->countries[$rate->tax_rate_country])) {
												esc_html(WC()->countries->countries[$rate->tax_rate_country]);
											}
											else {
												// Log the condition in which an invalid country is found. This should not happen,
												// it's important to keep track of such
												// @since 1.13.0.200317
												$this->logger->warning(__('Tax rate contains an in invalid country code', $this->text_domain), array(
													'Country Code' => $rate->tax_rate_country,
													'Rate ID' => $rate_id,
													'Tax Row' => $tax_row,
													'Tax Rate' => $rate,
												));
												echo esc_html(__('Invalid country, check error log.', $this->text_domain));
											}
										?></th>
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
																							 + $tax_row->refunded_items_total
																							 + $tax_row->refunded_shipping_total);
										?></td>
										<td class="total_row column_group right"><?php
											echo $this->format_price($tax_row->items_tax_amount
																							 + $tax_row->shipping_tax_amount
																							 + $tax_row->refunded_items_tax_amount
																							 + $tax_row->refunded_shipping_tax_amount);
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
																				 + $tax_grand_totals['refunded_items_total']
																				 + $tax_grand_totals['refunded_shipping_total']);
							?></td>
							<td class="total_row column_group right"><?php
								echo $this->format_price($tax_grand_totals['items_tax_amount']
																				 + $tax_grand_totals['shipping_tax_amount']
																				 + $tax_grand_totals['refunded_items_tax_amount']
																				 + $tax_grand_totals['refunded_shipping_tax_amount']);
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
		include(WC_Aelia_EU_VAT_Assistant::instance()->path('views') . '/admin/reports/eu-vat-report-header.php');
	}
}
