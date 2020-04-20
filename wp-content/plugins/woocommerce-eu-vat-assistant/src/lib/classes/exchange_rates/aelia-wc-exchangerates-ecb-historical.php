<?php
namespace Aelia\WC\EU_VAT_Assistant;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use \Exception;

/**
 * Retrieves the exchange rates from the European Central Bank.
 *
 * @link https://www.ecb.europa.eu/stats/exchange/eurofxref/html/index.en.html
 */
class Exchange_Rates_ECB_Historical_Model extends Exchange_Rates_ECB_Model {
	// @var string The date for which the exchange rates should be retrieved, in YYYY-MM-DD format
	protected $target_date;

	// @var string The URL template to use to query ECB
	private $ecb_api_rates_last_90_days_url = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-hist-90d.xml';

	/**
	 * Tranforms the exchange rates received from ECB into an array of
	 * currency code => exchange rate pairs.
	 *
	 * @param string ecb_rates The XML received from ECB.
	 * @retur array
	 */
	protected function decode_rates($ecb_rates) {
		$exchange_rates = array();

		// This loop looks for the node containing the exchange rates for the specified
		// target date. Using xpath would probably be a better approach, but it
		// doesn't seem to work properly with the DOM containing the ECB rates.
		//
		// Note
		// The ECB historical feed doesn't include all dates. Bank holidays and weekends
		// might be missing from it. Due to that, we need to take the first date that is
		// equal or higher than the target date.
		//
		// Example
		// 30 March 2019 is a Sunday -> Date to take: 1 April 2019.
		//
		// @since 1.9.13.190520
		// @link https://www.revenue.ie/en/vat/vat-moss/filing-and-paying-my-vat-on-moss/how-and-when-should-the-moss-vat-payment-be-submitted.aspx
		$closest_matching_date = date('Y-m-d');
		$rates_xml = null;
		foreach($ecb_rates->Cube->Cube as $rates_for_day) {
			// If there are rates on a date greater or equal to the target date, but on a date prior to the
			// last one found, take them. We need to get as close as possible to that target date, when such
			// falls on a bank holiday or weekend
			// @since 1.9.13.190520
			if(($rates_for_day['time'] >= $this->target_date) && ($rates_for_day['time'] < $closest_matching_date)) {
				$closest_matching_date = (string)$rates_for_day['time'];
				$rates_xml = $rates_for_day->Cube;
			}
		}

		// Debug
		//var_dump($closest_matching_date, $rates_xml);die();

		// Extract the exchange rates from the feed
		foreach($rates_xml as $rate) {
			$exchange_rates[(string)$rate['currency']] = (float)$rate['rate'];
		}

		// ECB feed is based against EUR, but it doesn't contain such currency. We
		// can safely add it manually, with an exchange rate of 1
		$exchange_rates['EUR'] = 1;

		return $exchange_rates;
	}

	/**
	 * Fetches all exchange rates from ECB API.
	 *
	 * @return object|bool An object containing the response from Open Exchange, or
	 * False in case of failure.
	 */
	private function fetch_all_rates() {
		$ecb_api_rates_url = $this->ecb_api_rates_last_90_days_url;

		try {
			$response = \Httpful\Request::get($ecb_api_rates_url)
				->expectsXml()
				->send();

			// Debug
			//var_dump("ECB RATES RESPONSE:", $response); die();
			if($response->hasErrors()) {
				// OpenExchangeRates sends error details in response body
				if($response->hasBody()) {
					$response_data = $response->body;

					$this->add_error(self::ERR_ERROR_RETURNED,
													 sprintf(__('Error returned by ECB. ' .
																			'Error code: %s. Error message: %s - %s.',
																			Definitions::TEXT_DOMAIN),
																	 $response_data->status,
																	 $response_data->message,
																	 $response_data->description));
				}
				return false;
			}
			return $response->body;
		}
		catch(Exception $e) {
			$this->add_error(self::ERR_EXCEPTION_OCCURRED,
											 sprintf(__('Exception occurred while retrieving the exchange rates from ECB. ' .
																	'Error message: %s.',
																	Definitions::TEXT_DOMAIN),
															 $e->getMessage()));
			return null;
		}
	}

	/**
	 * Stores the exchange rates retrieved from the provider for a specific date.
	 *
	 * @param string base_currency The base currency to which the exchange rates
	 * refer.
	 * @param string rates_date The date to which the exchange rates refer.
	 * @param array rates An array of exchange rates.
	 * @return bool
	 */
	protected function store_rates($base_currency, $rates_date, $rates) {
		global $wpdb;

		// If there aren't at least two entries in the exchange rates, then the
		// array was not populated correctly
		if(!is_array($rates) || count($rates) <= 1) {
			return false;
		}

		$table_name = $wpdb->prefix . 'aelia_exchange_rates_history';
		$SQL = "
			INSERT INTO `$table_name` (
				`provider_name`,
				`base_currency`,
				`rates_date`,
				`rates`,
				`date_updated`
			)
			VALUES (
				%s, -- Provider name
				%s, -- Base currency
				%s, -- Rates date
				%s, -- Rates (JSON)
				%s -- Date updated
			)
			ON DUPLICATE KEY UPDATE
				`rates` = %s,
				`date_updated` = %s
		";

		$query = $wpdb->prepare(
			$SQL,
			get_class($this),
			$base_currency,
			$rates_date,
			json_encode($rates),
			date('YmdHis'),
			// The two values below are repeated because they will populate the
			// ON DUPLICATE KEY section of the query
			json_encode($rates),
			date('YmdHis')
		);

		// Debug
		//var_dump($query);

		// Save to database the IP data for the country
		$rows_affected = $wpdb->query($query);

		$result = $rows_affected;
		if($result == false) {
			$error_message = sprintf(__('Could not store exchange rates. Provider: "%s". ' .
																	'Base currency: "%s". Rates date: "%s". Rates (JSON): "%s".', $this->text_domain),
															 get_class($this),
															 $base_currency,
															 date('Y-m-d', $rates_date),
															 json_encode($rates)
															 );
			$this->log($error_message, false);
		}
		return $result;
	}

	protected function get_stored_rates($rates_date) {
		global $wpdb;

		$SQL = "
			SELECT
				ERH.`provider_name`
				,ERH.`base_currency`
				,ERH.`rates_date`
				,ERH.`rates`
				,ERH.`date_updated`
			FROM
				{$wpdb->prefix}aelia_exchange_rates_history ERH
			WHERE
				(ERH.provider_name = %s) AND
				(ERH.rates_date = %s)
		";

		$query = $wpdb->prepare(
			$SQL,
			get_class($this),
			$rates_date
		);

		// Debug
		//var_dump($query);

		$rates = array();
		$dataset = $wpdb->get_results($query);

		// Debug
		if(is_array($dataset) && !empty($dataset)) {
			$dataset = array_shift($dataset);
			$rates = json_decode($dataset->rates, true);
		}

		// Debug
		//var_dump($rates);die();
		return $rates;
	}

	/**
	 * Returns current exchange rates for the specified currency.
	 *
	 * @param string base_currency The base currency.
	 * @return array An array of Currency => Exchange Rate pairs.
	 */
	public function current_rates($base_currency) {
		if(empty($this->_current_rates) ||
			 $this->_base_currency != $base_currency) {

			// Try to get the cached rates for the specified base currency, if any
			$this->_current_rates = $this->get_stored_rates($this->target_date);
			if(is_array($this->_current_rates) && !empty($this->_current_rates) && (count($this->_current_rates) > 1)) {
				$this->_current_rates = $this->rebase_rates($this->_current_rates, $base_currency);
				//return $this->_current_rates;
			}

			// Fetch exchange rates
			$ecb_exchange_rates = $this->fetch_all_rates();
			if($ecb_exchange_rates === false) {
				return null;
			}

			// Debug
			//var_dump($ecb_exchange_rates);die();

			// ECB rates are returned as JSON representation of an array of objects.
			// We need to transform it into an array of currency => rate pairs
			$exchange_rates = $this->decode_rates($ecb_exchange_rates);

			// Debug
			//var_dump($exchange_rates);die();

			if(!is_array($exchange_rates)) {
				$this->add_error(self::ERR_UNEXPECTED_ERROR_FETCHING_EXCHANGE_RATES,
												 __('An unexpected error occurred while fetching exchange rates ' .
														'from ECB. The most common cause of this issue is the ' .
														'absence of PHP CURL extension. Please make sure that ' .
														'PHP CURL is installed and configured in your system.',
														Definitions::TEXT_DOMAIN));
				return array();
			}

			// If the exchange rates array cointains one element, it means that
			// the rates were not retrieved correctly. In such case, there is no point
			// in storing them
			if(count($exchange_rates) > 1) {
				// Cache the exchange rates
				$this->store_rates('EUR', $this->target_date, $exchange_rates);
			}

			// Since we didn't get the exchange rates related to the base currency,
			// but in the default base currency used by the ECB, we need to
			// recalculate them against the base currency we would like to use
			$this->_current_rates = $this->rebase_rates($exchange_rates, $base_currency);
			$this->_base_currency = $base_currency;
		}
		return $this->_current_rates;
	}

	/**
	 * Recaculates the exchange rates using another base currency. This method
	 * is invoked because the rates fetched from ECB are relative to BitCoin,
	 * but another currency is most likely is used by WooCommerce.
	 *
	 * @param array exchange_rates The exchange rates retrieved from ECB.
	 * @param string base_currency The base currency against which the rates should
	 * be recalculated.
	 * @return array An array of currency => exchange rate pairs.
	 */
	private function rebase_rates(array $exchange_rates, $base_currency) {
		$recalc_rate = get_value($base_currency, $exchange_rates);
		//var_dump($base_currency, $exchange_rates);

		if(empty($recalc_rate)) {
			$this->add_error(self::ERR_BASE_CURRENCY_NOT_FOUND,
											 sprintf(__('Could not rebase rates against base currency "%s". ' .
																	'Currency not found in data returned by ECB.',
																	Definitions::TEXT_DOMAIN),
															 $base_currency));
			return null;
		}

		$result = array();
		foreach($exchange_rates as $currency => $rate) {
			$result[$currency] = $rate / $recalc_rate;
		}

		// Debug
		//var_dump($result); die();
		return $result;
	}

	/**
	 * Returns the exchange rate of a currency in respect to a base currency.
	 *
	 * @param string base_currency The code of the base currency.
	 * @param string currency The code of the currency for which to find the
	 * Exchange Rate.
	 * @return float
	 */
	protected function get_rate($base_currency, $currency) {
		$current_rates = $this->current_rates($base_currency);
		return get_value($currency, $current_rates);
	}

	/**
	 * Returns the last day of a quarter.
	 *
	 * @param string last_day_of_quarter A string indicating a quarter. It must
	 * have the format YYYY-Q (year-quarter).
	 * @return string A string representing the last day of a quarter, in YYYY-MM-DD
	 * format.
	 * @throws InvalidArgumentException if an invalid string is passed as a
	 * parameter.
	 */
	protected function get_last_day_of_quarter($last_of_quarter) {
		$last_of_quarter_elements = explode('-', $last_of_quarter);

		$quarter_year = array_shift($last_of_quarter_elements);
		$quarter_number = array_shift($last_of_quarter_elements);
		if(!is_numeric($quarter_year) ||
			 !is_numeric($quarter_number) ||
			 (($quarter_number < 1) && ($quarter_number > 4))) {
			throw new \InvalidArgumentException(sprintf(__('Invalid "last day of quarter" argument ' .
																										 'received: "%s". The argument must be in ' .
																										 'YYYY-Q format, and the quarter number ' .
																										 'must be between 1 and 4.',
																										 Definitions::TEXT_DOMAIN),
																									$last_of_quarter));
		}
		$quarter_last_month = $quarter_number * 3;
		$last_day_of_quarter = date('Y-m-t', strtotime("$quarter_year-{$quarter_last_month}-01"));

		// Debug
		//var_dump($last_day_of_quarter);die();
		return $last_day_of_quarter;
	}

	/**
	 * Class constructor.
	 *
	 * @param array An array of Settings that can be used to override the ones
	 * currently saved in the configuration.
	 */
	public function __construct($settings = null) {
		parent::__construct($settings);

		$this->logger = WC_Aelia_EU_VAT_Assistant::instance()->get_logger();

		// If a specific target date was passed, take it
		$target_date = get_value('target_date', $settings);

		if(empty($target_date)) {
			// Check if the "last of quarter" parameter was passed. If passed, it must
			// have the format YYYY-Q (year-quarter) and it will be used to determine
			// the last day of the quarter
			$last_of_quarter = get_value('last_of_quarter', $settings, '');
			if(!empty($last_of_quarter)) {
				$target_date = $this->get_last_day_of_quarter($last_of_quarter);
			}
		}

		// If no date was passed at all, take today
		if(empty($target_date)) {
			$target_date = date('Y-m-d');
		}
		$this->target_date = $target_date;
	}

	/**
	 * Initialises an instance of the model using the last day of the specified
	 * quarter as a target date. This is a convenience method, to avoid having to
	 * create an array of settings and pass it to the class constructor.
	 *
	 * @param int year A year, in YYYY format.
	 * @param int quarter A quarter. It must be between 1 and 4.
	 * @return \Aelia\WC\EU_VAT_Assistant\Exchange_Rates_ECB_Historical_Model.
	 */
	public static function init_for_last_day_of_quarter($year, $quarter) {
		$settings = array(
			'last_of_quarter' => "{$year}-{$quarter}",
		);

		// Initialise and return the instance
		$instance = new self($settings);
		return $instance;
	}

	/**
	 * Returns all the exchange rates retrieved by the provider for the specified
	 * target date. Rates are rebased against the specified base currency.
	 *
	 * @param string target_date The target date.
	 * @param string base_currency The base currency against which the exchange
	 * rates will be rebased.
	 * @return array An array of currency => exchange rate pairs.
	 */
	public static function get_rates_for_date($target_date, $base_currency = 'EUR') {
		$instance = new self(array('target_date' => $target_date));
		return $instance->current_rates($base_currency);
	}
}
