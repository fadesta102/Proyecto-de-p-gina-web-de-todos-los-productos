<?php
/*
Plugin Name: WPC Composite Products for WooCommerce
Plugin URI: https://wpclever.net/
Description: WPC Composite Products provide a powerful kit-building solution for WooCommerce store.
Version: 2.0.4
Author: WPClever.net
Author URI: https://wpclever.net
Text Domain: wpc-composite-products
Domain Path: /languages/
Requires at least: 4.0
Tested up to: 5.4
WC requires at least: 3.0
WC tested up to: 4.0.1
*/

defined( 'ABSPATH' ) || exit;

! defined( 'WOOCO_VERSION' ) && define( 'WOOCO_VERSION', '2.0.4' );
! defined( 'WOOCO_URI' ) && define( 'WOOCO_URI', plugin_dir_url( __FILE__ ) );
! defined( 'WOOCO_REVIEWS' ) && define( 'WOOCO_REVIEWS', 'https://wordpress.org/support/plugin/wpc-composite-products/reviews/?filter=5' );
! defined( 'WOOCO_CHANGELOG' ) && define( 'WOOCO_CHANGELOG', 'https://wordpress.org/plugins/wpc-composite-products/#developers' );
! defined( 'WOOCO_DISCUSSION' ) && define( 'WOOCO_DISCUSSION', 'https://wordpress.org/support/plugin/wpc-composite-products' );
! defined( 'WPC_URI' ) && define( 'WPC_URI', WOOCO_URI );

include 'includes/wpc-menu.php';
include 'includes/wpc-dashboard.php';

if ( ! function_exists( 'wooco_init' ) ) {
	add_action( 'plugins_loaded', 'wooco_init', 11 );

	function wooco_init() {
		// load text-domain
		load_plugin_textdomain( 'wpc-composite-products', false, basename( __DIR__ ) . '/languages/' );

		if ( ! function_exists( 'WC' ) || ! version_compare( WC()->version, '3.0.0', '>=' ) ) {
			add_action( 'admin_notices', 'wooco_notice_wc' );

			return;
		}

		if ( ! class_exists( 'WC_Product_Composite' ) && class_exists( 'WC_Product' ) ) {
			class WC_Product_Composite extends WC_Product {
				public function __construct( $product = 0 ) {
					parent::__construct( $product );
				}

				public function get_type() {
					return 'composite';
				}

				public function add_to_cart_url() {
					$product_id = $this->id;

					return apply_filters( 'woocommerce_product_add_to_cart_url', get_permalink( $product_id ), $this );
				}

				public function add_to_cart_text() {
					if ( $this->is_purchasable() && $this->is_in_stock() ) {
						$text = get_option( '_wooco_archive_button_select' );

						if ( empty( $text ) ) {
							$text = esc_html__( 'Select options', 'wpc-composite-products' );
						}
					} else {
						$text = get_option( '_wooco_archive_button_read' );

						if ( empty( $text ) ) {
							$text = esc_html__( 'Read more', 'wpc-composite-products' );
						}
					}

					return apply_filters( 'wooco_product_add_to_cart_text', $text, $this );
				}

				public function single_add_to_cart_text() {
					$text = get_option( '_wooco_single_button_add' );

					if ( empty( $text ) ) {
						$text = esc_html__( 'Add to cart', 'wpc-composite-products' );
					}

					return apply_filters( 'wooco_product_single_add_to_cart_text', $text, $this );
				}

				// extra functions

				public function get_pricing() {
					$product_id = $this->id;

					return get_post_meta( $product_id, 'wooco_pricing', true );
				}

				public function get_discount() {
					$product_id = $this->id;
					$discount   = 0;

					if ( ( $this->get_pricing() !== 'only' ) && ( $wooco_discount = get_post_meta( $product_id, 'wooco_discount_percent', true ) ) && is_numeric( $wooco_discount ) && ( (float) $wooco_discount < 100 ) && ( (float) $wooco_discount > 0 ) ) {
						$discount = (float) $wooco_discount;
					}

					return $discount;
				}

				public function get_components() {
					$product_id = $this->id;

					if ( ( $wooco_components = get_post_meta( $product_id, 'wooco_components', true ) ) && is_array( $wooco_components ) && count( $wooco_components ) > 0 ) {
						return $wooco_components;
					}

					return false;
				}

				public function get_composite_price() {
					// FB for WC
					return $this->get_price();
				}

				public function get_composite_price_including_tax() {
					// FB for WC
					return $this->get_price();
				}
			}
		}

		if ( ! class_exists( 'WPCleverWooco' ) && class_exists( 'WC_Product' ) ) {
			class WPCleverWooco {
				function __construct() {
					// Menu
					add_action( 'admin_menu', array( $this, 'wooco_admin_menu' ) );

					// Enqueue frontend scripts
					add_action( 'wp_enqueue_scripts', array( $this, 'wooco_wp_enqueue_scripts' ) );

					// Enqueue backend scripts
					add_action( 'admin_enqueue_scripts', array( $this, 'wooco_admin_enqueue_scripts' ) );

					// AJAX
					add_action( 'wp_ajax_wooco_add_component', array( $this, 'wooco_add_component' ) );

					// Add to selector
					add_filter( 'product_type_selector', array( $this, 'wooco_product_type_selector' ) );

					// Product data tabs
					add_filter( 'woocommerce_product_data_tabs', array( $this, 'wooco_product_data_tabs' ), 10, 1 );

					// Product data panels
					add_action( 'woocommerce_product_data_panels', array( $this, 'wooco_product_data_panels' ) );
					add_action( 'woocommerce_process_product_meta_composite', array(
						$this,
						'wooco_save_option_field'
					) );

					// Add to cart form & button
					add_action( 'woocommerce_composite_add_to_cart', array( $this, 'wooco_add_to_cart_form' ) );
					add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'wooco_add_to_cart_button' ) );

					// Add to cart
					add_filter( 'woocommerce_add_to_cart_validation', array(
						$this,
						'wooco_add_to_cart_validation'
					), 10, 2 );
					add_action( 'woocommerce_add_to_cart', array( $this, 'wooco_add_to_cart' ), 10, 6 );
					add_filter( 'woocommerce_add_cart_item_data', array( $this, 'wooco_add_cart_item_data' ), 10, 2 );
					add_filter( 'woocommerce_get_cart_item_from_session', array(
						$this,
						'wooco_get_cart_item_from_session'
					), 10, 2 );

					// Admin
					add_filter( 'display_post_states', array( $this, 'wooco_display_post_states' ), 10, 2 );

					// Cart item
					add_filter( 'woocommerce_cart_item_name', array( $this, 'wooco_cart_item_name' ), 10, 2 );
					add_filter( 'woocommerce_cart_item_quantity', array( $this, 'wooco_cart_item_quantity' ), 10, 3 );
					add_filter( 'woocommerce_cart_item_remove_link', array(
						$this,
						'wooco_cart_item_remove_link'
					), 10, 2 );
					add_filter( 'woocommerce_cart_contents_count', array( $this, 'wooco_cart_contents_count' ) );
					add_action( 'woocommerce_after_cart_item_quantity_update', array(
						$this,
						'wooco_update_cart_item_quantity'
					), 1, 2 );
					add_action( 'woocommerce_before_cart_item_quantity_zero', array(
						$this,
						'wooco_update_cart_item_quantity'
					), 1 );
					add_action( 'woocommerce_cart_item_removed', array( $this, 'wooco_cart_item_removed' ), 10, 2 );
					add_filter( 'woocommerce_cart_item_price', array( $this, 'wooco_cart_item_price' ), 10, 2 );
					add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'wooco_cart_item_subtotal' ), 10, 2 );

					// Hide on cart & checkout page
					if ( get_option( '_wooco_hide_component', 'no' ) !== 'no' ) {
						add_filter( 'woocommerce_cart_item_visible', array( $this, 'wooco_item_visible' ), 10, 2 );
						add_filter( 'woocommerce_order_item_visible', array( $this, 'wooco_item_visible' ), 10, 2 );
						add_filter( 'woocommerce_checkout_cart_item_visible', array(
							$this,
							'wooco_item_visible'
						), 10, 2 );
					}

					// Hide on mini-cart
					if ( get_option( '_wooco_hide_component_mini_cart', 'no' ) === 'yes' ) {
						add_filter( 'woocommerce_widget_cart_item_visible', array(
							$this,
							'wooco_item_visible'
						), 10, 2 );
					}

					// Item class
					if ( get_option( '_wooco_hide_component', 'no' ) !== 'yes' ) {
						add_filter( 'woocommerce_cart_item_class', array( $this, 'wooco_item_class' ), 10, 2 );
						add_filter( 'woocommerce_mini_cart_item_class', array( $this, 'wooco_item_class' ), 10, 2 );
						add_filter( 'woocommerce_order_item_class', array( $this, 'wooco_item_class' ), 10, 2 );
					}

					// Get item data
					if ( get_option( '_wooco_hide_component', 'no' ) === 'yes_text' ) {
						add_filter( 'woocommerce_get_item_data', array(
							$this,
							'wooco_get_item_data'
						), 10, 2 );
						add_action( 'woocommerce_checkout_create_order_line_item', array(
							$this,
							'wooco_checkout_create_order_line_item'
						), 10, 4 );
					}

					// Hide item meta
					add_filter( 'woocommerce_order_item_get_formatted_meta_data', array(
						$this,
						'wooco_order_item_get_formatted_meta_data'
					), 10, 1 );

					// Order item
					add_action( 'woocommerce_checkout_create_order_line_item', array(
						$this,
						'wooco_add_order_item_meta'
					), 10, 3 );
					add_filter( 'woocommerce_order_item_name', array( $this, 'wooco_cart_item_name' ), 10, 2 );
					add_filter( 'woocommerce_order_formatted_line_subtotal', array(
						$this,
						'wooco_order_formatted_line_subtotal'
					), 10, 2 );

					// Admin order
					add_filter( 'woocommerce_hidden_order_itemmeta', array(
						$this,
						'wooco_hidden_order_item_meta'
					), 10, 1 );
					add_action( 'woocommerce_before_order_itemmeta', array(
						$this,
						'wooco_before_order_item_meta'
					), 10, 1 );

					// Add settings link
					add_filter( 'plugin_action_links', array( $this, 'wooco_action_links' ), 10, 2 );
					add_filter( 'plugin_row_meta', array( $this, 'wooco_row_meta' ), 10, 2 );

					// Loop add-to-cart
					add_filter( 'woocommerce_loop_add_to_cart_link', array(
						$this,
						'wooco_loop_add_to_cart_link'
					), 10, 2 );

					// Cart contents instead of woocommerce_before_calculate_totals, prevent price error on mini-cart
					// Make sure this run after WPC Product Bundles
					add_filter( 'woocommerce_get_cart_contents', array(
						$this,
						'wooco_get_cart_contents'
					), 11, 1 );

					// Shipping
					add_filter( 'woocommerce_cart_shipping_packages', array(
						$this,
						'wooco_cart_shipping_packages'
					) );

					// Price html
					add_filter( 'woocommerce_get_price_html', array( $this, 'wooco_get_price_html' ), 99, 2 );

					// Order again
					add_filter( 'woocommerce_order_again_cart_item_data', array(
						$this,
						'wooco_order_again_cart_item_data'
					), 10, 2 );
					add_action( 'woocommerce_cart_loaded_from_session', array(
						$this,
						'wooco_cart_loaded_from_session'
					) );

					// Coupons
					add_filter( 'woocommerce_coupon_is_valid_for_product', array(
						$this,
						'wooco_coupon_is_valid_for_product'
					), 10, 4 );

					// Export
					add_filter( 'woocommerce_product_export_column_names', array( $this, 'wooco_add_export_column' ) );
					add_filter( 'woocommerce_product_export_product_default_columns', array(
						$this,
						'wooco_add_export_column'
					) );
					add_filter( 'woocommerce_product_export_product_column_wooco_components', array(
						$this,
						'wooco_add_export_data'
					), 10, 2 );

					// Import
					add_filter( 'woocommerce_csv_product_import_mapping_options', array(
						$this,
						'wooco_add_column_to_importer'
					) );
					add_filter( 'woocommerce_csv_product_import_mapping_default_columns', array(
						$this,
						'wooco_add_column_to_mapping_screen'
					) );
					add_filter( 'woocommerce_product_import_pre_insert_product_object', array(
						$this,
						'wooco_process_import'
					), 10, 2 );
				}

				function wooco_add_component() {
					if ( isset( $_POST['count'] ) && ( (int) $_POST['count'] > 2 ) ) {
						echo 'pv';
						die();
					}

					$this->wooco_component( true );
					die();
				}

				function wooco_component( $active = false, $component = array() ) {
					$component_default = array(
						'name'       => 'Name',
						'desc'       => 'Description',
						'type'       => 'categories',
						'orderby'    => 'default',
						'order'      => 'default',
						'categories' => '',
						'products'   => '',
						'default'    => '',
						'optional'   => 'no',
						'qty'        => 1,
						'custom_qty' => 'no',
						'price'      => '',
						'min'        => 0,
						'max'        => 1000
					);

					if ( ! empty( $component ) ) {
						$component = array_merge( $component_default, $component );
					} else {
						$component = $component_default;
					}

					$wooco_search_products_id   = uniqid( 'wooco_search_products-', false );
					$wooco_search_categories_id = uniqid( 'wooco_search_categories-', false );
					$wooco_search_default_id    = uniqid( 'wooco_search_default-', false );

					if ( class_exists( 'WPCleverWoopq' ) && ( get_option( '_woopq_decimal', 'no' ) === 'yes' ) ) {
						$wooco_step = '0.000001';
					} else {
						$wooco_step       = '1';
						$component['qty'] = (int) $component['qty'];
						$component['min'] = (int) $component['min'];
						$component['max'] = (int) $component['max'];
					}
					?>
                    <tr class="wooco_component">
                        <td>
                            <div class="wooco_component_inner <?php echo( $active ? 'active' : '' ); ?>">
                                <div class="wooco_component_heading">
                                    <span class="wooco_move_component"> # </span>
                                    <span class="wooco_component_name"><?php echo $component['name']; ?></span>
                                    <a class="wooco_remove_component"
                                       href="#"><?php esc_html_e( 'remove', 'wpc-composite-products' ); ?></a>
                                </div>
                                <div class="wooco_component_content">
                                    <div class="wooco_component_content_line">
                                        <div class="wooco_component_content_line_label">
											<?php esc_html_e( 'Name', 'wpc-composite-products' ); ?>
                                        </div>
                                        <div class="wooco_component_content_line_value">
                                            <input name="wooco_components[name][]" type="text" class="wooco_input_name"
                                                   value="<?php echo $component['name']; ?>"/>
                                        </div>
                                    </div>
                                    <div class="wooco_component_content_line">
                                        <div class="wooco_component_content_line_label">
											<?php esc_html_e( 'Description', 'wpc-composite-products' ); ?>
                                        </div>
                                        <div class="wooco_component_content_line_value">
                                            <textarea
                                                    name="wooco_components[desc][]"><?php echo $component['desc']; ?></textarea>
                                        </div>
                                    </div>
                                    <div class="wooco_component_content_line">
                                        <div class="wooco_component_content_line_label">
											<?php esc_html_e( 'Source', 'wpc-composite-products' ); ?>
                                        </div>
                                        <div class="wooco_component_content_line_value">
                                            <select name="wooco_components[type][]" class="wooco_component_type"
                                                    required>
                                                <option value=""><?php esc_html_e( 'Select source', 'wpc-composite-products' ); ?></option>
                                                <option value="categories" <?php echo( $component['type'] === 'categories' ? 'selected' : '' ); ?>>
													<?php esc_html_e( 'Categories', 'wpc-composite-products' ); ?>
                                                </option>
                                                <option value="products" <?php echo( $component['type'] === 'products' ? 'selected' : '' ); ?>>
													<?php esc_html_e( 'Products', 'wpc-composite-products' ); ?>
                                                </option>
                                            </select>
                                            <span class="wooco_hide wooco_show_if_categories">
                                                <span><?php esc_html_e( 'Orderby', 'wpc-composite-products' ); ?> <select
                                                            name="wooco_components[orderby][]">
                                                    <option value="default" <?php echo( $component['orderby'] === 'default' ? 'selected' : '' ); ?>><?php esc_html_e( 'Default', 'wpc-composite-products' ); ?></option>
                                                    <option value="none" <?php echo( $component['orderby'] === 'none' ? 'selected' : '' ); ?>><?php esc_html_e( 'None', 'wpc-composite-products' ); ?></option>
                                                    <option value="ID" <?php echo( $component['orderby'] === 'ID' ? 'selected' : '' ); ?>><?php esc_html_e( 'ID', 'wpc-composite-products' ); ?></option>
                                                    <option value="name" <?php echo( $component['orderby'] === 'name' ? 'selected' : '' ); ?>><?php esc_html_e( 'Name', 'wpc-composite-products' ); ?></option>
                                                    <option value="type" <?php echo( $component['orderby'] === 'type' ? 'selected' : '' ); ?>><?php esc_html_e( 'Type', 'wpc-composite-products' ); ?></option>
                                                    <option value="rand" <?php echo( $component['orderby'] === 'rand' ? 'selected' : '' ); ?>><?php esc_html_e( 'Rand', 'wpc-composite-products' ); ?></option>
                                                    <option value="date" <?php echo( $component['orderby'] === 'date' ? 'selected' : '' ); ?>><?php esc_html_e( 'Date', 'wpc-composite-products' ); ?></option>
                                                    <option value="modified" <?php echo( $component['orderby'] === 'modified' ? 'selected' : '' ); ?>><?php esc_html_e( 'Modified', 'wpc-composite-products' ); ?></option>
                                                </select></span>
                                                <span><?php esc_html_e( 'Order', 'wpc-composite-products' ); ?> <select
                                                            name="wooco_components[order][]">
                                                    <option value="default" <?php echo( $component['order'] === 'default' ? 'selected' : '' ); ?>><?php esc_html_e( 'Default', 'wpc-composite-products' ); ?></option>
                                                    <option value="DESC" <?php echo( $component['order'] === 'DESC' ? 'selected' : '' ); ?>><?php esc_html_e( 'DESC', 'wpc-composite-products' ); ?></option>
                                                    <option value="ASC" <?php echo( $component['order'] === 'ASC' ? 'selected' : '' ); ?>><?php esc_html_e( 'ASC', 'wpc-composite-products' ); ?></option>
                                                    </select></span>
                                                </span>
                                            <div class="wooco_hide wooco_show_if_categories">
                                                <input id="<?php echo $wooco_search_categories_id; ?>"
                                                       class="wooco-category-search-input"
                                                       name="wooco_components[categories][]" type="hidden"
                                                       value="<?php echo $component['categories']; ?>"/>
                                                <select class="wc-category-search wooco-category-search"
                                                        multiple="multiple"
                                                        style="width: 100%;"
                                                        data-placeholder="<?php esc_attr_e( 'Search for a category&hellip;', 'wpc-composite-products' ); ?>">
													<?php
													$wooco_category_ids = explode( ',', $component['categories'] );

													foreach ( $wooco_category_ids as $wooco_category_id ) {
														if ( absint( $wooco_category_id ) > 0 ) {
															$wooco_category = get_term_by( 'id', absint( $wooco_category_id ), 'product_cat' );
														} else {
															$wooco_category = get_term_by( 'slug', $wooco_category_id, 'product_cat' );
														}

														if ( $wooco_category ) {
															echo '<option value="' . esc_attr( $wooco_category_id ) . '" selected="selected">' . wp_kses_post( $wooco_category->name ) . '</option>';
														}
													}
													?>
                                                </select>
                                            </div>
                                            <div class="wooco_hide wooco_show_if_products">
                                                <input id="<?php echo $wooco_search_products_id; ?>"
                                                       class="wooco-product-search-input"
                                                       name="wooco_components[products][]" type="hidden"
                                                       value="<?php echo $component['products']; ?>"/>
                                                <select class="wc-product-search wooco-product-search"
                                                        multiple="multiple"
                                                        style="width: 100%;" data-sortable="1"
                                                        data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'wpc-composite-products' ); ?>"
                                                        data-action="woocommerce_json_search_products_and_variations">
													<?php
													$wooco_product_ids = explode( ',', $component['products'] );

													foreach ( $wooco_product_ids as $wooco_product_id ) {
														$wooco_product = wc_get_product( $wooco_product_id );

														if ( $wooco_product ) {
															echo '<option value="' . esc_attr( $wooco_product_id ) . '" selected="selected">' . wp_kses_post( $wooco_product->get_formatted_name() ) . '</option>';
														}
													}
													?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="wooco_component_content_line">
                                        <div class="wooco_component_content_line_label">
											<?php esc_html_e( 'Default option', 'wpc-composite-products' ); ?>
                                        </div>
                                        <div class="wooco_component_content_line_value">
                                            <input id="<?php echo $wooco_search_default_id; ?>"
                                                   class="wooco-product-search-input"
                                                   name="wooco_components[default][]" type="hidden"
                                                   value="<?php echo $component['default']; ?>"/>
                                            <select class="wc-product-search wooco-product-search"
                                                    style="width: 100%;" data-allow_clear="true"
                                                    data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'wpc-composite-products' ); ?>"
                                                    data-action="woocommerce_json_search_products_and_variations">
												<?php
												if ( ! empty( $component['default'] ) ) {
													$wooco_product_default = wc_get_product( $component['default'] );

													if ( $wooco_product_default ) {
														echo '<option value="' . esc_attr( $component['default'] ) . '" selected="selected">' . wp_kses_post( $wooco_product_default->get_formatted_name() ) . '</option>';
													}
												}
												?>
                                            </select>
                                        </div>
                                    </div>
									<?php echo '<script>jQuery(document.body).trigger( \'wc-enhanced-select-init\' );</script>'; ?>
                                    <div class="wooco_component_content_line">
                                        <div class="wooco_component_content_line_label">
											<?php esc_html_e( 'Required', 'wpc-composite-products' ); ?>
                                        </div>
                                        <div class="wooco_component_content_line_value">
                                            <select name="wooco_components[optional][]">
                                                <option value="no" <?php echo( $component['optional'] === 'no' ? 'selected' : '' ); ?>>
													<?php esc_html_e( 'Yes', 'wpc-composite-products' ); ?>
                                                </option>
                                                <option value="yes" <?php echo( $component['optional'] === 'yes' ? 'selected' : '' ); ?>>
													<?php esc_html_e( 'No', 'wpc-composite-products' ); ?>
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="wooco_component_content_line">
                                        <div class="wooco_component_content_line_label">
											<?php esc_html_e( 'Quantity', 'wpc-composite-products' ); ?>
                                        </div>
                                        <div class="wooco_component_content_line_value">
                                            <input name="wooco_components[qty][]" type="number" min="0"
                                                   step="<?php echo esc_attr( $wooco_step ); ?>"
                                                   value="<?php echo esc_attr( $component['qty'] ); ?>" required/>
                                        </div>
                                    </div>
                                    <div class="wooco_component_content_line">
                                        <div class="wooco_component_content_line_label">
											<?php esc_html_e( 'Custom quantity', 'wpc-composite-products' ); ?>
                                        </div>
                                        <div class="wooco_component_content_line_value">
                                            <select name="wooco_components[custom_qty][]">
                                                <option value="no" <?php echo( $component['custom_qty'] === 'no' ? 'selected' : '' ); ?>>
													<?php esc_html_e( 'No', 'wpc-composite-products' ); ?>
                                                </option>
                                                <option value="yes" <?php echo( $component['custom_qty'] === 'yes' ? 'selected' : '' ); ?>>
													<?php esc_html_e( 'Yes', 'wpc-composite-products' ); ?>
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="wooco_component_content_line">
                                        <div class="wooco_component_content_line_label">
											<?php esc_html_e( 'New price', 'wpc-composite-products' ); ?>
                                        </div>
                                        <div class="wooco_component_content_line_value">
                                            <input name="wooco_components[price][]" type="text"
                                                   style="width: 60px; display: inline-block"
                                                   value="<?php echo $this->wooco_format_price( $component['price'] ); ?>"/>
                                            <span class="woocommerce-help-tip"
                                                  data-tip="<?php esc_html_e( 'Set a new price using a number (eg. "49" for $49) or a percentage (eg. "90%" of the original price).', 'wpc-composite-products' ); ?>"></span>
                                        </div>
                                    </div>
                                    <div class="wooco_component_content_line">
                                        <div class="wooco_component_content_line_label">
											<?php esc_html_e( 'Min', 'wpc-composite-products' ); ?>
                                        </div>
                                        <div class="wooco_component_content_line_value">
                                            <input name="wooco_components[min][]" type="number" min="0"
                                                   step="<?php echo esc_attr( $wooco_step ); ?>"
                                                   value="<?php echo esc_attr( $component['min'] ); ?>"/>
                                        </div>
                                    </div>
                                    <div class="wooco_component_content_line">
                                        <div class="wooco_component_content_line_label">
											<?php esc_html_e( 'Max', 'wpc-composite-products' ); ?>
                                        </div>
                                        <div class="wooco_component_content_line_value">
                                            <input name="wooco_components[max][]" type="number" min="0"
                                                   step="<?php echo esc_attr( $wooco_step ); ?>"
                                                   value="<?php echo esc_attr( $component['max'] ); ?>"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
				<?php }

				function wooco_admin_menu() {
					add_submenu_page( 'wpclever', esc_html__( 'WPC Composite Products', 'wpc-composite-products' ), esc_html__( 'Composite Products', 'wpc-composite-products' ), 'manage_options', 'wpclever-wooco', array(
						&$this,
						'wooco_admin_menu_content'
					) );
				}

				function wooco_admin_menu_content() {
					add_thickbox();
					$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';
					?>
                    <div class="wpclever_settings_page wrap">
                        <h1 class="wpclever_settings_page_title"><?php echo esc_html__( 'WPC Composite Products', 'wpc-composite-products' ) . ' ' . WOOCO_VERSION; ?></h1>
                        <div class="wpclever_settings_page_desc about-text">
                            <p>
								<?php printf( esc_html__( 'Thank you for using our plugin! If you are satisfied, please reward it a full five-star %s rating.', 'wpc-composite-products' ), '<span style="color:#ffb900">&#9733;&#9733;&#9733;&#9733;&#9733;</span>' ); ?>
                                <br/>
                                <a href="<?php echo esc_url( WOOCO_REVIEWS ); ?>"
                                   target="_blank"><?php esc_html_e( 'Reviews', 'wpc-composite-products' ); ?></a> | <a
                                        href="<?php echo esc_url( WOOCO_CHANGELOG ); ?>"
                                        target="_blank"><?php esc_html_e( 'Changelog', 'wpc-composite-products' ); ?></a>
                                | <a href="<?php echo esc_url( WOOCO_DISCUSSION ); ?>"
                                     target="_blank"><?php esc_html_e( 'Discussion', 'wpc-composite-products' ); ?></a>
                            </p>
                        </div>
                        <div class="wpclever_settings_page_nav">
                            <h2 class="nav-tab-wrapper">
                                <a href="<?php echo admin_url( 'admin.php?page=wpclever-wooco&tab=how' ); ?>"
                                   class="<?php echo $active_tab === 'how' ? 'nav-tab nav-tab-active' : 'nav-tab'; ?>">
									<?php esc_html_e( 'How to use?', 'wpc-composite-products' ); ?>
                                </a>
                                <a href="<?php echo admin_url( 'admin.php?page=wpclever-wooco&tab=settings' ); ?>"
                                   class="<?php echo $active_tab === 'settings' ? 'nav-tab nav-tab-active' : 'nav-tab'; ?>">
									<?php esc_html_e( 'Settings', 'wpc-composite-products' ); ?>
                                </a>
                                <a href="<?php echo admin_url( 'admin.php?page=wpclever-wooco&tab=premium' ); ?>"
                                   class="<?php echo $active_tab === 'premium' ? 'nav-tab nav-tab-active' : 'nav-tab'; ?>">
									<?php esc_html_e( 'Premium Version', 'wpc-composite-products' ); ?>
                                </a>
                            </h2>
                        </div>
                        <div class="wpclever_settings_page_content">
							<?php if ( $active_tab === 'how' ) { ?>
                                <div class="wpclever_settings_page_content_text">
                                    <p>
										<?php esc_html_e( 'When creating the product, please choose product data is "Composite product" then you can see the search field to start search and add component products.', 'wpc-composite-products' ); ?>
                                    </p>
                                    <p>
                                        <img src="<?php echo WOOCO_URI; ?>assets/images/how-01.jpg"/>
                                    </p>
                                </div>
								<?php
							} elseif ( $active_tab === 'settings' ) {
								$price_format             = get_option( '_wooco_price_format', 'from_regular' );
								$selector                 = get_option( '_wooco_selector', 'ddslick' );
								$exclude_unpurchasable    = get_option( '_wooco_exclude_unpurchasable', 'yes' );
								$show_alert               = get_option( '_wooco_show_alert', 'load' );
								$show_qty                 = get_option( '_wooco_show_qty', 'yes' );
								$show_image               = get_option( '_wooco_show_image', 'yes' );
								$show_price               = get_option( '_wooco_show_price', 'yes' );
								$option_none              = get_option( '_wooco_option_none', '' );
								$total_text               = get_option( '_wooco_total_text', '' );
								$saved_text               = get_option( '_wooco_saved_text', '' );
								$change_price             = get_option( '_wooco_change_price', 'yes' );
								$change_price_custom      = get_option( '_wooco_change_price_custom', '.summary > .price' );
								$product_link             = get_option( '_wooco_product_link', 'no' );
								$archive_button_select    = get_option( '_wooco_archive_button_select' );
								$archive_button_read      = get_option( '_wooco_archive_button_read' );
								$single_button_add        = get_option( '_wooco_single_button_add' );
								$coupon_restrictions      = get_option( '_wooco_coupon_restrictions', 'no' );
								$cart_contents_count      = get_option( '_wooco_cart_contents_count', 'composite' );
								$hide_composite_name      = get_option( '_wooco_hide_composite_name', 'no' );
								$hide_component           = get_option( '_wooco_hide_component', 'no' );
								$hide_component_mini_cart = get_option( '_wooco_hide_component_mini_cart', 'no' );
								?>
                                <form method="post" action="options.php">
									<?php wp_nonce_field( 'update-options' ) ?>
                                    <table class="form-table">
                                        <tr class="heading">
                                            <th colspan="2">
												<?php esc_html_e( 'General', 'wpc-composite-products' ); ?>
                                            </th>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Price format', 'wpc-composite-products' ); ?></th>
                                            <td>
                                                <select name="_wooco_price_format">
                                                    <option value="from_regular" <?php echo esc_attr( $price_format === 'from_regular' ? 'selected' : '' ); ?>><?php esc_html_e( 'From regular price', 'wpc-composite-products' ); ?></option>
                                                    <option value="from_sale" <?php echo esc_attr( $price_format === 'from_sale' ? 'selected' : '' ); ?>><?php esc_html_e( 'From sale price', 'wpc-composite-products' ); ?></option>
                                                    <option value="normal" <?php echo esc_attr( $price_format === 'normal' ? 'selected' : '' ); ?>><?php esc_html_e( 'Regular and sale price', 'wpc-composite-products' ); ?></option>
                                                </select>
                                                <span class="description">
                                                    <?php esc_html_e( 'Choose a price format for composites on the archive page.', 'wpc-composite-products' ); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Selector interface', 'wpc-composite-products' ); ?></th>
                                            <td>
                                                <select name="_wooco_selector">
                                                    <option value="ddslick" <?php echo esc_attr( $selector === 'ddslick' ? 'selected' : '' ); ?>><?php esc_html_e( 'ddSlick', 'wpc-composite-products' ); ?></option>
                                                    <option value="select2" <?php echo esc_attr( $selector === 'select2' ? 'selected' : '' ); ?>><?php esc_html_e( 'Select2', 'wpc-composite-products' ); ?></option>
                                                    <option value="select" <?php echo esc_attr( $selector === 'select' ? 'selected' : '' ); ?>><?php esc_html_e( 'HTML select tag', 'wpc-composite-products' ); ?></option>
                                                </select>
                                                <span class="description">
                                                    Read more about <a href="https://designwithpc.com/Plugins/ddSlick"
                                                                       target="_blank">ddSlick</a>, <a
                                                            href="https://select2.org/" target="_blank">Select2</a> and <a
                                                            href="https://www.w3schools.com/tags/tag_select.asp"
                                                            target="_blank">HTML select tag</a>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Exclude unpurchasable', 'wpc-composite-products' ); ?></th>
                                            <td>
                                                <select name="_wooco_exclude_unpurchasable">
                                                    <option value="yes" <?php echo esc_attr( $exclude_unpurchasable === 'yes' ? 'selected' : '' ); ?>><?php esc_html_e( 'Yes', 'wpc-composite-products' ); ?></option>
                                                    <option value="no" <?php echo esc_attr( $exclude_unpurchasable === 'no' ? 'selected' : '' ); ?>><?php esc_html_e( 'No', 'wpc-composite-products' ); ?></option>
                                                </select>
                                                <span class="description"><?php esc_html_e( 'Exclude unpurchasable products from the list.', 'wpc-composite-products' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Show alert', 'wpc-composite-products' ); ?></th>
                                            <td>
                                                <select name="_wooco_show_alert">
                                                    <option value="load" <?php echo esc_attr( $show_alert === 'load' ? 'selected' : '' ); ?>><?php esc_html_e( 'On composite loaded', 'wpc-composite-products' ); ?></option>
                                                    <option value="change" <?php echo esc_attr( $show_alert === 'change' ? 'selected' : '' ); ?>><?php esc_html_e( 'On composite changing', 'wpc-composite-products' ); ?></option>
                                                    <option value="no" <?php echo esc_attr( $show_alert === 'no' ? 'selected' : '' ); ?>><?php esc_html_e( 'No, always hide the alert', 'wpc-composite-products' ); ?></option>
                                                </select>
                                                <span class="description"><?php esc_html_e( 'Show the inline alert under the components.', 'wpc-composite-products' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Show quantity', 'wpc-composite-products' ); ?></th>
                                            <td>
                                                <select name="_wooco_show_qty">
                                                    <option value="yes" <?php echo esc_attr( $show_qty === 'yes' ? 'selected' : '' ); ?>><?php esc_html_e( 'Yes', 'wpc-composite-products' ); ?></option>
                                                    <option value="no" <?php echo esc_attr( $show_qty === 'no' ? 'selected' : '' ); ?>><?php esc_html_e( 'No', 'wpc-composite-products' ); ?></option>
                                                </select>
                                                <span class="description"><?php esc_html_e( 'Show the quantity before product name.', 'wpc-composite-products' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Show image', 'wpc-composite-products' ); ?></th>
                                            <td>
                                                <select name="_wooco_show_image">
                                                    <option value="yes" <?php echo esc_attr( $show_image === 'yes' ? 'selected' : '' ); ?>><?php esc_html_e( 'Yes', 'wpc-composite-products' ); ?></option>
                                                    <option value="no" <?php echo esc_attr( $show_image === 'no' ? 'selected' : '' ); ?>><?php esc_html_e( 'No', 'wpc-composite-products' ); ?></option>
                                                </select>
                                                <span class="description"><?php esc_html_e( 'Only for HTML select tag.', 'wpc-composite-products' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Show price', 'wpc-composite-products' ); ?></th>
                                            <td>
                                                <select name="_wooco_show_price">
                                                    <option value="yes" <?php echo esc_attr( $show_price === 'yes' ? 'selected' : '' ); ?>><?php esc_html_e( 'Yes', 'wpc-composite-products' ); ?></option>
                                                    <option value="no" <?php echo esc_attr( $show_price === 'no' ? 'selected' : '' ); ?>><?php esc_html_e( 'No', 'wpc-composite-products' ); ?></option>
                                                </select>
                                                <span class="description"><?php esc_html_e( 'Only for HTML select tag.', 'wpc-composite-products' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Option none', 'wpc-composite-products' ); ?></th>
                                            <td>
                                                <input type="text" name="_wooco_option_none"
                                                       value="<?php echo esc_attr( $option_none ); ?>"
                                                       placeholder="<?php esc_attr_e( 'No, thanks. I don\'t need this', 'wpc-composite-products' ); ?>"/>
                                                <span class="description"><?php esc_html_e( 'Text to display for showing a "Don\'t choose any product" option.', 'wpc-composite-products' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Total text', 'wpc-composite-products' ); ?></th>
                                            <td>
                                                <input type="text" name="_wooco_total_text"
                                                       value="<?php echo esc_attr( $total_text ); ?>"
                                                       placeholder="<?php esc_attr_e( 'Total price:', 'wpc-composite-products' ); ?>"/>
                                                <span class="description">
											<?php esc_html_e( 'Leave blank to use the default text and its equivalent translation in multiple languages.', 'wpc-composite-products' ); ?>
										</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Saved text', 'wpc-composite-products' ); ?></th>
                                            <td>
                                                <input type="text" name="_wooco_saved_text"
                                                       value="<?php echo esc_attr( $saved_text ); ?>"
                                                       placeholder="<?php esc_attr_e( '(saved [d])', 'wpc-composite-products' ); ?>"/>
                                                <span class="description">
											<?php esc_html_e( 'Leave blank to use the default text and its equivalent translation in multiple languages.', 'wpc-composite-products' ); ?>
										</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Change price', 'wpc-composite-products' ); ?></th>
                                            <td>
                                                <select name="_wooco_change_price">
                                                    <option
                                                            value="yes" <?php echo esc_attr( $change_price === 'yes' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Yes', 'wpc-composite-products' ); ?>
                                                    </option>
                                                    <option
                                                            value="yes_custom" <?php echo esc_attr( $change_price === 'yes_custom' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Yes, custom selector', 'wpc-composite-products' ); ?>
                                                    </option>
                                                    <option
                                                            value="no" <?php echo esc_attr( $change_price === 'no' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'No', 'wpc-composite-products' ); ?>
                                                    </option>
                                                </select>
                                                <input type="text" name="_wooco_change_price_custom"
                                                       value="<?php echo esc_attr( $change_price_custom ); ?>"
                                                       placeholder=".summary > .price"/>
                                                <span class="description">
											<?php esc_html_e( 'Change the main product’s price based on the changes in prices of selected variations in a grouped products. This uses Javascript to change the main product’s price to it depends heavily on theme’s HTML. If the price doesn\'t change when this option is enabled, please contact us and we can help you adjust the JS file. ', 'wpc-composite-products' ); ?>
										</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Link to individual product', 'wpc-composite-products' ); ?></th>
                                            <td>
                                                <select name="_wooco_product_link">
                                                    <option
                                                            value="yes" <?php echo esc_attr( $product_link === 'yes' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Yes, open product page', 'wpc-composite-products' ); ?>
                                                    </option>
                                                    <option
                                                            value="yes_popup" <?php echo esc_attr( $product_link === 'yes_popup' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Yes, open quick view popup', 'wpc-composite-products' ); ?>
                                                    </option>
                                                    <option
                                                            value="no" <?php echo esc_attr( $product_link === 'no' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'No', 'wpc-composite-products' ); ?>
                                                    </option>
                                                </select> <span class="description">
											<?php esc_html_e( 'Add a link to the target individual product below this selection.', 'wpc-composite-products' ); ?> If you choose "Open quick view popup", please install <a
                                                            href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=woo-smart-quick-view&TB_iframe=true&width=800&height=550' ) ); ?>"
                                                            class="thickbox" title="Install WPC Smart Quick View">WPC Smart Quick View</a> to make it work.
										</span>
                                            </td>
                                        </tr>
                                        <tr class="heading">
                                            <th>
												<?php esc_html_e( '"Add to Cart" button labels', 'wpc-composite-products' ); ?>
                                            </th>
                                            <td>
												<?php esc_html_e( 'Leave blank to use the default text and its equivalent translation in multiple languages.', 'wpc-composite-products' ); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Archive/shop page', 'wpc-composite-products' ); ?></th>
                                            <td>
                                                <input type="text" name="_wooco_archive_button_select"
                                                       value="<?php echo esc_attr( $archive_button_select ); ?>"
                                                       placeholder="<?php esc_attr_e( 'Select options', 'wpc-composite-products' ); ?>"/>
                                                <span class="description">
											<?php esc_html_e( 'For purchasable composites.', 'wpc-composite-products' ); ?>
										</span><br/>
                                                <input type="text" name="_wooco_archive_button_read"
                                                       value="<?php echo esc_attr( $archive_button_read ); ?>"
                                                       placeholder="<?php esc_attr_e( 'Read more', 'wpc-composite-products' ); ?>"/>
                                                <span class="description">
											<?php esc_html_e( 'For unpurchasable composites.', 'wpc-composite-products' ); ?>
										</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Single product page', 'wpc-composite-products' ); ?></th>
                                            <td>
                                                <input type="text" name="_wooco_single_button_add"
                                                       value="<?php echo esc_attr( $single_button_add ); ?>"
                                                       placeholder="<?php esc_attr_e( 'Add to cart', 'wpc-composite-products' ); ?>"/>
                                            </td>
                                        </tr>
                                        <tr class="heading">
                                            <th colspan="2">
												<?php esc_html_e( 'Cart & Checkout', 'wpc-composite-products' ); ?>
                                            </th>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Coupon restrictions', 'wpc-composite-products' ); ?></th>
                                            <td>
                                                <select name="_wooco_coupon_restrictions">
                                                    <option
                                                            value="no" <?php echo esc_attr( $coupon_restrictions === 'no' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'No', 'wpc-composite-products' ); ?>
                                                    </option>
                                                    <option
                                                            value="composite" <?php echo esc_attr( $coupon_restrictions === 'composite' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Exclude composite', 'wpc-composite-products' ); ?>
                                                    </option>
                                                    <option
                                                            value="component" <?php echo esc_attr( $coupon_restrictions === 'component' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Exclude component products', 'wpc-composite-products' ); ?>
                                                    </option>
                                                    <option
                                                            value="both" <?php echo esc_attr( $coupon_restrictions === 'both' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Exclude both composite and component products', 'wpc-composite-products' ); ?>
                                                    </option>
                                                </select>
                                                <span class="description">
											<?php esc_html_e( 'Choose products you want to exclude from coupons.', 'wpc-composite-products' ); ?>
										</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Cart content count', 'wpc-composite-products' ); ?></th>
                                            <td>
                                                <select name="_wooco_cart_contents_count">
                                                    <option
                                                            value="composite" <?php echo esc_attr( $cart_contents_count === 'composite' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Composite only', 'wpc-composite-products' ); ?>
                                                    </option>
                                                    <option
                                                            value="component_products" <?php echo esc_attr( $cart_contents_count === 'component_products' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Component products only', 'wpc-composite-products' ); ?>
                                                    </option>
                                                    <option
                                                            value="both" <?php echo esc_attr( $cart_contents_count === 'both' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Both composite and component products', 'wpc-composite-products' ); ?>
                                                    </option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Hide composite name before component products', 'wpc-composite-products' ); ?></th>
                                            <td>
                                                <select name="_wooco_hide_composite_name">
                                                    <option
                                                            value="yes" <?php echo esc_attr( $hide_composite_name === 'yes' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Yes', 'wpc-composite-products' ); ?>
                                                    </option>
                                                    <option
                                                            value="no" <?php echo esc_attr( $hide_composite_name === 'no' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'No', 'wpc-composite-products' ); ?>
                                                    </option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Hide component products on cart & checkout page', 'wpc-composite-products' ); ?></th>
                                            <td>
                                                <select name="_wooco_hide_component">
                                                    <option
                                                            value="yes" <?php echo esc_attr( $hide_component === 'yes' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Yes, just show the composite', 'wpc-composite-products' ); ?>
                                                    </option>
                                                    <option
                                                            value="yes_text" <?php echo esc_attr( $hide_component === 'yes_text' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Yes, but show component product names under the composite', 'wpc-composite-products' ); ?>
                                                    </option>
                                                    <option
                                                            value="no" <?php echo esc_attr( $hide_component === 'no' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'No', 'wpc-composite-products' ); ?>
                                                    </option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Hide component products on mini-cart', 'wpc-composite-products' ); ?></th>
                                            <td>
                                                <select name="_wooco_hide_component_mini_cart">
                                                    <option
                                                            value="yes" <?php echo esc_attr( $hide_component_mini_cart === 'yes' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Yes', 'wpc-composite-products' ); ?>
                                                    </option>
                                                    <option
                                                            value="no" <?php echo esc_attr( $hide_component_mini_cart === 'no' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'No', 'wpc-composite-products' ); ?>
                                                    </option>
                                                </select>
                                                <span class="description">
											<?php esc_html_e( 'Hide component products, just show the main composite on mini-cart.', 'wpc-composite-products' ); ?>
										</span>
                                            </td>
                                        </tr>
                                        <tr class="submit">
                                            <th colspan="2">
                                                <input type="submit" name="submit" class="button button-primary"
                                                       value="<?php esc_attr_e( 'Update Options', 'wpc-composite-products' ); ?>"/>
                                                <input type="hidden" name="action" value="update"/>
                                                <input type="hidden" name="page_options"
                                                       value="_wooco_price_format,_wooco_selector,_wooco_exclude_unpurchasable,_wooco_show_alert,_wooco_show_qty,_wooco_show_image,_wooco_show_price,_wooco_option_none,_wooco_coupon_restrictions,_wooco_cart_contents_count,_wooco_hide_composite_name,_wooco_hide_component,_wooco_hide_component_mini_cart,_wooco_total_text,_wooco_saved_text,_wooco_change_price,_wooco_change_price_custom,_wooco_product_link,_wooco_archive_button_select,_wooco_archive_button_read,_wooco_single_button_add"/>
                                            </th>
                                        </tr>
                                    </table>
                                </form>
							<?php } elseif ( $active_tab == 'premium' ) { ?>
                                <div class="wpclever_settings_page_content_text">
                                    <p>
                                        Get the Premium Version just $29! <a
                                                href="https://wpclever.net/downloads/wpc-composite-products-for-woocommerce?utm_source=pro&utm_medium=wooco&utm_campaign=wporg"
                                                target="_blank">https://wpclever.net/downloads/wpc-composite-products-for-woocommerce</a>
                                    </p>
                                    <p><strong>Extra features for Premium Version:</strong></p>
                                    <ul style="margin-bottom: 0">
                                        <li>- Add more than 3 components.</li>
                                        <li>- Get the lifetime update & premium support.</li>
                                    </ul>
                                </div>
							<?php } ?>
                        </div>
                    </div>
					<?php
				}

				function wooco_wp_enqueue_scripts() {
					$total_text = get_option( '_wooco_total_text' );
					$saved_text = get_option( '_wooco_saved_text' );

					if ( empty( $total_text ) ) {
						$total_text = esc_html__( 'Total price:', 'wpc-composite-products' );
					}

					if ( empty( $saved_text ) ) {
						$saved_text = esc_html__( '(saved [d])', 'wpc-composite-products' );
					}

					wp_enqueue_style( 'wooco-frontend', WOOCO_URI . 'assets/css/frontend.css' );

					if ( get_option( '_wooco_selector', 'ddslick' ) === 'ddslick' ) {
						wp_enqueue_script( 'ddslick', WOOCO_URI . 'assets/libs/ddslick/jquery.ddslick.min.js', array( 'jquery' ), WOOCO_VERSION, true );
					}

					if ( get_option( '_wooco_selector', 'ddslick' ) === 'select2' ) {
						wp_enqueue_style( 'select2' );
						wp_enqueue_script( 'select2', WC()->plugin_url() . '/assets/js/select2/select2.full.min.js', array( 'jquery' ), WOOCO_VERSION, true );
					}

					wp_enqueue_script( 'wooco-frontend', WOOCO_URI . 'assets/js/frontend.js', array( 'jquery' ), WOOCO_VERSION, true );
					wp_localize_script( 'wooco-frontend', 'wooco_vars', array(
							'total_text'               => $total_text,
							'saved_text'               => $saved_text,
							'selector'                 => get_option( '_wooco_selector', 'ddslick' ),
							'change_price'             => get_option( '_wooco_change_price', 'yes' ),
							'price_selector'           => get_option( '_wooco_change_price_custom', '' ),
							'product_link'             => get_option( '_wooco_product_link', 'no' ),
							'show_alert'               => get_option( '_wooco_show_alert', 'load' ),
							'alert_min'                => esc_html__( 'Please choose at least [min] of the whole products before adding to the cart.', 'wpc-composite-products' ),
							'alert_max'                => esc_html__( 'Please choose maximum [max] of the whole products before adding to the cart.', 'wpc-composite-products' ),
							'alert_selection'          => esc_html__( 'Please select a purchasable product in the component [name] before adding to the cart.', 'wpc-composite-products' ),
							'wrap_selector'            => apply_filters( 'wooco_wrap_selector', '.product-type-composite' ),
							'price_format'             => get_woocommerce_price_format(),
							'price_decimals'           => wc_get_price_decimals(),
							'price_thousand_separator' => wc_get_price_thousand_separator(),
							'price_decimal_separator'  => wc_get_price_decimal_separator(),
							'currency_symbol'          => get_woocommerce_currency_symbol()
						)
					);
				}

				function wooco_admin_enqueue_scripts() {
					wp_enqueue_style( 'wooco-backend', WOOCO_URI . 'assets/css/backend.css' );
					wp_enqueue_script( 'dragarrange', WOOCO_URI . 'assets/js/drag-arrange.js', array( 'jquery' ), WOOCO_VERSION, true );
					wp_enqueue_script( 'wooco-backend', WOOCO_URI . 'assets/js/backend.js', array( 'jquery' ), WOOCO_VERSION, true );
				}

				function wooco_action_links( $links, $file ) {
					static $plugin;

					if ( ! isset( $plugin ) ) {
						$plugin = plugin_basename( __FILE__ );
					}

					if ( $plugin === $file ) {
						$settings_link = '<a href="' . admin_url( 'admin.php?page=wpclever-wooco&tab=settings' ) . '">' . esc_html__( 'Settings', 'wpc-composite-products' ) . '</a>';
						$links[]       = '<a href="' . admin_url( 'admin.php?page=wpclever-wooco&tab=premium' ) . '">' . esc_html__( 'Premium Version', 'wpc-composite-products' ) . '</a>';
						array_unshift( $links, $settings_link );
					}

					return (array) $links;
				}

				function wooco_row_meta( $links, $file ) {
					static $plugin;

					if ( ! isset( $plugin ) ) {
						$plugin = plugin_basename( __FILE__ );
					}

					if ( $plugin === $file ) {
						$row_meta = array(
							'support' => '<a href="https://wpclever.net/support?utm_source=support&utm_medium=wooco&utm_campaign=wporg" target="_blank">' . esc_html__( 'Premium support', 'wpc-composite-products' ) . '</a>',
						);

						return array_merge( $links, $row_meta );
					}

					return (array) $links;
				}

				function wooco_cart_contents_count( $count ) {
					$cart_contents_count = get_option( '_wooco_cart_contents_count', 'composite' );

					if ( $cart_contents_count !== 'both' ) {
						$cart_contents = WC()->cart->cart_contents;

						foreach ( $cart_contents as $cart_item_key => $cart_item ) {
							if ( ( $cart_contents_count === 'component_products' ) && ! empty( $cart_item['wooco_ids'] ) ) {
								$count -= $cart_item['quantity'];
							}

							if ( ( $cart_contents_count === 'composite' ) && ! empty( $cart_item['wooco_parent_id'] ) ) {
								$count -= $cart_item['quantity'];
							}
						}
					}

					return $count;
				}

				function wooco_cart_item_name( $name, $item ) {
					if ( isset( $item['wooco_parent_id'] ) && ! empty( $item['wooco_parent_id'] ) && ( get_option( '_wooco_hide_composite_name', 'no' ) === 'no' ) ) {
						if ( strpos( $name, '</a>' ) !== false ) {
							return '<a href="' . get_permalink( $item['wooco_parent_id'] ) . '">' . get_the_title( $item['wooco_parent_id'] ) . '</a> &rarr; ' . $name;
						}

						return get_the_title( $item['wooco_parent_id'] ) . ' &rarr; ' . strip_tags( $name );

					}

					return $name;
				}

				function wooco_order_formatted_line_subtotal( $subtotal, $item ) {
					if ( ! empty( $item['wooco_ids'] ) && isset( $item['wooco_price'] ) && ( $item['wooco_price'] !== '' ) ) {
						return wc_price( $item['wooco_price'] * $item['quantity'] );
					}

					return $subtotal;
				}

				function wooco_cart_item_price( $price, $cart_item ) {
					if ( isset( $cart_item['wooco_ids'], $cart_item['wooco_keys'] ) && method_exists( $cart_item['data'], 'get_pricing' ) && ( $cart_item['data']->get_pricing() !== 'only' ) ) {
						// composite
						$wooco_price = $cart_item['data']->get_pricing() === 'include' ? wc_get_price_to_display( $cart_item['data'] ) : 0;

						foreach ( $cart_item['wooco_keys'] as $cart_item_key ) {
							if ( isset( WC()->cart->cart_contents[ $cart_item_key ] ) ) {
								$wooco_price += wc_get_price_to_display( WC()->cart->cart_contents[ $cart_item_key ]['data'], array( 'qty' => WC()->cart->cart_contents[ $cart_item_key ]['wooco_qty'] ) );
							}
						}

						return wc_price( $wooco_price );
					}

					if ( isset( $cart_item['wooco_parent_key'] ) ) {
						// component products
						$cart_item_key = $cart_item['wooco_parent_key'];

						if ( isset( WC()->cart->cart_contents[ $cart_item_key ] ) && method_exists( WC()->cart->cart_contents[ $cart_item_key ]['data'], 'get_pricing' ) && ( WC()->cart->cart_contents[ $cart_item_key ]['data']->get_pricing() === 'only' ) ) {
							$item_product = wc_get_product( $cart_item['data']->get_id() );

							return wc_price( wc_get_price_to_display( $item_product ) );
						}
					}

					return $price;
				}

				function wooco_cart_item_subtotal( $subtotal, $cart_item = null ) {
					if ( isset( $cart_item['wooco_ids'], $cart_item['wooco_keys'] ) && method_exists( $cart_item['data'], 'get_pricing' ) && ( $cart_item['data']->get_pricing() !== 'only' ) ) {
						// composite
						$wooco_price = $cart_item['data']->get_pricing() === 'include' ? wc_get_price_to_display( $cart_item['data'] ) : 0;

						foreach ( $cart_item['wooco_keys'] as $wooco_key ) {
							if ( isset( WC()->cart->cart_contents[ $wooco_key ] ) ) {
								$wooco_price += wc_get_price_to_display( WC()->cart->cart_contents[ $wooco_key ]['data'], array( 'qty' => WC()->cart->cart_contents[ $wooco_key ]['wooco_qty'] ) );
							}
						}

						return wc_price( $wooco_price * $cart_item['quantity'] );
					}

					if ( isset( $cart_item['wooco_parent_key'] ) ) {
						// component products
						$cart_item_key = $cart_item['wooco_parent_key'];

						if ( isset( WC()->cart->cart_contents[ $cart_item_key ] ) && method_exists( WC()->cart->cart_contents[ $cart_item_key ]['data'], 'get_pricing' ) && ( WC()->cart->cart_contents[ $cart_item_key ]['data']->get_pricing() === 'only' ) ) {
							$item_product = wc_get_product( $cart_item['data']->get_id() );

							return wc_price( wc_get_price_to_display( $item_product, array( 'qty' => $cart_item['quantity'] ) ) );
						}
					}

					return $subtotal;
				}

				function wooco_update_cart_item_quantity( $cart_item_key, $quantity = 0 ) {
					if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['wooco_keys'] ) ) {
						foreach ( WC()->cart->cart_contents[ $cart_item_key ]['wooco_keys'] as $wooco_key ) {
							if ( isset( WC()->cart->cart_contents[ $wooco_key ] ) ) {
								if ( $quantity <= 0 ) {
									$wooco_qty = 0;
								} else {
									$wooco_qty = $quantity * ( WC()->cart->cart_contents[ $wooco_key ]['wooco_qty'] ?: 1 );
								}

								WC()->cart->set_quantity( $wooco_key, $wooco_qty, false );
							}
						}
					}
				}

				function wooco_cart_item_removed( $cart_item_key, $cart ) {
					if ( isset( $cart->removed_cart_contents[ $cart_item_key ]['wooco_keys'] ) ) {
						$wooco_keys = $cart->removed_cart_contents[ $cart_item_key ]['wooco_keys'];

						foreach ( $wooco_keys as $wooco_key ) {
							$cart->remove_cart_item( $wooco_key );
						}
					}
				}

				function wooco_check_in_cart( $product_id ) {
					foreach ( WC()->cart->get_cart() as $cart_item ) {
						if ( $cart_item['product_id'] === $product_id ) {
							return true;
						}
					}

					return false;
				}

				function wooco_add_cart_item_data( $cart_item_data, $product_id ) {
					if ( get_post_meta( $product_id, 'wooco_components', true ) ) {
						$wooco_ids = '';

						if ( isset( $_POST['wooco_ids'] ) ) {
							$wooco_ids = $_POST['wooco_ids'];
							unset( $_POST['wooco_ids'] );
						}

						$wooco_ids = $this->wooco_clean_ids( $wooco_ids );

						if ( ! empty( $wooco_ids ) ) {
							$cart_item_data['wooco_ids'] = $wooco_ids;
						}
					}

					return $cart_item_data;
				}

				function wooco_add_to_cart_validation( $passed, $product_id ) {
					$wooco_ids     = '';
					$wooco_product = wc_get_product( $product_id );

					if ( $wooco_product && $wooco_product->is_type( 'composite' ) ) {
						if ( isset( $_POST['wooco_ids'] ) ) {
							$wooco_ids = $_POST['wooco_ids'];
						}

						$wooco_ids = $this->wooco_clean_ids( $wooco_ids );
						$wooco_qty = isset( $_POST['quantity'] ) ? (int) $_POST['quantity'] : 1;

						if ( $wooco_items = $this->wooco_get_items( $wooco_ids ) ) {
							foreach ( $wooco_items as $wooco_item ) {
								$wooco_product = wc_get_product( $wooco_item['id'] );

								if ( ! $wooco_product ) {
									wc_add_notice( esc_html__( 'One of the component products is unavailable.', 'wpc-composite-products' ), 'error' );
									wc_add_notice( esc_html__( 'You cannot add this composite products to the cart.', 'wpc-composite-products' ), 'error' );

									return false;
								}

								if ( $wooco_product->is_type( 'variable' ) || $wooco_product->is_type( 'composite' ) ) {
									wc_add_notice( sprintf( esc_html__( '"%s" is un-purchasable.', 'wpc-composite-products' ), esc_html( $wooco_product->get_name() ) ), 'error' );
									wc_add_notice( esc_html__( 'You cannot add this composite products to the cart.', 'wpc-composite-products' ), 'error' );

									return false;
								}

								if ( ! $wooco_product->is_in_stock() || ! $wooco_product->is_purchasable() ) {
									wc_add_notice( sprintf( esc_html__( '"%s" is un-purchasable.', 'wpc-composite-products' ), esc_html( $wooco_product->get_name() ) ), 'error' );
									wc_add_notice( esc_html__( 'You cannot add this composite products to the cart.', 'wpc-composite-products' ), 'error' );

									return false;
								}

								if ( ! $wooco_product->has_enough_stock( $wooco_item['qty'] * $wooco_qty ) ) {
									wc_add_notice( sprintf( esc_html__( '"%s" has not enough stock.', 'wpc-composite-products' ), esc_html( $wooco_product->get_name() ) ), 'error' );
									wc_add_notice( esc_html__( 'You cannot add this composite products to the cart.', 'wpc-composite-products' ), 'error' );

									return false;
								}

								if ( $wooco_product->is_sold_individually() && $this->wooco_check_in_cart( $wooco_item['id'] ) ) {
									wc_add_notice( sprintf( esc_html__( 'You cannot add another "%s" to your cart.', 'wpc-composite-products' ), esc_html( $wooco_product->get_name() ) ), 'error' );
									wc_add_notice( esc_html__( 'You cannot add this composite products to the cart.', 'wpc-composite-products' ), 'error' );

									return false;
								}

								if ( $wooco_product->managing_stock() ) {
									$products_qty_in_cart = WC()->cart->get_cart_item_quantities();

									if ( isset( $products_qty_in_cart[ $wooco_product->get_stock_managed_by_id() ] ) && ! $wooco_product->has_enough_stock( $products_qty_in_cart[ $wooco_product->get_stock_managed_by_id() ] + $wooco_item['qty'] * $wooco_qty ) ) {
										wc_add_notice( sprintf( esc_html__( '"%s" has not enough stock.', 'wpc-composite-products' ), esc_html( $wooco_product->get_name() ) ), 'error' );
										wc_add_notice( esc_html__( 'You cannot add this composite products to the cart.', 'wpc-composite-products' ), 'error' );

										return false;
									}
								}

								if ( post_password_required( $wooco_item['id'] ) ) {
									wc_add_notice( sprintf( esc_html__( '"%s" is protected and cannot be purchased.', 'wpc-composite-products' ), esc_html( $wooco_product->get_name() ) ), 'error' );
									wc_add_notice( esc_html__( 'You cannot add this composite products to the cart.', 'wpc-composite-products' ), 'error' );

									return false;
								}
							}
						}
					}

					return $passed;
				}

				function wooco_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
					if ( ! empty( $cart_item_data['wooco_ids'] ) && ( $items = $this->wooco_get_items( $cart_item_data['wooco_ids'] ) ) ) {
						$this->wooco_add_to_cart_items( $items, $cart_item_key, $product_id, $quantity );
					}
				}

				function wooco_add_to_cart_items( $items, $cart_item_key, $product_id, $quantity ) {
					// add child products
					$wooco_i = 0; // for same component product

					foreach ( $items as $item ) {
						$wooco_i ++;
						$wooco_item_id    = $item['id'];
						$wooco_item_qty   = $item['qty'];
						$wooco_item_price = $item['price'];

						if ( ( $wooco_item_id > 0 ) && ( $wooco_item_qty > 0 ) ) {
							$wooco_item_variation_id = 0;
							$wooco_item_variation    = array();
							$wooco_product           = wc_get_product( $wooco_item_id );

							if ( $wooco_product instanceof WC_Product_Variation ) {
								// ensure we don't add a variation to the cart directly by variation ID
								$wooco_item_variation_id = $wooco_item_id;
								$wooco_item_id           = $wooco_product->get_parent_id();
								$wooco_item_variation    = $wooco_product->get_variation_attributes();
							}

							// add to cart
							$wooco_product_qty = $wooco_item_qty * $quantity;
							$wooco_item_data   = array(
								'wooco_pos'        => $wooco_i,
								'wooco_qty'        => $wooco_item_qty,
								'wooco_price'      => $wooco_item_price,
								'wooco_parent_id'  => $product_id,
								'wooco_parent_key' => $cart_item_key
							);
							$wooco_cart_id     = WC()->cart->generate_cart_id( $wooco_item_id, $wooco_item_variation_id, $wooco_item_variation, $wooco_item_data );
							$wooco_item_key    = WC()->cart->find_product_in_cart( $wooco_cart_id );

							if ( empty( $wooco_item_key ) ) {
								$wooco_item_key = WC()->cart->add_to_cart( $wooco_item_id, $wooco_product_qty, $wooco_item_variation_id, $wooco_item_variation, $wooco_item_data );
							}

							if ( empty( $wooco_item_key ) ) {
								// can't add the composite product
								if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['wooco_keys'] ) ) {
									$wooco_keys = WC()->cart->cart_contents[ $cart_item_key ]['wooco_keys'];

									foreach ( $wooco_keys as $wooco_key ) {
										// remove all components
										WC()->cart->remove_cart_item( $wooco_key );
									}

									// remove the composite
									WC()->cart->remove_cart_item( $cart_item_key );
								}
							} elseif ( ! isset( WC()->cart->cart_contents[ $cart_item_key ]['wooco_keys'] ) || ! in_array( $wooco_item_key, WC()->cart->cart_contents[ $cart_item_key ]['wooco_keys'], true ) ) {
								// add keys
								WC()->cart->cart_contents[ $cart_item_key ]['wooco_keys'][] = $wooco_item_key;
							}
						}
					}
				}

				function wooco_get_cart_contents( $cart_contents ) {
					foreach ( $cart_contents as $cart_item_key => $cart_item ) {
						// child product price
						if ( ! empty( $cart_item['wooco_parent_id'] ) ) {
							$parent_product = wc_get_product( $cart_item['wooco_parent_id'] );

							if ( ! $parent_product || ! $parent_product->is_type( 'composite' ) ) {
								continue;
							}

							if ( method_exists( $parent_product, 'get_pricing' ) && ( $parent_product->get_pricing() === 'only' ) ) {
								$cart_item['data']->set_price( 0 );
							} else {
								if ( $cart_item['variation_id'] > 0 ) {
									$wooco_product = wc_get_product( $cart_item['variation_id'] );
								} else {
									$wooco_product = wc_get_product( $cart_item['product_id'] );
								}

								$wooco_new_price     = false;
								$wooco_product_price = apply_filters( 'wooco_product_original_price', $wooco_product->get_price(), $wooco_product );

								if ( $cart_item['wooco_price'] !== '' ) {
									$wooco_new_price     = true;
									$wooco_product_price = $this->wooco_new_price( $wooco_product_price, $cart_item['wooco_price'] );
								}

								if ( $wooco_discount = $this->wooco_get_discount( get_post_meta( $cart_item['wooco_parent_id'], 'wooco_discount_percent', true ) ) ) {
									$wooco_new_price     = true;
									$wooco_product_price = $wooco_product_price * ( 100 - $wooco_discount ) / 100;
								}

								if ( $wooco_new_price ) {
									// set new price for child product
									$cart_item['data']->set_price( (float) $wooco_product_price );
								}
							}
						}

						// main product price
						if ( ! empty( $cart_item['wooco_ids'] ) && $cart_item['data']->is_type( 'composite' ) ) {
							if ( ! empty( $cart_contents[ $cart_item_key ]['wooco_keys'] ) ) {
								// set meta data price for composite
								if ( $cart_item['data']->get_pricing() !== 'only' ) {
									$wooco_price    = $cart_item['data']->get_pricing() === 'include' ? wc_get_price_to_display( $cart_item['data'] ) : 0;
									$wooco_discount = $this->wooco_get_discount( get_post_meta( $cart_item['product_id'], 'wooco_discount_percent', true ) );

									foreach ( $cart_contents[ $cart_item_key ]['wooco_keys'] as $wooco_key ) {
										if ( isset( $cart_contents[ $wooco_key ] ) ) {
											$wooco_price += wc_get_price_to_display( $cart_contents[ $wooco_key ]['data'], array( 'qty' => $cart_contents[ $wooco_key ]['wooco_qty'] ) ) * ( 100 - $wooco_discount ) / 100;
										}
									}

									$cart_contents[ $cart_item_key ]['wooco_price'] = $wooco_price;
								}
							}

							if ( method_exists( $cart_item['data'], 'get_pricing' ) && ( $cart_item['data']->get_pricing() === 'exclude' ) ) {
								$cart_item['data']->set_price( 0 );
							}
						}
					}

					return $cart_contents;
				}

				function wooco_item_visible( $visible, $item ) {
					if ( isset( $item['wooco_parent_id'] ) ) {
						return false;
					}

					return $visible;
				}

				function wooco_item_class( $class, $item ) {
					if ( isset( $item['wooco_parent_id'] ) ) {
						$class .= ' wooco-cart-item wooco-cart-child wooco-item-child';
					} elseif ( isset( $item['wooco_ids'] ) ) {
						$class .= ' wooco-cart-item wooco-cart-parent wooco-item-parent';

						if ( get_option( '_wooco_hide_component', 'no' ) !== 'no' ) {
							$class .= ' wooco-hide-component';
						}
					}

					return $class;
				}

				function wooco_get_item_data( $item_data, $cart_item ) {
					if ( empty( $cart_item['wooco_ids'] ) ) {
						return $item_data;
					}

					$wooco_items_str = '';

					if ( $wooco_items = $this->wooco_get_items( $cart_item['wooco_ids'] ) ) {
						foreach ( $wooco_items as $wooco_item ) {
							$wooco_items_str .= ( $wooco_item['qty'] * $cart_item['quantity'] ) . ' × ' . get_the_title( $wooco_item['id'] ) . '; ';
						}
					}

					if ( $wooco_items_str !== '' ) {
						$wooco_items_str = trim( $wooco_items_str, '; ' );
						$item_data[]     = array(
							'key'     => esc_html__( 'Components', 'wpc-composite-products' ),
							'value'   => $wooco_items_str,
							'display' => '',
						);
					}

					return $item_data;
				}

				function wooco_checkout_create_order_line_item( $item, $cart_item_key, $values, $order ) {
					if ( empty( $values['wooco_ids'] ) ) {
						return;
					}

					$wooco_items_arr = array();

					if ( $wooco_items = $this->wooco_get_items( $values['wooco_ids'] ) ) {
						foreach ( $wooco_items as $wooco_item ) {
							$wooco_items_arr[] = $wooco_item['qty'] . ' × ' . get_the_title( $wooco_item['id'] );
						}

						$item->add_meta_data( esc_html__( 'Components', 'wpc-composite-products' ), implode( '; ', $wooco_items_arr ) );
					}
				}

				function wooco_order_item_get_formatted_meta_data( $formatted_meta ) {
					foreach ( $formatted_meta as $key => $meta ) {
						if ( ( $meta->key === 'wooco_ids' ) || ( $meta->key === 'wooco_parent_id' ) || ( $meta->key === 'wooco_qty' ) || ( $meta->key === 'wooco_price' ) ) {
							unset( $formatted_meta[ $key ] );
						}
					}

					return $formatted_meta;
				}

				function wooco_add_order_item_meta( $item, $cart_item_key, $values ) {
					if ( isset( $values['wooco_parent_id'] ) ) {
						$item->update_meta_data( 'wooco_parent_id', $values['wooco_parent_id'] );
					}

					if ( isset( $values['wooco_qty'] ) ) {
						$item->update_meta_data( 'wooco_qty', $values['wooco_qty'] );
					}

					if ( isset( $values['wooco_ids'] ) ) {
						$item->update_meta_data( 'wooco_ids', $values['wooco_ids'] );
					}

					if ( isset( $values['wooco_price'] ) ) {
						$item->update_meta_data( 'wooco_price', $values['wooco_price'] );
					}
				}

				function wooco_hidden_order_item_meta( $hidden ) {
					return array_merge( $hidden, array( 'wooco_parent_id', 'wooco_qty', 'wooco_ids', 'wooco_price' ) );
				}

				function wooco_before_order_item_meta( $item_id ) {
					if ( $wooco_parent_id = wc_get_order_item_meta( $item_id, 'wooco_parent_id', true ) ) {
						echo sprintf( esc_html__( '(in %s)', 'wpc-composite-products' ), get_the_title( $wooco_parent_id ) );
					}
				}

				function wooco_get_cart_item_from_session( $cart_item, $item_session_values ) {
					if ( ! empty( $item_session_values['wooco_ids'] ) ) {
						$cart_item['wooco_ids']   = $item_session_values['wooco_ids'];
						$cart_item['wooco_price'] = $item_session_values['wooco_price'];
					}

					if ( isset( $item_session_values['wooco_parent_id'] ) ) {
						$cart_item['wooco_pos']        = $item_session_values['wooco_pos'];
						$cart_item['wooco_qty']        = $item_session_values['wooco_qty'];
						$cart_item['wooco_price']      = $item_session_values['wooco_price'];
						$cart_item['wooco_parent_id']  = $item_session_values['wooco_parent_id'];
						$cart_item['wooco_parent_key'] = $item_session_values['wooco_parent_key'];
					}

					return $cart_item;
				}

				function wooco_display_post_states( $states, $post ) {
					if ( 'product' == get_post_type( $post->ID ) ) {
						if ( ( $product = wc_get_product( $post->ID ) ) && $product->is_type( 'composite' ) ) {
							$wooco_count = 0;

							if ( $wooco_components = $product->get_components() ) {
								$wooco_count = count( $wooco_components );
							}

							$states[] = apply_filters( 'wooco_post_states', '<span class="wooco-state">' . sprintf( esc_html__( 'Composite (%s)', 'wpc-composite-products' ), $wooco_count ) . '</span>', $wooco_count, $product );
						}
					}

					return $states;
				}

				function wooco_cart_item_remove_link( $link, $cart_item_key ) {
					if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['wooco_parent_key'] ) ) {
						$wooco_parent_key = WC()->cart->cart_contents[ $cart_item_key ]['wooco_parent_key'];

						if ( isset( WC()->cart->cart_contents[ $wooco_parent_key ] ) ) {
							return '';
						}
					}

					return $link;
				}

				function wooco_cart_item_quantity( $quantity, $cart_item_key, $cart_item ) {
					// add qty as text - not input
					if ( isset( $cart_item['wooco_parent_id'] ) ) {
						return $cart_item['quantity'];
					}

					return $quantity;
				}

				function wooco_product_type_selector( $types ) {
					$types['composite'] = esc_html__( 'Composite product', 'wpc-composite-products' );

					return $types;
				}

				function wooco_product_data_tabs( $tabs ) {
					$tabs['composite'] = array(
						'label'  => esc_html__( 'Components', 'wpc-composite-products' ),
						'target' => 'wooco_settings',
						'class'  => array( 'show_if_composite' ),
					);

					return $tabs;
				}

				function wooco_product_data_panels() {
					global $post;
					$post_id = $post->ID;
					?>
                    <div id='wooco_settings' class='panel woocommerce_options_panel wooco_table'>
                        <table class="wooco_components">
                            <thead></thead>
                            <tbody>
							<?php
							$wooco_components = get_post_meta( $post_id, 'wooco_components', true );

							if ( is_array( $wooco_components ) ) {
								foreach ( $wooco_components as $wooco_component ) {
									$this->wooco_component( false, $wooco_component );
								}
							} else {
								$this->wooco_component( true );
							}
							?>
                            </tbody>
                            <tfoot>
                            <tr>
                                <td>
                                    <a href="#" class="wooco_add_component button">
										<?php esc_html_e( '+ Add component', 'wpc-composite-products' ); ?>
                                    </a> <span class="wooco_premium" style="display: none">Please use the Premium Version to add more than 3 components & get the premium support. Click <a
                                                href="https://wpclever.net/downloads/wpc-composite-products-for-woocommerce?utm_source=pro&utm_medium=wooco&utm_campaign=wporg"
                                                target="_blank">here</a> to buy, just $29!</span>
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                        <table>
                            <tr class="wooco_tr_space">
                                <th><?php esc_html_e( 'Pricing', 'wpc-composite-products' ); ?></th>
                                <td>
                                    <div style="text-decoration: underline; color: #c9356e"><?php esc_html_e( 'Always put a price in the General tab to display the Add to Cart button. This is also the base price.', 'wpc-composite-products' ); ?></div>
                                    <div><?php esc_html_e( 'Configure the pricing of the composite here.', 'wpc-composite-products' ); ?></div>
                                    <select id="wooco_pricing" name="wooco_pricing">
                                        <option value="only" <?php echo( get_post_meta( $post_id, 'wooco_pricing', true ) === 'only' ? 'selected' : '' ); ?>><?php esc_html_e( 'Only base price', 'wpc-composite-products' ); ?></option>
                                        <option value="include" <?php echo( get_post_meta( $post_id, 'wooco_pricing', true ) === 'include' ? 'selected' : '' ); ?>><?php esc_html_e( 'Include base price', 'wpc-composite-products' ); ?></option>
                                        <option value="exclude" <?php echo( get_post_meta( $post_id, 'wooco_pricing', true ) === 'exclude' ? 'selected' : '' ); ?>><?php esc_html_e( 'Exclude base price', 'wpc-composite-products' ); ?></option>
                                    </select>
                                    <span class="woocommerce-help-tip"
                                          data-tip="<?php esc_attr_e( '"Base price" is the price set in the General tab. When "Only base price" is chosen, the total price won\'t change despite the price changes in variable components.', 'wpc-composite-products' ); ?>"></span>
                                </td>
                            </tr>
                            <tr class="wooco_tr_space">
                                <th><?php esc_html_e( 'Discount', 'wpc-composite-products' ); ?></th>
                                <td style="vertical-align: middle; line-height: 30px;">
                                    <input id="wooco_discount_percent" name="wooco_discount_percent" type="number"
                                           min="0.0001" step="0.0001"
                                           max="99.9999"
                                           value="<?php echo( get_post_meta( $post_id, 'wooco_discount_percent', true ) ?: '' ); ?>"
                                           style="width: 80px"/>%. <span class="woocommerce-help-tip"
                                                                         data-tip="<?php esc_attr_e( 'The universal percentage discount will be applied equally on each component\'s price, not on the total.', 'wpc-composite-products' ); ?>"></span>
                                </td>
                            </tr>
                            <tr class="wooco_tr_space">
								<?php
								$wooco_qty_min = get_post_meta( $post_id, 'wooco_qty_min', true ) ?: '';
								$wooco_qty_max = get_post_meta( $post_id, 'wooco_qty_max', true ) ?: '';

								if ( class_exists( 'WPCleverWoopq' ) && ( get_option( '_woopq_decimal', 'no' ) === 'yes' ) ) {
									$wooco_step = '0.000001';
								} else {
									$wooco_step = '1';

									if ( ! empty( $wooco_qty_min ) ) {
										$wooco_qty_min = (int) $wooco_qty_min;
									}

									if ( ! empty( $wooco_qty_max ) ) {
										$wooco_qty_max = (int) $wooco_qty_max;
									}
								}
								?>
                                <th><?php esc_html_e( 'Quantity', 'wpc-composite-products' ); ?></th>
                                <td style="vertical-align: middle; line-height: 30px;">
                                    Min <input name="wooco_qty_min" type="number"
                                               min="0" step="<?php echo esc_attr( $wooco_step ); ?>"
                                               value="<?php echo esc_attr( $wooco_qty_min ); ?>"
                                               style="width: 80px"/> Max <input name="wooco_qty_max" type="number"
                                                                                min="0"
                                                                                step="<?php echo esc_attr( $wooco_step ); ?>"
                                                                                value="<?php echo esc_attr( $wooco_qty_max ); ?>"
                                                                                style="width: 80px"/>
                                </td>
                            </tr>
                            <tr class="wooco_tr_space">
                                <th><?php esc_html_e( 'Shipping fee', 'wpc-composite-products' ); ?></th>
                                <td>
                                    <select id="wooco_shipping_fee" name="wooco_shipping_fee">
                                        <option value="whole" <?php echo( get_post_meta( $post_id, 'wooco_shipping_fee', true ) === 'whole' ? 'selected' : '' ); ?>><?php esc_html_e( 'Apply to the whole composite', 'wpc-composite-products' ); ?></option>
                                        <option value="each" <?php echo( get_post_meta( $post_id, 'wooco_shipping_fee', true ) === 'each' ? 'selected' : '' ); ?>><?php esc_html_e( 'Apply to each component product', 'wpc-composite-products' ); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr class="wooco_tr_space">
                                <th><?php esc_html_e( 'Custom display price', 'wpc-composite-products' ); ?></th>
                                <td>
                                    <input type="text" name="wooco_custom_price"
                                           value="<?php echo stripslashes( get_post_meta( $post_id, 'wooco_custom_price', true ) ); ?>"/>
                                    E.g: <code>From $10 to $100</code>
                                </td>
                            </tr>
                            <tr class="wooco_tr_space">
                                <th><?php esc_html_e( 'Above text', 'wpc-composite-products' ); ?></th>
                                <td>
                                    <div class="w100">
                                        <textarea
                                                name="wooco_before_text"><?php echo stripslashes( get_post_meta( $post_id, 'wooco_before_text', true ) ); ?></textarea>
                                    </div>
                                </td>
                            </tr>
                            <tr class="wooco_tr_space">
                                <th><?php esc_html_e( 'Under text', 'wpc-composite-products' ); ?></th>
                                <td>
                                    <div class="w100">
                                        <textarea
                                                name="wooco_after_text"><?php echo stripslashes( get_post_meta( $post_id, 'wooco_after_text', true ) ); ?></textarea>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
					<?php
				}

				function wooco_save_option_field( $post_id ) {
					if ( isset( $_POST['wooco_components'] ) ) {
						update_post_meta( $post_id, 'wooco_components', $this->wooco_format_array( $_POST['wooco_components'] ) );
					} else {
						delete_post_meta( $post_id, 'wooco_components' );
					}

					if ( isset( $_POST['wooco_pricing'] ) ) {
						update_post_meta( $post_id, 'wooco_pricing', sanitize_text_field( $_POST['wooco_pricing'] ) );
					}

					if ( isset( $_POST['wooco_discount_percent'] ) ) {
						update_post_meta( $post_id, 'wooco_discount_percent', sanitize_text_field( $_POST['wooco_discount_percent'] ) );
					}

					if ( isset( $_POST['wooco_qty_min'] ) ) {
						update_post_meta( $post_id, 'wooco_qty_min', sanitize_text_field( $_POST['wooco_qty_min'] ) );
					}

					if ( isset( $_POST['wooco_qty_max'] ) ) {
						update_post_meta( $post_id, 'wooco_qty_max', sanitize_text_field( $_POST['wooco_qty_max'] ) );
					}

					if ( isset( $_POST['wooco_shipping_fee'] ) ) {
						update_post_meta( $post_id, 'wooco_shipping_fee', sanitize_text_field( $_POST['wooco_shipping_fee'] ) );
					}

					if ( ! empty( $_POST['wooco_custom_price'] ) ) {
						update_post_meta( $post_id, 'wooco_custom_price', addslashes( $_POST['wooco_custom_price'] ) );
					} else {
						delete_post_meta( $post_id, 'wooco_custom_price' );
					}

					if ( ! empty( $_POST['wooco_before_text'] ) ) {
						update_post_meta( $post_id, 'wooco_before_text', addslashes( $_POST['wooco_before_text'] ) );
					} else {
						delete_post_meta( $post_id, 'wooco_before_text' );
					}

					if ( ! empty( $_POST['wooco_after_text'] ) ) {
						update_post_meta( $post_id, 'wooco_after_text', addslashes( $_POST['wooco_after_text'] ) );
					} else {
						delete_post_meta( $post_id, 'wooco_after_text' );
					}
				}

				function wooco_add_to_cart_form() {
					$this->wooco_show_items();
					wc_get_template( 'single-product/add-to-cart/simple.php' );
				}

				function wooco_add_to_cart_button() {
					global $product;

					if ( $product->is_type( 'composite' ) ) {
						echo '<input name="wooco_ids" class="wooco_ids wooco-ids" type="hidden" value=""/>';
					}
				}

				function wooco_loop_add_to_cart_link( $link, $product ) {
					if ( $product->is_type( 'composite' ) ) {
						$link = str_replace( 'ajax_add_to_cart', '', $link );
					}

					return $link;
				}

				function wooco_cart_shipping_packages( $packages ) {
					if ( ! empty( $packages ) ) {
						foreach ( $packages as $package_key => $package ) {
							if ( ! empty( $package['contents'] ) ) {
								foreach ( $package['contents'] as $cart_item_key => $cart_item ) {
									if ( ! empty( $cart_item['wooco_parent_id'] ) ) {
										if ( get_post_meta( $cart_item['wooco_parent_id'], 'wooco_shipping_fee', true ) !== 'each' ) {
											unset( $packages[ $package_key ]['contents'][ $cart_item_key ] );
										}
									}

									if ( ! empty( $cart_item['wooco_ids'] ) ) {
										if ( get_post_meta( $cart_item['data']->get_id(), 'wooco_shipping_fee', true ) === 'each' ) {
											unset( $packages[ $package_key ]['contents'][ $cart_item_key ] );
										}
									}
								}
							}
						}
					}

					return $packages;
				}

				function wooco_get_price_html( $price, $product ) {
					if ( $product->is_type( 'composite' ) ) {
						$product_id   = $product->get_id();
						$custom_price = stripslashes( get_post_meta( $product_id, 'wooco_custom_price', true ) );

						if ( ! empty( $custom_price ) ) {
							return $custom_price;
						}

						if ( $product->get_pricing() !== 'only' ) {
							switch ( get_option( '_wooco_price_format', 'from_regular' ) ) {
								case 'from_regular':
									return esc_html__( 'From', 'wpc-composite-products' ) . ' ' . wc_price( wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ) );
									break;
								case 'from_sale':
									return esc_html__( 'From', 'wpc-composite-products' ) . ' ' . wc_price( wc_get_price_to_display( $product, array( 'price' => $product->get_price() ) ) );
									break;
							}
						}
					}

					return $price;
				}

				function wooco_order_again_cart_item_data( $item_data, $item ) {
					if ( isset( $item['wooco_ids'] ) ) {
						$item_data['wooco_ids']         = $item['wooco_ids'];
						$item_data['wooco_order_again'] = 'yes';
					}

					if ( isset( $item['wooco_parent_id'] ) ) {
						$item_data['wooco_order_again'] = 'yes';
						$item_data['wooco_parent_id']   = $item['wooco_parent_id'];
					}

					return $item_data;
				}

				function wooco_cart_loaded_from_session() {
					foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
						if ( isset( $cart_item['wooco_order_again'], $cart_item['wooco_parent_id'] ) ) {
							WC()->cart->remove_cart_item( $cart_item_key );
						}

						if ( isset( $cart_item['wooco_order_again'], $cart_item['wooco_ids'] ) ) {
							if ( $items = $this->wooco_get_items( $cart_item['wooco_ids'] ) ) {
								$this->wooco_add_to_cart_items( $items, $cart_item_key, $cart_item['product_id'], $cart_item['quantity'] );
							}
						}
					}
				}

				function wooco_coupon_is_valid_for_product( $valid, $product, $coupon, $item ) {
					if ( ( get_option( '_wooco_coupon_restrictions', 'no' ) === 'both' ) && ( isset( $item['wooco_parent_id'] ) || isset( $item['wooco_ids'] ) ) ) {
						// exclude both composite and component products
						return false;
					}

					if ( ( get_option( '_wooco_coupon_restrictions', 'no' ) === 'composite' ) && isset( $item['wooco_ids'] ) ) {
						// exclude composite
						return false;
					}

					if ( ( get_option( '_wooco_coupon_restrictions', 'no' ) === 'component' ) && isset( $item['wooco_parent_id'] ) ) {
						// exclude component products
						return false;
					}

					return $valid;
				}

				function wooco_show_items( $product = null ) {
					if ( ! $product ) {
						global $product;
					}

					if ( ! $product || ! $product->is_type( 'composite' ) ) {
						return;
					}

					$product_id  = $product->get_id();
					$wooco_count = 1;

					do_action( 'wooco_before_wrap', $product );

					if ( $wooco_components = $product->get_components() ) {
						// get settings
						$wooco_selector   = get_option( '_wooco_selector', 'ddslick' );
						$wooco_show_price = get_option( '_wooco_show_price', 'yes' );
						$wooco_show_image = get_option( '_wooco_show_image', 'yes' );
						$wooco_show_qty   = get_option( '_wooco_show_qty', 'yes' );

						echo '<div class="wooco_wrap wooco-wrap">';

						if ( $wooco_before_text = apply_filters( 'wooco_before_text', get_post_meta( $product_id, 'wooco_before_text', true ), $product_id ) ) {
							echo '<div class="wooco_before_text wooco-before-text wooco-text">' . do_shortcode( stripslashes( $wooco_before_text ) ) . '</div>';
						}

						do_action( 'wooco_before_components', $product );
						?>
                        <div class="wooco_components wooco-components"
                             data-percent="<?php echo esc_attr( $product->get_discount() ); ?>"
                             data-min="<?php echo esc_attr( get_post_meta( $product_id, 'wooco_qty_min', true ) ); ?>"
                             data-max="<?php echo esc_attr( get_post_meta( $product_id, 'wooco_qty_max', true ) ); ?>"
                             data-price="<?php echo wc_get_price_to_display( $product ); ?>"
                             data-pricing="<?php echo esc_attr( $product->get_pricing() ); ?>">
							<?php
							foreach ( $wooco_components as $wooco_component ) {
								if ( ( ( $wooco_component_type = $wooco_component['type'] ) === '' ) || ( $wooco_count > 3 ) || empty( $wooco_component[ $wooco_component_type ] ) ) {
									continue;
								}
								?>
                                <div class="wooco_component">
									<?php
									do_action( 'wooco_before_component', $wooco_component );

									if ( ! empty( $wooco_component['name'] ) ) {
										echo '<div class="wooco_component_name">' . $wooco_component['name'] . '</div>';
									}

									if ( ! empty( $wooco_component['desc'] ) ) {
										echo '<div class="wooco_component_desc">' . $wooco_component['desc'] . '</div>';
									}

									$wooco_component_default = isset( $wooco_component['default'] ) ? (int) $wooco_component['default'] : 0;
									$wooco_component_qty     = isset( $wooco_component['qty'] ) ? (float) $wooco_component['qty'] : 1;
									$wooco_component_orderby = isset( $wooco_component['orderby'] ) ? (string) $wooco_component['orderby'] : 'default';
									$wooco_component_order   = isset( $wooco_component['order'] ) ? (string) $wooco_component['order'] : 'default';
									$wooco_component_price   = isset( $wooco_component['price'] ) ? $this->wooco_format_price( $wooco_component['price'] ) : '';

									// get products
									$wooco_products = $this->wooco_get_products( $wooco_component['type'], $wooco_component_orderby, $wooco_component_order, $wooco_component[ $wooco_component_type ], $wooco_component_default, $wooco_component_qty, $wooco_component_price );

									if ( ! $wooco_products ) {
										if ( $wooco_component['optional'] !== 'yes' ) {
											// have no product and required
											?>
                                            <div class="wooco_component_product wooco_component_product_none"
                                                 data-name="<?php echo esc_attr( $wooco_component['name'] ); ?>"
                                                 data-id="0" data-price="0" data-new-price="0"
                                                 data-qty="<?php echo esc_attr( $wooco_component_qty ); ?>>"></div><!-- /wooco_component_product -->
											<?php
										}
									} elseif ( ( count( $wooco_products ) === 1 ) && ( $wooco_component['optional'] !== 'yes' ) ) {
										// only one product and required
										$wooco_product_only = wc_get_product( $wooco_products[0]['id'] );

										if ( $wooco_product_only ) {
											$wooco_product_only_id   = $wooco_products[0]['id'];
											$wooco_product_only_name = '';

											if ( $wooco_product_only->is_visible() && ( get_option( '_wooco_product_link', 'yes' ) !== 'no' ) ) {
												$wooco_product_only_name .= '<a ' . ( get_option( '_wooco_product_link', 'yes' ) === 'yes_popup' ? 'class="woosq-btn" data-id="' . $wooco_product_only_id . '"' : '' ) . ' href="' . get_permalink( $wooco_product_only_id ) . '" ' . ( get_option( '_wooco_product_link', 'yes' ) === 'yes_blank' ? 'target="_blank"' : '' ) . '>';
											}

											if ( $wooco_products[0]['purchasable'] !== 'yes' ) {
												$wooco_product_only_name .= '<s>';
											}

											$wooco_product_only_name .= ( $wooco_component['custom_qty'] !== 'yes' && $wooco_show_qty === 'yes' ? $wooco_component['qty'] . ' &times; ' . $wooco_products[0]['name'] : $wooco_products[0]['name'] );

											if ( $wooco_products[0]['purchasable'] !== 'yes' ) {
												$wooco_product_only_name .= '</s>';
											}

											if ( $wooco_product_only->is_visible() && ( get_option( '_wooco_product_link', 'yes' ) !== 'no' ) ) {
												$wooco_product_only_name .= '</a>';
											}
											?>
                                            <div class="wooco_component_product wooco_component_product_only"
                                                 data-name="<?php echo esc_attr( $wooco_component['name'] ); ?>"
                                                 data-id="<?php echo esc_attr( $wooco_products[0]['purchasable'] === 'yes' ? $wooco_products[0]['id'] : 0 ); ?>"
                                                 data-price="<?php echo esc_attr( $wooco_products[0]['price'] ); ?>"
                                                 data-new-price="<?php echo esc_attr( $wooco_component_price ); ?>"
                                                 data-qty="<?php echo esc_attr( $wooco_component['qty'] ); ?>">
												<?php if ( $wooco_show_image === 'yes' ) { ?>
                                                    <div class="wooco_component_product_image">
														<?php echo $wooco_product_only->get_image(); ?>
                                                    </div>
												<?php } ?>
                                                <div class="wooco_component_product_name">
													<?php echo $wooco_product_only_name; ?>
                                                </div>
												<?php if ( $wooco_show_price === 'yes' ) { ?>
                                                    <div class="wooco_component_product_price">
														<?php echo wc_price( $wooco_products[0]['price'] ); ?>
                                                    </div>
												<?php } ?>
												<?php if ( $wooco_component['custom_qty'] === 'yes' ) {
													$wooco_min = 0;
													$wooco_max = 1000;

													if ( ! empty( $wooco_component['min'] ) ) {
														$wooco_min = $wooco_component['min'];
													}

													if ( ! empty( $wooco_component['max'] ) ) {
														$wooco_max = $wooco_component['max'];
													}

													if ( class_exists( 'WPCleverWoopq' ) && ( get_option( '_woopq_decimal', 'no' ) === 'yes' ) ) {
														$wooco_step = '0.000001';
													} else {
														$wooco_step             = '1';
														$wooco_component['qty'] = (int) $wooco_component['qty'];
														$wooco_min              = (int) $wooco_min;
														$wooco_max              = (int) $wooco_max;
													}
													?>
                                                    <div class="wooco_component_product_qty wooco-qty">
                                                        <span class="wooco-qty-label"><?php esc_html_e( 'Qty:', 'wpc-composite-products' ); ?></span>
                                                        <span class="wooco-qty-input">
                                                            <span class="wooco_component_product_qty_btn wooco_component_product_qty_minus wooco-minus">-</span>
                                                            <input
                                                                    class="wooco_component_product_qty_input input-text text qty"
                                                                    type="number"
                                                                    min="<?php echo esc_attr( $wooco_min ); ?>"
                                                                    max="<?php echo esc_attr( $wooco_max ); ?>"
                                                                    step="<?php echo esc_attr( $wooco_step ); ?>"
                                                                    value="<?php echo esc_attr( $wooco_component['qty'] ); ?>"/>
                                                            <span class="wooco_component_product_qty_btn wooco_component_product_qty_plus wooco-plus">+</span>
                                                        </span>
                                                    </div>
												<?php } ?>
                                            </div><!-- /wooco_component_product -->
											<?php
										}
									} else {
										?>
                                        <div class="wooco_component_product" data-id="0" data-price="0"
                                             data-name="<?php echo esc_attr( $wooco_component['name'] ); ?>"
                                             data-new-price="<?php echo esc_attr( $wooco_component_price ); ?>"
                                             data-qty="<?php echo esc_attr( $wooco_component['qty'] ); ?>">
											<?php if ( ( $wooco_selector === 'select' ) && ( $wooco_show_image === 'yes' ) ) { ?>
                                                <div class="wooco_component_product_image">
													<?php echo wc_placeholder_img(); ?>
                                                </div>
											<?php } ?>
                                            <div class="wooco_component_product_selection">
                                                <select class="wooco_component_product_select"
                                                        id="<?php echo esc_attr( 'wooco_component_product_select_' . $wooco_count ); ?>">
													<?php
													if ( $wooco_component['optional'] === 'yes' ) {
														$wooco_option_none = get_option( '_wooco_option_none', '' );

														if ( empty( $wooco_option_none ) ) {
															$wooco_option_none = esc_html__( 'No, thanks. I don\'t need this', 'wpc-composite-products' );
														}

														echo '<option value="-1" data-pid="-1" data-qty="0" data-price="" data-link="" data-price-html="" data-imagesrc="' . esc_url( apply_filters( 'wooco_option_none_img_src', wc_placeholder_img_src() ) ) . '" data-imagefull="' . esc_url( apply_filters( 'wooco_option_none_img_full', wc_placeholder_img_src() ) ) . '" data-description="' . esc_attr( apply_filters( 'wooco_option_none_description', htmlentities( wc_price( 0 ) ) ) ) . '">' . esc_html( $wooco_option_none ) . '</option>';
													}

													foreach ( $wooco_products as $wooco_product ) {
														echo '<option value="' . esc_attr( $wooco_product['purchasable'] === 'yes' ? $wooco_product['id'] : 0 ) . '" data-pid="' . esc_attr( $wooco_product['pid'] ) . '" data-price="' . esc_attr( $wooco_product['price'] ) . '" data-link="' . esc_url( $wooco_product['link'] ) . '"  data-imagesrc="' . esc_url( $wooco_product['image'] ) . '" data-imagefull="' . esc_url( $wooco_product['image_full'] ) . '" data-description="' . esc_attr( $wooco_product['description'] ) . '" ' . ( $wooco_product['id'] == $wooco_component['default'] ? 'selected' : '' ) . '>' . ( $wooco_component['custom_qty'] !== 'yes' && $wooco_show_qty === 'yes' ? $wooco_component['qty'] . ' &times; ' . $wooco_product['name'] : $wooco_product['name'] ) . '</option>';
													}
													?>
                                                </select>
                                            </div>
											<?php if ( ( $wooco_selector === 'select' ) && ( $wooco_show_price === 'yes' ) ) { ?>
                                                <div class="wooco_component_product_price"></div>
											<?php } ?>
											<?php if ( $wooco_component['custom_qty'] === 'yes' ) {
												$wooco_min = 0;
												$wooco_max = 1000;

												if ( ! empty( $wooco_component['min'] ) ) {
													$wooco_min = $wooco_component['min'];
												}

												if ( ! empty( $wooco_component['max'] ) ) {
													$wooco_max = $wooco_component['max'];
												}

												if ( class_exists( 'WPCleverWoopq' ) && ( get_option( '_woopq_decimal', 'no' ) === 'yes' ) ) {
													$wooco_step = '0.000001';
												} else {
													$wooco_step             = '1';
													$wooco_component['qty'] = (int) $wooco_component['qty'];
													$wooco_min              = (int) $wooco_min;
													$wooco_max              = (int) $wooco_max;
												}
												?>
                                                <div class="wooco_component_product_qty wooco-qty">
                                                    <span class="wooco-qty-label"><?php esc_html_e( 'Qty:', 'wpc-composite-products' ); ?></span>
                                                    <span class="wooco-qty-input">
                                                        <span class="wooco_component_product_qty_btn wooco_component_product_qty_minus wooco-minus">-</span>
                                                        <input
                                                                class="wooco_component_product_qty_input input-text text qty"
                                                                type="number"
                                                                min="<?php echo esc_attr( $wooco_min ); ?>"
                                                                max="<?php echo esc_attr( $wooco_max ); ?>"
                                                                step="<?php echo esc_attr( $wooco_step ); ?>"
                                                                value="<?php echo esc_attr( $wooco_component['qty'] ); ?>"/>
                                                        <span class="wooco_component_product_qty_btn wooco_component_product_qty_plus wooco-plus">+</span>
                                                    </span>
                                                </div>
											<?php } ?>
                                        </div><!-- /wooco_component_product -->
										<?php
									}

									do_action( 'wooco_after_component', $wooco_component );
									?>
                                </div><!-- /wooco_component -->
								<?php
								$wooco_count ++;
							} ?>
                        </div><!-- /wooco_components -->
						<?php
						echo '<div class="wooco_total wooco-total wooco-text"></div>';

						if ( get_option( '_wooco_show_alert', 'load' ) !== 'no' ) {
							echo '<div class="wooco-alert wooco-text" style="display: none"></div>';
						}

						do_action( 'wooco_after_components', $product );

						if ( $wooco_after_text = apply_filters( 'wooco_after_text', get_post_meta( $product_id, 'wooco_after_text', true ), $product_id ) ) {
							echo '<div class="wooco_after_text wooco-after-text wooco-text">' . do_shortcode( stripslashes( $wooco_after_text ) ) . '</div>';
						}

						echo '</div><!-- /wooco_wrap -->';
					}

					do_action( 'wooco_after_wrap', $product );
				}

				function wooco_get_products( $type, $orderby, $order, $data, $default = 0, $qty = 1, $price = '' ) {
					$has_default    = false;
					$wooco_products = $wooco_args = array();
					$ids            = explode( ',', $data );

					switch ( $type ) {
						case 'products':
							if ( ! in_array( $default, $ids ) ) {
								// check default value
								array_unshift( $ids, $default );
							}

							foreach ( $ids as $id ) {
								$wooco_product = wc_get_product( $id );

								if ( ! $wooco_product ) {
									continue;
								}

								if ( $wooco_product->is_type( 'simple' ) || $wooco_product->is_type( 'variation' ) || $wooco_product->is_type( 'woosb' ) ) {
									if ( ( get_option( '_wooco_exclude_unpurchasable', 'yes' ) === 'yes' ) && ! $this->wooco_is_purchasable( $wooco_product, $qty ) ) {
										continue;
									}

									$wooco_products[] = $this->wooco_get_product_data( $wooco_product, $qty, $price );
								}

								if ( $wooco_product->is_type( 'variable' ) ) {
									$childs = $wooco_product->get_children();

									if ( ! empty( $childs ) ) {
										foreach ( $childs as $child ) {
											$wooco_product_child = wc_get_product( $child );

											if ( ! $wooco_product_child ) {
												continue;
											}

											if ( ( get_option( '_wooco_exclude_unpurchasable', 'yes' ) === 'yes' ) && ! $this->wooco_is_purchasable( $wooco_product_child, $qty ) ) {
												continue;
											}

											$wooco_products[] = $this->wooco_get_product_data( $wooco_product_child, $qty, $price );
										}
									}
								}
							}

							break;

						case 'categories':
							$cat_slugs = array();

							foreach ( $ids as $id ) {
								if ( absint( $id ) > 0 ) {
									$cat = get_term_by( 'id', absint( $id ), 'product_cat' );

									if ( $cat ) {
										$cat_slugs[] = $cat->slug;
									}
								} else {
									$cat_slugs[] = $id;
								}
							}

							$wooco_args = array(
								'is_wooco' => true,
								'category' => $cat_slugs,
								'orderby'  => $orderby,
								'order'    => $order,
								'limit'    => 100
							);

							$wooco_ps = wc_get_products( $wooco_args );

							foreach ( $wooco_ps as $wooco_product ) {
								if ( $wooco_product->is_type( 'simple' ) || $wooco_product->is_type( 'variation' ) ) {
									if ( ( get_option( '_wooco_exclude_unpurchasable', 'yes' ) === 'yes' ) && ! $this->wooco_is_purchasable( $wooco_product, $qty ) ) {
										continue;
									}

									$wooco_products[] = $this->wooco_get_product_data( $wooco_product, $qty, $price );

									if ( $wooco_product->get_id() == $default ) {
										$has_default = true;
									}
								}

								if ( $wooco_product->is_type( 'variable' ) ) {
									$childs = $wooco_product->get_children();

									if ( ! empty( $childs ) ) {
										foreach ( $childs as $child ) {
											$wooco_product_child = wc_get_product( $child );

											if ( ! $wooco_product_child ) {
												continue;
											}

											if ( ( get_option( '_wooco_exclude_unpurchasable', 'yes' ) === 'yes' ) && ! $this->wooco_is_purchasable( $wooco_product_child, $qty ) ) {
												continue;
											}

											$wooco_products[] = $this->wooco_get_product_data( $wooco_product_child, $qty, $price );

											if ( $child == $default ) {
												$has_default = true;
											}
										}
									}
								}
							}

							if ( ! $has_default ) {
								// add default product
								$wooco_product_default = wc_get_product( $default );

								if ( $wooco_product_default ) {
									if ( ( get_option( '_wooco_exclude_unpurchasable', 'yes' ) === 'yes' ) && ! $this->wooco_is_purchasable( $wooco_product_default, $qty ) ) {
										break;
									}

									array_unshift( $wooco_products, $this->wooco_get_product_data( $wooco_product_default, $qty, $price ) );
								}
							}

							break;
					}

					if ( count( $wooco_products ) > 0 ) {
						return $wooco_products;
					}

					return false;
				}

				function wooco_is_purchasable( $product, $qty ) {
					return $product->is_purchasable() && $product->is_in_stock() && $product->has_enough_stock( $qty );
				}

				function wooco_get_product_data( $wooco_product, $qty = 1, $price = '' ) {
					if ( $wooco_product->get_image_id() ) {
						$wooco_product_img          = wp_get_attachment_image_src( $wooco_product->get_image_id(), 'thumbnail' );
						$wooco_product_img_full     = wp_get_attachment_image_src( $wooco_product->get_image_id(), 'full' );
						$wooco_product_img_src      = $wooco_product_img[0];
						$wooco_product_img_full_src = $wooco_product_img_full[0];
					} else {
						$wooco_product_img_src = $wooco_product_img_full_src = wc_placeholder_img_src();
					}

					$wooco_product_price = apply_filters( 'wooco_product_original_price', $wooco_product->get_price(), $wooco_product );

					if ( $price !== '' ) {
						// new price
						$wooco_product_price       = $this->wooco_new_price( $wooco_product_price, $price );
						$wooco_product_description = wc_format_sale_price( $wooco_product->get_price(), $wooco_product_price );
					} else {
						// original price
						$wooco_product_description = $wooco_product->get_price_html();
					}

					$wooco_product_price       = wc_get_price_to_display( $wooco_product, array( 'price' => $wooco_product_price ) );
					$wooco_product_description .= wc_get_stock_html( $wooco_product );

					return array(
						'id'          => $wooco_product->get_id(),
						'pid'         => $wooco_product->is_type( 'variation' ) && $wooco_product->get_parent_id() ? $wooco_product->get_parent_id() : 0,
						'name'        => apply_filters( 'wooco_product_name', $wooco_product->get_name(), $wooco_product ),
						'price'       => apply_filters( 'wooco_product_price', $wooco_product_price, $wooco_product ),
						'link'        => get_permalink( $wooco_product->get_id() ),
						'description' => apply_filters( 'wooco_product_description', htmlentities( $wooco_product_description ), $wooco_product ),
						'image'       => $wooco_product_img_src,
						'image_full'  => $wooco_product_img_full_src,
						'purchasable' => $this->wooco_is_purchasable( $wooco_product, $qty ) ? 'yes' : 'no'
					);
				}

				function wooco_add_export_column( $columns ) {
					$columns['wooco_components'] = esc_html__( 'Components', 'wpc-composite-products' );

					return $columns;
				}

				function wooco_add_export_data( $value, $product ) {
					$value = get_post_meta( $product->get_id(), 'wooco_components', true );

					return serialize( $value );
				}

				function wooco_add_column_to_importer( $options ) {
					$options['wooco_components'] = esc_html__( 'Components', 'wpc-composite-products' );

					return $options;
				}

				function wooco_add_column_to_mapping_screen( $columns ) {
					$columns['Components']       = 'wooco_components';
					$columns['components']       = 'wooco_components';
					$columns['wooco components'] = 'wooco_components';

					return $columns;
				}

				function wooco_process_import( $object, $data ) {
					if ( ! empty( $data['wooco_components'] ) ) {
						$object->update_meta_data( 'wooco_components', unserialize( $data['wooco_components'] ) );
					}

					return $object;
				}

				function wooco_clean_ids( $ids ) {
					$ids = preg_replace( '/[^,.%\/0-9]/', '', $ids );

					return $ids;
				}

				function wooco_get_items( $ids ) {
					if ( ! empty( $ids ) ) {
						$wooco_arr = array();
						$items     = explode( ',', $ids );

						if ( is_array( $items ) && count( $items ) > 0 ) {
							foreach ( $items as $item ) {
								$item_arr    = explode( '/', $item );
								$wooco_arr[] = array(
									'id'    => absint( isset( $item_arr[0] ) ? $item_arr[0] : 0 ),
									'qty'   => (float) ( isset( $item_arr[1] ) ? $item_arr[1] : 1 ),
									'price' => isset( $item_arr[2] ) ? $this->wooco_format_price( $item_arr[2] ) : ''
								);
							}
						}

						if ( count( $wooco_arr ) > 0 ) {
							return $wooco_arr;
						}
					}

					return false;
				}

				function wooco_format_price( $price ) {
					// format price to percent or number
					$price = preg_replace( '/[^.%0-9]/', '', $price );

					return $price;
				}

				function wooco_new_price( $old_price, $new_price ) {
					if ( strpos( $new_price, '%' ) !== false ) {
						$calc_price = ( (float) $new_price * $old_price ) / 100;
					} else {
						$calc_price = (float) $new_price;
					}

					return $calc_price;
				}

				function wooco_get_discount( $number ) {
					$discount = 0;

					if ( is_numeric( $number ) && ( (float) $number < 100 ) && ( (float) $number > 0 ) ) {
						$discount = (float) $number;
					}

					return $discount;
				}

				function wooco_format_array( $array ) {
					$formatted_array = array();

					foreach ( array_keys( $array ) as $fieldKey ) {
						foreach ( $array[ $fieldKey ] as $key => $value ) {
							$formatted_array[ $key ][ $fieldKey ] = $value;
						}
					}

					return $formatted_array;
				}
			}

			new WPCleverWooco();
		}
	}
} else {
	add_action( 'admin_notices', 'wooco_notice_premium' );
}

if ( ! function_exists( 'wooco_notice_wc' ) ) {
	function wooco_notice_wc() {
		?>
        <div class="error">
            <p><strong>WPC Composite Products</strong> requires WooCommerce version 3.0.0 or greater.</p>
        </div>
		<?php
	}
}

if ( ! function_exists( 'wooco_notice_premium' ) ) {
	function wooco_notice_premium() {
		?>
        <div class="error">
            <p>Seems you're using both free and premium version of <strong>WPC Composite Products</strong>. Please
                deactivate the free version when using the premium version.</p>
        </div>
		<?php
	}
}