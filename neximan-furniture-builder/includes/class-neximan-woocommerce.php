<?php
/**
 * WooCommerce bridge: AJAX add-to-cart, dynamic pricing and order item meta.
 *
 * @package Neximan_Furniture_Builder
 */

namespace Neximan\Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WooCommerce
 *
 * Handles the connection between the builder widget and WooCommerce cart/orders.
 */
class WooCommerce {

	/**
	 * Cart item data key used to store the builder selection.
	 *
	 * @var string
	 */
	const CART_KEY = 'neximan_builder';

	/**
	 * Registers WordPress / WooCommerce hooks.
	 *
	 * @return void
	 */
	public function register() {
		// AJAX handlers (logged-in and guests).
		add_action( 'wp_ajax_neximan_add_to_cart', array( $this, 'ajax_add_to_cart' ) );
		add_action( 'wp_ajax_nopriv_neximan_add_to_cart', array( $this, 'ajax_add_to_cart' ) );

		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Make each configured selection a unique cart line.
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 3 );

		// Restore data when the cart is loaded from the session.
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 10, 2 );

		// Apply the dynamic price to the cart line.
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'apply_dynamic_price' ), 20, 1 );

		// Show the selected configuration in cart / checkout.
		add_filter( 'woocommerce_get_item_data', array( $this, 'display_cart_item_data' ), 10, 2 );

		// Persist the configuration on the order line item.
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_order_item_meta' ), 10, 4 );
	}

	/**
	 * AJAX: validate the selection server-side, compute the price and add to cart.
	 *
	 * @return void
	 */
	public function ajax_add_to_cart() {
		check_ajax_referer( 'neximan_builder', 'nonce' );

		if ( ! class_exists( 'WooCommerce' ) || is_null( WC()->cart ) ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce is not available.', 'neximan-builder' ) ) );
		}

		$post_id    = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$widget_id  = isset( $_POST['widget_id'] ) ? sanitize_text_field( wp_unslash( $_POST['widget_id'] ) ) : '';
		$module_key = isset( $_POST['module'] ) ? sanitize_text_field( wp_unslash( $_POST['module'] ) ) : '';
		$layout_key = isset( $_POST['layout'] ) ? sanitize_text_field( wp_unslash( $_POST['layout'] ) ) : '';
		$color_key  = isset( $_POST['color'] ) ? sanitize_text_field( wp_unslash( $_POST['color'] ) ) : '';

		if ( ! $post_id || '' === $widget_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'neximan-builder' ) ) );
		}

		$settings = $this->get_widget_settings( $post_id, $widget_id );
		if ( null === $settings ) {
			wp_send_json_error( array( 'message' => __( 'Builder configuration could not be found.', 'neximan-builder' ) ) );
		}

		require_once NEXIMAN_BUILDER_PATH . 'widgets/class-neximan-builder-widget.php';
		$config = \Neximan\Builder\Widgets\Builder_Widget::build_config( $settings );

		if ( ! isset( $config['modules'][ $module_key ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Selected module is not valid.', 'neximan-builder' ) ) );
		}

		$module = $config['modules'][ $module_key ];
		$price  = (float) $module['basePrice'];

		$layout_label = '';
		if ( isset( $config['layouts'][ $layout_key ] ) ) {
			$price       += (float) $config['layouts'][ $layout_key ]['price'];
			$layout_label = $config['layouts'][ $layout_key ]['label'];
		}

		$color_name = '';
		if ( isset( $config['colors'][ $color_key ] ) ) {
			$price     += (float) $config['colors'][ $color_key ]['price'];
			$color_name = $config['colors'][ $color_key ]['name'];
		}

		// Resolve the WooCommerce product to attach the line to.
		$product_id = $module['wooId'] ? $module['wooId'] : (int) $config['woo']['fallbackProduct'];
		if ( ! $product_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'No WooCommerce product is linked. Set a Module Product ID or a Fallback Product ID.', 'neximan-builder' ),
				)
			);
		}

		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_purchasable() ) {
			wp_send_json_error( array( 'message' => __( 'The linked product cannot be purchased.', 'neximan-builder' ) ) );
		}

		$price_mode = $config['woo']['priceMode'];

		$selection = array(
			'module_name'  => $module['name'],
			'module_type'  => $module['type'],
			'layout_label' => $layout_label,
			'color_name'   => $color_name,
			'price'        => 'dynamic' === $price_mode ? $price : (float) $product->get_price(),
			'price_mode'   => $price_mode,
			'currency'     => $config['currency']['symbol'],
		);

		$cart_item_data = array( self::CART_KEY => $selection );

		$added = WC()->cart->add_to_cart( $product_id, 1, 0, array(), $cart_item_data );

		if ( ! $added ) {
			wp_send_json_error( array( 'message' => __( 'Could not add the item to the cart.', 'neximan-builder' ) ) );
		}

		wp_send_json_success(
			array(
				'message'      => __( 'Added to cart.', 'neximan-builder' ),
				'cart_count'   => WC()->cart->get_cart_contents_count(),
				'cart_url'     => wc_get_cart_url(),
				'checkout_url' => wc_get_checkout_url(),
				'price'        => $selection['price'],
			)
		);
	}

	/**
	 * Loads the settings of a specific widget element from an Elementor document.
	 *
	 * @param int    $post_id   Post ID containing the Elementor data.
	 * @param string $widget_id Elementor element ID.
	 * @return array|null Settings array or null when not found.
	 */
	private function get_widget_settings( $post_id, $widget_id ) {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return null;
		}

		$document = \Elementor\Plugin::$instance->documents->get( $post_id );
		if ( ! $document ) {
			return null;
		}

		$elements     = $document->get_elements_data();
		$element_data = $this->find_element( $elements, $widget_id );
		if ( null === $element_data ) {
			return null;
		}

		$element = \Elementor\Plugin::$instance->elements_manager->create_element_instance( $element_data );
		if ( ! $element ) {
			return null;
		}

		return $element->get_settings_for_display();
	}

	/**
	 * Recursively searches an Elementor element tree for an element by ID.
	 *
	 * @param array  $elements Elements data.
	 * @param string $id       Element ID to find.
	 * @return array|null
	 */
	private function find_element( $elements, $id ) {
		foreach ( $elements as $element ) {
			if ( isset( $element['id'] ) && $element['id'] === $id ) {
				return $element;
			}

			if ( ! empty( $element['elements'] ) ) {
				$found = $this->find_element( $element['elements'], $id );
				if ( null !== $found ) {
					return $found;
				}
			}
		}

		return null;
	}

	/**
	 * Marks each configured selection as a distinct cart line.
	 *
	 * @param array $cart_item_data Existing cart item data.
	 * @param int   $product_id     Product ID.
	 * @param int   $variation_id   Variation ID.
	 * @return array
	 */
	public function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		unset( $variation_id, $product_id );

		if ( isset( $cart_item_data[ self::CART_KEY ] ) ) {
			// Ensure uniqueness so different configurations are separate lines.
			$cart_item_data['neximan_unique'] = md5( wp_json_encode( $cart_item_data[ self::CART_KEY ] ) . microtime() );
		}

		return $cart_item_data;
	}

	/**
	 * Restores custom data from the session.
	 *
	 * @param array $cart_item    Cart item.
	 * @param array $session_data Session data.
	 * @return array
	 */
	public function get_cart_item_from_session( $cart_item, $session_data ) {
		if ( isset( $session_data[ self::CART_KEY ] ) ) {
			$cart_item[ self::CART_KEY ] = $session_data[ self::CART_KEY ];
		}

		return $cart_item;
	}

	/**
	 * Applies the dynamic price to cart lines that use the builder.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @return void
	 */
	public function apply_dynamic_price( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( empty( $cart_item[ self::CART_KEY ] ) ) {
				continue;
			}

			$data = $cart_item[ self::CART_KEY ];

			if ( isset( $data['price_mode'] ) && 'dynamic' === $data['price_mode'] && isset( $data['price'] ) ) {
				$cart_item['data']->set_price( (float) $data['price'] );
			}
		}
	}

	/**
	 * Displays the configuration under the cart / checkout line.
	 *
	 * @param array $item_data Existing item data rows.
	 * @param array $cart_item Cart item.
	 * @return array
	 */
	public function display_cart_item_data( $item_data, $cart_item ) {
		if ( empty( $cart_item[ self::CART_KEY ] ) ) {
			return $item_data;
		}

		$data = $cart_item[ self::CART_KEY ];

		if ( ! empty( $data['module_name'] ) ) {
			$item_data[] = array(
				'key'   => __( 'Model', 'neximan-builder' ),
				'value' => wc_clean( $data['module_name'] ),
			);
		}

		if ( ! empty( $data['layout_label'] ) ) {
			$item_data[] = array(
				'key'   => __( 'Layout', 'neximan-builder' ),
				'value' => wc_clean( $data['layout_label'] ),
			);
		}

		if ( ! empty( $data['color_name'] ) ) {
			$item_data[] = array(
				'key'   => __( 'Color', 'neximan-builder' ),
				'value' => wc_clean( $data['color_name'] ),
			);
		}

		return $item_data;
	}

	/**
	 * Persists the configuration as order line item meta.
	 *
	 * @param \WC_Order_Item_Product $item          Order line item.
	 * @param string                 $cart_item_key Cart item key.
	 * @param array                  $values        Cart item values.
	 * @param \WC_Order              $order         Order object.
	 * @return void
	 */
	public function add_order_item_meta( $item, $cart_item_key, $values, $order ) {
		unset( $cart_item_key, $order );

		if ( empty( $values[ self::CART_KEY ] ) ) {
			return;
		}

		$data = $values[ self::CART_KEY ];

		if ( ! empty( $data['module_name'] ) ) {
			$item->add_meta_data( __( 'Model', 'neximan-builder' ), $data['module_name'] );
		}
		if ( ! empty( $data['layout_label'] ) ) {
			$item->add_meta_data( __( 'Layout', 'neximan-builder' ), $data['layout_label'] );
		}
		if ( ! empty( $data['color_name'] ) ) {
			$item->add_meta_data( __( 'Color', 'neximan-builder' ), $data['color_name'] );
		}
	}
}
