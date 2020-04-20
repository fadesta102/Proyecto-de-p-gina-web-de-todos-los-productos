<?php
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use Aelia\WC\EU_VAT_Assistant\WC_Aelia_EU_VAT_Assistant;
use Aelia\WC\EU_VAT_Assistant\Settings;

error_reporting(E_ALL);

$text_domain = WC_Aelia_EU_VAT_Assistant::$text_domain;
$settings = WC_Aelia_EU_VAT_Assistant::settings();
?>
<div id="woocommerce_location_self_certification" class="aelia_eu_vat_assistant checkout_field">
	<div class="description"><?php
		echo __('Due to European regulations, we have to ask you to confirm your ' .
						'location.', $text_domain);
	?></div>
	<div><?php
		woocommerce_form_field('customer_location_self_certified', array(
			'type' => 'checkbox',
			'class' => array('aelia_wc_eu_vat_assistant location_self_certification update_totals_on_change address-field form-row-wide'),
			'label' => '<span class="self_certification_label">' . __($settings->get(Settings::FIELD_SELF_CERTIFICATION_FIELD_TITLE), $text_domain) . '</span>',
		));
	?></div>
</div>
