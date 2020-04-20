<?php
namespace Aelia\WC\EU_VAT_Assistant;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use \Exception;

/**
 * Retrieves the exchange rates from the HM Revenue and Customs (UK).
 *
 * @link https://www.gov.uk/government/publications/exchange-rates-for-customs-and-vat-monthly
 * @link http://www.hmrc.gov.uk/softwaredevelopers/2014-exrates.htm
 */
class Exchange_Rates_HMRC_Model extends \Aelia\WC\ExchangeRatesModel {
	/**
	 * URLs template to query HMRC. In January 2015, they apparently
	 * replaced all the dashes in the URL with underscores, without notice and
	 * without reason.
	 * @var string
	 */
	protected $hmrc_api_rates_urls = array(
		// URL until the end of 2014
		'http://www.hmrc.gov.uk/softwaredevelopers/rates/exrates-monthly-%s.xml',
		// URL introduced in February 2015
		'http://www.hmrc.gov.uk/softwaredevelopers/rates/excrates_monthly%s.xml',
		// URL introduced in January 2015
		'http://www.hmrc.gov.uk/softwaredevelopers/rates/exrates_monthly_%s.xml',
	);

	/**
	 * Tranforms the exchange rates received from HMRC into an array of
	 * currency code => exchange rate pairs.
	 *
	 * @param string hmrc_rates The XML received from HMRC.
	 * @retur array
	 */
	protected function decode_rates($hmrc_rates) {
		$exchange_rates = array();

		foreach($hmrc_rates->exchangeRate as $rate) {
			$exchange_rates[(string)$rate->currencyCode] = (float)$rate->rateNew;
		}
		// HMRC feed is based against GBP, but it doesn't contain such currency. We
		// can safely add it manually, with an exchange rate of 1
		$exchange_rates['GBP'] = 1;
		return $exchange_rates;
	}

	/**
	 * Fetches all exchange rates from HMRC API.
	 *
	 * @return object|bool An object containing the response from Open Exchange, or
	 * False in case of failure.
	 */
	private function fetch_all_rates() {
		$errors = array(
			self::ERR_ERROR_RETURNED => array(),
			self::ERR_EXCEPTION_OCCURRED => array(),
		);
		foreach($this->hmrc_api_rates_urls as $url_template) {
			// The feed URL changes every month
			$feed_url = sprintf($url_template, date('my'));
			try {
				$response = \Httpful\Request::get($feed_url)
					->expectsXml()
					->send();

				// Debug
				//var_dump("HMRC RATES RESPONSE:", $response); die();
				if($response->hasErrors()) {
					// OpenExchangeRates sends error details in response body
					if($response->hasBody()) {
						$response_data = $response->body;

						$errors[self::ERR_ERROR_RETURNED][] =
							sprintf(__('Error returned by HMRC. ' .
												 'Feed URLs used for retrieval: %s. Error code: %s. Error message: %s - %s.',
												 Definitions::TEXT_DOMAIN),
											'"' . implode('", "', $this->hmrc_api_rates_urls) . '"',
											$response_data->status,
											$response_data->message,
											$response_data->description);
					}
				}
				else {
					// Return as soon as a good response is received
					return $response->body;
				}
			}
			catch(Exception $e) {
				$errors[self::ERR_EXCEPTION_OCCURRED][] =
					sprintf(__('Exception occurred while retrieving the exchange rates from HMRC. ' .
										 'Feed URLs used for retrieval: %s. Error message: %s.',
										 Definitions::TEXT_DOMAIN),
									'"' . implode('", "', $this->hmrc_api_rates_urls) . '"',
									$e->getMessage());
			}
		}
		// If we reach this point, then it means that the model could not retrieve
		// valid exchange rates from any of the URLs, so we can just add the errors
		// and return "false".
		foreach($errors as $error_type => $messages) {
			foreach($messages as $error_message) {
				$this->add_error($error_type, $error_message);
			}
		}
		return false;
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

			$cache_key = md5(get_class($this)) . $base_currency;
			// Try to get the cached rates for the specified base currency, if any
			$this->_current_rates = $this->get_cached_exchange_rates($cache_key);
			if(!empty($this->_current_rates)) {
				return $this->_current_rates;
			}

			// Fetch exchange rates
			$hmrc_exchange_rates = $this->fetch_all_rates();
			if($hmrc_exchange_rates == false) {
				return null;
			}

			// Debug
			//var_dump($hmrc_exchange_rates);die();

			// HMRC rates are returned as JSON representation of an array of objects.
			// We need to transform it into an array of currency => rate pairs
			$exchange_rates = $this->decode_rates($hmrc_exchange_rates);

			// Debug
			//var_dump($exchange_rates);die();

			if(!is_array($exchange_rates)) {
				$this->add_error(self::ERR_UNEXPECTED_ERROR_FETCHING_EXCHANGE_RATES,
												 __('An unexpected error occurred while fetching exchange rates ' .
														'from HMRC. The most common cause of this issue is the ' .
														'absence of PHP CURL extension. Please make sure that ' .
														'PHP CURL is installed and configured in your system.',
														Definitions::TEXT_DOMAIN));
				return array();
			}

			// Since we didn't get the exchange rates related to the base currency,
			// but in the default base currency used by OpenExchange, we need to
			// recalculate them against the base currency we would like to use
			$this->_current_rates = $this->rebase_rates($exchange_rates, $base_currency);
			$this->_base_currency = $base_currency;

			// Cache the exchange rates
			$this->cache_exchange_rates($cache_key, $this->_current_rates);
		}
		return $this->_current_rates;
	}

	/**
	 * Recaculates the exchange rates using another base currency. This method
	 * is invoked because the rates fetched from HMRC are relative to BitCoin,
	 * but another currency is most likely is used by WooCommerce.
	 *
	 * @param array exchange_rates The exchange rates retrieved from HMRC.
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
																	'Currency not found in data returned by HMRC.',
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
	 * Class constructor.
	 *
	 * @param array An array of Settings that can be used to override the ones
	 * currently saved in the configuration.
	 * @return Exchange_Rates_HMRC_Model.
	 */
	public function __construct($settings = null) {
		parent::__construct($settings);
	}
}
