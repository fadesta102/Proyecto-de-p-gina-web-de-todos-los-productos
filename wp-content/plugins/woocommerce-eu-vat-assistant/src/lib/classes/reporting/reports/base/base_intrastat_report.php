<?php
namespace Aelia\WC\EU_VAT_Assistant\Reports;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use Aelia\WC\EU_VAT_Assistant\WC_Aelia_EU_VAT_Assistant;
use Aelia\WC\EU_VAT_Assistant\Settings;
use Aelia\WC\EU_VAT_Assistant\Definitions;
use \DateTime;

/**
 * Renders the INTRASTAT report.
 *
 * @since 1.4.1.150407
 */
class Base_INTRASTAT_Report extends \Aelia\WC\EU_VAT_Assistant\Reports\Base_Sales_Report {
	const INTRASTAT_REPORT_TEMP_TABLE = 'aelia_euva_intrastat_report';

	/**
	 * Indicates if the tax passed as a parameter should be skipped (i.e. excluded
	 * from the report).
	 *
	 * @param array tax_details An array of data describing a tax.
	 * @return bool True (tax should be excluded from the report) or false (tax
	 * should be displayed on the report).
	 */
	protected function should_skip($order_data) {
		$eu_vat_evidence = maybe_unserialize(get_value('eu_vat_evidence', $order_data));
		// INTRASTAT reports must only include sales to the EU, excluding shop base
		// country
		if(empty($eu_vat_evidence['location']['is_eu_country']) ||
			 ($eu_vat_evidence['location']['is_eu_country'] == false) ||
			 ($eu_vat_evidence['location']['billing_country'] == $this->base_country())) {
			return true;
		}
		return false;
	}

	/**
	 * Creates the temporary table that will be used to generate the INTRASTAT report.
	 *
	 * @return string|bool The name of the created table, or false on failure.
	 */
	protected function create_temp_intrastat_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . self::INTRASTAT_REPORT_TEMP_TABLE;
		$sql = "
			CREATE TEMPORARY TABLE IF NOT EXISTS `$table_name` (
				`row_id` INT NOT NULL AUTO_INCREMENT,
				`order_id` INT NOT NULL,
				`order_date` DATETIME NOT NULL,
				`post_type` VARCHAR(50) NOT NULL,
				`is_eu_country` VARCHAR(10) NOT NULL,
				`billing_country` VARCHAR(10) NOT NULL,
				`vat_number` VARCHAR(50) NOT NULL,
				`vat_number_validated` VARCHAR(50) NOT NULL,
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
	 * Stores a row in the temporary table used to produce the INTRASTAT report.
	 */
	protected function store_temp_intrastat_row(array $fields) {
		global $wpdb;

		// Debug
		//var_dump("STORING TEMP. INTRASTAT DATA", $fields);

		$table_name = $wpdb->prefix . self::INTRASTAT_REPORT_TEMP_TABLE;
		$SQL = "
			INSERT INTO `$table_name` (
				`order_id`,
				`order_date`,
				`post_type`,
				`is_eu_country`,
				`billing_country`,
				`vat_number`,
				`vat_number_validated`,
				`order_item_id`,
				`line_total`,
				`line_tax`,
				`tax_rate`,
				`exchange_rate`
			)
			VALUES (
				%d, -- Order ID
				%s, -- Order date (date/time)
				%s, -- Post type (for debugging purposes)
				%s, -- Is EU country (flag)
				%s, -- Billing country
				%d, -- Order item ID
				%s, -- VAT Number
				%s, -- VAT Number validated (flag)
				%f, -- Line total
				%f, -- Line tax
				%f, -- Tax rate
				%f -- Exchange rate
			)
		";

		$query = $wpdb->prepare(
			$SQL,
			$fields['order_id'],
			$fields['order_date'],
			$fields['post_type'],
			$fields['is_eu_country'],
			$fields['billing_country'],
			$fields['vat_number'],
			$fields['vat_number_validated'],
			$fields['order_item_id'],
			$fields['line_total'],
			$fields['line_tax'],
			$fields['tax_rate'],
			$fields['exchange_rate']
		);

		// Debug
		//var_dump($fields, $query);die();

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
	 * Stores in a temporary table the data required to produce the INTRASTAT report.
	 *
	 * @param array dataset An array containing the data for the report.
	 * @return bool True if the data was stored correctly, false otherwise.
	 * @since 1.3.20.150402
	 */
	protected function store_report_data($dataset) {
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

				$fields = array(
					'order_id' => $entry->order_id,
					'order_date' => $entry->order_date,
					'post_type' => $entry->post_type,
					'is_eu_country' => $this->is_eu_country($entry->eu_vat_evidence['location']['billing_country']) ? 'eu' : 'non-eu',
					'billing_country' => $entry->eu_vat_evidence['location']['billing_country'],
					'vat_number' => $entry->eu_vat_evidence['exemption']['vat_number'],
					'vat_number_validated' => $entry->vat_number_validated,
					'order_item_id' => $entry->order_item_id,
					'exchange_rate' => $vat_currency_exchange_rate,
				);

				/* Calculate the tax rate
				 * A tax rate lower than zero means that the actual rate could not be
				 * calculated via SQL. This is often the case when the item is a shipping
				 * cost, as its tax is stored as an array, insteaf of a number (see
				 * above).
				 */
				// TODO This logic doesn't support compounding tax rates and should be reviewed.
				if($entry->tax_rate < 0) {
					$entry->tax_rate = 0;
					if($entry->line_total > 0) {
						$entry->tax_rate = round($entry->line_tax / $entry->line_total * 100, 2);
					}
				}

				$fields = array_merge($fields, array(
					'line_total' => $entry->line_total,
					'line_tax' => $entry->line_tax,
					'tax_rate' => $entry->tax_rate,
				));

				if(!$this->store_temp_intrastat_row($fields)) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Returns the sales data that will be included in the report. This method must
	 * be implemented by descendant classes.
	 *
	 * @return array
	 */
	protected function get_sales_data() {
	}

	/**
	 * Returns the refunds data that will be included in the report. This method
	 * is empty for compatibility with WooCommerce 2.1, which doesn't handle
	 * refunds. Classes designed for WooCommerce 2.2 and later will take care of
	 * fetching the refunds.
	 *
	 * @return array
	 */
	protected function get_refunds_data() {
		return array();
	}

	/**
	 * Consolidates the sales data with the refunds data and returns it.
	 *
	 * @return array An array containing the consolidated sales and return data.
	 */
	protected function get_intrastat_report_data() {
		global $wpdb;

		$px = $wpdb->prefix;
		$SQL = "
			SELECT
				YEAR(VR.order_date) AS year
				,MONTH(VR.order_date) AS month
				,SUM(VR.line_total * VR.exchange_rate) AS period_total
			FROM
				{$px}" . self::INTRASTAT_REPORT_TEMP_TABLE . " VR
			GROUP BY
				YEAR(VR.order_date)
				,MONTH(VR.order_date)
		";

		// Debug
		//var_dump($SQL);
		$dataset = $wpdb->get_results($SQL);

		// Debug
		//var_dump("REFUNDS RESULT", $dataset);
		return $dataset;
	}

	/**
	 * Loads and returns the report data.
	 *
	 * @return array An array with the report data.
	 */
	protected function get_report_data() {
		if($result = $this->create_temp_intrastat_table()) {
			// Retrieve and store sales data
			$result = $this->store_report_data($this->get_sales_data());

			// Retrieve and store refunds data
			if($result) {
				$result = $this->store_report_data($this->get_refunds_data());
			}

			if($result) {
				// Prepare a summary for the INTRASTAT report and return it
				$result = $this->get_intrastat_report_data();
			}
			return $result;
		}

		if(!$result) {
			$this->logger->warning(__('Could not prepare temporary table for the report. ' .
																'Please enable debug mode and try again. If the issue ' .
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
		$intrastat_report_data = $this->get_report_data();

		// Debug
		//var_dump($intrastat_report_data);

		// Keep track of the report columns. This information will be used to adjust
		// the "colspan" property
		$report_columns = 3;
		?>
		<div id="intrastat_report" class="wc_aelia_eu_vat_assistant report">
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
								<!--
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
								-->
							</ul>
						</th>
					</tr>
					<tr class="column_headers">
						<th class="year"><?php echo __('Year', $this->text_domain); ?></th>
						<th class="month"><?php echo __('Month', $this->text_domain); ?></th>
						<th class="total"><?php echo __('Sales', $this->text_domain); ?></th>
					</tr>
				</thead>
				<?php if(empty($intrastat_report_data)) : ?>
					<tbody>
						<tr>
							<td colspan="<?php echo $report_columns; ?>"><?php echo __('No sales falling under INTRASTAT scheme have been found.', $this->text_domain); ?></td>
						</tr>
					</tbody>
				<?php else : ?>
					<tbody>
						<?php
						$sales_total = 0;
						foreach($intrastat_report_data as $entry_id => $entry) {
							$sales_total += $entry->period_total;

							$date_obj = DateTime::createFromFormat('!m', $entry->month);
							$month_name = $date_obj->format('F');
							?>
							<tr>
								<td class="year"><?php echo $entry->year; ?></td>
								<td class="month"><?php echo $month_name; ?></td>
								<td class="total"><?php echo $this->format_price($entry->period_total); ?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
					<tfoot>
						<tr>
							<th colspan="2" class="label"><?php echo __('Total', $this->text_domain); ?></th>
							<td class="total"><?php echo $this->format_price($sales_total); ?></td>
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
		// The INTRASTAT report does not require a header
	}

	/**
	 * Returns the label to be used to indicate a bi-monthly period.
	 *
	 * @param int period The period for which the label has to be generated.
	 * @param int year The year to which the period refers.
	 * @return string The label for the period.
	 * @since 1.4.4.150421
	 */
	protected function get_period_label($period, $year) {
		$month2 = $period * 2;

		$month_name_1 = DateTime::createFromFormat('!m', $month2 - 1)->format('M');
		$month_name_2 = DateTime::createFromFormat('!m', $month2)->format('M');

		return sprintf(__('%s-%s %d', $this->text_domain), $month_name_1, $month_name_2, $year);
	}

	/**
	 * Returns an array of ranges that are used to produce the reports.
	 *
	 * @return array
	 */
	protected function get_report_ranges() {
		$ranges = array();

		$current_time = current_time('timestamp');

		// Current bi-monthly period
		$period = ceil(date('m', $current_time) / 2);
		$year = date('Y');
		$ranges['current_period'] = $this->get_period_label($period, $year);

		// Quarter before this one
		$month = date('m', strtotime('-2 MONTH', $current_time));
		$year  = date('Y', strtotime('-2 MONTH', $current_time));
		$period = ceil($month / 2);
		$ranges['previous_period'] = $this->get_period_label($period, $year);

		// Two quarters ago
		$month = date('m', strtotime('-4 MONTH', $current_time));
		$year  = date('Y', strtotime('-4 MONTH', $current_time));
		$period = ceil($month / 2);
		$ranges['before_previous_period'] = $this->get_period_label($period, $year);

		return array_reverse($ranges);
	}

	/**
	 * Output the report.
	 *
	 * @since 1.4.4.150421
	 */
	public function output_report() {
		$ranges = $this->get_report_ranges();
		$current_range = !empty($_GET['range']) ? sanitize_text_field($_GET['range']) : 'current_period';

		if(!in_array($current_range, array_merge(array_keys($ranges), array('custom')))) {
			$current_range = 'current_period';
		}
		$this->calculate_current_range($current_range);

		$hide_sidebar = true;

		// Render a header on top of the standard reporting UI
		$this->render_ui_header();

		include(WC()->plugin_path() . '/includes/admin/views/html-report-by-date.php');
	}

	/**
	 * Get the current range and calculate the start and end dates of the
	 * corresponding bi-monthly period.
	 *
	 * @param string $current_range The range to be used for the calculation.
	 * @since 1.4.4.150421
	 */
	public function calculate_current_range($current_range) {
		$this->chart_groupby = 'month';
		switch ($current_range) {
			case 'before_previous_period':
				$month = date('m', strtotime('-4 MONTH', current_time('timestamp')));
				$year  = date('Y', strtotime('-4 MONTH', current_time('timestamp')));
			break;
			case 'previous_period':
				$month = date('m', strtotime('-2 MONTH', current_time('timestamp')));
				$year  = date('Y', strtotime('-2 MONTH', current_time('timestamp')));
			break;
			case 'current_period':
				$month = date('m', current_time('timestamp'));
				$year  = date('Y', current_time('timestamp'));
			break;
			default:
				parent::calculate_current_range($current_range);
				return;
			break;
		}

		if($month <= 2) {
			$this->start_date = strtotime($year . '-01-01');
			$this->end_date = strtotime(date('Y-m-t', strtotime($year . '-02-01')));
		}
		elseif($month > 2 && $month <= 4) {
			$this->start_date = strtotime($year . '-03-01');
			$this->end_date = strtotime(date('Y-m-t', strtotime($year . '-04-01')));
		}
		elseif($month > 4 && $month <= 6) {
			$this->start_date = strtotime($year . '-05-01');
			$this->end_date = strtotime(date('Y-m-t', strtotime($year . '-06-01')));
		}
		elseif($month > 6 && $month <= 8) {
			$this->start_date = strtotime($year . '-07-01');
			$this->end_date = strtotime(date('Y-m-t', strtotime($year . '-08-01')));
		}
		elseif($month > 8 && $month <= 10) {
			$this->start_date = strtotime($year . '-09-01');
			$this->end_date = strtotime(date('Y-m-t', strtotime($year . '-10-01')));
		}
		elseif($month > 10) {
			$this->start_date = strtotime($year . '-11-01');
			$this->end_date = strtotime(date('Y-m-t', strtotime($year . '-12-01')));
		}
	}
}
