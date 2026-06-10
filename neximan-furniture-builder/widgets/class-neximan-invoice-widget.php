<?php
/**
 * Neximan Invoice Elementor widget.
 *
 * Renders the custom, RTL, fully-styled invoice / order summary from the cart.
 *
 * @package Neximan_Furniture_Builder
 */

namespace Neximan\Builder\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Neximan\Builder\Invoice;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Invoice_Widget
 */
class Invoice_Widget extends Widget_Base {

	/**
	 * Machine name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'neximan_invoice';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Neximan Invoice', 'neximan-builder' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	/**
	 * Categories.
	 *
	 * @return string[]
	 */
	public function get_categories() {
		return array( 'neximan' );
	}

	/**
	 * Keywords.
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'invoice', 'checkout', 'cart', 'factor', 'neximan', 'woocommerce' );
	}

	/**
	 * Style dependencies.
	 *
	 * @return string[]
	 */
	public function get_style_depends() {
		return array( 'neximan-invoice' );
	}

	/**
	 * Registers controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$labels = Invoice::default_labels();

		$this->start_controls_section(
			'section_text',
			array(
				'label' => __( 'Texts', 'neximan-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		// All label fields are editable so they can be translated to Persian.
		$fields = array(
			'title'       => __( 'Title', 'neximan-builder' ),
			'subtitle'    => __( 'Subtitle', 'neximan-builder' ),
			'badge'       => __( 'Item Badge', 'neximan-builder' ),
			'series'      => __( 'Label: Series', 'neximan-builder' ),
			'model'       => __( 'Label: Model', 'neximan-builder' ),
			'layout'      => __( 'Label: Layout', 'neximan-builder' ),
			'color'       => __( 'Label: Color', 'neximan-builder' ),
			'col_qty'     => __( 'Label: Qty', 'neximan-builder' ),
			'subtotal'    => __( 'Label: Subtotal', 'neximan-builder' ),
			'discount'    => __( 'Label: Discount', 'neximan-builder' ),
			'grand_total' => __( 'Label: Grand Total', 'neximan-builder' ),
			'checkout'    => __( 'Button: Checkout', 'neximan-builder' ),
			'continue'    => __( 'Button: Continue', 'neximan-builder' ),
			'empty'       => __( 'Empty cart message', 'neximan-builder' ),
		);

		foreach ( $fields as $key => $label ) {
			$this->add_control(
				'lbl_' . $key,
				array(
					'label'       => $label,
					'type'        => Controls_Manager::TEXT,
					'default'     => isset( $labels[ $key ] ) ? $labels[ $key ] : '',
					'label_block' => true,
				)
			);
		}

		$this->add_control(
			'show_images',
			array(
				'label'        => __( 'Show Product Images', 'neximan-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_checkout',
			array(
				'label'        => __( 'Show Checkout Button', 'neximan-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		$this->register_style_section();
	}

	/**
	 * Style controls (colors / radius), all editable.
	 *
	 * @return void
	 */
	private function register_style_section() {
		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Style', 'neximan-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$map = array(
			'accent'    => array( __( 'Accent Color', 'neximan-builder' ), '#10b981', '--nx-inv-accent' ),
			'dark'      => array( __( 'Heading Color', 'neximan-builder' ), '#1f2a37', '--nx-inv-dark' ),
			'text'      => array( __( 'Text Color', 'neximan-builder' ), '#4a5568', '--nx-inv-text' ),
			'card'      => array( __( 'Card Background', 'neximan-builder' ), '#ffffff', '--nx-inv-card' ),
			'bg'        => array( __( 'Panel Background', 'neximan-builder' ), '#f3f6f5', '--nx-inv-bg' ),
			'border'    => array( __( 'Border Color', 'neximan-builder' ), '#e6efea', '--nx-inv-border' ),
		);

		foreach ( $map as $key => $cfg ) {
			$this->add_control(
				'col_' . $key,
				array(
					'label'     => $cfg[0],
					'type'      => Controls_Manager::COLOR,
					'default'   => $cfg[1],
					'selectors' => array(
						'{{WRAPPER}} .neximan-invoice' => $cfg[2] . ': {{VALUE}};',
					),
				)
			);
		}

		$this->add_control(
			'radius',
			array(
				'label'      => __( 'Corner Radius', 'neximan-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 18 ),
				'selectors'  => array(
					'{{WRAPPER}} .neximan-invoice' => '--nx-inv-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Renders the widget.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$labels = array();
		foreach ( Invoice::default_labels() as $key => $val ) {
			$labels[ $key ] = isset( $settings[ 'lbl_' . $key ] ) && '' !== $settings[ 'lbl_' . $key ] ? $settings[ 'lbl_' . $key ] : $val;
		}

		$labels['show_images']   = isset( $settings['show_images'] ) ? $settings['show_images'] : 'yes';
		$labels['show_checkout'] = isset( $settings['show_checkout'] ) ? $settings['show_checkout'] : 'yes';

		echo Invoice::render( $labels ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Render method escapes internally.
	}
}
