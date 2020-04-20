=== EU VAT Compliance Assistant for WooCommerce ===
Contributors: DavidAnderson
Requires at least: 4.4
Tested up to: 5.4
Stable tag: 1.14.6
Tags: woocommerce, eu vat, vat compliance, iva, moss, vat rates, eu tax, hmrc, digital vat, tax, woocommerce taxes
License: GPLv3+
Donate link: https://david.dw-perspective.org.uk/donate

Assists with EU VAT compliance for WooCommerce, for the EU VAT regime that began 1st January 2015, including for with the MOSS system.

== Description ==

= The EU VAT (IVA) law =

Since January 1st 2015, all digital goods (including electronic, telecommunications, software, ebook and broadcast services) sold across EU borders have been liable under EU law to EU VAT (a.k.a. IVA) charged in the country of *purchase*, at the VAT rate in that country (background information: <a href="http://www2.deloitte.com/global/en/pages/tax/articles/eu-2015-place-of-supply-changes-mini-one-stop-shop.html">http://www2.deloitte.com/global/en/pages/tax/articles/eu-2015-place-of-supply-changes-mini-one-stop-shop.html</a>). This applies even if the seller is not based in the EU, and there is no minimum threshold.

= How this plugin can take away the pain =

This WooCommerce plugin provides features to assist with EU VAT law compliance. Currently, those features include:

- <strong>Identify your customers' locations:</strong> this plugin will record evidence of your customer's location, using their billing or shipping address, and their IP address (via a GeoIP lookup).

- <strong>Evidence is recorded, ready for audit:</strong> full information that was used to calculate VAT and customer location is displayed in the WooCommerce order screen in the back-end.

- <strong>Display prices including correct VAT from the first page:</strong> GeoIP information is also used to show the correct VAT from the first time a customer sees a product. A widget and shortcode are also provided allowing the customer to set their own country.

- <strong>Currency conversions:</strong> Most users (if not everyone) will be required to report VAT information in a specific currency. This may be a different currency from their shop currency. This feature causes conversion rate information to be stored together with the order, at order time. Currently, four official sources of exchange rates are available: the European Central Bank (ECB), the Danish National Bank, the Central Bank of the Russian Federation, and HM Revenue & Customs (UK).

- <strong>Entering and maintaining each country's VAT rates:</strong> this plugin assists with entering EU VAT rates accurately by supplying a single button to press in your WooCommerce tax rates settings, to add or update rates for all countries (standard or reduced) with one click.

- <strong>Reporting:</strong> Advanced reporting capabilities, allowing you to see all the information needed to make a MOSS (mini one-stop shop) VAT report. The report is sortable and broken down by country, VAT rate, VAT type (traditional/variable) and order status, and can be exported as a CSV.

- <strong>Forbid EU sales if any goods have VAT chargeable</strong> - for shop owners for whom EU VAT compliance is too burdensome, this feature will allow you to forbid EU customers to check-out if they have selected any goods which are subject to EU VAT (whilst still allowing purchase of other goods, unlike the built-in WooCommerce feature which allows you to forbid check-out from some countries entirely).

- <strong>Central control:</strong> brings all settings, reports and other information into a single centralised location, so that you don't have to deal with items spread all over the WordPress dashboard.

- <strong>Mixed shops:</strong> You can sell goods subject to EU VAT under the 2015 digital goods regulations and other physical goods which are (until 2016) subject to traditional base-country-based VAT regulations. The plugin supports this via allowing you to identify which tax classes in your WooCommerce configuration are used for 2015 digital goods items. Products which you place in other tax classes are not included in calculations/reports made by this plugin for per-country tax liabilities, even if VAT was charged upon them. (For such goods, you will calculate how much you owe your local tax-man by using WooCommerce's built-in tax reports).

- <strong>Distinguish VAT from other taxes:</strong> if you are in a jurisdiction where you have to apply other taxes also, then this plugin can handle that: it knows which taxes are EU VAT, and which are not.

- <strong>Add line to invoices:</strong> If VAT was paid on the order, then an extra, configurable line can be added to the footer of the PDF invoice (when using the <a href="https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/">the free WooCommerce PDF invoices and packing slips plugin</a>).

- <strong>Refund support:</strong> includes information on refunded VAT, on relevant orders.

- <strong>Same prices:</strong> Not strictly an EU VAT compliance issue (different pricing per-country is perfectly legal), but this plugin adds an option to enable WooCommerce's hidden support for adjusting pre-tax prices to enable the same post-tax (net) price to apply in all customer locations.

<a href="https://www.simbahosting.co.uk/s3/product/woocommerce-eu-vat-compliance/">A Premium version is on sale at this link</a>, and currently has these *additional* features ready:

- <strong>VAT-registered buyers can be exempted, and their numbers validated:</strong> a VAT number can be entered at the check-out, and it will be validated (via VIES). Qualifying customers can then be exempted from VAT on their purchase, and their information recorded. The VAT number is stored in the same format as the old official WooCommerce "EU VAT Number" extension, so any tools you had which rely on the stored format will be unaffected. The customer's VAT number will be appended to the billing address where shown (e.g. order summary email, PDF invoices). An extra, configurable line specific to this situation can be added to the footer of the PDF invoice (when using the <a href="https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/">the free WooCommerce PDF invoices and packing slips plugin</a>).

- <strong>Optionally allow B2B sales only</strong> - for shop owners who wish to only make sales that are VAT-exempt (i.e. B2B sales only), you can require that any EU customers (optionally including or excluding those in your country) enter a valid EU VAT number at the check-out.

- <strong>CSV download:</strong> A CSV containing comprehensive information on all orders with EU VAT data can be downloaded (including full compliance information). Manipulate in your spreadsheet program to make arbitrary calculations.

- <strong>Non-contradictory evidences:</strong> require two non-contradictory evidences of location (if the customer address and GeoIP lookup contradict, then the customer will be asked to self-certify his location, by choosing between them).

- <strong>Show multiple currencies for VAT taxes on PDF invoices produced by <a href="https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/">the free WooCommerce PDF invoices and packing slips plugin</a></strong>.

- <strong>Support for the official WooCommerce subscriptions extension, and for Subscriptio (a RightPress/CodeCanyon alternative)</strong>

<a href="https://www.simbahosting.co.uk/s3/product/woocommerce-eu-vat-compliance/">Read more about the Premium version of this plugin at this link.</a>

It is believed (but not legally guaranteed), that armed with the above capabilities, a WooCommerce shop owner will be in a position to fulfil all the requirements of the EU VAT law: identifying the customer's location and collecting multiple pieces of evidence, applying the correct VAT rate, validating VAT numbers for B2B transactions, and having the data needed to create returns. (If in the EU, then you will also need to make sure that you are issuing your customers with VAT invoices containing the information required in your jurisdiction, via <a href="https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/">a suitable WooCommerce invoice plugin</a>).

= Footnotes and legalese =

This plugin is supported on, and information in this document is for, WooCommerce 3.4 up to 4.0 (you can still <a href="https://wordpress.org/plugins/woocommerce-eu-vat-compliance/advanced/">download older versions supporting previous WooCommerce release series if you wish</a>). It fetches data on current VAT rates from Amazon S3 (using SSL if possible); or, upon failure to connect to Amazon S3, from https://raw.githubusercontent.com. If your server's firewall does not permit this, then it will use static data contained in the plugin.

Geographical IP lookups are performed via WooCommerce's built-in geo-location features; or, alternatively, if you use CloudFlare, then you can <a href="https://support.cloudflare.com/hc/en-us/articles/200168236-What-does-CloudFlare-IP-Geolocation-do-">activate the CloudFlare feature for sending geographical information</a>.

Please make sure that you review this plugin's installation instructions and have not missed any important information there.

Please note that, just as with WordPress and its plugins generally (including WooCommerce), this plugin comes with no warranty of any kind and you deploy it entirely at your own risk. Furthermore, nothing in this plugin (including its documentation) constitutes legal or financial or any other kind of advice of any sort. In particular, you remain completely and solely liable for your own compliance with all taxation laws and regulations at all times, including research into what you must comply with. Installing any version of this plugin does not absolve you of any legal liabilities, or transfer any liabilities of any kind to us, and we provide no guarantee that use of this plugin will cover everything that your store needs to be able to do.

Whether you think the EU's treaties with other jurisdictions will lead to success in enforcing the collection of taxes in other jurisdictions is a question for lawyers and potential tax-payers, not for software developers!

Many thanks to Diego Zanella, for various ideas we have swapped whilst working on these issues. Thanks to Dietrich Ayala and other authors, whose NuSOAP library is included under the LGPLv2 licence.

= Other information =

- <a href="https://www.simbahosting.co.uk/s3/shop/">Some other WooCommerce plugins you may be interested in</a>

- This plugin is ready for translations (English, Dutch, Finnish, French and German are currently available), and we would welcome new translations (please post them in the support forum; <a href="https://plugins.svn.wordpress.org/woocommerce-eu-vat-compliance/trunk/languages/">the POT file is here</a>, or you can contact us and ask for a web-based login for our translation website).

== Installation ==

Standard WordPress installation; either:

- Go to the Plugins -> Add New screen in your dashboard and search for this plugin; then install and activate it.

Or

- Upload this plugin's zip file into Plugins -> Add New -> Upload in your dashboard; then activate it.

After installation, you will want to configure this plugin, as follows:

1) If you are selling goods for which the VAT rate should depend upon the buyer's country, then go to WooCommerce -> Settings -> Tax -> Standard Rates, and press the "Add / Update EU Digital VAT Rates", making sure that "Standard" is selected in the rates drop-down.

2) If you have products that are liable for VAT at a reduced rate, then also go to WooCommerce -> Settings -> Tax -> Reduced Rate Rates, and press the "Add / Update EU Digital VAT Rates", making sure that "Reduced" is selected in the rates drop-down.

You must remember, of course, to make sure that a) your WooCommerce installation is set up to apply taxes to your sales (WooCommerce -> Settings -> Tax) and b) that your products are placed in the correct tax class (choose "Products" from the WordPress dashboard menu).

== Frequently Asked Questions ==

= How can I display a widget allowing a visitor to pre-select their country, when viewing products (and thus set VAT accordingly)? =

There is a widget for this; so, look in your dashboard, in Appearance -> Widgets. You can also display it anywhere in page content, using a shortcode, shown here with the default values for the available parameters: [euvat_country_selector include_notaxes="1" classes="" include_which_countries="all"]. The 'include_notaxes' parameter controls whether to include a "I am not liable to VAT" option. The 'classes' parameter allows you to add CSS classes to the resulting container. The 'include_which_countries' parameter can take the values "all", "shipping" (those countries that your store ships to, as indicated by the WooCommerce settings) or "selling" (those that it sells to), to indicate which countries should be included in the list.

= I want to make everyone pay the same prices, regardless of VAT =

This is not strictly an EU VAT compliance issue, and as such, does not come under the strict remit of this plugin. (Suggestions that can be found on the Internet that charging different prices in difference countries breaks non-discrimination law have no basis in fact). However, WooCommerce does include *experimental* support for this (see: <a href="https://github.com/woocommerce/woocommerce/wiki/How-Taxes-Work-in-WooCommerce#prices-including-tax---experimental-behavior">https://github.com/woocommerce/woocommerce/wiki/How-Taxes-Work-in-WooCommerce#prices-including-tax---experimental-behavior</a>), and so from version 1.12.10 of this plugin onwards (Aug 2018), we have provided an option in the settings to tell WooCommerce to turn this on.</a>

== Changelog ==

= 1.14.6 - 2020-03-25 =

* FIX: Navigation when choosing new ranges within WooCommerce -> Reports -> Taxes -> EU VAT Report had regressed on WooCommerce 4.0 (whilst still working within WooCommerce -> EU VAT Compliance -> Taxes)

= 1.14.5 - 2020-03-21 =

* TWEAK: Revert some of yesterday's changes, as the EU VIES server appears to have reverted its own behaviour

= 1.14.4 - 2020-03-20 =

* TWEAK: readme.txt description fix (was not mentioning WC 4.0 compatibility)
* TWEAK: Updated bundled updater libraries (PUC 4.9, SPMU 1.8.3) (Premium)
* TWEAK: Updater will run availability checks without requiring login
* TWEAK: Switch to the econea/nusoap version of nusoap because of active maintenance
* TWEAK: Mark plugin as requiring PHP 5.4, as required by econea/nusoap

= 1.14.3 - 2020-03-12 =

* FIX: The UK had ceased to appear in VAT reports in WC 4.0 due to internal changes in WC 4.0

= 1.14.2 - 2020-03-07 =

* TWEAK: Mark as supporting WP 5.4 (requires 4.4+) + WC 4.0 (requires 3.4+)

= 1.14.1 - 2020-02-11 =

* TWEAK: On WooCommerce 3.9+, update the now-deprecated filter woocommerce_geolocation_update_database_periodically

= 1.14.0 - 2020-01-16 =

* FEATURE: Upon creation of an automatic subscription renewal order, a fresh VIES check will be run if relevant, and failures recorded in the order notes; the action wc_eu_vat_compliance_renewal_validation_result is also run
* FIX: At some point, a regression probably due to a WooCommerce core change occurred preventing session data for pre-selected countries being properly saved
* TWEAK: If the order country is not known when processing invoice footer text, do not add anything
* TWEAK: WooCommerce 3.8 login form has had some DOM changes which caused a little uglification; fix this
* TWEAK: Move initialisation of the checkout title and message until the 'init' hook, allowing WPML to over-ride the contents
* TWEAK: Remove some legacy code for supporting WooCommerce Subscriptions versions earlier than 2.0 (released October 2015)
* TWEAK: Add detection for the Subscriben for WooCommerce extension
* COMPATIBILITY: We now officially support WP 4.3+ and WC 3.3+ (we don't believe we've done anything to make it incompatible on earlier versions, but support is not available if you encounter problems)

= 1.13.17 - 2019/10/24 =

* TWEAK: Replace euvatrates.com/rates.json as a backup source of VAT rates, as it is not up-to-date

= 1.13.16 - 2019/10/22 =

* TWEAK: Use the action wpo_wcpdf_footer_settings_text instead of wpo_wcpdf_footer as the filter to add invoice footer text, for better compatibility with WPML + WooCommerce PDF Packing Slips + Invoices Professional

= 1.13.15 - 2019/10/19 =

* TWEAK: Updated WPML file (thanks to Jan Schrader) with entries for more options fields.
* TWEAK: Include Isle of Man in the EU VAT area list if and only if GB is found in it
* TWEAK: Mark as supporting WC 3.8
* COMPATIBILITY: We now officially support WP 4.2+ and WC 3.2+ (we don't believe we've done anything to make it incompatible on earlier versions, but support is not available if you encounter problems)

= 1.13.14 - 2019/08/27 =

* FIX: When the shop was set to calculate all taxes based on the base address, the VAT field (Premium) would show at the checkout always, even if it was configured to not do so
* TWEAK: Tweak to subscriptions support for future compatibility

= 1.13.13 - 2019/08/06 =

* TWEAK: Explicit handling for the MS_MAX_CONCURRENT_REQ and GLOBAL_MAX_CONCURRENT_REQ VIES response codes.
* TWEAK: Mark as supporting WC 3.7
* COMPATIBILITY: We now officially support WP 4.1+ and WC 3.1+ (we don't believe we've done anything to make it incompatible on earlier versions, but support is not available if you encounter problems)

= 1.13.12 - 2019/05/30 =

* TRANSLATION: Updated translations, including new Italian translation with thanks to Alessandro Spurio
* TWEAK: Updated bundled updater libraries (PUC 4.6, SPMU 1.8.1)

= 1.13.11 - 2019/04/22 =

* TWEAK: Remove some obsolete geolocation code for supporting WooCommerce < 2.4
* TWEAK: Remove use of compatibility layer for WC_Order::update_meta_data() calls

= 1.13.10 - 2019/04/03 =

* TWEAK: When generating PDF invoices for old orders with WooCommerce PDF Invoices + Packing Slips, if configured to add exchange rate information, this will now also be added retrospectively to orders made when this plugin was not active (looking up the rates from the time of the order). To turn off this behaviour, use the filter wc_eu_vat_retrospectively_add_conversion_rates.

= 1.13.9 - 2019/04/01 =

* TWEAK: Fix layout of multiple-currency tax reporting in PDF invoices in current WooCommerce versions
* TWEAK: Add multiple-currency tax reporting in PDF invoices also with the Professional version of WooCommerce PDF Invoices and Packing Slips (and likely various other solutions too, given how it works - it's not specific to that plugin)
* TWEAK: Update bundled Premium updater class to current (1.8)
* TWEAK: Update bundled WooCommerce compat library to current (0.3.1)

= 1.13.8 - 2019/03/26 =

* TWEAK: Use the available filter to request WooCommerce to keep its GeoIP database up to date
* TWEAK: Now marked as supporting WC 3.6 (no change to minimum WC 3.0 requirement)
* TWEAK: Now marked as supporting WP 5.2 (no change to minimum WP 4.0 requirement)

= 1.13.7 - 2019/02/20 =

* TWEAK: Correction in the wpml-config.xml file
* TWEAK: If XML is fetched from an exchange rates provider but does not pass, then error_log() something to help debugging.
* TWEAK: Do an ltrim() on the fetched XML before doing the "does this actually look more like HTML?" check
* TWEAK: If the XML fetches looks like HTML, error_log() that

= 1.13.5 - 2019/02/19 =

* TWEAK: The filter wceuvat_msg_checking was mis-named as wceuvat_msh_checking; this is now corrected (if you were using it, you will need to update your code)
* TWEAK: Add CSS classes vat-result-* (e.g. valid, invalid, checking) to the DOM element #woocommerce_eu_vat_compliance_vat_number_validity to allow easier styling
* COMPATIBILITY: We now officially support WP 4.0+ (we don't believe we've done anything to make it incompatible on earlier versions)

= 1.13.4 - 2019/02/16 =

* FEATURE: Allow the VAT number field (Premium) to only display if the customer has entered a company name
* TWEAK: Adjust the wording "Add / Update EU VAT rates" to include the word "Digital" for clarity.
* TWEAK: Resolve a PHP 7.3 deprecation notice
* TWEAK: Update bundled translations

= 1.13.2 - 2018/12/18 =

* TWEAK: Add a filter wc_eu_vat_number_cache_positive_validation for users who do not wish to cache positive VAT-number validations
* TWEAK: Add a filter wc_eu_vat_store_order_vat_number allowing pre-filtering of the VAT number before it is stored in the database
* TWEAK: Update updater library (Premium) to latest version (1.5.10)
* TRANSLATIONS: Update the bundled translations (Premium version)

= 1.13.1 - 2018/12/07 =

* FIX: The XML from the Danish National Bank, if that was chosen as the exchange rate provider, lists the exchange rate for 100 DKK, not for 1 DKK.

= 1.13.0 - 2018/10/18 =

* COMPATIBILITY: Marked as compatible with WooCommerce 3.5, and now requiring 3.0+. Nothing has been specifically done to break compatibility on 2.6, but this is what we are officially supporting.
* COMPATIBILITY: Similarly, we now officially support WP 3.9+ (we don't believe we've done anything to make it incompatible on earlier versions)
* REFACTOR: Various pieces of internal re-factoring and abstraction to help keep the plugin future-ready
* TWEAK: Removed more code sections that existed to support WC versions that we stopped supporting long ago
* TWEAK: Updated the list of readiness tests in light of not-supported WC versions
* TWEAK: Replace jQuery.parseJSON with JSON.parse

= 1.12.11 - 2018/08/18 =

* FIX: Fix a regression in 1.12.10 which prevented the configured EU B2B VAT-exempted footer being added to PDF invoices

= 1.12.10 - 2018/08/11 =

* FEATURE: Expose WooCommerce's experimental option for adjusting base prices to result in the same net (after tax) price for products regardless of differing tax rates for buyers in different territories (more info: https://github.com/woocommerce/woocommerce/wiki/How-Taxes-Work-in-WooCommerce#prices-including-tax---experimental-behavior)
* TWEAK: Some readme tweaks/updates
* TWEAK: Marked as supported on WP 3.8+ (nothing has changed to make it incompatible on earlier versions, but there this is now the official support requirement)
* TWEAK: Removed various pieces of code that were providing compatibility with long-unsupported WC versions. Our official support has not changed; it's still WC 2.6+, but now various things will definitely cause fatal errors before WC 2.3 now, so don't try it!

= 1.12.9 - 2018/08/04 =

* FEATURE: Add a new capability to add invoice footer text for non-EU and non-taxed orders, as required by some national laws (Premium)

= 1.12.8 - 2018/07/30 =

* TWEAK: If Subscriptio indicates a renewal for a subscription for which it lists non-existent orders, prevent this calling a PHP Fatal when Subscriptio_Order_Handler::order_is_renewal is called on it.
* TWEAK: Prevent deprecation notices related to parse_str() on PHP 7.2+
* TWEAK: Minor tweaks to the display of raw VIES information in the order screen meta box

= 1.12.7 - 2018/05/22 =

* TWEAK: Marked as WooCommerce 3.4 compatible (now requires 2.6+)
* TWEAK: Marked as supported on WP 3.7+ (nothing has changed to make it incompatible on earlier versions, but there this is the official support requirement)

= 1.12.6 - 2018/05/16 =

* FEATURE: Add an option to anonymize any personal data in downloaded CSVs (which, for now, is just IP addresess - they did not contain any other personal data). Helps with GDPR compliance; you won't need to justify having detailed CSVs lying around or create a process for cleansing them if they do not contain any personal data.

= 1.12.5 - 2018/05/11 =

* FIX: The WC_EU_VAT_NOCOUNTRYPRESELECT constant/over-ride did not work on all PHP versions because of how PHP processes class definitions.
* TWEAK: Update updater library (Premium) to latest version (1.5.3)

= 1.12.4 - 2018/04/26 =

* TWEAK: Update call to deprecated WC_Order::get_order_currency
* TWEAK: Improve the SQL query used for generating VAT report summaries, reducing the time needed by around 80% on large sites.

= 1.12.3 - 2018/02/27 =

* FEATURE: (Premium) Introduce the wc_eu_vat_get_base_countries filter, to allow multiple countries to be considered as the 'base country', for the purpose of all options/behaviours that differentiate on the base country

= 1.12.2 - 2018/01/17 =

* FEATURE: Add a new option for use with WooCommerce Subscriptions, allowing renewal orders to not be created if they are liable to EU VAT (use case: stores which previously allowed VAT-able orders from the EU, but have changed their policy, and wish to prevent future renewals). This complements the existing "forbid EU VAT checkouts" option.
* TWEAK: The wceuvat_check_cart_items_is_forbidden filter has been abolished; a necessity arising due to a restructuring. If you used it, you should look at the wceuvat_product_list_product_list_has_relevant_products and wceuvat_cart_is_permitted filters.

= 1.12.1 - 2018/01/15 =

* FIX: Fix a regression since 1.11.27 in the "require a VAT number always outside of your base country" feature, which was causing it to be required there too.

= 1.12.0 - 2018/01/12 =

* FEATURE: New hooks added for supporting download of HMRC (UK) reporting spreadsheet (with separate plugin)
* COMAPTIBILITY: Now supporting WooCommerce 3.3 (tested with release candidate 1)
* COMPATIBILITY: Support for WooCommerce 2.4+ dropped (now supporting 2.5+)

= 1.11.27 - 2018/01/08 =

* FEATURE: Added a new option, allowing a valid EU VAT number to be required, but only if the cart contains goods liable to (which includes that the VAT address is an EU one) variable EU VAT (complementing the existing option for requiring a valid EU VAT number for all carts.
* TWEAK: Introduce a WC_EU_VAT_DEBUG constant; if set to true, then the detected country will be output in front-end page footers.

= 1.11.26 - 2017/12/02 =

* TWEAK: Add wc_eu_vat_check_vat_number_country and wc_eu_vat_disallowed_country_message filters to allow a developer to forbid VAT numbers for chosen EU countries

= 1.11.25 - 2017/11/27 =

* TWEAK: Handle a null order value being passed to woocommerce_checkout_order_processed without causing an exception.

= 1.11.24 - 2017/11/23 =

* TWEAK: Update bundled updater (Premium) to the latest version (1.5.0)

= 1.11.23 - 2017/11/07 =

* TWEAK: Avoid using a deprecated method in current WooCommerce Subscriptions releases

= 1.11.22 - 2017/10/27 =

* FIX: Fix a regression in version the Premium version of 1.11.18, which caused VAT numbers to permit VAT exemption for the base country if the shop options were set to request a number.

= 1.11.21 - 2017/10/23 =

* FEATURE: New option for the drop-down country selector, as to which countries are listed: all (default/previous behaviour), countries sold to, countries shipped to. The euvat_country_selector shortcode has been enhanced with a new parameter 'include_which_countries', which can take the values "all" (default), "shipping" or "selling".

= 1.11.19 - 2017/10/23 =

* TWEAK: Enhance the wording of the 'tax based on' compliance test to clarify that it applies to EU digital goods sales.
* TWEAK: Remove some debug messages from the updates checker

= 1.11.18 - 2017/10/17 =

* FEATURE: Option to accept any number as validated (Premium) when the taxation country is your base country (in which situation, you are usually not deducting VAT, but just wish to record their entered number, especially if you have a separate set of numbers locally)

= 1.11.17 - 2017/10/06 =

* TWEAK: Update bundled updater library for Premium (now 1.4.8)

= 1.11.16 - 2017/10/05 =

* FEATURE: Add access to Czech National Bank official exchange rates
* TWEAK: Fetch ECB exchange rates over https, not http
* TWEAK: Update bundled updater library for Premium (now 1.4.7)
* TWEAK: 'Test Provider' button now correctly shows 'Testing...' when pressed

= 1.11.15 - 2017/09/30 =

* TWEAK: Marked as compatible with the forthcoming WC 3.2 (no changes were needed), and now tested and supported from WC 2.4 onwards (one-in, one-out).

= 1.11.14 - 2017/09/26 =

* TWEAK: (Relevant to Premium only): If the user has chosen 'never' for VAT number entry and made the title and text blank, then default to showing no other text in that section (still over-rideable by the filter wc_eu_vat_nob2b_message, as before)

= 1.11.13 - 2017/09/23 =

* TWEAK: Update bundled updater library for Premium version (1.4.6)
* TWEAK: Add filters allowing developers to add columns to the detailed CSV download spreadsheet (Premium)

= 1.11.12 - 2017/08/31 =

* TWEAK: Add WooCommerce version headers (https://woocommerce.wordpress.com/2017/08/28/new-version-check-in-woocommerce-3-2/)

= 1.11.11 - 2017-08-18 =

* TWEAK: Fix unbalanced HTML tag in noscript section

= 1.11.10 - 2017-07-22 =

* TWEAK: Use the latest version (1.4.2) of the bundled updater library
* TWEAK: Use WC_Customer::set_billing_country instead of the deprecated WC_Customer::set_country when possible

= 1.11.9 - 2017-06-22 =

* FIX: HMRC's CDN URL for the download of current currency conversion rates had started rejecting the default WordPress user agent, meaning that rates were not being updated
* TWEAK: Add a new readiness test for the freshness of currently configured exchange rates
* TWEAK: Give the download of currency conversion rates a few more seconds before timing out
* TWEAK: Make the VAT number edit row in 'My Account' to have the form-row-wide class

= 1.11.8 - 2017-06-01 =

* FIX: Fix a typo that caused a fatal error when generating PDF invoices in Premium in 1.11.7

= 1.11.7 - 2017-06-01 =

* COMPATIBILITY: Mark as compatible with WordPress 4.8 (requires at least: 3.4 - nothing in particular is known to make it incompatible on previously supported versions; this just indicates that it won't be tested/supported)
* TWEAK: Prevent a deprecation notice when getting order date on WC 3.0+
* UPDATE: Update the bundled WooCommerce compatibility library
* UPDATE: Update the bundled updater library (Premium)

= 1.11.6 - 2017-05-30 =

* TWEAK: Improve integration of reporting with WooCommerce Sequential Order Numbers

= 1.11.5 - 2017-05-23 =

* FIX: The prior 1.11 series releases had a packaging error (Premium), omitting code necessary for the Premium updates mechanism

= 1.11.4 - 2017-04-13 =

* FIX: Prevent a JavaScript error on checkout on WooCommerce 3.0 when forbidding all VAT-able EU checkouts
* TWEAK: Change the default message shown when the conflict mode resolution option is set to absolutely require consistent country data before checkout can proceed, so that it is less likely to be mis-read.

= 1.11.3 - 2017-03-25 =

* COMPATIBILITY: Updated for WooCommerce 3.0 (tested with release candidate 2)
* FIX: Fix a bug which prevented WooCommerce's "Tax based on shipping address" from working correctly in the free version.
* FIX: Update the bundled woocommerce-compat library library to version 0.2.2, fixing a bug in meta handling

= 1.11.2 - 2017-03-03 =

* FIX: Fix wrong function name in get_vat_paid() method
* FIX: Fix bug in recording of VAT number (Premium version)
* TWEAK: Allow get_main_chart() in the reporting module to cope with failures in wc_get_order()

= 1.11.1 - 2017-03-03 =

* COMPATIBILITY: Updated for WooCommerce 2.7 (tested with and requires at least release candidate 1)
* TWEAK: Add wc_eu_vat_set_not_vat_exempt filter, to allow developers to stop the plugin registering customers as not-VAT exempt (e.g. if they have an extra reason for thus registering them)
* TWEAK: Import woocommerce-compat library to abstract away changes in WC 2.7
* TWEAK: Port all accesses of WC_Order::id over to woocommerce-compat library
* TWEAK: Port all accesses of WC_Product::get_price_(ex|in)cluding_tax over to woocommerce-compat library
* TWEAK: On WC 2.7+, use the WC_Customer::get_billing_country() instead of the deprecated WC_Customer::get_country()
* TWEAK: Prevent PHP notice about deprecated coding construction used in NuSOAP library on PHP 7+
* TWEAK: Added a new filter wc_euvat_compliance_wpo_wcpdf_footer_result for allowing easier over-riding of the added footer
* TWEAK: Update bundled updater (Premium) to the latest version

= 1.10.40 - 2017-01-07 =

* TRANSLATIONS: Partial Dutch translation, thanks to Peter Landman

= 1.10.39 - 2017-01-07 =

* TRANSLATIONS: Plugin is now set up for compatibility with wordpress.org's translation system. Translators welcome! https://translate.wordpress.org/projects/wp-plugins/woocommerce-eu-vat-compliance
* TRANSLATIONS: Existing translations updated (many thanks to translators)

= 1.10.38 - 2017-01-02 =

* TWEAK: Change the reports page, to have a "VAT-able supplies" column. The "Items" column (which does not take partial refunds into account) is still available, but now hidden by default.
* TWEAK: In the tax rates readiness test, if less tax rates were found in your tables than expected, then the resulting message had confusing wording.
* TWEAK: In the tax rates readiness test, accomodate the fact that sales between the Isle of Man and the UK are not accounted as exports, but as in-country transactions.
* TWEAK: Allow the rounding and formatting functions to be filtered, for easier customisation, in case anyone has varying local requirements
* FIX: If you had unused and deleted tax classes in your WooCommerce install, then the readiness test for whether you had VAT rates for each country would still see these, as WooCommerce does not actually delete them from the database when you remove them from your WooCommerce options.
* FIX: In the tax rates readiness test, some wrong rates in the 'Standard' class could be overlooked

= 1.10.36 - 2016-12-31 =

* TWEAK: Update the bundled VAT rates file to reflect the change in Romania VAT rate

= 1.10.35 - 2016-12-27 =

* TWEAK: For certain classes of VAT number lookup failure (Premium version), associated returned information about the failure was not always making it through
* TWEAK: Update the bundled updater to version 1.3.0, which allows the user to choose to automatically install updates (applies to Premium version only)

= 1.10.34 - 2016-12-16 =

* FEATURE: Added an 'export settings' button, which makes debugging/comparisons easier
* TWEAK: Remove a non-sensical line from the free/paid feature comparison table, which had crept in from the code in another of my plugins that it was originally copy/pasted from.
* FIX: A logic error meant that certain combinations of options surrounding forbidding all non-B2B orders without valid EU VAT numbers did not work correctly

= 1.10.32 - 2016-12-10 =

* FIX: On orders which had multiple refunds against them, the VAT could be totalled wrongly if the MySQL server returned records in an unexpected order.

= 1.10.31 - 2016-11-29 =

* TWEAK: Add BTW, B.T.W. (Dutch) to the list of default strings recognised as VAT-like taxes
* TWEAK: The "no VAT charged" invoice footer notice was not being added if the VAT number was accepted (i.e. VAT removed) for some other reason than a successful VIES validation; it is now added.

= 1.10.30 - 2016-10-21 =

* TWEAK: Update the bundled updater to version 1.2.1, which is more tolerant of the plugin being moved to a different location (applies to Premium version only)

= 1.10.29 - 2016-10-05 =

* TWEAK: Prevent a PHP notice when running custom reports
* PERFORMANCE: A considerable speed boost (typically 80% faster) when generating reports, via using larger page sizes on queries

= 1.10.28 - 2016-09-06 =

* FIX: Fix the operation of the VAT number box (and VAT deduction) on checkout pages for which the shop owner had restricted the allowed countries to only one.
* TWEAK: Updated the bundled updater class versions

= 1.10.26 - 2016-07-29 =

* TWEAK: The bundled VAT rates file reflects Greece's new 24% rate

= 1.10.25 - 2016-07-07 =

* COMPATIBILITY: Marked as compatible with the forthcoming WP 4.6
* FEATURE: Detailed CSV download (available in the Premium version) now includes a "payment method" column
* SECURITY: (Affects Premium version only): previous releases allowed any logged-in user to download CSV reports, by visiting a specially crafted URL. This is now restricted to those with permission to view WooCommerce reports only.

= 1.10.24 - 2016-06-29 =

* FIX: VIES has made a minor change to the format of the data it returns in the case of an invalid VAT number - previous versions of this plugin handled its new format as an 'unknown' result. (So, if your settings were that unknown results should be treated as invalid, all was well - but if they were treated as valid, this was a problem).
* TWEAK: Pass back more data in the case of an unknown VIES result from the network

= 1.10.23 - 2016-04-26 =

* TWEAK: Add a work-around to parse some of the entities that the VIES server can pass back that the XML/SOAP parser doesn't like

= 1.10.22 - 2016-04-06 =

* TWEAK: Tweak rates readiness test to be more suitable for mixed stores (thanks to Fabian Schweinfurth for the patch)

= 1.10.21 - 2016-04-04 =

* TWEAK: CloudFlare's IP country header has apparently begun returning some results in lower-case, contrary to the relevant ISO standard. Tweak code to deal with this by converting back to upper-case.

= 1.10.20 - 2016-03-31 =

* TWEAK: Improve the wording of the option that allows store to require EU buyers to enter an EU VAT number.
* FIX: The capability for a customer to edit their saved VAT number was previously only working with specific WordPress permalink structures
* COMPATIBILITY: Marked as compatible with WordPress 4.5

= 1.10.19 - 2016-01-11 =

* FEATURE: Allow the base country to be exempted from the "require all customers to enter a VAT number" option (Premium)
* FEATURE: Provide an option allowing orders to be forbidden in case of conflicts between different evidences concerning the customer's location (Premium)
* FEATURE: The customer can now edit their saved VAT number as part of editing their billing address from their account page, for future orders (Premium)

= 1.10.18 - 2016-01-09 =

* TWEAK: Fix issue with unnecessary warning in the reports caused by a bug in Subscriptio (only relevant if that plugin is active)
* TWEAK: Clarify the message indicating missing WooCommerce data when creating reports, to cover more possible causes

= 1.10.17 - 2016-01-08 =

* FEATURE: Add a new (filterable) option allowing the base country to be excluded when requiring all customers with carts liable to digital VAT to have a valid EU VAT number (so, you can exclude chargeable B2C customers outside of your own country, but allow them within it).
* TWEAK: Removed obsolete fallback to VAT-number checking service that no longer exists

= 1.10.16 - 2016-01-01 =

* COMPATIBILITY: Tested + supported on WooCommerce 2.5 (tested with beta 3)
* TWEAK: Update bundled VAT rates file to reflect updated VAT rate for Romania (20%). Do remember to visit your tax rates pages in WooCommerce, and update them to current rates. (And if you've not yet set up a readiness report that automatically emails you about potential problems in WooCommerce -> EU VAT Compliance -> Readiness Report, if you've not done so already). (Note that the bundled file isn't the first chosen source of VAT rates - so, even if you don't update to the latest plugin version, you can get the latest rates; but you do need to update your tables).
* FIX: Fix a variable misnaming causing a JavaScript error on WC < 2.5 in the only-briefly-available 1.10.16

= 1.10.14 - 2015-12-01 =

* TWEAK: When VIES is unreachable, pass more information back to the customer/shop owner (e.g. the member state's service was unavailable)

= 1.10.13 - 2015-11-25 =

* TWEAK: Make the button for adding EU VAT rates to tax settings work in WC 2.5
* TWEAK: Add an option for what to do if VIES is unreachable. Defaults to the previous option: which was to assume that the customer entered a valid VAT number (which is more common than not - but you may wish to be strict, and prefer to lose the sales until VIES is back online).

= 1.10.12 - 2015-10-31 =

* TWEAK: Add a field in the WooCommerce tax rates table allowing adjustment of the tax description (so, for example, you can use MWSt or IVA instead of the default 'VAT', and avoid having to edit each line manually)
* TWEAK: Update the alternate reduced VAT rate for Greece in the bundled JSON rates (though, alternate rates aren't currently used in the plugin)

= 1.10.11 - 2015-10-14 =

* TWEAK: When WooCommerce Subscriptions 2.0+ is in use, use the new provided method instead of relying upon a deprecated method

= 1.10.10 - 2015-10-12 =

* FIX: Remove debugging function inadvertantly left in 1.10.8
* TWEAK: Work around bug in WooCommerce Subscriptions 2.0.0 (which is fixed in WooCommerce Subscriptions 2.0.1, so you should update that)

= 1.10.8 - 2015-10-10 =

* FIX: Fix bug that caused some 100% refunded orders to have the refunded amount appear in the dashboard summary table in the row for orders with 'completed' status
* TWEAK: Small internal reorganisation of how the report is generated, allowing easier access from other scripts

= 1.10.7 - 2015-09-11 =

* TWEAK: Add a CSS class to the form element containing a drop-down country selection widget
* FIX: The setting for the store's VAT number (Premium), used for optional extended VAT checks, was rejecting some valid formats.

= 1.10.6 - 2015-08-21 =

* FEATURE: Show the customer's VAT number (if any) in the billing address on the "My Account" page
* TWEAK: Prevent PHP notice being logged when displaying order in admin when no currency conversion was needed

= 1.10.5 - 2015-08-13 =

* TWEAK: Attempt to re-parse returned VIES result with a different encoding if default parse fails on an encoding issue

= 1.10.4 - 2015-08-01 =

* TWEAK: Store's VAT number check (Premium) in readiness report will display an error message, if possible, if validation fails
* COMPATIBILITY: Tested with WooCommerce 2.4 (RC1) and WP 4.3 (RC1). No issues identified (i.e. existing release believed to be compatible).
* TRANSLATION: Updated French translation (thanks to Guy Pasteger)

= 1.10.3 - 2015-07-16 =

* FIX: Remove stray line of code in 1.10.2 which broke the EU VAT control centre page layout

= 1.10.2 - 2015-07-16 =

* FIX: Country selector shortcode now returns its output, instead of echo-ing it (which could cause it to appear in the wrong place)
* FEATURE: (Premium) All (or your selection of) "readiness tests" can now be run automatically daily, with results of any failing tests emailed to specified email addresses.
* FEATURE: The comprehensive CSV download (Premium) now includes an 'Invoice Number' column, if that feature is in use, currently supporting the WooCommerce PDF & Packing Slips plugin
* TWEAK: Remove a couple of error_log() debugging calls left in 1.10.1
* TWEAK: In in-dashboard reports, show all amounts to the number of decimal places configured in the main WooCommerce settings

= 1.10.1 - 2015-07-13 =

* FEATURE: VAT summary report table now has an option to export the table directly as a CSV file
* FEATURE: It is now possible to perform an extended VIES lookup, recording the customer's detailed information (if available) of any customers supplying VAT numbers (thanks to Sven Auhagen for code and ideas)
* FEATURE: Cause the VAT number field to be pre-populated if a logged-in repeat customer checks out
* FEATURE: Show the customer's VAT number (if any) in their profile page (in the WooCommerce customer information section)
* FEATURE: Add support for WPML for multi-language translation of fields shown at the checkout and price suffixes
* FIX: Fix issue which could cause VAT number field to wrongly not appear in certain complicated visiting country/customer country/goods/taxes combinations (required that GeoIP lookup was inaccurate, amongst other conditions)
* TWEAK: Removed a little unused code
* TWEAK: When advising of pre-WC-2.1 orders (which have incomplete information due to WC not recording it before 2.2), indicate which orders specifically are meant.
* TWEAK: It turns out that a WooCommerce order can remain in the 'Payment Pending' state forever, causing a surprising "pre-WC-2.1 order" notice in one of the charts, if a customer comes back to complete a pending order from long ago. The wording of the notice has been changed to reflect this. (Obviously, as time goes on, this condition is even more unlikely to ever be seen).
* TWEAK: Introduce wceuvat_check_cart_items_is_forbidden filter, to allow developers to apply arbitrary customisations to criteria for forbidding check-out for VAT-related reasons
* TWEAK: Stop using PHP 4-style parent constructor call in widget class
* TWEAK: Update bundled TableSorter library to latest (2.22.3)

= 1.9.3 - 2015-06-27 =

* FEATURE: Support for Subscriptio (Premium) (Subscriptio is an alternative to the official WooCommerce Subscriptions extension) - i.e. repeat orders automatically created on a schedule by Subscriptio will have VAT audit/proof of location information copied over from the original order, and the current exchange rates at the order-time will be updated.
* TWEAK: Readiness test in the free version will now alert if the Subscriptio extension is active (the free version does not contain the extra code needed to support it)

= 1.9.2 - 2015-05-07 =

* TWEAK: Prevent PHP notice with bbPress due to current_user_can() being called early

= 1.9.1 - 2015-04-09 =

* FEATURE: In-dashboard reports table now includes "refunds" column
* TWEAK: Added explanatory note and link to WooCommerce refunds documentation, to help users understand the meaning/derivation of refunds data
* TWEAK: Updated a couple of the plugin screenshots
* TWEAK: Added free/Premium comparison table to free version
* TRANSLATIONS: Updated POT file
* FIX: Fix a bug in 1.9.0 that caused 100% discounted orders (i.e. 100% coupon) to result in an erronenous message appearing in the reports dashboard

= 1.9.0 - 2015-04-08 =

* FEATURE: The order-page widget now additionally displays VAT refund information, if a refund exists on the order
* FEATURE: The CSV download (Premium) now contains additional column with VAT refund information (per-rate, and total, in both order and reporting currencies)
* TWEAK: Premium version now contains support link to the proper place (not to wordpress.org's free forum)
* FIX: "Export CSV" button/link did not handle the chosen date range correctly in all situations
* FIX: Bug that caused items in orders with the same VAT rate, but which differed through some being digital VAT and others traditional VAT (i.e. physical goods), being wrongly banded together in CSV download VAT summaries.

= 1.8.5 - 2015-04-02 =

* FEATURE: Add "Items (without VAT)" column to dashboard VAT report. (Requires all orders in the selected period to have been made with WC 2.2 or later).
* TWEAK: Tested + compatible with WP 4.2 and later (tested on beta3-31975)

= 1.8.4 - 2015-03-24 =

* TWEAK: Prevent PHP notice when collating report data on orders recorded by older versions of the plugin
* TWEAK: Change the default order statuses selected on the reports page to 'completed' and 'processing' only. (It's unlikely that data for orders with statuses like 'failed' or 'pending payment' are what people want to see at first).
* TWEAK: Cause selected status boxes on the report page to be retained when selecting a different quarter

= 1.8.3 - 2015-03-16 =

* FIX: Correct one of the VAT column names in the CSV download
* FIX: Display 0, not 1, where relevant in secondary VAT columns in the CSV download
* FIX: Prevent fatal error on reports page if the user had never saved their settings.
* TWEAK: If the user has never saved their settings, then default to using ECB as the exchange rate provider (instead of saving no currency conversion information).
* TRANSLATION: Updated POT file, and updated French and Finnish translations.

= 1.8.1 - 2015-03-13 =

* FIX: Fix issue in updater that could cause blank page on some sites

= 1.8.0 - 2015-03-05 =

* FIX: Reports table now sorts on click on column headings again (unknown when it was broken)
* FEATURE: EU VAT report now re-coded to show data in the configured reporting currency (only), and to show shipping VAT separately
* FEATURE: Downloadable CSV now shows separate VAT totals for each rate in separate rows, and shows separate rows for variable and traditional non-variable VAT (if your shop sells both kinds of goods)
directory due to licensing complications.
* FEATURE: Downloadable CSV now shows information on the configured reporting currency (as well as the order currency)
* FEATURE: (Premium) - updater now added so that the plugin integrates fully with the WP dashboard's updates mechanism
* TWEAK: Removed the static 'rates' column from the VAT report table (which only showed the current configured rates), and instead show a row for each rate actually charged.
* TWEAK: Reports page now uses the built-in WooCommerce layout, including quick-click buttons for recent quarters (some code used from Diego Zanella, gratefully acknowledged)
* TWEAK: Columns in downloadable CSV are now translatable (translations welcome)
* TWEAK: Re-ordered and re-labelled some columns in CSV download for clarity
* TWEAK: Provide link to download location for geoip-detect plugin, if relevant - it is no longer present in the wordpress.org
* TRANSLATION: New POT file

= 1.7.8 - 2015-02-28 =

* TRANSLATION: Finnish translation, courtesy of Arhi Paivarinta

= 1.7.7 - 2015-02-23 =

* FIX: Deal with undocumented change in WC's tax tables setup in WC 2.3 - the "add/update rates" feature is now working again on WC 2.3

= 1.7.6 - 2015-02-20 =

* TWEAK: VAT number fields will no longer appear at the check-out if there were no VAT-liable items in the cart
* TWEAK: Add wc_eu_vat_default_vat_number_field_value filter, allowing developers to pre-fill the VAT number field (e.g. with a previously-used value)

= 1.7.5 - 2015-02-17 =

* TWEAK: If on WC 2.3 or greater, then use WC's built-in geo-location code for geo-locating, and thus avoid requiring either CloudFlare or a second geo-location plugin.
* TWEAK: Avoided using a deprecated method in WC 2.3

= 1.7.4 - 2015-02-13 =

* FIX: The HMRC (UK) decided to move their rates feed to a new URL this month (again!), removing one of the under-scores from the URL (also see changelog for 1.6.7). This fix will also be OK next month in case this was a mistake and they revert, or even if they switch back to Dec 2014's location. Update in order to make sure you are using current rates.

= 1.7.2 - 2015-02-07 =

* COMPATIBILITY: Tested on WooCommerce 2.3 (RC1). Note that WooCommerce EU VAT Compliance will over-ride WooCommerce 2.3's built-in geo-location features - so, you should not need to adjust any settings after updating to WooCommerce 2.3. WooCommerce 2.0 is no longer officially supported or tested (though this release is still believed to be compatible).
* TWEAK: Add order number to the CSV download (allowing order number to differ from the WooCommerce order ID - e.g. if using http://www.woothemes.com/products/sequential-order-numbers-pro/).
* TWEAK: Introduce WC_EU_VAT_NOCOUNTRYPRESELECT constant, allowing you to disable the built-in country pre-selection (if, for example, you already have an existing solution)

= 1.7.1 - 2015-01-20 =

* FIX: No longer require the shop base country to be in the EU when applying VAT exemptions for B2B customers
* FEATURE: Add an option for a separate checkbox for the "show prices without taxes" option in the country-selection widget (in addition to the existing, but not-necessarily-easy-to-find, menu option on the country list)
* TRANSLATION: Updated French translation (thanks to Guy Pasteger)

= 1.7.0 - 2015-01-13 =

* USER NOTE: This plugin is already compatible with version 2.0 of the GeoIP detect plugin, but if/when you update to that, you will need to update the GeoIP database (as version 2.0 uses a new format) - go to Tools -> GeoIP Detection ... you will then need to reload the dashboard once more to get rid of the "No database" warning message.
* FEATURE: Optionally forbid checkout if any goods liable to EU VAT are in the cart (this can be a better option than using WooCommerce's built-in feature to forbid all sales at all to EU countries - perhaps not all your goods are VAT-liable. Note that this is a stronger option that the existing option to only forbid consumer sales (i.e. customers who have no access to VAT exemption via supply of a VAT number))
* FEATURE: Support mixed shops, selling goods subject to EU VAT under the 2015 digital goods regulations and other goods subject to traditional base-country-based VAT regulations. The plugin supports this via allowing you to identify which tax classes in your WooCommerce configuration are used for 2015 digital goods items. Products which you place in other tax classes are not included in calculations/reports made by this plugin for per-country tax liabilities, even if VAT was charged upon them. (For such goods, you calculate how much you owe your local tax-man by using WooCommerce's built-in tax reports).
* FEATURE: Within {iftax}{/iftax} tags, you can use the special value value {country_with_brackets} to show the country that tax was calculated using, surrounded by brackets, if one is relevant; or nothing will be shown if not. Example: {iftax}incl. VAT {country_with_brackets}{/iftax}. This is most useful for mixed shops, where you will not what the confuse the customer by showing the country for products for which the VAT is not based upon country.
* FIX: Country pre-selection drop-down via shortcode was not activating if the page URL had a # in it.
* FIX: Unbalanced div tag in Premium plugin on checkout page if self-certification was disabled.
* TWEAK: Negative VAT number lookups are now cached for 1 minute instead of 7 days (to mitigate the possible undesirable consequences of cacheing a false negative, and given that we expect very few negatives anyway)
* TWEAK: Change prefix used for transient names, to effectively prevent any previously cached negative lookups for certain valid Spanish VAT numbers (see 1.6.14) being retained, without requiring the shop owner to manually flush their transients.
* TRANSLATION: Updated French translation (thanks to Guy Pasteger)

= 1.6.14 - 2015-01-10 =

* FEATURE: Upon discovery of a valid Spanish VAT number which the existing API server did not return as valid, we now use the official VIES service directly, and fall back to a second option if that does not respond positively (thus adding some redundancy if one service is down).
* FEATURE: VAT number validity at the checkout is now checked as it is typed (i.e. before order is placed), and feedback given allowing the customer to respond (e.g. hint that you have chosen a different country to that which the VAT number is for).
* FEATURE: Support for the official exchange rates of the Central Bank of the Russian Federation (http://www.cbr.ru)
* TWEAK: Move the position of the "VAT Number" field at the checkout to the bottom of the billing column, and make it filterable
* TWEAK: If Belgian customer enters a 9-digit VAT number, then automatically prefix with a 0 (https://www.gov.uk/vat-eu-country-codes-vat-numbers-and-vat-in-other-languages)
* TRANSLATIONS: Updated POT file

= 1.6.13 - 2015-01-08 =

* FIX: The button to add tax rates was not appearing when WordPress was en Français.
* TWEAK: Add TVA/T.V.A. to the list of taxes recognised as VAT by default
* TWEAK: Readiness test in the free version will now alert if the WooCommerce Subscriptions extension is active (free version does not contain the extra code needed to support it)
* TWEAK: Add link in the control centre to the official EU PDF detailing current VAT rates

= 1.6.12 - 2015-01-06 =

* FEATURE: CSV downloads now take notice of the chosen dates in the date selector widget (reports) (i.e. so you can now also download selected data, instead of only downloading all data)
* FIX: Some more translated strings are now translated in the admin interface.
* FIX: Restore functionality on WooCommerce < 2.2 (checkout broken in 1.6.0)
* FIX: Don't tweak the "taxes estimated for" message on the cart page on WooCommerce < 2.2.9, since the country choice widget requires this version
* FIX: The button on the report date selector form, if accessed via the compliance centre (rather than WooCommerce reports) was not working

= 1.6.11 - 2015-01-06 =

* FIX: Restore ability to run on PHP 5.2
* FIX: If no current exchange rates were available at check-out time, and HTTP network download failed, then this case was handled incorrectly.
* FIX: Some settings strings were not being translated in the admin interface.
* FIX: "Taxes estimated for" message on the cart page now indicates the correct country
* TWEAK: Move widget + shortcode code to a different file
* TWEAK: CSV order download will now only list orders from 1st Jan 2015 onwards, to prevent large numbers of database queries for orders preceeding the VAT law on shops with large existing order lists.
* TWEAK: CSV order download will now intentionally show orders from non-EU countries (since these could be subject to audit for compliance also); a later release will make this optional. Before, these orders were shown, though not intentionally, and the data was incomplete.
* TRANSLATION: Updated French translation (thanks to Guy Pasteger)

= 1.6.9 - 2015-01-04 =

* FIX: Download of current VAT rates via HTTP was not working (bundled copy of rates in the plugin always ended up getting used)
* FEATURE: New readiness tests added for checking access to current VAT rates via network, checking that each country has an entry in a tax table, and checking that they agree with the apparent current rates.
* TWEAK: Don't load un-needed PHP classes if not in admin area (minor performance improvement)

= 1.6.8 - 2015-01-03 =

* FEATURE: VAT rate tables can now be pre-filled for any tax class (not just WooCommerce's built-in standard / reduced), and you can choose which rates to fetch them from
* FIX: Fix bug (since 1.6.0) in the free version that caused any widget-selected country's VAT rate to be applied at the check-out, despite other settings.
* FIX: Where no reduced rate exists (currently, Denmark), the standard rate is added instead
* UPDATE: Default VAT rates for Luxembourg updated to reflect new values (Jan 2015) - you will need to update your WooCommerce tax tables to pick up the new rates
* TWEAK: Round prices before comparing taxed and untaxed prices (otherwise two actually identical prices may apparently differ due to the nature of PHP floating point arithmetic - which could cause an "including tax" label to show when tax was actually zero)
* TWEAK: CSV spreadsheet download now supplies date in local format (as well as standard ISO-8601 format) (suggestion from Guy Pasteger)
* TWEAK: Date entry boxes in the control centre now have a date-picker widget (as they did if used from the WooCommerce reports page)
* TWEAK: Record + display information on which exchange rate provider was used to convert (useful for audit), and the recorded rate
* TWEAK: Added new readiness test: tests that all coupons are applied before tax (doing so after tax leads to non-compliant VAT invoices)
* TWEAK: Added new readiness test: check that tax is enabled for the store
* TRANSLATIONS: Updated POT file

= 1.6.7 - 2015-01-01 =

* TWEAK: Added a 'classes' parameter to the [euvat_country_selector] shortcode, allowing CSS classes to be added to the widget
* TWEAK: Correct filter name in base XML provider
* FIX: "VAT Number" heading would sometimes show at the check-out when it was not needed (Premium)
* FIX: The HMRC (UK) decided to move their rates feed to a different URL this month, swapping hyphens for under-scores. How stupid. This fix will also be OK next month in case this was a mistake and they revert.

= 1.6.6 - 2014-12-31 =

* FIX: Fix bug that could cause the 'Phrase matches used to identify VAT taxes' and 'Invoice footer text (B2C)' settings to be reset to default values.
* TWEAK: Add help text to the settings in the control centre, mentioning the {iftax} and {country} tags.
* TWEAK: Automatic entries in WooCommerce tables now show the VAT rate in the name - because compliant invoices in some states require to show the rate. It is recommended that you go and update your tables in WooCommerce -> Settings -> Tax -> (rate), if this applies to you (you may need to delete all existing rows).

= 1.6.5 - 2014-12-31 =

* TWEAK: Those with non-EU billing addresses (or shipping, if that's what you're using) are no longer exempted from other checks (specifically, self-certification in the case of an address/GeoIP conflict). This release is for the Premium version only (since the tweak does not affect the free version).

= 1.6.4 - 2014-12-31 =

* FEATURE: Support official exchange rates from the Danish National Bank (https://www.nationalbanken.dk/en/statistics/exchange_rates/Pages/Default.aspx)
* TRANSLATION: German translation is now updated, courtesy of Gunther Wegner.
* TRANSLATION: New French translation, courtesy of Guy Pasteger.

= 1.6.3 - 2014-12-30 =

* FEATURE: You can now enter special values in WooCommerce's 'Price display suffix' field: anything enclosed in between {iftax} and {/iftax} will only be added if the item has taxes; and within that tag, you can use the special value {country} to show the country that tax was calculated using. Example: {iftax}incl. VAT{/iftax} More complicated example: {iftax}incl. VAT ({country}){/iftax}
* FIX: Resolve issue that required self-certification even when none was required, if the user was adding an extra option to the self-certification field via a filter.

= 1.6.2 - 2014-12-30 =

* FIX: Remove debugging code that was inadvertantly left in 1.6.0
* FIX: Fix fatal PHP error in admin products display (since 1.6.0)

= 1.6.0 - 2014-12-30 =

* FEATURE: Detect visitor's country and display prices accordingly on all shop pages from their first access (requires WooCommerce 2.2.9 or later; as noted in the WooCommerce changelog - https://wordpress.org/plugins/woocommerce/changelog/ - that is the first version that allows the taxable country to be changed at this stage). This feature also pre-sets the billing country on the check-out page.
* FEATURE: Option to make entry of VAT number for VAT exemption either optional, mandatory, or not possible. (Previously, only 'optional' was available). This means that store owners can decide to always charge VAT, or to not take orders from EU customers who are not VAT exempt. (Non-EU customers can still make orders; if you do not wish that to be possible, then there are existing WooCommerce settings for that). (This option is only relevant to the premium version, as the free version has no facility for entering VAT numbers).
* FEATURE: Support for WooCommerce subscriptions (Premium)
* TWEAK: Self-certification option now asks for 'country of residence', rather than of current location; to comply with our updated understanding of what the user should be asked to do. (But note that the message was, and continues to be, over-ridable via the wc_eu_vat_certify_message filter).
* TWEAK: Make it possible (via a filter, wc_eu_vat_certify_form_field) to not pre-select any option for the self-certified VAT country. If your view is no option should be pre-selected, then you can use this filter. (We offer you no legal or taxation advice - you are responsible to consult your own local resources).
* TWEAK: First beginnings of the readiness report: will now examine your WC version and "tax based on" setting.
* TWEAK: EU VAT report now moved to the 'Taxes' tab of the WooCommerce reports (from 'Orders')
* TRANSLATION: German translation is now complete, courtesy of Gunther Wegner. POT file updated.

= 1.5.7 - 2014-12-29 =

* FEATURE: Add the option to add configurable footer text to invoices produced by the <a href="https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/">WooCommerce PDF invoices and packing slips plugin</a>, if VAT was paid; or a different message is a valid VAT number was added and VAT was removed.
* FEATURE: New German translation, courtesy of Gunther Wegner

= 1.5.6 - 2014-12-27 =

* FEATURE (Premium): Option to display converted amounts for VAT taxes on invoices produced by the <a href="https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/">WooCommerce PDF invoices and packing slips plugin</a>.
* TWEAK: Prevent many useless database queries for reports when handling orders made before the plugin was active
* TWEAK: Prevent a PHP notice on WC < 2.2 for the VAT number field at the checkout
* FIX: Prevent PHP notices + missing data for some currency combinations in the CSV spreadsheet download

= 1.5.5 - 2014-12-26 =

* FIX: Monaco and the Isle of Man were previously being erroneously omitted from reports, despite being part of the EU for VAT purposes
* FIX: The Isle of Man was being missed when rates were being automatically added/updated
* FEATURE: If the customer added a VAT number for VAT exemption (Premium), then it will be appended to the billing address, where relevant (e.g. email order summary, PDF invoices). Credit to Diego Zanella for the idea and modified code.
* FEATURE: Rate information is now saved at order time in more detail, and displayed by rate; this is important data, especially if you sell goods which are not all in the same VAT band (i.e. different VAT bands in the same country, e.g. standard rate and reduced rate)
* TWEAK: Move compliance information on the order screen into its own meta box
* TWEAK: Exchange rate information is now stored with the order in a more convenient format - we recommend you update (though, the old format is still supported; but, it's not 1st Jan yet, so actually we recommend you apply every update until then, as nobody has a good reason to be running legacy code before the law launches).

= 1.5.4 - 2014-12-26 =
 
* FIX: Back-end order page now shows the VAT paid as 0.00 instead of 'Unknown', if a valid VAT number was entered. The VAT number is also shown more prominently.
* FIX: Add missing file to 1.5.2 release (exchange rate providers were not working properly without it)
* TWEAK: Settings page will now require the user to confirm that they wish to leave, if they have unsaved changes

= 1.5.2 - 2014-12-24 =

* TWEAK: Re-worked the exchange rate cacheing layer to provide maximum chance of returning an exchange rate (out-of-date data is better than no data)

= 1.5.1 - 2014-12-24 =

* FEATURE: Added the European Central Bank's exchange rates as a source of exchange rates

= 1.5.0 - 2014-12-24 =

* FEATURE: Currency conversion: if your shop sells in a different currency than you are required to make VAT reports in, then you can now record currency conversion data with each order. Currently, the official rates of HM Revenue & Customs (UK) are used; more providers will be added.

= 1.4.2 -2014-12-23 =

* FEATURE: Control centre now contains relevant WooCommerce settings, and links to tax tables, for quick access

= 1.4.1 - 2014-12-22 =

* FEATURE: Dashboard reports are now available on WooCommerce 2.2, with full functionality (so, now available on WC 2.0 to 2.2)
* FEATURE: All versions of the plugin can now select date ranges for reports
* FEATURE: Download all VAT compliance data in CSV format (Premium version)
* TWEAK: Report tables are now sortable via clicking the column headers

= 1.4.0 - 2014-12-19 =

* FEATURE: Beginnings of a control centre, where all functions are brought together in a single location, for ease of access (in the dashboard menu, WooCommerce -> EU Vat Compliance)
* TRANSLATIONS: A POT file is available for translators to use - http://plugins.svn.wordpress.org/woocommerce-eu-vat-compliance/trunk/languages/wc_eu_vat_compliance.pot

= 1.3.1 - 2014-12-18 =

* FEATURE: Reports have now been added to the free version. So far, this is still WC 2.0 and 2.1 only - 2.2 is not yet finished.
* FIX: Reporting in 1.3.0 was omitting orders with order statuses failed/cancelled/processing, even if the user included them

= 1.3.0 - 2014-12-18 =

* FEATURE: Premium version now shows per-country VAT reports on WooCommerce 2.0 and 2.1 (2.2 to follow). Which reporting features will or won't go into the free version is still to-be decided.
* FIX: The value of the "Phrase matches used to identify VAT taxes" setting was reverting to default - please update it again if you had attempted to change it (after updating to this version)
* IMPORTANT TWEAK: Order VAT is now computed and stored at order time, to spare the computational expense of calculating it, order-by-order, when reporting. You should apply this update asap: orders made before you upgrade to it will not be tracked in your report. (Note also that reporting features are still under development, in case you're wondering where they are - they're not technically needed until the 1st quarter of 2015 ends, and only need to cover from 1st Jan 2015 onwards). 

= 1.2.0 - 2014-12-12 =

* COMPATIBILITY: Tested on WordPress 4.1
* TWEAK: Code re-factored
* TWEAK: Re-worked the readme.txt file to reflect current status
* FEATURE: Premium version has been launched: https://www.simbahosting.co.uk/s3/product/woocommerce-eu-vat-compliance/
* FEATURE (Premium version): Ability to allow the customer to enter their VAT number, if they have one, and (if it validates) be exempted from VAT transactions. Compatible with WooCommerce's official extension (i.e. you can remove that extension, and your old data will be retained).
* FEATURE (Premium version): Dealing with conflicts: if the customer's IP address + billing (or shipping, according to your WooCommerce settings) conflict, then optionally the customer can self-certify their country (or, you can force them to do this always, if you prefer).
* FIX: The initial value of the "Phrase matches used to identify VAT taxes" setting could be empty (check in your WooCommerce -> Settings -> Tax options, if you are updating from a previous plugin version; the default value should be: VAT, V.A.T, IVA, I.V.A., Value Added Tax)

= 1.1.2 - 2014-12-10 =

* FIX: Fix bug which prevented France (FR) being entered into the rates table. If you had a previous version installed, then you will need to either wait 24 hours before pressing the button to update rates since you last did so, or to clear your transients, or enter French VAT (20% / 10%) manually into the tax table.
* TWEAK: Reduce time which current rates are cached for to 12 hours

= 1.1.1 - 2014-12-09 =

* FIX: Fix bug with display of info in admin area in WooCommerce 2.2

= 1.1 - 2014-12-06 =

* GeoIP information, and what information WooCommerce used in setting taxes, is now recorded at order time
* Recorded VAT-relevant information is now displayed in the admin area

= 1.0 - 2014-11-28 =

* First release: contains the ability to enter and update current EU VAT rates

== Screenshots ==

1. A button is added to allow you to enter all EU VAT rates with one click. <em>Note: Screenshots are included below from <a href="https://www.simbahosting.co.uk/s3/product/woocommerce-eu-vat-compliance/">the Premium version</a>. Please check the feature list for this plugin to clarify which features are available in which version.</em>

2. VAT information being shown in the order details page

3. Per-country VAT reports

4. Download all compliance information in a spreadsheet.

5. Compliance dashboard, bringing all settings and information into one place

6. Currency conversions, if you sell and report VAT in different currencies.

7. Compliance report, checking a number of common essentials for configuring your store correctly for EU VAT.

== License ==

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

== Upgrade Notice ==
* 1.14.6 - Navigation when choosing new ranges within WooCommerce -> Reports -> Taxes -> EU VAT Report had regressed on WooCommerce 4.0 (whilst still working within WooCommerce -> EU VAT Compliance -> Taxes)
