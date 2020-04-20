<?php
namespace Aelia\WC\AFC;
if(!defined('ABSPATH')) exit; // Exit ifaccessed directly

/**
 * Handles the settings for the Aelia Foundation Classes plugin and provides
 * convenience methods to read and write them.`
 *
 * @since 1.9.4.170202
 */
class Settings extends \Aelia\WC\Settings {
	public static $id = 'wc_aelia_foundation_classes';
	protected static $license_settings_id;
	protected static $text_domain;

	/*** Settings Key ***/
	// @var string The key to identify plugin settings amongst WP options.
	const SETTINGS_KEY = 'wc-aelia-foundation-classes';

	/*** Settings fields ***/
	const FIELD_DEBUG_MODE = 'debug_mode';

	/**
	 * Returns the default settings for the plugin. Used mainly at first
	 * installation.
	 *
	 * @param string key If specified, method will return only the setting identified
	 * by the key.
	 * @param mixed default The default value to return if the setting requested
	 * via the "key" argument is not found.
	 * @return array|mixed The default settings, or the value of the specified
	 * setting.
	 *
	 * @see WC_Aelia_Settings:default_settings().
	 */
	public function default_settings($key = null, $default = null) {
		$upload_dir = wp_upload_dir();
		$default_options = array(
			self::FIELD_DEBUG_MODE => 'no',
		);

		if(empty($key)) {
			return $default_options;
		}
		else {
			return get_value($key, $default_options, $default);
		}
	}

	/**
	 * Validates the settings specified via the Options page.
	 *
	 * @param array settings An array of settings.
	 */
	public function validate_settings($settings) {
		// Debug
		//var_dump($settings);die();
		$this->validation_errors = array();
		$processed_settings = $this->current_settings();

		// Validate the settings posted via the $settings variable

		// Save settings if they passed validation
		if(empty($this->validation_errors)) {
			$processed_settings = array_merge($processed_settings, $settings);
		}
		else {
			$this->show_validation_errors();
		}

		// Return the array processing any additional functions filtered by this action.
		return apply_filters('wc_aelia_afc_settings', $processed_settings, $settings);
	}

	/**
	 * Class constructor.
	 */
	public function __construct($settings_key = self::SETTINGS_KEY,
															$text_domain = '',
															\Aelia\WC\Settings_Renderer $renderer = null) {
		$this->settings_key = $settings_key;

		self::$text_domain = $text_domain;
		// Legacy property
		$this->textdomain = $text_domain;

		$this->load();
		$this->set_hooks();
	}

	/**
	 * Sets the hooks required by the class.
	 */
	protected function set_hooks() {
		add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_page'), 9999);
		// Render sections
		// @since 1.9.8.171002
		add_action('woocommerce_sections_' . self::$id, array($this, 'render_sections'));
		// Plugin settings
		add_action('woocommerce_settings_' . self::$id, array($this, 'render_options_page'), 10);
		add_action('woocommerce_update_options_'. self::$id, array($this, 'update_settings'));
	}

	/**
	 * Factory method.
	 *
	 * @param string settings_key The key used to store and retrieve the plugin settings.
	 * @param string textdomain The text domain used for localisation.
	 * @param string renderer The renderer to use to generate the settings page.
	 * @return WC_Aelia_Settings.
	 */
	public static function factory($settings_key = self::SETTINGS_KEY,
																 $textdomain = '') {
		$class = get_called_class();
		$settings_manager = new $class($settings_key, $textdomain, $renderer);
		return $settings_manager;
	}

	/**
	 * Returns the settings for the "General" section.
	 *
	 * @param array settings An array of settings. The method will merge its data
	 * with the one found in this parameter.
	 * @return array An array of settings.
	 */
	protected function get_general_settings(array $settings = array()) {
		$settings = $settings + array(
			'general_settings_title_' . self::$id => array(
				'name' => __('General', self::$text_domain),
				'type' => 'title',
				'desc' => '',
			),
			//self::FIELD_UPDATES_SERVER_PATH => array(
			//	'id' => self::$id . '_' . self::FIELD_UPDATES_SERVER_PATH,
			//	'name' => __('Server path', self::$text_domain),
			//	'type' => 'text',
			//	'desc' => __('Enter the path where the updates server will look for the packages ' .
			//							 '(ZIP files) and store its cache.', self::$text_domain) . ' ' .
			//						__('Files will be stored in the specified path, as follows: ', self::$text_domain) .
			//						'<ul class="paths">' .
			//							'<li>' .
			//								'<span class="path_description">' .
			//									'<strong>' . '{server_path}/packages' . '</strong>: ' .
			//									__('This folder will contain the packages (ZIP files).', self::$text_domain) .
			//								'</span>' .
			//							'</li>' .
			//							'<li>' .
			//								'<span class="path_description">' .
			//									'<strong>' . '{server_path}/cache' . '</strong>: ' .
			//									__('This folder will contain the cache files generated by the update server.', self::$text_domain) .
			//								'</span>' .
			//							'</li>' .
			//						'</ul>' .
			//						'<span><strong>' . __('Important', self::$text_domain) . ':</strong> ' .
			//						__('Please make sure that all the above paths are writable.', $this->textdomain) .
			//						'</span>',
			//	'class' => 'aelia_path writable',
			//	'default' => $this->get_default_updates_server_path(),
			//	'autoload' => false,
			//),
			'general_settings_' . self::$id => array(
				'type' => 'sectionend',
			)
		);
		return $settings;
	}

	/**
	 * Returns the settings for the "Support" section.
	 *
	 * @param array settings An array of settings. The method will merge its data
	 * with the one found in this parameter.
	 * @return array An array of settings.
	 */
	protected function get_support_settings(array $settings) {
		$settings = $settings + array(
			'support_settings_title_' . self::$id => array(
				'name' => __('Support', self::$text_domain),
				'type' => 'title',
				'desc' => '',
			),
			self::FIELD_DEBUG_MODE => array(
				'id' => self::$id . '_' . self::FIELD_DEBUG_MODE,
				'name' => __('Debug mode', self::$text_domain),
				'type' => 'checkbox',
				'desc' => __('Enable debug mode.', self::$text_domain) . ' ' .
									__('When debug mode is enabled, the plugin will log additional ' .
										 'information about the operations it performs. The log file ' .
										 'will be located at', self::$text_domain) .
									' <code class="log_file_path">' .
									\Aelia\WC\Logger::get_log_file_name(\Aelia\WC\WC_AeliaFoundationClasses::$plugin_slug) .
									'</code>',
				'class' => 'afc_debug_mode',
				'default' => 'no',
				'autoload' => false,
			),
			'support_settings_' . self::$id => array(
				'type' => 'sectionend',
			)
		);
		return $settings;
	}

	/**
	 * Returns the array of the settings used by the plugin.
	 *
	 * @return array An array of settings.
	 */
	public function get_settings() {
		$settings = array();
		// TODO Add general settigs
		//$settings = $this->get_general_settings($settings);
		$settings = $this->get_support_settings($settings);

		return apply_filters('wc_aelia_afc_settings', $settings);
	}

	/**
	 * Adds a settings page to WooCommerce settings.
	 *
	 * @param array pages An array of settings pages.
	 * @return array An array of settings pages.
	 */
	public function add_settings_page($pages){
		$pages[self::$id] = __('Aelia', self::$text_domain);
		return $pages;
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 * @since 1.9.8.171002
	 */
	public function get_sections() {
		// Load settings sections
		$sections = apply_filters('woocommerce_get_sections_' . self::$id, array());

		// Add the "support" section at the end
		$sections['support'] = __('Support', self::$text_domain);

		return $sections;
	}

	/**
	 * Render sections.
	 *
	 * @since 1.9.8.171002
	 */
	public function render_sections() {
		global $current_section;

		$sections = $this->get_sections();

		if(empty($sections) || 1 === sizeof($sections)) {
			return;
		}

		// If no section was specified, default to the first one
		if(empty($current_section)) {
			reset($sections);
			$current_section = key($sections);
		}

		echo '<ul class="subsubsub">';

		$array_keys = array_keys($sections);

		foreach($sections as $id => $label) {
			echo '<li><a href="' . admin_url('admin.php?page=wc-settings&tab=' . self::$id . '&section=' . sanitize_title($id)) . '" class="' .($current_section == $id ? 'current' : '') . '">' . $label . '</a> ' . (end($array_keys) == $id ? '' : '|') . ' </li>';
		}

		echo '</ul><br class="clear" />';
	}

	/**
	 * Renders the settings page.
	 */
	public function render_options_page() {
		woocommerce_admin_fields($this->get_settings());
	}

	/**
	 * Updates the plugin settings.
	 */
	public function update_settings() {
		woocommerce_update_options($this->get_settings());
	}

	/**
	 * Loads the plugin settings.
	 */
	public function load() {
		if(empty($this->_current_settings)) {
			$this->_current_settings = array();

			foreach($this->get_settings() as $name => $params) {
				if(!isset($params['id'])) {
					continue;
				}
				$default = isset($params['default']) ? $params['default'] : null;

				$setting_key = str_replace(self::$id . '_', '', $params['id']);
				$this->_current_settings[$setting_key] = get_option($params['id'], $default);
			}
		}
		return $this->_current_settings;
	}

	/**
	 * Deletes the plugin settings.
	 */
	public function delete_settings() {
		foreach($this->get_settings() as $name => $params) {
			if(!isset($params['id'])) {
				continue;
			}
			delete_option($params['id']);
		}
	}

	/**
	 * Indicates if debug mode is active.
	 *
	 * @return bool
	 */
	public function debug_mode() {
		return ($this->current_settings(self::FIELD_DEBUG_MODE, 'no') == 'yes');
	}

	/*** Validation methods ***/
}
