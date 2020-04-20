<?php
namespace Aelia\WC;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

//define('SCRIPT_DEBUG', 1);
//error_reporting(E_ALL);

require_once('lib/classes/base/plugin/aelia-plugin.php');
require_once('lib/classes/definitions/definitions.php');

use Aelia\WC\AFC\Settings;
use Aelia\WC\AFC\Messages;

/**
 * Aelia Foundation Classes for WooCommerce.
 **/
class WC_AeliaFoundationClasses extends Aelia_Plugin {
	public static $version = '2.0.16.200317';

	public static $plugin_slug = Definitions::PLUGIN_SLUG;
	public static $text_domain = Definitions::TEXT_DOMAIN;
	public static $plugin_name = 'Aelia Foundation Classes for WooCommerce';

	/**
	 * The action used to route ajax calls to this plugin.
	 *
	 * @var string
	 */
	protected static $ajax_action = 'wc_aelia_afc_ajax';

	public static function factory() {
		// Load Composer autoloader
		require_once(__DIR__ . '/vendor/autoload.php');

		$settings_controller = new Settings(self::$text_domain);
		$messages_controller = new Messages(self::$text_domain);

		$plugin_instance = new self($settings_controller, $messages_controller);
		return $plugin_instance;
	}

	/**
	 * Constructor.
	 *
	 * @param Aelia\WC\Settings settings_controller The controller that will handle
	 * the plugin settings.
	 * @param Aelia\WC\AFC\Messages messages_controller The controller that will handle
	 * the messages produced by the plugin.
	 */
	public function __construct($settings_controller,
															$messages_controller) {
		// Load Composer autoloader
		require_once(__DIR__ . '/vendor/autoload.php');
		require_once('lib/wc-core-aux-functions.php');

		parent::__construct($settings_controller, $messages_controller);
	}

	/**
	 * Sets the hooks required by the plugin.
	 *
	 * @since 1.6.0.150724
	 */
	protected function set_hooks() {
		parent::set_hooks();
		add_filter('cron_schedules', array($this, 'cron_schedules'));
		add_action('aelia_afc_geoip_updater', array('\Aelia\WC\IP2Location', 'update_database'));

		// Admin init
		add_action('admin_init', array($this, 'admin_init'), 5);

		add_action('wp_login', array($this, 'wp_login'), 10, 2);
	}

	/**
	 * Adds more scheduling options to WordPress Cron.
	 *
	 * @param array schedules Existing Cron scheduling options.
	 * @return array The schedules, with "weekly" and "monthly" added to the list.
	 * @since 1.6.0.150724
	 */
	public function cron_schedules($schedules) {
		if(empty($schedules['weekly'])) {
			// Adds "weekly" to the existing schedules
			$schedules['weekly'] = array(
				'interval' => 604800,
				'display' => __('Weekly', self::$text_domain),
			);
		}

		if(empty($schedules['monthly'])) {
			// Adds "monthly" to the existing schedules
			$schedules['monthly'] = array(
				'interval' => 2592000,
				'display' => __('Monthly (every 30 days)', self::$text_domain),
			);
		}
		return $schedules;
	}

	/**
	 * Registers the script and style files required in the backend (even outside
	 * of plugin's pages).
	 *
	 * @since 1.6.1.150728
	 */
	protected function register_common_admin_scripts() {
		// Scripts
		wp_register_script(self::$plugin_slug . '-admin-common',
											 $this->url('plugin') . '/js/admin/admin-common.js',
											 array('jquery'),
											 self::$version,
											 true);
		wp_enqueue_script(static::$plugin_slug . '-admin-common');

		// Styles
		wp_register_style(self::$plugin_slug . '-admin',
											$this->url('plugin') . '/design/css/admin.css',
											array(),
											self::$version,
											'all');
		// Styles - Enqueue styles required for plugin Admin page
		wp_enqueue_style(static::$plugin_slug . '-admin');

		do_action('wc_aelia_afc_load_admin_scripts');

		$this->localize_admin_scripts();
	}

	/**
	 * Loads the settings that will be used by the admin scripts.
	 *
	 * @since 1.9.4.170410
	 */
	protected function localize_admin_scripts() {
		// Prepare parameters for common admin scripts
		$admin_scripts_params = array(
			'ajax_action' => $this->ajax_action(),
			'ajax_url' => admin_url('admin-ajax.php', 'relative'),
			'home_url' => home_url(),
			'wp_nonce' => wp_create_nonce($this->ajax_nonce_id()),
		);

		$admin_scripts_params = apply_filters('wc_aelia_afc_admin_script_params', $admin_scripts_params);

		wp_localize_script(static::$plugin_slug . '-admin-common',
											 'aelia_afc_admin_params',
											 $admin_scripts_params);
	}

	/**
	 * Loads Styles and JavaScript for the frontend. Extend as needed in
	 * descendant classes.
	 */
	public function load_frontend_scripts() {
		// Enqueue the required Frontend stylesheets
		//wp_enqueue_style(static::$plugin_slug . '-frontend');

		// JavaScript
		wp_enqueue_script(static::$plugin_slug . '-frontend');
		//$this->localize_frontend_scripts();
	}

	/**
	 * Sets the Cron schedules required by the plugin.
	 *
	 * @since 1.6.0.150724
	 */
	protected function set_cron_schedules() {
		//wp_schedule_event(strtotime('first tuesday of next month'), 'monthly', 'woocommerce_geoip_updater' );
		if(!wp_get_schedule('aelia_afc_geoip_updater')) {
			wp_schedule_event(time(), 'weekly', 'aelia_afc_geoip_updater');
		}
	}

	/**
	 * Triggers actions when the admin section is initialised.
	 *
	 * @since 1.9.10.171201
	 */
	public function admin_init() {
		$this->show_admin_messages();

		// Initialize the updaters when in the Admin section
		// @since 1.8.3.170110
		$this->initialize_updaters();
	}

	/**
	 * Shows messages to the site administrators.
	 *
	 * @since 1.9.10.171201
	 */
	public function show_admin_messages() {
		// Premium feature removed
	}

	/**
	 * Indicates if the plugin updaters should be initialised.
	 *
	 * @return bool
	 * @since 1.9.12.180104
	 */
	protected function should_initialize_updaters() {
		// Disable updaters if the global DISABLE_AFC_UPDATERS is set
		// @since 2.0.2.181203
		if(defined('DISABLE_AFC_UPDATERS') && DISABLE_AFC_UPDATERS) {
			return false;
		}

		// If current user cannot manage the plugins, don't load the updaters
		if(!current_user_can('update_plugins')) {
			return false;
		}

		// If we are handling an Ajax call, and it's not a plugin update or an AFC
		// call, don't load the updaters
		if(self::doing_ajax() && (empty($_REQUEST['action']) || !in_array($_REQUEST['action'], array('update-plugin', 'wc_aelia_afc_ajax')))) {
			return false;
		}

		return true;
	}

	/**
	 * Initialises the updater classes, which will check for product updates.
	 *
	 * @since 1.7.0.150818
	 */
	public function initialize_updaters() {
		// Standalone AFC Framework feature removed
	}

	/**
	 * Allows the plugin to register itself for automatic updates.
	 *
	 * @param array The array of the plugins to update, structured as follows:
	 * array(
	 *   'free' => <Array of free plugins>,
	 *   'free-dev' => <Array of free plugins (development versions)>,
	 *   'premium' => <Array of premium plugins, which require licence activation>,
	 * )
	 * @return array The array of plugins to update, with the details of this
	 * plugin added to it.
	 * @since 1.7.0.150818
	 */
	public function wc_aelia_afc_register_plugins_to_update(array $plugins_to_update) {
		// Set the following to "false" to stop using the development version of the
		// AFC
		$use_dev_updates = false;

		// If the "DEV" flag is enabled, use the updater for the development version
		// of the plugin
		if($use_dev_updates) {
			// Add this plugins to the list of the free plugins (development versions)
			// to update automatically
			$plugins_to_update['free-dev'][self::$plugin_slug] = $this;
		}
		else {
			// Add this plugins to the list of the free plugins to update automatically
			$plugins_to_update['free'][self::$plugin_slug] = $this;
		}

		return $plugins_to_update;
	}

	/**
	 * Setup function. Called when plugin is enabled.
	 *
	 * @since 1.6.0.150724
	 */
	public function setup() {
		// Keep track of the fact that we are in the setup phase
		$this->running_setup = true;
		IP2Location::install_database();

		// Register the deactivation hook
		register_deactivation_hook($this->main_plugin_file, array($GLOBALS['wc-aelia-foundation-classes'], 'deactivate'));
	}

	/**
	 * Performs cleanup operations when the plugin is uninstalled.
	 *
	 * @since 2.0.9.191108
	 */
	public function deactivate() {
		wp_clear_scheduled_hook('aelia_afc_geoip_updater');
	}

	/**
	 * Performs operations required on WooCommerce load.
	 */
	public function woocommerce_loaded() {
		if(!$this->running_setup && current_user_can('manage_woocommerce')) {
			// Check if the forced installation of the GeoIP database was requested
			if(!empty($_REQUEST[Definitions::ARG_INSTALL_GEOIP_DB])) {
				$this->running_setup = true;
				IP2Location::install_database();
			}

			if(is_admin()) {
				// Ensure that the GeoIP database exists, and inform the Administrator if
				// it doesn't
				if(!$this->running_setup && !file_exists(IP2Location::geoip_db_file())) {
					Messages::admin_message(
						__('GeoIP database file not found.', self::$text_domain) .
						'&nbsp;' .
						sprintf(__('Please %s.', self::$text_domain),
										IP2Location::get_geoip_install_html(__('try to install the database again',
																										 self::$text_domain))) .
						'&nbsp;' .
						sprintf(__('If the error persists, please download the the database ' .
											 'manually, from <a href="%1$s">%1$s</a>. Extract file ' .
											 '<strong>%2$s</strong> from the archive and copy it to ' .
											 '<code>%3$s</code>.',
											 self::$text_domain),
										IP2Location::GEOLITE_DB,
										IP2Location::$geoip_db_file,
										dirname(IP2Location::geoip_db_file())) .
						'&nbsp;' .
						__('Geolocation will become available automatically, as soon as the ' .
							 'GeoIP database is copied in the indicated folder.',
							 self::$text_domain) .
						'&nbsp;<br /><br />' .
						sprintf(__('For more information about this message, <a href="%s">please refer to our ' .
											 'knowledge base</a>.',
											 self::$text_domain),
									 'http://bit.ly/AFC_Geolocation'),
						array(
							'level' => E_USER_ERROR,
							'code' => Definitions::ERR_COULD_NOT_UPDATE_GEOIP_DATABASE,
						)
					);
				}
			}
		}
	}

	/**
	 * Performs actions when the user logs in.
	 *
	 * @param string user_login The user's login.
	 * @param object user A user object.
	 * @since 1.8.2.161216
	 */
	public function wp_login($user_login, $user) {
		// Sets the cookie that indicates that cart fragments should be refreshed.
		// We store the cookie path in the cookie, to make it easier to retrieve that
		// information via JavaScript
		Aelia_SessionManager::set_cookie(Definitions::SESSION_USER_LOGGED_IN, COOKIEPATH);
	}

	/**
	 * Indicates if debug mode is active.
	 *
	 * @return bool
	 * @since 1.9.4.170410
	 */
	public function debug_mode() {
		return $this->_settings_controller->debug_mode();
	}

	/**
	 * Returns a list of valid Ajax commands and the callback associated to each.
	 *
	 * @return array A list of command => callback pairs.
	 * @since 1.9.4.170410
	 */
	protected function get_valid_ajax_commands() {
		return apply_filters('wc_aelia_afc_ajax_callbacks', array(
			// Add the Ajax commands provided by the plugin, in
			// command => callable format
		));
	}

	/**
	 * Returns the directory in which the plugin is stored. Only the base name of
	 * the directory is returned (i.e. without path).
	 *
	 * @return string
	 * @since
	 */
	public function plugin_dir() {
		// If a 3rd party specified a directory for the AFC
		// plugin, use it
		// @since 2.0.2.181203
		if(defined('AFC_PLUGIN_DIR')) {
			$this->plugin_directory = AFC_PLUGIN_DIR;
		}

		return parent::plugin_dir();
	}
}
class_alias('\Aelia\WC\WC_AeliaFoundationClasses', 'WC_AeliaFoundationClasses');

// Instantiate plugin and add it to the set of globals
$GLOBALS[WC_AeliaFoundationClasses::$plugin_slug] = WC_AeliaFoundationClasses::factory();
