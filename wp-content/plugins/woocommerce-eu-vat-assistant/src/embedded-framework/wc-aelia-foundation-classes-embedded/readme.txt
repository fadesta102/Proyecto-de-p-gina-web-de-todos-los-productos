=== Aelia Foundation Classes for WooCommerce ===
Contributors: daigo75, aelia
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=F8ND89AA8B8QJ
Tags: woocommerce, utility, framework, aelia
Requires at least: 4.0
Tested up to: 5.4
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
WC requires at least: 2.6
WC tested up to: 4.0

Adds a set of classes that can simplify the development of other plugins for WooCommerce.

== Description ==
The Aelia Foundation Classes add several classes that can simplify the development of plugins for WooCommerce. Some of the available classes are listed below.

**Namespace `Aelia\WC`**

* `IP2Location`. Implements methods to determine visitor's country. Library relies on MaxMind GeoLite2 library.
* `Order`. An extended Order class, which includes methods to retrieve attributes of orders generated in multi-currency setups.
* `Settings`. Allows to manage the settings of a plugin. The class does not rely on WooCommerce Settings API.
* `Settings_Renderer`. Allows to render the settings interface for a plugin. The can automatically render a tabbed interface, using jQuery UI.
* `Logger`. A Logger class.
* `Aelia_Plugin`. A base plugin class, which other plugins can extend. The class implements convenience methods to access plugin settings, WooCommerce settings, common paths and URLs, and automatically load CSS and JavaScript files when needed.
* `Semaphore`. Implements a simple semaphore logic, which can be used to prevent race conditions in operations that cannot run concurrently.

**Global namespace**

* Aelia_WC_RequirementsChecks. Implements the logic to use for requirement checking. When requirements are not met, a message is displayed to the site administrators and the plugin doesn't run. Everything is handled gracefully, and displayed messages are clear also to non-technical users.

This product includes GeoLite2 data created by MaxMind, available from [https://www.maxmind.com](https://www.maxmind.com). By using this software, you agree to use the GeoLite2 exclusively according to the terms set in the GeoLite2 End User License Agreement, which is available here: https://www.maxmind.com/en/geolite2/eula. If you do not wish to accept the terms, you must stop using the software immediately.

##Requirements
* WordPress 4.0 or later.
* PHP 5.4 or later.
* WooCommerce 2.6 or later

## Notes
* This plugin is provided as-is, and it's not automatically covered by free support. See FAQ for more details.

== Frequently Asked Questions ==

= What plugins used this library? =

Most of our free and premium plugins use this library. We released it to the public as many of our customers and collaborators expressed interest in using it.

= What is the support policy for this plugin? =

As indicated in the Description section, we offer this plugin **free of charge**, but we cannot afford to also provide free, direct support for it as we do for our paid products.
Should you encounter any difficulties with this plugin, and need support, you have several options:

1. **[Contact us](http://aelia.co/contact) to report the issue**, and we will look into it as soon as possible. This option is **free of charge**, and it's offered on a best effort basis. Please note that we won't be able to offer hands-on troubleshooting on issues related to a specific site, such as incompatibilities with a specific environment or 3rd party plugins.
2. **If you need urgent support**, you can avail of our standard paid support. As part of paid support, you will receive direct assistance from our team, who will troubleshoot your site and help you to make it work smoothly. We can also help you with installation, customisation and development of new features.

= I have a question unrelated to support, where can I ask it? =

Should you have any question about this product, please feel free to [contact us](http://aelia.co/contact). We will deal with each enquiry as soon as possible.

== Installation ==

1. Extract the zip file and drop the contents in the ``wp-content/plugins/`` directory of your WordPress installation.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Done. No further configuration is required.

For more information about installation and management of plugins, please refer to [WordPress documentation](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

== Changelog ==

= 2.0.16.200317 =
* Tweak - Improved error handling during automatic updates of premium plugins.

= 2.0.15.200310 =
* Updated supported WooCommerce versions.

= 2.0.14.200214 =
* Tweak - Improved error logging during automatic update of GeoIP database.

= 2.0.13.200131 =
* Tweak - Added possibility to stop setting a cookie in `Aelia_SessionManager` class.
* Tweak - Improved notice message displayed when cookies can't be set due to the headers having already been sent.
* Updated supported WooCommerce versions.

= 2.0.12.200101 =
* Fix - Downgraded MaxMind GeoIP library to align it with the one used by WooCommerce.

= 2.0.11.191231 =
* Fix - Fixed invalid reference to the Definitions class in the Updater library.

= 2.0.10.191231 =
* Tweak - Updated logic to fetch the GeoIP database.
* Updated supported WooCommerce versions.

= 2.0.9.191108 =
* Feature- Added support for custom field types to the internal settings API.
* Fix - Fixed collation used to create table `aelia_dismissed_messages`.
* Tweak - Optimised logic used to perform initialisation and cleanup on activate/deactivate events.
* Updated supported WooCommerce versions.

= 2.0.8.190822 =
* Improvement - Added filter `wc_aelia_geoip_database_exists`, to allow 3rd party to override the result of the check for the existence of the GeoIP database.
* Updated supported WooCommerce versions.

= 2.0.7.190809 =
* Updated Yahnis Elsts Plugin Update Checker library.

= 2.0.8.190715 =
* Tweak - Set default URL of GeoIP database to HTTPS protocol.

= 2.0.7.190613 =
* Tweak - Improved error handling during deactivation of a premium licence.
* Tweak - Added logging of errors occurring during the dismissal of admin messages.

= 2.0.6.190322 =
* Tweak - Added check on frontend script, to handle the case where the `wc_cart_fragments_params` variable has been removed by disabling WooCommerce's cart fragments.

= 2.0.5.190301 =
* Tweak - Improved error handling and logging in `Aelia\WC\Order` class, to handle the condition in which an invalid order ID is passed.

= 2.0.4.190201 =
* Fix - Removed notices caused by IP2Location logging.

= 2.0.3.190129 =
* Tweak - Added logging to simplify debugging of the geolocation feature.

= 2.0.2.181203 =
* Tweak - Added "httponly" option to `Aelia_SessionManager::set_cookie()`.

= 2.0.1.180821 =
* Feature - Added new method `Order::get_total_refunded_in_base_currency`;

= 2.0.0.180808 =
* Feature - Added new auto-update and licence management features for premium plugins.

= 1.9.19.180713 =
* Tweak - Added new function `aelia_date_to_string`.

= 1.9.18.180319 =
* Feature - Premium Licenses. Added possibility to easily replace a license key, without having to deactivate it first.

= 1.9.17.180307 =
* Tweak - Improved validation of remote API responses in Premium Updater.

= 1.9.16.180213 =
* Tweak - Improved handling of remote API errors in Premium Updater.

= 1.9.15.180210 =
* Dev - Added new updater to handle updates for development versions of free plugins.
* Fixed call to `empty()` in Premium Plugin Updater.

= 1.9.14.180126 =
* Fix - Fixed bug that caused the licence data from a plugin to overwrite the data from another plugin during update checks.
* Tweak - Improved IP2Location class to parse additional reverse proxy headers.

= 1.9.13.180123 =
* Improvement - Improved validation of Ajax responses.

= 1.9.12.180104 =
* Added new filters to allow filtering keys and values for cookies and session storage.

= 1.9.11.171214 =
* Updated Yahnis Plugin Updater library to version 4.x.

= 1.9.10.171201 =
* UI - Added message to inform shop administrators about the new licensing system.

= 1.9.9.171120 =
* Improvement - Added handling of exceptions in the Logger, to prevent crashes due to incorrect folder permissions.

= 1.9.8.171002 =
* UI - Consolidated the Admin UI for the support/debug settings and the license management under a single Aelia tab.

= 1.9.7.170912 =
* Fix - Improved logic used to ensure that minicart is updated when the currency changes, to handle the new "hashed" cart fragment IDs.

= 1.9.6.170823 =
* Improved license management interface for Aelia Premium Plugin Updater.

= 1.9.4.170410 =
* Improved logging. Added logic to initialise extra handlers (e.g. ChromePHP) in debug mode.

= 1.8.9.170629 =
* Fix - Logger doesn't track debug messages anymore when debug mode is deactivated.

= 1.8.8.170506 =
* Tweak - Updated logic to detect frontend Ajax calls

= 1.8.7.170426 =
* Lowered permission required to manage plugin's settings to "manage_woocommerce".

= 1.8.6.170408 =
* Improved support for WooCommerce 3.0:
	*Updated Aelia\WC\Order class. Used method `Order::get_id()` instead of direct access to `Order::$id` in WooCommerce 3.0 and later.

= 1.8.6.170405 =
* Improved support for WooCommerce 3.0:
	* Extended `Aelia\WC\Order` class with getter methods, to allow accessing order meta transparently in WooCommerce 3.0 and earlier.

= 1.8.5.170317 =
* Improved Logger:
	* Cleaned up logging logic to avoid the creation of spurious log files.

= 1.8.4.170307 =
* Improved Admin Messages. Added support for custom headers and improved look.
* Added new function `aelia_wp_version_is`. The function allows to check the WordPress version.

= 1.8.3.170202 =
* Improved compatibility with WooCommerce 2.7:
	* Added method `Order::get_currency()`.
* Removed legacy code.

= 1.8.3.170110 =
* Added logic to check for updates for premium plugins.
* Added new class `Aelia\WC\AFC\Messages`, to handle plugin's messages.

= 1.8.2.161216 =
* Improved compatibility with WooCommerce 2.7:
	* Added function `aelia_get_product_id()`.
	* Added checks to prevent product raw meta from being overwritten after a currency conversion.

= 1.8.1.160816 =
* Added new wc_aelia_afc_validate_ajax_nonce filter. The filter allows to bypess the validation of the Ajax nonce for requests that don't need it.

= 1.8.0.160728 =
* Introduced new Logger class, based on Monolog.

= 1.7.5.160722 =
* Added workaround for logging issue introduced by WooCommerce 2.6. WC 2.6 may trigger "loggable" events too early, when the required WordPress functions are not yet loaded. The workaround will prevent the logger from crashing.

= 1.7.4.160705 =
* Added new filter `wc_aelia_afc_exchange_rates_decimals`. The filter allows to alter the amount of decimals used when retrieving the exchange rates for a currency.

= 1.7.3.160531 =
* Added functions to handle Ajax calls.

= 1.7.2.160513 =
* Fixed minor issue in the SQL for the creation of `aelia_dismissed_messages` table. The CREATE TABLE statement contained a field set as NULLable as part of the primary key, and this could cause an error in some MySQL configurations.

= 1.7.1.160403 =
* Fixed bug caused by CloudFlare geolocation passing lowercase country codes.

= 1.7.0.160329 =
* Added new `Aelia_Integration` class.
* Updated external libraries.

= 1.6.16.160317 =
* Fixed bug in installer class. The bug caused the error message "Updates halted" to be displayed when the `aelia_dismissed_messages` was not created because it already existed. Such condition does not represent an error, and should not interrupt the updates.

= 1.6.15.160304 =
* Added new `Settings::factory()` method.
* Added new CSS classes to settings pages. The classes will help identify the elements on settings pages of Aelia plugins.
* Added logic to return the HTML of settings elements, instead of rendering them.

= 1.6.14.160209 =
* Updated 3rd party libraries.

= 1.6.13.160128 =
* Fixed bug in `Order::get_meta()` method. The method did not return the loaded value, thus always returning "null".

= 1.6.12.160120 =
* Improved flexibility of permission handling. Now it's easier to change the capability required to manage the settings of a plugin.

= 1.6.11.151217 =
* Improved support for HTML5 input fields. The SettingsRenderer class can now render input fields of different types, such as number, email, and so on.
* Fixed missing property `ExchangeRatesModel::$_base_currency`.

= 1.6.10.151105 =
* Improved support for admin notices. The logic used to render admin notices has been refactored to be more efficient and easier to maintain.
* Optimised performance. Added a caching mechanism to speed up the retrieval of the settings controller instance.
* Prevented loading of frontend JavaScript in Admin section.
* Refactored method `Order::get_order_currency()` to be compatible with parent method's signature.

= 1.6.9.151103 =
* Added support for dismissable messages.
* Updated language files.

= 1.6.8.151007 =
* Removed unneeded code. The calls to `register_install_hook()` and `register_uninstall_hook()` have been removed from the base plugin class, as they were superfluous (they registered hooks were never invoked).

= 1.6.7.150910 =
* Updated CSS to improve backend UI.
* Fixed bug in the initialisation of AFC Logger instance.

= 1.6.6.150825 =
* Fixed text domain references in `IP2Location` class.

= 1.6.5.150822 =
* Fixed reference to logger class in IP2Location::update_database().

= 1.6.4.150820 =
* Improved performance of requirement checking class.

= 1.6.3.150815 =
* Added new Aelia_Plugin::is_frontend() method. This method will allow plugins to implement their custom logic to determine if they are working on a frontend page.

= 1.6.2.150731 =
* Improved geolocation features:
	* Added message explaining how the new geolocation system works.
	* Fixed bug in the installation of the Geolocation database.
* Improved requirement checking class.
* Added language files.

= 1.6.1.150728 =
* Improved geolocation:
	* Removed geolocation DB from plugin package.
	* Added automatic installation of geolocation database on activation.
	* Improved error checking, to ensure that issues encountered during automatic update of geolocation DB won't cause crashes.
	* Added caching of geolocation results.
	* Improved logging mechanism.
	* Added new method to retrieve visitor's State/county and city.
* Added new "Admin Messages" feature. This feature will allow plugins to display admin messages in a simple and straightforward way.

= 1.6.0.150724 =
* Improved geolocation:
	* Replaced MaxMind database with "City" one.
	* Added automatic update of GeoIP database.
	* Added functions to geolocate the city information.

= 1.5.19.150625 =
* Added new `Aelia_Plugin::editing_order()` method.
* Added new `Aelia_Plugin::doing_reports()` method.
* Improved rendering of setting pages. Now content posted by customer is escaped, to prevent issues due to HTML embedded in it.

= 1.5.18.150604 =
* Added new `aelia_wc_registered_order_types()` function. The function will provide a list of the registered order types even in WC2.1 and earlier.

= 1.5.17.150529 =
* Improved requirement checking. Now the plugin gracefully informs the user if an unsupported PHP version is installed.

= 1.5.16.150519 =
* Updated requirements. The AFC plugin now requires at least WooCommerce 2.1.9, as method `WC_Session_Handler::has_session()`, invoked by the plugin Session Manager, was introduced in that release.

= 1.5.15.150518 =
* Improved rendering of plugin settings page:
	* Added check to prevent raising a warning when no plugin settings are found.
	* Added CSS class to the plugin settings form, to simplify styling.

= 1.5.14.150514 =
* Improved performance. Moved call to updater and installer to the `admin_init` event.

= 1.5.13.150514 =
* Fixed bug in initialisation of WooCommerce session. the bug caused the session to be initialised when not needed.
* Added caching of session status, to improve performance.

= 1.5.12.150512 =
* Optimised Composer autoloader to improve performance.
* Updated GeoIP library and database.
* Refactored `get_value()` function. The function now uses `isset()` to determine the existence of a key. NOTE: this change makes the function behave differently from before. Now it will return the default value also if the key IS set, but it's "null".
* Refactored `Aelia_Plugin::path()` and `Aelia_Plugin::url()` to improve performance.
* Added `get_arr_value()` function.

= 1.5.11.150507 =
* Extended `Aelia_SessionManager class`. Added `set_cookie()` and `get_cookie()` methods.

= 1.5.10.150505 =
* Added `aelia_wc_version_is()` function. The function allows quickly compare the version of WooCommerce against an arbitrary version value.

= 1.5.9.150504 =
* Added support for CloudFlare. The IP2Location class can now use the country detect by CloudFlare and skips its internal detection logic.
* Added new *wc_aelia_ip2location_before_get_country_code* filter. This new filter will allow 3rd parties to set the country code as they wish, skipping the geolocation detection logic.

= 1.5.8.150429 =
* Improved check to prevent initialising a WooCommerce session when one was already started.
* Added new method to check if a WooCommerce session was started.
* Removed legacy code for WooCommerce 1.6.x.

= 1.5.7.150408 =
* Changed scope of `Aelia_Plugin::visitor_is_bot()` to static.
* Updated GeoIP database.

= 1.5.6.150402 =
* Optimised auto-update logic. The logic now keeps track of the last successful step and, in case of error, it resumes the updates starting from it, rather than from the beginning .
* Added logic to prevent automatic initialisation of sessions when bots visit the site.

= 1.5.5.150318 =
* Fixed bug in handling of error messages related to invalid widget classes.

= 1.5.4.150316 =
* Refactored `Aelia_WC_RequirementsChecks` class to fix incompatibility with wpMandrill plugin.
* Fixed bug in plugin activation logic. The bug caused the plugin activation to fail if the plugin requirements were not indicated as an array.

= 1.5.3.150312 =
* `Aelia_WC_RequirementsChecks` class - Fixed notice related to check for plugin url.

= 1.5.2.150309 =
* Fixed loading of text domain in `Aelia\WC\Aelia_Plugin` base class.

= 1.5.1.150305 =
* Improved `Aelia_WC_RequirementsChecks` class:
	* Fixed `Aelia_WC_RequirementsChecks::js_url()` method. The method now returns the full path to the JS Admin files, without requiring further
	* Improved messages displayed to the Admins.
	* Added `Aelia_WC_RequirementsChecks::$js_dir` property, to allow plugins to specify a custom JS directory.
	* Added `Aelia_WC_RequirementsChecks::$required_php_version` property, to allow plugins to specify the version of PHP.
	* Added logic to prevent enqueuing the `admin-install.js` script more than once.

= 1.5.0.150225 =
* Extended `Aelia_WC_RequirementsChecks` class. Added support for automatic installation and activation of required plugins.

= 1.4.11.150220 =
* Improved `Aelia_Install::add_column()` method. The method is now more powerful and flexible.

= 1.4.10.150209 =
* Changed scope of `Aelia\WC\Aelia_Plugin::log()` to `public`.

= 1.4.9.150111 =
* Added `Aelia_Install::column_exists()` method.
* Added `Aelia_Install::add_column()` method.

= 1.4.8.150109 =
* Added `attributes` argument to `Settings_Renderer::render_text_field()`.
* Added `attributes` argument to `Settings_Renderer::render_checkbox_field()`.

= 1.4.7.150107 =
* Improved requirement checker class. Replaced absolute plugin path with `WP_PLUGIN_DIR` constant.

= 1.4.6.150106 =
* Fixed bug in `Settings_Renderer::render_dropdown_field()`. The bug prevented the CSS class specified in field settings from being applied to the rendered element.

= 1.4.5.141230 =
* Added Httpful library.

= 1.4.4.141224 =
* Fixed bug in auto-update mechanism that prevented external plugin from being able to call it.

= 1.4.3.141223 =
* Refactored `Semaphore` class to use MySQL `GET_LOCK()`.
* Moved automatic updates to WordPress `init` event.

= 1.4.2.141222 =
* Updated GeoIP database.

= 1.4.1.141214 =
* Improved display of "missing requirements" messages.

= 1.4.0.141210 =
* Improved performance in Admin sections. Admin pages now run initialisation code only when they are requested.
* Improved rendering of checkboxes. The new logic ensures that a value is always posted for a checkbox, whether it's ticked or unticked.
* Added `Aelia\WC\Order::get_meta()` method.

= 1.3.0.141208 =
* Added base `Aelia\WC\ExchangeRatesModel` class.
* Added `Settings_Renderer::render_dropdown_field()` method.
* Improved semaphore logic used during auto-updates to reduce race conditions.
* Updated GeoIP database.

= 1.2.3.141129 =
* Added `WC\Aelia\Aelia_Plugin::log()` method.
* Added `Aelia\WC\Order::get_customer_vat_number()` method.
* Added possibility to show a description below the fields in plugin's settings page.
* Added `Aelia\WC\Settings_Renderer::render_text_field()` method.
* Added `Aelia\WC\Settings_Renderer::render_checkbox_field()` method.
* Added `AeliaSimpleXMLElement` class.
* Added support for database transactions to `Aelia_Install` class.
* Added `Model` class.
* Fixed bug in `Settings_Renderer::default_settings()` method.

= 1.2.2.141023 =
* Improved checks in `Aelia\WC\Settings::load()`. Method can now detect and ignore corrupt settings.

= 1.2.1.141017 =
* Added new `Aelia\WC\Aelia_Plugin::init_woocommerce_session()` method, which initialises the WooCommerce session.

= 1.2.0.141013 =
* Added `aelia_t()` function. The function integrates with WPML to translate dynamically generated text and plugin settings.

= 1.1.3.140910 =
* Updated reference to `plugin-update-checker` library.

= 1.1.2.140909 =
* Updated `readme.txt`.

= 1.1.1.140909 =
* Cleaned up build file.

= 1.1.0.140909 =
* Added automatic update mechanism.

= 1.0.12.140908 =
* Added `Aelia_SessionManager::session()` method, as a convenience to retrieve WC session

= 1.0.11.140825 =
* Fixed minor bug in `IP2Location` class that generated a notice message.

= 1.0.10.140819 =
* Fixed logic used to check and load plugin dependencies in `Aelia_WC_RequirementsChecks` class.

= 1.0.9.140717 =
* Refactored semaphore class:
	* Optimised logic used for auto-updates to improve performance.
	* Fixed logic to determine the lock ID for the semaphore.

= 1.0.8.140711 =
* Improved semaphore used for auto-updates:
	* Reduced timeout to forcibly release a lock to 180 seconds.
* Modified loading of several classes to work around quirks of Opcode Caching extensions, such as APC and XCache.

= 1.0.7.140626 =
* Added geolocation resolution for IPv6 addresses.
* Updated Geolite database.

= 1.0.6.140619 =
* Modified loading of Aelia_WC_RequirementsChecks class to work around quirks of Opcode Caching extensions, such as APC and XCache.

= 1.0.5.140611 =
* Corrected loading of plugin's text domain.

= 1.0.4.140607 =
* Modified logic used to load main class to allow dependent plugins to load AFC for unit testing.

= 1.0.3.140530 =
* Optimised auto-update logic to reduce the amount of queries.

= 1.0.2.140509 =
* Updated Composer dependencies.
* Removed unneeded code.

== Upgrade Notice ==

= 2.0 =
Version 2.0 is a major update of the Aelia Foundation Classes. Although the upgrade is designed to be 100% backward compatible, we recommend take a full backup of your site before upgrading the plugin and test your ecommerce flow after the update.
