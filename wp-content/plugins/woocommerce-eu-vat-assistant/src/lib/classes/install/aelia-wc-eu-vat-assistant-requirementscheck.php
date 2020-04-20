<?php
if(!defined('ABSPATH')) exit; // Exit if accessed directly

require_once('aelia-wc-requirementscheck.php');

/**
 * Checks that plugin's requirements are met.
 */
class Aelia_WC_EU_VAT_Assistant_RequirementsChecks extends Aelia_WC_RequirementsChecks {
	// @var string The namespace for the messages displayed by the class.
	protected $text_domain = 'wc-aelia-eu-vat-assistant';
	// @var string The plugin for which the requirements are being checked. Change it in descendant classes.
	protected $plugin_name = 'WooCommerce EU VAT Assistant';

	// @var array An array of PHP extensions required by the plugin
	protected $required_extensions = array(
		'curl',
	);

	// @var array An array of WordPress plugins (name => version) required by the plugin.
	protected $required_plugins = array(
		'WooCommerce' => '3.0',
		'Aelia Foundation Classes for WooCommerce' => array(
			'version' => '1.8.6.170405',
			'extra_info' => 'You can get the plugin <a href="http://bit.ly/WC_AFC_S3">from our site</a>, free of charge.',
			'autoload' => true,
			'url' => 'http://bit.ly/WC_AFC_S3',
		),
	);

	/**
	 * Factory method. It MUST be copied to every descendant class, as it has to
	 * be compatible with PHP 5.2 and earlier, so that the class can be instantiated
	 * in any case and and gracefully tell the user if PHP version is insufficient.
	 *
	 * @return Aelia_WC_AFC_RequirementsChecks
	 */
	public static function factory() {
		$instance = new self();
		return $instance;
	}

	// { WordPress.org-Feature-Start }
	/**
	 * Indicates if the standalone AFC is being installed. In such case,
	 * the embedded framework should not be loaded, or it could cause a
	 * conflict during the installation.
	 *
	 * @return bool
	 * @since 1.9.3.181217
	 */
	protected function skip_embedded_framework() {
		if(isset($_REQUEST['action']) && isset($_REQUEST['plugin'])) {
			// Activation or deletion of AFC via standard WordPress link on Plugins page
			if(in_array($_REQUEST['action'], array('activate', 'delete-plugin')) && (strpos($_REQUEST['plugin'], 'wc-aelia-foundation-classes.php') !== false)) {
				return true;
			}

			// Installation or activation of AFC via Aelia's Ajax functions
			if(($_REQUEST['plugin'] === 'aelia-foundation-classes-for-woocommerce') &&
					((strpos($_REQUEST['action'], 'install_plugin_') === 0) ||
					(strpos($_REQUEST['action'], 'activate_plugin_') === 0))) {
				return true;
			}
		}
		return false;
	}
	// { WordPress.org-Feature-End }

	// { WordPress.org-Feature-Start }
	/**
	 * Alters the requirements checking logic to allow the plugin to load an
	 * "embedded" version of the Aelia Foundation Classes. Required by the
	 * WordPress.org guidelines.
	 *
	 * @param bool $autoload_plugins
	 * @since 1.9.1.181209
	 */
	protected function check_required_plugins($autoload_plugins = true) {
		parent::check_required_plugins($autoload_plugins);

		// Skip the loading of the embedded framework while the standalone
		// AFC is being installed or plugins are modified (installed, deleted).
		// This will prevent conflicts arising during the installation or
		// removal of the AFC
		// @since 1.9.3.181217
		if($this->skip_embedded_framework()) {
			return;
		}

		if(!empty($this->plugin_actions)) {
			foreach($this->plugin_actions as $plugin_name => $action) {
				// Set AFC "plugin" directory, to allow loading its CSS and JS files
				// from the correct location when the embedded framework is used
				if(!defined('AFC_PLUGIN_DIR')) {
					define('AFC_PLUGIN_DIR', 'woocommerce-eu-vat-assistant/src/embedded-framework/wc-aelia-foundation-classes-embedded');
				}

				// If the AFC is missing, use the "embedded" framework. If the AFC is
				// present, but inactive, then the plugin will ask the Administrator
				// to activate it instead
				if(($plugin_name === 'Aelia Foundation Classes for WooCommerce')) {
					// Try to load the "embedded" AFC. If successful, remove the
					// "AFC missing" message and configure the AFC to run as an
					// embedded framework
					if(@include_once(__DIR__ . '/../../../embedded-framework/wc-aelia-foundation-classes-embedded/wc-aelia-foundation-classes.php')) {
						// Disable the AFC Updaters. They can't run properly when the AFC is loaded
						// as a local framework
						if(!defined('DISABLE_AFC_UPDATERS')) {
							define('DISABLE_AFC_UPDATERS', true);
						}

						// Remove the" missing requirement" message
						unset($this->plugin_actions[$plugin_name]);
						unset($this->requirements_errors[$plugin_name]);
					};

				}
			}

			// Debug
			//var_dump($this->plugin_actions);die();
		}
	}
	// { WordPress.org-Feature-End }
}
