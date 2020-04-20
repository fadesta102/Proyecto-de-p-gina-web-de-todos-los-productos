<?php
namespace Aelia\WC\EU_VAT_Assistant;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Runs automatic updates for the EU VAT Assistant plugin.
 **/
class WC_Aelia_EU_VAT_Assistant_Install extends \Aelia\WC\Aelia_Install {
	// @var string The name of the lock that will be used by the installer to prevent race conditions.
	protected $lock_name = 'WC_AELIA_EU_VAT_ASSISTANT';
	// @var string The text domain, used for localisation.
	protected $text_domain;

	/**
	 * Returns the instance of the EU VAT Assistant plugin.
	 *
	 * @return \Aelia\WC\EU_VAT_Assistant\WC_Aelia_EU_VAT_Assistant
	 * @since 1.3.20.150330
	 */
	protected function EUVA() {
		return WC_Aelia_EU_VAT_Assistant::instance();
	}

	/**
	 * Class constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->logger = $this->EUVA()->get_logger();
		$this->text_domain = DEFINITIONS::TEXT_DOMAIN;
	}

	/**
	 * Updates the VAT data for the orders saved by the EU VAT Assistant 0.9.8.x.
	 * and earlier.
	 *
	 * @return bool
	 * @since 0.9.9.141223
	 */
	protected function update_to_0_9_9_141223() {
		// Retrieve the exchange rates for the orders whose data already got
		// partially converted
		$SQL = "
			SELECT
				PM.post_id AS order_id
				,PM.meta_value AS eu_vat_data
			FROM
				{$this->wpdb->postmeta} AS PM
			WHERE
				(PM.meta_key = '_eu_vat_data')
		";

		$orders_to_update = $this->select($SQL);
		// Debug
		//var_dump($orders_to_update); die();

		foreach($orders_to_update as $order_meta) {
			$eu_vat_data = maybe_unserialize($order_meta->eu_vat_data);
			// Skip VAT data that is already in the correct format
			$vat_data_version = get_value('eu_vat_assistant_version', $eu_vat_data);
			if(version_compare($vat_data_version, '0.9.9.141223', '>=')) {
				continue;
			}

			// Add tax rate details to the all orders having VAT data that doesn't
			// already contain such information
			$order = new Order($order_meta->order_id);
			$order->update_vat_data();
		}
		return true;
	}

	/**
	 * Updates the VAT data for the orders saved by the EU VAT Assistant 0.10.0.x
	 * and earlier.
	 *
	 * @return bool
	 * @since 0.10.1.141230
	 */
	protected function update_to_0_10_1_141230() {
		// Retrieve the exchange rates for the orders whose data already got
		// partially converted
		$SQL = "
			SELECT
				PM.post_id AS order_id
				,PM.meta_value AS eu_vat_data
			FROM
				{$this->wpdb->postmeta} AS PM
			WHERE
				(PM.meta_key = '_eu_vat_data')
		";

		$orders_to_update = $this->select($SQL);
		// Debug
		//var_dump($orders_to_update); die();

		foreach($orders_to_update as $order_meta) {
			$eu_vat_data = maybe_unserialize($order_meta->eu_vat_data);
			// Skip VAT data that is already in the correct format
			$vat_data_version = get_value('eu_vat_assistant_version', $eu_vat_data);
			if(version_compare($vat_data_version, '0.10.1.141230', '>=')) {
				continue;
			}

			// Add tax rate details to the all orders having VAT data that doesn't
			// already contain such information
			$order = new Order($order_meta->order_id);
			$order->update_vat_data();
		}
		return true;
	}

	/**
	 * Runs the updates required by the EU VAT Assistant 1.1.3.150111 and later.
	 *
	 * @return bool
	 * @since 1.1.3.150111
	 */
	protected function update_to_1_1_3_150111() {
		global $wpdb;

		// Adds an extra column to the taxes table, to keep track of whic country
		// each tax should be paid to
		$table_name = $wpdb->prefix . 'woocommerce_tax_rates';
		$column_name = 'tax_payable_to_country';
		if($this->column_exists($table_name, $column_name)) {
			return true;
		}
		/* Note: we are using the extremely large VARCHAR(200) type for the
		 * "tax_payable_to_country" column because the core "tax_rate_country"
		 * column is also using the same. Since both columns are going to contain a
		 * country, it makes sense that they have the same type (although the size
		 * is probably excessive).
		 */
		return $this->add_column($table_name, $column_name, 'VARCHAR(200)');
	}

	/**
	 * Runs the updates required by the EU VAT Assistant 1.2.0.150215 and later.
	 *
	 * @return bool
	 * @since 1.2.0.150215
	 */
	protected function update_to_1_2_0_150215() {
		$this->start_transaction();

		try {
			$charset_collate = $this->wpdb->get_charset_collate();
			$table_name = $this->wpdb->prefix . 'aelia_exchange_rates_history';
			$SQL = "
				CREATE TABLE IF NOT EXISTS `$table_name` (
					`provider_name` varchar(170) NOT NULL,
					`base_currency` char(3) NOT NULL,
					`rates_date` datetime NOT NULL,
					`rates` longtext DEFAULT NULL,
					`date_updated` datetime DEFAULT NULL,
					PRIMARY KEY (`provider_name`, `rates_date`)
				) {$charset_collate};
			";
			$result = $this->exec($SQL);

			if($result === false) {
				$this->add_message(E_USER_ERROR,
													 sprintf(__('Creation of table "%s" failed. Please ' .
																			'check PHP error log for error messages ' .
																			'related to the operation.', $this->text_domain),
																	 $table_name));
				$this->rollback_transaction();
			}
			else {
				$this->add_message(E_USER_NOTICE,
													 sprintf(__('Table "%s" created successfullly.', $this->text_domain),
																	 $table_name));
				$this->commit_transaction();
				$result = true;
			}
		}
		catch(Exception $e) {
			$this->rollback_transaction();
			$this->log($e->getMessage());
			$this->add_message(E_USER_ERROR, $e->getMessage());
			return false;
		}
		return (bool)$result;
	}

	/**
	 * Runs the following updates required by the EU VAT Assistant 1.2.1.150215
	 * and later:
	 * - Ensures the the EU VAT data saved with all orders contains the tax class
	 *   of each tax rate.
	 *
	 * @return bool
	 * @since 1.2.1.150215
	 */
	protected function update_to_1_2_1_150215() {
		$this->start_transaction();
		try {
			$SQL = "
				SELECT
					TR.tax_rate_id
					,TR.tax_rate_class
				FROM
					{$this->wpdb->prefix}woocommerce_tax_rates TR
			";

			$tax_rates_data = $this->select($SQL, OBJECT_K);
			// Debug
			//var_dump($tax_rates_data); die();

			// Retrieve past orders
			$SQL = "
				SELECT
					PM.post_id AS order_id
					,PM.meta_value AS eu_vat_data
				FROM
					{$this->wpdb->postmeta} AS PM
				WHERE
					(PM.meta_key = '_eu_vat_data')
			";

			$orders_to_update = $this->select($SQL);
			// Debug
			//var_dump($orders_to_update); die();

			foreach($orders_to_update as $order_meta) {
				$eu_vat_data = maybe_unserialize($order_meta->eu_vat_data);
				// Skip VAT data that is already in the correct format
				$vat_data_version = get_value('eu_vat_assistant_version', $eu_vat_data);
				if(version_compare($vat_data_version, '1.2.1.150215', '>=')) {
					continue;
				}

				if(empty($eu_vat_data['taxes']) || !is_array($eu_vat_data['taxes'])) {
					continue;
				}

				foreach($eu_vat_data['taxes'] as $rate_id => $tax_info) {
					if(isset($tax_info['tax_rate_class'])) {
						continue;
					}

					$tax_rate = get_value($rate_id, $tax_rates_data);
					if(!empty($tax_rate)) {
						// Note: an empty tax rate class is not an error. It simply represents the
						// "Standard" class
						$tax_info['tax_rate_class'] = $tax_rate->tax_rate_class;
					}
					$eu_vat_data['taxes'][$rate_id] = $tax_info;
				}
				$eu_vat_data['eu_vat_assistant_version'] = '1.2.1.150215';

				// Debug
				//var_dump($eu_vat_data);die();
				update_post_meta($order_meta->order_id, '_eu_vat_data', $eu_vat_data);
			}
			$this->commit_transaction();
			$result = true;
		}
		catch(Exception $e) {
			$this->rollback_transaction();
			$this->log($e->getMessage());
			$this->add_message(E_USER_ERROR, $e->getMessage());
			$result = false;
		}
		return $result;
	}

	/**
	 * Updates the VAT numbers stored with the orders, to ensure that they contain
	 * the correct prefix.
	 *
	 * @return bool
	 * @since 1.3.20.150330
	 */
	protected function update_to_1_3_20_150330() {
		$px = $this->wpdb->prefix;

		// Retrieve the orders that have a valid EU VAT number
		$SQL = "
			SELECT
				ORDER_META.post_id AS order_id
				,ORDER_META.meta_value AS eu_vat_evidence
			FROM
				{$this->wpdb->postmeta} AS ORDER_META
				INNER JOIN
				{$px}postmeta AS ORDER_META2 ON
					(ORDER_META2.post_id = ORDER_META.post_id) AND
					(ORDER_META2.meta_key = '_vat_number_validated') AND
					(ORDER_META2.meta_value = 'valid')
			WHERE
				(ORDER_META.meta_key = '_eu_vat_evidence')
		";

		$orders_to_update = $this->select($SQL);
		// Debug
		//var_dump($orders_to_update); die();

		foreach($orders_to_update as $order_meta) {
			$eu_vat_evidence = maybe_unserialize($order_meta->eu_vat_evidence);
			// Skip VAT evidence that is already in the correct format
			$vat_evidence_version = get_value('eu_vat_assistant_version', $eu_vat_evidence);
			if(version_compare($vat_evidence_version, '1.3.20.150330', '>=')) {
				continue;
			}

			/* Only process orders that actually have a VAT number associated. The SQL
			 * should have filtered the ones that don't have such data, but this extra
			 * check will filter out orders that have been tampered with.
			 */
			$vat_number = trim(get_value('vat_number', $eu_vat_evidence['exemption']));
			$vat_country = trim(get_value('vat_country', $eu_vat_evidence['exemption']));
			if(empty($vat_number) || empty($vat_country)) {
				continue;
			}

			// Replace the VAT number with the full one, inclusive of country prefix
			$order_vat_number = (string)$this->EUVA()->get_full_vat_number($vat_country, $vat_number);
			if(empty($order_vat_number)) {
				$this->log(sprintf(__('Order ID "%s". Invalid VAT Number parsed: "%s".', $this->text_domain),
													 $order_meta->order_id,
													 $order_vat_number), false);
			}
			$eu_vat_evidence['exemption']['vat_number'] = $order_vat_number;
			update_post_meta($order_meta->order_id, '_eu_vat_evidence', $eu_vat_evidence);
			update_post_meta($order_meta->order_id, 'vat_number', $order_vat_number);
		}
		return true;
	}

	/**
	 * Overrides standard update method to ensure that requirements for update are
	 * in place.
	 *
	 * @param string plugin_id The ID of the plugin.
	 * @param string new_version The new version of the plugin, which will be
	 * stored after a successful update to keep track of the status.
	 * @return bool
	 */
	public function update($plugin_id, $new_version) {
		//delete_option($plugin_id);
		return parent::update($plugin_id, $new_version);
	}
}
