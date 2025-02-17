<?php

if (!defined('WC_EU_VAT_COMPLIANCE_DIR')) die('No direct access');

// Purpose: provide a central location where all relevant features can be accessed

/*
Components:

- Readiness tests
- Link to reports
- Link to settings (eventually: move settings)
- Link to tax rates
- Link to Premium
- Link to GeoIP settings, if needed + GeoIP status

- Switch plugin action link to point here

- Add FAQ link at the top, if/when there are some
*/

class WC_EU_VAT_Compliance_Control_Centre {

	/**
	 * Plugin constructor
	 */
	public function __construct() {
		add_action('admin_menu', array($this, 'admin_menu'));
// 		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_filter('woocommerce_screen_ids', array($this, 'woocommerce_screen_ids'));
		add_filter('woocommerce_reports_screen_ids', array($this, 'woocommerce_screen_ids'));
		add_action('wp_ajax_wc_eu_vat_cc', array($this, 'ajax'));
		add_action('wceuvat_background_tests', array($this, 'wceuvat_background_tests'));
	}

	public function ajax() {

		if (empty($_POST) || empty($_POST['subaction']) || !isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wc_eu_vat_nonce')) die('Security check');

		if (!current_user_can('manage_woocommerce')) die('Security check');
		
		if ('savesettings' == $_POST['subaction'] || 'savereadiness' == $_POST['subaction']) {

			if (empty($_POST['settings']) || !is_string($_POST['settings'])) die;

			parse_str($_POST['settings'], $posted_settings);

			if ('savereadiness' == $_POST['subaction']) {

				$save_email = empty($posted_settings['wceuvat_compliance_readiness_report_emails']) ? '' : $posted_settings['wceuvat_compliance_readiness_report_emails'];

				$tests = array();
				foreach ($posted_settings as $key => $val) {
					if (0 === strpos($key, 'wceuvat_test_')) {
						$test = substr($key, 13);
						$tests[$test] = (empty($val)) ? false : true;
					}
				}

				update_option('wceuvat_background_tests', array(
					'email' => $save_email,
					'tests' => $tests
				));
				
				wp_clear_scheduled_hook('wceuvat_background_tests');

				if ($save_email) {
					$time_now = time();
					$day_start = $time_now - ($time_now % 86400);
					// 2:15 am. Choose a fixed time so that the event doesn't run lots of times when the settings are saved.
					$next_time = $day_start + 8100 + rand(0, 3600);
					if ($next_time < $time_now) $next_time += 86400;
					wp_schedule_event($next_time, 'daily', 'wceuvat_background_tests');
				}

				echo json_encode(array('result' => 'ok'));
				die;
			}

			$all_settings = $this->get_all_settings();

			$any_found = false;

			// Save settings
			// If this gets more complex, we should instead use WC_Admin_Settings::save_fields()
			foreach ($all_settings as $setting) {
				if (!is_array($setting) || empty($setting['id'])) continue;
				if ($setting['type'] == 'euvat_tax_options_section' || $setting['type'] == 'sectionend') continue;

				if (!isset($posted_settings[$setting['id']])) {
// 					error_log("NOT FOUND: ".$setting['id']);
					continue;
				}

				$value = null;

				switch ($setting['type']) {
					case 'text';
					case 'radio';
					case 'select';
					$value = $posted_settings[$setting['id']];
					break;
					case 'wceuvat_taxclasses';
					$value = array_diff($posted_settings[$setting['id']], array('0'));
					break;
					case 'textarea';
					$value = wp_kses_post( trim( $posted_settings[$setting['id']] ) );
					break;
					case 'checkbox';
					$value = empty($posted_settings[$setting['id']]) ? 'no' : 'yes';
					break;
				}

				if (!is_null($value)) {
					$any_found = true;
					update_option($setting['id'], $value);
				}

			}

			if (!$any_found) {
				echo json_encode(array('result' => 'no options found'));
				die;
			}

			echo json_encode(array('result' => 'ok'));
		} elseif ('testprovider' == $_POST['subaction'] && !empty($_POST['key']) && !empty($_POST['tocurrency'])) {

			$providers = WooCommerce_EU_VAT_Compliance()->get_rate_providers();

			$to_currency = $_POST['tocurrency'];
			// Base currency
			$from_currency = get_option('woocommerce_currency');

			if (!is_array($providers) || empty($providers[$_POST['key']])) {
				echo json_encode(array('response' => 'Error: provider not found'));
				die;
			}

			$provider = $providers[$_POST['key']];

			$result = $provider->convert($from_currency, $to_currency, 10);

			$currency_code_options = get_woocommerce_currencies();

			$from_currency_label = $from_currency;
			if (isset($currency_code_options[$from_currency])) $from_currency_label = $currency_code_options[$from_currency]." - $from_currency";

			$to_currency_label = $to_currency;
			if (isset($currency_code_options[$to_currency])) $to_currency_label = $currency_code_options[$to_currency]." - $to_currency";

			if (false === $result) {
				echo json_encode(array('response' => __('Failed: The currency conversion failed. Please check the settings, that the chosen provider provides exchange rates for your chosen currencies, and the outgoing network connectivity from your webserver.', 'woocommerce-eu-vat-compliance')));
				die;
			}

			echo json_encode(array('response' => sprintf(__('Success: %s currency units in your shop base currency (%s) are worth %s currency units in your chosen VAT reporting currency (%s)', 'woocommerce-eu-vat-compliance'), '10.00', $from_currency_label, $result, $to_currency_label)));

		} elseif ('load_reports_tab' == $_POST['subaction']) {
			ob_start();
			do_action('wc_eu_vat_compliance_cc_tab_reports', true);
			$contents = @ob_get_contents();
			@ob_end_clean();

			echo json_encode(array(
				'result' => 'ok',
				'content' => $contents
			));
		} elseif ('export_settings' == $_POST['subaction']) {
		
			$plugin_version = WooCommerce_EU_VAT_Compliance()->get_version();

			include(ABSPATH.WPINC.'/version.php');

			$settings = $this->get_all_settings();
			
			$options = array();
			
			foreach ($settings as $setting) {
				$id = $setting['id'];
				$options[$id] = get_option($id);
			}
			
			$results = array(
				'options' => $options,
				'versions' => array(
					'wc' => defined('WOOCOMMERCE_VERSION') ? WOOCOMMERCE_VERSION : '?',
					'wc_eu_vat_compliance' => '?',
					'wp' => $wp_version
				),
			);
			
			if (!empty($plugin_version)) $results['versions']['wc_eu_vat_compliance'] = $plugin_version;
			
			echo json_encode($results);
		}

		die;

	}
	
	private function get_all_settings() {
		$vat_settings = $this->get_settings_vat();
		$tax_settings = $this->get_settings_tax();

		$exchange_rate_providers = WooCommerce_EU_VAT_Compliance()->get_rate_providers();

		$exchange_rate_settings = $this->get_settings();

		if (!empty($exchange_rate_providers) && is_array($exchange_rate_providers)) {
			foreach ($exchange_rate_providers as $key => $provider) {
				$settings = method_exists($provider, 'settings_fields') ? $provider->settings_fields() : false;
				if (!is_string($settings) && !is_array($settings)) continue;
				if (is_array($settings)) {
					$exchange_rate_settings[] = $settings;
				}
			}
		}

		return array_merge($vat_settings, $tax_settings, $exchange_rate_settings);
	}
	
	public function woocommerce_settings_euvat_vat_options_end() {
		?><tr valign="top">
			<th scope="row" class="titledesc">
				<?php _e('Export settings', 'woocommerce-eu-vat-compliance');?>
			</th>
			<td class="forminp">
				<button class="button" id="euvatcompliance-export-settings"><?php _e('Export settings', 'woocommerce-eu-vat-compliance');?></button>
				<img id="euvatcompliance_export_spinner" src="<?php echo esc_attr(admin_url('images/spinner.gif'));?>" style="width:18px; height: 18px;padding-left: 18px;display:none;">
				<br>
				<p><?php _e('The main use of this button is for debugging purposes - it allows a third party who does not have access to your WP dashboard to easily see/analyse/reproduce your settings.', 'woocommerce-eu-vat-compliance');?></p>
			</td>
		</tr>
		<?php
	}

	public function wceuvat_background_tests() {
		$opts = get_option('wceuvat_background_tests');

		if (!is_array($opts) || empty($opts['email']) || empty($opts['tests']) || !is_array($opts['tests'])) return;

		if (!class_exists('WC_EU_VAT_Compliance_Readiness_Tests')) require_once(WC_EU_VAT_COMPLIANCE_DIR.'/readiness-tests.php');
		$test = new WC_EU_VAT_Compliance_Readiness_Tests();

		$results = $test->get_results($opts['tests']);

		$result_descriptions = $test->result_descriptions();

		$any_failed = false;

		$mail_body = site_url()."\r\n\r\n".__('The following readiness tests failed; for more information, or to change your configuration visit the EU VAT Compliance control centre in your WP dashboard.', 'woocommerce-eu-vat-compliance')."\r\n\r\n";

		foreach ($results as $id => $res) {
			if (!is_array($res)) continue;
			// fail|pass|warning|?
			if ($res['result'] != 'fail') continue;
			$any_failed = true;
			$mail_body .= $res['label'].': '.$res['info']."\r\n\r\n";
		}

		if (!$any_failed) return;

		foreach (explode(',', $opts['email']) as $sendmail_addr) {

			$subject = __('Failed EU VAT compliance readiness tests on '.site_url(), 'woocommerce-eu-vat-compliance');

			$sent = wp_mail(trim($sendmail_addr), $subject, $mail_body);

		}

	}

	public function woocommerce_screen_ids($screen_ids) {
		if (!in_array('woocommerce_page_wc_eu_vat_compliance_cc', $screen_ids)) $screen_ids[] = 'woocommerce_page_wc_eu_vat_compliance_cc';
		return $screen_ids;
	}

	public function admin_menu() {
		add_submenu_page(
			'woocommerce',
			__('EU VAT Compliance', 'woocommerce-eu-vat-compliance'),
			__('EU VAT Compliance', 'woocommerce-eu-vat-compliance'),
			'manage_woocommerce',
			'wc_eu_vat_compliance_cc',
			array($this, 'settings_page')
		);
	}

	public function settings_page() {

		$tabs = apply_filters('wc_eu_vat_compliance_cc_tabs', array(
			'settings' => __('Settings', 'woocommerce-eu-vat-compliance'),
			'readiness' => __('Readiness Report', 'woocommerce-eu-vat-compliance'),
			'reports' => __('VAT Reports', 'woocommerce-eu-vat-compliance'),
			'premium' => __('Premium', 'woocommerce-eu-vat-compliance')
		));

		$active_tab = !empty($_REQUEST['tab']) ? $_REQUEST['tab'] : 'settings';
		if ('taxes' == $active_tab || !empty($_GET['range'])) $active_tab = 'reports';

		$this->compliance = WooCommerce_EU_VAT_Compliance();

		$version = $this->compliance->get_version();
		$premium = false;

		if (!$this->compliance->is_premium()) {
			// What to do here?
		} else {
			$premium = true;
			$version .= ' '.__('(premium)', 'woocommerce-eu-vat-compliance');
		}

// .' - '.sprintf(__('version %s', 'woocommerce-eu-vat-compliance'), $version);
		?>
		<h1><?php echo __('EU VAT Compliance', 'woocommerce-eu-vat-compliance').' '.__('for WooCommerce', 'woocommerce-eu-vat-compliance');?></h1>
		<a href="<?php echo apply_filters('wceuvat_support_url', 'https://wordpress.org/support/plugin/woocommerce-eu-vat-compliance/');?>"><?php _e('Support', 'woocommerce-eu-vat-compliance');?></a> | 
		<?php if (!$premium) {
			?><a href="https://www.simbahosting.co.uk/s3/product/woocommerce-eu-vat-compliance/"><?php _e("Premium", 'woocommerce-eu-vat-compliance');?></a> |
		<?php } ?>
		<a href="https://www.simbahosting.co.uk/s3/shop/"><?php _e('More plugins', 'woocommerce-eu-vat-compliance');?></a> |
		<a href="https://updraftplus.com">UpdraftPlus WordPress Backups</a> | 
		<a href="http://david.dw-perspective.org.uk"><?php _e("Lead developer's homepage",'woocommerce-eu-vat-compliance');?></a>
		<!--<a href="https://wordpress.org/plugins/woocommerce-eu-vat-compliance/faq/">FAQs</a> | -->
		- <?php _e('Version','woocommerce-eu-vat-compliance');?>: <?php echo $version; ?>
		<br>

		<h2 class="nav-tab-wrapper" id="wceuvat_tabs" style="margin: 14px 0px;">
		<?php

		foreach ($tabs as $slug => $title) {
			?>
				<a class="nav-tab <?php if($slug == $active_tab) echo 'nav-tab-active'; ?>" href="#wceuvat-navtab-<?php echo $slug;?>-content" id="wceuvat-navtab-<?php echo $slug;?>"><?php echo $title;?></a>
			<?php
		}

		echo '</h2>';

		foreach ($tabs as $slug => $title) {
			echo "<div class=\"wceuvat-navtab-content\" id=\"wceuvat-navtab-".$slug."-content\"";
			if ($slug != $active_tab) echo ' style="display:none;"';
			echo ">";

			if (method_exists($this, 'render_tab_'.$slug)) call_user_func(array($this, 'render_tab_'.$slug));

			do_action('wc_eu_vat_compliance_cc_tab_'.$slug);

			echo "</div>";
		}

		add_action('admin_footer', array($this, 'admin_footer'));
		
	}

	private function render_tab_premium() {
		echo '<h2>'.__('Premium version', 'woocommerce-eu-vat-compliance').'</h2>';

			$tick = WC_EU_VAT_COMPLIANCE_URL.'/images/tick.png';
			$cross = WC_EU_VAT_COMPLIANCE_URL.'/images/cross.png';
			
			?>
			<div>
				<p>
					<span style="font-size: 115%;"><?php _e('You are currently using the free version of WooCommerce EU VAT Compliance from wordpress.org.', 'woocommerce-eu-vat-compliance');?> <a href="https://www.simbahosting.co.uk/s3/product/woocommerce-eu-vat-compliance/"><?php _e('A premium version of this plugin is available at this link.', 'woocommerce-eu-vat-compliance');?></a></span>
				</p>
			</div>
			<div>
				<div style="margin-top:30px;">
				<table class="wceuvat_feat_table">
					<tr>
						<th class="wceuvat_feat_th" style="text-align:left;"></th>
						<th class="wceuvat_feat_th"><?php _e('Free version', 'woocommerce-eu-vat-compliance');?></th>
						<th class="wceuvat_feat_th"><?php _e('Premium version', 'woocommerce-eu-vat-compliance');?></th>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e('Get it from', 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell" style="vertical-align:top; line-height: 120%; margin-top:6px; padding-top:6px;">WordPress.Org</td>
						<td class="wceuvat_tick_cell" style="padding: 6px; line-height: 120%;">
							<a href="https://www.simbahosting.co.uk/s3/product/woocommerce-eu-vat-compliance/"><strong><?php _e('Follow this link', 'woocommerce-eu-vat-compliance');?></strong></a><br>
							</td>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e("Identify your customers' locations", 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e('Evidence is recorded in detail, ready for audit', 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e('Display prices including correct geographical VAT from the first page', 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e('Currency conversions into reporting currency', 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e('Live exchange rates', 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e("Quick entering of each country's VAT rates", 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e('Advanced dashboard reports', 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e('Option to forbid EU sales if VAT is chargeable', 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e('Central control panel', 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e('Mixed shops (i.e. handle non-digital goods also)', 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e('Extra text on invoices (e.g. VAT notices for business customers)', 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e('Refund support', 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e('Exempt business customers (i.e. B2B) from VAT', 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $cross;?>"></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e('Add B2B VAT numbers to invoices', 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $cross;?>"></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e('Option to allow B2B sales only', 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $cross;?>"></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e('CSV (i.e. spreadsheet) download of comprehensive information on all orders', 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $cross;?>"></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e('Optionally resolve location conflicts via self-certification', 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $cross;?>"></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e('Show VAT in multiple currencies upon invoices', 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $cross;?>"></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e('Support for the official WooCommerce subscriptions extension', 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $cross;?>"></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e('Helps to fund continued development', 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $cross;?>"></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
					</tr>
					<tr>
						<td class="wceuvat_feature_cell"><?php _e('Personal support', 'woocommerce-eu-vat-compliance');?></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $cross;?>"></td>
						<td class="wceuvat_tick_cell"><img src="<?php echo $tick;?>"></td>
					</tr>
				</table>
				<p><em><?php echo __('All invoicing features are in conjunction with the free WooCommerce PDF invoices and packing slips plugin.', 'woocommerce-eu-vat-compliance');?> - <a href="https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/"><?php _e('link', 'woocommerce-eu-vat-compliance');?></a></em></p>
				</div>
			</div>
			<?php
			
		add_action('admin_footer', array($this, 'admin_footer_premiumcss'));

	}

	public function admin_footer_premiumcss() {
		?>
		<style type="text/css">
			ul.wceuvat_premium_description_list {
				list-style: disc inside;
			}
			ul.wceuvat_premium_description_list li {
				display: inline;
			}
			ul.wceuvat_premium_description_list li::after {
				content: " | ";
			}
			ul.wceuvat_premium_description_list li.last::after {
				content: "";
			}
			.wceuvat_feature_cell{
					background-color: #F7D9C9 !important;
					padding: 5px 10px 5px 10px;
			}
			.wceuvat_feat_table, .wceuvat_feat_th, .wceuvat_feat_table td{
					border: 1px solid black;
					border-collapse: collapse;
					font-size: 120%;
					background-color: white;
			}
			.wceuvat_feat_th {
				padding: 6px;
			}
			.wceuvat_tick_cell{
					padding: 4px;
					text-align: center;
			}
			.wceuvat_tick_cell img{
					margin: 4px 0;
					height: 24px;
			}
		</style>
		<?php
	}

// 	private function render_class_settings($name) {
// 		if (false == ($class = WooCommerce_EU_VAT_Compliance($name))) return false;
// 		if (empty($class->settings)) return false;
// 		woocommerce_admin_fields($class->settings);
// 		return true;
// 	}

	public function woocommerce_admin_field_euvat_tax_options_section($value) {
		if ( ! empty( $value['title'] ) ) {
			echo '<h3>' . esc_html( $value['title'] ) . '</h3>';
		}
		if ( ! empty( $value['desc'] ) ) {
			echo wpautop( wptexturize( wp_kses_post( $value['desc'] ) ) );
		}
		echo '<div>';
		echo '<table class="form-table">'. "\n\n";
		if ( ! empty( $value['id'] ) ) {
			do_action( 'woocommerce_settings_' . sanitize_title( $value['id'] ) );
		}
	}

	public function woocommerce_settings_euvat_vat_options_after() {
		echo '</div>';
	}

	public function woocommerce_settings_euvat_tax_options_after() {
		echo '</div>';
	}

	public function get_settings_vat() {
		$vat_settings = array(
			array( 'title' => __( 'WooCommerce VAT settings (new settings from the EU VAT compliance plugin)', 'woocommerce-eu-vat-compliance' ), 'type' => 'euvat_tax_options_section', 'desc' => '', 'id' => 'euvat_vat_options' ),
		);

// Premium has a "force VAT display" option that is not yet implemented
// 		$get_from = array('WC_EU_VAT_Compliance', 'WC_EU_VAT_Compliance_VAT_Number', 'WC_EU_VAT_Compliance_Premium');
		$get_from = array('WC_EU_VAT_Compliance', 'WC_EU_VAT_Compliance_VAT_Number');

		foreach ($get_from as $name) {
			if (false == ($class = WooCommerce_EU_VAT_Compliance($name))) continue;
			if (empty($class->settings)) continue;
			$vat_settings = array_merge($vat_settings, $class->settings);
		}
		
		$vat_settings[] = array( 'type' => 'sectionend', 'id' => 'euvat_vat_options' );

		static $action_added = false;
		if (!$action_added) {
			add_action('woocommerce_settings_euvat_vat_options_end', array($this, 'woocommerce_settings_euvat_vat_options_end'));
			$action_added = true;
		}
		
		return $vat_settings;
	}

	public function get_settings_tax() {
		// From class-wc-settings-tax.php
		$tax_settings = array(

			array( 'title' => __( 'Other WooCommerce tax options potentially relevant for EU VAT compliance', 'woocommerce-eu-vat-compliance' ), 'type' => 'euvat_tax_options_section','desc' => '', 'id' => 'euvat_tax_options' ),

			array(
				'title'   => __( 'Enable Taxes', 'woocommerce-eu-vat-compliance' ),
				'desc'    => __( 'Enable taxes and tax calculations', 'woocommerce-eu-vat-compliance' ),
				'id'      => 'woocommerce_calc_taxes',
				'default' => 'no',
				'type'    => 'checkbox'
			),

			array(
				'title'    => __( 'Prices Entered With Tax', 'woocommerce-eu-vat-compliance' ),
				'id'       => 'woocommerce_prices_include_tax',
				'default'  => 'no',
				'type'     => 'radio',
				'desc_tip' =>  __( 'This option is important as it will affect how you input prices. Changing it will not update existing products.', 'woocommerce-eu-vat-compliance' ),
				'options'  => array(
					'yes' => __( 'Yes, I will enter prices inclusive of tax', 'woocommerce-eu-vat-compliance' ),
					'no'  => __( 'No, I will enter prices exclusive of tax', 'woocommerce-eu-vat-compliance' )
				),
			),

			array(
				'title'    => __( 'Calculate Tax Based On:', 'woocommerce-eu-vat-compliance' ),
				'id'       => 'woocommerce_tax_based_on',
				'desc_tip' =>  __( 'This option determines which address is used to calculate tax.', 'woocommerce-eu-vat-compliance' ),
				'default'  => 'shipping',
				'type'     => 'select',
				'options'  => array(
					'shipping' => __( 'Customer shipping address', 'woocommerce-eu-vat-compliance' ),
					'billing'  => __( 'Customer billing address', 'woocommerce-eu-vat-compliance' ),
					'base'     => __( 'Shop base address', 'woocommerce-eu-vat-compliance' )
				),
			),
		);

			if (function_exists('WC') && version_compare(WC()->version, '2.3', '>=')) {
				// WC 2.3 has an extra 'geo-locate' option
				$tax_settings[] = array(
					'title'    => __( 'Default Customer Address:', 'woocommerce-eu-vat-compliance' ),
					'id'       => 'woocommerce_default_customer_address',
					'desc_tip' =>  __( 'This option determines the customers default address (before they input their details).', 'woocommerce-eu-vat-compliance' ),
					'default'  => 'geolocation',
					'type'     => 'select',
					'class'    => 'wc-enhanced-select',
					'options'  => array(
						''            => __( 'No address', 'woocommerce-eu-vat-compliance' ),
						'base'        => __( 'Shop base address', 'woocommerce-eu-vat-compliance' ),
						'geolocation' => __( 'Geolocate address', 'woocommerce-eu-vat-compliance' ),
					),
				);
			} else {
				$tax_settings[] = array(
					'title'    => __( 'Default Customer Address:', 'woocommerce-eu-vat-compliance' ),
					'id'       => 'woocommerce_default_customer_address',
					'desc_tip' =>  __( 'This option determines the customers default address (before they input their own).', 'woocommerce-eu-vat-compliance' ),
					'default'  => 'base',
					'type'     => 'select',
					'options'  => array(
						''     => __( 'No address', 'woocommerce-eu-vat-compliance' ),
						'base' => __( 'Shop base address', 'woocommerce-eu-vat-compliance' ),
					),
				);
			}

// 			array(
// 				'title'   => __( 'Additional Tax Classes', 'woocommerce-eu-vat-compliance' ),
// 				'desc'    => __( 'List additional tax classes below (1 per line). This is in addition to the default <code>Standard Rate</code>. Tax classes can be assigned to products.', 'woocommerce-eu-vat-compliance' ),
// 				'id'      => 'woocommerce_tax_classes',
// 				'css'     => 'width:100%; height: 65px;',
// 				'type'    => 'textarea',
// 				'default' => sprintf( __( 'Reduced Rate%sZero Rate', 'woocommerce-eu-vat-compliance' ), PHP_EOL )
// 			),

			$tax_settings = array_merge($tax_settings, array(
			array(
				'title'   => __( 'Display prices in the shop:', 'woocommerce-eu-vat-compliance' ),
				'id'      => 'woocommerce_tax_display_shop',
				'default' => 'excl',
				'type'    => 'select',
				'options' => array(
					'incl'   => __( 'Including tax', 'woocommerce-eu-vat-compliance' ),
					'excl'   => __( 'Excluding tax', 'woocommerce-eu-vat-compliance' ),
				)
			),

			array(
				'title'   => __( 'Price display suffix:', 'woocommerce-eu-vat-compliance' ),
				'id'      => 'woocommerce_price_display_suffix',
				'default' => '',
				'class' => 'widefat',
				'type'    => 'text',
				'desc'    => __( 'Define text to show after your product prices. This could be, for example, "inc. Vat" to explain your pricing. You can also have prices substituted here using one of the following: <code>{price_including_tax}, {price_excluding_tax}</code>. Content wrapped in-between <code>{iftax}</code> and <code>{/iftax}</code> will display only if there was tax; within that, <code>{country}</code> will be replaced by the name of the country used to calculate tax.', 'woocommerce-eu-vat-compliance' ).' '.__('Use <code>{country_with_brackets}</code> to show the country only if the item had per-country varying VAT, and to show brackets around the country.', 'woocommerce-eu-vat-compliance'),
			),

			array(
				'title'   => __( 'Display prices during cart/checkout:', 'woocommerce-eu-vat-compliance' ),
				'id'      => 'woocommerce_tax_display_cart',
				'default' => 'excl',
				'type'    => 'select',
				'options' => array(
					'incl'   => __( 'Including tax', 'woocommerce-eu-vat-compliance' ),
					'excl'   => __( 'Excluding tax', 'woocommerce-eu-vat-compliance' ),
				),
				'autoload'      => false
			),

			array(
				'title'   => __( 'Display tax totals:', 'woocommerce-eu-vat-compliance' ),
				'id'      => 'woocommerce_tax_total_display',
				'default' => 'itemized',
				'type'    => 'select',
				'options' => array(
					'single'     => __( 'As a single total', 'woocommerce-eu-vat-compliance' ),
					'itemized'   => __( 'Itemized', 'woocommerce-eu-vat-compliance' ),
				),
				'autoload' => false
			),

			array( 'type' => 'sectionend', 'id' => 'euvat_tax_options' ),

		));

		return $tax_settings;
	}

	private function render_tab_settings() {
		echo '<h2>'.__('Settings', 'woocommerce-eu-vat-compliance').'</h2>';

		echo '<p><em>'.__('Many settings below can also be found in other parts of your WordPress dashboard; they are brought together here also for convenience.', 'woocommerce-eu-vat-compliance').'</em></p>';

		$tax_settings_link = (defined('WOOCOMMERCE_VERSION') && version_compare(WOOCOMMERCE_VERSION, '2.1', '<')) ? admin_url('admin.php?page=woocommerce_settings&tab=tax') : admin_url('admin.php?page=wc-settings&tab=tax');

// 		echo '<h3>'.__('Tax settings', 'woocommerce-eu-vat-compliance').'</h3><p><a href="'.$tax_settings_link.'">'.__('Find these in the "Tax" section of the WooCommerce settings.', 'woocommerce-eu-vat-compliance').'</a></p>';

		$register_actions = array('woocommerce_admin_field_euvat_tax_options_section', 'woocommerce_settings_euvat_tax_options_after', 'woocommerce_settings_euvat_vat_options_after');
		foreach ($register_actions as $action) {
			add_action($action, array($this, $action));
		}

// __('Find these in the "Tax" section of the WooCommerce settings.', 'woocommerce-eu-vat-compliance')

		$vat_settings = $this->get_settings_vat();
		$tax_settings = $this->get_settings_tax();

		wp_enqueue_script('jquery-ui-accordion');

		echo '<div style="width:960px; margin-bottom: 8px;" id="wceuvat_settings_accordion">';

		// Needed for 2.0 (not for 2.2)
		if (!function_exists('woocommerce_admin_fields')) {
			$this->compliance->wc->admin_includes();
			if (!function_exists('woocommerce_admin_fields')) include_once(  $this->compliance->wc->plugin_path().'/admin/woocommerce-admin-settings.php' );
		}

		// VAT settings
		woocommerce_admin_fields($vat_settings);
		
		// Currency conversion
		echo '<h3>'.__('VAT reporting currency', 'woocommerce-eu-vat-compliance').'</h3><div>';
		$this->currency_conversion_section();
		echo '</div>';

		// Other WC tax settings
		woocommerce_admin_fields($tax_settings);

		// Tax tables
		echo '<h3>'.__('Tax tables (set up tax rates for each country)', 'woocommerce-eu-vat-compliance').'</h3>';

		echo '<div>';

		echo '<p><a href="http://ec.europa.eu/taxation_customs/resources/documents/taxation/vat/how_vat_works/rates/vat_rates_en.pdf">'.__('Official EU documentation on current VAT rates.', 'woocommerce-eu-vat-compliance').'</a></p>';

		// TODO: List all known rate classes
		echo '<h4>'.__('Standard-rate tax table', 'woocommerce-eu-vat-compliance').'</h4><p><a href="'.$tax_settings_link.'&section=standard">'.__('Follow this link.', 'woocommerce-eu-vat-compliance').'</a></p>';

		echo '<h4>'.__('Reduced-rate tax table', 'woocommerce-eu-vat-compliance').'</h4><p><a href="'.$tax_settings_link.'&section=reduced-rate">'.__('Follow this link.', 'woocommerce-eu-vat-compliance').'</a></p>';


		echo '</div></div>';

		?>
		<button style="margin-left: 4px;" id="wc_euvat_cc_settings_save" class="button button-primary"><?php _e('Save Settings', 'woocommerce-eu-vat-compliance');?></button>
		<script>

			var wceuvat_query_leaving = false;

			window.onbeforeunload = function(e) {
				if (wceuvat_query_leaving) {
					var ask = "<?php echo esc_js(__('You have unsaved settings.', 'woocommerce-eu-vat-compliance'));?>";
					e.returnValue = ask;
					return ask;
				}
			}

			jQuery(document).ready(function($) {
				$("#wceuvat_settings_accordion").accordion({collapsible: true, active: false, animate: 100, heightStyle: "content" });
				$("#wceuvat_settings_accordion input, #wceuvat_settings_accordion textarea, #wceuvat_settings_accordion select").change(function() {
					wceuvat_query_leaving = true;
				});
				$("#wc_euvat_cc_settings_save").click(function() {
					wceuvat_savesettings("savesettings");
				});
				
				$('#euvatcompliance-export-settings').click(function(e) {
					e.preventDefault();
					$('#euvatcompliance_export_spinner').show();
					$.post(ajaxurl, {
						action: 'wc_eu_vat_cc',
						subaction: 'export_settings',
						_wpnonce: '<?php echo esc_js(wp_create_nonce('wc_eu_vat_nonce'));?>',
					}, function(response) {
						$('#euvatcompliance_export_spinner').hide();
						try {
							resp = $.parseJSON(response);
							
							console.log("euvatcompliance: export_settings: result follows");
							console.log(resp);
							
							mime_type = 'application/json';
							var stuff = response;
							var link = document.body.appendChild(document.createElement('a'));
							link.setAttribute('download', 'euvatcompliance-export-settings.json');
							link.setAttribute('style', "display:none;");
							link.setAttribute('href', 'data:' + mime_type  +  ';charset=utf-8,' + encodeURIComponent(stuff));
							link.click(); 

						} catch(err) {
							console.log("Unexpected response (export_settings 2): "+response);
							console.log(err);
						}
					});
				});
				
			});
		</script>
		<style type="text/css">
			#wceuvat_settings_accordion .ui-accordion-content, #wceuvat_settings_accordion .ui-widget-content, #wceuvat_settings_accordion h3 { background: transparent !important; }
			.ui-widget {font-family: inherit !important; }
		</style>

		<?php
	}

	private function get_settings() {

		$base_currency = get_option('woocommerce_currency');
		$base_currency_symbol = get_woocommerce_currency_symbol($base_currency);

		$currency_code_options = get_woocommerce_currencies();

		$currency_label = $base_currency;
		if (isset($currency_code_options[$base_currency])) $currency_label = $currency_code_options[$base_currency]." ($base_currency)";

		foreach ( $currency_code_options as $code => $name ) {
			$currency_code_options[ $code ] = $name;
			$symbol = get_woocommerce_currency_symbol( $code );
			if ($symbol) $currency_code_options[$code] .= ' (' . get_woocommerce_currency_symbol( $code ) . ')';
		}

		$exchange_rate_providers = WooCommerce_EU_VAT_Compliance()->get_rate_providers();

		$exchange_rate_options = array();
		foreach ($exchange_rate_providers as $key => $provider) {
			$info = $provider->info();
			$exchange_rate_options[$key] = $info['title'];
		}

		return apply_filters('wc_euvat_compliance_exchange_settings', array(
			array(
				'title'    => __( 'Currency', 'woocommerce-eu-vat-compliance' ),
				'desc'     => __( "When an order is made, exchange rate information will be added to the order, allowing all amounts to be converted into the currency chosen here. This is necessary if orders may be made in a different currency than the currency you are required to report VAT in.", 'woocommerce-eu-vat-compliance' ),
				'id'       => 'woocommerce_eu_vat_compliance_vat_recording_currency',
				'css'      => 'min-width:350px;',
				'default'  => $base_currency,
				'type'     => 'select',
				'class'    => 'chosen_select',
				'desc_tip' =>  true,
				'options'  => $currency_code_options
			),

			array(
				'title'    => __( 'Exchange rate provider', 'woocommerce-eu-vat-compliance' ),
				'id'       => 'woocommerce_eu_vat_compliance_exchange_rate_provider',
				'css'      => 'min-width:350px;',
				'default'  => 'ecb',
				'type'     => 'select',
				'class'    => 'chosen_select',
				'desc_tip' =>  true,
				'options'  => $exchange_rate_options
			),
		));
	}

	public function currency_conversion_section() {

		$base_currency = get_option('woocommerce_currency');
		$base_currency_symbol = get_woocommerce_currency_symbol($base_currency);
		$currency_code_options = get_woocommerce_currencies();
		$currency_label = $base_currency;
		if (isset($currency_code_options[$base_currency])) $currency_label = $currency_code_options[$base_currency]." ($base_currency)";

		echo '<p>'.sprintf(__('Set the currency that you have to use when making VAT reports. If this is not the same as your base currency (%s), then when orders are placed, the exchange rate will be recorded as part of the order information, allowing accurate VAT reports to be made.', 'woocommerce-eu-vat-compliance'), $currency_label).' '.__('If using a currency other than your base currency, then you must configure an exchange rate provider.', 'woocommerce-eu-vat-compliance').'</p>';

		echo '<p>'.__('N.B. If you have a need for a specific provider, then please let us know.', 'woocommerce-eu-vat-compliance').'</p>';

		echo '<table class="form-table">'. "\n\n";

		$currency_settings = $this->get_settings();

		woocommerce_admin_fields($currency_settings);

		echo '</table>';

		$exchange_rate_providers = WooCommerce_EU_VAT_Compliance()->get_rate_providers();

		foreach ($exchange_rate_providers as $key => $provider) {
			$settings = method_exists($provider, 'settings_fields') ? $provider->settings_fields() : false;
			if (!is_string($settings) && !is_array($settings)) continue;
			$info = $provider->info();
			echo '<div id="wceuvat-rate-provider_container_'.$key.'" class="wceuvat-rate-provider_container wceuvat-rate-provider_container_'.$key.'">';
			echo '<h4 style="padding-bottom:0px; margin-bottom:0px;">'.__('Configure exchange rate provider', 'woocommerce-eu-vat-compliance').': '.htmlspecialchars($info['title']).'</h4>';
			echo '<p style="padding-top:0px; margin-top:0px;">'.htmlspecialchars($info['description']);
			if (!empty($info['url'])) echo ' <a href="'.$info['url'].'">'.__('Follow this link for more information.', 'woocommerce-eu-vat-compliance').'</a>';
			echo '</p>';
			echo '<table class="form-table" style="">'. "\n\n";
			if (is_string($settings)) {
				echo "<tr><td>$settings</td></tr>";
			} elseif (is_array($settings)) {
				woocommerce_admin_fields($settings);
			}
			echo '</table>';
			echo "<div id=\"wc_eu_vat_test_provider_$key\"></div><button id=\"wc_eu_vat_test_provider_button_$key\" onclick=\"test_provider('".$key."')\" class=\"button wc_eu_vat_test_provider_button\">".__('Test Provider', 'woocommerce-eu-vat-compliance')."</button>";
			echo '</div>';
		}

	}

	public function render_tab_readiness() {
		echo '<h2>'.__('EU VAT Compliance Readiness', 'woocommerce-eu-vat-compliance').'</h2>';

		echo '<div style="width:960px;">';

		echo '<p><em>'.__('N.B. Items listed below are listed as suggestions only, and it is not claimed that all apply to every situation. Items listed do not constitute legal or financial advice. For all decisions on which settings are right for you in your location and setup, final responsibility is yours.', 'woocommerce-eu-vat-compliance').'</em></p>';

		// 1420070400
		if (time() < strtotime('1 Jan 2015 00:00:00 GMT')) {
			echo '<p><strong><em>'.__('N.B. It is not yet the 1st of January 2015; so, you may not want to act on all the items mentioned below yet.', 'woocommerce-eu-vat-compliance').'</em></strong></p>';
		}

// 		echo '<p>'.__('Please come back here after your next plugin update, to see what has been added - this feature is still under development. When done, it will advise you on which of your WooCommerce and other settings may need adjusting for EU VAT compliance.', 'woocommerce-eu-vat-compliance').'</p>';

		if (!class_exists('WC_EU_VAT_Compliance_Readiness_Tests')) require_once(WC_EU_VAT_COMPLIANCE_DIR.'/readiness-tests.php');
		$test = new WC_EU_VAT_Compliance_Readiness_Tests();
		$results = $test->get_results();

		$result_descriptions = $test->result_descriptions();

		?>
		<table>
		<thead>
			<tr>
				<th></th>
				<th style="text-align:left; min-width: 140px;"><?php _e('Test', 'woocommerce-eu-vat-compliance');?></th>
				<th style="text-align:left; min-width:60px;"><?php _e('Result', 'woocommerce-eu-vat-compliance');?></th>
				<th style="text-align:left;"><?php _e('Futher information', 'woocommerce-eu-vat-compliance');?></th>
			</tr>
		</thead>
		<tbody>
		<?php

		$opts = get_option('wceuvat_background_tests');
		$email = empty($opts['email']) ? '' : (string)$opts['email'];

		$default_bottom_blurb = '<p><a href="https://www.simbahosting.co.uk/s3/product/woocommerce-eu-vat-compliance/">'.__('To automatically run these tests daily, and notify yourself of any failed tests by email, use our Premium version.', 'woocommerce-eu-vat-compliance').'</a></p>';
		$bottom_blurb = apply_filters('wceuvat_readinesstests_bottom_section', $default_bottom_blurb, $email);
		$premium_present = ($bottom_blurb == $default_bottom_blurb) ? false : true;

		foreach ($results as $id => $res) {
			if (!is_array($res)) continue;
			// result, label, info
			switch ($res['result']) {
				case 'fail':
					$col = 'red';
					break;
				case 'pass':
					$col = 'green';
					break;
				case 'warning':
					$col = 'orange';
					break;
				default:
					$col = 'orange';
					break;
			}
			$row_bg = 'color:'.$col;

			$checked = (is_array($opts) && empty($opts['tests'][$id])) ? false : true;

			?>

			<tr style="<?php echo $row_bg;?>">
				<td style="vertical-align:top;"><?php
				if ($premium_present) { ?>
					<input type="checkbox" id="wceuvat_test_<?php echo esc_attr($id);?>" name="wceuvat_test_<?php echo esc_attr($id);?>" value="1" <?php if ($checked) echo 'checked="checked"'; ?>>
				<?php } ?>
				</td>
				<td style="vertical-align:top;"><label for="wceuvat_test_<?php echo esc_attr($id);?>"><?php echo $res['label'];?></label></td>
				<td style="vertical-align:top;"><?php echo $result_descriptions[$res['result']];?></td>
				<td style="vertical-align:top;"><?php echo $res['info'];?></td>
			</tr>
			<?php
		}

		?>
		</tbody>
		</table>
		<?php

		echo $bottom_blurb;
		// TODO: Links to the other stuff?

		echo '</div>';

	}

	public function admin_footer() {
		$text = esc_attr(__('N.B. The final country used may be modified according to your EU VAT settings.', 'woocommerce-eu-vat-compliance'));
		$text2 = esc_attr(__('N.B. The WooCommerce EU VAT Compliance plugin causes geo-location to identify the default address, regardless of whether you also activate the geo-location built into WooCommerce (2.3+) here. We recommend choosing "Shop Base Address" here (though, choosing "Geolocate address" should be harmless, as both geo-locations should have the same result).', 'woocommerce-eu-vat-compliance'));
		$testing = esc_js(__('Testing...', 'woocommerce-eu-vat-compliance'));
		$test = esc_js(__('Test Provider', 'woocommerce-eu-vat-compliance'));
		$nonce = wp_create_nonce("wc_eu_vat_nonce");
		$response = esc_js(__('Response:', 'woocommerce-eu-vat-compliance'));
		$loading = esc_js(__('Loading...', 'woocommerce-eu-vat-compliance'));
		$error = esc_js(__('Error', 'woocommerce-eu-vat-compliance'));

		echo '
		<script>
			function wceuvat_savesettings(subaction) {

				jQuery.blockUI({ message: "<h1>'.__('Saving...', 'woocommerce-eu-vat-compliance').'</h1>" });

				// https://stackoverflow.com/questions/10147149/how-can-i-override-jquerys-serialize-to-include-unchecked-checkboxes

				var formData;
				var which_checkboxes;

				if ("savereadiness" == subaction) {
					formData = jQuery("#wceuvat-navtab-readiness-content input, #wceuvat-navtab-readiness-content textarea, #wceuvat-navtab-readiness-content select").serialize();
					which_checkboxes = "#wceuvat-navtab-readiness-content";
				} else {
					formData = jQuery("#wceuvat_settings_accordion input, #wceuvat_settings_accordion textarea, #wceuvat_settings_accordion select").serialize();
					which_checkboxes = "#wceuvat_settings_accordion";
				}

				// include unchecked checkboxes. use filter to only include unchecked boxes.
				jQuery.each(jQuery(which_checkboxes+" input[type=checkbox]")
				.filter(function(idx){
					return jQuery(this).prop("checked") === false
				}),
				function(idx, el){
					// attach matched element names to the formData with a chosen value.
					var emptyVal = "0";
					formData += "&" + jQuery(el).attr("name") + "=" + emptyVal;
				}
				);

				jQuery.post(ajaxurl, {
					action: "wc_eu_vat_cc",
					subaction: subaction,
					settings: formData,
					_wpnonce: "'.$nonce.'"
				}, function(response) {
					try {
						resp = jQuery.parseJSON(response);
						if (resp.result == "ok") {
// 								alert("'.esc_js(__('Settings Saved.', 'woocommerce-eu-vat-compliance')).'");
							wceuvat_query_leaving = false;
						} else {
							alert("'.esc_js(__('Response:', 'woocommerce-eu-vat-compliance')).' "+resp.result);
						}
					} catch(err) {
						alert("'.esc_js(__('Response:', 'woocommerce-eu-vat-compliance')).' "+response);
						console.log(response);
						console.log(err);
					}
					jQuery.unblockUI();
				});
			}';

		echo <<<ENDHERE
			function test_provider(key) {
				jQuery('#wc_eu_vat_test_provider_button_'+key).html('$testing');
				jQuery.post(ajaxurl, {
					action: "wc_eu_vat_cc",
					subaction: "testprovider",
					tocurrency: jQuery('#woocommerce_eu_vat_compliance_vat_recording_currency').val(),
					key: key,
					_wpnonce: "$nonce"
				}, function(response) {
					jQuery('#wc_eu_vat_test_provider_button_'+key).html('$test');
					try {
						resp = jQuery.parseJSON(response);
						jQuery('#wc_eu_vat_test_provider_'+key).html('<p>'+resp.response+'</p>');
					} catch(err) {
						alert('$response '+response);
						console.log(response);
						console.log(err);
					}
				});
			}
			jQuery(document).ready(function($) {

				$("#wc_euvat_cc_readiness_save").click(function() {
					wceuvat_savesettings("savereadiness");
				});

				function show_correct_provider() {
					var provider = $('#woocommerce_eu_vat_compliance_exchange_rate_provider').val();
					$('.wceuvat-rate-provider_container').hide();
					$('#wceuvat-rate-provider_container_'+provider).show();
				}
				show_correct_provider();
				$('#woocommerce_eu_vat_compliance_exchange_rate_provider').change(function() {
					show_correct_provider();
				});
				$('#woocommerce_tax_based_on').after('<br><em>$text</em>');
				$('#woocommerce_default_customer_address').after('<br><em>$text2</em>');
				$('#wceuvat_tabs a.nav-tab').click(function() {
					$('#wceuvat_tabs a.nav-tab').removeClass('nav-tab-active');
					$(this).addClass('nav-tab-active');
					var id = $(this).attr('id');
					if ('wceuvat-navtab-' == id.substring(0, 15)) {
						$('div.wceuvat-navtab-content').hide();
						$('#wceuvat-navtab-'+id.substring(15)+'-content').show();
						// This is not yet ready
// 						$('#wceuvat_tabs').trigger('show_'+id.substring(15));
					}
					return false;
				});
				
				var content_loaded = false;
				$('#wceuvat_tabs').on('show_reports', function() {
					if (content_loaded) return;
					content_loaded = true;
					$('#wceuvat-navtab-reports-content').html('$loading');
					$.post(ajaxurl, {
						action: "wc_eu_vat_cc",
						subaction: 'load_reports_tab',
						_wpnonce: '$nonce'
					}, function(response) {
						resp = jQuery.parseJSON(response);
						if (resp.result == 'ok') {
							$('#wceuvat-navtab-reports-content').html(resp.content);
						} else {
							$('#wceuvat-navtab-reports-content').html('$error');
							console.log(resp);
						}
					});
				});
				
			});
		</script>
ENDHERE;
	}

}
