<?php
namespace Aelia\WC\EU_VAT_Assistant\Reports;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use Aelia\WC\EU_VAT_Assistant\WC_Aelia_EU_VAT_Assistant;
use Aelia\WC\EU_VAT_Assistant\Settings;
use Aelia\WC\EU_VAT_Assistant\Definitions;

/**
 * Base class for the sales summary report.
 *
 * @since 1.5.8.160112
 */
abstract class Base_Sales_Summary_Report extends \Aelia\WC\EU_VAT_Assistant\Reports\Base_Sales_Report {
	const SALES_SUMMARY_REPORT_TEMP_TABLE = 'aelia_euva_sales_summary_report';

	/**
	 * Indicates if the tax passed as a parameter should be skipped (i.e. excluded
	 * from the report).
	 *
	 * @param array tax_details An array of data describing a tax.
	 * @return bool True (tax should be excluded from the report) or false (tax
	 * should be displayed on the report).
	 */
	protected function should_skip($order_data) {
		return false;
	}

	/**
	 * Creates the temporary table that will be used to generate the VIES report.
	 *
	 * @return string|bool The name of the created table, or false on failure.
	 * @since 1.3.20.150330
	 */
	protected function create_temp_report_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . self::SALES_SUMMARY_REPORT_TEMP_TABLE;
		$sql = "
			CREATE TEMPORARY TABLE IF NOT EXISTS `$table_name` (
				`row_id` INT NOT NULL AUTO_INCREMENT,
				`order_id` INT NOT NULL,
				`post_type` VARCHAR(50) NOT NULL,
				`is_eu_country` VARCHAR(10) NOT NULL,
				`billing_country` VARCHAR(10) NOT NULL,
				`order_item_id` INT NOT NULL,
				`line_total` DECIMAL(18,6) NOT NULL,
				`line_tax` DECIMAL(18,6) NOT NULL,
				`tax_rate` DECIMAL(18,2) NOT NULL,
				`exchange_rate` DECIMAL(18,6) NOT NULL,
				PRIMARY KEY (`row_id`)
			) {$charset_collate};
		";

		return $this->create_temporary_table($table_name, $sql);
	}

	/**
	 * Stores a row in the temporary table used to produce the VIES report.
	 *
	 * @since 1.3.20.150330
	 */
	protected function store_temp_data(array $fields) {
		global $wpdb;

		// Debug
		//var_dump("STORING TEMP. TAX DATA", $fields);

		$table_name = $wpdb->prefix . self::SALES_SUMMARY_REPORT_TEMP_TABLE;
		$SQL = "
			INSERT INTO `$table_name` (
				`order_id`,
				`post_type`,
				`is_eu_country`,
				`billing_country`,
				`order_item_id`,
				`line_total`,
				`line_tax`,
				`tax_rate`,
				`exchange_rate`
			)
			VALUES (
				%d, -- Order ID
				%s, -- Post type (for debugging purposes)
				%s, -- Is EU country (flag)
				%s, -- Billing country
				%d, -- Order item ID
				%f, -- Line total
				%f, -- Line tax
				%f, -- Tax rate
				%f -- Exchange rate
			)
		";

		$query = $wpdb->prepare(
			$SQL,
			$fields['order_id'],
			$fields['post_type'],
			$fields['is_eu_country'],
			$fields['billing_country'],
			$fields['order_item_id'],
			$fields['line_total'],
			$fields['line_tax'],
			$fields['tax_rate'],
			$fields['exchange_rate']
		);

		// Debug
		//var_dump($SQL, $fields, $query);die();

		// Save to database the IP data for the country
		$rows_affected = $wpdb->query($query);

		$result = $rows_affected;
		if($result == false) {
			$this->logger->warning(__('Could not store row in temporary table.', $this->text_domain), array(
				'Table Name' => $table_name,
				'Fields' => $fields,
				'WPDB Error' => $wpdb->last_error,
			));
		}
		return $result;
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

	/**
	 * Returns the sales data that will be included in the report. This method must
	 * be implemented by descendant classes.
	 *
	 * @return array
	 * @since 1.10.1.191108
	 */
	protected function prepare_sales_data() {
		return array();
	}

	/**
	 * Returns the refunds data that will be included in the report. This method
	 * is empty for compatibility with WooCommerce 2.1, which doesn't handle
	 * refunds. Classes designed for WooCommerce 2.2 and later will take care of
	 * fetching the refunds.
	 *
	 * @return array
	 * @since 1.10.1.191108
	 */
	protected function prepare_refunds_data() {
		return array();
	}

	/**
	 * Consolidates the sales data with the refunds data and returns it.
	 *
	 * @return array An array containing the consolidated sales and return data.
	 * @since 1.3.20.150330
	 */
	protected function get_sales_summary_report_data() {
		global $wpdb;

		$px = $wpdb->prefix;
		$SQL = "
			SELECT
				SSR.is_eu_country
				,SSR.billing_country
				,SSR.tax_rate
				,SUM(SSR.line_total) AS sales_total
				,SUM(SSR.line_tax) AS tax_total
			FROM
				{$px}" . self::SALES_SUMMARY_REPORT_TEMP_TABLE . " SSR
			GROUP BY
				SSR.is_eu_country
				,SSR.billing_country
				,SSR.tax_rate
			HAVING
				-- Discard rows with zero, they don't need to be added to the report.
				-- We can't just use 'greater than zero' as a criteria, because rows
				-- with negative values must be included
				(sales_total <> 0)
			ORDER BY
				SSR.is_eu_country
				,SSR.tax_rate
				,SSR.billing_country
		";

		// Debug
		if($this->debug) {
			var_dump("SALES SUMMARY REPORT DATA QUERY", $SQL);
		}
		$dataset = $wpdb->get_results($SQL);

		// Debug
		//var_dump("REFUNDS RESULT", $dataset);
		return $dataset;
	}

	/**
	 * Loads and returns the report data.
	 *
	 * @return array An array with the report data.
	 * @since 1.3.20.150402
	 */
	protected function get_report_data() {
		if($result = $this->create_temp_report_table()) {
			// Retrieve and store sales data
			$result = $this->prepare_sales_data();

			// Retrieve and store refunds data
			if($result) {
				$result = $this->prepare_refunds_data();
			}

			if($result) {
				// Prepare a summary for the VAT RTD report and return it
				$result = $this->get_sales_summary_report_data();
			}
			return $result;
		}

		if(!$result) {
			$this->logger->warning(__('Could not prepare temporary table for the report. ' .
														 'Please enable debug mode and tru again. If the issue ' .
														 'persists, contact support and forward them the debug ' .
														 'log produced by the plugin. For more information, please ' .
														 'go to WooCommerce > EU VAT Assistant > Support.',
														  $this->text_domain));
		}
	}

	/**
	 * Get the data for the report.
	 *
	 * @return string
	 */
	public function get_main_chart() {
		$sales_summary_report_data = $this->get_report_data();

		// Keep track of the report columns. This information will be used to adjust
		// the "colspan" property
		$report_columns = 6;
		$debug_columns_class = $this->debug ? '' : ' hidden ';
		?>
		<div id="sales_summary_report" class="wc_aelia_eu_vat_assistant report">
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
					<tr class="column_headers">
						<th class="is_eu <?php echo $debug_columns_class; ?>"><?php echo __('EU', $this->text_domain); ?></th>
						<th class="billing_country"><?php echo __('Customer country', $this->text_domain); ?></th>
						<th class="tax_rate total_row column_group left right"><?php echo __('Tax rate', $this->text_domain); ?></th>
						<th class="total_sales total_row "><?php echo __('Total Sales (ex. tax)', $this->text_domain); ?></th>
						<th class="total_tax total_row "><?php echo __('Total Tax', $this->text_domain); ?></th>
						<th class="total_tax total_row inc_tax column_group left"><?php echo __('Total Sales (inc. tax)', $this->text_domain); ?></th>
					</tr>
				</thead>
				<?php if(empty($sales_summary_report_data)) : ?>
					<tbody>
						<tr>
							<td colspan="<?php echo $report_columns; ?>"><?php echo __('No sales have been found for the selected period.', $this->text_domain); ?></td>
						</tr>
					</tbody>
				<?php else : ?>
					<tbody>
						<?php

						$sales_total = 0;
						$taxes_total = 0;
						$render_group = null;
						$row_index = 0;
						foreach($sales_summary_report_data as $entry_id => $entry) {
							if($render_group != $entry->is_eu_country) {
								$this->render_group_header($entry->is_eu_country, $report_columns);
								$render_group = $entry->is_eu_country;
							}

							$sales_total += $entry->sales_total;
							// Round the tax before adding it to the total. This is to return a result more
							// consistent with the EU VAT by Country report
							// @since 1.9.11.190510
							$taxes_total += wc_round_tax_total($entry->tax_total);

							// Add CSS to allow highlighting odd and even numbers, for readability
							// @since 1.10.0.191023
							$row_class = ($row_index % 2) ? 'odd' : 'even';
							?>
							<tr class="<?php echo $row_class; ?>">
								<th class="is_eu <?php echo $debug_columns_class; ?>"><?php echo $entry->is_eu_country; ?></th>
								<th class="billing_country"><?php echo $entry->billing_country; ?></th>
								<th class="tax_rate total_row column_group left right"><?php echo $entry->tax_rate; ?></th>
								<th class="total_sales total_row "><?php echo $this->format_price($entry->sales_total); ?></th>
								<th class="total_tax total_row "><?php echo $this->format_price($entry->tax_total); ?></th>
								<th class="total_sales total_row inc_tax column_group left"><?php echo $this->format_price($entry->sales_total + $entry->tax_total); ?></th>
							</tr>
							<?php
							$row_index++;
						} // First loop - END
						?>
					</tbody>
					<tfoot>
						<tr>
							<th class="label column_group right" colspan="2"><?php echo __('Totals', $this->text_domain); ?></th>
							<?php
								// Filler column, to ensure that the CSV export produces the correct number of columns.
								//
								// @since 1.9.12.190516
								// @link https://wordpress.org/support/topic/export-csv-12/
							?>
							<th class="hidden filler"></th>
							<th class="total total_row"><?php echo $this->format_price($sales_total); ?></th>
							<th class="total total_row"><?php echo $this->format_price($taxes_total); ?></th>
							<th class="total total_row column_group left"><?php echo $this->format_price($sales_total + $taxes_total); ?></th>
						</tr>
					</tfoot>
				<?php endif; ?>
			</table>
		</div>
		<?php
	}

	/**
	 * Renders a header on top of the standard reporting UI.
	 */
	protected function render_ui_header() {
		include(WC_Aelia_EU_VAT_Assistant::instance()->path('views') . '/admin/reports/sales-summary-report-header.php');
	}

	/**
	 * Renders a group header, to organise the data displayed in the report.
	 *
	 * @param string group_id The group ID. Each group ID will show a different
	 * text.
	 * @param int report_columns The number of columns in the report. Used to
	 * determine the "colspan" of the group header.
	 */
	protected function render_group_header($group_id, $report_columns) {
		$group_header_content = array(
			'eu' => array(
				'title' => __('EU Sales', $this->text_domain),
				'description' => __('This section shows sales made to EU countries.', $this->text_domain),
			),
			'non-eu' => array(
				'title' => __('Non-EU Sales', $this->text_domain),
				'description' => __('This section shows sales made to countries outside the EU.', $this->text_domain),
			),
		);

		$content = get_value($group_id, $group_header_content);
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
}