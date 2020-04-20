<?php
namespace Aelia\WC\EU_VAT_Assistant\Reports;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use Aelia\WC\EU_VAT_Assistant\Definitions;

/**
 * Base class for the VIES report.
 *
 * @since 1.4.1.150407
 */
abstract class Base_VIES_Report extends \Aelia\WC\EU_VAT_Assistant\Reports\Base_Sales_Report {
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
		// VIES reports must only include sales to the EU, excluding shop base
		// country
		if(empty($eu_vat_evidence['location']['is_eu_country']) ||
			 ($eu_vat_evidence['location']['is_eu_country'] == false) ||
			 ($eu_vat_evidence['location']['billing_country'] == $this->base_country())) {
			return true;
		}

		// VIES reports must only include sales to customers who entered a valid EU
		// VAT number
		if(empty($eu_vat_evidence['exemption']['vat_number']) ||
			 empty($eu_vat_evidence['exemption']['vat_number_validated']) ||
			 $eu_vat_evidence['exemption']['vat_number_validated'] !== Definitions::VAT_NUMBER_VALIDATION_VALID) {
			return true;
		}
		return false;
	}

	/**
	 * Creates the temporary table that will be used to generate the VIES report.
	 *
	 * @return string|bool The name of the created table, or false on failure.
	 * @since 1.3.20.150330
	 */
	protected function create_temp_vies_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'aelia_euva_vies_report';
		$sql = "
			CREATE TEMPORARY TABLE IF NOT EXISTS `$table_name` (
				`row_id` INT NOT NULL AUTO_INCREMENT,
				`order_id` INT NOT NULL,
				`product_id` INT NOT NULL,
				`billing_country` VARCHAR(5) NOT NULL,
				`vat_number` VARCHAR(50) NOT NULL,
				`line_total` DECIMAL(18,6) NOT NULL,
				`is_service` SMALLINT NOT NULL,
				`is_triangulation` SMALLINT NOT NULL,
				`exchange_rate` DECIMAL(18,6) NOT NULL,
				PRIMARY KEY (`row_id`),
				INDEX `IX_ORDER_ID` (`order_id` ASC),
				INDEX `IX_PRODUCT_ID` (`product_id` ASC)
			) {$charset_collate};
		";

		return $this->create_temporary_table($table_name, $sql);
	}

	/**
	 * Stores a row in the temporary table used to produce the VIES report.
	 *
	 * @since 1.3.20.150330
	 */
	protected function store_temp_vies_row(array $fields) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'aelia_euva_vies_report';
		$SQL = "
			INSERT INTO `$table_name` (
				`order_id`,
				`product_id`,
				`billing_country`,
				`vat_number`,
				`line_total`,
				`is_service`,
				`is_triangulation`,
				`exchange_rate`
			)
			VALUES (
				%d, -- Order ID
				%d, -- Product ID
				%s, -- Billing country
				%s, -- VAT Number
				%f, -- Line total
				%d, -- 'Is service' flag
				%d, -- 'Triangulation' flag
				%f -- Exchange rate
			)
		";

		$query = $wpdb->prepare(
			$SQL,
			$fields['order_id'],
			$fields['product_id'],
			$fields['billing_country'],
			$fields['vat_number'],
			$fields['line_total'],
			$fields['is_service'],
			$fields['is_triangulation'],
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

	protected function get_order_items_meta_keys() {
		return array(
			// _line_total: total charged for order items
			'_line_total',
			// cost: total charged for shipping
			'cost',
		);
	}

	/**
	 * Stores in a temporary table the data required to produce the VIES report.
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
					'product_id' => $entry->product_id,
					'billing_country' => $entry->eu_vat_evidence['location']['billing_country'],
					'vat_number' => $entry->eu_vat_evidence['exemption']['vat_number'],
					'line_total' => $entry->line_total,
					'is_service' => $entry->is_service,
					'is_triangulation' => $entry->is_triangulation,
					'exchange_rate' => $vat_currency_exchange_rate,
				);

				// Debug
				//var_dump($fields);die();

				if(!$this->store_temp_vies_row($fields)) {
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
		return array();
	}

	/**
	 * Returns the refunds data that will be included in the report. This method
	 * is empty for compatibility with WooCommerce 2.1, which doesn't handle
	 * refunds. Classes designed for WooCommerce 2.2 and later will take care of
	 * fetching the refunds.
	 *
	 * @return array
	 * @since 1.3.20.150330
	 */
	protected function get_refunds_data() {
		return array();
	}

	/**
	 * Consolidates the sales data with the refunds data and returns it.
	 *
	 * @return array An array containing the consolidated sales and return data.
	 * @since 1.3.20.150330
	 */
	protected function get_vies_report_data() {
		global $wpdb;

		$px = $wpdb->prefix;
		$SQL = "
			SELECT
				VR.order_id
				,VR.billing_country
				,VR.vat_number
				,VR.is_service
				,VR.is_triangulation
				,SUM(VR.line_total * VR.exchange_rate) AS line_total
			FROM
				{$px}aelia_euva_vies_report VR
			GROUP BY
				VR.vat_number
				,VR.is_service
				,VR.is_triangulation
			HAVING
				-- Discard rows with zero, they don't need to be added to the report.
				-- We can't just use 'greater than zero' as a criteria, because rows
				-- with negative values must be included
				line_total <> 0
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
	 * @since 1.3.20.150402
	 */
	protected function get_report_data() {
		if($result = $this->create_temp_vies_table()) {
			// Retrieve and store sales data
			$result = $this->store_report_data($this->get_sales_data());

			// Retrieve and store refunds data
			if($result) {
				$result = $this->store_report_data($this->get_refunds_data());
			}

			if($result) {
				// Prepare a summary for the VIES report and return it
				$result = $this->get_vies_report_data();
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
		$vies_report_data = $this->get_report_data();

		// Keep track of the report columns. This information will be used to adjust
		// the "colspan" property
		$report_columns = 6;
		?>
		<div id="vies_report" class="wc_aelia_eu_vat_assistant report">
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
						<th class="order_id"><?php echo __('Order ID', $this->text_domain); ?></th>
						<th class="billing_country"><?php echo __('Customer country', $this->text_domain); ?></th>
						<th class="vat_number"><?php echo __('Customer VAT Number', $this->text_domain); ?></th>
						<th class="total_sales"><?php echo __('Sales', $this->text_domain); ?></th>
						<th class="is_service"><?php echo __('Services', $this->text_domain); ?></th>
						<th class="is_triangulation"><?php echo __('Triangulation', $this->text_domain); ?></th>
					</tr>
				</thead>
				<?php if(empty($vies_report_data)) : ?>
					<tbody>
						<tr>
							<td colspan="<?php echo $report_columns; ?>"><?php echo __('No sales falling under VIES scheme have been found.', $this->text_domain); ?></td>
						</tr>
					</tbody>
				<?php else : ?>
					<tbody>
						<?php

						$sales_total = 0;
						$row_index = 0;

						// First loop - Tax groups (MOSS and non-MOSS)
						foreach($vies_report_data as $entry_id => $entry) {
							$sales_total += $entry->line_total;

							// Add CSS to allow highlighting odd and even numbers, for readability
							// @since 1.7.18.180114
							$row_class = ($row_index % 2) ? 'odd' : 'even';
							?>
							<tr class="<?php echo $row_class; ?>">
								<td class="order_id">
									<a href="<?php echo admin_url('post.php?post=' . absint($entry->order_id) . '&action=edit'); ?>"><?php
										echo $entry->order_id;
									?></a>
								</td>
								<td class="billing_country"><?php echo $entry->billing_country; ?></td>
								<td class="vat_number"><?php echo $entry->vat_number; ?></td>
								<td class="total_sales"><?php echo $this->format_price($entry->line_total); ?></td>
								<td class="is_service"><?php echo (int)$entry->is_service; ?></td>
								<td class="is_triangulation"><?php echo (int)$entry->is_triangulation; ?></td>
							</tr>
							<?php
							$row_index++;
						} // First loop - END
						?>
					</tbody>
					<tfoot>
						<tr>
							<th class="label"><?php echo __('Total', $this->text_domain); ?></th>
							<th class="total"><?php echo $this->format_price($sales_total); ?></th>
							<th colspan="4"></th>
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
		// The VIES report does not require a header
	}
}
