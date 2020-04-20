<?php
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use Aelia\WC\EU_VAT_Assistant\WC_Aelia_EU_VAT_Assistant;
use Aelia\WC\EU_VAT_Assistant\Settings;

$text_domain = WC_Aelia_EU_VAT_Assistant::$text_domain;
$settings = WC_Aelia_EU_VAT_Assistant::settings();
$current_user = wp_get_current_user();
?>
<div id="vat_number_field" class="aelia_eu_vat_assistant checkout_field">
	<h4 class="title"><?php
		echo __($settings->get(Settings::FIELD_EU_VAT_FIELD_TITLE), $text_domain);
	?></h4>
	<div class="description"><?php
		echo __($settings->get(Settings::FIELD_EU_VAT_FIELD_DESCRIPTION), $text_domain);
	?></div>
	<div><?php
		woocommerce_form_field('vat_number', array(
			'type' => 'text',
			'class' => array('aelia_wc_eu_vat_assistant vat_number update_totals_on_change address-field form-row-wide'),
			//'label' => __('EU VAT Number', $text_domain),
			'default' => is_object($current_user) ? $current_user->vat_number : '',
			'placeholder' => __('VAT Number', $text_domain),
			'custom_attributes' => array(
				'valid' => 0,
			),
		));
	?></div>
</div>
