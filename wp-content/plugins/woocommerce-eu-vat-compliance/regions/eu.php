<?php

if (!defined('WC_EU_VAT_COMPLIANCE_DIR')) die('No direct access.');

class WC_VAT_Region_eu extends WC_VAT_Region {

	/**
	 * Get a list of countries that define the region
	 *
	 * @return Array
	 */
	public function get_countries() {
	
		$eu_countries = WC()->countries->get_european_union_countries();
		
		// GB added because it is part of a common VAT area until the end of 2020
		$extra_countries = array('MC', 'GB');
		
		// Isle of Man belongs with GB
		if (in_array('GB', $extra_countries)) $extra_countries[] = 'IM';
		
		return array_merge($eu_countries, $extra_countries);
	
	}
	
	/**
	 * Return the title for the region
	 *
	 * @param String $context - 'noun', 'adjective'
	 *
	 * @return String
	 */
	public function get_region_title($context) {
		if ('adjective' == $context) return __('EU', 'woocommerce-eu-vat-compliance');
		return __('the EU', 'woocommerce-eu-vat-compliance');
	}

	/**
	 * Get an array listing the minimum number of characters in a valid VAT number for the region's countries (and a default)
	 *
	 * @return Integer
	 */
	public function map_country_codes_to_minimum_characters() {
		// https://www.gov.uk/vat-eu-country-codes-vat-numbers-and-vat-in-other-languages
		return array(
			'RO' => 2,
			'CZ' => 8,
			'DK' => 8,
			'FI' => 8,
			'HU' => 8,
			'MT' => 8,
			'LU' => 8,
			'SI' => 8,
			'IE' => 8,
			'PL' => 10,
			'SK' => 10,
			'HR' => 11,
			'FR' => 11,
			'MC' => 11,
			'IT' => 11,
			'LV' => 11,
			'NL' => 12,
			'SE' => 12,
			// All others
			'default' => 9
		);
	}

	/**
	 * Given a VAT number in unspecified format, canonicalise it. This includes removing any standard country prefix.
	 *
	 * @param String $vat_number - the VAT number
	 * @param String $country	 - country the VAT number is intended for, if already definitely known
	 *
	 * @return String
	 */
	public function standardise_vat_number($vat_number, $country = '') {
	
		// Format the number canonically (remove spaces, hyphens, underscores, periods; make upper-case)
		$vat_number = parent::standardise_vat_number($vat_number);
	
		// Remove country prefix; including two possibilities for which the VAT prefix differs from the country code
		if (in_array(substr($vat_number, 0, 2), array_merge($this->get_countries(), array('EL', 'GB')))) {
			$vat_number = substr($vat_number, 2);
		}

		// https://www.gov.uk/vat-eu-country-codes-vat-numbers-and-vat-in-other-languages
		if ('BE' == $country && 9 == strlen($vat_number)) $vat_number = '0'.$vat_number;
	
		return $vat_number;
	}
	
	/**
	 * Return the VAT number prefix for the given country
	 *
	 * @param String $country
	 *
	 * @return String - the prefix
	 */
	public function get_vat_number_prefix($country) {
	
		$vat_prefix = $country;

		// Deal with exceptions
		switch ($country) {
			case 'GR' :
				$vat_prefix = 'EL';
			break;
			case 'IM' :
				$vat_prefix = 'GB';
			break;
			case 'MC' :
				$vat_prefix = 'FR';
			break;
		}

		return $vat_prefix;
	}
	
	/**
	 * @param String  $vat_prefix	- the country prefix to the VAT number
	 * @param String  $vat_number	- the VAT number (already canonicalised), minus any country prefix
	 * @param Boolean $force_simple	- force a non-extended lookup, even if in the saved options there is a VAT ID for the store
	 *
	 * @uses option woocommerce_eu_vat_store_id
	 *
	 * @return WP_Error|Array - an error object, or an array with key 'body' containing either (string)true or SERVER_BUSY, or an array with a fault code
	 */
	public function get_validation_result_from_network($vat_prefix, $vat_number, $force_simple = false) {

		// Preference: use VIES directly
		// Some code adapted from Diego Zanella, with contributions from Sven Auhagen

		// Enforce requirements of the nusoap library
		// if (version_compare(PHP_VERSION, '5.4', '<')) {
		// 	return new WP_Error('insufficient_php', 'This feature requires PHP 5.4 or later.');
		// }
		
		if (!class_exists('nusoap_base')) require_once(WC_EU_VAT_COMPLIANCE_DIR.'/nusoap/class.nusoap_base.php');
		// require_once(WC_EU_VAT_COMPLIANCE_DIR.'/vendor/autoload.php');
		
		$wsdl = get_transient('VIES_WSDL');
		if (1 || empty($wsdl) || (defined('WC_EU_VAT_DEBUG') && WC_EU_VAT_DEBUG)) {
			$wsdl = new wsdl('http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl', '', '', '', '', 5);
			// Cache, but not for too long (since sometimes something invalid is returned)
			set_transient('VIES_WSDL', $wsdl, 60);
		}

		// Create SOAP client. HTTPS not supported (verified Jan 2015; still the case Aug 2018)
		$client = new nusoap_client($wsdl, 'wsdl');
		
		// March 2020: a change at the VIES server end means that using curl results in "Fault code: No such operation: (HTTP GET PATH_INFO: /taxation_customs/vies/checkVatService)" for some VAT numbers, including from Poland and Greece. (Next day: all seems well again)
		// Of interest: https://wordpress.org/support/topic/vat-number-not-validating-for-requester-country-greece/ , https://wordpress.org/support/topic/vat-number-not-validating-for-requester-country-poland/
 		if (function_exists('curl_exec')) $client->setUseCurl(true);
		
		// Check if any error occurred initialising the SOAP client. We can't continue in this case.
		$error = $client->getError();

		$compliance = WooCommerce_EU_VAT_Compliance();

		if (!$error) {
		
			// Unless a non-extended check is forbidden by the parameter, perform one
			if (!$force_simple && '' != ($raw_store_id = get_option('woocommerce_eu_vat_store_id', ''))) {

				if (preg_match('/^([A-Z][A-Z])?([0-9A-Z]+)/i', str_replace(' ', '', $raw_store_id), $matches)) {

					if (empty($matches[1])) {
						// We look for the country code of the store
						$base_countries = $compliance->get_base_countries();
						$base_country = $base_countries[0];
						$storevat_country = $this->get_vat_number_prefix($base_country);
					} else {
						$storevat_country = strtoupper($matches[1]);
					}

					$storevat_id = $matches[2];
				}
			}
		
			/*
			For usefulness (since it's hard to emulate VIES being down for a specific country, on demand), here's what you get from VIES when a country's VIES connection is down:
			a:2:{s:9:"faultcode";s:11:"soap:Server";s:11:"faultstring";s:14:"MS_UNAVAILABLE";}
			i.e.
			Array
				(
					[faultcode] => soap:Server
					[faultstring] => MS_UNAVAILABLE
				)
			*/

			if (!empty($storevat_id) && !empty($storevat_country)) {

				$response = $client->call('checkVatApprox', array(
					'countryCode' => $vat_prefix,
					'vatNumber' => $vat_number,
					// Shop Owners Data have to be sent in order to retrieve the requestIdentifier
					// they should be entered in a field in the woocommerce backend
					'requesterCountryCode' => $storevat_country,
					'requesterVatNumber' => $storevat_id,
				));
			} else {

				$response = $client->call('checkVat', array(
					'countryCode' => $vat_prefix,
					'vatNumber' => $vat_number,
				));

			}
error_log("R: ".serialize($response));
			if (isset($response['valid']) && 'true' == $response['valid']) {
				return array(
					'body' => 'true',
					'service_response' => $response,
				);
			} elseif (!empty($response['faultcode'])) {
			
				return array(
					'body' => $response,
					'fault_code' => $response['faultstring'],
					'service_response' => $response
				);
				
			} elseif (isset($response['valid']) && 'false' == $response['valid']) {
			
				return array(
					'body' => $response,
					'service_response' => $response
				);
			
			}
			
		}
		
		return new WP_Error('no_result', 'No result was returned from the network VAT number check.');
		
		// We used to have a fallback service here, before it was discontinued
		// return wp_remote_get($this->validation_api_url . $vat_prefix . '/' . $vat_number . '/');

	}
}
