<?php
/**
 * WooCommerce bridge: AJAX add-to-cart, dynamic pricing, variations and order meta.
 *
 * Pricing is recomputed server-side from a signed manifest embedded in the page,
 * so the cart price cannot be tampered with and the flow does not depend on
 * re-reading (possibly unsaved) Elementor settings.
 *
 * @package Neximan_Furniture_Builder
 */

namespace Neximan\Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WooCommerce
 */
class WooCommerce {

	/**
	 * Cart item data key.
	 *
	 * @var string
	 */
	const CART_KEY = 'neximan_builder';

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_ajax_neximan_add_to_cart', array( $this, 'ajax_add_to_cart' ) );
		add_action( 'wp_ajax_nopriv_neximan_add_to_cart', array( $this, 'ajax_add_to_cart' ) );

		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 3 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 10, 2 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'apply_dynamic_price' ), 20, 1 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'display_cart_item_data' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_order_item_meta' ), 10, 4 );
	}

	/**
	 * AJAX: verify the manifest, compute the price/variation and add to cart.
	 *
	 * @return void
	 */
	public function ajax_add_to_cart() {
		check_ajax_referer( 'neximan_builder', 'nonce' );

		if ( ! class_exists( 'WooCommerce' ) || is_null( WC()->cart ) ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce is not available.', 'neximan-builder' ) ) );
		}

		// The manifest arrives JSON-encoded; do not unslash before signature check
		// because the signature was computed on the exact JSON string.
		$manifest_json = isset( $_POST['manifest'] ) ? wp_unslash( $_POST['manifest'] ) : '';
		$signature     = isset( $_POST['sig'] ) ? sanitize_text_field( wp_unslash( $_POST['sig'] ) ) : '';

		$manifest = Config::verify( $manifest_json, $signature );
		if ( null === $manifest ) {
			wp_send_json_error( array( 'message' => __( 'Builder configuration could not be verified. Please reload the page.', 'neximan-builder' ) ) );
		}

		$series_id = isset( $_POST['series'] ) ? sanitize_text_field( wp_unslash( $_POST['series'] ) ) : '';
		$model_id  = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : '';
		$layout_id = isset( $_POST['layout'] ) ? sanitize_text_field( wp_unslash( $_POST['layout'] ) ) : '';
		$color_id  = isset( $_POST['color'] ) ? sanitize_text_field( wp_unslash( $_POST['color'] ) ) : '';

		$option_sel = array();
		if ( isset( $_POST['options'] ) && is_array( $_POST['options'] ) ) {
			foreach ( wp_unslash( $_POST['options'] ) as $group_id => $choice_id ) {
				$option_sel[ sanitize_text_field( $group_id ) ] = sanitize_text_field( $choice_id );
			}
		}

		$part_sel = array();
		if ( isset( $_POST['parts'] ) && is_array( $_POST['parts'] ) ) {
			foreach ( wp_unslash( $_POST['parts'] ) as $part_id => $module_id ) {
				$part_sel[ sanitize_text_field( $part_id ) ] = sanitize_text_field( $module_id );
			}
		}

		$resolved = Config::compute_from_manifest( $manifest, $series_id, $model_id, $layout_id, $color_id, $option_sel, $part_sel );
		if ( null === $resolved ) {
			wp_send_json_error( array( 'message' => __( 'Selected configuration is not valid.', 'neximan-builder' ) ) );
		}

		$product_id = (int) $resolved['productId'];
		if ( ! $product_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'No WooCommerce product is linked. Set a product on the series/model or a fallback product.', 'neximan-builder' ),
				)
			);
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'The linked product was not found.', 'neximan-builder' ) ) );
		}

		// Resolve a matching variation when applicable.
		$variation_id    = 0;
		$variation_attrs = array();
		if ( $product->is_type( 'variable' ) && ! empty( $resolved['variation_attr'] ) ) {
			$variation_attrs = $this->normalize_variation_attrs( $resolved['variation_attr'] );
			$data_store      = \WC_Data_Store::load( 'product' );
			$variation_id    = (int) $data_store->find_matching_product_variation( $product, $variation_attrs );

			if ( ! $variation_id ) {
				wp_send_json_error( array( 'message' => __( 'No product variation matches the selected options.', 'neximan-builder' ) ) );
			}
		}

		// Decide the line-item price.
		$price_mode = $resolved['priceMode'];
		if ( 'variation' === $price_mode && $variation_id ) {
			$line_price = (float) wc_get_product( $variation_id )->get_price();
		} elseif ( 'product' === $price_mode ) {
			$line_price = (float) $product->get_price();
		} else {
			$line_price = (float) $resolved['price'];
		}

		$selection = array(
			'series_name'   => $resolved['series_name'],
			'module_name'   => $resolved['model_name'],
			'module_type'   => $resolved['model_type'],
			'layout_label'  => $resolved['layout_label'],
			'color_name'    => $resolved['color_name'],
			'option_labels' => $resolved['option_labels'],
			'price'         => $line_price,
			'price_mode'    => $price_mode,
		);

		$cart_item_data = array( self::CART_KEY => $selection );

		$added = WC()->cart->add_to_cart( $product_id, 1, $variation_id, $variation_attrs, $cart_item_data );

		if ( ! $added ) {
			wp_send_json_error( array( 'message' => __( 'Could not add the item to the cart.', 'neximan-builder' ) ) );
		}

		wp_send_json_success(
			array(
				'message'      => __( 'Added to cart.', 'neximan-builder' ),
				'cart_count'   => WC()->cart->get_cart_contents_count(),
				'cart_url'     => wc_get_cart_url(),
				'checkout_url' => wc_get_checkout_url(),
				'price'        => $line_price,
			)
		);
	}

	/**
	 * Ensures variation attribute keys are lowercase 'attribute_*' as WC expects.
	 *
	 * @param array $attrs Raw attributes (already 'attribute_' prefixed).
	 * @return array
	 */
	private function normalize_variation_attrs( $attrs ) {
		$out = array();
		foreach ( $attrs as $key => $value ) {
			$key         = 0 === strpos( $key, 'attribute_' ) ? $key : 'attribute_' . $key;
			$out[ $key ] = (string) $value;
		}
		return $out;
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
	 * Applies the dynamic price to builder cart lines.
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
	 * @param array $item_data Existing rows.
	 * @param array $cart_item Cart item.
	 * @return array
	 */
	public function display_cart_item_data( $item_data, $cart_item ) {
		if ( empty( $cart_item[ self::CART_KEY ] ) ) {
			return $item_data;
		}

		$data = $cart_item[ self::CART_KEY ];

		foreach ( $this->meta_rows( $data ) as $row ) {
			$item_data[] = array(
				'key'   => $row['key'],
				'value' => wc_clean( $row['value'] ),
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

		foreach ( $this->meta_rows( $values[ self::CART_KEY ] ) as $row ) {
			$item->add_meta_data( $row['key'], $row['value'] );
		}
	}

	/**
	 * Builds the human-readable meta rows for a selection.
	 *
	 * @param array $data Selection data.
	 * @return array List of { key, value }.
	 */
	private function meta_rows( $data ) {
		$rows = array();

		if ( ! empty( $data['series_name'] ) ) {
			$rows[] = array(
				'key'   => __( 'Series', 'neximan-builder' ),
				'value' => $data['series_name'],
			);
		}
		if ( ! empty( $data['module_name'] ) ) {
			$rows[] = array(
				'key'   => __( 'Model', 'neximan-builder' ),
				'value' => $data['module_name'],
			);
		}
		if ( ! empty( $data['layout_label'] ) ) {
			$rows[] = array(
				'key'   => __( 'Layout', 'neximan-builder' ),
				'value' => $data['layout_label'],
			);
		}
		if ( ! empty( $data['color_name'] ) ) {
			$rows[] = array(
				'key'   => __( 'Color', 'neximan-builder' ),
				'value' => $data['color_name'],
			);
		}
		if ( ! empty( $data['option_labels'] ) && is_array( $data['option_labels'] ) ) {
			foreach ( $data['option_labels'] as $opt ) {
				if ( ! empty( $opt['value'] ) ) {
					$rows[] = array(
						'key'   => ! empty( $opt['label'] ) ? $opt['label'] : __( 'Option', 'neximan-builder' ),
						'value' => $opt['value'],
					);
				}
			}
		}

		return $rows;
	}
}
