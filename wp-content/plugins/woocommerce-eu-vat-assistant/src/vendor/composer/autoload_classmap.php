<?php

// autoload_classmap.php @generated by Composer

$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
    'Aelia\\WC\\EU_VAT_Assistant\\Definitions' => $baseDir . '/lib/classes/definitions/definitions.php',
    'Aelia\\WC\\EU_VAT_Assistant\\EU_VAT_Validation' => $baseDir . '/lib/classes/tools/eu_vat_validation.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Exchange_Rates_BitPay_Model' => $baseDir . '/lib/classes/exchange_rates/aelia-wc-exchangerates-bitpay.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Exchange_Rates_DNB_Model' => $baseDir . '/lib/classes/exchange_rates/aelia-wc-exchangerates-dnb.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Exchange_Rates_ECB_Historical_Model' => $baseDir . '/lib/classes/exchange_rates/aelia-wc-exchangerates-ecb-historical.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Exchange_Rates_ECB_Model' => $baseDir . '/lib/classes/exchange_rates/aelia-wc-exchangerates-ecb.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Exchange_Rates_HMRC_Model' => $baseDir . '/lib/classes/exchange_rates/aelia-wc-exchangerates-hmrc.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Exchange_Rates_IrishRevenueHTML_Model' => $baseDir . '/lib/classes/exchange_rates/aelia-wc-exchangerates-irish-revenue-html.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Frontend_Integration' => $baseDir . '/lib/classes/integration/frontend/frontend_integration.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Logger' => $baseDir . '/lib/classes/logger/logger.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Order' => $baseDir . '/lib/classes/order/order.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Orders_Integration' => $baseDir . '/lib/classes/integration/admin/orders_integration.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Products_Integration' => $baseDir . '/lib/classes/integration/admin/products_integration.php',
    'Aelia\\WC\\EU_VAT_Assistant\\ReportsManager' => $baseDir . '/lib/classes/reporting/reports_manager.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Reports\\Base_EU_VAT_By_Country_Report' => $baseDir . '/lib/classes/reporting/reports/base/base_eu_vat_by_country_report.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Reports\\Base_INTRASTAT_Report' => $baseDir . '/lib/classes/reporting/reports/base/base_intrastat_report.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Reports\\Base_Report' => $baseDir . '/lib/classes/reporting/reports/base/base_report.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Reports\\Base_Sales_Report' => $baseDir . '/lib/classes/reporting/reports/base/base_sales_report.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Reports\\Base_Sales_Summary_Report' => $baseDir . '/lib/classes/reporting/reports/base/base_sales_summary_report.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Reports\\Base_VIES_Report' => $baseDir . '/lib/classes/reporting/reports/base/base_vies_report.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Reports\\WC21\\EU_VAT_By_Country_Report' => $baseDir . '/lib/classes/reporting/reports/WC21/eu_vat_by_country_report.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Reports\\WC21\\INTRASTAT_Report' => $baseDir . '/lib/classes/reporting/reports/WC21/intrastat_report.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Reports\\WC21\\VIES_Report' => $baseDir . '/lib/classes/reporting/reports/WC21/vies_report.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Reports\\WC22\\EU_VAT_By_Country_Report' => $baseDir . '/lib/classes/reporting/reports/WC22/eu_vat_by_country_report.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Reports\\WC22\\INTRASTAT_Report' => $baseDir . '/lib/classes/reporting/reports/WC22/intrastat_report.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Reports\\WC22\\Sales_List_Report' => $baseDir . '/lib/classes/reporting/reports/WC22/sales_list_report.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Reports\\WC22\\Sales_Summary_Report' => $baseDir . '/lib/classes/reporting/reports/WC22/sales_summary_report.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Reports\\WC22\\VIES_Report' => $baseDir . '/lib/classes/reporting/reports/WC22/vies_report.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Reports\\WC30\\EU_VAT_By_Country_Report' => $baseDir . '/lib/classes/reporting/reports/WC30/eu_vat_by_country_report.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Reports\\WC30\\INTRASTAT_Report' => $baseDir . '/lib/classes/reporting/reports/WC30/intrastat_report.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Reports\\WC30\\Sales_List_Report' => $baseDir . '/lib/classes/reporting/reports/WC30/sales_list_report.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Reports\\WC30\\Sales_Summary_Report' => $baseDir . '/lib/classes/reporting/reports/WC30/sales_summary_report.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Reports\\WC30\\VIES_Report' => $baseDir . '/lib/classes/reporting/reports/WC30/vies_report.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Settings' => $baseDir . '/lib/classes/settings/settings.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Settings_Renderer' => $baseDir . '/lib/classes/settings/settings-renderer.php',
    'Aelia\\WC\\EU_VAT_Assistant\\Tax_Settings_Integration' => $baseDir . '/lib/classes/integration/admin/tax_settings_integration.php',
    'Aelia\\WC\\EU_VAT_Assistant\\WCPDF\\EU_Invoice_Helper' => $baseDir . '/lib/classes/integration/pdf_invoices_packing_slips/eu_invoice_helper.php',
    'Aelia\\WC\\EU_VAT_Assistant\\WCPDF\\EU_Invoice_Order' => $baseDir . '/lib/classes/integration/pdf_invoices_packing_slips/eu_invoice_order.php',
    'Aelia\\WC\\EU_VAT_Assistant\\WC_Aelia_EU_VAT_Assistant_Install' => $baseDir . '/lib/classes/install/aelia-wc-eu-vat-assistant-install.php',
    'Aelia_WC_EU_VAT_Assistant_RequirementsChecks' => $baseDir . '/lib/classes/install/aelia-wc-eu-vat-assistant-requirementscheck.php',
    'Aelia_WC_RequirementsChecks' => $baseDir . '/lib/classes/install/aelia-wc-requirementscheck.php',
    'XMLSchema' => $vendorDir . '/econea/nusoap/src/nusoap.php',
    'nusoap_base' => $vendorDir . '/econea/nusoap/src/nusoap.php',
    'nusoap_client' => $vendorDir . '/econea/nusoap/src/nusoap.php',
    'nusoap_fault' => $vendorDir . '/econea/nusoap/src/nusoap.php',
    'nusoap_parser' => $vendorDir . '/econea/nusoap/src/nusoap.php',
    'nusoap_server' => $vendorDir . '/econea/nusoap/src/nusoap.php',
    'nusoap_wsdlcache' => $vendorDir . '/econea/nusoap/src/nusoap.php',
    'nusoap_xmlschema' => $vendorDir . '/econea/nusoap/src/nusoap.php',
    'soap_fault' => $vendorDir . '/econea/nusoap/src/nusoap.php',
    'soap_parser' => $vendorDir . '/econea/nusoap/src/nusoap.php',
    'soap_server' => $vendorDir . '/econea/nusoap/src/nusoap.php',
    'soap_transport_http' => $vendorDir . '/econea/nusoap/src/nusoap.php',
    'soapclient' => $vendorDir . '/econea/nusoap/src/nusoap.php',
    'soapval' => $vendorDir . '/econea/nusoap/src/nusoap.php',
    'wsdl' => $vendorDir . '/econea/nusoap/src/nusoap.php',
    'wsdlcache' => $vendorDir . '/econea/nusoap/src/nusoap.php',
);
