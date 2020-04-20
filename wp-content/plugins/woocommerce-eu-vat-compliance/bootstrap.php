<?php

// Purpose: boot-strap plugin. Also contains the main class.

if (!defined('ABSPATH')) die('Access denied.');

if (class_exists('WC_EU_VAT_Compliance')) return;

define('WC_EU_VAT_COMPLIANCE_DIR', dirname(__FILE__));
define('WC_EU_VAT_COMPLIANCE_URL', plugins_url('', __FILE__));

$active_plugins = (array) get_option( 'active_plugins', array() );
if (is_multisite()) $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));

if (!in_array('woocommerce/woocommerce.php', $active_plugins ) && !array_key_exists('woocommerce/woocommerce.php', $active_plugins)) return;

// This plugin performs various distinct functions. So, we have separated the code accordingly.
// Not all of these files may be present, depending on a) whether this is the free or premium version b) whether I've written the feature yet
require_once(WC_EU_VAT_COMPLIANCE_DIR.'/vendor/davidanderson684/woocommerce-compat/woocommerce-compat.php');
@include_once(WC_EU_VAT_COMPLIANCE_DIR.'/vat-number.php');
@include_once(WC_EU_VAT_COMPLIANCE_DIR.'/record-order-country.php');
@include_once(WC_EU_VAT_COMPLIANCE_DIR.'/rates.php');
@include_once(WC_EU_VAT_COMPLIANCE_DIR.'/widgets.php');
@include_once(WC_EU_VAT_COMPLIANCE_DIR.'/preselect-country.php');
@include_once(WC_EU_VAT_COMPLIANCE_DIR.'/premium.php');

// Though the code is separated, some pieces are inter-dependent; the order also matters. So, don't assume you can just change this arbitrarily.
$potential_classes_to_activate = array(
	'WooCommerce_Compat_0_3',
	'WC_EU_VAT_Compliance',
	'WC_EU_VAT_Compliance_VAT_Number',
	'WC_EU_VAT_Compliance_Record_Order_Country',
	'WC_EU_VAT_Compliance_Rates',
	'WC_EU_VAT_Country_PreSelect_Widget',
	'WC_EU_VAT_Compliance_Preselect_Country',
	'WC_EU_VAT_Compliance_Premium',
);

if (is_admin() || (defined('DOING_CRON') && DOING_CRON) || (defined('WC_EU_VAT_LOAD_ALL_CLASSES') && WC_EU_VAT_LOAD_ALL_CLASSES)) {
	@include_once(WC_EU_VAT_COMPLIANCE_DIR.'/reports.php');
	@include_once(WC_EU_VAT_COMPLIANCE_DIR.'/control-centre.php');
	$potential_classes_to_activate[] = 'WC_EU_VAT_Compliance_Reports';
	$potential_classes_to_activate[] = 'WC_EU_VAT_Compliance_Control_Centre';
}

$classes_to_activate = apply_filters('woocommerce_eu_vat_compliance_classes', $potential_classes_to_activate);

if (!class_exists('WC_EU_VAT_Compliance')):
class WC_EU_VAT_Compliance {

	private $default_vat_matches = 'VAT, V.A.T, IVA, I.V.A., Value Added Tax, TVA, T.V.A., BTW, B.T.W.';
	public $wc;

	public $settings;

	private $wcpdf_order_id;

	public $data_sources = array();
	
	public $wc_compat;

	/**
	 * Plugin constructor
	 */
	public function __construct() {

		$this->data_sources = array(
			'HTTP_CF_IPCOUNTRY' => __('CloudFlare Geo-Location', 'woocommerce-eu-vat-compliance'),
			'woocommerce' => __('WooCommerce 2.3+ built-in geo-location', 'woocommerce-eu-vat-compliance'),
			'geoip_detect_get_info_from_ip_function_not_available' => __('MaxMind GeoIP database was not installed', 'woocommerce-eu-vat-compliance'),
			'geoip_detect_get_info_from_ip' => __('MaxMind GeoIP database', 'woocommerce-eu-vat-compliance'),
		);

		add_action('before_woocommerce_init', array($this, 'before_woocommerce_init'), 1, 1);
		add_action('plugins_loaded', array($this, 'plugins_loaded'));

		add_action( 'woocommerce_settings_tax_options_end', array($this, 'woocommerce_settings_tax_options_end'));
		add_action( 'woocommerce_update_options_tax', array( $this, 'woocommerce_update_options_tax'));

		add_filter('network_admin_plugin_action_links', array($this, 'plugin_action_links'), 10, 2);
		add_filter('plugin_action_links', array($this, 'plugin_action_links'), 10, 2);

		add_action('wpo_wcpdf_process_template_order', array($this, 'wpo_wcpdf_process_template_order'), 10, 2);

		add_filter('wpo_wcpdf_footer_settings_text', array($this, 'wpo_wcpdf_footer'));

		add_action('woocommerce_admin_field_wceuvat_taxclasses', array($this, 'woocommerce_admin_field_wceuvat_taxclasses'));

		add_action('woocommerce_check_cart_items', array($this, 'woocommerce_check_cart_items'));
		add_action('woocommerce_checkout_process', array($this, 'woocommerce_check_cart_items'));

		// These are heavy-handed, downgrade the WooCommerce experience for everyone, and thereby harm the whole ecosystem
		add_filter('woocommerce_allow_marketplace_suggestions', '__return_false', 20);
		
		// This is for convenience
		$this->wc_compat = WooCommerce_EU_VAT_Compliance('WooCommerce_Compat_0_3');
		
		add_action('plugins_loaded', array($this, 'load_updater'), 0);

	}
	
	/**
	 * Runs upon the WP action plugins_loaded
	 */
	public function load_updater() {
		if (file_exists(WC_EU_VAT_COMPLIANCE_DIR.'/wpo_update.php')) {
			require(WC_EU_VAT_COMPLIANCE_DIR.'/wpo_update.php');
		} elseif (file_exists(WC_EU_VAT_COMPLIANCE_DIR.'/updater.php')) {
			require(WC_EU_VAT_COMPLIANCE_DIR.'/updater.php');
		}
	}
	
	public function ajax_update_checkout_totals($form_data) {
		
		parse_str($form_data, $parsed_form_data);

		if (empty($parsed_form_data['billing_country']) && empty($parsed_form_data['shipping_country'])) return;

		if (empty($parsed_form_data['billing_state'])) $parsed_form_data['billing_state'] = '';
		if (empty($parsed_form_data['shipping_state'])) $parsed_form_data['shipping_state'] = '';
		if (empty($parsed_form_data['billing_country'])) $parsed_form_data['billing_country'] = '';
		if (empty($parsed_form_data['shipping_country'])) $parsed_form_data['shipping_country'] = '';

		$tax_based_on = get_option('woocommerce_tax_based_on');

		if ('shipping' == $tax_based_on && !$this->wc->cart->needs_shipping()) $tax_based_on = 'billing';

		// Requires WC 2.1+ (the field name changed)
		if ('shipping' == $tax_based_on && empty($parsed_form_data['ship_to_different_address'])) $tax_based_on = 'billing';

		switch ($tax_based_on) {
			case 'billing' :
			case 'base' :
				$country = !empty($parsed_form_data['billing_country']) ? $parsed_form_data['billing_country'] : '';
				$state = !empty($parsed_form_data['billing_state']) ? $parsed_form_data['billing_state'] : '';
			break;
			case 'shipping' :
				$country = !empty($parsed_form_data['shipping_country']) ? $parsed_form_data['shipping_country'] : $parsed_form_data['billing_country'];
				$state = !empty($parsed_form_data['shipping_state']) ? $parsed_form_data['shipping_state'] : '';
			break;
		}

		$country_info = $this->get_visitor_country_info();
		
		$wc_eu_vat_ip_country_code = empty($country_info['data']) ? '??' : $country_info['data'];

		$this->wc->session->set('eu_vat_country_checkout', $country);
		$this->wc->session->set('eu_vat_state_checkout', $state);
		
	}
	
	/**
	 * Get the VAT region code
	 *
	 * @return String
	 */
	public function get_vat_region() {
		return 'eu';
	}
	
	/**
	 * Get a VAT region object
	 *
	 * @return WC_VAT_Region
	 */
	public function get_vat_region_object() {
	
		static $region_object = null;
		
		if (null === $region_object) {
	
			if (!class_exists('WC_VAT_Region')) require_once(WC_EU_VAT_COMPLIANCE_DIR.'/regions/vat-region.php');
		
			$region = $this->get_vat_region();
			
			$region_class = 'WC_VAT_Region_'.$region;
			
			if (!class_exists($region_class)) require_once(WC_EU_VAT_COMPLIANCE_DIR.'/regions/'.$region.'.php');
		
			$region_object = new $region_class;
		
		}
		
		return $region_object;
	}

	/**
	 * Get a list of countries for the VAT area
	 *
	 * @return Array
	 */
	public function get_vat_countries() {
		return $this->get_vat_region_object()->get_countries();
	}
	
	/**
	 * Get the VAT region title
	 *
	 * @param String $context - 'noun', 'adjective'
	 *
	 * @return String
	 */
	public function get_vat_region_title($context) {
		return $this->get_vat_region_object()->get_region_title($context);
	}
	
	/**
	 * An abstraction function, allowing alteration of the base country, or multiple base countries
	 *
	 * @return Array
	 */
	public function get_base_countries() {
	
		$base_countries = array($this->wc->countries->get_base_country());
		
		// Should return a numerically indexed array, beginning from 0. The first element should be the one considered most primary, should such a concept be needed anywhere.
		
		return apply_filters('wc_eu_vat_get_base_countries', $base_countries);
	
	}
	
	/**
	 * This method checks that the cart's contents are not forbidden by configuration
	 *
	 * @return Boolean
	 */
	public function cart_is_permitted() {
	
		$is_permitted = true;
	
		$cart = $this->wc->cart->get_cart();

		$has_relevant_products = $this->product_list_has_relevant_products($cart);
		
		if ($has_relevant_products) {
			$taxable_address = $this->wc->customer->get_taxable_address();
			$eu_vat_countries = $this->get_vat_countries();

			if (!empty($taxable_address[0]) && in_array($taxable_address[0], $eu_vat_countries)) {
				$is_permitted = false;
			}
		}

		return apply_filters('wceuvat_cart_is_permitted', $is_permitted);
	
	}
	
	/**
	 * This method checks that the product list's contents are not forbidden by configuration
	 *
	 * @param Array $list - list of product items
	 *
	 * @return Boolean
	 */
	public function product_list_has_relevant_products($list) {
	
		$opts_classes = $this->get_region_vat_tax_classes();

		$has_relevant_products = false;
		$relevant_products_found = false;

		foreach ($list as $item) {
			if (empty($item['data'])) continue;
			$_product = $item['data'];
			$tax_status = $_product->get_tax_status();
			if ('taxable' != $tax_status) continue;
			$tax_class = $_product->get_tax_class();
			if (empty($tax_class)) $tax_class = 'standard';
			if (in_array($tax_class, $opts_classes)) {
				$has_relevant_products = true;
				break;
			}
		}
		
		return apply_filters('wceuvat_product_list_product_list_has_relevant_products', $has_relevant_products, $relevant_products_found, $list);
	
	}

	// If VAT checkout is forbidden, then this function is where the work is done to prevent it
	public function woocommerce_check_cart_items() {

		// WooCommerce 3.0 runs both woocommerce_check_cart_items and woocommerce_checkout_process, which results in duplicate notices.
		static $we_already_did_this = false;
		if ($we_already_did_this) return;
		$we_already_did_this = true;
	
		// Only proceed if taxes are turned on on the store, or and VAT-able orders are forbidden
		if ('yes' != get_option('woocommerce_eu_vat_compliance_forbid_vatable_checkout', 'no') || 'yes' != get_option('woocommerce_calc_taxes')) return;

		$cart_is_permitted = $this->cart_is_permitted();

		if (!$cart_is_permitted) {
			// If in cart, then warn - they still may select a different VAT country.
			$current_filter = current_filter();

			if ('woocommerce_checkout_process' != $current_filter && (!defined('WOOCOMMERCE_CHECKOUT') || !WOOCOMMERCE_CHECKOUT)) {
				// Cart: just warn
				echo "<p class=\"woocommerce-info\" id=\"wceuvat_notpossible\">".apply_filters('wceuvat_euvatcart_message', __('Depending on your country, it may not be possible to purchase all the items in this cart. This is because this store does not sell items liable to EU VAT to EU customers (due to the high costs of complying with EU VAT laws).', 'woocommerce-eu-vat-compliance'))."</p>";
			} else {
				// Attempting to check-out: prevent
				$this->add_wc_error(
					apply_filters('wceuvat_euvatcheckoutforbidden_message', __('This order cannot be processed. Due to the high costs of complying with EU VAT laws, we do not sell items liable to EU VAT to EU customers.', 'woocommerce-eu-vat-compliance'))
				);
			}
		}

	}

	/**
	 * Get a list of tax class identifiers and titles
	 *
	 * @return Array
	 */
	public function get_tax_classes() {
	
		$tax = new WC_Tax();
		// Does not exist until WC 2.2
		$tax_classes = $tax->get_tax_classes();

		$classes_by_title = array('standard' => __('Standard Rate', 'woocommerce-eu-vat-compliance'));

		foreach ($tax_classes as $class) {
			$classes_by_title[sanitize_title($class)] = $class;
		}

		return $classes_by_title;
	}

	/**
	 * Get a list of tax classes which are VAT
	 *
	 * @param Array|Boolean -  an array of slugs of default tax classes, if you have one; otherwise (if false), one will be obtained from self::get_tax_classes();
	 *
	 * @return Array
	 */
	public function get_region_vat_tax_classes($default = false) {

		if (false === $default) $default = array_keys($this->get_tax_classes());

		// Apply a default value, for if this is not set (people upgrading)
		$opts_classes = get_option('woocommerce_eu_vat_compliance_tax_classes', $default);

		return is_array($opts_classes) ? $opts_classes : $default;
	}

	/**
	 * Find out whether a particular product or tax class has variable VAT
	 *
	 * @param $product_or_tax_class String|WC_Product
	 *
	 * @return Boolean
	 */
	public function product_taxable_class_indicates_variable_digital_vat($product_or_tax_class) {
		if (is_a($product_or_tax_class, 'WC_Product') && 'taxable' != $product_or_tax_class->get_tax_status()) return false;
		
		static $vat_classes = null;
		
		if (null == $vat_classes) $vat_classes = $this->get_region_vat_tax_classes();
		
		$tax_class = (is_a($product_or_tax_class, 'WC_Product')) ? $product_or_tax_class->get_tax_class() : $product_or_tax_class;
		
		// WC's handling of the 'default' tax class is rather ugly/non-intuitive - you need the secret knowledge of its name
		if (empty($tax_class)) $tax_class = 'standard';
		
		return in_array($tax_class, $vat_classes);
	}


	/**
	 * Runs upon the WP action woocommerce_admin_field_wceuvat_taxclasses
	 */
	public function woocommerce_admin_field_wceuvat_taxclasses() {

		$tax_classes = $this->get_tax_classes();
		$opts_classes = $this->get_region_vat_tax_classes(array_diff(array_keys($tax_classes), array('zero-rate')));

		$settings_link = admin_url('admin.php?page=wc-settings&tab=tax');

		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
			<label><?php _e('Relevant tax classes', 'woocommerce-eu-vat-compliance');?></label>
			</th>
			<td>
				<p><em><?php echo __('Indicate all the WooCommerce tax classes for which variable-by-country EU VAT is charged.', 'woocommerce-eu-vat-compliance').' <a href="'.esc_attr($settings_link).'">'.__('To create additional tax classes, go to the WooCommerce tax settings.', 'woocommerce-eu-vat-compliance').'</a> '.__('Products which are not in one of these tax classes will be excluded from per-country VAT calculations recorded by this plugin (though they may still have traditional EU VAT charged if you have configured them to do so - i.e., the purpose of this setting is to allow you to have a shop selling mixed goods).', 'woocommerce-eu-vat-compliance');?></em></p>
					<?php
						foreach ($tax_classes as $slug => $label) {
							$checked = (in_array($slug, $opts_classes) || in_array('all$all', $opts_classes)) ? ' checked="checked"' : '';
							echo '<input type="checkbox"'.$checked.' id="woocommerce_eu_vat_compliance_tax_classes_'.$slug.'" name="woocommerce_eu_vat_compliance_tax_classes[]" value="'.$slug.'"> <label for="woocommerce_eu_vat_compliance_tax_classes_'.$slug.'">'.htmlspecialchars($label).'</label><br>';
						}
					?>
			</td>
		</tr>
		<?php
	}

	/**
	 * WP filter wpo_wcpdf_footer_settings_text (was wpo_wcpdf_footer prior to 1.13.16 - see WPO HS#19569)
	 *
	 * @param String - pre-filter footer text
	 *
	 * @return String
	 */
	public function wpo_wcpdf_footer($footer) {

		$valid_eu_vat_number = null;
		$vat_number_validated = null;
		$vat_number = null;
		$vat_paid = array();
		$new_footer = $footer;
		$text = '';
		$order = null;

		$order_id = $this->wcpdf_order_id;

		if (!empty($order_id)) {

			$order = $this->get_order($order_id);

			if (is_a($order, 'WC_Order')) {

				$vat_paid = $this->get_vat_paid($order, true, true);

				$valid_eu_vat_number = $this->wc_compat->get_meta($order, 'Valid EU VAT Number', true);
				$vat_number_validated = $this->wc_compat->get_meta($order, 'VAT number validated', true);
				$vat_number = $this->wc_compat->get_meta($order, 'VAT Number', true);

				// !empty used, because this is only for non-zero VAT
				if (is_array($vat_paid) && !empty($vat_paid['total'])) {
					$text = get_option('woocommerce_eu_vat_compliance_pdf_footer_b2c');

					if (!empty($text)) {
						$new_footer = wpautop(wptexturize($text)).$footer;
					}
				}

			}

		}

		return apply_filters('wc_euvat_compliance_wpo_wcpdf_footer', $new_footer, $footer, $text, $vat_paid, $vat_number, $valid_eu_vat_number, $vat_number_validated, $order);
	}

	public function wpo_wcpdf_process_template_order($template_id, $order_id) {
		$this->wcpdf_order_id = $order_id;
	}

	public function enqueue_jquery_ui_style() {
		global $wp_scripts;
		$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';
		wp_enqueue_style( 'jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $jquery_version . '/themes/smoothness/jquery-ui.css', array(), WC_VERSION );
	}

	/**
	 * Get the plugin version
	 *
	 * @return String
	 */
	public function get_version() {

		if (!empty($this->version)) return $this->version;

		$file = $this->is_premium() ? WC_EU_VAT_COMPLIANCE_DIR.'/eu-vat-compliance-premium.php' : WC_EU_VAT_COMPLIANCE_DIR.'/eu-vat-compliance.php';
		
		if ($fp = fopen($file, 'r')) {
			$file_data = fread($fp, 1024);
			if (preg_match("/Version: ([\d\.]+)(\r|\n)/", $file_data, $matches)) {
				$this->version = $matches[1];
			}
			fclose($fp);
		}

		return $this->version;
	}

	/**
	 * A convenience method, which was previously used to provide compatibility for a change in WC 2.1
	 *
	 * @param String $msg - the error message
	 */
	public function add_wc_error($msg) {
		wc_add_notice($msg, 'error');
	}

	// Returns normalised data
	public function get_vat_matches($format = 'array') {
		$matches = get_option('woocommerce_eu_vat_compliance_vat_match', $this->default_vat_matches);
		if (!is_string($matches) || empty($matches)) $matches = $this->default_vat_matches;
		$arr = array_map('trim', explode(',', $matches));
		if ('regex' == $format) {
			$ret = '#(';
			foreach ($arr as $str) {
				$ret .= ($ret == '#(') ? preg_quote($str) : '|'.preg_quote($str);
			}
			$ret .= ')#i';
			return $ret;
		} elseif ('html-printable' == $format) {
			$ret = '';
			foreach ($arr as $str) {
				$ret .= ($ret == '') ? htmlspecialchars($str) : ', '.htmlspecialchars($str);
			}
			return $ret;
		} elseif ('sqlregex' == $format) {
			$ret = '';
			foreach ($arr as $str) {
				$ret .= ($ret == '') ? esc_sql($str) : '|'.esc_sql($str);
			}
			return $ret;
		}
		return $arr;
	}

	/**
	 * A convenience method, which was previously used to provide compatibility for a change in WC 2.2
	 *
	 * @param Integer $order_id
	 *
	 * @return WC_Order|Boolean|WC_refund
	 */
	public function get_order($order_id) {
		return wc_get_order($order_id);
	}

	// This function is for output - it will add on conversions into the indicate currencies
	public function get_amount_in_conversion_currencies($amount, $conversion_currencies, $conversion_rates, $order_currency, $paid = false) {
		foreach ($conversion_currencies as $currency) {
			$rate = ($currency == $order_currency) ? 1 : (isset($conversion_rates['rates'][$currency]) ? $conversion_rates['rates'][$currency] : '??');

			if ('??' == $rate) continue;

			if ($paid !== false) {
				$paid .= ' / ';
			} else {
				$paid = '';
			}
			$paid .= get_woocommerce_currency_symbol($currency).' '.sprintf('%.02f', $amount * $rate);
		}
		return $paid;
	}

	/**
	 * @param Integer|WC_Order $order - Pass in a WC_Order object, or an order number
	 * @param Boolean $allow_quick - allow re-use of an already set/saved value
	 * @param Boolean $set_on_quick
	 * @param Boolean $quick_only
	 *
	 * @return Array|Boolean
	 */
	public function get_vat_paid($order, $allow_quick = false, $set_on_quick = false, $quick_only = false) {

		if (!is_a($order, 'WC_Order') && is_numeric($order)) $order = $this->get_order($order);
		
		if (!is_object($order)) return false;

		$order_id = $this->wc_compat->get_id($order);

		if ($allow_quick) {
			if (!empty($this->vat_paid_post_id) && $this->vat_paid_post_id == $order_id && !empty($this->vat_paid_info)) {
				$vat_paid = $this->vat_paid_info;
			} else {
				$vat_paid =  $this->wc_compat->get_meta($order, 'vat_compliance_vat_paid', true);
			}
			if (!empty($vat_paid)) {
				$vat_paid = maybe_unserialize($vat_paid);
				// If by_rates is not set, then we need to update the version of the data by including that data asap
				if (isset($vat_paid['by_rates'])) return $vat_paid;
			}
			if ($quick_only) return false;
		}

// This is the wrong approach, kept for the purposes of illustration only. What we actually need to do is to take the rate ID, and see what table that comes from. Tables are 1:1 in relationship with classes; thus, certain rate IDs just don't count.
/*
		$items = $order->get_items();
		if (empty($items)) return false;

		foreach ($items as $item) {
			if (!is_array($item)) continue;
			$tax_class = (empty($item['tax_class'])) ? 'standard' : $item['tax_class'];
			if (!$this->product_taxable_class_indicates_variable_digital_vat($tax_class)) {
				// New-style EU VAT does not apply to this product - do something
				
			}
		}
*/

		$taxes = $order->get_taxes();

		// Remove this check in case WooCommerce invents a new object in future and gives it array access (as they have with other objects in the past)
 		// if (!is_array($taxes)) return false;
		if (empty($taxes)) $taxes = array();

		// Get an array of string matches
		$vat_strings = $this->get_vat_matches('regex');

		// Not get_woocommerce_currency(), as currency switcher plugins filter that.
		$base_currency = get_option('woocommerce_currency');
		// get_order_currency is since WC 2.1.
		// get_order_currency changed to get_currency in WC 3.0
		$currency = method_exists($order, 'get_currency') ? $order->get_currency() : $order->get_order_currency();

		$vat_total = 0;
		$vat_shipping_total = 0;
		$vat_total_base_currency = 0;
		$vat_shipping_total_base_currency = 0;

		// Add extra information
		// N.B. In WC 3.0+, what is returned is an array of WC_Order_Item_Tax objects; that class implements an array-access interface
		$taxes = $this->add_tax_rates_details($taxes);

		$by_rates = array();

		// Some amendments here in versions 1.5.5+ inspired by Diego Zanella
		foreach ($taxes as $tax) {

			// There used to be a !is_array($tax) check here - that fails on WC 2.7+, as we are now dealing with WC_Order_Item_Tax objects (with an array interface)
			if (!isset($tax['label']) || !preg_match($vat_strings, $tax['label'])) continue;

			$tax_rate_class = empty($tax['tax_rate_class']) ? 'standard' : $tax['tax_rate_class'];

			$is_variable_eu_vat = $this->product_taxable_class_indicates_variable_digital_vat($tax_rate_class);

			$tax_rate_id = $tax['rate_id'];
			
			if (!isset($by_rates[$tax_rate_id])) {
				$by_rates[$tax_rate_id] = array(
					'is_variable_eu_vat' => $is_variable_eu_vat,
					'items_total' => 0,
					'shipping_total' => 0,
				);
				$by_rates[$tax_rate_id]['rate'] = $tax['tax_rate'];
				$by_rates[$tax_rate_id]['name'] = $tax['tax_rate_name'];
			}

			if (!empty($tax['tax_amount'])) $by_rates[$tax_rate_id]['items_total'] += $tax['tax_amount'];
			if (!empty($tax['shipping_tax_amount'])) $by_rates[$tax_rate_id]['shipping_total'] += $tax['shipping_tax_amount'];

			if ($is_variable_eu_vat) {
				if (!empty($tax['tax_amount'])) $vat_total += $tax['tax_amount'];
				if (!empty($tax['shipping_tax_amount'])) $vat_shipping_total += $tax['shipping_tax_amount'];

				// TODO: Remove all base_currency stuff from here - instead, we are using conversions at reporting time
				if ($currency != $base_currency) {
					if (empty($tax['tax_amount_base_currency'])) {
						// This will be wrong, of course, unless your conversion rate is 1:1
						if (!empty($tax['tax_amount'])) $vat_total_base_currency += $tax['tax_amount'];
						if (!empty($tax['shipping_tax_amount'])) $vat_shipping_total_base_currency += $tax['shipping_tax_amount'];
					} else {
						if (!empty($tax['tax_amount'])) $vat_total_base_currency += $tax['tax_amount_base_currency'];
						if (!empty($tax['shipping_tax_amount'])) $vat_shipping_total_base_currency += $tax['shipping_tax_amount_base_currency'];
					}
				} else {
					$vat_total_base_currency = $vat_total;
					$vat_shipping_total_base_currency = $vat_shipping_total;
				}
			}
		}

		// We may as well return the kitchen sink, since we've spent the cycles on getting it.
		$vat_paid = apply_filters('wc_eu_vat_compliance_get_vat_paid', array(
			'by_rates' => $by_rates,
			'items_total' => $vat_total,
			'shipping_total' => $vat_shipping_total,
			'total' => $vat_total + $vat_shipping_total,
			'currency' => $currency,
			'base_currency' => $base_currency,
			'items_total_base_currency' => $vat_total_base_currency,
			'shipping_total_base_currency' => $vat_shipping_total_base_currency,
			'total_base_currency' => $vat_total_base_currency + $vat_shipping_total_base_currency,
		), $order, $taxes, $currency, $base_currency);

/*
e.g. (and remember, there may be other elements which are not VAT).

Array
(
    [62] => Array
        (
            [name] => GB-VAT (UNITED KINGDOM)-1
            [type] => tax
            [item_meta] => Array
                (
                    [rate_id] => Array
                        (
                            [0] => 28
                        )

                    [label] => Array
                        (
                            [0] => VAT (United Kingdom)
                        )

                    [compound] => Array
                        (
                            [0] => 1
                        )

                    [tax_amount_base_currency] => Array
                        (
                            [0] => 2
                        )

                    [tax_amount] => Array
                        (
                            [0] => 3.134
                        )

                    [shipping_tax_amount_base_currency] => Array
                        (
                            [0] => 2.8
                        )

                    [shipping_tax_amount] => Array
                        (
                            [0] => 4.39
                        )

                )

            [rate_id] => 28
            [label] => VAT (United Kingdom)
            [compound] => 1
            [tax_amount_base_currency] => 2
            [tax_amount] => 3.134
            [shipping_tax_amount_base_currency] => 2.8
            [shipping_tax_amount] => 4.39
        )


*/
		if ($set_on_quick) {
			$this->wc_compat->update_meta_data($order, 'vat_compliance_vat_paid', apply_filters('wc_eu_vat_compliance_vat_paid', $vat_paid, $order));
		}

		$this->vat_paid_post_id = $order_id;
		$this->vat_paid_info = $vat_paid;

		return $vat_paid;

	}

	// This is here as a funnel that can be changed in future, without needing to adapt everywhere that calls it
	public function round_amount($amount) {
		return apply_filters('wc_eu_vat_compliance_round_amount', round($amount, 2), $amount);
	}

	// This function lightly adapted from the work of Diego Zanella
	protected function add_tax_rates_details($taxes) {
		global $wpdb, $table_prefix;

		if (empty($taxes) || !is_array($taxes)) return $taxes;

		$tax_rate_ids = array();
		foreach($taxes as $order_tax_id => $tax) {
			// Keep track of which tax ID corresponds to which ID within the order.
			// This information will be used to add the new information to the correct
			// elements in the $taxes array
			$tax_rate_ids[(int)$tax['rate_id']] = $order_tax_id;
		}

// No reason to record these here
// 				,TR.tax_rate_country
// 				,TR.tax_rate_state
		$SQL = "
			SELECT
				TR.tax_rate_id
				,TR.tax_rate
				,TR.tax_rate_class
				,TR.tax_rate_name
			FROM
				".$table_prefix."woocommerce_tax_rates TR
			WHERE
				(TR.tax_rate_id IN (%s))
		";
		// We cannot use $wpdb::prepare(). We need the result of the implode()
		// call to be injected as is, while the prepare() method would wrap it in quotes.
		$SQL = sprintf($SQL, implode(',', array_keys($tax_rate_ids)));

		// Populate the original tax array with the tax details
		$tax_rates_info = $wpdb->get_results($SQL, ARRAY_A);
		foreach ($tax_rates_info as $tax_rate_info) {
			// Find to which item the details belong, amongst the order taxes
			$order_tax_id = (int)$tax_rate_ids[$tax_rate_info['tax_rate_id']];
			$taxes[$order_tax_id]['tax_rate'] = $tax_rate_info['tax_rate'];
			$taxes[$order_tax_id]['tax_rate_name'] = $tax_rate_info['tax_rate_name'];
			$taxes[$order_tax_id]['tax_rate_class'] = $tax_rate_info['tax_rate_class'];
// 			$taxes[$order_tax_id]['tax_rate_country'] = $tax_rate_info['tax_rate_country'];
// 			$taxes[$order_tax_id]['tax_rate_state'] = $tax_rate_info['tax_rate_state'];

			// Attach the tax information to the original array, for convenience
			$taxes[$order_tax_id]['tax_info'] = $tax_rate_info;
		}

		return $taxes;
	}

	public function get_rate_providers($just_this_one = false) {
		$provider_dirs = apply_filters('wc_eu_vat_rate_provider_dirs', array(WC_EU_VAT_COMPLIANCE_DIR.'/rate-providers'));
		$classes = array();
		foreach ($provider_dirs as $dir) {
			$providers = apply_filters('wc_eu_vat_rate_providers_from_dir', false, $dir);
			if (false === $providers) {
				$providers = scandir($dir);
				foreach ($providers as $k => $file) {
					if ('.' == $file || '..' == $file || '.php' != strtolower(substr($file, -4, 4)) || 'base-' == strtolower(substr($file, 0, 5)) || !is_file($dir.'/'.$file)) unset($providers[$k]);
				}
			}
			foreach ($providers as $file) {
				$key = str_replace('-', '_', sanitize_title(basename(strtolower($file), '.php')));
				$class_name = 'WC_EU_VAT_Compliance_Rate_Provider_'.$key;
				if (!class_exists($class_name)) include_once($dir.'/'.$file);
				if (class_exists($class_name)) $classes[$key] = new $class_name;
			}
		}
		if ($just_this_one) {
			return isset($classes[$just_this_one]) ? $classes[$just_this_one] : false;
		}
		return $classes;
	}

	public function plugin_action_links($links, $file) {
		if (!is_array($links) || false === strpos($file, basename(WC_EU_VAT_COMPLIANCE_DIR).'/eu-vat-compliance')) return $links;
		
		$settings_link = '<a href="'.admin_url('admin.php?page=wc_eu_vat_compliance_cc').'">'.sprintf(__("%s VAT Compliance Dashboard", "woocommerce-eu-vat-compliance"), $this->get_vat_region_title('adjective')).'</a>';
		array_unshift($links, $settings_link);
		if (false === strpos($file, 'premium')) {
			$settings_link = '<a href="https://www.simbahosting.co.uk/s3/product/woocommerce-eu-vat-compliance/">'.__("Premium Version", "woocommerce-eu-vat-compliance").'</a>';
			array_unshift($links, $settings_link);
		}

		return $links;
	}

	public function woocommerce_settings_tax_options_end() {
		woocommerce_admin_fields($this->settings);
	}

	public function woocommerce_update_options_tax() {
		if (isset($_POST['woocommerce_eu_vat_compliance_vat_match'])) woocommerce_update_options($this->settings);
	}

	// From WC 2.2
	public function order_status_to_text($status) {
		$order_statuses = array(
			'wc-pending'    => _x( 'Pending Payment', 'Order status', 'woocommerce-eu-vat-compliance'),
			'wc-processing' => _x( 'Processing', 'Order status', 'woocommerce-eu-vat-compliance'),
			'wc-on-hold'    => _x( 'On Hold', 'Order status', 'woocommerce-eu-vat-compliance'),
			'wc-completed'  => _x( 'Completed', 'Order status', 'woocommerce-eu-vat-compliance'),
			'wc-cancelled'  => _x( 'Cancelled', 'Order status', 'woocommerce-eu-vat-compliance'),
			'wc-refunded'   => _x( 'Refunded', 'Order status', 'woocommerce-eu-vat-compliance'),
			'wc-failed'     => _x( 'Failed', 'Order status', 'woocommerce-eu-vat-compliance'),
		);
		$order_statuses = apply_filters( 'wc_order_statuses', $order_statuses );

		if (true === $status) return $order_statuses;

		if (substr($status, 0, 3) != 'wc-') $status = 'wc-'.$status;
		
		return isset($order_statuses[$status]) ? $order_statuses[$status] : __('Unknown', 'woocommerce-eu-vat-compliance').' ('.substr($status, 3).')';
	}

	/**
	 * Runs upon the WP action before_woocommerce_init
	 */
	public function before_woocommerce_init() {
		$this->wc = WC();
	}

	/**
	 * Runs upon the WP action plugins_loaded
	 */
	public function plugins_loaded() {

		// Request WooCommerce to download the GeoIP database periodically to keep it up to date, even if the base WC settings are not for geo-location (we want it for our own usage)
		if (defined('WC_VERSION') && version_compare(WC_VERSION, '3.9', '>=')) {
			// From WC 3.9, woocommerce_geolocation_update_database_periodically is deprecated
			add_filter('woocommerce_maxmind_geolocation_update_database_periodically', '__return_true');
		} else {
			add_filter('woocommerce_geolocation_update_database_periodically', '__return_true');
		}
	
		// https://github.com/woocommerce/woocommerce/wiki/How-Taxes-Work-in-WooCommerce#prices-including-tax---experimental-behavior
		if ('yes' == get_option('woocommerce_eu_vat_compliance_same_prices', 'no')) {
			add_filter('woocommerce_adjust_non_base_location_prices', '__return_false');
		}

		load_plugin_textdomain('woocommerce-eu-vat-compliance', false, basename(WC_EU_VAT_COMPLIANCE_DIR).'/languages');

		if (!apply_filters('wc_eu_vat_compliance_ajax_update_checkout_totals_handler', false)) add_action( 'woocommerce_checkout_update_order_review', array( $this, 'ajax_update_checkout_totals' )); // Check during ajax update totals
		
		$this->settings = apply_filters('wc_eu_vat_compliance_settings_after_forbid_checkout', array(array(
			'name' => __("Forbid EU VAT checkout", 'woocommerce-eu-vat-compliance'),
			'desc' => __("If this option is selected, then <strong>all</strong> check-outs by EU customers (whether consumer or business) which contain goods subject to variable EU VAT (whether the customer is exempt or not) will be forbidden.", 'woocommerce-eu-vat-compliance').' ',
			'desc_tip' 	=> __('This feature is intended only for sellers who wish to avoid issues from EU variable VAT regulations entirely, by not selling any qualifying goods to EU customers (even ones who are potentially VAT exempt).', 'woocommerce-eu-vat-compliance' ).' '.__("Check-out will be forbidden if the cart contains any goods from the relevant tax classes indicated below, and if the customer's VAT country is part of the EU.", 'woocommerce-eu-vat-compliance'),
			'id' => 'woocommerce_eu_vat_compliance_forbid_vatable_checkout',
			'type' => 'checkbox',
			'default' => 'no'
		)));

		$this->settings[] = array(
			'name' => __('Phrase matches used to identify VAT', 'woocommerce-eu-vat-compliance'),
			'desc' => __('A comma-separated (optional spaces) list of strings (phrases) used to identify taxes which are EU VAT taxes. One of these strings must be used in your tax name labels (i.e. the names used in your tax tables) if you wish the tax to be identified as EU VAT.', 'woocommerce-eu-vat-compliance'),
			'id' => 'woocommerce_eu_vat_compliance_vat_match',
			'type' => 'text',
			'default' => $this->default_vat_matches
		);

		$this->settings[] = array(
			'name' => __("Relevant tax classes", 'woocommerce-eu-vat-compliance'),
			'desc' => __("Select all tax classes which are used in your store for products sold under EU Digital VAT regulations", 'woocommerce-eu-vat-compliance'),
			'id' => 'woocommerce_eu_vat_compliance_tax_classes',
			'type' => 'wceuvat_taxclasses',
			'default' => 'yes'
		);
		
		$this->settings[] = array(
			'name' => __("Same net prices everywhere", 'woocommerce-eu-vat-compliance'),
			'desc' => __("This turns on WooCommerce's experimental feature to change the base price of products in order to achieve the same final (after tax) price for buyers all locations (whatever their tax rate). Note that it is still WooCommerce core that performs all pricing calculations (this option just exposes the core feature); we cannot provide support for calculation issues.", 'woocommerce-eu-vat-compliance').' <a href="https://github.com/woocommerce/woocommerce/wiki/How-Taxes-Work-in-WooCommerce#prices-including-tax---experimental-behavior">'.__('More information', 'woocommerce-eu-vat-compliance').'</a>',
			'id' => 'woocommerce_eu_vat_compliance_same_prices',
			'type' => 'checkbox',
			'default' => 'no'
		);

		$this->settings[] = array(
			'name' => __('Invoice footer text (B2C)', 'woocommerce-eu-vat-compliance'),
			'desc' => __("Text to prepend to the footer of your PDF invoice for transactions with VAT paid and non-zero (for supported PDF invoicing plugins)", 'woocommerce-eu-vat-compliance'),
			'id' => 'woocommerce_eu_vat_compliance_pdf_footer_b2c',
			'type' => 'textarea',
			'css' => 'width:100%; height: 100px;'
		);

	}

	// Function adapted from Aelia Currency Switcher under the GPLv3 (https://aelia.co)
	private function get_visitor_ip_address() {

		$forwarded_for = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];

		// Field HTTP_X_FORWARDED_FOR may contain multiple addresses, separated by a
		// comma. The first one is the real client, followed by intermediate proxy
		// servers

		$ff = explode(',', $forwarded_for);

		$forwarded_for = array_shift($ff);

		$visitor_ip = trim($forwarded_for);

		// The filter makes it easier to test without having to visit another country.
		return apply_filters('wc_eu_vat_compliance_visitor_ip', $visitor_ip, $forwarded_for);
	}

	// Here's where the hard work is done - where we get the information on the visitor's country and how it was discerned
	// Returns an array
	public function get_visitor_country_info() {

		$ip = $this->get_visitor_ip_address();

		$info = null;

		// If CloudFlare has already done the hard work, return their result (which is probably more accurate)
		if (!empty($_SERVER["HTTP_CF_IPCOUNTRY"])) {
			$info = null;
			$country_info = array(
				'source' => 'HTTP_CF_IPCOUNTRY',
				// April 2016 - saw a case of CloudFlare returning in lower-case, contrary to the ISO standard. Saw a changelog from Diego today that indicated he's seeing the same thing
				'data' => strtoupper($_SERVER["HTTP_CF_IPCOUNTRY"])
			);
		} elseif (class_exists('WC_Geolocation') && ($data = WC_Geolocation::geolocate_ip()) && is_array($data) && isset($data['country'])) {
			$info = null;
			$country_info = array(
				'source' => 'woocommerce',
				'data' => $data['country']
			);
		} elseif (!function_exists('geoip_detect_get_info_from_ip')) {
			$country_info = array(
				'source' => 'geoip_detect_get_info_from_ip_function_not_available',
				'data' => false
			);
		}

		// Get the GeoIP info even if CloudFlare has a country - store it
		if (function_exists('geoip_detect_get_info_from_ip')) {
			if (isset($country_info)) {
				$country_info_geoip = $this->construct_country_info($ip);
				if (is_array($country_info_geoip) && isset($country_info_geoip['meta'])) $country_info['meta'] = $country_info_geoip['meta'];
			} else {
				$country_info = $this->construct_country_info($ip);
			}

		}

		return apply_filters('wc_eu_vat_compliance_get_visitor_country_info', $country_info, $info, $ip);
	}

	// Make sure that function_exists('geoip_detect_get_info_from_ip') before calling this
	public function construct_country_info($ip) {
		$info = geoip_detect_get_info_from_ip($ip);
		if (!is_object($info) || empty($info->country_code)) {
			$country_info = array(
				'source' => 'geoip_detect_get_info_from_ip',
				'data' => false,
				'meta' => array('ip' => $ip, 'reason' => 'geoip_detect_get_info_from_ip failed')
			);
		} else {
			$country_info = array(
				'source' => 'geoip_detect_get_info_from_ip',
				'data' => $info->country_code,
				'meta' => array('ip' => $ip, 'info' => $info)
			);
		}
		return $country_info;
	}

	/**
	 * Indicates whether the running plugin is the Premium version, or not
	 *
	 * @return Boolean
	 */
	public function is_premium() {
		return is_object(WooCommerce_EU_VAT_Compliance('WC_EU_VAT_Compliance_Premium'));
	}
}
endif;

if (!function_exists('WooCommerce_EU_VAT_Compliance')):
function WooCommerce_EU_VAT_Compliance($class = 'WC_EU_VAT_Compliance') {
	global $woocommerce_eu_vat_compliance_classes;
	return (!empty($woocommerce_eu_vat_compliance_classes[$class]) && is_object($woocommerce_eu_vat_compliance_classes[$class])) ? $woocommerce_eu_vat_compliance_classes[$class] : false;
}
endif;

global $woocommerce_eu_vat_compliance_classes;
$woocommerce_eu_vat_compliance_classes = array();
foreach ($classes_to_activate as $cl) {
	if (class_exists($cl) && empty($woocommerce_eu_vat_compliance_classes[$cl])) {
		$woocommerce_eu_vat_compliance_classes[$cl] = new $cl;
	}
}
