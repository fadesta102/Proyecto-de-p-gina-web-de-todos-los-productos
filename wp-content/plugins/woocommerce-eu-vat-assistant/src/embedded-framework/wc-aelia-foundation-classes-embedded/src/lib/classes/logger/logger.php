<?php
namespace Aelia\WC;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use Monolog\Handler\StreamHandler;
use Monolog\Handler\LogglyHandler;
use Monolog\Handler\ChromePHPHandler;
use Monolog\Processor\ProcessIdProcessor;
use \Exception;

/**
 * Writes to the log used by the plugin.
 */
class Logger {
	// @var string The log id.
	public $log_id = '';
	// @var bool Indicates if debug mode is active.
	protected $_debug_mode = false;

	/**
	 * The logger used to store log messages.
	 *
	 * @var \Monolog\Logger
	 * @since 1.8.0.160728
	 */
	protected $logger;
	/**
	 * A list of handlers that will be used by Monolog to log messages.
	 *
	 * @var array
	 * @since 1.8.0.160728
	 */
	protected $log_handlers = array();

	/**
	 * Sets the log level for the logger.
	 *
	 * @param int log_level The new log level.
	 * @since 1.8.0.160728
	 */
	protected function set_log_level($log_level) {
		foreach($this->log_handlers as $log_handler) {
			$log_handler->setLevel($log_level);
		}
	}

	/**
	 * Sets the "debug mode" setting.
	 *
	 * @return bool
	 */
	public function set_debug_mode($debug_mode) {
		$this->_debug_mode = $debug_mode;

		if($debug_mode) {
			$log_level = \Monolog\Logger::DEBUG;

			$this->init_debug_log_handlers();
			$this->logger->info('Debug mode enabled', array(
				'Logger ID' => $this->log_id,
			));
		}
		else {
			$log_level = \Monolog\Logger::NOTICE;
		}
		$this->set_log_level($log_level);
	}

	/**
	 * Initialises additional loggers that should be used in debug mode.
	 *
	 * @since 1.8.4.170118
	 */
	protected function init_debug_log_handlers() {
		// The ChromePHPHandler seems to cause 404 errors, for some reason, and has
		// been disabled. Errors verified with WC 3.3.1 and WordPress 4.9.4.
		// @since 1.9.16.180213
		//$this->logger->pushHandler(new ChromePHPHandler(\Monolog\Logger::DEBUG));
	}

	/**
	 * Retrieves the "debug mode" setting.
	 *
	 * @return bool
	 */
	public function get_debug_mode() {
		return $this->_debug_mode;
	}

	/**
	 * Indicates if debug mode is active.
	 *
	 * @return bool
	 */
	protected function debug_mode() {
		if($this->_debug_mode === null) {
			$this->_debug_mode = $this->get_debug_mode();
		}
		return $this->_debug_mode;
	}

	/**
	 * Determines if WordPress maintenance mode is active.
	 *
	 * @return bool
	 */
	protected function maintenance_mode() {
		return file_exists(ABSPATH . '.maintenance') || defined('WP_INSTALLING');
	}

	/**
	 * Handles an exception occurred while trying to log a message.
	 *
	 * @param \Exception e The exception occurred while logging the message.
	 * @param string message The original message being logged.
	 * @since 1.9.9.171120
	 */
	protected function log_exception(\Exception $e, $message) {
		$msg = sprintf('[%1$s] - Unexpected exception occurred while logging a message. Original message: "%2$s". Exception: "%3$s".',
									 get_class($this),
									 $message,
									 $e->getMessage());
		$msg .= ' ';
		$msg .= 'This error could be due to a misconfiguration, or a conflict. Please report it to the Aelia Support Team.';
		trigger_error($msg, E_USER_WARNING);
	}

	/**
	 * Adds a message to the log.
	 *
	 * @param string message The message to log.
	 * @param bool is_debug_msg Indicates if the message should only be logged
	 * while debug mode is true.
	 */
	public function log($message, $is_debug_msg = true) {
		try {
			if($is_debug_msg) {
				return $this->logger->debug($message);
			}
			else {
				return $this->logger->notice($message);
			}
		}
		catch(\Exception $e) {
			$this->log_exception($e, $message);
		}
	}

	/**
	 * Adds a log record at the DEBUG level.
	 *
	 * This method allows for compatibility with common interfaces.
	 *
	 * @param  string  $message The log message
	 * @param  array   $context The log context
	 * @return Boolean Whether the record has been processed
	 * @see Monolog\Logger\debug()
	 * @since 1.8.0.160728
	 */
	public function debug($message, array $context = array()) {
		try {
			$this->logger->debug($message, $context);
		}
		catch(\Exception $e) {
			$this->log_exception($e, $message);
		}
	}

	/**
	 * Adds a log record at the INFO level.
	 *
	 * This method allows for compatibility with common interfaces.
	 *
	 * @param  string  $message The log message
	 * @param  array   $context The log context
	 * @return Boolean Whether the record has been processed
	 * @see Monolog\Logger\info()
	 * @since 1.8.0.160728
	 */
	public function info($message, array $context = array())	{
		try {
			$this->logger->info($message, $context);
		}
		catch(\Exception $e) {
			$this->log_exception($e, $message);
		}
	}

	/**
	 * Adds a log record at the NOTICE level.
	 *
	 * This method allows for compatibility with common interfaces.
	 *
	 * @param  string  $message The log message
	 * @param  array   $context The log context
	 * @return Boolean Whether the record has been processed
	 * @see Monolog\Logger\notice()
	 * @since 1.8.0.160728
	 */
	public function notice($message, array $context = array()) {
		try {
			$this->logger->notice($message, $context);
		}
		catch(\Exception $e) {
			$this->log_exception($e, $message);
		}
	}

	/**
	 * Adds a log record at the WARNING level.
	 *
	 * This method allows for compatibility with common interfaces.
	 *
	 * @param  string  $message The log message
	 * @param  array   $context The log context
	 * @return Boolean Whether the record has been processed
	 * @see Monolog\Logger\warning()
	 * @since 1.8.0.160728
	 */
	public function warning($message, array $context = array()) {
		try {
			$this->logger->warning($message, $context);
		}
		catch(\Exception $e) {
			$this->log_exception($e, $message);
		}
	}

	/**
	 * Adds a log record at the ERROR level.
	 *
	 * This method allows for compatibility with common interfaces.
	 *
	 * @param  string  $message The log message
	 * @param  array   $context The log context
	 * @return Boolean Whether the record has been processed
	 * @see Monolog\Logger\error()
	 * @since 1.8.0.160728
	 */
	public function error($message, array $context = array()) {
		try {
			$this->logger->error($message, $context);
		}
		catch(\Exception $e) {
			$this->log_exception($e, $message);
		}
	}

	/**
	 * Adds a log record at the CRITICAL level.
	 *
	 * This method allows for compatibility with common interfaces.
	 *
	 * @param  string  $message The log message
	 * @param  array   $context The log context
	 * @return Boolean Whether the record has been processed
	 * @see Monolog\Logger\critical()
	 * @since 1.8.0.160728
	 */
	public function critical($message, array $context = array()) {
		try {
			$this->logger->critical($message, $context);
		}
		catch(\Exception $e) {
			$this->log_exception($e, $message);
		}
	}

	/**
	 * Adds a log record at the ALERT level.
	 *
	 * This method allows for compatibility with common interfaces.
	 *
	 * @param  string  $message The log message
	 * @param  array   $context The log context
	 * @return Boolean Whether the record has been processed
	 * @see Monolog\Logger\alert()
	 * @since 1.8.0.160728
	 */
	public function alert($message, array $context = array()) {
		try {
			$this->logger->alert($message, $context);
		}
		catch(\Exception $e) {
			$this->log_exception($e, $message);
		}
	}

	/**
	 * Adds a log record at the EMERGENCY level.
	 *
	 * This method allows for compatibility with common interfaces.
	 *
	 * @param  string  $message The log message
	 * @param  array   $context The log context
	 * @return Boolean Whether the record has been processed
	 * @see Monolog\Logger\emergency()
	 * @since 1.8.0.160728
	 */
	public function emergency($message, array $context = array()) {
		try {
			$this->logger->emergency($message, $context);
		}
		catch(\Exception $e) {
			$this->log_exception($e, $message);
		}
	}

	/**
	 * Adds an AUDIT log record. This is a special record, at INFO level, which is
	 * used to keep track of special events, to audit application usage.
	 *
	 * @param  string  $message The log message
	 * @param  array   $context The log context
	 * @return Boolean Whether the record has been processed
	 * @see Monolog\Logger\emergency()
	 * @since 1.8.0.160728
	 */
	public function audit($message, array $context = array()) {
		// Add a special "audit" argument, to separate this message from the normal
		// NOTICE messages
		$context['_audit'] = true;
		try {
			$this->notice($message, $context);
		}
		catch(\Exception $e) {
			$this->log_exception($e, $message);
		}
	}

	/**
	 * Returns the log file to be used by this logger.
	 *
	 * @param string log_id The ID of this log.
	 * @return string
	 * @since 1.8.0.160728
	 */
	public static function get_log_file_name($log_id) {
		if(defined('WC_LOG_DIR')) {
			$log_dir = WC_LOG_DIR;
		}
		else {
			$upload_dir = wp_upload_dir();
			$log_dir = $upload_dir['basedir'] . '/wc-logs/';
		}
		return str_replace('\\', '/', trailingslashit($log_dir) . sanitize_file_name($log_id) . '.log');
	}

	/**
	 * Initialises the logger.
	 *
	 * @param bool debug_mode Indicates if debug mode is active. If it's not,
	 * debug messages won't be logged.
	 * @since 1.8.0.160728
	 */
	protected function init_logger($debug_mode = false) {
		// TODO Check that the target log file is writable. If not, raise a PHP warning

		$this->log_handlers = apply_filters('wc_aelia_log_handlers', array(
			new StreamHandler(self::get_log_file_name($this->log_id), \Monolog\Logger::NOTICE),
			// TODO Implement Loggly only if plugin is configured for that purpose
			//new LogglyHandler('15fc33d6-ac21-44c2-a88e-a2d5bf4db398'),
		), $this->log_id, $this);

		$this->logger = new \MonoLog\Logger($this->log_id, $this->log_handlers);

		// Only add the Process ID logger if the getmypid() function exists. Some
		// hosting providers remove it for some reason.
		// @since 1.9.8.171002
		if(function_exists('getmypid')) {
			$this->logger->pushProcessor(new ProcessIdProcessor());
		}
		$this->set_debug_mode($debug_mode);
	}

	/**
	 * Class constructor.
	 *
	 * @param string log_id The identifier for the log.
	 * @param bool debug_mode Indicates if debug mode is active. If it's not,
	 * debug messages won't be logged.
	 */
	public function __construct($log_id, $debug_mode = false) {
		$this->log_id = $log_id;

		$this->init_logger($debug_mode);
	}

	/**
	 * Factory method.
	 *
	 * @param string log_id The identifier for the log.
	 * @return Aelia\WC\Logger.
	 */
	public static function factory($log_id) {
		$class = get_called_class();
		return new $class($log_id);
	}
}
