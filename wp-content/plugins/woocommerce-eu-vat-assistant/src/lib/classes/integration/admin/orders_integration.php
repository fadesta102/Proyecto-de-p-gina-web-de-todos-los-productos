<?php
namespace Aelia\WC\EU_VAT_Assistant;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Implements an integration with WooCommerce Orders pages.
 *
 * @since 1.6.1.160201
 */
class Orders_Integration extends \Aelia\WC\Base_Class {
	/**
	 * Returns the URL for Ajax operations related to orders.
	 *
	 * @param int order_id The targer order ID. If empty, the ID of the order being
	 * edited, if any, is taken.
	 * @return string
	 */
	protected static function get_ajax_url($order_id = null) {
		$ajax_args = array(
			'action' => Definitions::ARG_COLLECT_ORDER_VAT_INFO,
			'_ajax_nonce' => wp_create_nonce('aelia-euva-' . Definitions::ARG_COLLECT_ORDER_VAT_INFO),
		);

		if(empty($order_id)) {
			$order_id = self::editing_order();
			$ajax_args['order_id'] = $order_id;
		}
		// Convert the array of arguments into an HTTP query string, ready for use
		// in an Ajax call
		$ajax_args = http_build_query($ajax_args);

		return admin_url('admin-ajax.php', 'absolute') . '?' . $ajax_args;
	}

	/**
	 * Adds localisation data for the JavaScript that extend the Tax Settings pages.
	 *
	 * @param array $admin_scripts_params The array of parameters to extend.
	 * @return array The array of parameters with additional data.
	 */
	public static function localize_admin_scripts(array $admin_scripts_params) {
		$euva = WC_Aelia_EU_VAT_Assistant::instance();
		$text_domain = WC_Aelia_EU_VAT_Assistant::$text_domain;

		$admin_scripts_params['orders_settings'] = array(
			'collect_vat_data_for_manual_orders' => WC_Aelia_EU_VAT_Assistant::settings()->get(Settings::FIELD_COLLECT_VAT_DATA_FOR_MANUAL_ORDERS, false),
			'collect_order_ajax_url' => self::get_ajax_url(),
			'user_interface' => array(
				'collect_order_vat_prompt' => __('This operation will analyse the order, collect ' .
																				 'the VAT information associated to it and make ' .
																				 'it available to the EU VAT Assistant.', $text_domain) .
																			' ' .
																			__('If you already performed this operation before, ' .
																				 'on this order, the VAT information collected ' .
																				 'will replace the existing one.', $text_domain) .
																			"\n\n" .
																			__('Would you like to continue?', $text_domain),
			),
		);
		return $admin_scripts_params;
	}

	/**
	 * Validates an Ajax request.
	 *
	 * @return bool
	 */
	public static function validate_ajax_request($user_permission = 'manage_woocommerce') {
		$result = true;
		if(!current_user_can($user_permission)) {
			$message = 'HTTP/1.1 403 Forbidden';
			header($message, true, 403);
			$result = false;
		}

		// Handle invalid requests (e.g. a request missing required arguments)
		if($result && (empty($_REQUEST[Definitions::ARG_COLLECT_ORDER_ID]) || !is_numeric($_REQUEST[Definitions::ARG_COLLECT_ORDER_ID]))) {
			$message = 'HTTP/1.1 400 Bad Request';
			header($message, true, 400);
			$result = false;
		}

		if($result && !check_ajax_referer('aelia-euva-' . Definitions::ARG_COLLECT_ORDER_VAT_INFO, '_ajax_nonce', false)) {
			header('HTTP/1.1 400 Bad Request', true, 400);
			$message = 'Ajax referer check failed';
			$result = false;
		};

		if($result == false) {
			wp_send_json(array(
				'result' => $result,
				'messages' => array($message),
			));
		}
		return $result;
	}

	/**
	 * Indicates if we are on an "Edit Order" page.
	 *
	 * @return int|false The ID of the order being edited, or false if we are not
	 * on the "Edit Order" page.
	 */
	public static function editing_order() {
		global $post;
		if(is_admin() && !WC_Aelia_EU_VAT_Assistant::doing_ajax() &&
			 is_object($post) && ($post->post_type == 'shop_order')) {
			return $post->ID;
		}
		return false;
	}

	/**
	 * Adds the "VAT number" field to the billing fields on the order edit page.
	 * This will allow admins to enter a VAT number more easily when creating
	 * orders manually.
	 *
	 * @param array billing_fields An array of billing fields.
	 * @retur array The billing fields, with the VAT number field added to it.
	 * @since 1.6.2.160315
	 */
	public static function woocommerce_admin_billing_fields($billing_fields) {
		$text_domain = WC_Aelia_EU_VAT_Assistant::$text_domain;
		$billing_fields['vat_number'] = array(
			//'id' => 'vat_number',
			'name' => 'vat_number',
			'label' => __('VAT number', $text_domain),
			'class' => 'tips',
			'placeholder' => __("Customer's VAT number, if any", $text_domain),
			'custom_attributes' => array(
				'data-tip' => __('This number will be stored as is, without validation.', $text_domain) .
											'<br><br>'.
											'<strong>' . __('Important', $text_domain) . '</strong>: ' .
											__('Changing this number will not apply a tax exemption, and ' .
												 'it will not affect the VAT data that has already been ' .
												 'collected by the EU VAT Assistant (see <strong>VAT ' .
												 'Information</strong> box).', $text_domain),
			),
			'show'  => false
		);

		// Since WooCommerce 3.x, the value of the field is no longer taken
		// automatically, using the field ID as the meta key. WooCommerce 3.x
		// incorrectly assumes that the field ID should be prefixed with an underscore
		// and tries to fetch the VAT number from field "_vat_number", which doesn't
		// exist.
		// To prevent this issue, the easiest way is to pass the field value explicitly.
		// @since WC 3.0
		// @since 1.8.1.180802
		if(aelia_wc_version_is('>=', '3.0')) {
			global $theorder;

			if(!empty($theorder) && ($theorder instanceof \WC_Order)) {
				$billing_fields['vat_number']['value'] = $theorder->get_meta('vat_number');
			}
		}

		return $billing_fields;
	}

	/**
	 * Saves order meta, to ensure that the VAT number is stored correctly.
	 *
	 * @param int $order_id
	 * @since 1.9.6.190208
	 */
	public static function woocommerce_process_shop_order_meta($order_id) {
		// Get order object.
		$order = wc_get_order( $order_id );

		if(isset($_POST['vat_number'])) {
			$order->update_meta_data('vat_number', wc_clean($_POST['vat_number']));
		}
		$order->save();
	}

	/**
	 * Adds a customer's VAT number to the data loaded when filling customer's
	 * billing details on the Edit Order page.
	 *
	 * @param array $data
	 * @param object $customer
	 * @param int $user_id
	 * @return array
	 * @since 1.9.6.190208
	 */
	public function woocommerce_ajax_get_customer_details($data, $customer, $user_id) {
		if(empty($data['billing']['vat_number'])) {
			$data['billing']['vat_number'] = get_user_meta($user_id, Definitions::FIELD_VAT_NUMBER, true);
		}

		return $data;
	}

	/**
	 * Intercepts the creation of refunds, storing VAT data against them.
	 *
	 * NOTE
	 * This features has been deliberately disabled because it might not be needed
	 * and could increase the size of the order meta stored in the database.
	 *
	 * @param int $refund_id
	 * @param array $args
	 * @since 1.10.1.191108
	 */
	// public function woocommerce_refund_created($refund_id, $args) {
	// 	$refund = new Order($refund_id);
	// 	// Generate and store details about order VAT
	// 	$refund->update_vat_data();
	// 	// Save EU VAT compliance evidence
	// 	$refund->store_vat_evidence();
	// }
}
