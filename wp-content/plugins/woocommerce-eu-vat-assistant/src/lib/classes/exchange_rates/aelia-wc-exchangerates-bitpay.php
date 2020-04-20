<?php
namespace Aelia\WC\EU_VAT_Assistant;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use \Exception;

/**
 * Retrieves the exchange rates from BitPay.
 *
 * @link https://bitpay.com/api#resource-Rates
 */
class Exchange_Rates_BitPay_Model extends \Aelia\WC\ExchangeRatesModel {
	// @var string The URL template to use to query BitPay
	private $bitpay_api_rates_url = 'https://bitpay.com/api/rates';

	/**
	 * Tranforms the exchange rates received from BitPay into an array of
	 * currency code => exchange rate pairs.
	 *
	 * @param string bitpay_rates The JSON received from BitPay.
	 * @retur array
	 */
	protected function decode_rates($bitpay_rates) {
		$exchange_rates = array();
		foreach($bitpay_rates as $rates_obj) {
			$exchange_rates[$rates_obj->code] = $rates_obj->rate;
		}

		return $exchange_rates;
	}

	/**
	 * Fetches all exchange rates from BitPay API.
	 *
	 * @return object|bool An object containing the response from Open Exchange, or
	 * False in case of failure.
	 */
	private function fetch_all_rates() {
		try {
			$response = \Httpful\Request::get($this->bitpay_api_rates_url)
				->expectsJson()
				->send();

			// Debug
			//var_dump("BITPAY RATES RESPONSE:", $response); die();
			if($response->hasErrors()) {
				// OpenExchangeRates sends error details in response body
				if($response->hasBody()) {
					$response_data = $response->body;

					$this->add_error(self::ERR_ERROR_RETURNED,
													 sprintf(__('Error returned by BitPay. ' .
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
											 sprintf(__('Exception occurred while retrieving the exchange rates from BitPay. ' .
																	'Error message: %s.',
																	Definitions::TEXT_DOMAIN),
															 $e->getMessage()));
			return null;
		}
	}

	/**
	 * Returns current exchange rates for the specified currency.
	 *
	 * @param string base_currency The base currency.
	 * @return array An array of Currency => Exchange Rate pairs.
	 */
	private function current_rates($base_currency) {
		if(empty($this->_current_rates) ||
			 $this->_base_currency != $base_currency) {

			$cache_key = get_class($this);
			// Try to get the cached rates for the specified base currency, if any
			$this->_current_rates = $this->get_cached_exchange_rates($cache_key);
			if(!empty($this->_current_rates)) {
				return $this->_current_rates;
			}

			// Fetch exchange rates
			$bitpay_exchange_rates = $this->fetch_all_rates();
			if(empty($bitpay_exchange_rates)) {
				return null;
			}

			// BitPay rates are returned as JSON representation of an array of objects.
			// We need to transform it into an array of currency => rate pairs
			$exchange_rates = $this->decode_rates($bitpay_exchange_rates);
			if(!is_array($exchange_rates)) {
				$this->add_error(self::ERR_UNEXPECTED_ERROR_FETCHING_EXCHANGE_RATES,
												 __('An unexpected error occurred while fetching exchange rates ' .
														'from BitPay. The most common cause of this issue is the ' .
														'absence of PHP CURL extension. Please make sure that ' .
														'PHP CURL is installed and configured in your system.',
														Definitions::TEXT_DOMAIN));
				return array();
			}

			// Cache the exchange rates
			$this->cache_exchange_rates($cache_key, $exchange_rates);

			// Recalculate exchange rates against the base currency we would like to use
			$this->_current_rates = $this->rebase_rates($exchange_rates, $base_currency);
			$this->_base_currency = $base_currency;
		}
		return $this->_current_rates;
	}

	/**
	 * Recaculates the exchange rates using another base currency. This method
	 * is invoked because the rates fetched from BitPay are relative to BitCoin,
	 * but another currency is most likely is used by WooCommerce.
	 *
	 * @param array exchange_rates The exchange rates retrieved from BitPay.
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
																	'Currency not found in data returned by BitPay.',
																	Definitions::TEXT_DOMAIN),
															 $base_currency));
			return null;
		}

		$result = array();
		foreach($exchange_rates as $currency => $rate) {
			$result[$currency] = $rate / $recalc_rate;
		}

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
	 * Class constructor.
	 *
	 * @param array An array of Settings that can be used to override the ones
	 * currently saved in the configuration.
	 * @return Exchange_Rates_BitPay_Model.
	 */
	public function __construct($settings = null) {
		parent::__construct($settings);
	}
}
