<?php
/**
 * Main plugin class.
 *
 * @package Neximan_Furniture_Builder
 */

namespace Neximan\Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin
 *
 * Singleton that wires up the Elementor widget, assets and WooCommerce bridge.
 */
final class Plugin {

	/**
	 * Single instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * WooCommerce bridge instance.
	 *
	 * @var WooCommerce|null
	 */
	public $woocommerce = null;

	/**
	 * Custom post type handler instance.
	 *
	 * @var CPT|null
	 */
	public $cpt = null;

	/**
	 * Returns the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor: register hooks.
	 */
	private function __construct() {
		// Register the widget category.
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );

		// Register widgets.
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

		// Register front-end assets.
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_scripts' ) );
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_styles' ) );

		// Invoice styles on WooCommerce pages (cart, checkout, order).
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_invoice_styles' ) );

		// WooCommerce bridge (cart / order item data, AJAX handlers).
		require_once NEXIMAN_BUILDER_PATH . 'includes/class-neximan-config.php';
		require_once NEXIMAN_BUILDER_PATH . 'includes/class-neximan-cpt.php';
		require_once NEXIMAN_BUILDER_PATH . 'includes/class-neximan-woocommerce.php';

		// Register the "series" custom post type and its admin UI.
		$this->cpt = new CPT();
		$this->cpt->register();

		$this->woocommerce = new WooCommerce();
		$this->woocommerce->register();
	}

	/**
	 * Registers the "Neximan" Elementor category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 * @return void
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'neximan',
			array(
				'title' => __( 'Neximan', 'neximan-builder' ),
				'icon'  => 'eicon-products',
			)
		);
	}

	/**
	 * Registers the plugin widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		require_once NEXIMAN_BUILDER_PATH . 'widgets/class-neximan-builder-widget.php';
		$widgets_manager->register( new Widgets\Builder_Widget() );
	}

	/**
	 * Registers front-end scripts.
	 *
	 * @return void
	 */
	public function register_scripts() {
		wp_register_script(
			'neximan-builder',
			NEXIMAN_BUILDER_URL . 'assets/js/builder.js',
			array( 'jquery' ),
			NEXIMAN_BUILDER_VERSION,
			true
		);

		wp_localize_script(
			'neximan-builder',
			'NeximanBuilderConfig',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'neximan_builder' ),
				'cartUrl'    => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
				'hasWoo'     => class_exists( 'WooCommerce' ) ? 1 : 0,
				'i18n'       => array(
					'added'   => __( 'Added to cart', 'neximan-builder' ),
					'adding'  => __( 'Adding...', 'neximan-builder' ),
					'error'   => __( 'Something went wrong. Please try again.', 'neximan-builder' ),
				),
			)
		);
	}

	/**
	 * Registers front-end styles.
	 *
	 * @return void
	 */
	public function register_styles() {
		wp_register_style(
			'neximan-builder',
			NEXIMAN_BUILDER_URL . 'assets/css/builder.css',
			array(),
			NEXIMAN_BUILDER_VERSION
		);
	}

	/**
	 * Enqueues the invoice styles on WooCommerce cart / checkout / order pages.
	 *
	 * @return void
	 */
	public function enqueue_invoice_styles() {
		if ( ! function_exists( 'is_cart' ) ) {
			return;
		}

		if ( is_cart() || is_checkout() || is_account_page() || is_wc_endpoint_url( 'order-received' ) || is_wc_endpoint_url( 'view-order' ) ) {
			wp_enqueue_style(
				'neximan-invoice',
				NEXIMAN_BUILDER_URL . 'assets/css/invoice.css',
				array(),
				NEXIMAN_BUILDER_VERSION
			);
		}
	}
}
