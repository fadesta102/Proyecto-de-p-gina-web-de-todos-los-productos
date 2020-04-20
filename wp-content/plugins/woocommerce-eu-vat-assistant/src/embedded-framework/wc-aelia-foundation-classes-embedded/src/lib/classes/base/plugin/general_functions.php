<?php if(!defined('ABSPATH')) exit; // Exit if accessed directly

// General functions used throughout the plugin.

if(!function_exists('get_value')) {
	/**
	 * Return the value from an associative array or an object.
	 *
	 * @param string $key The key or property name of the value.
	 * @param mixed $collection The array or object to search.
	 * @param mixed $default The value to return if the key does not exist.
	 * @return mixed The value from the array or object.
	 */
	function get_value($key, $collection, $default = FALSE) {
		$result = $default;
		if(is_array($collection) && isset($collection[$key])) {
			$result = $collection[$key];
		} elseif(is_object($collection) && isset($collection->$key)) {
			$result = $collection->$key;
		}

		return $result;
	}
}

if(!function_exists('get_arr_value')) {
	/**
	 * Return the value from an associative array.
	 *
	 * @param string $key The key of the value.
	 * @param mixed $collection The array search.
	 * @param mixed $default The value to return if the key does not exist.
	 * @return mixed The value from the array, or the default.
	 * @since 1.5.12.150512
	 */
	function get_arr_value($key, array $collection, $default = FALSE) {
		return isset($collection[$key]) ? $collection[$key] : $default;
	}
}

if(!function_exists('get_datetime_format')) {
	/**
	 * Returns a concatenation of WordPress settings for date and time formats.
	 *
	 * @param string separator A string to separate date and time formatting
	 * strings.
	 * @return string The concatenation of date_format, separator and time_format.
	 */
	function get_datetime_format($separator = ' ') {
		return get_option('date_format') . $separator . get_option('time_format');
	}
}

if(!function_exists('coalesce')) {
	/**
	 * Returns the value of the first non-empty argument received.
	 *
	 * @param mixed Any arguments.
	 * @return mixed The value of the first non-empty argument.
	 */
	function coalesce() {
		$args = func_get_args();
		foreach($args as $arg) {
			if(!empty($arg)) {
				return $arg;
			}
		}
		return null;
	}
}

if(!function_exists('aelia_get_product_id')) {
	/**
	 * Returns the ID of a product, or variation.
	 *
	 * @param WC_Product product.
	 * @return int
	 * @since 1.8.2.161216
	 */
	function aelia_get_product_id($product) {
		if(method_exists($product, 'get_id')) {
			return $product->get_id();
		}
		else {
			if($product->is_type('variation')) {
				return $product->variation_id;
			}
			else {
				return $product->id;
			}
		}
	}
}

if(!function_exists('aelia_get_object_id')) {
	/**
	 * Returns the ID of an object (Order, Coupon, etc).
	 *
	 * @param object object The object whose ID will be returned.
	 * @return int
	 * @since 1.8.2.161216
	 */
	function aelia_get_object_id($object) {
		if(method_exists($object, 'get_id')) {
			return $object->get_id();
		}
		return $object->id;
	}
}


if(!function_exists('aelia_get_order_id')) {
	/**
	 * Returns the ID of an order.
	 *
	 * @param WC_Order order The order whose ID will be returned.
	 * @return int
	 * @since 1.8.2.161216
	 */
	function aelia_get_order_id($order) {
		return aelia_get_object_id($order);
	}
}

if(!function_exists('aelia_get_coupon_id')) {
	/**
	 * Returns the ID of a coupon.
	 *
	 * @param WC_Coupon coupon The coupon whose ID will be returned.
	 * @return int
	 * @since 1.8.2.161216
	 */
	function aelia_get_coupon_id($coupon) {
		return aelia_get_object_id($coupon);
	}
}
