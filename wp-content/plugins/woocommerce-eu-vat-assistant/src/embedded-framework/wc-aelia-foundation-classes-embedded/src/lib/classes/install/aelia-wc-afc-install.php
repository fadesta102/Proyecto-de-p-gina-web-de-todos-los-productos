<?php
namespace Aelia\WC;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Aelia Foundation Classes for WooCommerce.
 **/
class WC_AeliaFoundationClasses_Install extends Aelia_Install {
	// @var string The name of the lock that will be used by the installer to prevent race conditions.
	protected $lock_name = 'WC_AELIA_AFC';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->logger = WC_AeliaFoundationClasses::instance()->get_logger();
	}

	/**
	 * Runs plugin updates required by version 1.6.1.150728:
	 * - Automatic update of GeoIP database.
	 *
	 * @since 1.6.1.150728
	 */
	protected function update_to_1_6_1_150728() {
		IP2Location::install_database();
		return true;
	}

	/**
	 * Runs the updates required to upgrade to version 1.6.9.151103.
	 * - Adds a table to keep track of dismissed messages.
	 *
	 * @return bool
	 * @since 1.6.9.151103
	 */
	protected function update_to_1_6_9_151103() {
		$this->start_transaction();

		$charset_collate = $this->wpdb->get_charset_collate();
		try {
			$table_name = $this->wpdb->prefix . 'aelia_dismissed_messages';
			$SQL = "
				CREATE TABLE IF NOT EXISTS `$table_name` (
					`user_id` int NOT NULL,
					`message_id` varchar(100) NOT NULL DEFAULT '',
					`date_updated` datetime DEFAULT NULL,
					PRIMARY KEY (`user_id`, `message_id`)
				) {$charset_collate};
			";
			$result = $this->exec($SQL);

			if($result === false) {
				$this->add_message(E_USER_ERROR,
													 sprintf(__('Creation of table "%s" failed. Please ' .
																			'check PHP error log for error messages ' .
																			'related to the operation.', WC_AeliaFoundationClasses::$text_domain),
																	 $table_name));
				$this->rollback_transaction();
			}
			else {
				$this->add_message(E_USER_NOTICE,
													 sprintf(__('Table "%s" created successfullly.', WC_AeliaFoundationClasses::$text_domain),
																	 $table_name));
				$this->commit_transaction();
				$result = true;
			}
		}
		catch(Exception $e)	{
			throw $e;

			$this->rollback_transaction();
			$this->log($e->getMessage());
			$this->add_message(E_USER_ERROR, $e->getMessage());
			return false;
		}
		return (bool)$result;
	}
}
