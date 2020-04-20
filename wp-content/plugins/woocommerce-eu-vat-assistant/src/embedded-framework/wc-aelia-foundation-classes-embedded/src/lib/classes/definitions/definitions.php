<?php
namespace Aelia\WC;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Implements a base class to store definitions for the plugin.
 */
class Definitions {
	// @var string The menu slug for plugin's settings page.
	const MENU_SLUG = 'aelia_foundation_classes';
	// @var string The plugin slug
	const PLUGIN_SLUG = 'wc-aelia-foundation-classes';
	// @var string The plugin text domain
	const TEXT_DOMAIN = 'wc-aelia-foundation-classes';

	// @var string The URL to the support request page
	const URL_SUPPORT = 'https://aelia.co/contact';

	// Get/Post Arguments
	const ARG_INSTALL_GEOIP_DB = 'aelia_install_geoip_db';
	const ARG_MESSAGE_ID = 'aelia_msg_id';
	const ARG_AJAX_COMMAND = 'exec';

	const ARG_AJAX_ACTION = 'exec';
	const ARG_LICENSE_KEY = 'license_key';
	const ARG_PRODUCT_SLUG = 'product_slug';
	const ARG_SITE_URL = 'site_url';
	const ARG_SITE_NAME = 'site_name';
	const ARG_SITE_DESCRIPTION = 'site_description';
	const ARG_INSTALLED_VERSION = 'installed_version';
	const ARG_REMOTE_USER_ID = 'user_id';
	const ARG_REMOTE_USER_EMAIL = 'user_email';
	const ARG_ORDER_ID = 'order_id';
	const ARG_LICENSE_ID = 'license_id';
	const ARG_SITE_ID = 'site_id';

	// Remote Ajax requests (from client sites)
	const REQ_REMOTE_ACTIVATE_SITE = 'remote_activate_site';
	const REQ_REMOTE_DEACTIVATE_SITE = 'remote_deactivate_site';
	const REQ_REMOTE_CHECK_PRODUCT_VERSION = 'remote_check_product_version';
	const REQ_REMOTE_GET_PRODUCT_UPDATE = 'remote_get_product_update';

	// License Activation statuses
	const SITE_ACTIVE = 'active';
	const SITE_INACTIVE = 'inactive';

	// Error codes
	const OK = 0;
	const RES_OK = 0;
	const ERR_COULD_NOT_UPDATE_GEOIP_DATABASE = 1100;

	const ERR_PRODUCT_LICENSE_NOT_SET = 2100;
	// @since 1.9.10.171201
	const NOTICE_NEW_LICENSING_SYSTEM = 2101;
	// @since 2.0.9.191108
	const ERROR_INVALID_PRODUCT_FOR_UPDATER = 2103;

	const ERR_INVALID_AJAX_REQUEST = 17001;
	const ERR_AJAX_COMMAND_MISSING = 17002;
	const ERR_AJAX_INVALID_COMMAND = 17003;
	const ERR_AJAX_INVALID_REFERER = 17004;
	const ERR_REMOTE_REQUEST_HTTP_ERROR = 17005;
	const ERR_REMOTE_REQUEST_UNEXPECTED_RESPONSE = 17006;
	const ERR_REMOTE_REQUEST_UNSUCCESSFUL = 17007;
	const ERR_REMOTE_REQUEST_RESPONSE_EMPTY = 17008;
	// @since 2.0.16.200317
	const ERR_REMOTE_REQUEST_UNAUTHORIZED = 17009;

	// Premium Updater - Licenses
	const ERR_COULD_NOT_ADD_LICENSE = 5001;
	const ERR_LICENSE_FOR_ORDER_ITEM_EXISTS = 5002;
	const ERR_LICENSE_FOR_ORDER_ITEM_NOT_FOUND = 5003;
	const ERR_LICENSE_NOT_FOUND = 5004;
	const ERR_LICENSE_NOT_ACTIVE = 5005;
	const ERR_COULD_NOT_UPDATE_LICENSE = 5006;
	const ERR_COULD_NOT_REVOKE_LICENSE = 5007;
	const ERR_ORDER_STATUS_NOT_VALID = 5008;
	const ERR_LICENSE_NOT_VALID_FOR_UPDATES = 5009;
	const ERR_LICENSE_STATUS_NOT_VALID = 5010;
	const ERR_INVALID_PRODUCT_SLUG = 5011;

	const ERR_PRODUCT_PACKAGE_NOT_FOUND = 5012;
	const ERR_LICENSE_EXPIRED = 5013;
	const ERR_COULD_NOT_SET_STATUS_FOR_EXPIRED_LICENSES = 5014;
	const ERR_LICENSE_KEY_NOT_ACTIVE = 5015;

	// Premium Updater - Sites
	const ERR_COULD_NOT_ADD_SITE = 6001;
	const ERR_COULD_NOT_UPDATE_SITE = 6002;
	const ERR_LICENSE_MAX_ACTIVATIONS_REACHED = 6003;
	const ERR_COULD_NOT_VALIDATE_ACTIVATION = 6004;
	const ERR_SITE_ACTIVATION_ALREADY_EXISTS = 6005;
	const ERR_SITE_ALREADY_INACTIVE = 6006;
	const ERR_COULD_NOT_DEACTIVATE_SITE = 6007;
	const ERR_COULD_NOT_VALIDATE_DEACTIVATION = 6008;
	const ERR_SITE_DOES_NOT_EXIST = 6009;
	const ERR_SITE_NOT_ACTIVE = 6010;

	// Session/User Keys
	const SESSION_USER_LOGGED_IN = 'aelia_user_logged_in';

	// Options
	const OPT_LICENCE_INFO_PREFIX = 'aelia_plugin_licence_key_';

	// Transients
}
