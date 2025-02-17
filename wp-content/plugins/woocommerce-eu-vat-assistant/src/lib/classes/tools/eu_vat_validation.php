<?php
namespace Aelia\WC\EU_VAT_Assistant;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use \nusoap_client;
use \wsdl;

/**
 * Handles the validation of EU VAT numbers using the VIES service.
 */
class EU_VAT_Validation extends \Aelia\WC\Base_Class {
	/**
	 * An associative array of country code => EU VAT prefix pairs.
	 * @var array
	 */
	protected static $vat_country_prefixes;

	/**
	 * The errors generated by the class.
	 * @var array
	 */
	protected $errors = array();

	/**
	 * The VAT prefix that will be passed for validation.
	 * @var string
	 */
	protected $vat_prefix;

	/**
	 * The VAT number that will be passed for validation.
	 * @var string
	 */
	protected $vat_number;

	/**
	 * The requester VAT prefix that will be passed for validation.
	 * @var string
	 * @since 1.10.1.191108
	 */
	protected $requester_vat_prefix;

	/**
	 * The requester VAT number that will be passed for validation.
	 * @var string
	 * @since 1.10.1.191108
	 */
	protected $requester_vat_number;

	// @var bool Indicates if debug mode is active.
	protected $debug_mode;

	// @vat string The minimum length of an EU VAT number.
	// @since 1.9.7.190221
	const MIN_VAT_NUMBER_LENGTH = 8;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		$euva = WC_Aelia_EU_VAT_Assistant::instance();
		$this->debug = $euva->debug_mode();
		$this->logger = $euva->get_logger();

		$this->text_domain = Definitions::TEXT_DOMAIN;
	}

	/**
	 * Factory method.
	 */
	public static function factory() {
		return new static();
	}

	/**
	 * Returns a list of errors occurred during the validation of a VAT number.
	 *
	 * @return array
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Returns sn associative array of country code => EU VAT prefix pairs.
	 *
	 * @return array
	 */
	protected static function get_vat_country_prefixes() {
		if(empty(self::$vat_country_prefixes)) {
			self::$vat_country_prefixes = array();
			foreach(WC_Aelia_EU_VAT_Assistant::instance()->get_eu_vat_countries() as $country_code) {
				self::$vat_country_prefixes[$country_code] = $country_code;
			}

			// Correct vat prefixes that don't match the country code and add some
			// extra ones
			// Greece
			self::$vat_country_prefixes['GR'] = 'EL';
			// Isle of Man
			self::$vat_country_prefixes['IM'] = 'GB';
			// Monaco
			self::$vat_country_prefixes['MC'] = 'FR';
		}

		return apply_filters('wc_aelia_euva_vat_country_prefixes', self::$vat_country_prefixes);
	}

	/**
	 * Parses a VAT number, removing special characters and the country prefix, if
	 * any.
	 */
	public function parse_vat_number($vat_number) {
		// Remove special characters
		$vat_number = strtoupper(str_replace(array(' ', '-', '_', '.'), '', $vat_number));

		// Remove country code if set at the begining
		$prefix = substr($vat_number, 0, 2);
		if(in_array($prefix, array_values(self::get_vat_country_prefixes()))) {
			$vat_number = substr($vat_number, 2);
		}
		if(empty($vat_number)) {
			return false;
		}
		return $vat_number;
	}

	/**
	 * Returns the VAT prefix used by a specific country.
	 *
	 * @param string country A country code.
	 * @return string|false
	 */
	public function get_vat_prefix($country) {
		$country_prefixes = self::get_vat_country_prefixes();
		return get_value($country, $country_prefixes, false);
	}

	/**
	 * Caches the validation result of a VAT number for a limited period of time.
	 * This will improve performances when customers will place new orders in a
	 * short timeframe, by reducing the amount of calls to the VIES service.
	 *
	 * @param string vat_prefix The VAT prefix.
	 * @param string vat_number The VAT number.
	 * @param array result The validation result.
	 */
	protected function cache_validation_result($vat_prefix, $vat_number, $result) {
		set_transient(Definitions::TRANSIENT_EU_NUMBER_VALIDATION_RESULT . $vat_prefix . $vat_number,
									$result, apply_filters('wc_aelia_euva_vat_validation_cache_duration', 1 * HOUR_IN_SECONDS, $vat_prefix, $vat_number, $result));
	}

	/**
	 * Returns the cached result of a VAT number validation, if it exists.
	 *
	 * @param string vat_prefix The VAT prefix.
	 * @param string vat_number The VAT number.
	 * @return array|bool An array with the validatin result, or false if a cached
	 * result was not found.
	 */
	protected function get_cached_validation_result($vat_prefix, $vat_number) {
		// In debug mode, behave as if nothing was cached
		if($this->debug_mode) {
			return false;
		}
		return get_transient(Definitions::TRANSIENT_EU_NUMBER_VALIDATION_RESULT . $vat_prefix . $vat_number);
	}

	/**
	 * Returns the minimum length of a VAT number for a given country.
	 *
	 * @param string $country
	 * @return int
	 * @since 1.12.4.200131
	 */
	protected static function get_minimum_vat_number_length($country) {
		$min_lengths = array(
			'AT' => 9,
			'BE' => 10,
			'BG' => 9,
			'CY' => 9,
			'CZ' => 8,
			'DE' => 9,
			'DK' => 8,
			'EE' => 9,
			'EL' => 9,
			'ES' => 9,
			'FI' => 8,
			'FR' => 11,
			'GB' => 5,
			'HR' => 11,
			'HU' => 8,
			'IE' => 8,
			'IT' => 11,
			'LT' => 9,
			'LV' => 11,
			'LU' => 8,
			'MT' => 8,
			'NL' => 12,
			'PL' => 10,
			'PT' => 9,
			'RO' => 2,
			'SE' => 12,
			'SI' => 8,
			'SK' => 10,
		);

		return apply_filters('wc_aelia_euva_min_vat_number_length', isset($min_lengths[$country]) ? $min_lengths[$country] : self::MIN_VAT_NUMBER_LENGTH, $country);
	}

	/**
	 * Validates the argument passed for validation, transforming a countr code
	 * into a VAT prefix and checking the VAT number before it's used for a VIES
	 * request.
	 *
	 * @param string country A country code. It will be used to determine the VAT
	 * number prefix.
	 * @param string vat_number A VAT number.
	 * @param string requester_country The country code of the requester.
	 * @param string requester_vat_number The VAT number of the requester.
	 * @return bool
	 */
	protected function prepare_request_arguments($country, $vat_number, $requester_country = null, $requester_vat_number = null) {
		// Some preliminary formal validation, to prevent unnecessary requests with
		// clearly invalid data
		$this->vat_number = $this->parse_vat_number($vat_number);
		if($this->vat_number == false) {
			$this->errors[] = implode(' ', array(
				__('An empty or invalid VAT number was passed for validation.', $this->text_domain),
				sprintf(__('Received VAT number: "%1$s".', $this->text_domain), $vat_number),
			));
		}

		// Get the minimum length expected for a EU VAT number for customer's country
		// @since 1.12.6.200212
		$min_vat_number_length = self::get_minimum_vat_number_length($country);
		// If the VAT number includes the country code at the beginning,
		// the minimum length should be increased, as those two characters
		// don't count
		if(substr($this->vat_number, 0, 2) === $country) {
			$min_vat_number_length += 2;
		}

		// Don't validate VAT numbers that are too short, as they would be invalid anyway
		// @since 1.9.7.190221
		if(strlen($this->vat_number) < $min_vat_number_length) {
			$this->errors[] = sprintf(__('An invalid VAT number was passed for validation. ' .
																	 'A VAT number for country "%1$s" should contain a minimum of %2$d digits, excluding the ' .
																	 'country prefix. Received VAT number: "%3$s".',
																	 $this->text_domain),
																$country,
																$min_vat_number_length,
																$vat_number);
		}

		// Validate the country prefix for the VAT number
		$this->vat_prefix = $this->get_vat_prefix($country);
		if(empty($this->vat_prefix)) {
			$this->errors[] = sprintf(__('A VAT prefix could not be found for the specified country. ' .
																	 'Received country code: "%s".',
																	 $this->text_domain),
																$country);
		}

		// Validate the requester VAT number
		// @since 1.10.1.191108
		$this->requester_vat_prefix = '';
		$this->requester_vat_number = '';
		$requester_vat_number_valid = true;

		if(!empty($requester_vat_number)) {
			// Get the minimum length expected for a EU VAT number for merchant's country
			// @since 1.12.6.200212
			$min_vat_number_length = self::get_minimum_vat_number_length($requester_country);

			$this->requester_vat_number = $requester_vat_number;
			// If the VAT number includes the country code at the beginning,
			// the minimum length should be increased, as those two characters
			// don't count
			if(substr($this->requester_vat_number, 0, 2) === $requester_country) {
				$min_vat_number_length += 2;
			}

			// Don't validate VAT numbers that are too short, as they would be invalid anyway
			if(strlen($this->requester_vat_number) < $min_vat_number_length) {
				$this->logger->warning(implode(' ', array(
					__('The requester VAT number configured in the VAT Number Validation settings is too short.', $this->text_domain),
					sprintf(__('A VAT number for country "%1$s" should contain a minimum of %2$d digits, excluding the country prefix.',$this->text_domain), $requester_country, $min_vat_number_length),
					sprintf(__('Requester VAT number: "%1$s".', $this->text_domain), $requester_vat_number),
				)));

				$requester_vat_number_valid = false;
			}

			// Validate the requester VAT number prefix
			// @since 1.10.1.191108
			if($requester_vat_number_valid && !empty($requester_country)) {
				// Validate the country prefix for the requester VAT number
				$this->requester_vat_prefix = $this->get_vat_prefix($requester_country);
				if(empty($this->requester_vat_prefix)) {
					$this->logger->warning(implode(' ', array(
						__('A VAT prefix could not be found for the requester country.', $this->text_domain),
						sprintf(__('Requester country code: "%1$s".', $this->text_domain), $requester_country),
					)));

					$requester_vat_number_valid = false;
				}
			}

			// Ignore the requester VAT number if it's not valid. This will allow the
			// request to the VIES system to go through without it
			// @since 1.10.1.191108
			if(!$requester_vat_number_valid) {
				$this->requester_vat_prefix = '';
				$this->requester_vat_number = '';

				$this->logger->warning(__('Requester VAT number is not valid. Performing validation without it.', $this->text_domain), array(
					'Requester Country' => $requester_country,
					'Requester VAT Number' => $requester_vat_number,
				));
			}
		}

		// Log all the error messages that occurred during the validation
		// @since 1.9.7.190221
		foreach($this->errors as $error_message) {
			$this->logger->info($error_message);
		}

		return empty($this->errors);
	}

	/**
	 * Checks if a cached response is valid. In some older versions, an incorrect
	 * response was cached and, when returned, caused the plugin to consider invalid
	 * numbers that were actually valid.
	 *
	 * @param mixed $response The cached response.
	 * @return bool
	 */
	protected function valid_cached_response($response) {
		return ($response != false) &&
					 is_array($response) &&
					 (get_value('valid', $response) == 'true');
	}

	/**
	 * Validates a VAT number.
	 *
	 * @param string country The country code to which the VAT number belongs.
	 * @param string vat_number The VAT number to validate.
	 * @param string requester_country The country code of the requester.
	 * @param string requester_vat_number The VAT number of the requester.
	 * @return array|bool An array with the validation response returned by the
	 * VIES service, or false when the request could not be sent for some reason.
	 * @link http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl
	 */
	public function validate_vat_number($country, $vat_number, $requester_country = null, $requester_vat_number = null) {
		$this->errors = array();

		// The arguments must be prepared (e.g. length checked, country prefix added, etc) before sending the request
		// and before checking if a cached result exists
		// @since 1.10.1.191108
		if(!$this->prepare_request_arguments($country, $vat_number, $requester_country, $requester_vat_number)) {
			return array(
				'valid' => false,
				'errors' => array(Definitions::VAT_NUMBER_VALIDATION_NOT_VALID),
				'raw_response' => null,
			);
		}

		// Return a cached response, if one exists. Faster than sending a SOAP request.
		$cached_response = $this->get_cached_validation_result($this->vat_prefix, $this->vat_number);
		if($this->valid_cached_response($cached_response)) {
			return $cached_response;
		}

		// Debug
		//var_dump($country, $vat_number, $this->vat_prefix, $this->vat_number);die();

		// Cache the WSDL
		$wsdl = get_transient('VIES_WSDL');
		if(empty($wsdl) || $this->debug_mode) {
			$wsdl = new wsdl('http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl', '', '', '', '', 5);
			// Cache the WSDL for one minute. Sometimes VIES returns an invalid WSDL,
			// caching it for too long could cause the whole validation system to fail
			set_transient('VIES_WSDL', $wsdl, 60);
		}

		// Create SOAP client
		$client = new nusoap_client($wsdl, 'wsdl');
		// Ensure that UTF-8 encoding is used, so that the client won't crash when
		// "odd" characters are used
		$client->decode_utf8 = false;
		$client->soap_defencoding = 'UTF-8';

		// Using CURL seems to throw an "error 60 - Could not validate host certificate". Removing
		// this line solves the issue
		// @since 1.13.1.200319
		// @link https://wordpress.org/support/topic/vat-number-not-validating-for-requester-country-greece/
		// @link https://wordpress.org/support/topic/vat-number-not-validating-for-requester-country-poland/
		//$client->setUseCurl(true);

		// Check if any error occurred initialising the SOAP client. We won't be able
		// to continue, in such case.
		$error = $client->getError();
		if($error) {
			$this->errors[] = __('An error occurred initialising SOAP client.', $this->text_domain) .
												' ' .
												sprintf(__('Error message: "%s".', $this->text_domain), $error);
			// Log the initialisation error
			// @since 1.9.7.190221
			$this->logger->error(__('An error occurred initialising SOAP client.', $this->text_domain), array(
				'Error message' => $error,
			));

			return false;
		}

		$request_args = array(
			'countryCode' => $this->vat_prefix,
			'vatNumber' => $this->vat_number,
		);

		// Add the Requester details, if specified
		// @since 1.9.0.181022
		if(!empty($this->requester_vat_prefix) && !empty($this->requester_vat_number)) {
			$request_args['requesterCountryCode'] = $this->requester_vat_prefix;
			$request_args['requesterVatNumber'] = $this->requester_vat_number;
		}

		// Log the request arguments
		// @since 1.10.1.191108
		$this->logger->debug(__('VAT number validation request.', $this->text_domain), array(
			'Request Arguments' => $request_args,
		));

		// Call the VIES service to validate the VAT number
		$response = $client->call('checkVatApprox', $request_args);

		$this->logger->debug(__('VAT number validation complete.', $this->text_domain), array(
			'Country Code' => $this->vat_prefix,
			'VAT Number' => $this->vat_number,
			'VIES Response' => $response,
		));

		if(is_array($response)) {
			// Change all the keys to lower-case. This is to avoid issues with keys being
			// returned like "faultString", "FaultString", "faultstring" by different versions
			// of the VIES service
			// @since 1.9.16.191004
			$response = array_change_key_case($response, CASE_LOWER);
			$result = array(
				'valid' => isset($response['valid']) ? ($response['valid'] === 'true') : false,
				'company_name' => get_arr_value('name', $response, ''),
				'company_address' => get_arr_value('address', $response, ''),
				'errors' => array(get_arr_value('faultstring', $response, '')),
				'raw_response' => $response,
			);
		}
		else {
			$result = array(
				'valid' => null,
				'company_name' => null,
				'company_address' => null,
				'errors' => $this->get_errors(),
				'raw_response' => null,
			);
		}

		// Cache response for valid VAT numbers
		if(($result['valid'] === 'true') && !$this->debug_mode) {
			$this->cache_validation_result($this->vat_prefix, $this->vat_number, $result);
		}
		return $result;
	}
}
