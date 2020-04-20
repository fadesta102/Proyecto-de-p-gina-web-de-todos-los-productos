<?php
namespace Aelia\WC\EU_VAT_Assistant\Reports;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use Aelia\WC\EU_VAT_Assistant\WC_Aelia_EU_VAT_Assistant;
use Aelia\WC\EU_VAT_Assistant\Settings;
use Aelia\WC\EU_VAT_Assistant\Definitions;
use Aelia\WC\Logger;
use \Exception;

/**
 * A base report class with properties and methods common to all EU VAT Assistant
 * reports.
 */
class Base_Report extends \WC_Admin_Report {
	// @var string The text domain to use for localisation.
	protected $text_domain;
	// @var bool Indicates if debug mode is active.
	protected $debug;
	// @var string A list of the tax classes that are not part of MOSS
	protected $non_moss_tax_classes;
	/**
	 * Used for caching. A list of the exchange rates used to convert from various
	 * currencies to the VAT currency, in several target dates. The list keys will
	 * be in <currency>-<YYYY-MM-DD> format, and the value will be the exchange rate.
	 * Example: "USD-2014-12-31" => 0.12345
	 *
	 * @var array
	 */
	protected $vat_currency_exchange_rates = array();


	// @var \Aelia\WC\Logger The logger used by the class.
	protected $logger;

	/**
	 * Logs a message.
	 *
	 * @param string message The message to log.
	 * @param bool debug Indicates if the message is for debugging. Debug messages
	 * are not saved if the "debug mode" flag is turned off.
	 */
	protected function log($message, $debug = true) {
		$this->logger->log($message, $debug);
	}

	/**
	 * Returns the instance of the EU VAT Assistant plugin.
	 *
	 * @return \Aelia\WC\EU_VAT_Assistant\WC_Aelia_EU_VAT_Assistant
	 * @since 1.3.18.150324
	 */
	protected function EUVA() {
		return WC_Aelia_EU_VAT_Assistant::instance();
	}

	/**
	 * Determines if a country is part of the EU.
	 *
	 * @param string country The country code to check.
	 * @return bool
	 * @since 1.3.18.150324
	 */
	protected function is_eu_country($country) {
		return $this->EUVA()->is_eu_country($country);
	}

	/**
	 * Indicates if a combination of country and tax class makes the tax rate fall
	 * under VAT MOSS.
	 *
	 * @param string country A country code.
	 * @param string tax_rate_class A tax rate class.
	 * @return bool
	 * @since 1.3.18.150324
	 */
	protected function is_tax_moss($country, $tax_rate_class) {
		// Tag the tax record to indicate if it's part of MOSS or not
		return ($country !== $this->base_country()) &&
					 (!in_array($tax_rate_class, $this->non_moss_tax_classes));
	}

	/**
	 * Returns shop base country.
	 *
	 * @return string
	 * @since 1.3.10.150306
	 */
	protected function base_country() {
		return wc()->countries->get_base_country();
	}

	/**
	 * Creates the temporary table that will be used to generate the report.
	 *
	 * @param string table_name The name of the table to create. Used mainly for
	 * logging purposes, as the SQL already contains all the instructions to create
	 * it.
	 * @param string $sql The SQL used to create the temporary table.
	 * @return bool True on success, or false on failure.
	 * @since 1.3.20.150330
	 */
	protected function create_temporary_table($table_name, $sql) {
		global $wpdb;

		try {
			$result = $wpdb->query($sql);

			if($result === false) {
				$this->logger->warning(__('Creation of temporary table failed. Please ' .
																	'check PHP error log for error messages ' .
																	'related to the operation.', $this->text_domain), array(
					'Table Name' => $table_name,
					'WPDB Error' => $wpdb->last_error,
				));
			}
			else {
				$this->logger->debug(__('Table created successfullly.', $this->text_domain), array(
					'Table Name' => $table_name,
				));
				$result = true;
			}
		}
		catch(Exception $e) {
			$this->logger->error(__('Exception occurred while creating temporary ' .
															'table. Please check the PHP error log for further details ' .
															'related to the operation.', $this->text_domain), array(
				'Table Name' => $table_name,
				'WPDB Error' => $wpdb->last_error,
				'Exception' => $e->getMessage(),
			));

			$result = false;
		}
		return $result;
	}

	/**
	 * Returns a list of the order statuses to use when fetching the order data
	 * for the report.
	 *
	 * @param bool apply_prefix Indicates if the "wc-" prefix should be appended
	 * to each of the statuses. The prefix was introduced in WooCommerce 2.2.
	 * @return array
	 * @since 1.3.19.150327
	 */
	protected function order_statuses_to_include($apply_prefix = false) {
		$order_statuses_to_include = array('processing', 'completed');

		// If requested, include refunded orders
		if(get_arr_value(Definitions::ARG_INCLUDE_REFUNDED_ORDERS, $_REQUEST) === 'yes') {
			$order_statuses_to_include[] = 'refunded';
		}

		if($apply_prefix) {
			foreach($order_statuses_to_include as &$status) {
				$status = 'wc-' . $status;
			}
		}
		return apply_filters('wc_aelia_euva_report_order_statuses_to_include', $order_statuses_to_include, $apply_prefix, $this);
	}

	/**
	 * Returns the last day of the quarter of which a date is part.
	 *
	 * @param string target_date The date for which the quarter, and its last day,
	 * have to be extracted.
	 * @return string A string representing the last day of a quarter, in YYYY-MM-DD
	 * format.
	 * @throws InvalidArgumentException if an invalid string is passed as a
	 * parameter.
	 */
	protected function get_last_day_of_quarter($target_date) {
		$target_timestamp = strtotime($target_date);
		if($target_timestamp === false) {
			throw new \InvalidArgumentException(sprintf(__('Invalid target date specified: "%s". ' .
																										 'The argument must be in YYYY-MM-DD format.',
																										 $this->text_domain),
																									$target_date));
		}
		$target_date_parts = explode('-', date('Y-m-d', $target_timestamp));

		$quarter_year = array_shift($target_date_parts);
		$quarter_month = array_shift($target_date_parts);
		$quarter_last_month = ceil($quarter_month / 3) * 3;

		$last_day_of_quarter = date('Y-m-t', strtotime("$quarter_year-{$quarter_last_month}-01"));

		// Debug
		//var_dump($last_day_of_quarter);die();
		return $last_day_of_quarter;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->text_domain = WC_Aelia_EU_VAT_Assistant::$text_domain;

		$euva = WC_Aelia_EU_VAT_Assistant::instance();
		$this->debug = $euva->debug_mode();
		$this->logger = $euva->get_logger();

		// Keep a list of the tax classes that are not part of MOSS
		$this->non_moss_tax_classes = WC_Aelia_EU_VAT_Assistant::settings()->get(Settings::FIELD_TAX_CLASSES_EXCLUDED_FROM_MOSS, array());
	}

	/**
	 * Returns the value of a configuration parameter for the EU VAT Assistant
	 * plugin.
	 *
	 * @param string settings_key The key to retrieve the parameter.
	 * @param mixed default The default value to return if the parameter is not
	 * found.
	 * @return mixed
	 */
	protected function settings($settings_key, $default = null) {
		return WC_Aelia_EU_VAT_Assistant::settings()->get($settings_key, $default);
	}

	/**
	 * Returns the currency to use for the VAT returns.
	 *
	 * @return string
	 */
	protected function vat_currency() {
		return $this->settings(Settings::FIELD_VAT_CURRENCY);
	}

	/**
	 * Formats a price, adding the VAT currency symbol.
	 *
	 * @param float price The price to format.
	 * @return string
	 */
	protected function format_price($price) {
		$args = array(
			'currency' => $this->vat_currency(),
		);
		return wc_price($price, $args);
	}

	/**
	 * Get the legend for the main chart sidebar.
	 *
	 * @return array
	 */
	public function get_chart_legend() {
		// No legend is needed (this report) doesn't have a chart
		return array();
	}

	/**
	 * Output an export link
	 */
	public function get_export_button() {
		$current_range = !empty($_GET['range']) ? sanitize_text_field($_GET['range']) : 'last_month';
		?>
		<a
			href="#"
			download="report-<?php echo esc_attr($current_range); ?>-<?php echo date_i18n('Y-m-d', current_time('timestamp')); ?>.csv"
			class="export_csv"
			data-export="table"
		>
			<?php echo __('Export CSV', $this->text_domain); ?>
		</a>
		<?php
	}

	/**
	 * Returns an array of ranges that are used to produce the reports.
	 *
	 * @return array
	 * @since 0.9.7.141221
	 */
	protected function get_report_ranges() {
		$ranges = array();

		$current_time = current_time('timestamp');
		$label_fmt = __('Q%d %d', $this->text_domain);

		// Current quarter
		$quarter = ceil(date('m', $current_time) / 3);
		$year = date('Y');
		$ranges['quarter'] = sprintf($label_fmt, $quarter, $year);

		// Quarter before this one
		$month = date('m', strtotime('-3 MONTH', $current_time));
		$year  = date('Y', strtotime('-3 MONTH', $current_time));
		$quarter = ceil($month / 3);
		$ranges['previous_quarter'] = sprintf($label_fmt, $quarter, $year);

		// Two quarters ago
		$month = date('m', strtotime('-6 MONTH', $current_time));
		$year  = date('Y', strtotime('-6 MONTH', $current_time));
		$quarter = ceil($month / 3);
		$ranges['quarter_before_previous'] = sprintf($label_fmt, $quarter, $year);

		return array_reverse($ranges);
	}

	/**
	 * Renders a header on top of the standard reporting UI.
	 */
	protected function render_ui_header() {
		// To be implemented by descendant classes
	}

	/**
	 * Output the report
	 */
	public function output_report() {
		$ranges = $this->get_report_ranges();
		$current_range = !empty($_GET['range']) ? sanitize_text_field($_GET['range']) : 'quarter';

		if(!in_array($current_range, array_merge(array_keys($ranges), array('custom')))) {
			$current_range = 'quarter';
		}
		$this->calculate_current_range($current_range);

		$hide_sidebar = true;

		// Render a header on top of the standard reporting UI
		$this->render_ui_header();

		include(WC()->plugin_path() . '/includes/admin/views/html-report-by-date.php');
	}

	/**
	 * Get the current range and calculate the start and end dates
	 * @param  string $current_range
	 */
	public function calculate_current_range($current_range) {
		$this->chart_groupby = 'month';
		switch ($current_range) {
			case 'quarter_before_previous':
				$month = date('m', strtotime('-6 MONTH', current_time('timestamp')));
				$year  = date('Y', strtotime('-6 MONTH', current_time('timestamp')));
			break;
			case 'previous_quarter':
				$month = date('m', strtotime('-3 MONTH', current_time('timestamp')));
				$year  = date('Y', strtotime('-3 MONTH', current_time('timestamp')));
			break;
			case 'quarter':
				$month = date('m', current_time('timestamp'));
				$year  = date('Y', current_time('timestamp'));
			break;
			default:
				parent::calculate_current_range($current_range);
				return;
			break;
		}

		if($month <= 3) {
			$this->start_date = strtotime($year . '-01-01');
			$this->end_date = strtotime(date('Y-m-t', strtotime($year . '-03-01')));
		}
		elseif($month > 3 && $month <= 6) {
			$this->start_date = strtotime($year . '-04-01');
			$this->end_date = strtotime(date('Y-m-t', strtotime($year . '-06-01')));
		}
		elseif($month > 6 && $month <= 9) {
			$this->start_date = strtotime($year . '-07-01');
			$this->end_date = strtotime(date('Y-m-t', strtotime($year . '-09-01')));
		}
		elseif($month > 9) {
			$this->start_date = strtotime($year . '-10-01');
			$this->end_date = strtotime(date('Y-m-t', strtotime($year . '-12-01')));
		}
	}

	/**
	 * Returns the details of the specified tax rate IDs.
	 *
	 * @param array tax_rate_ids An array of tax rate IDs.
	 * @return array An array with the details of each tax rate ID.
	 */
	protected function get_tax_rates_data(array $tax_rate_ids) {
		global $wpdb;
		if(empty($tax_rate_ids)) {
			return;
		}

		$SQL = "
			SELECT
				TR.tax_rate_id
				,TR.tax_rate
				,TR.tax_rate_class
				,TR.tax_rate_country
			FROM
				{$wpdb->prefix}woocommerce_tax_rates TR
			WHERE
				(TR.tax_rate_id IN (%s));
		";
		// We cannot use $wpdb::prepare(). We need the result of the implode()
		// call to be injected as is, while the prepare() method would wrap it in quotes.
		$SQL = sprintf($SQL, implode(',', $tax_rate_ids));

		// Retrieve the details of the tax rates
		return $wpdb->get_results($SQL, OBJECT_K);
	}

	/**
	 * Get report totals such as order totals and discount amounts.
	 *
	 * Data example:
	 *
	 * '_order_total' => array(
	 *     'type'     => 'meta',
	 *     'function' => 'SUM',
	 *     'name'     => 'total_sales'
	 * )
	 *
	 * @param  array $args
	 * @return array|string depending on query_type
	 */
	public function get_order_report_data($args = array()) {
		$args = array_merge(array(
			'filter_range' => true,
			'nocache' => true,
			'debug' => $this->debug,
		), $args);
		return parent::get_order_report_data($args);
	}

	/**
	 * Returns the name of the country passed as an argument.
	 *
	 * @param string country_code A ountry code.
	 * @return string The country name.
	 * @since 0.9.7.141221
	 */
	protected function get_country_name($country_code) {
		if(empty($this->countries)) {
			$this->countries = WC()->countries->countries;
		}
		return $this->countries[$country_code];
	}

	/**
	 * Returns the exchange for a currency in a specific date.
	 *
	 * @param string from_currency The currency for which the exchange rate should
	 * be retrieved.
	 * @param string target_date Indicates for which date the exchange rates should
	 * be retrieved.
	 * @return float|null An exchange rate, if any is found, or null.
	 */
	protected function get_vat_currency_exchange_rate($from_currency, $target_date) {
		$rate = null;
		// There's no point in trying to get historical rates for a date in the
		// future
		if($target_date > date('Y-m-d')) {
			return $rate;
		}

		$rate_key = $from_currency . '-' . $target_date;

		// If we already have the exchange rate for the date, return it
		if(empty($this->vat_currency_exchange_rates[$target_date])) {
			$exchange_rate_provider_class = apply_filters('wc_aelia_euva_report_fx_provider_class',
																										'\Aelia\WC\EU_VAT_Assistant\Exchange_Rates_ECB_Historical_Model');
			$rates = $exchange_rate_provider_class::get_rates_for_date($target_date, $this->vat_currency());

			$this->vat_currency_exchange_rates[$target_date] = apply_filters('wc_aelia_eu_vat_assistant_eu_vat_report_exchange_rates',
																																			 $rates,
																																			 $target_date,
																																			 $this);
		}

		/* The rates represent how many "FROM CURRENCY" correspond to one "VAT
		 * CURRENCY". We will need to know the opposite, i.e. how many VAT CURRENCY
		 * correspond to one FROM CURRENCY, therefore we have to calculate the
		 * quotient as "1 divided by <returned rate>".
		 */
		if(!empty($this->vat_currency_exchange_rates[$target_date][$from_currency])) {
			$rate = 1 / $this->vat_currency_exchange_rates[$target_date][$from_currency];
		}

		// Debug
		//var_dump("[$target_date] - $from_currency to " . $this->vat_currency() . ": $rate");
		return $rate;
	}

	/**
	 * Indicates if the VAT data passed as a parameter contains tax data
	 * collected by the EU VAT Assistant.
	 *
	 * @param array vat_data The VAT data collected by the plugin.
	 * @return bool
	 * @since 1.10.0.191023
	 */
	protected function order_has_taxes($vat_data) {
		return !empty($vat_data['taxes']) && is_array($vat_data['taxes']);
	}
}
