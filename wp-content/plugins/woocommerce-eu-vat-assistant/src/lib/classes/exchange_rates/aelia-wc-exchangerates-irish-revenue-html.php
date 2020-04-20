<?php
namespace Aelia\WC\EU_VAT_Assistant;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use \Exception;

/**
 * Retrieves the exchange rates from the European Central Bank.
 *
 * @link http://www.revenue.ie/en/customs/businesses/importing/exchange-rates/
 */
class Exchange_Rates_IrishRevenueHTML_Model extends \Aelia\WC\ExchangeRatesModel {
	// @var string The URL template to use to get rates from Irish Revenue's website (parsing the HTML page)
	private $irish_revenue_html_rates_url = 'http://www.revenue.ie/en/customs/businesses/importing/exchange-rates/';

	// @var array Maps the currency names used by Irish Revenue with the corresponding codes.
	protected $currency_names_map = array(
		'US Dollar' => 'USD',
		'Sterling' => 'GBP',
		'Japanese Yen' => 'JPY',
		'Swiss Franc' => 'CHF',
		'Danish Krone' => 'DKK',
		'Swedish Krona' => 'SEK',
		'Norwegian Krone' => 'NOK',
		'Czech Koruna' => 'CZK',
		'Hungarian Forint' => 'HUF',
		'Polish Zloty' => 'PLN',
		'Canadian Dollar' => 'CAD',
		'Australian Dollar' => 'AUD',
		'New Zealand Dollar' => 'NZD',
		'Chinese Renminbi' => 'RMB',
		'Hong Kong Dollar' => 'HKD',
		'Indonesian Rupiah' => 'IDR',
		'South Korean Won' => 'KRW',
		'Lithuanian Litas' => 'LTL',
		'Malaysian Ringgit' => 'MYR',
		'Philippines Peso' => 'PHP',
		'Romanian Leu' => 'RON',
		'Russian Rouble' => 'RUB',
		'Singapore Dollar' => 'SGD',
		'South African Rand' => 'ZAR',
		'Thailand Baht' => 'THB',
		'Turkish Lira' => 'TRY',
		'Bulgarian Lev' => 'BGN',
		'Croatian Kuna' => 'HRK',
		'Algerian Dinar' => 'DZD',
		'Bahrain Dinar' => 'BHD',
		'Botswana Pula' => 'BWP',
		'CFA Franc' => 'XAF',
		'Costa Rican Colon' => 'CRC',
		'Egyptian Pound' => 'EGP',
		'Ghana Cedi' => 'GHS',
		'Gibraltar Pound' => 'GIP',
		'Indian Rupee' => 'INR',
		'Iraqi Dinar' => 'IQD',
		'Israeli Shekel' => 'ILS',
		'Kenyan Shilling' => 'KES',
		'Kuwaiti Dinar' => 'KWD',
		'Lebanese Pound' => 'LBP',
		'Malawi Kwacha' => 'MWK',
		'Mexican Peso' => 'MXN',
		'Moroccan Dirham' => 'MAD',
		'Nigerian Naira' => 'NGN',
		'Pakistan Rupee' => 'PKR',
		'Qatar Riyal' => 'QAR',
		'Saudi Arabia Riyal' => 'SAR',
		'Sri Lankan Rupee' => 'LKR',
		'Syrian Pound' => 'SYP',
		'Taiwan Dollar' => 'TWD',
		'Tanzanian Shilling' => 'TZS',
		'Trinidad/Tobago Dollar' => 'TTD',
		'Tunisian Dinar' => 'TND',
		'UAE Dirham' => 'AED',
		'Yemen Rial' => 'YER',
		'Omani Rial' => 'OMR',
		'Venezuelan Bolivar Fuerte' => 'VEF',
		'Argentina Peso' => 'ARS',
		'Colombian Peso' => 'COP',
		'Bermuda Dollar' => 'BMD',
		'Brazil Real' => 'BRL',
		'Brunei Dollar' => 'BND',
		'Fiji Dollar' => 'FJD',
		'Namibia Dollar' => 'NAD',
		'Libyan Dinar' => 'LYD',
		'Chilean Peso' => 'CLP',
		'Vietnam Dong' => 'VND',
		'Peruvian New Sol' => 'PEN',
		'Iran Rial' => 'IRR',
		'Tonga is Paanga' => 'TOP',
		'Nepalese Rupee' => 'NPR',
		'Serbia Dinar' => 'RSD',
		'Icelandic Krona' => 'ISK',
		'Maur Rupee' => 'MUR',
		'Ukraine Hryvnia' => 'UAH',
	);

	/**
	 * Tranforms the exchange rates retrieved from Irish Revenue site into an array of
	 * currency code => exchange rate pairs.
	 *
	 * @param string $irish_revenue_rates_html The HTML received from Irish Revenue webpage.
	 * @retur array
	 */
	protected function decode_rates($irish_revenue_rates_html) {
		$html = new \DOMDocument;

		// Transform the HTML from the Revenue page into a DOM object
		$html->loadHTML($irish_revenue_rates_html);
		//var_dump($html);die();

		$exchange_rates_tmp = array();
		$xpath = new \DOMXPath($html);

		// The HTML is not only heave, but it also lacks clear identifiers for the
		// exchange rate elements. We have to find the first currency denomination
		// and move to its parent <tr>
		$nodes = $xpath->query('//ancestor::tr[td[@headers="denomination"]]');
		foreach($nodes as $node) {
			$values = array();
			foreach($node->childNodes as $child) {
				$values[] = trim($child->nodeValue);
			}
			$values = array_filter($values);

			$currency_name = array_shift($values);
			foreach($values as $rate) {
				if(is_numeric($rate)) {
					$exchange_rates_tmp[$currency_name] = $rate;
				}
			}
		}

		$exchange_rates = array();
		foreach($exchange_rates_tmp as $currency_name => $rate) {
			if(isset($this->currency_names_map[$currency_name])) {
				$exchange_rates[$this->currency_names_map[$currency_name]] = $rate;
			}
		}
		// Irish Revenue feed is based against EUR, but it doesn't contain such currency. We
		// can safely add it manually, with an exchange rate of 1
		$exchange_rates['EUR'] = 1;
		return $exchange_rates;
	}

	/**
	 * Fetches all exchange rates from IrishRevenueHTML API.
	 *
	 * @return object|bool An object containing the response from Open Exchange, or
	 * False in case of failure.
	 */
	private function fetch_all_rates() {
		try {
			$response = \Httpful\Request::get($this->irish_revenue_html_rates_url)
				->expectsHtml()
				->send();

			// Debug
			//var_dump("IrishRevenueHTML RATES RESPONSE:", $response); die();
			if($response->hasErrors()) {
				// OpenExchangeRates sends error details in response body
				if($response->hasBody()) {
					$response_data = $response->body;

					$this->add_error(self::ERR_ERROR_RETURNED,
													 sprintf(__('Error returned fetching Irish Revenue HTML page. ' .
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
											 sprintf(__('Exception occurred while retrieving the exchange rates from Irish Revenue HTML page. ' .
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

			$cache_key = md5(get_class($this)) . $base_currency;
			// Try to get the cached rates for the specified base currency, if any
			$this->_current_rates = $this->get_cached_exchange_rates($cache_key);
			if(!empty($this->_current_rates)) {
				return $this->_current_rates;
			}

			// Fetch exchange rates
			$irishrevenue_exchange_rates = $this->fetch_all_rates();
			if($irishrevenue_exchange_rates === false) {
				return null;
			}

			// Debug
			//var_dump($irishrevenue_exchange_rates);die();

			// IrishRevenueHTML rates are returned as JSON representation of an array of objects.
			// We need to transform it into an array of currency => rate pairs
			$exchange_rates = $this->decode_rates($irishrevenue_exchange_rates);
			if(!is_array($exchange_rates)) {
				$this->add_error(self::ERR_UNEXPECTED_ERROR_FETCHING_EXCHANGE_RATES,
												 __('An unexpected error occurred while fetching exchange rates ' .
														'from IrishRevenueHTML. The most common cause of this issue is the ' .
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
	 * is invoked because the rates fetched from IrishRevenueHTML are relative to BitCoin,
	 * but another currency is most likely is used by WooCommerce.
	 *
	 * @param array exchange_rates The exchange rates retrieved from IrishRevenueHTML.
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
																	'Currency not found in data returned by IrishRevenueHTML.',
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
	 * @return Exchange_Rates_IrishRevenueHTML_Model.
	 */
	public function __construct($settings = null) {
		parent::__construct($settings);
	}
}
