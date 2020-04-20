<?php
namespace Aelia\WC\EU_VAT_Assistant;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Writes to the log used by the plugin.
 */
class Logger extends \Aelia\WC\Logger {
	/**
	 * Class constructor.
	 *
	 * @param string log_id The identifier for the log.
	 * @param bool debug_mode Indicates if debug mode is active. If it's not,
	 * debug messages won't be logged.
	 */
	public function __construct($log_id, $debug_mode = false) {
		parent::__construct($log_id, WC_Aelia_EU_VAT_Assistant::settings()->get('debug_mode', false));
	}
}
