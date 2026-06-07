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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Builder_Widget
 *
 * Renders a configurable furniture builder (sofa / table / ...) with dynamic
 * pricing and optional WooCommerce add-to-cart.
 */
class Builder_Widget extends Widget_Base {

	/**
	 * Maximum number of layout image slots provided per module.
	 *
	 * Layouts are defined once (shared list) and each module supplies an image
	 * for the slots it supports. A module hides layout buttons it has no image
	 * for, which lets a "table" module expose different layouts than a "sofa".
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
		$this->register_layout_controls();
		$this->register_module_controls();
		$this->register_color_controls();
		$this->register_woocommerce_controls();
		$this->register_style_controls();
	}

	/**
	 * General content controls (texts).
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
			'title_text',
			array(
				'label'       => __( 'Title', 'neximan-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'مبل خود را بسازید', 'neximan-builder' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'subtitle_text',
			array(
				'label'       => __( 'Subtitle', 'neximan-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'ترکیب و رنگ دلخواه خود را انتخاب کنید', 'neximan-builder' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'layout_section_label',
			array(
				'label'   => __( 'Layout Section Label', 'neximan-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'چیدمان مبل', 'neximan-builder' ),
			)
		);

		$this->add_control(
			'color_section_label',
			array(
				'label'   => __( 'Color Section Label', 'neximan-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'رنگ پارچه', 'neximan-builder' ),
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label'   => __( 'Button Text', 'neximan-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'انتخاب این ترکیب', 'neximan-builder' ),
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
				'label_on'     => __( 'Yes', 'neximan-builder' ),
				'label_off'    => __( 'No', 'neximan-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'price_label',
			array(
				'label'     => __( 'Price Label', 'neximan-builder' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'قیمت', 'neximan-builder' ),
				'condition' => array( 'show_price' => 'yes' ),
			)
		);

		$this->add_control(
			'currency_symbol',
			array(
				'label'   => __( 'Currency Symbol', 'neximan-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'تومان', 'neximan-builder' ),
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
	 * Layout slots controls (the shared list of configurations / arrangements).
	 *
	 * @return void
	 */
	private function register_layout_controls() {
		$this->start_controls_section(
			'section_layouts',
			array(
				'label' => __( 'Layout Options', 'neximan-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'layouts_notice',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => __( 'Define the shared list of arrangements (e.g. 2-seater, 3-seater, L-shape). Each layout maps to an image "slot". In every Module you then upload an image for the matching slot. Buttons without an image for the active module are hidden automatically.', 'neximan-builder' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'layout_label',
			array(
				'label'       => __( 'Label', 'neximan-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'چیدمان', 'neximan-builder' ),
				'label_block' => true,
			)
		);

		$repeater->add_control(
			'layout_slot',
			array(
				'label'       => __( 'Image Slot', 'neximan-builder' ),
				'type'        => Controls_Manager::NUMBER,
				'description' => __( 'Slot number (1-8). The matching "Layout Image (Slot N)" inside each Module is used for this layout.', 'neximan-builder' ),
				'default'     => 1,
				'min'         => 1,
				'max'         => self::MAX_SLOTS,
				'step'        => 1,
			)
		);

		$repeater->add_control(
			'layout_price',
			array(
				'label'       => __( 'Price Modifier', 'neximan-builder' ),
				'type'        => Controls_Manager::NUMBER,
				'description' => __( 'Amount added to the module base price when this layout is selected.', 'neximan-builder' ),
				'default'     => 0,
				'min'         => 0,
				'step'        => 1,
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
						'layout_label' => __( '۲ نفره', 'neximan-builder' ),
						'layout_slot'  => 1,
						'layout_price' => 0,
					),
					array(
						'layout_label' => __( '۳ نفره', 'neximan-builder' ),
						'layout_slot'  => 2,
						'layout_price' => 0,
					),
					array(
						'layout_label' => __( '۴ نفره', 'neximan-builder' ),
						'layout_slot'  => 3,
						'layout_price' => 0,
					),
					array(
						'layout_label' => __( '۵ نفره', 'neximan-builder' ),
						'layout_slot'  => 4,
						'layout_price' => 0,
					),
					array(
						'layout_label' => __( '۶ نفره', 'neximan-builder' ),
						'layout_slot'  => 5,
						'layout_price' => 0,
					),
					array(
						'layout_label' => __( '۷ نفره', 'neximan-builder' ),
						'layout_slot'  => 6,
						'layout_price' => 0,
					),
					array(
						'layout_label' => __( 'L شکل', 'neximan-builder' ),
						'layout_slot'  => 7,
						'layout_price' => 0,
					),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Module / product controls (sofa designs, tables, ...).
	 *
	 * @return void
	 */
	private function register_module_controls() {
		$this->start_controls_section(
			'section_modules',
			array(
				'label' => __( 'Modules / Products', 'neximan-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'module_name',
			array(
				'label'       => __( 'Module Name (tab)', 'neximan-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'مدل ۱', 'neximan-builder' ),
				'label_block' => true,
			)
		);

		$repeater->add_control(
			'module_type',
			array(
				'label'   => __( 'Module Type', 'neximan-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'sofa',
				'options' => array(
					'sofa'   => __( 'Sofa', 'neximan-builder' ),
					'table'  => __( 'Table', 'neximan-builder' ),
					'bed'    => __( 'Bed', 'neximan-builder' ),
					'custom' => __( 'Custom', 'neximan-builder' ),
				),
			)
		);

		$repeater->add_control(
			'module_base_price',
			array(
				'label'   => __( 'Base Price', 'neximan-builder' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 0,
				'min'     => 0,
				'step'    => 1,
			)
		);

		$repeater->add_control(
			'module_woo_id',
			array(
				'label'       => __( 'WooCommerce Product ID', 'neximan-builder' ),
				'type'        => Controls_Manager::NUMBER,
				'description' => __( 'Optional. If set, "Add to cart" adds this product with the chosen configuration as line-item data.', 'neximan-builder' ),
				'min'         => 0,
				'step'        => 1,
			)
		);

		// Image controls, one per slot.
		for ( $slot = 1; $slot <= self::MAX_SLOTS; $slot++ ) {
			$repeater->add_control(
				'module_image_' . $slot,
				array(
					/* translators: %d: slot number */
					'label'   => sprintf( __( 'Layout Image (Slot %d)', 'neximan-builder' ), $slot ),
					'type'    => Controls_Manager::MEDIA,
					'default' => array( 'url' => '' ),
				)
			);
		}

		$this->add_control(
			'modules',
			array(
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ module_name }}}',
				'default'     => array(
					array(
						'module_name'       => __( 'مدل ۱', 'neximan-builder' ),
						'module_type'       => 'sofa',
						'module_base_price' => 0,
					),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Fabric color controls.
	 *
	 * @return void
	 */
	private function register_color_controls() {
		$this->start_controls_section(
			'section_colors',
			array(
				'label' => __( 'Fabric Colors', 'neximan-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
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
						'color_name'  => __( 'مشکی', 'neximan-builder' ),
					),
					array(
						'color_value' => '#9bb89b',
						'color_name'  => __( 'سبز', 'neximan-builder' ),
					),
					array(
						'color_value' => '#f0e6d6',
						'color_name'  => __( 'کرم', 'neximan-builder' ),
					),
					array(
						'color_value' => '#c3ccd4',
						'color_name'  => __( 'طوسی', 'neximan-builder' ),
					),
					array(
						'color_value' => '#1f2a63',
						'color_name'  => __( 'سرمه‌ای', 'neximan-builder' ),
					),
					array(
						'color_value' => '#e9b576',
						'color_name'  => __( 'عسلی', 'neximan-builder' ),
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
					'raw'             => __( 'WooCommerce is not active. Install and activate WooCommerce to enable cart and checkout features.', 'neximan-builder' ),
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
				'label'       => __( 'Price Mode', 'neximan-builder' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'dynamic',
				'description' => __( 'Dynamic: use the builder price as the line-item price. Product: use the linked WooCommerce product price.', 'neximan-builder' ),
				'options'     => array(
					'dynamic' => __( 'Dynamic (builder price)', 'neximan-builder' ),
					'product' => __( 'WooCommerce product price', 'neximan-builder' ),
				),
				'condition'   => array( 'woo_action!' => 'none' ),
			)
		);

		$this->add_control(
			'woo_fallback_product',
			array(
				'label'       => __( 'Fallback Product ID', 'neximan-builder' ),
				'type'        => Controls_Manager::NUMBER,
				'description' => __( 'Used when a module has no specific WooCommerce Product ID. Useful for dynamic-priced custom products.', 'neximan-builder' ),
				'min'         => 0,
				'step'        => 1,
				'condition'   => array( 'woo_action!' => 'none' ),
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
	 * Builds the normalized configuration array used by both render and JSON output.
	 *
	 * @param array $settings Widget settings.
	 * @return array
	 */
	public static function build_config( array $settings ) {
		// Layouts.
		$layouts = array();
		if ( ! empty( $settings['layouts'] ) && is_array( $settings['layouts'] ) ) {
			foreach ( $settings['layouts'] as $index => $layout ) {
				$slot = isset( $layout['layout_slot'] ) ? (int) $layout['layout_slot'] : ( $index + 1 );
				$key  = 'l' . $index;

				$layouts[ $key ] = array(
					'label' => isset( $layout['layout_label'] ) ? $layout['layout_label'] : '',
					'slot'  => $slot,
					'price' => isset( $layout['layout_price'] ) ? (float) $layout['layout_price'] : 0,
				);
			}
		}

		// Modules.
		$modules = array();
		if ( ! empty( $settings['modules'] ) && is_array( $settings['modules'] ) ) {
			foreach ( $settings['modules'] as $index => $module ) {
				$key    = 'm' . $index;
				$images = array();

				for ( $slot = 1; $slot <= self::MAX_SLOTS; $slot++ ) {
					$field = 'module_image_' . $slot;
					if ( ! empty( $module[ $field ]['url'] ) ) {
						$images[ $slot ] = esc_url_raw( $module[ $field ]['url'] );
					}
				}

				$modules[ $key ] = array(
					'name'      => isset( $module['module_name'] ) ? $module['module_name'] : '',
					'type'      => isset( $module['module_type'] ) ? $module['module_type'] : 'custom',
					'basePrice' => isset( $module['module_base_price'] ) ? (float) $module['module_base_price'] : 0,
					'wooId'     => isset( $module['module_woo_id'] ) ? (int) $module['module_woo_id'] : 0,
					'images'    => $images,
				);
			}
		}

		// Colors.
		$colors = array();
		if ( ! empty( $settings['colors'] ) && is_array( $settings['colors'] ) ) {
			foreach ( $settings['colors'] as $index => $color ) {
				$key = 'c' . $index;

				$colors[ $key ] = array(
					'value' => isset( $color['color_value'] ) ? $color['color_value'] : '#cccccc',
					'name'  => isset( $color['color_name'] ) ? $color['color_name'] : '',
					'price' => isset( $color['color_price'] ) ? (float) $color['color_price'] : 0,
				);
			}
		}

		return array(
			'currency'      => array(
				'symbol'    => isset( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : '',
				'position'  => isset( $settings['currency_position'] ) ? $settings['currency_position'] : 'after',
				'separator' => isset( $settings['thousand_separator'] ) ? $settings['thousand_separator'] : ',',
			),
			'showPrice'     => ( isset( $settings['show_price'] ) && 'yes' === $settings['show_price'] ),
			'woo'           => array(
				'action'           => isset( $settings['woo_action'] ) ? $settings['woo_action'] : 'none',
				'priceMode'        => isset( $settings['woo_price_mode'] ) ? $settings['woo_price_mode'] : 'dynamic',
				'fallbackProduct'  => isset( $settings['woo_fallback_product'] ) ? (int) $settings['woo_fallback_product'] : 0,
			),
			'layouts'       => $layouts,
			'modules'       => $modules,
			'colors'        => $colors,
		);
	}

	/**
	 * Renders the widget on the front-end.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$config   = self::build_config( $settings );

		$uid = 'neximan-' . $this->get_id();
		$post_id = get_the_ID();

		// Determine the default (first) module and a default layout slot.
		$module_keys = array_keys( $config['modules'] );
		$layout_keys = array_keys( $config['layouts'] );
		$color_keys  = array_keys( $config['colors'] );

		$default_module = ! empty( $module_keys ) ? $module_keys[0] : '';
		$default_color  = ! empty( $color_keys ) ? $color_keys[0] : '';

		// Pick first layout the default module actually has an image for.
		$default_layout = '';
		if ( '' !== $default_module ) {
			foreach ( $layout_keys as $lkey ) {
				$slot = $config['layouts'][ $lkey ]['slot'];
				if ( isset( $config['modules'][ $default_module ]['images'][ $slot ] ) ) {
					$default_layout = $lkey;
					break;
				}
			}
		}
		if ( '' === $default_layout && ! empty( $layout_keys ) ) {
			$default_layout = $layout_keys[0];
		}

		$config['defaults'] = array(
			'module' => $default_module,
			'layout' => $default_layout,
			'color'  => $default_color,
		);
		?>
		<div id="<?php echo esc_attr( $uid ); ?>" class="neximan-builder" data-neximan-builder="<?php echo esc_attr( $uid ); ?>" data-post-id="<?php echo esc_attr( $post_id ); ?>" data-widget-id="<?php echo esc_attr( $this->get_id() ); ?>">
			<div class="neximan-builder-inner">

				<?php if ( count( $config['modules'] ) > 1 ) : ?>
					<div class="neximan-module-tabs">
						<?php foreach ( $config['modules'] as $mkey => $module ) : ?>
							<button type="button"
								class="neximan-module-tab<?php echo ( $mkey === $default_module ) ? ' is-active' : ''; ?>"
								data-module="<?php echo esc_attr( $mkey ); ?>">
								<?php echo esc_html( $module['name'] ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

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
							<div class="neximan-sofa-display">
								<img class="neximan-main-image" src="" alt="<?php esc_attr_e( 'Preview', 'neximan-builder' ); ?>" />
							</div>
						</div>
						<div class="neximan-config-info">
							<strong class="neximan-info-layout"></strong>
							<span class="neximan-info-module"></span>
						</div>
					</div>

					<div class="neximan-control-panel">
						<div class="neximan-control-section">
							<label class="neximan-control-label"><?php echo esc_html( $settings['layout_section_label'] ); ?></label>
							<div class="neximan-layout-options">
								<?php foreach ( $config['layouts'] as $lkey => $layout ) : ?>
									<button type="button"
										class="neximan-layout-btn"
										data-layout="<?php echo esc_attr( $lkey ); ?>"
										data-slot="<?php echo esc_attr( $layout['slot'] ); ?>">
										<?php echo esc_html( $layout['label'] ); ?>
									</button>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="neximan-control-section">
							<label class="neximan-control-label"><?php echo esc_html( $settings['color_section_label'] ); ?></label>
							<div class="neximan-color-options">
								<?php foreach ( $config['colors'] as $ckey => $color ) : ?>
									<button type="button"
										class="neximan-color-swatch"
										data-color="<?php echo esc_attr( $ckey ); ?>"
										style="background-color: <?php echo esc_attr( $color['value'] ); ?>;"
										title="<?php echo esc_attr( $color['name'] ); ?>">
									</button>
								<?php endforeach; ?>
							</div>
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

			<script type="application/json" class="neximan-config">
				<?php echo wp_json_encode( $config ); ?>
			</script>
		</div>
		<?php
	}
}
