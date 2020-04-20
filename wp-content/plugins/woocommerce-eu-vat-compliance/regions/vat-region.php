<?php

if (!defined('WC_EU_VAT_COMPLIANCE_DIR')) die('No direct access.');

abstract class WC_VAT_Region {

	/**
	 * Get a list of countries that define the region
	 *
	 * @return Array
	 */
	abstract public function get_countries();
	
	/**
	 * Return the title for the region
	 *
	 * @param String $context - 'noun', 'adjective'
	 *
	 * @return String
	 */
	abstract public function get_region_title($context);
	
	/**
	 * Given a country, return the minimum number of characters in a valid VAT number for that country
	 * Generally a child class will not need to re-implement this (but map_country_codes_to_minimum_characters())
	 *
	 * @param String $country_code - the country code
	 *
	 * @return Integer
	 */
	public function get_vat_number_minimum_characters($country_code) {
		$mapping = $this->map_country_codes_to_minimum_characters();
		return isset($mapping[$country_code]) ? $mapping[$country_code] : $mapping['default'];
	}
	
	/**
	 * Get an array listing the minimum number of characters in a valid VAT number for the region's countries (and a default)
	 *
	 * @return Integer
	 */
	public function map_country_codes_to_minimum_characters() {
		// Some small default - but this is expected to be over-ridden
		return array('default' => 6);
	}
	
	/**
	 * Given a VAT number in unspecified format, canonicalise it. This includes removing any standard country prefix.
	 * The routine here will perform a basic removal of extraneous characters, and upper-case the number. Child classes may want to call this first before doing their own further processing.
	 *
	 * @param String $vat_number - the VAT number
	 *
	 * @return String
	 */
	public function standardise_vat_number($vat_number) {
		return strtoupper(str_replace(array(' ', '-', '_', '.'), '', $vat_number));
	}
	
	/**
	 * Return the VAT number prefix for the given country
	 *
	 * @param String $country
	 *
	 * @return String - the prefix
	 **/
	public function get_vat_number_prefix($country) {
		return $country;
	}
	
	/**
	 * @param String  $vat_prefix	- the country prefix to the VAT number
	 * @param String  $vat_number	- the VAT number (already canonicalised), minus any country prefix
	 * @param Boolean $force_simple	- force a non-extended lookup, even if in the saved options there is a VAT ID for the store
	 *
	 * @return WP_Error|Array - an error object, or an array with key 'body' containing either (string)true or SERVER_BUSY, or an array with a fault code
	 */
	abstract function get_validation_result_from_network($vat_prefix, $vat_number, $force_simple = false);

}
