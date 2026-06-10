<?php
/**
 * Neximan Furniture Builder Elementor widget.
 *
 * @package Neximan_Furniture_Builder
 */

namespace Neximan\Builder\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Neximan\Builder\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Builder_Widget
 *
 * Renders a configurable furniture builder (sofa / table / ...) with dynamic
 * pricing and optional WooCommerce add-to-cart. Supports two data sources:
 *  - inline : quick, fixed configuration via Elementor repeaters/slots.
 *  - posts  : unlimited models/layouts pulled from "Neximan Builder" series
 *             posts, with one or more parent products (Noah, Melorin, ...).
 */
class Builder_Widget extends Widget_Base {

	/**
	 * Maximum number of layout image slots in inline mode.
	 *
	 * @var int
	 */
	const MAX_SLOTS = 8;

	/**
	 * Widget machine name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'neximan_furniture_builder';
	}

	/**
	 * Widget human title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Furniture Builder', 'neximan-builder' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-products';
	}

	/**
	 * Widget categories.
	 *
	 * @return string[]
	 */
	public function get_categories() {
		return array( 'neximan' );
	}

	/**
	 * Search keywords.
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'sofa', 'furniture', 'builder', 'configurator', 'mobl', 'woocommerce', 'neximan' );
	}

	/**
	 * Front-end script handles.
	 *
	 * @return string[]
	 */
	public function get_script_depends() {
		return array( 'neximan-builder' );
	}

	/**
	 * Front-end style handles.
	 *
	 * @return string[]
	 */
	public function get_style_depends() {
		return array( 'neximan-builder' );
	}

	/**
	 * Registers all widget controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_series_controls();
		$this->register_layout_controls();
		$this->register_module_controls();
		$this->register_color_controls();
		$this->register_woocommerce_controls();
		$this->register_style_controls();
	}

	/**
	 * General content controls.
	 *
	 * @return void
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'General', 'neximan-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'data_source',
			array(
				'label'       => __( 'Data Source', 'neximan-builder' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'posts',
				'options'     => array(
					'inline' => __( 'Inline (simple / fixed)', 'neximan-builder' ),
					'posts'  => __( 'Builder Posts (unlimited / multiple products)', 'neximan-builder' ),
				),
				'description' => __( 'Inline: configure everything here. Builder Posts: select one or more "Neximan Builder" series (e.g. Noah, Melorin), each a parent product with unlimited models & layouts.', 'neximan-builder' ),
			)
		);

		$this->add_control(
			'inline_notice',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => __( 'Tables, accessories (modules), real sizes (60/85) and the live assembly preview are only available in <strong>Builder Posts</strong> mode. Switch Data Source to "Builder Posts", then manage products under the <strong>Neximan Builders</strong> menu.', 'neximan-builder' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'condition'       => array( 'data_source' => 'inline' ),
			)
		);

		$this->add_control(
			'title_text',
			array(
				'label'       => __( 'Title', 'neximan-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Build your own sofa', 'neximan-builder' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'subtitle_text',
			array(
				'label'       => __( 'Subtitle', 'neximan-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Choose your favorite combination and color', 'neximan-builder' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'layout_section_label',
			array(
				'label'   => __( 'Layout Section Label', 'neximan-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Arrangement', 'neximan-builder' ),
			)
		);

		$this->add_control(
			'color_section_label',
			array(
				'label'   => __( 'Color Section Label', 'neximan-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Fabric color', 'neximan-builder' ),
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label'   => __( 'Button Text', 'neximan-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Choose this combination', 'neximan-builder' ),
			)
		);

		$this->add_control(
			'heading_price',
			array(
				'label'     => __( 'Price', 'neximan-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'show_price',
			array(
				'label'        => __( 'Show Live Price', 'neximan-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'price_label',
			array(
				'label'     => __( 'Price Label', 'neximan-builder' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Price', 'neximan-builder' ),
				'condition' => array( 'show_price' => 'yes' ),
			)
		);

		$this->add_control(
			'currency_symbol',
			array(
				'label'   => __( 'Currency Symbol', 'neximan-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Toman', 'neximan-builder' ),
			)
		);

		$this->add_control(
			'currency_position',
			array(
				'label'   => __( 'Currency Position', 'neximan-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'after',
				'options' => array(
					'before' => __( 'Before amount', 'neximan-builder' ),
					'after'  => __( 'After amount', 'neximan-builder' ),
				),
			)
		);

		$this->add_control(
			'thousand_separator',
			array(
				'label'   => __( 'Thousand Separator', 'neximan-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => ',',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Series selection controls (post data source).
	 *
	 * @return void
	 */
	private function register_series_controls() {
		$this->start_controls_section(
			'section_series',
			array(
				'label'     => __( 'Series / Products', 'neximan-builder' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => array( 'data_source' => 'posts' ),
			)
		);

		$this->add_control(
			'series_ids',
			array(
				'label'       => __( 'Select Builders', 'neximan-builder' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->get_series_options(),
				'description' => __( 'Each selected builder appears as a top-level tab (e.g. Noah, Melorin). Manage them under "Neximan Builders".', 'neximan-builder' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Returns published series posts as id => title options.
	 *
	 * @return array
	 */
	private function get_series_options() {
		$options = array();

		$posts = get_posts(
			array(
				'post_type'      => Config::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		foreach ( $posts as $post ) {
			$options[ $post->ID ] = $post->post_title ? $post->post_title : sprintf( '#%d', $post->ID );
		}

		return $options;
	}

	/**
	 * Inline layout slot controls.
	 *
	 * @return void
	 */
	private function register_layout_controls() {
		$this->start_controls_section(
			'section_layouts',
			array(
				'label'     => __( 'Arrangements', 'neximan-builder' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => array( 'data_source' => 'inline' ),
			)
		);

		$this->add_control(
			'layouts_help',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => __( 'Each arrangement (2-seater, L-shape, ...) is a button with its own image and price.', 'neximan-builder' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'layout_label',
			array(
				'label'       => __( 'Label', 'neximan-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Arrangement', 'neximan-builder' ),
				'label_block' => true,
			)
		);

		$repeater->add_control(
			'layout_image',
			array(
				'label'   => __( 'Image', 'neximan-builder' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array( 'url' => '' ),
			)
		);

		$repeater->add_control(
			'layout_price',
			array(
				'label'       => __( 'Price', 'neximan-builder' ),
				'type'        => Controls_Manager::NUMBER,
				'description' => __( 'Price of this arrangement before color modifiers.', 'neximan-builder' ),
				'default'     => 0,
				'min'         => 0,
				'step'        => 1,
			)
		);

		$repeater->add_control(
			'layout_type',
			array(
				'label'   => __( 'Type', 'neximan-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'sofa',
				'options' => array(
					'sofa'   => __( 'Sofa', 'neximan-builder' ),
					'table'  => __( 'Table', 'neximan-builder' ),
					'bed'    => __( 'Bed', 'neximan-builder' ),
					'chair'  => __( 'Chair', 'neximan-builder' ),
					'custom' => __( 'Custom', 'neximan-builder' ),
				),
			)
		);

		$this->add_control(
			'layouts',
			array(
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ layout_label }}}',
				'default'     => array(
					array(
						'layout_label' => __( '2-seater', 'neximan-builder' ),
						'layout_price' => 5000000,
						'layout_type'  => 'sofa',
					),
					array(
						'layout_label' => __( '3-seater', 'neximan-builder' ),
						'layout_price' => 7500000,
						'layout_type'  => 'sofa',
					),
					array(
						'layout_label' => __( '4-seater', 'neximan-builder' ),
						'layout_price' => 10000000,
						'layout_type'  => 'sofa',
					),
					array(
						'layout_label' => __( 'L-shape', 'neximan-builder' ),
						'layout_price' => 12000000,
						'layout_type'  => 'sofa',
					),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Inline add-on controls (e.g. coffee table) the customer can add with a
	 * quantity. Add-ons increase the price by (qty x add-on price).
	 *
	 * @return void
	 */
	private function register_module_controls() {
		$this->start_controls_section(
			'section_addons',
			array(
				'label'     => __( 'Add-ons', 'neximan-builder' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => array( 'data_source' => 'inline' ),
			)
		);

		$this->add_control(
			'addons_section_label',
			array(
				'label'   => __( 'Add-ons Section Label', 'neximan-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Add-ons', 'neximan-builder' ),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'addon_name',
			array(
				'label'       => __( 'Name', 'neximan-builder' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$repeater->add_control(
			'addon_price',
			array(
				'label'   => __( 'Unit Price', 'neximan-builder' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 0,
				'min'     => 0,
				'step'    => 1,
			)
		);

		$repeater->add_control(
			'addon_max',
			array(
				'label'       => __( 'Max Quantity', 'neximan-builder' ),
				'type'        => Controls_Manager::NUMBER,
				'description' => __( 'Maximum number the customer can add (e.g. 3 coffee tables).', 'neximan-builder' ),
				'default'     => 3,
				'min'         => 1,
				'step'        => 1,
			)
		);

		$this->add_control(
			'addons',
			array(
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ addon_name }}}',
				'default'     => array(
					array(
						'addon_name'  => __( 'Coffee table', 'neximan-builder' ),
						'addon_price' => 1500000,
						'addon_max'   => 3,
					),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Inline fabric color controls.
	 *
	 * @return void
	 */
	private function register_color_controls() {
		$this->start_controls_section(
			'section_colors',
			array(
				'label'     => __( 'Fabric Colors', 'neximan-builder' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => array( 'data_source' => 'inline' ),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'color_value',
			array(
				'label'   => __( 'Color', 'neximan-builder' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#cccccc',
			)
		);

		$repeater->add_control(
			'color_name',
			array(
				'label'       => __( 'Color Name', 'neximan-builder' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$repeater->add_control(
			'color_price',
			array(
				'label'   => __( 'Price Modifier', 'neximan-builder' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 0,
				'min'     => 0,
				'step'    => 1,
			)
		);

		$this->add_control(
			'colors',
			array(
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ color_name }}}',
				'default'     => array(
					array(
						'color_value' => '#4a4a4a',
						'color_name'  => __( 'Black', 'neximan-builder' ),
					),
					array(
						'color_value' => '#9bb89b',
						'color_name'  => __( 'Green', 'neximan-builder' ),
					),
					array(
						'color_value' => '#f0e6d6',
						'color_name'  => __( 'Cream', 'neximan-builder' ),
					),
					array(
						'color_value' => '#e9b576',
						'color_name'  => __( 'Honey', 'neximan-builder' ),
					),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * WooCommerce integration controls.
	 *
	 * @return void
	 */
	private function register_woocommerce_controls() {
		$this->start_controls_section(
			'section_woo',
			array(
				'label' => __( 'WooCommerce', 'neximan-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->add_control(
				'woo_missing',
				array(
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => __( 'WooCommerce is not active. Install and activate it to enable cart and checkout.', 'neximan-builder' ),
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
				)
			);
		}

		$this->add_control(
			'woo_action',
			array(
				'label'   => __( 'Button Action', 'neximan-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'add_to_cart',
				'options' => array(
					'none'        => __( 'Do nothing (display only)', 'neximan-builder' ),
					'add_to_cart' => __( 'Add to cart (AJAX)', 'neximan-builder' ),
					'buy_now'     => __( 'Add to cart & go to checkout', 'neximan-builder' ),
				),
			)
		);

		$this->add_control(
			'woo_price_mode',
			array(
				'label'       => __( 'Price Mode (inline)', 'neximan-builder' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'dynamic',
				'description' => __( 'Used for the inline data source. Builder Posts define their own price mode.', 'neximan-builder' ),
				'options'     => array(
					'dynamic'   => __( 'Dynamic (builder price)', 'neximan-builder' ),
					'product'   => __( 'WooCommerce product price', 'neximan-builder' ),
					'variation' => __( 'WooCommerce variation price', 'neximan-builder' ),
				),
				'condition'   => array(
					'woo_action!'  => 'none',
					'data_source'  => 'inline',
				),
			)
		);

		$this->add_control(
			'var_attr_layout',
			array(
				'label'       => __( 'Variation Attribute (layout)', 'neximan-builder' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Optional. WooCommerce attribute name for layouts, e.g. pa_chideman.', 'neximan-builder' ),
				'condition'   => array(
					'data_source'    => 'inline',
					'woo_price_mode' => 'variation',
				),
			)
		);

		$this->add_control(
			'var_attr_color',
			array(
				'label'       => __( 'Variation Attribute (color)', 'neximan-builder' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Optional. WooCommerce attribute name for colors, e.g. pa_color.', 'neximan-builder' ),
				'condition'   => array(
					'data_source'    => 'inline',
					'woo_price_mode' => 'variation',
				),
			)
		);

		$this->add_control(
			'bind_current_product',
			array(
				'label'        => __( 'Bind to Current Product', 'neximan-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'no',
				'description'  => __( 'When placed on a WooCommerce single product page, add THIS product to the cart with the chosen configuration (overrides series/model/fallback product IDs).', 'neximan-builder' ),
				'condition'    => array( 'woo_action!' => 'none' ),
			)
		);

		$this->add_control(
			'woo_fallback_product',
			array(
				'label'       => __( 'Fallback Product ID', 'neximan-builder' ),
				'type'        => Controls_Manager::NUMBER,
				'description' => __( 'Used when a model/series has no specific product. Useful for dynamic-priced custom products.', 'neximan-builder' ),
				'min'         => 0,
				'step'        => 1,
				'condition'   => array(
					'woo_action!'          => 'none',
					'bind_current_product!' => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style controls.
	 *
	 * @return void
	 */
	private function register_style_controls() {
		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Style', 'neximan-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'accent_color',
			array(
				'label'     => __( 'Accent Color', 'neximan-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#10b981',
				'selectors' => array(
					'{{WRAPPER}} .neximan-builder' => '--nx-accent: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'dark_color',
			array(
				'label'     => __( 'Dark / Heading Color', 'neximan-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2C3E50',
				'selectors' => array(
					'{{WRAPPER}} .neximan-builder' => '--nx-dark: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'preview_bg_start',
			array(
				'label'     => __( 'Preview Background (start)', 'neximan-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#E8EEEE',
				'selectors' => array(
					'{{WRAPPER}} .neximan-builder' => '--nx-preview-1: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'preview_bg_end',
			array(
				'label'     => __( 'Preview Background (end)', 'neximan-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#D3EAE7',
				'selectors' => array(
					'{{WRAPPER}} .neximan-builder' => '--nx-preview-2: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .neximan-builder-header h2',
				'global'   => array( 'default' => Global_Typography::TYPOGRAPHY_PRIMARY ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Builds one normalized series from inline (Elementor repeater) settings.
	 *
	 * @param array $settings Widget settings.
	 * @return array Series array.
	 */
	private static function build_inline_series( array $settings ) {
		// Each arrangement becomes a layout under a single model.
		$layouts = array();

		if ( ! empty( $settings['layouts'] ) && is_array( $settings['layouts'] ) ) {
			foreach ( $settings['layouts'] as $index => $layout ) {
				$layouts[] = array(
					'id'    => 'l' . $index,
					'label' => isset( $layout['layout_label'] ) ? $layout['layout_label'] : '',
					'image' => ! empty( $layout['layout_image']['url'] ) ? esc_url_raw( $layout['layout_image']['url'] ) : '',
					'price' => isset( $layout['layout_price'] ) ? (float) $layout['layout_price'] : 0,
					'type'  => isset( $layout['layout_type'] ) ? $layout['layout_type'] : 'sofa',
					'parts' => array(),
				);
			}
		}

		$model = array(
			'id'        => 'm0',
			'name'      => isset( $settings['title_text'] ) ? $settings['title_text'] : '',
			'type'      => 'sofa',
			'basePrice' => 0,
			'wooId'     => 0,
			'layouts'   => $layouts,
		);

		// Colors.
		$colors = array();
		if ( ! empty( $settings['colors'] ) && is_array( $settings['colors'] ) ) {
			foreach ( $settings['colors'] as $cindex => $color ) {
				$colors[] = array(
					'id'       => 'c' . $cindex,
					'name'     => isset( $color['color_name'] ) ? $color['color_name'] : '',
					'value'    => isset( $color['color_value'] ) ? $color['color_value'] : '#cccccc',
					'price'    => isset( $color['color_price'] ) ? (float) $color['color_price'] : 0,
					'varValue' => '',
				);
			}
		}

		// Add-ons (e.g. coffee table) with a quantity selector.
		$addons = array();
		if ( ! empty( $settings['addons'] ) && is_array( $settings['addons'] ) ) {
			foreach ( $settings['addons'] as $aindex => $addon ) {
				if ( empty( $addon['addon_name'] ) ) {
					continue;
				}
				$addons[] = array(
					'id'    => 'a' . $aindex,
					'name'  => $addon['addon_name'],
					'price' => isset( $addon['addon_price'] ) ? (float) $addon['addon_price'] : 0,
					'max'   => isset( $addon['addon_max'] ) ? max( 1, (int) $addon['addon_max'] ) : 1,
				);
			}
		}

		return array(
			'id'           => 'inline',
			'postId'       => 0,
			'name'         => '',
			'wooId'        => isset( $settings['woo_fallback_product'] ) ? (int) $settings['woo_fallback_product'] : 0,
			'priceMode'    => isset( $settings['woo_price_mode'] ) ? $settings['woo_price_mode'] : 'dynamic',
			'pricingMode'  => 'simple',
			'addonsLabel'  => isset( $settings['addons_section_label'] ) ? $settings['addons_section_label'] : '',
			'varAttrs'     => array(
				'layout' => isset( $settings['var_attr_layout'] ) ? $settings['var_attr_layout'] : '',
				'color'  => isset( $settings['var_attr_color'] ) ? $settings['var_attr_color'] : '',
			),
			'modules'      => array(),
			'models'       => array( $model ),
			'colors'       => $colors,
			'options'      => array(),
			'addons'       => $addons,
		);
	}

	/**
	 * Builds the full normalized config (series list + meta) from settings.
	 *
	 * @param array $settings Widget settings.
	 * @return array
	 */
	public static function build_config( array $settings ) {
		$source = isset( $settings['data_source'] ) ? $settings['data_source'] : 'inline';
		$series = array();

		if ( 'posts' === $source ) {
			$ids = isset( $settings['series_ids'] ) ? (array) $settings['series_ids'] : array();
			foreach ( $ids as $id ) {
				$id = (int) $id;
				if ( ! $id ) {
					continue;
				}
				$one = Config::get_series( $id );
				if ( null === $one ) {
					continue;
				}
				$raw                = Config::get_raw( $id );
				$one['priceMode']   = isset( $raw['priceMode'] ) ? $raw['priceMode'] : 'dynamic';
				$one['fallbackWoo'] = isset( $settings['woo_fallback_product'] ) ? (int) $settings['woo_fallback_product'] : 0;
				$series[]           = $one;
			}
		} else {
			$series[] = self::build_inline_series( $settings );
		}

		return array(
			'source'    => $source,
			'currency'  => array(
				'symbol'    => isset( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : '',
				'position'  => isset( $settings['currency_position'] ) ? $settings['currency_position'] : 'after',
				'separator' => isset( $settings['thousand_separator'] ) ? $settings['thousand_separator'] : ',',
			),
			'labels'    => array(
				'layout' => isset( $settings['layout_section_label'] ) ? $settings['layout_section_label'] : '',
				'color'  => isset( $settings['color_section_label'] ) ? $settings['color_section_label'] : '',
			),
			'showPrice' => ( isset( $settings['show_price'] ) && 'yes' === $settings['show_price'] ),
			'woo'       => array(
				'action'          => isset( $settings['woo_action'] ) ? $settings['woo_action'] : 'none',
				'fallbackProduct' => isset( $settings['woo_fallback_product'] ) ? (int) $settings['woo_fallback_product'] : 0,
				'forceProduct'    => 0,
			),
			'series'    => $series,
		);
	}

	/**
	 * Renders the widget on the front-end. The interactive control panel is
	 * built by builder.js from the embedded JSON; PHP outputs the shell.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$config   = self::build_config( $settings );

		$uid     = 'neximan-' . $this->get_id();
		$post_id = get_the_ID();

		// Single product page integration: force the current product.
		if ( isset( $settings['bind_current_product'] ) && 'yes' === $settings['bind_current_product']
			&& function_exists( 'is_product' ) && is_product() ) {
			$config['woo']['forceProduct'] = (int) get_the_ID();
		}

		if ( empty( $config['series'] ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="neximan-builder"><p style="padding:2rem;text-align:center">' . esc_html__( 'Select a Builder series or configure the inline data source.', 'neximan-builder' ) . '</p></div>';
			}
			return;
		}

		// Build the signed pricing manifest. The cart price is recomputed from
		// this manifest server-side; signing the exact JSON string lets the
		// client echo it back without serialization mismatches.
		$manifest      = Config::build_manifest( $config['series'], $config['woo'] );
		$manifest_json = Config::json( $manifest );
		$signature     = Config::sign_json( $manifest_json );
		?>
		<div id="<?php echo esc_attr( $uid ); ?>"
			class="neximan-builder"
			data-neximan-builder="<?php echo esc_attr( $uid ); ?>"
			data-source="<?php echo esc_attr( $config['source'] ); ?>"
			data-post-id="<?php echo esc_attr( $post_id ); ?>"
			data-widget-id="<?php echo esc_attr( $this->get_id() ); ?>"
			data-sig="<?php echo esc_attr( $signature ); ?>">
			<div class="neximan-builder-inner">

				<div class="neximan-series-tabs"></div>

				<div class="neximan-builder-header">
					<?php if ( ! empty( $settings['title_text'] ) ) : ?>
						<h2><?php echo esc_html( $settings['title_text'] ); ?></h2>
					<?php endif; ?>
					<?php if ( ! empty( $settings['subtitle_text'] ) ) : ?>
						<p><?php echo esc_html( $settings['subtitle_text'] ); ?></p>
					<?php endif; ?>
				</div>

				<div class="neximan-builder-grid">

					<div class="neximan-preview-area">
						<div class="neximan-stage">
							<div class="neximan-assembly"></div>
						</div>
						<div class="neximan-config-info">
							<strong class="neximan-info-layout"></strong>
							<span class="neximan-info-module"></span>
						</div>
					</div>

					<div class="neximan-control-panel">
						<div class="neximan-model-tabs-wrap neximan-control-section">
							<div class="neximan-model-tabs"></div>
						</div>

						<div class="neximan-control-section">
							<label class="neximan-control-label"><?php echo esc_html( $settings['layout_section_label'] ); ?></label>
							<div class="neximan-layout-options"></div>
						</div>

						<div class="neximan-parts"></div>

						<div class="neximan-options"></div>

						<div class="neximan-addons"></div>

						<div class="neximan-control-section">
							<label class="neximan-control-label"><?php echo esc_html( $settings['color_section_label'] ); ?></label>
							<div class="neximan-color-options"></div>
						</div>

						<?php if ( $config['showPrice'] ) : ?>
							<div class="neximan-price-box">
								<span class="neximan-price-label"><?php echo esc_html( $settings['price_label'] ); ?></span>
								<span class="neximan-price-value"></span>
							</div>
						<?php endif; ?>

						<button type="button" class="neximan-cta" data-action="<?php echo esc_attr( $config['woo']['action'] ); ?>">
							<span class="neximan-cta-text"><?php echo esc_html( $settings['button_text'] ); ?></span>
						</button>

						<div class="neximan-feedback" role="status" aria-live="polite"></div>
					</div>

				</div>
			</div>

			<script type="application/json" class="neximan-config"><?php echo Config::json( $config ); ?></script>
			<script type="application/json" class="neximan-manifest"><?php echo $manifest_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON, signed and consumed verbatim. ?></script>
		</div>
		<?php
	}
}
