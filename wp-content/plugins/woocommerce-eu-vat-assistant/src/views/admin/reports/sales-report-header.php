<?php
namespace Aelia\WC\EU_VAT_Assistant;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

$text_domain = Definitions::TEXT_DOMAIN;
$tax_type = get_value(Definitions::ARG_TAX_TYPE, $_REQUEST, Definitions::TAX_MOSS_ONLY);
$exchange_rates_type = get_value(Definitions::ARG_EXCHANGE_RATES_TYPE, $_REQUEST, Definitions::FX_SAVED_WITH_ORDER);

// TODO Remove "Hidden" class from the main wrapper
?>
<div id="sales_report_header" class="wc_aelia_eu_vat_assistant report header Hidden">
	<div class="options">
		<h3><?php echo __('Options', $text_domain); ?></h3>
		<?php
		/**** Use the section below as a template for the options to show in the header
		<!-- Select exchange rates -->
		<div class="exchange_rates clearfix">
			<h4><?php
				echo __('Choose which exchange rates should be used', $text_domain); ?>
				<span class="tips"
							more_info_target="exchange_rates_more_info"
							data-tip="<?php echo __('Choose if you would like generate the report using the ' .
																			'exchange rate saved with each order, or the official ' .
																			'European Central Bank (ECB) rates that apply for the ' .
																			'quarter. Click for more information.',
																			$text_domain); ?>"><?php
					echo __('[What is this?]', $text_domain);
				?></span>
			</h4>
			<!-- Exchange Rates - More info - Start -->
			<div id="exchange_rates_more_info" class="more_info Hidden">
				<div class="info"><?php
						echo __('The EU VAT Assistant saves the exchange rate to convert order amounts from ' .
										'the currency in which orders were placed to the currency to be used for VAT ' .
										'reports. Such exchange rate is saved together with the order as soon as it is ' .
										'placed, and can be used to prepare the EU VAT report.', $text_domain);
						echo __('EU VAT regulations, however, have specific requirements about the ' .
									'exchange rates to be used. As per <a href="http://www.revenue.ie/en/tax/vat/leaflets/mini-one-stop-shop.html"' .
									'title="Irish Revenue - The Mini One Stop Shop (MOSS)" target="_blank">official documentation</a>:', $text_domain);
					?>
					<blockquote cite="http://www.revenue.ie/en/tax/vat/leaflets/mini-one-stop-shop.html">
						<strong><?php
							echo __('The Mini One Stop Shop (MOSS) - What currency should I use for the MOSS ' .
											'VAT return?', $text_domain);
						?></strong>
						<br/>
						<?php
							echo __('The exchange rate that must be used is the European Central Bank (ECB) rate ' .
											'applicable on the last day of the calendar quarter to which the return ' .
											'relates.', $text_domain);
					?></blockquote>
					<h4><?php
						echo __('Which exchange rates should I use?', $text_domain);
					?></h4>
					<?php
						echo __('If you are preparing the report for your own use (for example, to get a rough ' .
										'idea of how much VAT you have collected), or if the quarter has not ended yet, ' .
										'you can use the exchange rates saved with the orders.', $text_domain);
					?>
					&nbsp;
					<strong><?php
						echo __('If you are preparing the report to file the official VAT MOSS return, we ' .
										'recommend that you use the ECB exchange rates for the last day of the selected quarter.',
										$text_domain);
					?></strong>
					<span class="close"><?php
						echo __('[Close]', $text_domain);
					?></span>
				</div>
			</div>
			<!-- Exchange Rates - More info - End -->
			<ul>
				<li>
					<input id="saved_with_order"
								 name="exchange_rates_type"
								 type="radio"
								 value="<?php echo Definitions::FX_SAVED_WITH_ORDER; ?>"
								 target_field="exchange_rates_type"
								 <?php if($exchange_rates_type === Definitions::FX_SAVED_WITH_ORDER) { echo 'checked="checked"'; } ?> />
					<label for="saved_with_order"><?php echo __('Use the exchange rates saved with each order', $text_domain); ?></label>
				</li>
				<li>
					<input id="ecb_rates_for_quarter"
								 name="exchange_rates_type"
								 type="radio"
								 value="<?php echo Definitions::FX_ECB_FOR_QUARTER; ?>"
								 target_field="exchange_rates_type"
								 <?php if($exchange_rates_type === Definitions::FX_ECB_FOR_QUARTER) { echo 'checked="checked"'; } ?> />
					<label for="ecb_rates_for_quarter"><?php echo __('Use the European Central Bank (ECB) rates for the last day of the selected quarter', $text_domain); ?></label>
				</li>
			</ul>
		</div>
		****/
		?>
	</div>
</div>
