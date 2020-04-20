<?php
// This view is used to render the UI to manage prices by country for variations
use Aelia\WC\EU_VAT_Assistant\Definitions;

$text_domain = Definitions::TEXT_DOMAIN;
?>
<!-- Variation - EU VAT Country Settings by Country -->
<?php
	// The wrapper for WooCommerce 2.2 and earlier has to be a TR. In WooCommerce
	// 2.3, the table has been replaced with a set of DIV elements
	if(version_compare(wc()->version, '2.3', '<')):
?>
<tr class="euva product_settings variation"><!-- WC 2.2 Wrapper - START -->
	<td colspan="2">
<?php
	// In WooCommerce 2.3, the table has been replaced with a set of DIV elements
	else:
?>
<div class="euva product_settings variation clearfix"><!-- WC 2.3+ Wrapper - START -->
<?php endif; ?>

		<div id="euva_data_<?php echo $variation_id; ?>" class="woocommerce_options_panel">
			<h2 class="title"><?php
				echo __('EU VAT Assistant', $text_domain);
			?></h2>
			<div class="variation-prices-wrapper">
				<div class="wc-metaboxes"><?php
					include('product_euva_section.php');
				?></div>
			</div>
		</div>

<?php if(version_compare(wc()->version, '2.2', '<=')): ?>
	</td>
</tr><!-- WC 2.2 Wrapper - END -->
<?php else: ?>
</div><!-- WC 2.3+ Wrapper - END -->
<?php endif; ?>
