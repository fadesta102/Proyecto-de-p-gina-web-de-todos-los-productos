<?php
namespace Aelia\WC;
if(!defined('ABSPATH')) exit; // Exit ifaccessed directly

if(!class_exists('Aelia\WC\Aelia_SessionManager')) {
	/**
	 * A simple Session handler. Compatible with WooCommerce 2.0 and later.
	 */
	class Aelia_SessionManager {
		// @var bool Indicates if WooCommerce session was started
		protected static $_wc_session_started = null;

		/**
		 * Indicates if the WooCommerce session was started.
		 *
		 * @return bool
		 * @since 1.5.8.150429
		 */
		public static function has_session() {
			if(self::$_wc_session_started === null) {
				self::$_wc_session_started = is_object(self::session()) &&
																		 self::session()->has_session();
			}
			return self::$_wc_session_started;
		}

		/**
		 * Returns the instance of WooCommerce session.
		 *
		 * @return WC_Session
		 */
		protected static function session() {
			return self::wc()->session;
		}

		/**
		 * Returns global instance of WooCommerce.
		 *
		 * @return object The global instance of WC.
		 */
		protected static function wc() {
			global $woocommerce;
			return $woocommerce;
		}

		/**
		 * Safely store data into the session. Compatible with WooCommerce 2.0+ and
		 * backwards compatible with previous versions.
		 *
		 * @param string key The Key of the value to retrieve.
		 * @param mixed value The value to set.
		 */
		public static function set_value($key, $value) {
			$woocommerce = self::wc();

			// Allow to change both the key and the value before setting the data
			// against the session
			// @since 1.9.12.180104
			$key = apply_filters('wc_aelia_afc_session_set_value_key', $key, $value);
			$value = apply_filters('wc_aelia_afc_session_set_value', $value, $key);

			// Don't set values if the key is empty
			if(empty($key)) {
				return;
			}

			// WooCommerce 2.1
			if(version_compare($woocommerce->version, '2.1', '>=')) {
				if(isset($woocommerce->session)) {
					$woocommerce->session->set($key, $value);
				}
				return;
			}

			// WooCommerce 2.0
			if(version_compare($woocommerce->version, '2.0', '>=')) {
				if(isset($woocommerce->session)) {
					$woocommerce->session->$key = $value;
				}
				return;
			}
		}

		/**
		 * Safely retrieve data from the session. Compatible with WooCommerce 2.0+ and
		 * backwards compatible with previous versions.
		 *
		 * @param string key The Key of the value to retrieve.
		 * @param mixed default The default value to return if the key is not found.
		 * @param bool remove_after_get Indicates if the value should be removed after
		 * having been retrieved.
		 * @return mixed The value associated with the key, or the default.
		 */
		public static function get_value($key, $default = null, $remove_after_get = false) {
			$woocommerce = self::wc();
			$result = null;

			// WooCommerce 2.1
			if(is_null($result) && version_compare($woocommerce->version, '2.1', '>=')) {
				if(!isset($woocommerce->session)) {
					return $default;
				}
				$result = @$woocommerce->session->get($key);
			}

			// WooCommerce 2.0
			if(is_null($result) && version_compare($woocommerce->version, '2.0', '>=')) {
				if(!isset($woocommerce->session)) {
					return $default;
				}
				$result = @$woocommerce->session->$key;
			}

			if($remove_after_get) {
				self::delete_value($key);
			}

			$result = empty($result) ? $default : $result;

			// Allow to change both the value before returning it
			// @since 1.9.12.180104
			return apply_filters('wc_aelia_afc_session_get_value', $result, $key, $default, $remove_after_get);
		}

		/**
		 * Safely remove data from the session. Compatible with WooCommerce 2.0+ and
		 * backwards compatible with previous versions.
		 *
		 * @param string key The Key of the value to retrieve.
		 */
		public static function delete_value($key) {
			$woocommerce = self::wc();

			// Allow to change the key before deleting the value
			// @since 1.9.12.180104
			$key = apply_filters('wc_aelia_afc_session_delete_value_key', $key);

			// WooCommerce 2.1
			if(version_compare($woocommerce->version, '2.1', '>=')) {
				if(isset($woocommerce->session)) {
					$woocommerce->session->set($key, null);
				}
				return;
			}

			// WooCommerce 2.0
			if(version_compare($woocommerce->version, '2.0', '>=')) {
				if(isset($woocommerce->session)) {
					unset($woocommerce->session->$key);
				}
				return;
			}
		}

		/**
		 * Set a cookie. This method is a wrapper for setcookie() function, which
		 * automatically uses WordPress constants.
		 *
		 * @param string $name The name of the cookie being set.
		 * @param string $value The value of the cookie.
		 * @param integer $expire The expiration of the cookie.
		 * @param bool  $secure Whether the cookie should be served only over https.
		 * @param bool $httponly Whether the cookie should be served only over http
		 * calls (no script access).
		 * @since 1.5.11.150507
		 */
		public static function set_cookie($name, $value, $expire = 0, $secure = false, $httponly = false) {
			// Allow to change the cookie before setting it
			// @since 1.9.12.180104
			$cookie_data = apply_filters('wc_aelia_afc_session_set_cookie', array(
				'name' => $name,
				'value' => $value,
				'expire' => $expire,
				'secure' => $secure,
				'cookie_path' => COOKIEPATH,
				'cookie_domain' => COOKIE_DOMAIN,
				// Set httponly option
				// @since 2.0.2.181203
				'httponly' => $httponly,
			));

			// Don't try to set a cookie when its data is clearly not valid
			// @since 2.0.13.200131
			// @link https://aelia.freshdesk.com/a/tickets/84200
			if(empty($cookie_data) || !is_array($cookie_data)) {
				return;
			}

			if(!headers_sent($file, $line)) {
				// Check that the cookie data is valid, before setting the cookie
				// @since 1.9.18.180319
				if(!empty($cookie_data) && is_array($cookie_data)) {
					setcookie($cookie_data['name'],
										$cookie_data['value'],
										$cookie_data['expire'],
										$cookie_data['cookie_path'],
										$cookie_data['cookie_domain'],
										$cookie_data['secure'],
										$cookie_data['httponly']);
					// Overwrite the cookie in the global variable, so that it can be accessed immediately
					$_COOKIE[$cookie_data['name']] = $cookie_data['value'];
				}
			}
			elseif(defined('WP_DEBUG') && WP_DEBUG) {
				$file = empty($file) ? 'unknown' : '';
				trigger_error("{$name} cookie cannot be set - headers already sent by file '{$file}' on line {$line}", E_USER_NOTICE);
			}
		}

		/**
		 * Returns the value of a cookie, or a default if such cookie is not found.
		 *
		 * @param string $name The name of the cookie being retrieved.
		 * @param string $default The default value to return if the cookie is not
		 * set.
		 * @since 1.5.11.150507
		 */
		public static function get_cookie($name, $default = null) {
			// Allow to change the cookie name before fetching it
			// @since 1.9.12.180104
			$value = isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;

			return apply_filters('wc_aelia_afc_session_get_cookie', $value, $name, $default);
		}
	}
}
