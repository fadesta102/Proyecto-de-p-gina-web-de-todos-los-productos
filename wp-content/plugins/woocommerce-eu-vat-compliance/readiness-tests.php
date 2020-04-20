<?php

if (!defined('WC_EU_VAT_COMPLIANCE_DIR')) die('No direct access');

class WC_EU_VAT_Compliance_Readiness_Tests {

	/**
	 * Get a list of text descriptions for different result codes
	 *
	 * @return Array
	 */
	public function result_descriptions() {
		return array(
			'pass' => __('Passed', 'woocommerce-eu-vat-compliance'),
			'fail' => __('Failed', 'woocommerce-eu-vat-compliance'),
			'unknown' => __('Uncertain', 'woocommerce-eu-vat-compliance'),
		);
	}

	/**
	 * Plugin constructor
	 */
	public function __construct() {

		$this->tests = array(
			'woo_minver' => __('WooCommerce version', 'woocommerce-eu-vat-compliance'),
			'tax_based_on' => __('Tax based upon', 'woocommerce-eu-vat-compliance'),
			'tax_enabled' => __('Store has tax enabled', 'woocommerce-eu-vat-compliance'),
			'rates_remote_fetch' => __('Current VAT rates can be fetched from network', 'woocommerce-eu-vat-compliance'),
			'rates_exist_and_up_to_date' => __('VAT rates are up-to-date', 'woocommerce-eu-vat-compliance'),
			'exchange_rates_exist_and_up_to_date' => __('Exchange rates care up-to-date', 'woocommerce-eu-vat-compliance'),
			'extended_vat_check_id' => __('Extended VAT Check', 'woocommerce-eu-vat-compliance'),
		);

		$this->compliance = WooCommerce_EU_VAT_Compliance();
		$this->vatnumber_class = WooCommerce_EU_VAT_Compliance('WC_EU_VAT_Compliance_VAT_Number');
		$this->rates_class = WooCommerce_EU_VAT_Compliance('WC_EU_VAT_Compliance_Rates');

		if (!$this->compliance->is_premium()) {
			$this->tests['subscriptions_plugin_on_free_version'] = __('Support for the WooCommerce Subscriptions extension', 'woocommerce-eu-vat-compliance');
			$this->tests['subscriptio_plugin_on_free_version'] = __('Support for the RightPress Subscriptio extension', 'woocommerce-eu-vat-compliance');
			$this->tests['subscriben_plugin_on_free_version'] = __('Support for the Subscriben extension', 'woocommerce-eu-vat-compliance');
		}

		$this->vat_region_countries = $this->compliance->get_vat_countries();

	}

	public function get_results($only_these_tests = false) {

		$results = array();

		foreach ($this->tests as $test => $label) {

			if (is_array($only_these_tests) && empty($only_these_tests[$test])) continue;

			if (!method_exists($this, $test)) continue;
			$res = call_user_func(array($this, $test));
			// label, result, info
			if (is_wp_error($res)) {
				$res = $this->res(false, $res->get_error_message());
			}
			if (isset($res['result'])) {
				$results[$test] = $res;
				$results[$test]['label'] = $label;
			}
		}

		return $results;
	}

	/**
	 * Return the results format expected by the test runner
	 *
	 * @param Boolean $result - whether the test passed or not
	 * @param Mixed	  $info	  - any further information
	 *
	 * @return Array - keys are result, info
	 */
	protected function res($result, $info) {
		if (is_bool($result)) {
			if ($result) {
				$rescode = 'pass';
			} else {
				$rescode = 'fail';
			}
		} else {
			$rescode = 'unknown';
		}
		return array(
			'result' => $rescode,
			'info' => $info
		);
	}

	protected function woo_minver() {
		$result = (defined('WOOCOMMERCE_VERSION') && version_compare(WOOCOMMERCE_VERSION, '2.6', '>='));
		if ($result) {
			$info = sprintf(__('Your WooCommerce version (%s) is supported by this plugin with all features.', 'woocommerce-eu-vat-compliance'), WOOCOMMERCE_VERSION);
		} else {
			$info = sprintf(__('Your WooCommerce version (%s) is lower than %s, which is the lowest supported version. You should upgrade WooCommerce to a supported version.', 'woocommerce-eu-vat-compliance'), WOOCOMMERCE_VERSION, '2.6');
		}
		return $this->res($result, $info);
	}

	protected function tax_based_on() {
		$tax_based_on = get_option('woocommerce_tax_based_on');

		if ($tax_based_on == 'shipping' || $tax_based_on == 'billing') {
			$result = true;
			$info = __('If selling digital goods to the EU, tax calculations must be based on either the customer billing or shipping address.', 'woocommerce-eu-vat-compliance');
		} else {
			$result = false;
			$info = __('If selling digital goods to the EU, tax calculations must be based on either the customer billing or shipping address.', 'woocommerce-eu-vat-compliance').' '.__('They cannot be based upon the shop base address. If you are not selling digital goods, then you can de-activate this test.', 'woocommerce-eu-vat-compliance');
		}
		return $this->res($result, $info);
	}

	// TODO: Test for whether base country settings are consistent (if we charge no VAT to base country, then... etc.)
	// get_option( 'woocommerce_eu_vat_compliance_deduct_in_base' ) == 'yes' )
	// $compliance->wc->countries->get_base_country()

// TODO: This check needs to only check rate tables that are in the configured tax classes (or does it? Even traditional VAT should have the right rate).
	protected function rates_exist_and_up_to_date() {
		$has_rate_remaining_countries = $this->vat_region_countries;
		$countries_found = 0;
		$countries_expected = count($has_rate_remaining_countries);
		$countries_with_apparently_wrong_rates = array();
		$base_country = $this->compliance->wc->countries->get_base_country();

		$rates = $this->rates_class->get_vat_rates();

		$info = '';
		$result = false;
		if (empty($rates)) {
			$info = __('Could not get any VAT rate information.', 'woocommerce-eu-vat-compliance');
		} else {
			global $wpdb, $table_prefix;
			// get ticked tax classes
			$tax_classes = $this->compliance->get_tax_classes();
			$opts_classes = $this->compliance->get_region_vat_tax_classes(array_diff(array_keys($tax_classes), array('zero-rate')));

			$active_classes = WC_Tax::get_tax_classes();
			$active_classes_list = "''"; // In WooCommerce, the 'standard rate' tax class has an empty slug.
			foreach ($active_classes as $class) {
				$slug = esc_sql(sanitize_title($class));
				$active_classes_list .= $active_classes_list ? ",'$slug'" : "'".$slug."'";
			}
			
			$tax_rate_classes = get_option('woocommerce_tax_classes');
			$sql = "SELECT tax_rate_country, tax_rate, tax_rate_class FROM ".$table_prefix."woocommerce_tax_rates WHERE tax_rate_state='' AND tax_rate_class IN ($active_classes_list)";
			
			# Get an array of objects
			$results = $wpdb->get_results($sql);

			if (!is_array($results)) {
				return $results;
			} else {
				foreach ($results as $res) {
					$tax_rate_country = $res->tax_rate_country;
					$tax_rate_class = $res->tax_rate_class;
					// In our options handling, we use the slug 'standard', whereas WooCommerces's tables use an empty value
					if ('' == $tax_rate_class) $tax_rate_class = 'standard';
					$tax_rate = $res->tax_rate;

					if (($key = array_search($tax_rate_country, $has_rate_remaining_countries)) !== false) {
						unset($has_rate_remaining_countries[$key]);
						$countries_found++;
					}
					// only check countries that fall in selected tax-rate-classes
					if (!in_array($tax_rate_class, $opts_classes)) continue;
					if (empty($tax_rate_country) || '*' == $tax_rate_country || !isset($rates[$tax_rate_country]) || !is_array($rates[$tax_rate_country])) continue;

					$found_rate = false;
					foreach ($rates[$tax_rate_country] as $label => $rate) {
						# N.B. Not all attribute/values are rates; but, all the numerical ones are
						if (is_numeric($rate)) {
							if ($rate == $tax_rate) {
								$found_rate = true;
							}
						}
					}
					if (!$found_rate) {
						$countries_with_apparently_wrong_rates[$tax_rate_country] = $tax_rate;
					}
				}
			}
		}

		if (count($countries_with_apparently_wrong_rates) > 0) {
			$info = __('The following countries have tax rates set in a tax table, that were not found as any current VAT rate:', 'woocommerce-eu-vat-compliance').' ';
			$first = true;
			foreach ($countries_with_apparently_wrong_rates as $country => $rate) {
				if ($first) { $first = false; } else { $info .= ', '; }
				$info .= "$country (".round($rate, 2)." %)";
			}
			$info .= '.';
		} else {
			if ($countries_found == $countries_expected) {
				$result = true;
				$info = __('All countries had at least one tax table in which a current digital VAT rate entry was found.', 'woocommerce-eu-vat-compliance');
			} elseif (!empty($has_rate_remaining_countries) && $this->countries_are_base_country_or_equivalent($base_country, $has_rate_remaining_countries)) {
				$info = __('All countries had at least one tax table in which a current digital VAT rate entry was found, with the exception of base or equivalent countries.', 'woocommerce-eu-vat-compliance');
				$result = true;
			} elseif (0 == $countries_found) {
				$info = __('No tax rates at all were found in your WooCommerce tax tables. Have you set any up yet?', 'woocommerce-eu-vat-compliance');
			} else {
				$info = sprintf(__('Less countries (%d) had at least one tax table in which a current digital VAT rate entry was found than expected (%d).', 'woocommerce-eu-vat-compliance'), $countries_found, $countries_expected);
			}
		}

		if (count($has_rate_remaining_countries) > 0) {
			if ($this->countries_are_base_country_or_equivalent($base_country, $has_rate_remaining_countries)) {
				if ($result) $result = 'unknown';
				$info .= ' '.sprintf(__('You have countries that are either your base country or equivalent (%s) with no tax rate set in any tax rate table; but, perhaps this was intentional.', 'woocommerce-eu-vat-compliance'), implode(', ', $has_rate_remaining_countries));
			} else {
				$result = false;
				$info .= ' '.__('These countries have no tax rate set in any tax rate table:', 'woocommerce-eu-vat-compliance').' '.implode(', ', $has_rate_remaining_countries);
			}
		}

		return $this->res($result, $info);
	}
	
	protected function countries_are_base_country_or_equivalent($base_country, $countries) {
		// Countries which, for the purposes of VAT exports, count as the same territory
		$equivalents = array(
			'GB' => array('IM'),
			'IM' => array('GB')
		);
		foreach ($countries as $country) {
			if ($base_country != $country && (!isset($equivalents[$country]) || !in_array($base_country, $equivalents[$country]))) return false;
		}
		return true;
	}

	// With thanks to Sven Auhagen
	protected function extended_vat_check_id() {

		$result = true;
		$info = __('The extended VAT check is not enabled (you have not entered your VAT number)', 'woocommerce-eu-vat-compliance');    

		if ( get_option( 'woocommerce_eu_vat_store_id', '' ) != '' ) {

			preg_match('/^([A-Z][A-Z])?([0-9A-Z]+)/', str_replace(' ', '', get_option( 'woocommerce_eu_vat_store_id')), $matches);
			if ($matches[1] == '') {
				//we look for the country code of the store
				$storevat_country = $this->compliance->wc->countries->get_base_country();
			} else {
				$storevat_country = $matches[1];
			}

			$storevat_id = $matches[2];
	
			//disable extended vat check for now to do this test
			$response = $this->compliance->get_vat_region_object()->get_validation_result_from_network($storevat_country, $storevat_id, true);

			//if the VAT is valid
			if (is_array($response) && !empty($response['body']) && 'true' == $response['body']) {
				$result = true;
				$info = sprintf(__('The store VAT ID (%s) is valid, so extended VAT checks are possible.', 'woocommerce-eu-vat-compliance'), $storevat_country.' '.$storevat_id);
			} else {
				$result = false;

				$info = sprintf(__('The store VAT ID (%s) could not be confirmed as valid, so VAT number validity checks will fail.', 'woocommerce-eu-vat-compliance'), $storevat_country.' '.$storevat_id);

				if (!empty($this->vatnumber_class->fault_code)) {
					$info .= ' '.__('Error:', 'woocommerce-eu-vat-compliance').' '.$this->vatnumber_class->fault_code_to_text();
				}
				
				if (is_array($response) && !empty($response['body']) && is_string($response['body'])) {
					$resp = json_decode($response['body'], true);
					if (is_array($resp) && !empty($resp['error']) && !empty($resp['error_message'])) {
						$info .= ' '.__('Error:', 'woocommerce-eu-vat-compliance').' '.$resp['error_message'];
					}
				}

			}

		}
	
		return $this->res($result, $info);
	}

	protected function rates_remote_fetch() {
		$rates = $this->rates_class->fetch_remote_vat_rates();
		$info = __('Testing ability to fetch current VAT rates from the network.', 'woocommerce-eu-vat-compliance');
		if (empty($rates)) $info .= ' '.__('If this fails, then check (with your web hosting company) the network connectivity from your webserver.', 'woocommerce-eu-vat-compliance');
		return $this->res(!empty($rates), $info);
	}

	protected function exchange_rates_exist_and_up_to_date() {
	
		$conversion_provider = get_option('woocommerce_eu_vat_compliance_exchange_rate_provider', 'ecb');

		$providers = $this->compliance->get_rate_providers();
		if (!is_array($providers) || !isset($providers[$conversion_provider])) return $this->res(false, __('No exchange rate providers found', 'woocommerce-eu-vat-compliance'));
	
		$provider = $providers[$conversion_provider];
	
		$result = $provider->convert('GBP', 'EUR', 1);
		
		if (!$result) {
			return $this->res(false, __('Conversion failed', 'woocommerce-eu-vat-compliance').' ('.serialize($result).')');
		} else {
		
			$latest_update = $provider->get_last_updated();
			
			if ($latest_update < 1) return $this->res(false, __('No rates were successfully fetched from the network (provider: '.$conversion_provider.')', 'woocommerce-eu-vat-compliance'));
			
			if ($latest_update < time() - 86400 - 2678400) return $this->res(false, sprintf(__('No updated rates were successfully fetched from the network (since %s)',  'woocommerce-eu-vat-compliance'), gmdate('Y-m-d H:i:s', $latest_update).' UTC'));
		
		}
	
	}
	
	protected function tax_enabled() {
		$woocommerce_calc_taxes = get_option('woocommerce_calc_taxes');
		return $this->res('yes' == $woocommerce_calc_taxes, __('Taxes need to be enabled in the WooCommerce tax settings.', 'woocommerce-eu-vat-compliance'));
	}

	protected function subscriptions_plugin_on_free_version() {
		$active_plugins = (array) get_option( 'active_plugins', array() );
		if (is_multisite()) $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
		// Return just true: better not to report a non-event
		if (!in_array('woocommerce-subscriptions/woocommerce-subscriptions.php', $active_plugins ) || array_key_exists('woocommerce-subscriptions/woocommerce-subscriptions.php', $active_plugins)) return true;
		return $this->res(false, sprintf(__('The %s plugin is active, but support for subscription orders is not part of the free version of the WooCommerce VAT Compliance plugin. New orders created via subscriptions will not have VAT compliance information attached.', 'woocommerce-eu-vat-compliance'), __('WooCommerce subscriptions', 'woocommerce-eu-vat-compliance')));
	}

	protected function subscriptio_plugin_on_free_version() {
		$active_plugins = (array) get_option( 'active_plugins', array() );
		if (is_multisite()) $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
		// Return just true: better not to report a non-event
		if (!in_array('subscriptio/subscriptio.php', $active_plugins ) || array_key_exists('subscriptio/subscriptio.php', $active_plugins)) return true;
		return $this->res(false, sprintf(__('The %s plugin is active, but support for subscription orders is not part of the free version of the WooCommerce VAT Compliance plugin. New orders created via subscriptions will not have VAT compliance information attached.', 'woocommerce-eu-vat-compliance'), __('RightPress Subscriptio', 'woocommerce-eu-vat-compliance')));
	}
	
	protected function subscriben_plugin_on_free_version() {
		$active_plugins = (array) get_option( 'active_plugins', array() );
		if (is_multisite()) $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
		// Return just true: better not to report a non-event
		if (!in_array('subscriben/subscriben.php', $active_plugins ) || array_key_exists('subscriben/subscriben.php', $active_plugins)) return true;
		return $this->res(false, sprintf(__('The %s plugin is active, but support for subscription orders is not part of the free version of the WooCommerce VAT Compliance plugin. New orders created via subscriben will not have VAT compliance information attached.', 'woocommerce-eu-vat-compliance'), __('Subscriben', 'woocommerce-eu-vat-compliance')));
	}

}
