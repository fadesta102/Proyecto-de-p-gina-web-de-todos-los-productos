<?php
/**
 * Header for the Sales Summary Report.
 *
 * @since 1.5.8.160112
 */
namespace Aelia\WC\EU_VAT_Assistant;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

$text_domain = Definitions::TEXT_DOMAIN;
$exchange_rates_type = get_value(Definitions::ARG_EXCHANGE_RATES_TYPE, $_REQUEST, Definitions::FX_SAVED_WITH_ORDER);
$refunds_period = get_value(Definitions::ARG_REFUNDS_PERIOD, $_REQUEST, Definitions::REFUNDS_FOR_ORDERS_IN_PERIOD);
$include_refunded_orders = get_value(Definitions::ARG_INCLUDE_REFUNDED_ORDERS, $_REQUEST, Definitions::NO);
?>
<div id="eu_vat_by_country_report_header" class="wc_aelia_eu_vat_assistant report header">
	<div class="options">
		<h3><?php echo __('Options', $text_domain); ?></h3>
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
		<?php
		// Refunds are available in WooCommerce 2.2 and later. We can hide the
		// related section in earlier versions
		if(version_compare(wc()->version, '2.2', '>=')):
		?>
		<!-- Select how to handle refunds -->
		<div class="refunds clearfix">
			<h4><?php
				echo __('Choose how you would like to process refunds', $text_domain); ?>
				<span class="tips"
							more_info_target="refunds_more_info"
							data-tip="<?php echo __('Here you can choose how you would like to process the ' .
																			'refunds that could be included in the report.',
																			$text_domain); ?>"><?php
					echo __('[What is this?]', $text_domain);
				?></span>
			</h4>
			<!-- Refunds - More info - Start -->
			<div id="refunds_more_info" class="more_info Hidden">
				<div class="info">
						<h4><?php
							echo __('Which refunds should I include in the report?', $text_domain);
						?></h4>
						<?php
						echo __('While this report was designed for VAT MOSS purposes, it can also be ' .
										'used for domestic VAT returns. You can find the data for your domestic ' .
										'VAT return in the "Domestic" section of the report (if applicable).', $text_domain);
						?>
						<br /><br />
						<?php
						echo __('EU VAT regulations indicate that refunds should be applied to the return ' .
										'related to the quarter in which the original order was placed. For ' .
										'example, if an order for 100 EUR is placed in March 2015, and a refund is ' .
										'granted in April 2015, the rule is that the original return for Q1 2015 ' .
										'should be amended.',
										$text_domain);
						echo '&nbsp';
						echo __('Domestic VAT returns, instead, are regulated by the country where the merchant ' .
										'resides. In many cases, domestic rules indicate that refunds should be ' .
										'applied to the VAT return for the period in which they occurred (i.e. the ' .
										'original return does not have to be modified.', $text_domain);
						?>
						<br /><br />
						<?php
						echo __('By selecting the appropriate setting, below, you can specify if the ' .
										'report should include the refunds <strong>granted in the ' .
										'selected period</strong>, or if it should include the refunds ' .
										'<strong>related to the orders that were placed in the selected ' .
										'period</strong>.', $text_domain);
					?>
					<span class="close"><?php
						echo __('[Close]', $text_domain);
					?></span>
				</div>
			</div>
			<!-- Refunds - More info - End -->
			<!-- Refunds period - Start -->
			<ul>
				<li>
					<input id="refunds_for_orders_in_period"
								 name="refunds_period"
								 type="radio"
								 value="<?php echo Definitions::REFUNDS_FOR_ORDERS_IN_PERIOD; ?>"
								 target_field="refunds_period"
								 <?php if($refunds_period === Definitions::REFUNDS_FOR_ORDERS_IN_PERIOD) { echo 'checked="checked"'; } ?> />
					<label for="refunds_for_orders_in_period"><?php
						echo __('Include refunds applied to the orders placed in the selected period', $text_domain);
					?></label>
				</li>
				<li>
					<input id="refunds_granted_in_period"
								 name="refunds_period"
								 type="radio"
								 value="<?php echo Definitions::REFUNDS_IN_PERIOD; ?>"
								 target_field="refunds_period"
								 <?php if($refunds_period === Definitions::REFUNDS_IN_PERIOD) { echo 'checked="checked"'; } ?> />
					<label for="refunds_granted_in_period"><?php
						echo __('Include refunds granted in the selected period', $text_domain);
					?></label>
				</li>
			</ul>
			<!-- Refunds period - End -->
		</div>
		<!-- Select how to handle refunds - END-->
		<!-- Include refunded order -->
		<div class="refunded_orders clearfix">
			<h4><?php
				echo __('Include refunded orders?', $text_domain); ?>
				<span class="tips"
							more_info_target="refunded_orders_more_info"
							data-tip="<?php echo __('Here you can choose if you would like to include ' .
																			'refunded orders in the data used for the report.',
																			$text_domain); ?>"><?php
					echo __('[What is this?]', $text_domain);
				?></span>
			</h4>
			<!-- Refunds - More info - Start -->
			<div id="refunded_orders_more_info" class="more_info Hidden">
				<div class="info">
					<h4><?php
						echo __('Should I include refunded orders?', $text_domain);
					?></h4>
					<?php
						echo __('By default, the report does not include refunded orders. Such orders ' .
										'are considered fully refunded, and all the revenue and the refunds ' .
										'related to them are ignored, as they should void each other.', $text_domain);
						echo '&nbsp;';
						echo __('If you choose to include refunded orders, then their amounts will be ' .
										'added to the sales total, and any refund associated to them will be ' .
										'deducted from it. ',
										$text_domain);
					?>
					<h5><?php echo __('Important', $text_domain); ?></h5>
					<?php
						echo __('If you decide to include refunded orders, make sure that you have ' .
										'created the appropriate refunds associated to them, so that the amounts ' .
										'that you have returned to your customers can be deducted. If you are not ' .
										'sure of what this means, we recommend to <strong>exclude</strong> refunded ' .
										'orders from the report.',
										$text_domain);
						echo __('For more information on refunds and on how to create them, please ' .
										'<a href="http://docs.woothemes.com/document/woocommerce-refunds/"> ' .
										'refer to WooCommerce Refunds documentation</a>.',
										$text_domain);
					?>
					<span class="close"><?php
						echo __('[Close]', $text_domain);
					?></span>
				</div>
			</div>
			<!-- Refunds - More info - End -->
			<!-- Include refunded orders - Start -->
			<ul>
				<li>
					<input id="include_refunded_orders_no"
								 name="include_refunded_orders"
								 type="radio"
								 value="<?php echo Definitions::NO; ?>"
								 target_field="include_refunded_orders"
								 <?php if($include_refunded_orders === Definitions::NO) { echo 'checked="checked"'; } ?> />
					<label for="include_refunded_orders_no"><?php
						echo __('Exclude refunded orders', $text_domain);
					?></label>
				</li>
				<li>
					<input id="include_refunded_orders_yes"
								 name="include_refunded_orders"
								 type="radio"
								 value="<?php echo Definitions::YES; ?>"
								 target_field="include_refunded_orders"
								 <?php if($include_refunded_orders === Definitions::YES) { echo 'checked="checked"'; } ?> />
					<label for="include_refunded_orders_yes"><?php
						echo __('Include refunded orders', $text_domain);
					?></label>
				</li>
			</ul>
			<!-- Include refunded orders - End -->
		</div>
		<!-- Include refunded order - END -->
		<?php endif; ?>
	</div>
</div>
