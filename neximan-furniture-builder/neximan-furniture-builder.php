<?php
/**
 * Plugin Name: Neximan Furniture Builder
 * Plugin URI:  https://github.com/ojemusoba882-sketch/neximan
 * Description: A standalone Elementor widget to build configurable furniture (sofas, tables, ...) with dynamic pricing and WooCommerce cart/checkout integration.
 * Version:     1.0.0
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

define( 'NEXIMAN_BUILDER_VERSION', '1.0.0' );
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
				'name'  => 'نشیمن ۶۰',
				'price' => 2500000,
			),
			array(
				'id'    => 'seat85',
				'name'  => 'نشیمن ۸۵',
				'price' => 3200000,
			),
			array(
				'id'    => 'corner',
				'name'  => 'کنج (۲ پشتی)',
				'price' => 3800000,
			),
			array(
				'id'    => 'pouf60',
				'name'  => 'پاف ۶۰',
				'price' => 1800000,
			),
			array(
				'id'    => 'pouf85',
				'name'  => 'پاف ۸۵',
				'price' => 2200000,
			),
			array(
				'id'    => 'sidetable',
				'name'  => 'میز عسلی',
				'price' => 1500000,
			),
		),
		'colors'       => array(
			array(
				'id'    => 'cblack',
				'name'  => 'مشکی',
				'value' => '#4a4a4a',
				'price' => 0,
			),
			array(
				'id'    => 'cgreen',
				'name'  => 'سبز',
				'value' => '#9bb89b',
				'price' => 0,
			),
			array(
				'id'    => 'ccream',
				'name'  => 'کرم',
				'value' => '#f0e6d6',
				'price' => 0,
			),
			array(
				'id'    => 'choney',
				'name'  => 'عسلی',
				'value' => '#e9b576',
				'price' => 0,
			),
		),
		'options'      => array(),
		'models'       => array(
			array(
				'id'        => 'msofa',
				'name'      => 'مبل',
				'type'      => 'sofa',
				'basePrice' => 0,
				'wooId'     => 0,
				'layouts'   => array(
					// 2-seater: two middle seats, each selectable 60/85.
					array(
						'id'    => 'l2',
						'label' => '۲ نفره',
						'image' => '',
						'price' => 0,
						'parts' => array(
							array(
								'id'        => 'l2p1',
								'label'     => 'سایز نشیمن',
								'qty'       => 2,
								'moduleIds' => array( 'seat60', 'seat85' ),
							),
						),
					),
					// 3-seater: three middle seats, selectable 60/85.
					array(
						'id'    => 'l3',
						'label' => '۳ نفره',
						'image' => '',
						'price' => 0,
						'parts' => array(
							array(
								'id'        => 'l3p1',
								'label'     => 'سایز نشیمن',
								'qty'       => 3,
								'moduleIds' => array( 'seat60', 'seat85' ),
							),
						),
					),
					// 4-seater.
					array(
						'id'    => 'l4',
						'label' => '۴ نفره',
						'image' => '',
						'price' => 0,
						'parts' => array(
							array(
								'id'        => 'l4p1',
								'label'     => 'سایز نشیمن',
								'qty'       => 4,
								'moduleIds' => array( 'seat60', 'seat85' ),
							),
						),
					),
					// L-shape / corner: one fixed corner + two selectable seats.
					array(
						'id'    => 'll',
						'label' => 'L / کنج',
						'image' => '',
						'price' => 0,
						'parts' => array(
							array(
								'id'        => 'llp1',
								'label'     => 'کنج',
								'qty'       => 1,
								'moduleIds' => array( 'corner' ),
							),
							array(
								'id'        => 'llp2',
								'label'     => 'سایز نشیمن',
								'qty'       => 2,
								'moduleIds' => array( 'seat60', 'seat85' ),
							),
						),
					),
				),
			),
			array(
				'id'        => 'mtable',
				'name'      => 'میز',
				'type'      => 'table',
				'basePrice' => 0,
				'wooId'     => 0,
				'layouts'   => array(
					array(
						'id'    => 'tcoffee',
						'label' => 'میز عسلی',
						'image' => '',
						'price' => 0,
						'parts' => array(
							array(
								'id'        => 'tp1',
								'label'     => 'میز',
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
			'post_title'  => 'نوا (نمونه)',
			'post_status' => 'publish',
		)
	);

	if ( $post_id && ! is_wp_error( $post_id ) ) {
		update_post_meta( $post_id, \Neximan\Builder\Config::META_KEY, wp_json_encode( $clean ) );
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
