/* JavaScript for plugin's admin pages */
jQuery(document).ready(function($) {
	var main = this;
	this.params = aelia_eu_vat_assistant_admin_params;
	this.ui_params = this.params.user_interface;

	/**
	 * Adds triggers that allow to quickly populate country selector fields.
	 */
	this.add_country_selection_triggers = function () {
		// Add the triggers
		var $triggers_wrapper = $('<div class="triggers">');
		//__('Add European Union countries', $this->_textdomain);
		var $eu_trigger_element = $('<span class="trigger add_eu_countries">' +
																main.ui_params.add_eu_countries_trigger +
																'</span>');
		$triggers_wrapper.append($eu_trigger_element);
		$('.multi_country_selector').after($triggers_wrapper);

		// Hook the trigger events
		// Automatically populate a field with all European countries
		$triggers_wrapper.on('click', '.add_eu_countries', function() {
			var $trigger = $(this);
			var $target = $trigger.parents('td').first().find('select').first();

			$target.val(main.params.eu_vat_countries);
			$target.trigger('chosen:updated');
		});
	}

	var $settings_form = $('#wc_aelia_eu_vat_assistant_form');
	// If form is not found, we are not on this plugin's setting page
	if(!$settings_form.length) {
		return;
	}

	// Display tabbed interface
	$settings_form.find('.tabs').tabs();

	// Add triggers to quickly populate the country selector fields
	this.add_country_selection_triggers();

	// Use Chosen plugin to replace standard multiselect
	if(jQuery().chosen) {
		// Multiselect for enabled currencies
		$settings_form.find('select').chosen();
	}

	// Check/uncheck all "set manually" boxes when the one at the top of the column
	// is clicked
	$settings_form.find('#exchange_rates_settings #set_manually_all').on('click', function() {
		var $checkboxes = $settings_form.find('#exchange_rates_settings tbody .set_manually input');
		$checkboxes.prop('checked', $(this).prop('checked'));
	});

	// Add event handler on "Set All to Manual" checkbox
	var $exchange_rates_settings_table = $('#exchange_rates_settings');
	$exchange_rates_settings_table.on('click', '.set_manually .select_all', function() {
		$exchange_rates_settings_table
			.find('.exchange_rate_set_manually')
			.prop('checked', true);
	});
	$exchange_rates_settings_table.on('click', '.set_manually .deselect_all', function() {
		$exchange_rates_settings_table
			.find('.exchange_rate_set_manually')
			.prop('checked', false);
	});
});
