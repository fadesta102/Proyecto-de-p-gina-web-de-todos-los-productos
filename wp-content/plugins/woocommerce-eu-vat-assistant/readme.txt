=== WooCommerce EU VAT Assistant ===
Contributors: daigo75
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=LVSZCS2SABN7Y
Requires at least: 4.0
Tested up to: 5.4
Tags: woocommerce, eu vat, vat compliance, iva, moss, vat rates, eu tax, hmrc, digital vat, tax, woocommerce taxes, aelia
License: GPLv3
WC requires at least: 3.0
WC tested up to: 4.0.1

Extends the standard WooCommerce sale process and assists in achieving compliance with the new EU VAT regime starting on the 1st of January 2015.

== Description ==

= This is a full version of the premium EU VAT Assistant plugin =

We are proud to say that this is [the most powerful **free** EU VAT solution](https://aelia.co/shop/eu-vat-assistant-woocommerce/?src=wp) on the market. **It was designed with you, the merchant, in mind**, and it will make it easier to deal with the new, complex EU VAT regulations. this plugin was developed by [Aelia Team - The WooCommerce internationalisation experts](https://aelia.co).

The WooCommerce EU VAT Assistant is designed to help achieving compliance with the new European VAT regulations, coming into effect on the 1st of January 2015. Starting from that date, digital goods sold to consumers in the European Union are liable to EU VAT, no matter where the seller is located. The VAT rate to apply to each sale is the one charged in the country of consumption, i.e. where the customer  resides. These new rules apply to worldwide sellers, whether resident in the European Union or not, who sell their products to EU customers. For more information: [EU: 2015 Place of Supply Changes - Mini One-Stop-Shop](https://www2.deloitte.com/global/en/pages/tax/articles/eu-2015-place-of-supply-changes-mini-one-stop-shop.html).

= How this plugin will help you =

The EU VAT Assistant plugin extends the standard WooCommerce sale process and calculates the VAT due under the new regime. The information gathered by the plugin can then be used to prepare VAT reports, which will help filing the necessary VAT/MOSS returns.

* **Tracks and records customers' location**. The EU VAT Assistant plugin also records details about each sale, to prove that the correct VAT rate was applied. This is done to comply with the new rules, which require that at least two pieces of non contradictory evidence must be gathered, for each sale, as a proof of customer's location. The evidence is saved automatically against each new order, from the moment the EU VAT compliance plugin is activated.
* **Collects evidence required by the new regulations**. All the data used to determine the VAT regime to apply is recorded in real-time, stored with the order and made available as needed.
* **Accepts and validates EU VAT numbers, adjusting VAT accordingly**. Validation of European VAT numbers is performed via the official VIES service, provided by the European Commission. This feature is equivalent to the one provided by the EU VAT Number plugin.
* **Supports a dedicated VAT currency**, which is used to generate the reports. You can sell in any currency you like, the EU VAT Assistant plugin will take care of converting the VAT amounts to the currency you will use to file your returns.
* **Can automatically populates the VAT rates for all EU countries**. With a single click, you enter the VAT rates for all 28 EU countries. No more tedious manual typing!
* **Includes advanced Reports**
	* *EU VAT report by Country*. This report will show you all the VAT collected under the VAT MOSS regime, as well as the VAT collected for your domestic VAT return.
	* *VIES report*. This report shows all the supplies provided to B2B customers.
	* *INTRASTAT report*. This report shows all the sales made to the EU.
	* Sales by Country (**in development**).
* **Supports ECB exchange rates in VAT MOSS reports**. VAT MOSS Reports can use either the exchange rate saved with each order, or the European Central Bank rate required to produce the official VAT MOSS returns (ref. [official documentation](https://www.revenue.ie/en/tax/vat/leaflets/mini-one-stop-shop.html)). This feature will allow you to use the most appropriate rate when producing your domestic VAT return and the VAT MOSS return.
* **Supports mixed products/services scenarios**. The new EU VAT MOSS regime applies to the sale of digital products and services that do not require significal manual intervention. Sale of services that are provided with human intervention, such as support, consultancy, design, are still subject to VAT at source. In this case, VAT has to be paid to the revenue in merchant's country. WooCommerce allows to specify to which country a tax applies, but not to which country it should be paid once collected. The EU VAT Assistant can help, by allowing merchants to specify the "payable to" country for each VAT. Such information is then displayed in the VAT reports.
* **Allows to force B2B or B2C sales**. You can decide if you wish to force customers to a valid EU VAT number at checkout, thus accepting only B2B transactions, or prevent them from doing it, thus accepting only B2C transactions.
* **Can prevent sales to specific countries**. You can exclude some countries from the list of allowed ones, thus preventing customers from those countries from placing an order.
* **It's fully compatible with our internationalisation solutions**, such the [WooCommerce Currency Switcher, for multi-currency support](https://aelia.co/shop/currency-switcher-woocommerce/), [Prices by Country](https://aelia.co/shop/prices-by-country-woocommerce/), [Tax Display by Country](https://aelia.co/shop/tax-display-by-country-for-woocommerce/) and Prices by Role (coming soon).
* **Automatically updates the exchange rates that are be used to produce the VAT reports in the selected VAT currency**. The plugin can fetch exchange rates from the following providers:
  * European Central Bank
	* HM Revenue and Customs service
	* Bitpay
	* Irish Revenue (experimental)
	* Danish National Bank (sponsored by [Asbjoern Andersen](https://www.asoundeffect.com/)).
* **Fully supports refunds**. Refunds were introduced in WooCommerce 2.2, and support for it was added to our plugin right from the start.
* **Integrates with [PDF Invoices and Packing Slips plugin](https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/)**, to automatically generate EU VAT-compliant invoices.

== Requirements ==

* WordPress 4.0 or newer.
* PHP 5.3 or newer.
* WooCommerce 3.0 or newer.
* [Aelia Foundation Classes](https://aelia.co/downloads/wc-aelia-foundation-classes.zip) framework 1.8.2.161216 or newer.

== Disclaimer ==

This product has been designed to help you fulfil the requirements of the following new EU VAT regulations:

* Identify customers' location.
* Collect at least two non-contradictory pieces of evidence about the determined location.
* Apply the correct VAT rate.
* Ensure that VAT numbers used for B2B transactions are valid before applying VAT exemption.
* Collect all the data required to prepare VAT returns.

We cannot, however, give any legal guarantee that the features provided by this product will be sufficient for you to be fully compliant. By using this product, you declare that you understand and agree that we cannot take any responsibility for errors, omissions or any non-compliance arising from the use of this plugin, alone or together with other products, plugins, themes, extensions or services. It will be your responsibility to check the data produced by this product and file accurate VAT returns on time with your Revenue authority. For more information, please refer to our [terms and conditions of sale and support](https://aelia.co/terms-and-conditions-of-sale/#FreeSupportCovers).

== Frequently Asked Questions ==

= What features are included in this plugin? =

The EU VAT Assistant is a premium plugin, **with all features included**. It's based on the same framework we use for our other premium products, such as the [WooCommerce Currency Switcher](https://aelia.co/shop/currency-switcher-woocommerce/), [Prices by Country](https://aelia.co/shop/prices-by-country-woocommerce/), [Tax Display by Country](https://aelia.co/shop/tax-display-by-country-for-woocommerce/), and it follows the same quality standards. It's fully functional, without restrictions or limitations.

= Can the EU VAT Assistant validate EU VAT numbers? =

Yes. The EU VAT Assistant automatically validates the VAT number entered by the customer on the checkout page. When a valid VAT number is entered, the plugin informs WooCommerce that a VAT exemption should be applied.

= How does the EU VAT Assistant validate VAT numbers? =

Our solution relies on the official VIES service to validate VAT numbers. The EU VAT Assistant also includes several options to accept VAT numbers when the remote VIES service is unavailable or overloaded. Such options are disabled by default, and can be enabled in the plugin settings.

= Can the EU VAT Assistant show the correct VAT rate as soon as a visitor lands on the site? =

Such feature is provided by our [Tax Display by Country plugin](https://codecanyon.net/item/tax-display-by-country-for-woocommerce/8184759?ref=aelia_co), which was released at the beginning of 2014. If you like the EU VAT Assistant, we invite you to purchase the Tax Display by Country as well, and enjoy the powerful features of a comprehensive tax compliance solution, at a small price. The revenue will also help us covering the maintenance costs of the EU VAT Assistant, allowing us to keep it 100% free (you won't have to buy a "premium version" with us).

= I would like to show the same prices to all customers, regardless of the applicable VAT =
Our [Tax Display by Country plugin](https://codecanyon.net/item/tax-display-by-country-for-woocommerce/8184759?ref=aelia_co) includes such feature as well, using it is as simple as ticking a box.

= Does the EU VAT Assistant guarantee compliance with regulations? =

We developed the EU VAT Assistant to be as accurate as possible, in order to help fulfil the requiremenets of the EU VAT MOSS regulations. [As required by WordPress policies](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/), we can't guarantee legal compliance.
Similarly, although our solution is flexible enough to cover scenarios that falls outside the EU VAT MOSS regulations (e.g. the sale of physical products), we can't promise that it will cover all of them. That's simply due to the presence of too many variables, which could introduce edge cases that our tests didn't cover.

Of course, if you spot a specific condition in which the EU VAT Assistant doesn't seem to work as expected, please feel free to report any issue you might encounter, either [via our contact form](https://aelia.co/contact) (premium support) or [on the public forum](https://wordpress.org/support/plugin/woocommerce-eu-vat-assistant) (free support). We're always happy to review each special cases, and see if we can support them in our solution.

= What is the support policy for this plugin? =

Should you encounter any difficulties with this plugin, and need support, you have several options:

1. **[Buy a support plan](https://aelia.co/shop/eu-vat-assistant-woocommerce/?src=wp)**. You will receive top class, direct assistance from our team, who will troubleshoot your site and help you to make it work smoothly. We can also help you with installation, as well as analyse customisations and development of new features.
2. **Report the issue [in the public Support section](https://wordpress.org/support/plugin/woocommerce-eu-vat-assistant/), above**. We monitor that secion on a regular basis, and we will reply as soon as we can (usually within a couple of days). Posting the request there will also allow other users to see it, and they may be able to assist you.

= I have a question unrelated to support, where can I ask it? =

Should you have any question about this product, please use the [contact form on our site](https://aelia.co/contact). We will deal with each enquiry as soon as possible. **Important**: we won't be able to provide advice about taxation, accounting or legal matters of any kind.

== Installation ==

1. Extract the zip file and drop the contents in the ```wp-content/plugins/``` directory of your WordPress installation.
2. Activate the EU VAT Assistant plugin through the **Plugins** menu in WordPress.
3. Go to ```WooCommerce > EU VAT Assistant``` to configure the plugin. **Important**: the EU VAT Assistant is very flexible and includes many options. We recommend reading the descriptions carefully, to ensure that you have a clear understanding of what each setting does. Its features can be summarised as follows:

For more information about installation and management of plugins, please refer to [WordPress documentation](https://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

== Screenshots ==

1. **Settings > Checkout**. In this section you can configure how the EU VAT Assistant will behave on the checkout page.
2. **Settings > Self-certification**. In this section you can configure if the plugin should allow customers to self-certify their location.
3. **Settings > Currency**. In this section you can specify which currency you would like to use for VAT reports. It doesn't have to match the WooCommerce base currency. In the lower section, you can choose which provider you would like to use to retrieve the exchange rates that will be used to calculate the amounts in VAT currency.
4. **Settings > Sales**. This section contains the settings that can be used to control how sales are handled (e.g. by preventing sales to some specific countries).
5. **Settings > Options**. Miscellaneous options.
6. **Settings > Shortcuts**. This section contains a few handy shortcuts to reach the WooCommerce sections related to the EU VAT compliance.
7. **Frontend > Checkout**. This screenshot shows the new elements displayed to the customer at checkout. The **EU VAT Number** field can be used by EU businesses to enter their own VAT number. The number is validated using the VIES service and, when valid, a VAT exemption is applied automatically. The **self-certification** element can be used to allow the customer to self-certify that he is resident in the country he selected. This information can be used as a further piece of evidence to prove that the correct VAT rate was applied.
8. **Admin > WooCommerce > Order edit page**. This page shows how the VAT details are displayed when an order is reviewed in the Admin section. The meta box shows the details of the VAT charged for order items and shipping, as well as the amounts refunded. **Note**: refunds are available in WooCommerce 2.2 and later.
9. **Admin > WooCommerce > Tax Settings**. This screenshots shows the Tax Settings page extended by the EU VAT Assistant. The new user inerface allows to automatically retrieve and update the European VAT rates. It's possible to choose which VAT rates are applied in each page. Another important feature is the possibility to specify to which country a VAT will have to be paid. It will be possible, for example, to apply a *20% UK VAT* for services to a German customer who buys consultancy hours, and still keep track of the fact that such tax will have to be paid to HMRC (i.e. outside of MOSS scheme).
10. **Report > EU VAT by Country**. This report shows the totals of VAT applied and refunded at each rate, for both items and shipping, grouped by country. The Export CSV button allows to export the data to a CSV file, which can be easily imported by accounting software.

== Changelog ==

= 1.13.3.200325 =
* Fix - Added UK (ISO code "GB") back to the list of EU VAT countries in WooCommerce 4.0 and later.
* Updated supported WooCommerce versions.

= 1.13.2.200319 =
* Tweak - Replaced country code with country name in the error message displayed when an invalid VAT number is entered in the plugin settings.
* Updated language files.

= 1.13.1.200319 =
* Updated logic to call the VIES VAT number validation service.

= 1.13.0.200317 =
* Fix - Removed notice message that could occasionally appear on the EU VAT by Country report.
* Updated supported WooCommerce versions.

= 1.12.6.200212 =
* Tweak - Clarified error message logged when a requester VAT number saved in the EU VAT Assistant settings is too short.

= 1.12.5.200211 =
* Fix - Fixed typo that could cause an error during the logging of a "VAT number too short" message.

= 1.12.4.200131 =
* Tweak - Improved validation of minimum length of VAT numbers.
* Fix - Fixed logic to use ECB rates in Sales Summary (VAT RTD) report.
* Fix - Fixed logic fetching the list of available tax rates in EU VAT Assistant > Reports settings page.
* Updated supported WooCommerce versions.

= 1.12.3.200101 =
* Updated embedded AFC framework to version 2.0.12.200101.

= 1.12.2.191231 =
* Updated supported WooCommerce versions.

= 1.12.1.191217 =
* Fix - Handled edge condition which prevented the automatic update of exchange rates from being scheduled.
* Updated supported WooCommerce versions.

= 1.12.0.191127 =
* Fix - Fixed bug in the logic used to pass the requester VAT number to the VIES service, which caused the consultation number not to be returned by the remote service.

= 1.11.0.191108 =
* User Interface - Moved VIES validation settings to the new "VAT Validation" section.
* Feature - Added option to retry the validation of a VAT number when the VIES service is considering the requester information as not valid.
* Feature - Added option to accept VAT numbers as valid when the VIES service is unavailable.
* Feature - Added option to accept VAT numbers as valid when the VIES service is rejecting the call due to too many requests.
* Feature - Added new filter `wc_aelia_euva_vat_validation_cache_duration`. The filter allows to specify for how long the result of a VAT number validation should be cached (default: one hour).
* Improvement - Optimised reports for WooCommerce 3.7+ (EU VAT by Country, Sales Summary (VAT RTD), VIES, INTRASTAT).
* Tweak - Made minor changes to report interface, to make it lighter.
* Updated language files.

= 1.10.1.191108 =
* Improvement - Improved logic used to validate the requested VAT number on the settings page.
* Improvement - Improved logging and error checking during VAT number validation.
* Improvement - If an invalid requester VAT number is stored in the settings, the plugin won't use it to validate VAT numbers at checkout.
* User Experience - When an invalid requester VAT number is entered in the settings, the page now shows all the details related to the failed validation.
* Updated supported WooCommerce versions.

= 1.10.0.191023 =
* Improvement - Rewritten logic for the VAT RTD report, to ensure consistency with the VAT MOSS reports.
* Tweak - Improved handling of response from VIES service, to deal with mixed case keys in the response data.
* Tweak - Added styles to improve readability of VAT MOSS and VAT RTD reports.
* Fix - Fixed display of VAT number field at checkout, when it's configured to be "always required".

= 1.9.17.191022 =
* Tweak - Improved message related to the failed validation of the requester VAT number, on the settings page.
* Updated supported WooCommerce versions.

= 1.9.16.191004 =
* Improvement - Improved handling of response from VIES service, to fetch the validation error message when present.

= 1.9.15.190819 =
* Updated supported WooCommerce versions.

= 1.9.14.190618 =
* Tweak - Improved user interface on My Account > Edit Billing Address. The "VAT Number" field is now displayed on its own line.

= 1.9.13.190520 =
* Tweak - Updated URL of ECB exchange rate feed for historical rates.
* Tweak - Optimised logic used to fetch rates from the ECB historical feed.
* Updated language files.

= 1.9.12.190516 =
* Tweak - Reduced size of column `aelia_exchange_rates_history.provider_name`. This is to prevent errors in MySQL 5.6 and earlier, due to the primary key being too long.
* Fix - Fixed layout of the footer in "Sales Summary Report (VAT RTD)", to produce the correct CSV file during export.

= 1.9.11.190510 =
* Tweak - Handled edge case in which the tax rate for a refunded item or shipping could not be determined.
* Tweak - Improved consistency in rounding logic in the VAT RTD report.

= 1.9.10.190508 =
* Tweak - Updated SQL queries used to create temporary tables.
* Tweak - Improved debugging features for sales reports.
* Tweak - Improved logic used to fetch the label for the tax rates associated to an order.

= 1.9.9.190426 =
* Tweak - Improved logging during the generation of reports.
* Updated supported WooCommerce versions.

= 1.9.8.190327 =
* Fix - Fixed display of "VAT Number" field title on the checkout page.

= 1.9.7.190221 =
* Tweak - Added check to skip calls to the VIES service when a VAT number is too short.

= 1.9.6.190208 =
* Fix - Fixed loading of VAT number field during the "load address" operation on the Edit Order page.

= 1.9.5.190117 =
* Fix - Fixed URL of the ECB exchange rate feed.

= 1.9.4.190109 =
* Fix - Fixed loading of `frontend.js` script from the embedded AFC framework.

= 1.9.3.181217 =
* Tweak - Improved UI on checkout page. An empty VAT number is now displayed as invalid only when it's required.
* Fix - Fixed error that could occur when both the Aelia Foundation Classes and the embedded AFC were loaded on a site.

= 1.9.2.181212 =
* Fix - Fixed validation of merchant's VAT number while configuring the plugin for the first time.
* Tweak - Improved validation of VAT number and self-certification requirements on the checkout page.

= 1.9.1.181209 =
* Tweak - Added support for updates served via Aelia update servers (premium version).

= 1.9.0.181022 =
* Feature - Added support for "requester VAT number" in VAT validation requests to the VIES service.

= 1.8.4.181009 =
* BREAKING CHANGE - Used filter `woocommerce_checkout_fields` to display the VAT number and self certification fields.
* BREAKING CHANGE - Removed rendering of VAT number and self certification fields that used action `woocommerce_checkout_billing`.
* Tweak - Improved VAT number scripts on checkout pages.
* Tweak - Updated CSS to reflect the IDs and classes of the new VAT number and self certification fields.

= 1.8.3.181004 =
* Updated supported WooCommerce versions.

= 1.8.2.180919 =
* Fix - Fixed deprecation notice in WC 3.x.
* Feature - Added new `wc_aelia_eu_vat_assistant_validate_vat_number` filter, to allow 3rd parties to validate VAT numbers.

= 1.8.1.180802 =
* Fix - Fixed issue of VAT number being deleted in WooCommerce 3.3 and later, after updating an order from the Edit Order page.

= 1.8.0.180604 =
* Tweak - Added logic to set the "VAT exempt" flag for orders added or modified manually in the backend.

= 1.7.19.180531 =
* Updated supported WooCommerce versions.

= 1.7.18.180114 =
* UI - Added CSS to highlight odd/even rows in reports.
* Improvement - Added logic to reuse the same logger instance throughout the plugin, instead of loading a new instance every time.

= 1.7.17.180106 =
* UI - Added VAT Number field to Admin > User Profile page.

= 1.7.16.171215 =
* Tweak - Added custom repository as a new source of VAT rates.
* Updated supported WooCommerce version.

= 1.7.15.171106 =
* Tweak - Added `wc_aelia_euva_eu_vat_number_validation_complete` event upon completion of the validation of a VAT number via Ajax.

= 1.7.13.171025 =
* Tweak - Added new filters `wc_aelia_eu_vat_assistant_show_vat_field` and `wc_aelia_eu_vat_assistant_show_self_certification_field`, to simplify the customisation of the checkout page.

= 1.7.12.171019 =
* Updated supported WooCommerce version to 3.2.

= 1.7.11.170927 =
* Fix - Fixed logic used to check if the "collect VAT data for manual orders" is enabled.
* Localisation - Updated language files.

= 1.7.10.170711 =
* Tweak - Improved collection of tax data for manual orders in WooCommerce 3.0.x/3.1.x.

= 1.7.9.170710 =
* Tweak - Added check to prevent divisions by zero, with "zero VAT" rates are used.

= 1.7.9.170602 =
* Fix - Handled condition that prevented the collection of VAT exemption data in some circumstances.

= 1.7.8.170421 =
* Improvement - Removed obsolete method `EU_Invoice_Order::get_order_currency()`. Many thanks to Malte Vollmerhausen for reporting the bug and proposing a solution.

= 1.7.7.170415 =
* Fixed "hanging" comment, which caused a syntax error in the code.

= 1.7.6.170415 =
* Improvement - Compatibility with WooCommerce 3.0:
	* Replaced direct access to properties with calls to the new `get()` methods.
	* Fixed logic used to collect VAT data in WooCommerce 3.0.3.

= 1.7.4.170330 =
* Bug fix - Fixed saving of the "Is VIES service" option for variations.

= 1.7.3.170324 =
* Re-tested for compatibility with WooCommerce 3.0.

= 1.7.2.170306 =
* Improvement - Compatibility with WooCommerce 2.7:
	* Replaced call to `WC_Customer::get_country()` with `WC_Customer::get_billing_country()` in WC 2.7 and newer.

= 1.7.1.170214 =
* Improvement - Compatibility with WooCommerce 2.7:
	* Replaced `woocommerce_product_write_panels` action with `woocommerce_product_data_panels`.

= 1.7.0.161228 =
* Improvement - Compatibility with WooCommerce 2.7:
	* Replaced calls to order properties with calls to wrapper methods.

= 1.6.11.161207 =
* Replaced obsolete calls to `jQuery.delegate()` with `jQuery.on()`.
* Updated supported WordPress version to 4.7.

= 1.6.10.161104 =
* Improvement - Added compatibility with PHP 7.
* Improvement - Added filter `wc_aelia_euva_vat_country_prefixes`. This filter allows to alter the prefix associated to each EU country, which is used for the validation of VAT numbers.
* Improvement - Added possibility to override the frontend templates (VAT field and self-certification) in a theme.
* Improvement - Handled edge condition that prevented the VAT exemption from being applied in some cases.

= 1.6.9.161017 =
* Improvement - Added filter `wc_aelia_euva_eu_vat_number_raw_validation_result`. This filter allows to alter the response returned by the validation of an EU VAT number.

= 1.6.8.160727 =
* Updated supported WordPress version to 4.6.

= 1.6.8.160610 =
* Bug fix - Fixed priority of the checks related to the VAT number at checkout. Now the number is no longer required if it's hidden because the customer is in shop's base country.

= 1.6.7.160525 =
* Improvement - Added extra check when saving product metadata.

= 1.6.6.160518 =
* Tweak - The description of the "deduct VAT for customers in shop's base country" setting is now clearer.

= 1.6.5.160408 =
* Marked as compatible with WordPress 4.5.

= 1.6.4.160322 =
* Bug fix - Fixed JavaScript issue related to order. The issue was caused by an incorrect initialisation of the new features used to collect VAT information for orders entered manually.

= 1.6.3.160322 =
* Bug fix - Fixed check in `Orders_Integration` class. The incorrect check could caused a display issue in order and coupon list pages.

= 1.6.2.160315 =
* Tweak - Tweaked calculation of sales totals in EU VAT by Country report, to reduce the discrepancies caused by rounding.
* New feature - Added option to enable/disable the collection of VAT data for manual orders.
* New feature - Added VAT Number to the Billing fields in the Edit Order page.

= 1.6.2.160210 =
* New feature - Collection of VAT information for orders entered manually. The new feature allows to process orders entered manually, collecting the VAT data related to them and making it available to the reports provided by the EU VAT Assistant.

= 1.6.1.160208 =
* Fixed bug in Sales Summary and INTRASTAT reports. The bug caused shipping charges to be aggregated under the "zero" tax rates.
* Tweak - Handled the case when `get_current_screen()` doesn't return an object.

= 1.6.0.160118 =
* Added Sales Summary report. The new report will be useful to file returns such as the Irish VAT RTD.

= 1.5.9.160114 =
* Refactored INTRASTAT report.
* Updated clearfix CSS for better compatibility with 3rd party plugins.

= 1.5.8.160112 =
* Fixed UI glitches in report pages. The glitches were caused by new CSS styles introduced by WordPress 4.4.

= 1.5.7.160108 =
* Added new filter: `wc_aelia_eu_vat_assistant_customer_vat_exemption`. The filter allows to alter the result of the VAT exemption check performed by the EU VAT Assistant, for example to make a customer exempt from VAT based on custom criteria.

= 1.5.6.151230 =
* Improved UI of checkout page:
	* The VAT number field is now highlighted by WooCommerce when it's required and it's left empty.
	* Improved logic used to show/hide the VAT number and determine if it's required.

= 1.5.5.151217 =
* Refactored VAT number validation logic:
	* Replaced validation logic with a simplified process, which also returns more details about the validation result.
	* Fixed bug in VAT number validation. The bug caused the wrong error code to be returned for invalid VAT numbers (error "5002 - Could not validate" was returned instead of "5001 - VAT number not valid").
* Improved settings UI. The "Set manually" column in the Currency section now shows Select/Deselect all, which is clearer than a checkbox.

= 1.5.4.151210 =
* Added possibility to make EU VAT field compulsory when the customer enters a company name only if the address is in the EU.
* Fixed bug in display of EU VAT number at checkout. The bug caused the field to be hidden for non-EU countries even if the display option was set to "always required".
* Updated language files.

= 1.5.4.151209 =
* Added possibility to make EU VAT field compulsory when the customer enters a company name.
* Handled edge case in which the shop is configured to sell to a single country.

= 1.5.3.151126 =
* Fixed display of the EU VAT options tab for simple products.

= 1.5.2.151117 =
* Improved Admin UI. Moved links to reports to "Reports" tab and removed the "Shortcuts" tab.

= 1.5.1.151110 =
* Improved handling of EU VAT field visibility. The field is now shown and hidden dynamically in the frontend when option "Show EU VAT field when customer is located in base country" is enabled.

= 1.5.0.151109 =
* Preliminary WooCommerce 2.5 compatibility:
	* Reviewed logic used to populate the VAT rates in Tax Rates page.
	* Removed "tax payable to country" field from Tax Rates pages.
	* Improved checks to handle cases in which the "tax payable to country" field is not POSTed with the tax rate data.
* Corrected name of localisation files for Finnish.

= 1.4.17.151109 =
* Fixed invalid reference to settings constant.

= 1.4.16.151106 =
* Fixed bug in handling of EU VAT field visibility. The field is now hidden correctly when option "Show EU VAT field when customer is located in base country" is enabled.
* Fixed initialisation of Messages controller. The controller now uses the correct text domain.
* Updated language files.
* Removed redundant logic used to check for updates.

= 1.4.15.151029 =
* Fixed UI conflicts with Currency Switcher (JavaScript and CSS).
* Improved handling of reduced EU VAT rates. The plugin now uses the standard VAT rate when it finds a country that doesn't have a reduced rate (e.g. Denmark).

= 1.4.14.151022 =
* Updated link to Aelia Foundation Classes plugin.

= 1.4.13.151017 =
* Improved BitPay integration. The class now uses a simpler caching mechanism to prevent multiple calls to BitPay servers. This will avoid issues caused by the connection being refused by BitPay.

= 1.4.12.150923 =
* Fixed conflict with [Aelia Tax Display by Country plugin](https://aelia.co/shop/tax-display-by-country-for-woocommerce/). The conflict prevented VAT exemptions from being applied correctly with the "keep prices fixed" feature of the Tax Display plugin was enabled.

= 1.4.11.150810 =
* Verified compatibility with WordPress 4.3.
* Verified compatibility with WooCommerce 2.4.

= 1.4.10.150720 =
* Improved handling of EU VAT field:
	* VAT field is now displayed in *My Account > Billing* section.
	* VAT field is automatically pre-populated at checkout for registered customers.

= 1.4.9.150709 =
* Updated VIES report:
	* Added customer country and order ID.
	* Altered query to retrieve the "is service" flag for variations.

= 1.4.8.150629 =
* Improved support for refunds in EU VAT reports:
	* Improved query.
	* Added option to include/exclude refunded orders.
	* Added option to included refunds placed in the specified period, or related to orders placed in the specified period.

= 1.4.7.150625 =
* Added missing WPML configuration file.

= 1.4.6.150623 =
* Improved localisation:
	* Added missing string for Admin UI.
	* Added WPML configuration file.

= 1.4.5.150429 =
* Improved requirement checking. Now the plugin gracefully informs the user if an unsupported PHP version is installed.

= 1.4.4.150421 =
* Improved INTRASTAT report:
	* Improved UI.
	* Replaced "quarter" filters with "bi-monthly" ones.

= 1.4.3.150420 =
* Fixed bug in integration with the Currency Switcher. The bug raised a notice when the Currency Switcher settings were saved, due to an incorrect check about the saved data.

= 1.4.2.150411 =
* Fixed bugs in INTRASTAT report:
	* Fixed rendering of month names.
	* Fixed formatting.

= 1.4.1.150407 =
* Added stub for INTRASTAT Report.
* Added base `Base_VIES_Report` class.
* Added `WC21\VIES_Report` class.

= 1.4.0.150406 =
* Fixed bug in `EU_VAT_By_Country_Report::get_tax_refunds_data()`. The bug caused the report to include refunds for orders whose status was "refunded", which should be excluded by default from the report.
* Removed "Options" section from VIES Report UI. Such report does not need it (yet).
* Added "refunds options" section to EU VAT Report UI (currently disabled, as the feature is not yet complete).
* Added header view template for sales report.

= 1.3.21.150405 =
* Added UI to specify if a variable product is a service for VIES purposes.
* Completed UI to handle "is service" setting for products.
* Added logic to save and retrieve the "is service" setting.
* Fixed calculations in VIES report.

= 1.3.20.150402 =
* Added VIES report.
* Added logic to normalise the EU VAT numbers stored with each order.
* Added plugin version to the EU VAT Evidence stored with each order (previously, the version was associated only to the EU VAT Data field).
* Refactored query to produce the VIES report.
* Refactored base report classes:
	* Moved method Base_EU_VAT_By_Country_Report::get_vat_currency_exchange_rate() to Base_Report class.
* Added support for refunds to VIES report.
* Added method `Base_Report::order_statuses_to_include()`. The method allows to dynamically specify the orders to include in each report.

= 1.3.20.150409 =
* Fixed minor warning introduced in previous version.
* Added Italian localisation.

= 1.3.19.150409 =
* Fixed bug in validation of VAT numbers at checkout. The bug caused the checkout process to continue, instead of being blocked, when an invalid VAT number was entered.

= 1.3.18.150331 =
* Fixed possible bug in `WC_Aelia_EU_VAT_Assistant_Install::update_to_1_2_0_150215()`. The bug could cause the auto-update process to be considered failed, even when the creation of the exchange rates table was successful.

= 1.3.17.150318 =
* Added `wc_aelia_euva_order_is_eu_vat_number_required` filter. The filter will allow 3rd parties to decide if a valid EU VAT number is required for specific countries.

= 1.3.16.150316 =
* Updated class used for requirement checking.

= 1.3.15.150316 =
* Fixed bug in UI of EU VAT MOSS report. The bug caused the selected options not to be used correctly when a custom date range was used to generate the report.

= 1.3.14.150313 =
* Added `wc_aelia_euva_invoice_target_currencies` filter.
* Fixed bug in plugin settings management. The bug caused multi-select fields that were emptied (e.g. sale restrictions field) to be ignored if they were previously populated.

= 1.3.13.150309 =
* Added logging of VAT validation response.
* Fixed initialisation of logger in `EU_VAT_Validation`.
* Removed redundant initialisation of plugin hooks.
* Fixed processing of translations:
	* Payment provider names.
	* Title of self certification field.
* Added Finnish translations. Courtesy of Arhi Paivarinta.

= 1.3.12.150306 =
* Updated English .MO file.

= 1.3.11.150306 =
* Updated English localisation.

= 1.3.10.150306 =
* Fixed bug in recording of VAT data. The bug prevented the "tax payable to country" information from being recorded correctly.
* Improved EU VAT Report:
	* Added check to skip processing of orders on which no taxes were paid.
	* Removed *Country - Payable* from the table.
	* Improved filtering of MOSS/non-MOSS data.
	* Added grouping of MOSS and non-MOSS VAT data.
	* Improved user interface and help information of *Tax Types* filter.

= 1.3.9.150302 =
* Updated build file to include language files.

= 1.3.8.150220 =
* Set minimum required version of Aelia Foundation Classes to 1.4.11.150220.

= 1.3.7.150219 =
* Improved validation of checkout data. The new logic should prevent cases in which a VAT number cannot be validated because customer's country cannot be determined.

= 1.3.6.150219 =
* Fixed check on "shipping as evidence" on frontend JavaScript.

= 1.3.5.150219 =
* Changed selector for checkout form in frontend script. The selector was too specific and did not work with all themes. This prevented the self-certification box and VAT number field from behaving correctly.
* Fixed check on "shipping as evidence" on frontend JavaScript.

= 1.3.4.150218 =
* Fixed incorrect variable reference in JavaScript. The bug prevented the self certification box from displaying the correct country name.

= 1.3.3.150217 =
* Handled case in which reports are run using ECB rates for a date in the future (e.g. for a quarter that is not yet ended).
* Improved code documentation.

= 1.3.2.150216 =
* Extended EU VAT by Country report to allow use of ECB historical exchange rates:
	* Added `Base_Report::get_last_day_of_quarter()` method.
	* Added `Exchange_Rates_ECB_Historical_Model::get_rates_for_date()` method.
	* Added logic to automatically retrieve and store the exchange rates for a specific quarter.

= 1.3.1.150215 =
* Added Tax Rate Class information with the EU VAT data saved against each order.
* Added update script for this version.

= 1.3.0.150215 =
* Added logic to produce the EU VAT reports using the ECB rates for the last day of the quarter.

= 1.2.3.150216 =
* Fixed JavaScript bug on checkout page. The bug prevented the self-certification field from updating correctly when the EU VAT number field was hidden.

= 1.2.2.150214 =
* Added extra checks to the EU VAT Number validation logic. The checks will keep track of validations that fail due to unexpected errors in the communication with the VIES service.
* Disabled caching of VAT validation data when debug mode is active. This will force the VAT validation class to always perform live requests when in debug mode.
* Fixed bug in handling of `SERVER_BUSY` response received from VIES service.

= 1.2.1.150213 =
* Added support for another URL format in the HRMC Exchange Rates Provider. The new URL format was unexpectedly introduced by HMRC in February 2015, and it doesn't match the structure of any of the previous one. The update is backward compatible, and any of the formats supported will keep working.

= 1.2.0.150213 =
* Improved compatibility with WooCommerce 2.3:
	* Fixed issue with self certification box at checkout. The issue was caused by a breaking change in WC 2.3, which now renders checkbox fields in a different way.

= 1.1.9.150212 =
* Fixed warning on checkout page.

= 1.1.8.150207 =
* Fixed bug in automatic population of tax rates. The bug caused the rates to be saved with an incorrect country code.

= 1.1.7.150203 =
* Fixed query used to retrieve refunds for the VAT MOSS report.

= 1.1.6.150126 =
* Extended EU VAT Report to include the totals of sales and shipping charged to each country.

= 1.1.5.150120 =
* Fixed text domain.
* Added Bulgarian translation, courtesy of Ivaylo Ivanov.

= 1.1.4.150113 =
* Fixed notice in `Settings` class.

= 1.1.3.150111 =
* Added support for additional tax rate fields. The fields will allow to identify not only to which country a VAT applies, but also to which country it should be paid.
* Altered `EU VAT by Country report` to display the "country payable" for each tax amount.
* Renamed class `Aelia\WC\EU_VAT_Assistant\WCPDF\EU_Invoice_Price_Formatter` to `EU_Invoice_Helper`.

= 1.1.2.150109 =
* Fixed notices on EU VAT Report page.

= 1.1.1.150107 =
* Fixed bug in recording of customer's self-certification. Plugin always recorded "yes" even when the customer did not self-certify his location.
* Fixed minor notice messages.

= 1.1.0.150106 =
* Added possibility to disallow sales to specific countries.

= 1.0.6.150105 =
* Removed display of an empty "*VAT #*" entry in customer's address when such information is not available.

= 1.0.5.150105 =
* Improved UI.
* Added `WCPDF\EU_Invoice_Price_Formatter::reverse_charge()` method. The method allows to quickly determine if an invoice is based on EU reverse charge rules, and print the related note on the invoice.

= 1.0.4.150103 =
* Fixed minor bug at checkout. The bug caused the wrong country to be used for VAT number validation when tax was setting to use customer's shipping address.
* Improved recording of VAT data against orders. Now basic VAT details, such as the exchange rate to VAT currency, are recorded for all orders, whether VAT was applied or not.
* Improved display of VAT details in `Order Edit` admin page.

= 1.0.3.150103 =
* Extended `Order::get_vat_data()` method to allow retrieval of specific parts of the VAT data.

= 1.0.2.150101 =
* Fixed bug in reports. The bug caused reports for past quarters to appear empty.

= 1.0.1.150101 =
* Added support for the new (and unannounced) exchange rates feed used by HMRC.
Updated language files.

= 1.0.0.141231 =
* Production ready.

= 0.10.6.141231 =
* Fixed JavaScript bug in Admin section.

= 0.10.5.141231 =
* Redacted FAQ.
* Fixed minor bug in `Order::get_vat_data()`.
* Optimised in `Order::get_vat_refunds()`.

= 0.10.4.141231 =
* Fixed import of Danish National Bank exchange rates.
* Updated language files.
* Optimised loading of JavaScript parameters for `Tax Settings` admin pages.

= 0.10.3.141231 =
* Added missing file (Danish National Bank interface was missing from WordPress repository).

= 0.10.2.141231 =
* Rewritten EU VAT by Country report to correctly process VAT refunds.

= 0.10.1.141230 =
* Fixed bug in handling of VAT refunds. Now VAT refunds are calculated on the fly on order edit view page.

= 0.10.0.141230 =
* Added exchange rates provider for [Danish National Bank feed](https://www.nationalbanken.dk/en/statistics/exchange_rates/Pages/default.aspx).
* Added tracking of exchange rates provider against each order.
* Improved validation of VAT rates to be used on `WooCommerce > Tax` settings page.
* Reorganised Admin UI.
* Added possibility to make the `EU VAT Number` field optional, required, required for EU countries or hidden.

= 0.9.23.141230 =
* Improved checks on of VAT validation responses. This will prevent issues caused by corrupt cached responses.

= 0.9.22.141230 =
* Updated requirements.

= 0.9.21.141229 =
* Added recording of the timestamp of the VAT currency exchange rate.

= 0.9.20.141229 =
* Fixed bug in handling of VIES response containing non-Latin UTF-8 characters.

= 0.9.19.141229 =
* Added caching of VIES WSDL to speed up VAT validation.

= 0.9.18.141229 =
* Fixed bug in VAT Number validation. The bug caused validation to fail when "odd" characters were returned by VIES service.

= 0.9.17.141228 =
* Fixed bugs in EU VAT by Country report:
	* Fixed incorrect reference to plugin class.
	* Fixed bug in range calculation.

= 0.9.16.141228 =
* Removed unused report.
* Fixed bug in handling of VAT rates for Isle of Man and Monaco.

= 0.9.15.141227 =
* Fixed bug in handling of VAT rates for Isle of Man and Monaco.

= 0.9.14.141227 =
* Added exchange rates provider for [HMRC feed](https://www.gov.uk/government/publications/exchange-rates-for-customs-and-vat-monthly).
* Added VAT rates for Monaco and Isle of Man.

= 0.9.13.141226 =
* Fixed call to `WC_Aelia_EU_VAT_Assistant::get_eu_vat_countries()`.

= 0.9.12.141226 =
* Replaced hard-coded table prefix with dynamic one in `Order::add_tax_rates_details()`.
* Added possibility to specify if shipping country should be used as location evidence.
* Added possibility to customise the self-certification message.

= 0.9.11.141224 =
* Fixed call to auto-update mechanism (the wrong plugin ID was used

= 0.9.10.141224 =
* Rewritten EU VAT by Country report.
* Fixed several minor warnings.
* Added call to auto-update mechanism.

= 0.9.9.141223 =
* Added logic to save tax details (rate and label) with the VAT data associated to an order.
* `Order` class now saves the tax rate and the tax country with the VAT data.
* Reorganised reports:
	* Divided reports in WC2.1 and WC2.2 namespaces.
	* Restructured report class to promote code reuse.
	* Updated EU VAT report to include "processing" orders.
* Added support for shipping tax refunds.
* Rewritten code to calculate and save tax subtotals against the orders.

= 0.9.8.141222 =
* Added logic to fix incorrect country codes in the VAT Rates feed.

= 0.9.7.141221 =
* Fixed logic in validation of VAT evidence at checkout.
* Refactored `EU VAT by Country` report.

= 0.9.6.141220 =
* Implemented scaffolding classes for report management.
* Implemented `EU VAT by Country` report (draft, untested).

= 0.9.5.141218 =
* Improved recording of VAT data and evidence:
	* Reduced the amount of duplicate data stored in order's "VAT paid" metadata.
	* Altered order metabox to calculate amounts in VAT currency on the fly.
	* Modified order VAT metadata to be stored as hidden fields.
	* Repurposed the "VAT paid" metadata to a more generic "VAT data".
* Added support for refunds (WooCommerce 2.2 and newer).
* Improved UI.

= 0.9.4.141218 =
* Added logic to automatically append the VAT number to customer's formatted billing address.

= 0.9.3.141218 =
* Added exchange rates provider for [ECB feed](https://www.ecb.europa.eu/stats/exchange/eurofxref/html/index.en.html).
* Added exchange rates provider for [Irish Revenue website](https://www.revenue.ie/en/customs/businesses/importing/exchange-rates/).

= 0.9.2.141218 =
* Fixed logic used to determine the availability of sufficient evidence about customer's location.
* Fixed bug in validation of sufficient customer's location evidence.
* Added integration with [WooCommerce PDF Invoices & Packing Slips](https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/).
* Added notice to invite site administrator to complete the plugin configuration.

= 0.9.1.141217 =
* Added extra filter to facilitate 3rd party integrations.
	* Added filter `wc_aelia_eu_vat_assistant_get_order_exchange_rate`.
	* Added filter `wc_aelia_eu_vat_assistant_get_setting`.

= 0.9.0.141216 =
* Added feature to allow automatic population of EU VAT rates.

= 0.8.1.141216 =
* Renamed plugin to **EU VAT Assistance** to avoid confusion with the existing EU VAT Compliance one.

= 0.8.0.141215 =
* Added initial support for subscription renewal orders.
* Added validation of location self-certification field.

= 0.7.5.141212 =
* Added collection of the exchange rate used during the VAT calculation in `Order::update_vat_paid_data()` method.

= 0.7.0.141212 =
* Implemented handling of self-certification field:
	* Added field to checkout page.
	* Added logic to show/hide the field depending on the configuration, and on the presence of sufficient evidence or a valid VAT number.
	* Added logic to save the self certification flag against orders.

= 0.6.5.141211 =
* Improved logic that records VAT information against an order. Now data is recorded with subtotals for each tax rate.
* Improved order metabox to display the VAT totals broken down by rate.
* Improved UI of order metabox.

= 0.6.0.141210 =
* Added recording of VAT paid upon order completion.
* Added recording of VAT evidence upon order completion.
* Added meta box to display VAT information on order edit page.
* Fixed bugs in handling of VAT details.

= 0.5.0.141209 =
* Implemented integration with Currency Switcher.
	* Added automatic update of exchange rates when Currency Switcher settings change.
* Improved admin UI.
* Added `Settings::get_exchange_rates_method()`.
* Added rounding of VAT amounts during conversion to VAT currency.

= 0.4.0.141208 =
* Improved look of admin UI.
* Added settings for customer's self-certification at checkout.
* Added settings for currency management (exchange rates and VAT currency).
* Added automatic updates of exchange rates.

= 0.3.0.141207 =
* Added `EU_VAT_Validation` class to validate EU VAT numbers using VIES.
* Added view to render the EU VAT number field at checkout.
* Added frontend validation of the EU VAT number.
* Added caching of EU VAT validation responses.
* Added plugin settings UI.
* Added `Order` class template.
* Added icons to indicate if VAT number was validated correctly.

= 0.1.0.141205 =
* First plugin draft.
