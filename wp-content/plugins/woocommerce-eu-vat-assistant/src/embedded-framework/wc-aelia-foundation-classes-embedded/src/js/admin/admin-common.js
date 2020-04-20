/* JavaScript for Admin section (loaded on all admin pages) */
jQuery(document).ready(function($) {
	/**
	 * Handles the Ajax requests in the Admin area.
	 *
	 * @param object params The parameterd used to initialise the class.
	 * @since 1.9.4.170410
	 */
	function Aelia_AFC_Ajax_Handler(params) {
		this.params = params;
		this.ajax_url = params.ajax_url;

		// Common arguments for Ajax calls
		this.default_ajax_args = {
			'action': this.params.ajax_action,
			'_ajax_nonce': this.params.wp_nonce
		}

		/**
		 * Returns a set of Ajax arguments, which include the default ones.
		 *
		 * @param object args A set of arguments, to replace the default ones.
		 * @return object
		 */
		this.get_ajax_args = function(args) {
			return $.extend({}, this.default_ajax_args, args);
		}

		/**
		 * Blocks or unblocks a set of elements, using jQueryUI.
		 *
		 * @param array element_selectors An array of selectors that will be used
		 * to retrieve the elements to block or unblock.
		 * @param bool should_block Indicates if the elements should be blocked or
		 * unblocked.
		 */
		var set_elements_block = function(element_selectors, should_block) {
			$.each(element_selectors, function(index, element_selector) {
				var $elem = $(element_selector);
				if(should_block) {
					$elem.block({
						message: null,
						overlayCSS: {
							background: '#fff',
							opacity: 0.6
						}
					});
				}
				else {
					$elem.unblock();
				}
			});
		}

		/**
		 * Performs an Ajax call and returns its result to a callback.
		 *
		 * @param array args The arguments to use for the Ajax call.
		 * @param callable success_callback The callback to invoke when the call is
		 * succesful.
		 * @param array ui_elements_to_block An array of UI elements that should be
		 * blocked during the Ajax call.
		 */
		this.ajax_call = function(args, success_callback, ui_elements_to_block) {
			// Prepare arguments for Ajax call
			var ajax_args = this.get_ajax_args(args);

			// Trigger an event when the response has been processed
			var exec_action = args['exec'] ? args['exec'] : 'unknown';

			// Block the UI elements before the Ajax call
			if(ui_elements_to_block) {
				set_elements_block(ui_elements_to_block, true);
			}
			$.post(this.ajax_url, ajax_args, function(response) {
				//console.log(response);
				// Invoke the callback on success
				success_callback(response, args);

				// Trigger an event when the Ajax call is successful
				// @since 1.9.18.180319
				$(document).trigger(exec_action + '-success');
			})
			.always(function() {
				if(ui_elements_to_block) {
					// Unblock the elements that were blocked during the Ajax call
					set_elements_block(ui_elements_to_block, false);
				}

				// Trigger an event when the Ajax call is complete
				// @since 1.9.18.180319
				$(document).trigger(exec_action + '-complete');
			});
		}
	}
	// Initialise the Ajax handler class
	this.aelia_afc_ajax_handler = new Aelia_AFC_Ajax_Handler(aelia_afc_admin_params);

	var $messages = $('.wc_aelia.message');
	/**
	 * Dismisses admin messages.
	 */
	$messages.find('.message_action').on('click', function(e) {
		e.stopPropagation();

		var $action = $(this);
		var action_url = $action.attr('href');
		var message_id = $action.attr('message_id');

		// Hide the message in the UI, while the Ajax call is processed. This will
		// give an immediate feedback to the user
		$action.parents('#' + message_id).fadeOut('slow');

		$.ajax({
			type: 'POST',
			url: action_url,
			dataType: 'json',
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				console.log(XMLHttpRequest.responseText);
				console.log(textStatus);
				console.log(errorThrown);
			},
			success: function(json) {
				console.log(json);
			},
			complete: function(XMLHttpRequest, textStatus) {
				console.log('complete');
			}
		});
		return false;
	});
});
