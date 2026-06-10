<?php
/**
 * Plugin Name: Neximan Furniture Builder
 * Plugin URI:  https://github.com/ojemusoba882-sketch/neximan
 * Description: A standalone Elementor widget to build configurable furniture (sofas, tables, ...) with dynamic pricing and WooCommerce cart/checkout integration.
 * Version:     1.6.1
 * Author:      Neximan
 * Text Domain: neximan-builder
 * Domain Path: /languages
 * Requires Plugins: elementor
 * Elementor tested up to: 3.25.0
 *
 * @package Neximan_Furniture_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'NEXIMAN_BUILDER_VERSION', '1.6.1' );
define( 'NEXIMAN_BUILDER_FILE', __FILE__ );
define( 'NEXIMAN_BUILDER_PATH', plugin_dir_path( __FILE__ ) );
define( 'NEXIMAN_BUILDER_URL', plugin_dir_url( __FILE__ ) );
define( 'NEXIMAN_BUILDER_MIN_ELEMENTOR_VERSION', '3.5.0' );
define( 'NEXIMAN_BUILDER_MIN_PHP_VERSION', '7.4' );

/**
 * Bootstraps the plugin once all plugins are loaded.
 *
 * @return void
 */
function neximan_builder_bootstrap() {
	// Check Elementor is installed and active.
	if ( ! did_action( 'elementor/loaded' ) ) {
		add_action( 'admin_notices', 'neximan_builder_notice_missing_elementor' );
		return;
	}

	// Check Elementor version.
	if ( ! version_compare( ELEMENTOR_VERSION, NEXIMAN_BUILDER_MIN_ELEMENTOR_VERSION, '>=' ) ) {
		add_action( 'admin_notices', 'neximan_builder_notice_min_elementor' );
		return;
	}

	// Check PHP version.
	if ( version_compare( PHP_VERSION, NEXIMAN_BUILDER_MIN_PHP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'neximan_builder_notice_min_php' );
		return;
	}

	require_once NEXIMAN_BUILDER_PATH . 'includes/class-neximan-builder.php';
	\Neximan\Builder\Plugin::instance();
}
add_action( 'plugins_loaded', 'neximan_builder_bootstrap' );

/**
 * On activation, seed a ready-to-edit sample "Builder" so admins immediately
 * see how to add multiple product types (sofa + table) with their own layouts
 * and a size option group.
 *
 * @return void
 */
function neximan_builder_activate() {
	require_once NEXIMAN_BUILDER_PATH . 'includes/class-neximan-config.php';

	$existing = get_posts(
		array(
			'post_type'      => \Neximan\Builder\Config::POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	if ( ! empty( $existing ) ) {
		return; // Don't override an existing setup.
	}

	$config = array(
		'wooProductId' => 0,
		'priceMode'    => 'dynamic',
		'varAttrs'     => array(
			'layout' => '',
			'color'  => '',
		),
		// Building blocks with prices (fully editable in the form).
		'modules'      => array(
			array(
				'id'    => 'seat60',
				'name'  => 'Seat 60',
				'price' => 2500000,
			),
			array(
				'id'    => 'seat85',
				'name'  => 'Seat 85',
				'price' => 3200000,
			),
			array(
				'id'    => 'corner',
				'name'  => 'Corner (2 backrests)',
				'price' => 3800000,
			),
			array(
				'id'    => 'pouf60',
				'name'  => 'Pouf 60',
				'price' => 1800000,
			),
			array(
				'id'    => 'pouf85',
				'name'  => 'Pouf 85',
				'price' => 2200000,
			),
			array(
				'id'    => 'sidetable',
				'name'  => 'Coffee table',
				'price' => 1500000,
			),
		),
		'colors'       => array(
			array(
				'id'    => 'cblack',
				'name'  => 'Black',
				'value' => '#4a4a4a',
				'price' => 0,
			),
			array(
				'id'    => 'cgreen',
				'name'  => 'Green',
				'value' => '#9bb89b',
				'price' => 0,
			),
			array(
				'id'    => 'ccream',
				'name'  => 'Cream',
				'value' => '#f0e6d6',
				'price' => 0,
			),
			array(
				'id'    => 'choney',
				'name'  => 'Honey',
				'value' => '#e9b576',
				'price' => 0,
			),
		),
		'options'      => array(),
		'models'       => array(
			array(
				'id'        => 'msofa',
				'name'      => 'Sofa',
				'type'      => 'sofa',
				'basePrice' => 0,
				'wooId'     => 0,
				'layouts'   => array(
					// تک نفره: one seat, selectable 60/85.
					array(
						'id'    => 'l1',
						'label' => 'Single',
						'image' => '',
						'price' => 0,
						'parts' => array(
							array(
								'id'        => 'l1p1',
								'label'     => 'Seat size',
								'qty'       => 1,
								'moduleIds' => array( 'seat60', 'seat85' ),
							),
						),
					),
					// تک نفره کنج: a single corner module.
					array(
						'id'    => 'lcorner',
						'label' => 'Single corner',
						'image' => '',
						'price' => 0,
						'parts' => array(
							array(
								'id'        => 'lcp1',
								'label'     => 'Corner',
								'qty'       => 1,
								'moduleIds' => array( 'corner' ),
							),
						),
					),
					// دو نفره.
					array(
						'id'    => 'l2',
						'label' => '2-seater',
						'image' => '',
						'price' => 0,
						'parts' => array(
							array(
								'id'        => 'l2p1',
								'label'     => 'Seat size',
								'qty'       => 2,
								'moduleIds' => array( 'seat60', 'seat85' ),
							),
						),
					),
					// دو نفره با پاف.
					array(
						'id'    => 'l2p',
						'label' => '2-seater + pouf',
						'image' => '',
						'price' => 0,
						'parts' => array(
							array(
								'id'        => 'l2pp1',
								'label'     => 'Seat size',
								'qty'       => 2,
								'moduleIds' => array( 'seat60', 'seat85' ),
							),
							array(
								'id'        => 'l2pp2',
								'label'     => 'Pouf',
								'qty'       => 1,
								'moduleIds' => array( 'pouf60', 'pouf85' ),
							),
						),
					),
					// سه نفره (size selector covers کوتاه/بلند).
					array(
						'id'    => 'l3',
						'label' => '3-seater',
						'image' => '',
						'price' => 0,
						'parts' => array(
							array(
								'id'        => 'l3p1',
								'label'     => 'Seat size',
								'qty'       => 3,
								'moduleIds' => array( 'seat60', 'seat85' ),
							),
						),
					),
					// سه نفره با پاف.
					array(
						'id'    => 'l3p',
						'label' => '3-seater + pouf',
						'image' => '',
						'price' => 0,
						'parts' => array(
							array(
								'id'        => 'l3pp1',
								'label'     => 'Seat size',
								'qty'       => 3,
								'moduleIds' => array( 'seat60', 'seat85' ),
							),
							array(
								'id'        => 'l3pp2',
								'label'     => 'Pouf',
								'qty'       => 1,
								'moduleIds' => array( 'pouf60', 'pouf85' ),
							),
						),
					),
					// چهار نفره.
					array(
						'id'    => 'l4',
						'label' => '4-seater',
						'image' => '',
						'price' => 0,
						'parts' => array(
							array(
								'id'        => 'l4p1',
								'label'     => 'Seat size',
								'qty'       => 4,
								'moduleIds' => array( 'seat60', 'seat85' ),
							),
						),
					),
					// پنج نفره.
					array(
						'id'    => 'l5',
						'label' => '5-seater',
						'image' => '',
						'price' => 0,
						'parts' => array(
							array(
								'id'        => 'l5p1',
								'label'     => 'Seat size',
								'qty'       => 5,
								'moduleIds' => array( 'seat60', 'seat85' ),
							),
						),
					),
					// L / کنج: one corner + selectable seats.
					array(
						'id'    => 'll',
						'label' => 'L-shape / corner',
						'image' => '',
						'price' => 0,
						'parts' => array(
							array(
								'id'        => 'llp1',
								'label'     => 'Corner',
								'qty'       => 1,
								'moduleIds' => array( 'corner' ),
							),
							array(
								'id'        => 'llp2',
								'label'     => 'Seat size',
								'qty'       => 2,
								'moduleIds' => array( 'seat60', 'seat85' ),
							),
						),
					),
				),
			),
			array(
				'id'        => 'mtable',
				'name'      => 'Table',
				'type'      => 'table',
				'basePrice' => 0,
				'wooId'     => 0,
				'layouts'   => array(
					array(
						'id'    => 'tcoffee',
						'label' => 'Coffee table',
						'image' => '',
						'price' => 0,
						'parts' => array(
							array(
								'id'        => 'tp1',
								'label'     => 'Table',
								'qty'       => 1,
								'moduleIds' => array( 'sidetable' ),
							),
						),
					),
				),
			),
		),
	);

	$clean   = \Neximan\Builder\Config::sanitize( $config );
	$post_id = wp_insert_post(
		array(
			'post_type'   => \Neximan\Builder\Config::POST_TYPE,
			'post_title'  => 'Noah (sample)',
			'post_status' => 'publish',
		)
	);

	if ( $post_id && ! is_wp_error( $post_id ) ) {
		update_post_meta( $post_id, \Neximan\Builder\Config::META_KEY, \Neximan\Builder\Config::json( $clean ) );
	}
}
register_activation_hook( __FILE__, 'neximan_builder_activate' );

/**
 * Admin notice: Elementor is not installed/active.
 *
 * @return void
 */
function neximan_builder_notice_missing_elementor() {
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}

	$message = sprintf(
		/* translators: 1: Plugin name, 2: Elementor */
		esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'neximan-builder' ),
		'<strong>' . esc_html__( 'Neximan Furniture Builder', 'neximan-builder' ) . '</strong>',
		'<strong>' . esc_html__( 'Elementor', 'neximan-builder' ) . '</strong>'
	);

	printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', wp_kses_post( $message ) );
}

/**
 * Admin notice: Elementor version is too old.
 *
 * @return void
 */
function neximan_builder_notice_min_elementor() {
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}

	$message = sprintf(
		/* translators: 1: Plugin name, 2: Elementor, 3: Required version */
		esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'neximan-builder' ),
		'<strong>' . esc_html__( 'Neximan Furniture Builder', 'neximan-builder' ) . '</strong>',
		'<strong>' . esc_html__( 'Elementor', 'neximan-builder' ) . '</strong>',
		NEXIMAN_BUILDER_MIN_ELEMENTOR_VERSION
	);

	printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', wp_kses_post( $message ) );
}

/**
 * Admin notice: PHP version is too old.
 *
 * @return void
 */
function neximan_builder_notice_min_php() {
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}

	$message = sprintf(
		/* translators: 1: Plugin name, 2: PHP, 3: Required version */
		esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'neximan-builder' ),
		'<strong>' . esc_html__( 'Neximan Furniture Builder', 'neximan-builder' ) . '</strong>',
		'<strong>' . esc_html__( 'PHP', 'neximan-builder' ) . '</strong>',
		NEXIMAN_BUILDER_MIN_PHP_VERSION
	);

	printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', wp_kses_post( $message ) );
}
