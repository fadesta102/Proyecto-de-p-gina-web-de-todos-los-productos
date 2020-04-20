<?php
namespace Aelia\WC;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Implements a base class to store and handle the messages returned by the
 * plugin. This class is used to extend the basic functionalities provided by
 * standard WP_Error class.
 */
class Messages {
	const DEFAULT_TEXTDOMAIN = 'wc-aelia';

	// Result constants
	const RES_OK = 0;
	const ERR_FILE_NOT_FOUND = 100;
	const ERR_NOT_IMPLEMENTED = 101;
	const ERR_INVALID_TEMPLATE = 102;
	const ERR_INVALID_WIDGET_CLASS = 103;

	// @var bool Indicates if the class has already been initialised
	protected static $initialised = false;

	// @var WP_Error Holds the error messages registered by the plugin
	protected $wp_error;

	// @var string The text domain used by the class
	protected $text_domain = self::DEFAULT_TEXTDOMAIN;

	// @var string A list of admin messages to display
	protected static $admin_messages = array();

	// @var array A list of the messages dismissed by the user. Used for caching.
	protected static $dismissed_messages = array();

	public function __construct($text_domain = self::DEFAULT_TEXTDOMAIN) {
		$this->text_domain = $text_domain;
		$this->wp_error = new \WP_Error();
		$this->load_error_messages();
	}

	/**
	 * Loads all the messages used by the plugin. This class should be
	 * extended during implementation, to add all error messages used by
	 * the plugin.
	 */
	public function load_messages() {
		$this->add_message(self::ERR_FILE_NOT_FOUND, __('File not found: "%s".', $this->text_domain));
		$this->add_message(self::ERR_NOT_IMPLEMENTED, __('Not implemented.', $this->text_domain));
		$this->add_error_message(self::ERR_INVALID_TEMPLATE,
														 __('Rendering - Requested template could not be found in either plugin\'s ' .
																'folders, nor in your theme. Plugin slug: "%s". Template name: "%s".',
																$this->text_domain));
		$this->add_error_message(self::ERR_INVALID_WIDGET_CLASS,
														 __('Invalid widget class: "%s".',
																$this->text_domain));

		// TODO Add here all the error messages used by the plugin
	}

	/**
	 * Registers an error message in the internal wp_error object.
	 *
	 * @param mixed $message_code The message code.
	 * @param string The message corresponding to the specified code.
	 */
	public function add_message($message_code, $message) {
		$this->wp_error->add($message_code, $message);
	}

	/**
	 * Retrieves an error message from the internal wp_error object.
	 *
	 * @param mixed $message_code The message code.
	 * @return string The message corresponding to the specified code.
	 */
	public function get_message($message_code) {
		return $this->wp_error->get_error_message($message_code);
	}

	/**
	 * Calls Aelia\WC\Messages::load_messages(). Implemented for backward
	 * compatibility.
	 */
	public function load_error_messages() {
		$this->load_messages();
	}

	/**
	 * Calls Aelia\WC\Messages::add_message(). Implemented for backward
	 * compatibility.
	 */
	public function add_error_message($error_code, $error_message) {
		$this->add_message($error_code, $error_message);
	}

	/**
	 * Calls Aelia\WC\Messages::get_message(). Implemented for backward
	 * compatibility.
	 */
	public function get_error_message($error_code) {
		return $this->get_message($error_code);
	}

	/**
	 * Initialises the message system.
	 *
	 * @since 1.6.1.150728
	 */
	public static function init() {
		// Prevent multiple initialisations
		if(self::$initialised) {
			return;
		}
		add_action('admin_notices', array(__CLASS__, 'admin_notices'));

		// Ajax hooks
		if(WC_AeliaFoundationClasses::doing_ajax()) {
			add_action('wp_ajax_aelia_dismiss_message', array(__CLASS__, 'wp_ajax_aelia_dismiss_message'));
		}
		self::$initialised = true;
	}

	/**
	 * Adds an admin message to the list.
	 *
	 * @param string message The message.
	 * @param array params An array of parameters used to create the message.
	 * @since 1.6.9.151103
	 */
	public static function admin_message($message, array $params = array()) {
		$message = Admin_Message::factory($message, $params);

		if(!isset(self::$admin_messages[$message->sender_id])) {
			self::$admin_messages[$message->sender_id] = array();
		}
		self::$admin_messages[$message->sender_id][] = $message;
	}

	/**
	 * Displays all stored admin messages.
	 *
	 * @since 1.6.1.150728
	 */
	public static function admin_notices() {
		if(empty(self::$admin_messages)) {
			return;
		}

		// Load the list of dismissed messages
		$dismissed_messages = self::get_dismissed_messages();

		foreach(self::$admin_messages as $sender_id => $messages) {
			foreach($messages as $message) {
				// Skip messages flagged as "dismissable" which have already been dismissed
				$message_id = $message->get_message_id();
				if($message->dismissable &&
					 isset($dismissed_messages[$message_id])) {
					continue;
				}

				$message->render(true);
			}
		}
	}

	/**
	 * Validates Ajax requests.
	 *
	 * @param string ajax_referer_key Indicates which URL argument contains the
	 * Ajax nonce.
	 * @return bool
	 * @since 1.6.9.151103
	 */
	protected static function validate_ajax_request($ajax_nonce_key) {
		$result = true;

		// User must be logged in to dismiss a message
		if($result && !is_user_logged_in()) {
			$message = 'HTTP/1.1 400 Bad Request';
			header($message, true, 400);
			$result = false;
		}

		// Handle invalid requests (e.g. a request missing the "aelia_msg_id" argument)
		if($result && empty($_REQUEST[Definitions::ARG_MESSAGE_ID])) {
			$message = 'HTTP/1.1 400 Bad Request';
			header($message, true, 400);
			$result = false;
		}

		if($result && !check_ajax_referer($ajax_nonce_key, '_ajax_nonce', false)) {
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
	}

	/**
	 * Handles the Ajax request to hide a message.
	 *
	 * @since 1.6.9.151103
	 */
	public static function wp_ajax_aelia_dismiss_message() {
		$message_id = isset($_REQUEST[Definitions::ARG_MESSAGE_ID]) ? $_REQUEST[Definitions::ARG_MESSAGE_ID] : '';
		self::validate_ajax_request('aelia-message-dismiss-' . $message_id);

		$result = array(
			'status' => true,
			'messages' => array(),
		);

		$result['status'] = self::dismiss_message($message_id);
		if(!$result['status']) {
			$result['messages'][] = sprintf(__('Could not track message dismissal to ' .
																				 '"aelia_messages" table. User ID: "%s". ' .
																				 'Message ID: "%s".',
																				 WC_AeliaFoundationClasses::$text_domain),
																			get_current_user_id(),
																			$message_id);
			global $wpdb;
			$logger = WC_AeliaFoundationClasses::instance()->get_logger();

			// Log the details of the failed dismissal, for easier debugging
			// @since 2.0.7.190613
			$logger->error(__('Could not track message dismissal to "aelia_messages" table.', WC_AeliaFoundationClasses::$text_domain), array(
				'User ID' => get_current_user_id(),
				'Message ID' => $message_id,
				'WPDB Last Query' => $wpdb->last_query,
				'WPDB Last Result' => $wpdb->last_result,
				'WPDB Last Error' => $wpdb->last_error,
			));
		}
		else {
			$result['messages'][] = sprintf(__('Message dismissed successfully. User ID: ' .
																				 '"%s". Message ID: "%s". Dismissal record ID: "%s".',
																				 WC_AeliaFoundationClasses::$text_domain),
																			get_current_user_id(),
																			$message_id,
																			$result['status']);
		}
		wp_send_json($result);
	}

	/**
	 * Dismissed a displayed message.
	 *
	 * @param string message_id The unique message ID.
	 * @param int user_id The ID of the user for whom the message should be
	 * dismissed. If empty, current user ID is taken.
	 * @return int|false The ID of the record that tracks the dismissal of the
	 * message, or false if the operation failed.
	 * @since 1.6.9.151103
	 */
	protected static function dismiss_message($message_id, $user_id = null) {
		global $wpdb;

		if(empty($user_id)) {
			$user_id = get_current_user_id();
		}

		// Delete the transient storing the list of dismissed messages, as it's no
		// longer valid
		delete_transient('aelia_dismissed_messages_' . (string)$user_id);

		// Store the dismissal of the message to the database
		return $wpdb->replace($wpdb->prefix . 'aelia_dismissed_messages', array(
			'user_id' => $user_id,
			'message_id' => $message_id,
			'date_updated' => date('YmdHis'),
		), array('%d', '%s', '%s'));
	}

	/**
	 * Gets the list of the messages dismissed by current user. This method loads
	 * all dismissed messages (they should be a few dozen at most) and caches them
	 * into memory for faster access.
	 *
	 * @param int user_id The ID of the user for whom the message should be
	 * dismissed. If empty, current user ID is taken.
	 * @return array An array containing the list of the dismissed messages.
	 * @since 1.6.9.151103
	 */
	protected static function get_dismissed_messages($user_id = null) {
		if(empty(self::$dismissed_messages)) {
			if(empty($user_id)) {
				$user_id = get_current_user_id();
			}
			// Use data stored in transient, if available
			$transient_key = 'aelia_dismissed_messages_' . (string)$user_id;
			self::$dismissed_messages = get_transient($transient_key);

			// If the list is empty at this point, it means that it was not stored in
			// the transients, or that it's expired. In such case, the data has to be
			// retrieved from the database
			if(self::$dismissed_messages === false) {
				global $wpdb;
				$SQL = "
					SELECT
						ADM.user_id
						,ADM.message_id
					FROM
						{$wpdb->prefix}aelia_dismissed_messages ADM
					WHERE
						(ADM.user_id = %d)
				";
				$dataset = $wpdb->get_results($wpdb->prepare($SQL, $user_id));
				if(is_array($dataset)) {
					foreach($dataset as $message) {
						self::$dismissed_messages[$message->message_id] = $message;
					}
				}

				// Use transients to cache the list of dismissed messages, for better
				// performance
				set_transient($transient_key, self::$dismissed_messages, HOUR_IN_SECONDS);
			}
		}
		// Debug
		//var_dump(self::$dismissed_messages);
		return self::$dismissed_messages;
	}
}
// Initialise the messages system
Messages::init();
