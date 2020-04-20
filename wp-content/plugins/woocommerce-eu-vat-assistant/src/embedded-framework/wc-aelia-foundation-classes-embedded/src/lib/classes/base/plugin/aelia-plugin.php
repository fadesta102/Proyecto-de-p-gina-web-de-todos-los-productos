<?php
namespace Aelia\WC;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use \ReflectionClass;
use \Exception;
use \WC_Admin_Reports;

if(!class_exists('Aelia\WC\Aelia_Plugin')) {
	interface IAelia_Plugin {
		public function settings_controller();
		public function messages_controller();
		public static function instance();
		public static function settings();
		public function setup();
		public static function cleanup();
		public function get_plugin_file($relative_path_only = false);
		public function get_slug($for_updates = false);
	}

	// Load general functions file
	require_once('general_functions.php');

	/**
	 * Implements a base plugin class to be used to implement WooCommerce plugins.
	 */
	class Aelia_Plugin implements IAelia_Plugin {
		// @var string The plugin version.
		public static $version = '0.1.0';

		// @var string The plugin slug
		public static $plugin_slug = 'wc-aelia-plugin';
		// @var string The plugin text domain
		public static $text_domain = 'wc-aelia-plugin';
		// @var string The plugin name
		public static $plugin_name = 'wc-aelia-plugin';

		/**
		 * The action used to route ajax calls to this plugin.
		 *
		 * @var string
		 * @since 1.9.4.170410
		 */
		protected static $ajax_action = 'wc-aelia-plugin';

		// @var string The folder and file name of the main plugin file. This may
		// not be the same file where the main plugin class is located, as many
		// plugin use the main file as a simple loader.
		public $main_plugin_file;

		// @var string The base name of the plugin directory
		protected $plugin_directory;

		// @var Aelia\WC\Settings The object that will handle plugin's settings.
		protected $_settings_controller;
		// @var Aelia\WC\Messages The object that will handle plugin's messages.
		protected $_messages_controller;
		// @var Aelia_SessionManager The session manager
		protected $_session;
		// @var Settings The instances of the settings controllers used by the various plugins. Used for caching.
		protected static $_settings = array();

		// @var bool Indicates if the setup process of the plugin is running
		protected $running_setup = false;

		protected $paths = array(
			// This array will contain the paths used by the plugin
		);

		protected $urls = array(
			// This array will contain the URLs used by the plugin
		);

		// @var Aelia\WC\Logger The logger used by the class.
		protected $logger;

		// @var string The class name to use as a prefix for log messages.
		protected $class_for_log = '';

		/**
		 * Returns the class name to use as a prefix for log messages.
		 *
		 * @return string
		 * @since 1.6.1.150728
		 */
		protected function get_class_for_log() {
			if(empty($this->class_for_log)) {
				$reflection = new \ReflectionClass($this);
				$this->class_for_log = $reflection->getShortName();
			}
			return $this->class_for_log;
		}

		/**
		 * Logs a message.
		 *
		 * @param string message The message to log.
		 * @param bool debug Indicates if the message is for debugging. Debug messages
		 * are not saved if the "debug mode" flag is turned off.
		 */
		public function log($message, $debug = true) {
			// Prefix message with the class name, for easier identification
			$message = sprintf('[%s] %s',
												 $this->get_class_for_log(),
												 $message);
			$this->get_logger()->log($message, $debug);
		}

		/**
		 * Returns the instance of the logger used by the plugin.
		 *
		 * @return \Aelia\WC\Logger.
		 * @since 1.6.1.150728
		 */
		public function get_logger() {
			if(empty($this->logger)) {
				$this->logger = new Logger(static::$plugin_slug);
				$this->logger->set_debug_mode($this->debug_mode());
			}
			return $this->logger;
		}

		/**
		 * Returns the URL to use to check for plugin updates.
		 *
		 * @param string plugin_slug The plugin slug.
		 * @return string
		 */
		protected function get_update_url($plugin_slug) {
			return 'https://wpupdate.aelia.co?action=get_metadata&slug=' . $plugin_slug;
		}

		/**
		 * Checks for plugin updates.
		 */
		public function check_for_updates($plugin_file, $plugin_slug = null) {
			if(empty($plugin_slug)) {
				$plugin_slug = static::$plugin_slug;
			}

			// Debug
			//var_dump($this->path('vendor') . '/yahnis-elsts/plugin-update-checker/plugin-update-checker.php');die();

			//var_dump(
			//		$this->get_update_url($plugin_slug),
			//		$plugin_file,
			//		$plugin_slug
			//);die();

			require_once(WC_AeliaFoundationClasses::instance()->path('vendor') . '/yahnis-elsts/plugin-update-checker/plugin-update-checker.php');
			$update_checker = \Puc_v4_Factory::buildUpdateChecker(
					$this->get_update_url($plugin_slug),
					$plugin_file,
					$plugin_slug
			);
		}

		/**
		 * Returns global instance of WooCommerce.
		 *
		 * @return object The global instance of WooCommerce.
		 */
		protected function wc() {
			return wc();
		}

		/**
		 * Returns the session manager.
		 *
		 * @return Aelia_SessionManager The session manager instance.
		 */
		protected function session() {
			if(empty($this->_session)) {
				$this->_session = new Aelia_SessionManager();
			}
			return $this->_session;
		}

		/**
		 * Returns the instance of the Settings Controller used by the plugin.
		 *
		 * @return Aelia_Settings.
		 */
		public function settings_controller() {
			return $this->_settings_controller;
		}

		/**
		 * Returns the instance of the Messages Controller used by the plugin.
		 *
		 * @return Aelia_Messages.
		 */
		public function messages_controller() {
			return $this->_messages_controller;
		}

		/**
		 * Returns the instance of the plugin.
		 *
		 * @return Aelia_Plugin.
		 */
		public static function instance() {
			return $GLOBALS[static::$plugin_slug];
		}

		/**
		 * Returns the plugin path.
		 *
		 * @return string
		 */
		public static function plugin_path() {
			$reflection_class = new ReflectionClass(get_called_class());

			return dirname($reflection_class->getFileName());
		}

		/**
		 * Returns the Settings Controller used by the plugin.
		 *
		 * @return Aelia\WC\Settings.
		 */
		public static function settings() {
			return self::$_settings[static::$plugin_slug];
		}

		/**
		 * Returns the Messages Controller used by the plugin.
		 *
		 * @return Aelia\WC\Messages.
		 */
		public static function messages() {
			return self::instance()->messages_controller();
		}

		/**
		 * Retrieves an error message from the internal Messages object.
		 *
		 * @param mixed error_code The Error Code.
		 * @return string The Error Message corresponding to the specified Code.
		 */
		public function get_error_message($error_code) {
			return $this->_messages_controller->get_error_message($error_code);
		}

		/**
		 * Indicates if current visitor is a bot.
		 *
		 * @return bool
		 * @since 1.5.6.150402
		 */
		protected static function visitor_is_bot() {
			$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
			$bot_types = 'bot|crawl|slurp|spider';

			$result = !empty($user_agent) ? preg_match("/$bot_types/", $user_agent) > 0 : false;
			return apply_filters('wc_aelia_visitor_is_bot', $result);
		}

		/**
		 * Triggers an error displaying the message associated to an error code.
		 *
		 * @param mixed error_code The Error Code.
		 * @param int error_type The type of Error to raise.
		 * @param array error_args An array of arguments to pass to the vsprintf()
		 * function which will format the error message.
		 * @param bool show_backtrace Indicates if a backtrace should be displayed
		 * after the error message.
		 * @return string The formatted error message.
		 */
		public function trigger_error($error_code, $error_type = E_USER_NOTICE, array $error_args = array(), $show_backtrace = false) {
			$error_message = $this->get_error_message($error_code);

			$message = vsprintf($error_message, $error_args);
			if($show_backtrace) {
				$e = new Exception();
				$backtrace = $e->getTraceAsString();
				$message .= " \n" . $backtrace;
			}

			return trigger_error($message, $error_type);
		}

			/**
		 * Sets the hook handlers for WC and WordPress.
		 */
		protected function set_hooks() {
			add_action('init', array($this, 'wordpress_loaded'));
			add_action('admin_init', array($this, 'run_updates'));

			// Called after all plugins have loaded
			add_action('plugins_loaded', array($this, 'plugins_loaded'));
			add_action('woocommerce_init', array($this, 'woocommerce_loaded'), 1);

			add_action('admin_enqueue_scripts', array($this, 'load_admin_scripts'));
			add_action('wp_enqueue_scripts', array($this, 'load_frontend_scripts'));

			// Register Widgets
			add_action('widgets_init', array($this, 'register_widgets'));

			// Ajax
			$ajax_action = $this->ajax_action();
			if(!empty($ajax_action)) {
				add_action('wp_ajax_' . $ajax_action, array($this, 'aelia_ajax_request'));
			}

			$nopriv_ajax_action = $this->nopriv_ajax_action();
			if(!empty($nopriv_ajax_action)) {
				add_action('wp_ajax_nopriv_' . $nopriv_ajax_action, array($this, 'aelia_ajax_request'));
			}

			// Automatic updates
			add_filter('wc_aelia_afc_register_plugins_to_update', array($this, 'wc_aelia_afc_register_plugins_to_update'), 10, 1);
		}

		/**
		 * Returns the full path corresponding to the specified key.
		 *
		 * @param key The path key.
		 * @return string
		 */
		public function path($key) {
			return isset($this->paths[$key]) ? $this->paths[$key] : '';
		}

		/**
		 * Builds and stores the paths used by the plugin.
		 */
		protected function set_paths() {
			$this->paths['plugin'] = WP_PLUGIN_DIR . '/' . $this->plugin_dir()  . '/src';
			$this->paths['languages'] = WP_PLUGIN_DIR . '/' . $this->plugin_dir()  . '/languages';
			$this->paths['languages_rel'] = $this->plugin_dir()  . '/languages';
			$this->paths['lib'] = $this->path('plugin') . '/lib';
			$this->paths['views'] = $this->path('plugin') . '/views';
			$this->paths['admin_views'] = $this->path('views') . '/admin';
			$this->paths['classes'] = $this->path('lib') . '/classes';
			$this->paths['widgets'] = $this->path('classes') . '/widgets';
			$this->paths['vendor'] = $this->path('plugin') . '/vendor';

			$this->paths['design'] = $this->path('plugin') . '/design';
			$this->paths['css'] = $this->path('design') . '/css';
			$this->paths['images'] = $this->path('design') . '/images';

			$this->paths['js'] = $this->path('plugin') . '/js';
			$this->paths['js_admin'] = $this->path('js') . '/admin';
			$this->paths['js_frontend'] = $this->path('js') . '/frontend';
		}

		/**
		 * Builds and stores the URLs used by the plugin.
		 */
		protected function set_urls() {
			$this->urls['plugin'] = plugins_url() . '/' . $this->plugin_dir() . '/src';

			$this->urls['design'] = $this->url('plugin') . '/design';
			$this->urls['css'] = $this->url('design') . '/css';
			$this->urls['images'] = $this->url('design') . '/images';
			$this->urls['js'] = $this->url('plugin') . '/js';
			$this->urls['js_admin'] = $this->url('js') . '/admin';
			$this->urls['js_frontend'] = $this->url('js') . '/frontend';
		}

		/**
		 * Returns the URL corresponding to the specified key.
		 *
		 * @param key The URL key.
		 * @return string
		 */
		public function url($key) {
			return isset($this->urls[$key]) ? $this->urls[$key] : '';
		}

		/**
		 * Returns the directory in which the plugin is stored. Only the base name of
		 * the directory is returned (i.e. without path).
		 *
		 * @return string
		 */
		public function plugin_dir() {
			if(empty($this->plugin_directory)) {
				$reflector = new ReflectionClass($this);
				$this->plugin_directory = basename(dirname(dirname($reflector->getFileName())));
			}

			return $this->plugin_directory;
		}

		/**
		 * Constructor.
		 *
		 * @param Aelia\WC\Settings settings_controller The controller that will handle
		 * the plugin settings.
		 * @param Aelia\WC\Messages messages_controller The controller that will handle
		 * the messages produced by the plugin.
		 */
		public function __construct($settings_controller = null, $messages_controller = null) {
			$this->_settings_controller = $settings_controller;
			$this->_messages_controller = empty($messages_controller) ? new Messages : $messages_controller;

			// Set plugin's paths
			$this->set_paths();
			// Set plugin's URLs
			$this->set_urls();

			// Set all required hooks
			$this->set_hooks();

			// indicates we are running the admin
			if(is_admin()) {
				// ...
			}

			// indicates we are being served over ssl
			if(is_ssl()) {
				// ...
			}

			// Store the settings controller in a cache, for better performance
			// @since 1.6.10.151105
			if(!is_array(self::$_settings)) {
				self::$_settings = array();
			}
			self::$_settings[static::$plugin_slug] = $settings_controller;

			// Set schedules, if needed
			$this->set_cron_schedules();
		}

		/**
		 * Run the updates required by the plugin. This method runs at every load, but
		 * the updates are executed only once. This allows the plugin to run the
		 * updates automatically, without requiring deactivation and rectivation.
		 *
		 * @return bool
		 */
		public function run_updates() {
			// Run updates only when in Admin area. This should occur automatically
			// when plugin is activated, since it's done in the Admin area. Updates
			// are also NOT executed during Ajax calls
			if(!is_admin() || self::doing_ajax()) {
				return;
			}

			$installer_class = get_class($this) . '_Install';
			if(!class_exists($installer_class)) {
				return;
			}

			$installer = new $installer_class();
			return $installer->update(static::$plugin_slug, static::$version);
		}

		/**
		 * Returns an instance of the class. This method should be implemented by
		 * descendant classes to return a pre-configured instance of the plugin class,
		 * complete with the appropriate settings controller.
		 *
		 * @return Aelia\WC\Aelia_Plugin
		 * @throws Aelia\WC\NotImplementedException
		 */
		public static function factory() {
			throw new NotImplementedException();
		}

		/**
		 * Take care of anything that needs to be done as soon as WordPress finished
		 * loading.
		 */
		public function wordpress_loaded() {
			if(!is_admin()) {
				$this->register_common_frontend_scripts();
			}
		}

		/**
		 * Performs operation when all plugins have been loaded.
		 */
		public function plugins_loaded() {
			load_plugin_textdomain(static::$text_domain, false, $this->path('languages_rel') . '/');
		}

		/**
		 * Performs operation when woocommerce has been loaded.
		 */
		public function woocommerce_loaded() {
			// To be implemented by descendant classes
		}

		/**
		 * Registers all the Widgets used by the plugin.
		 */
		public function register_widgets() {
			// Register the required widgets
			//$this->register_widget('Aelia\WC\Template_Widget');
		}

		/**
		 * Determines if one of plugin's admin pages is being rendered. Override it
		 * if plugin implements pages in the Admin section.
		 *
		 * @return bool
		 */
		protected function rendering_plugin_admin_page() {
			return false;
		}

		/**
		 * Registers the script and style files required in the backend (even outside
		 * of plugin's pages). Extend in descendant plugins.
		 */
		protected function register_common_admin_scripts() {
			// Dummy
		}

		/**
		 * Registers the script and style files needed by the admin pages of the
		 * plugin. Extend in descendant plugins.
		 */
		protected function register_plugin_admin_scripts() {
			// Admin scripts
			wp_register_script(static::$plugin_slug . '-admin',
												 $this->url('plugin') . '/js/admin/admin.js',
												 array('jquery'),
												 null,
												 false);
			// Admin styles
			wp_register_style(static::$plugin_slug . '-admin',
												$this->url('plugin') . '/design/css/admin.css',
												array(),
												null,
												'all');
		}

		/**
		 * Registers the script and style files required in the frontend (even outside
		 * of plugin's pages).
		 */
		protected function register_common_frontend_scripts() {
			// Scripts
			wp_register_script(static::$plugin_slug . '-frontend',
												 $this->url('plugin') . '/js/frontend/frontend.js',
												 array('jquery'),
												 null,
												 true);
			// Styles
			wp_register_style(static::$plugin_slug . '-frontend',
												$this->url('plugin') . '/design/css/frontend.css',
												array(),
												null,
												'all');
		}

		/**
		 * Loads Styles and JavaScript for the Admin pages.
		 */
		public function load_admin_scripts() {
			// Register common JS for the backend
			$this->register_common_admin_scripts();
			if($this->rendering_plugin_admin_page()) {
				// Load Admin scripts only on plugin settings page
				$this->register_plugin_admin_scripts();

				// Styles - Enqueue styles required for plugin Admin page
				wp_enqueue_style(static::$plugin_slug . '-admin');

				// JavaScript - Enqueue scripts required for plugin Admin page
				// Enqueue the required Admin scripts
				wp_enqueue_script(static::$plugin_slug . '-admin');
			}
		}


		/**
		 * Loads Styles and JavaScript for the frontend. Extend as needed in
		 * descendant classes.
		 */
		public function load_frontend_scripts() {
			// Enqueue the required Frontend stylesheets
			//wp_enqueue_style(static::$plugin_slug . '-frontend');

			// JavaScript
			//wp_enqueue_script(static::$plugin_slug . '-frontend');
		}

		/**
		 * Returns the full path and file name of the specified template, if such file
		 * exists.
		 *
		 * @param string template_name The name of the template.
		 * @return string
		 */
		public function get_template_file($template_name) {
			$template = '';

			/* Look for the following:
			 * - yourtheme/{plugin_slug}-{template_name}.php
			 * - yourtheme/{plugin_slug}/{template_name}.php
			 */
			$template = locate_template(array(
				static::$plugin_slug . "-{$template_name}.php",
				static::$plugin_slug . '/' . "{$template_name}.php"
			));

			// If template could not be found, get default one
			if(empty($template)) {
				$default_template_file = $this->path('views') . '/' . "{$template_name}.php";

				if(file_exists($default_template_file)) {
					$template = $default_template_file;
				}
			}

			// If template does not exist, trigger a warning to inform the site administrator
			if(empty($template)) {
				$this->trigger_error(Messages::ERR_INVALID_TEMPLATE,
														 E_USER_WARNING,
														 array(static::$plugin_slug, $template_name));
			}

			return $template;
		}

		/**
		 * Setup function. Called when plugin is enabled.
		 */
		public function setup() {
		}

		/**
		 * Cleanup function. Called when plugin is uninstalled.
		 */
		public static function cleanup() {
			if(!defined('WP_UNINSTALL_PLUGIN')) {
				return;
			}
		}

		/**
		 * Registers a widget class.
		 *
		 * @param string widget_class The widget class to register.
		 * @param bool stop_on_error Indicates if the function should raise an error
		 * if the Widget Class doesn't exist or cannot be loaded.
		 * @return bool True, if the Widget was registered correctly, False otherwise.
		 */
		protected function register_widget($widget_class, $stop_on_error = true) {
			if(!class_exists($widget_class)) {
				if($stop_on_error === true) {
					$this->trigger_error(\Aelia\WC\Messages::ERR_INVALID_WIDGET_CLASS,
															 E_USER_WARNING, array($widget_class));
				}
				return false;
			}
			register_widget($widget_class);

			return true;
		}

		/**
		 * Indicates if we are processing an Ajax call.
		 *
		 * @return bool
		 */
		public static function doing_ajax() {
			return defined('DOING_AJAX') && DOING_AJAX;
		}

		/**
		 * Indicates if we are rendering a frontend page. In case of Ajax calls, this
		 * method checks if the call was made in the backend by looking at its
		 * arguments.
		 *
		 * @return bool
		 * @since 1.6.3.15815
		 */
		public static function is_frontend() {
			$ajax_action = isset($_REQUEST['action']) ? strtolower($_REQUEST['action']) : '';

			return !is_admin() || (
				self::doing_ajax() &&
				!in_array($ajax_action, array(
					// The following actions are called in the backend. If they are used, then
					// we are in the backend, regardless of the fact that we are using Ajax
					'woocommerce_load_variations',
					'woocommerce_add_variation',
					'woocommerce_remove_variations',
					'woocommerce_save_variations',
					'woocommerce_link_all_variations',
					'woocommerce_bulk_edit_variations',
					'woocommerce_json_search_products_and_variations',
				)));
		}

		/**
		 * Indicates if we are preparing a report. The logic to determine which
		 * report is being rendered was copied from WC_Admin_Reports class.
		 *
		 * @param mixed reports The report ID, or an array of report IDs. The function
		 * will return true if we are rendering any of them.
		 * @return bool
		 * @since 1.5.19.150625
		 * @see WC_Admin_Reports::output()
		 */
		public static function doing_reports($reports = array()) {
			// Check if we are rendering a report page
			if(is_admin() && isset($_GET['page']) && ($_GET['page'] === 'wc-reports')) {
				/* If the "reports" argument is empty, we are just checking if we are
				 * rendering ANY report page
				 */
				if(empty($reports)) {
					return true;
				}

				if(!is_array($reports)) {
					$reports = array($reports);
				}

				$available_reports = WC_Admin_Reports::get_reports();
				$first_tab = array_keys($available_reports);
				$current_tab = ! empty($_GET['tab']) ? sanitize_title($_GET['tab']) : $first_tab[0];
				$current_report = isset($_GET['report']) ? sanitize_title($_GET['report']) : current(array_keys($available_reports[ $current_tab ]['reports']));

				return empty($reports) || in_array($current_report, $reports);
			}
			return false;
		}

		/**
		 * Indicates if we are on the "order edit" page.
		 *
		 * @return int|false The ID of the order being modified, or false if we are
		 * on another page.
		 * @since 1.5.19.150625
		 */
		public static function editing_order() {
			if(!empty($_GET['action']) && ($_GET['action'] == 'edit') && !empty($_GET['post'])) {
				global $post;
				if(!empty($post) && ($post->post_type == 'shop_order')) {
					return $post->ID;
				}
			}
			return false;
		}

		/**
		 * Sets the Cron schedules required by the plugin.
		 *
		 * @since 1.6.0.150724
		 */
		protected function set_cron_schedules() {
			// To be implemented by descendant class
		}

		/**
		 * Returns the Nonce ID used by the Ajax call handled by the plugin.
		 *
		 * @since 1.7.3.160531
		 */
		protected function ajax_nonce_id() {
			return static::$plugin_slug . '-nonce';
		}

		/**
		 * Returns the action used to route Ajax calls to this plugin.
		 *
		 * @return string
		 * @since 1.7.3.160531
		 */
		protected function ajax_action() {
			return static::$ajax_action;
		}

		/**
		 * Returns the action used to route Ajax calls to this plugin for anonymous
		 * users (i.e "nopriv" Ajax calls).
		 *
		 * @return string
		 * @since 1.7.3.160531
		 */
		protected function nopriv_ajax_action() {
			return $this->ajax_action();
		}

		/**
		 * Returns a list of valid Ajax commands.
		 *
		 * @param string command The command to validate.
		 * @return bool
		 * @since 1.7.3.160531
		 */
		protected function get_valid_ajax_commands() {
			return array();
		}

		/**
		 * Indicates if an Ajax command is valid.
		 *
		 * @param string command The command to validate.
		 * @return bool
		 * @since 1.7.3.160531
		 */
		protected function is_valid_ajax_command($command) {
			$ajax_callbacks = $this->get_valid_ajax_commands();
			return isset($ajax_callbacks[$command]) && is_callable($ajax_callbacks[$command]);
		}

		/**
		 * Returns the callback associated to an Ajax command.
		 *
		 * @param string ajax_command The ajax command.
		 * @return callable|null The callback to invoke, or null if the command is not
		 * valid.
		 * @since 1.7.3.160531
		 */
		protected function get_ajax_callback($ajax_command) {
			if($this->is_valid_ajax_command($ajax_command)) {
				$ajax_callbacks = $this->get_valid_ajax_commands();
				return $ajax_callbacks[$ajax_command];
			}
			return null;
		}

		/**
		 * Validates an Ajax request.
		 *
		 * @return int Zero if the Ajax request is valid, otherwise an error number.
		 * @since 1.7.3.160531
		 */
		protected function validate_ajax_request() {
			$result = Definitions::RES_OK;
			// Verify nonce
			if(apply_filters('wc_aelia_afc_should_validate_ajax_nonce', true)) {
				if(!check_ajax_referer($this->ajax_nonce_id(), '_ajax_nonce', false)) {
					$result = Definitions::ERR_AJAX_INVALID_REFERER;
				}
			}

			// Additional check to verify that it's a valid Ajax request
			//if(empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
			//	 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
			//	$result = Definitions::ERR_INVALID_AJAX_REQUEST;
			//}

			// Check that a command was passed
			if($result == Definitions::RES_OK) {
				if(empty($_REQUEST[Definitions::ARG_AJAX_COMMAND])) {
					$result = Definitions::ERR_AJAX_COMMAND_MISSING;
				}
			}

			// Check that the command is valid
			if($result == Definitions::RES_OK) {
				if(empty($_REQUEST[Definitions::ARG_AJAX_COMMAND]) ||
					 !$this->is_valid_ajax_command($_REQUEST[Definitions::ARG_AJAX_COMMAND])) {
					$result = Definitions::ERR_AJAX_INVALID_COMMAND;
				}
			}

			// Allow further validation
			$result = apply_filters('wc_aelia_afc_validate_ajax_request', $result);

			if($result != Definitions::RES_OK) {
				status_header(400);
				wp_send_json(apply_filters('wc_aelia_afc_invalid_request_response', array(
					'result' => $result,
				)));
			}
			return $result;
		}

		/**
		 * Handles Ajax calls. This method is a central handler for all Ajax calls. It
		 * performs some validation before handing over the actual execution to
		 * the appropriate callback.
		 *
		 * @since 1.7.3.160531
		 */
		public function aelia_ajax_request() {
			if($this->validate_ajax_request() != Definitions::RES_OK) {
				exit;
			}

			// Get the callback to use for the Ajax request
			$callback = $this->get_ajax_callback($_REQUEST[Definitions::ARG_AJAX_COMMAND]);

			$result = call_user_func($callback);
			wp_send_json($result);
		}

		/**
		 * Allows the plugin to register itself for automatic updates.
		 *
		 * @param array The array of the plugins to update, structured as follows:
		 * array(
		 *   'free' => <Array of free plugins>,
		 *   'premium' => <Array of premium plugins, which require licence activation>,
		 * )
		 * @return array The array of plugins to update, with the details of this
		 * plugin added to it.
		 * @since 1.7.0.150818
		 */
		public function wc_aelia_afc_register_plugins_to_update(array $plugins_to_update) {
			return $plugins_to_update;
		}

		/**
		 * Sets the the folder and file name of the main plugin file.
		 *
		 * WHY
		 * The "main plugin file" information cannot always be retrieved with __FILE__.
		 * The main plugin file could actually be just a loader, which takes care of
		 * loading the actual plugin class. A call to __FILE__ from this plugin class
		 * could, therefore, return the wrong information. By allowing the loader to
		 * set the plugin file, we can always get the correct information.
		 *
		 * NOTE
		 * If the main_plugin_file property is left empty, the value of __FILE__ is
		 * returned by default.
		 *
		 * @param string plugin_file A string indicating the main plugin file.
		 * @since 1.7.1.150824
		 */
		public function set_main_plugin_file($plugin_file) {
			//$this->main_plugin_file = untrailingslashit(plugin_basename($plugin_file));
			$this->main_plugin_file = $plugin_file;
		}

		/**
		 * gets the the folder and file name of the main plugin file.
		 *
		 * @param bool relative_path_only Indicates if the method should return only
		 * the relative path of plugin's file (i.e. my-plugin-folder/my-plugin-file.php).
		 * If set to false, the method will return the full path to the file.
		 *
		 * @since 1.8.4.170118
		 */
		public function get_plugin_file($relative_path_only = false) {
			$main_plugin_file = $this->main_plugin_file;

			if(empty($main_plugin_file)) {
				$main_plugin_file = __FILE__;

				$this->get_logger()->info(__('Main plugin file not specified. Taking current file ' .
																		 'as a default.', Definitions::TEXT_DOMAIN),
																	array(
																		'Plugin Slug' => static::$plugin_slug,
																		'Plugin File' => $main_plugin_file,
																	));
			}

			if($relative_path_only) {
				// Return the last folder and the file name
				return basename(dirname($main_plugin_file)) . '/' . basename($main_plugin_file) ;
			}
			return $main_plugin_file;
		}

		/**
		 * Returns the plugin's slug.
		 *
		 * @param bool for_updates If set to true, it will return the plugin's slug
		 * that should be used to check for updates. This allows plugins to have a
		 * different slug when checking for updates.
		 *
		 * @since 1.8.4.170118
		 */
		public function get_slug($for_updates = false) {
			$plugin_class = get_class($this);

			if($for_updates) {
				// In case the plugin uses a different slug from the one registered in the
				// update server, it can set the "slug_for_update_check" property to indicate
				// it
				return isset($plugin_class::$slug_for_update_check) ? $plugin_class::$slug_for_update_check : $plugin_class::$plugin_slug;
			}
			return $plugin_class::$plugin_slug;
		}

		/**
		 * Indicates if the plugin has been configured. This method must be
		 * overridden by descendant classes.
		 *
		 * @return bool
		 * @since 1.8.2.161216
		 */
		public function is_plugin_configured() {
			return true;
		}

		/**
		 * Indicates if the plugin's debug mode is active. This method must be
		 * overridden by descendant classes.
		 *
		 * @return bool
		 * @since 1.8.2.161216
		 */
		public function debug_mode() {
			return false;
		}
	}
}
