<?php
namespace Aelia\WC\AFC;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use Aelia\WC\Definitions;
use Aelia\WC\Premium_Plugin_Updater;

/**
 * Implements a base class to store and handle the messages used by the Aelia
 * Foundation Classes.
 */
class Messages extends \Aelia\WC\Messages {
	/**
	 * Loads the messages used by the plugin.
	 *
	 * @since 1.8.3.170110
	 */
	public function load_messages() {
		parent::load_messages();

		// Messages related to Premium Updater class
		$this->load_premium_plugin_updater_messages();
	}

	/**
	 * Loads the messages related to the Premium Updater class.
	 *
	 * @since 1.8.3.170110
	 */
	protected function load_premium_plugin_updater_messages() {
		// @see Premium_Plugin_Updater
		// TODO Replace error messages with more user friendly ones. No need to give too many technical details to the customer
		$this->add_message(Definitions::ERR_COULD_NOT_ADD_LICENSE,
											 __('Could not add license.', $this->text_domain));
		$this->add_message(Definitions::ERR_LICENSE_FOR_ORDER_ITEM_EXISTS,
											 __('License already exists for the order.', $this->text_domain));
		$this->add_message(Definitions::ERR_LICENSE_FOR_ORDER_ITEM_NOT_FOUND,
											 __('License not found for the specified order item', $this->text_domain));
		$this->add_message(Definitions::ERR_LICENSE_NOT_FOUND,
											 __('License not found, or not valid for this product', $this->text_domain));
		$this->add_message(Definitions::ERR_LICENSE_NOT_ACTIVE,
											 __('License is not active', $this->text_domain));
		$this->add_message(Definitions::ERR_COULD_NOT_UPDATE_LICENSE,
											 __('License could not be updated', $this->text_domain));
		$this->add_message(Definitions::ERR_COULD_NOT_REVOKE_LICENSE,
											 __('License could not be revoked', $this->text_domain));
		$this->add_message(Definitions::ERR_ORDER_STATUS_NOT_VALID,
											 __('Order status not valid', $this->text_domain));
		$this->add_message(Definitions::ERR_LICENSE_NOT_VALID_FOR_UPDATES,
											 __('License is not valid', $this->text_domain));
		$this->add_message(Definitions::ERR_LICENSE_STATUS_NOT_VALID,
											 __('License status is not valid', $this->text_domain));

		$this->add_message(Definitions::ERR_PRODUCT_PACKAGE_NOT_FOUND,
											 __('Product package not found', $this->text_domain));
		$this->add_message(Definitions::ERR_LICENSE_EXPIRED,
											 __('License has expired', $this->text_domain));
		$this->add_message(Definitions::ERR_COULD_NOT_SET_STATUS_FOR_EXPIRED_LICENSES,
											 __('Could not set status because the license has expired', $this->text_domain));
		$this->add_message(Definitions::ERR_LICENSE_KEY_NOT_ACTIVE,
											 __('License key not active, or expired', $this->text_domain));

		// Messages related to premium product licenses (related to site activations)
		// @see Premium_Plugin_Updater
		$this->add_message(Definitions::ERR_COULD_NOT_ADD_SITE,
											 __('Could not add site activation', $this->text_domain));
		$this->add_message(Definitions::ERR_COULD_NOT_UPDATE_SITE,
											 __('Could not modify site activation', $this->text_domain));
		$this->add_message(Definitions::ERR_LICENSE_MAX_ACTIVATIONS_REACHED,
											 __('Reached the maximum numbers of activations for the licenses, no further activations allowed', $this->text_domain));
		$this->add_message(Definitions::ERR_COULD_NOT_VALIDATE_ACTIVATION,
											 __('Could not validate license activation request for the site', $this->text_domain));
		$this->add_message(Definitions::ERR_SITE_ACTIVATION_ALREADY_EXISTS,
											 __('License was already activated for this site', $this->text_domain));
		$this->add_message(Definitions::ERR_SITE_ALREADY_INACTIVE,
											 __('License was already deactivated for this site', $this->text_domain));
		$this->add_message(Definitions::ERR_COULD_NOT_DEACTIVATE_SITE,
											 __('Site could not be deactivated', $this->text_domain));
		$this->add_message(Definitions::ERR_COULD_NOT_VALIDATE_DEACTIVATION,
											 __('Could not validate license deactivation request for the site', $this->text_domain));
		$this->add_message(Definitions::ERR_SITE_DOES_NOT_EXIST,
											 __('Could not find a license for the specified site', $this->text_domain));
		$this->add_message(Definitions::ERR_SITE_NOT_ACTIVE,
											 __('Could not find an active license for the specified site', $this->text_domain));

		// Premium feature removed
	}
}
