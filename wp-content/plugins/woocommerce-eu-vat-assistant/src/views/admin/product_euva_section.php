<?php
use Aelia\WC\EU_VAT_Assistant\Definitions;

$text_domain = Definitions::TEXT_DOMAIN;
?>
<div class="woocommerce_euva wc-metabox">
	<h3 class="section_header">
		<div class="handlediv" title="<?php echo __('Click to toggle', $text_domain); ?>"></div>
		<div class="title">
			<?php echo __('VIES Settings', $text_domain); ?>
		</div>
	</h3>

	<div class="wc-metabox-content">
		<!-- Product EU VAT Settings -->
		<div class="vies-settings"><?php
			woocommerce_wp_hidden_input(array(
				'id' => Definitions::FIELD_VIES_PRODUCT_IS_SERVICE . "[$product_id]",
				'value' => 'no',
				'class' => 'hidden',
			));
			woocommerce_wp_checkbox(array(
				'id' => Definitions::FIELD_VIES_PRODUCT_IS_SERVICE . "[$product_id]",
				'value' => $vies_product_is_service,
				'class' => 'vies_product_is_service',
				'label' => __('This product is a service', $text_domain),
				'description' => __('Tick this box if you want this product to be considered a ' .
														'service when generating the VIES report.', $text_domain),
			));
		?></div>
	</div>
</div>
