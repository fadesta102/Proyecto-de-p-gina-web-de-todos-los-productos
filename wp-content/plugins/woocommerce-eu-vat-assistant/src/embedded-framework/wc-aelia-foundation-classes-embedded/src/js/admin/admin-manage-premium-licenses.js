/* JavaScript for Edit Order */
jQuery(document).ready(function($) {
	/**
	 * Handles the license management on the Order Edit Admin page.
	 *
	 * @param object params The parameterd used to initialise the class.
	 * @param Aelia_AFC_Ajax_Handler ajax_handler The handler that will take care
	 * of processing Ajax calls.
	 * @see Aelia_AFC_Ajax_Handler
	 */
	function Premium_Licenses_Manager(params, ajax_handler) {
		// @var object A set of parameters
		this.params = params;
		// @var Aelia_SL_Ajax_Handler The instance of the class that will handle Ajax calls
		this.ajax_handler = ajax_handler;

		// @var array An array of messages used in the UI
		//this.messages = params['order_settings']['user_interface']['messages'];

		/**
		 * Sets the error message returned when checking or activating/deactivating
		 * a license.
		 *
		 * @param string plugin_slug
		 * @param string message
		 */
		this.set_license_error_message = function(plugin_slug, message) {
			var $license_section = $('#' + plugin_slug + '-license-section');

			message = message.trim();
			var $error_message_elem = $license_section.find('.license_error_message');
			$error_message_elem.find('.error_message').html(message);

			$error_message_elem.toggle(message != '');
		}

		/**
		 * Processes the response returned by an Ajax call.
		 *
		 * @param object response The Ajax response.
		 */
		this.process_ajax_response = function(response, request_args) {
			console.log("Ajax Request Args", request_args);
			console.log("Ajax Response", response);

			var $license_section = $('#' + request_args['plugin_slug'] + '-license-section');

			response['response'] = response['response'] || {};
			response['response']['license'] = response['response']['license'] || {};
			if($(response['response']['license']).length > 0) {
				var site_status = response['response']['license']['site_status'] || 'inactive';
				$license_section.find('.license_status').text(site_status);

				var date_expiration = response['response']['license']['date_expiration'] || '';
				$license_section.find('.date_expiration').text(date_expiration.split(' ')[0]);

				$license_section.removeClass('active inactive').addClass(site_status);
			}
			// Show the error message, if any
			if(response['response']['message']) {
				premium_licenses_manager.set_license_error_message(request_args['plugin_slug'], response['response']['message']);
			}

			premium_licenses_manager.update_ui();
		}

		/**
		 * Activates a site associated to a license.
		 *
		 * @param object action_element An action element.
		 * @param string license_key The licence key to activate. If empty, the
		 * key will be fetched from the "license_key" found for the plugin slug
		 * assigned to the action element.
		 */
		this.activate_site = function($action_element, license_key) {
			var license_section_id = '#' + $action_element.data('plugin_slug') + '-license-section';
			var plugin_slug = $action_element.data('plugin_slug');

			// Check if the license was passed as a parameter
			// @since 1.9.18.180319
			license_key = license_key || '';
			license_key = (license_key != '') ? license_key : $(license_section_id).find('.license_key').val();

			if(license_key == '') {
				// TODO Take message from the parameters, to allow translation
				window.alert('License activation halted. Please enter the license key and try again.');
				return;
			}
			console.log("Activating license. Plugin slug: " + plugin_slug + ". License key: " + license_key);

			var ajax_args = {
				'exec': 'aelia-premium-plugin-updater-activate_site',
				'plugin_slug': plugin_slug,
				'license_key': license_key,
			};

			// Reset and hide the error message element
			this.set_license_error_message(plugin_slug, '');
			// Pass a callback that will display the new licenses added to the order
			var ajax_response = this.ajax_handler.ajax_call(ajax_args,
																											this.process_ajax_response,
																											[license_section_id]);
		}

		/**
		 * Deactivates a site associated to a license.
		 *
		 * @param object action_element An action element.
		 * @param string license_key The licence key to deactivate. If empty, the
		 * key will be fetched from the "license_key" found for the plugin slug
		 * assigned to the action element.
		 */
		this.deactivate_site = function($action_element, license_key) {
			var license_section_id = '#' + $action_element.data('plugin_slug') + '-license-section';
			var plugin_slug = $action_element.data('plugin_slug');

			// Check if the license was passed as a parameter
			// @since 1.9.18.180319
			license_key = license_key || '';
			license_key = (license_key != '') ? license_key : $(license_section_id).find('.license_key').val();

			if(license_key == '') {
				// TODO Take message from the parameters, to allow translation
				window.alert('License deactivation halted. Please enter the license key and try again.');
				return;
			}
			console.log("Deactivating license. Plugin slug: " + plugin_slug + ". License key: " + license_key);

			var ajax_args = {
				'exec': 'aelia-premium-plugin-updater-deactivate_site',
				'plugin_slug': plugin_slug,
				'license_key': license_key,
			};

			// Reset and hide the error message element
			this.set_license_error_message(plugin_slug, '');
			// Pass a callback that will display the new licenses added to the order
			var ajax_response = this.ajax_handler.ajax_call(ajax_args,
																											this.process_ajax_response,
																											[license_section_id]);
		}

		/**
		 * Refreshes the status of the license associated to a site.
		 *
		 * @param object action_element An action element.
		 */
		this.refresh_site_status = function($action_element) {
			var license_section_id = '#' + $action_element.data('plugin_slug') + '-license-section';
			var plugin_slug = $action_element.data('plugin_slug');
			var license_key = $(license_section_id).find('.license_key').val();

			if(license_key == '') {
				// TODO Take message from the parameters, to allow translation
				window.alert('Please enter the license key and try again.');
				return;
			}
			console.log("Refreshing site activation status. Plugin slug: " + plugin_slug + ". License key: " + license_key);

			var ajax_args = {
				'exec': 'aelia-premium-plugin-updater-refresh_site_status',
				'plugin_slug': plugin_slug,
				'license_key': license_key,
			};

			// Reset and hide the error message element
			this.set_license_error_message(plugin_slug, '');
			// Pass a callback that will display the new licenses added to the order
			var ajax_response = this.ajax_handler.ajax_call(ajax_args,
																											this.process_ajax_response,
																											[license_section_id]);
		}

		/**
		 * Allows to replace the license key.
		 *
		 * @param object action_element An action element.
		 * @since 1.9.18.180319
		 */
		this.replace_license_key = function($action_element) {
			var license_section_id = '#' + $action_element.data('plugin_slug') + '-license-section';
			var $license_section = $(license_section_id);
			var plugin_slug = $action_element.data('plugin_slug');
			var $license_key_field = $license_section.find('.license_key');
			var license_key = $license_key_field.val();

			$license_key_field
				.data('current-license-key', license_key)
				.val('')
				.prop('readonly', false);

			// Hide the actions. The appropriate ones will be displayed later
			$license_section.find('.button').hide();
			// Show the buttons to cancel or confirm the action
			$license_section.find('.license_key_replace_actions_wrapper .cancel_replace_license,' +
														'.license_key_replace_actions_wrapper .confirm_replace_license').show();

			console.log("Enabled replacement of license key. Plugin slug: " + plugin_slug + ". License key: " + license_key);

			// Reset and hide the error message element
			this.set_license_error_message(plugin_slug, '');
		}

		/**
		 * Cancels the replacement of a license key.
		 *
		 * @param object action_element An action element.
		 * @since 1.9.18.180319
		 */
		this.cancel_replace_license_key = function($action_element) {
			var license_section_id = '#' + $action_element.data('plugin_slug') + '-license-section';
			var $license_section = $(license_section_id);
			var plugin_slug = $action_element.data('plugin_slug');
			var $license_key_field = $license_section.find('.license_key');

			// Hide the actions. The appropriate ones will be displayed later
			$license_section.find('.button').hide();

			var original_license_key = $license_key_field.data('current-license-key').trim();
			if(original_license_key != '') {
				$license_key_field
					.val(original_license_key)
					.prop('readonly', true);
				// Show the buttons to cancel or confirm the action
				$license_section.find('.license_key_replace_actions_wrapper .replace_license').show();
			}

			premium_licenses_manager.update_ui();

			console.log("Cancelled replacement of license key. Plugin slug: " + plugin_slug + ". License key: " + original_license_key);
		}

		/**
		 * Confirms the replacement of a license key.
		 *
		 * @param object action_element An action element.
		 * @since 1.9.18.180319
		 */
		this.confirm_replace_license_key = function($action_element) {
			var license_section_id = '#' + $action_element.data('plugin_slug') + '-license-section';
			var $license_section = $(license_section_id);
			var plugin_slug = $action_element.data('plugin_slug');
			var $license_key_field = $license_section.find('.license_key');

			// Deactivate the old license, if one was present
			var new_license_key = $license_key_field.val().trim();
			var original_license_key = $license_key_field.data('current-license-key').trim();

			if(new_license_key == '') {
				// TODO Take message from the parameters, to allow translation
				window.alert('Please enter the license key and try again.');
				return;
			}

			// TODO Take message from the parameters, to allow translation
			if(!window.confirm('This action will deactivate the existing license key (' +
					 							 original_license_key +
						 						 ') and replace it with the new license key you entered. ' +
												 "\n\n" +
												 'Click OK to proceed. If you cancel the operation, the original ' +
												 'license key will be restored.')) {
				this.cancel_replace_license_key($action_element);
				return;
			}

			/**
			 * Callback for when the license key replacement is completed. It updates
			 * updates the UI, showing the appropriate buttons.
			 */
			var license_replacement_complete = function() {
				// Unhook this callback to avoid calling it again
				$(document).off('aelia-premium-plugin-updater-activate_site-complete', license_replacement_complete);

				$license_key_field.prop('readonly', true);
				// Show the buttons to cancel or confirm the action
				$license_section.find('.license_key_replace_actions_wrapper .button').hide();
				$license_section.find('.license_key_replace_actions_wrapper .replace_license').show();

				premium_licenses_manager.update_ui();
			}
			$(document).on('aelia-premium-plugin-updater-activate_site-complete', license_replacement_complete);

			// If there is an old license key, we must deactivate it first
			if(original_license_key != '') {
				/**
				 * Callback to run after the old license has been deactivated. The callback
				 * will activate the new license.
				 */
				var replace_license_after_deactivation = function() {
					// Unhook this function from "aelia-premium-plugin-updater-activate_site-complete"
					// event. We need to run it only once, when the license is replaced,
					// not when the site is deactivated manually
					$(document).off('aelia-premium-plugin-updater-deactivate_site-complete', replace_license_after_deactivation);

					// Activate the new license
					premium_licenses_manager.activate_site($action_element, new_license_key);
				}
				// We need to wait for the deactivation of the license key before
				// activating the new one
				$(document).on('aelia-premium-plugin-updater-deactivate_site-complete', replace_license_after_deactivation);

				// Deactivate the original license
				this.deactivate_site($action_element, original_license_key);
			}
			else {
				// If no deactivation is needed, we can activate the new license key
				// right away
				this.activate_site($action_element, new_license_key);
			}

			console.log("Replaced license key. Plugin slug: " + plugin_slug + ". New license key: " + $license_key_field + ". Original license key: " + original_license_key);
		}

		/**
		 * Updates the status of the "Refresh license status" button.
		 *
		 * @param object $license_section
		 * @param bool disabled
		 * @since 1.9.8.171002
		 */
		this.set_refresh_action_status = function($license_section, disabled) {
			if(!$license_section || ($license_section.length <= 0)) {
				return;
			}

			var $check_status_action = $license_section.find('.action.refresh_status');
			if($check_status_action.length > 0) {
				$check_status_action.prop('disabled', disabled);
			}
		}

		/**
		 * Updates the user interface, reflecting the status of the license key.
		 */
		this.update_ui = function() {
			var licenses_sections = $('.aelia-premium-plugin-updater.license');

			// Trigger the "change" event, so that the status of action buttons can be
			// updated depending on the license key
			licenses_sections.find('.license_key').trigger('change');
			licenses_sections.filter('.active').find('.license_key').prop('readonly', true);
			licenses_sections.filter('.active').find('.actions .deactivate, .actions .refresh_status').show();
			licenses_sections.filter('.active').find('.actions .activate').hide();

			licenses_sections.filter('.inactive').find('.license_key').prop('readonly', false);
			licenses_sections.filter('.inactive').find('.actions .activate, .actions .refresh_status').show();
			licenses_sections.filter('.inactive').find('.actions .deactivate').hide();
		}

		/**
		 * Initialises the class.
		 */
		this.init = function() {
			var licenses_sections = $('.aelia-premium-plugin-updater.license');

			// Set action hooks on the UI
			licenses_sections.on('click', '.actions .action', function(ev) {
				ev.stopPropagation();
				var $elem = $(this);
				var action = $elem.data('action');

				// Call the action associated to the clicked element, if such action exists
				if(typeof premium_licenses_manager[action] === 'function') {
					premium_licenses_manager[action]($elem);
				}
				return false;
			})
			.on('change', '.license_key', function(ev) {
				ev.stopPropagation();
				var $elem = $(this);
				var $license_section = $elem.parents('.license_key_section').first();

				premium_licenses_manager.set_refresh_action_status($license_section, $elem.val().trim() == '');
				return false;
			});
		}

		/**
		 * Adds a new row to the licenses box on the Edit Order page.
		 *
		 * @param object license An object describing a license.
		 * @since 0.2.1.161102
		 */
		this.render_license_row = function(license) {
			var licenses_box = $('#wc_aelia_afc_order_licenses_box');
			var licenses_box_target = licenses_box.find('.licenses_list');
			var $new_row = this.license_row_template.clone();

			// Set the license ID against the row. It will be used by row-specific actions
			if(license['license_id']) {
				$new_row.data('license_id', license['license_id']);
			}
			// Use the license's status as a CSS class to easily highlight its status
			$new_row.addClass('license_' + license['license_status']);

			for(var key in license) {
				if(license.hasOwnProperty(key)) {
					// Debug
					//console.log("License property " + key + " = " + license[key]);
					$new_row.find('.' + key).text(license[key]);
				}
			}

			// Add the license row
			licenses_box_target.append($new_row);

			// Add the wrapper for the list of sites
			var $sites_wrapper_row = $('<tr>').addClass('license_sites_wrapper').data('parent_license', 'license_' + license['license_id']);
			var license_columns = $new_row.find('td').length;
			$sites_wrapper_row.append('<td colspan="' + license_columns + '">')

			// Render the table showing the sites associated to the license
			this.render_license_sites_table($sites_wrapper_row, license)

			licenses_box_target.append($sites_wrapper_row, license);
		}

		/**
		 * Adds a new table to a license row, with the list of the sites associated
		 * to the license.
		 *
		 * @param object wrapper_row The containers in which the sites table will be
		 * rendered.
		 * @param array license An array describing the license for which the sites
		 * list is being rendered.
		 * @since 0.2.1.161102
		 */
		this.render_license_sites_table = function($wrapper_row, license) {
			var $sites_table = this.license_sites_table_template.clone();
			var $target_container = $sites_table.find('tbody');

			if(!license['sites']) {
				$sites_table.addClass('empty');
				$target_container.append(this.license_sites_empty_row_template.clone());
			}
			else {
				var sites = license['sites'];
				for(var site_idx = 0; site_idx < sites.length; site_idx++) {
					var site = sites[site_idx];
					var $new_site_row = this.license_sites_row_template.clone();
					// Use the site's status as a CSS class to easily highlight its status
					$new_site_row.addClass('site_' + site['site_status']);

					for(var key in site) {
						if(site.hasOwnProperty(key)) {
							// Debug
							//console.log("Site property " + key + " = " + site[key]);
							$new_site_row.find('.' + key).text(site[key]);
						}
					}

					$new_site_row.data('license_id', site['license_id']);
					$new_site_row.data('site_id', site['site_id']);

					$target_container.append($new_site_row);
				}
			}
			$wrapper_row.find('td').append($sites_table);
		}

		this.init();
	};

	var premium_licenses_manager = new Premium_Licenses_Manager(aelia_afc_admin_params, this.aelia_afc_ajax_handler);
	premium_licenses_manager.update_ui();
});
