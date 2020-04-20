<?php
namespace Aelia\WC\EU_VAT_Assistant;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use \Aelia\WC\Messages;
use \WP_Error;

/**
 * Implements a base class to store and handle the messages returned by the
 * plugin. This class is used to extend the basic functionalities provided by
 * standard WP_Error class.
 */
class Definitions {
	// @var string The menu slug for plugin's settings page.
	const MENU_SLUG = 'wc_aelia_eu_vat_assistant';
	// @var string The plugin slug
	const PLUGIN_SLUG = 'wc-aelia-eu-vat-assistant';
	// @var string The plugin text domain
	const TEXT_DOMAIN = 'wc-aelia-eu-vat-assistant';

	/**
	 * The slug used to check for updates.
	 *
	 * @var string
	 * @since 1.9.1.181209
	 */
	const PLUGIN_SLUG_FOR_UPDATES = 'woocommerce-eu-vat-assistant';

	// GET/POST Arguments
	const ARG_COUNTRY = 'country';
	const ARG_VAT_NUMBER = 'vat_number';
	const ARG_LOCATION_SELF_CERTIFICATION = 'customer_location_self_certified';
	const ARG_TAX_TYPE = 'tax_type';
	const ARG_EXCHANGE_RATES_TYPE = 'exchange_rates_type';
	const ARG_REFUNDS_PERIOD = 'refunds_period';
	const ARG_INCLUDE_REFUNDED_ORDERS = 'include_refunded_orders';
	const ARG_COLLECT_ORDER_VAT_INFO = 'collect_order_vat_info';
	const ARG_COLLECT_ORDER_ID = 'order_id';

	// Session constants

	// Transients
	const TRANSIENT_EU_NUMBER_VALIDATION_RESULT = 'aelia_wc_eu_vat_validation_';
	const TRANSIENT_EU_VAT_RATES = 'aelia_wc_eu_vat_rates';

	// Error codes
	const RES_OK = 0;
	const ERR_INVALID_TEMPLATE = 1001;
	const ERR_INVALID_SOURCE_CURRENCY = 1103;
	const ERR_INVALID_DESTINATION_CURRENCY = 1104;
	const ERR_INVALID_EU_VAT_NUMBER = 5001;
	const ERR_COULD_NOT_VALIDATE_VAT_NUMBER = 5002;

	const YES = 'yes';
	const NO = 'no';
	const ALL = 'all';
	const VAT_NUMBER_VALIDATION_NO_NUMBER = 'no-number';
	const VAT_NUMBER_VALIDATION_VALID = 'valid';
	const VAT_NUMBER_VALIDATION_NOT_VALID = 'not-valid';
	const VAT_NUMBER_VALIDATION_NON_EU = 'non-eu';
	const VAT_NUMBER_COULD_NOT_BE_VALIDATED = 'could-not-be-validated';
	const VAT_NUMBER_ENTERED_MANUALLY_NOT_VALIDATED = 'entered-manually-not-validated';

	// Argument values
	// EU VAT Report
	const TAX_MOSS_ONLY = 'moss_only';
	const TAX_NON_MOSS_ONLY = 'non_moss_only';
	const TAX_ALL = 'all_tax_types';
	const FX_SAVED_WITH_ORDER = 'saved_with_order';
	const FX_ECB_FOR_QUARTER = 'ecb_rates_for_quarter';

	const REFUNDS_FOR_ORDERS_IN_PERIOD = 'refunds_for_orders_in_period';
	const REFUNDS_IN_PERIOD = 'refunds_granted_in_period';

	// Sales Report
	const SALES_EU_ONLY = 'eu_sales_only';
	const SALES_NON_EU_ONLY = 'non_eu_sales_only';
	const SALES_WITH_VAT = 'sales_with_vat';
	const SALES_WITHOUT_VAT = 'sales_without_vat';

	// Fields
	const FIELD_VIES_PRODUCT_IS_SERVICE = '_vies_product_is_service';
	const FIELD_VAT_NUMBER = 'vat_number';

	// Messages
	const NOTICE_NEW_SALES_SUMMARY_REPORT = 12001;

	// URLs
	const URL_CONTACT_FORM = 'https://aelia.co/contact';
	const URL_PUBLIC_SUPPORT_FORUM = 'https://wordpress.org/support/plugin/woocommerce-eu-vat-assistant/';
}
