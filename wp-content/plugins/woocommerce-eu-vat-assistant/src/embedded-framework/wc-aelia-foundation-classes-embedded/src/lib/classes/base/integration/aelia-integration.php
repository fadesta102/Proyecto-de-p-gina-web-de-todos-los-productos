<?php
namespace Aelia\WC;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use \ReflectionClass;
use \Exception;
use \WC_Admin_Reports;

if(!class_exists('Aelia\WC\Aelia_Integration')) {
	/**
	 * Implements a special plugin class to be used to implement WooCommerce
	 * Integrations. This class is an almost identical clone of the Aelia_Plugin
	 * class, but it descends from the \WC_Integration class.
	 *
	 * @since 1.7.0.160329
	 * @see \Aelia\WC\Aelia_Plugin.
	 */
	class Aelia_Integration extends \WC_Integration {
		// @var string The plugin text domain
		protected static $text_domain = 'wc-aelia-plugin';
		// @var string The plugin slug
		protected static $integration_id = 'aelia_base_integration';
		// @var string The integration title
		protected static $integration_title = 'Aelia Base Integration';
		// @var string The integration description
		protected static $integration_description = 'Aelia Base Integration - Description';

		// @var Aelia_Integration The instance of this integration
		protected static $_instance;

		/**
		 * Returns the instance of the integration.
		 *
		 * @return Aelia_Integration.
		 */
		public static function instance() {
			if(empty(static::$_instance)) {
				$integrations = WC()->integrations->get_integrations();
				static::$_instance = $integrations[static::$integration_id];
			}
			return static::$_instance;
		}

		/**
		 * Sets the hook handlers for WC and WordPress.
		 */
		protected function set_hooks() {
			// Hook into the action used to process the integration settings
			add_action('woocommerce_update_options_integration_' .  $this->id, array($this, 'process_admin_options'));
		}

		/**
		 * Constructor.
		 *
		 * the messages produced by the plugin.
		 */
		public function __construct() {
			$this->id = static::$integration_id;
			$this->method_title = __(static::$integration_title, self::$text_domain);
			$this->method_description = __(static::$integration_description, self::$text_domain);

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Load the integration settings
			$this->load_settings();

			// Set all required hooks
			$this->set_hooks();

			// indicates we are running the admin
			if(is_admin()) {
				// ...
			}
		}

		/**
		 * Initialises the fields for the integration. Extend as needed.
		 *
		 * @return void
		 */
		public function init_form_fields() {
			return parent::init_form_fields();
		}

		/**
		 * Loads the settings for the integration, using WooCommerce Settings API.
		 */
		protected function load_settings() {
			// Load settings for the integration (see examples below)
			//$this->api_key = $this->get_option('api_key');
			//$this->debug = $this->get_option('debug');
		}

		/**
		 * Adds this integration to the list of the WooCommerce integrations.
		 */
		public static function register_integration(array $integrations) {
			$integrations[] = get_called_class();
			return $integrations;
		}
	}
}
