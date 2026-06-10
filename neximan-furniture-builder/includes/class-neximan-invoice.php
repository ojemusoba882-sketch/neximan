<?php
/**
 * Custom invoice / checkout summary renderer.
 *
 * Reads the WooCommerce cart and renders a styled, RTL-friendly invoice of the
 * builder items (series, model, layout, color, options and add-ons) with line
 * totals and a grand total. Exposed both as a shortcode ([neximan_invoice]) and
 * used by the Elementor "Neximan Invoice" widget.
 *
 * @package Neximan_Furniture_Builder
 */

namespace Neximan\Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Invoice
 */
class Invoice {

	/**
	 * Registers the shortcode and assets.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'neximan_invoice', array( $this, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
	}

	/**
	 * Registers the invoice stylesheet.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style(
			'neximan-invoice',
			NEXIMAN_BUILDER_URL . 'assets/css/invoice.css',
			array(),
			NEXIMAN_BUILDER_VERSION
		);
	}

	/**
	 * Default, fully-customizable labels (English; translate as needed).
	 *
	 * @return array
	 */
	public static function default_labels() {
		return array(
			'title'        => __( 'Your Invoice', 'neximan-builder' ),
			'subtitle'     => __( 'Review your custom order before payment', 'neximan-builder' ),
			'col_product'  => __( 'Product', 'neximan-builder' ),
			'col_details'  => __( 'Details', 'neximan-builder' ),
			'col_qty'      => __( 'Qty', 'neximan-builder' ),
			'col_total'    => __( 'Total', 'neximan-builder' ),
			'series'       => __( 'Series', 'neximan-builder' ),
			'model'        => __( 'Model', 'neximan-builder' ),
			'layout'       => __( 'Layout', 'neximan-builder' ),
			'color'        => __( 'Color', 'neximan-builder' ),
			'subtotal'     => __( 'Subtotal', 'neximan-builder' ),
			'discount'     => __( 'Discount', 'neximan-builder' ),
			'shipping'     => __( 'Shipping', 'neximan-builder' ),
			'tax'          => __( 'Tax', 'neximan-builder' ),
			'grand_total'  => __( 'Grand Total', 'neximan-builder' ),
			'empty'        => __( 'Your cart is empty.', 'neximan-builder' ),
			'checkout'     => __( 'Proceed to payment', 'neximan-builder' ),
			'continue'     => __( 'Continue shopping', 'neximan-builder' ),
			'badge'        => __( 'Custom build', 'neximan-builder' ),
		);
	}

	/**
	 * Shortcode handler.
	 *
	 * @param array $atts Shortcode attributes (label overrides + options).
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts = shortcode_atts(
			array_merge(
				self::default_labels(),
				array(
					'show_checkout' => 'yes',
					'show_images'   => 'yes',
					'shop_url'      => '',
				)
			),
			is_array( $atts ) ? $atts : array(),
			'neximan_invoice'
		);

		return self::render( $atts );
	}

	/**
	 * Renders the invoice markup from the current cart.
	 *
	 * @param array $labels Label / option overrides.
	 * @return string HTML.
	 */
	public static function render( $labels = array() ) {
		wp_enqueue_style( 'neximan-invoice' );

		$l = array_merge( self::default_labels(), is_array( $labels ) ? $labels : array() );

		$show_checkout = ! isset( $l['show_checkout'] ) || 'no' !== $l['show_checkout'];
		$show_images   = ! isset( $l['show_images'] ) || 'no' !== $l['show_images'];

		if ( ! class_exists( 'WooCommerce' ) || is_null( WC()->cart ) ) {
			return '<div class="neximan-invoice neximan-invoice--empty"><p>' . esc_html( $l['empty'] ) . '</p></div>';
		}

		$cart  = WC()->cart;
		$items = $cart->get_cart();

		ob_start();

		if ( empty( $items ) ) {
			$shop = ! empty( $l['shop_url'] ) ? $l['shop_url'] : ( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) );
			?>
			<div class="neximan-invoice neximan-invoice--empty">
				<div class="neximan-invoice-emptyicon">🛒</div>
				<p><?php echo esc_html( $l['empty'] ); ?></p>
				<a class="neximan-invoice-btn neximan-invoice-btn--ghost" href="<?php echo esc_url( $shop ); ?>"><?php echo esc_html( $l['continue'] ); ?></a>
			</div>
			<?php
			return ob_get_clean();
		}
		?>
		<div class="neximan-invoice" dir="rtl">
			<div class="neximan-invoice-head">
				<div>
					<h2 class="neximan-invoice-title"><?php echo esc_html( $l['title'] ); ?></h2>
					<?php if ( ! empty( $l['subtitle'] ) ) : ?>
						<p class="neximan-invoice-subtitle"><?php echo esc_html( $l['subtitle'] ); ?></p>
					<?php endif; ?>
				</div>
				<div class="neximan-invoice-logo"><?php bloginfo( 'name' ); ?></div>
			</div>

			<div class="neximan-invoice-list">
				<?php
				foreach ( $items as $item ) {
					self::render_item( $item, $l, $show_images );
				}
				?>
			</div>

			<div class="neximan-invoice-totals">
				<?php
				$subtotal = $cart->get_cart_subtotal();
				$discount = $cart->get_cart_discount_total();
				$total    = $cart->get_total();
				?>
				<div class="neximan-invoice-row">
					<span><?php echo esc_html( $l['subtotal'] ); ?></span>
					<span class="neximan-invoice-amount"><?php echo wp_kses_post( $subtotal ); ?></span>
				</div>
				<?php if ( $discount > 0 ) : ?>
					<div class="neximan-invoice-row">
						<span><?php echo esc_html( $l['discount'] ); ?></span>
						<span class="neximan-invoice-amount">- <?php echo wp_kses_post( wc_price( $discount ) ); ?></span>
					</div>
				<?php endif; ?>
				<div class="neximan-invoice-row neximan-invoice-row--grand">
					<span><?php echo esc_html( $l['grand_total'] ); ?></span>
					<span class="neximan-invoice-amount"><?php echo wp_kses_post( $total ); ?></span>
				</div>
			</div>

			<?php if ( $show_checkout ) : ?>
				<div class="neximan-invoice-actions">
					<a class="neximan-invoice-btn" href="<?php echo esc_url( wc_get_checkout_url() ); ?>">
						<?php echo esc_html( $l['checkout'] ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Renders a single cart line as an invoice card.
	 *
	 * @param array $item        Cart item.
	 * @param array $l           Labels.
	 * @param bool  $show_images Whether to show the product image.
	 * @return void
	 */
	private static function render_item( $item, $l, $show_images ) {
		$product = isset( $item['data'] ) ? $item['data'] : null;
		if ( ! $product ) {
			return;
		}

		$name      = $product->get_name();
		$qty       = isset( $item['quantity'] ) ? (int) $item['quantity'] : 1;
		$line_total = isset( $item['line_total'] ) ? (float) $item['line_total'] : 0;
		$data      = isset( $item[ WooCommerce::CART_KEY ] ) ? $item[ WooCommerce::CART_KEY ] : array();
		$image     = $show_images ? $product->get_image( 'woocommerce_thumbnail' ) : '';

		// Build the detail rows (series/model/layout/color/options + add-ons).
		$rows = array();
		if ( ! empty( $data['series_name'] ) ) {
			$rows[] = array( $l['series'], $data['series_name'] );
		}
		if ( ! empty( $data['module_name'] ) ) {
			$rows[] = array( $l['model'], $data['module_name'] );
		}
		if ( ! empty( $data['layout_label'] ) ) {
			$rows[] = array( $l['layout'], $data['layout_label'] );
		}
		if ( ! empty( $data['color_name'] ) ) {
			$rows[] = array( $l['color'], $data['color_name'] );
		}
		if ( ! empty( $data['option_labels'] ) && is_array( $data['option_labels'] ) ) {
			foreach ( $data['option_labels'] as $opt ) {
				if ( ! empty( $opt['value'] ) ) {
					$rows[] = array( ! empty( $opt['label'] ) ? $opt['label'] : $l['col_details'], $opt['value'] );
				}
			}
		}
		?>
		<div class="neximan-invoice-item">
			<?php if ( $show_images && $image ) : ?>
				<div class="neximan-invoice-thumb"><?php echo wp_kses_post( $image ); ?></div>
			<?php endif; ?>

			<div class="neximan-invoice-body">
				<div class="neximan-invoice-name">
					<?php echo esc_html( $name ); ?>
					<?php if ( ! empty( $data ) ) : ?>
						<span class="neximan-invoice-badge"><?php echo esc_html( $l['badge'] ); ?></span>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $rows ) ) : ?>
					<div class="neximan-invoice-specs">
						<?php foreach ( $rows as $row ) : ?>
							<div class="neximan-invoice-spec">
								<span class="neximan-invoice-spec-k"><?php echo esc_html( $row[0] ); ?></span>
								<span class="neximan-invoice-spec-v"><?php echo esc_html( $row[1] ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="neximan-invoice-meta">
				<div class="neximan-invoice-qty"><?php echo esc_html( $l['col_qty'] ); ?>: <?php echo esc_html( self::fa_num( $qty ) ); ?></div>
				<div class="neximan-invoice-line"><?php echo wp_kses_post( wc_price( $line_total ) ); ?></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Converts Western digits to Persian digits for nicer RTL display.
	 *
	 * @param string|int $value Value.
	 * @return string
	 */
	public static function fa_num( $value ) {
		$west = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
		$fa   = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
		return str_replace( $west, $fa, (string) $value );
	}
}
