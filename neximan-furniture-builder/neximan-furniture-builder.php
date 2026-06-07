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
