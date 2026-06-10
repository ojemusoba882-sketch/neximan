<?php
/**
 * Admin settings page for the custom invoice / checkout summary.
 *
 * Adds an "Invoice Settings" submenu under the Neximan Builders menu where the
 * whole invoice can be customized globally: all labels (translatable to
 * Persian), colors, and behaviour (e.g. replace the WooCommerce cart page).
 *
 * @package Neximan_Furniture_Builder
 */

namespace Neximan\Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 */
class Settings {

	/**
	 * Option key holding all invoice settings.
	 *
	 * @var string
	 */
	const OPTION = 'neximan_invoice_settings';

	/**
	 * Settings group / page slug.
	 *
	 * @var string
	 */
	const SLUG = 'neximan-invoice-settings';

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Returns the default settings (text labels + colors + behaviour).
	 *
	 * @return array
	 */
	public static function defaults() {
		$labels = Invoice::default_labels();

		return array_merge(
			$labels,
			array(
				'accent'        => '#10b981',
				'accent2'       => '#0e9f74',
				'dark'          => '#1f2a37',
				'text'          => '#4a5568',
				'card'          => '#ffffff',
				'bg'            => '#f3f6f5',
				'border'        => '#e6efea',
				'radius'        => 18,
				'fa_digits'     => 'yes',
				'show_images'   => 'yes',
				'show_checkout' => 'yes',
				'replace_cart'  => 'no',
			)
		);
	}

	/**
	 * Returns the saved settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get() {
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return array_merge( self::defaults(), $saved );
	}

	/**
	 * Adds the settings submenu under the Neximan Builders CPT menu.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_submenu_page(
			'edit.php?post_type=' . Config::POST_TYPE,
			__( 'Invoice Settings', 'neximan-builder' ),
			__( 'Invoice Settings', 'neximan-builder' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueues the invoice stylesheet on the settings page (for the preview).
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public function admin_assets( $hook ) {
		if ( false === strpos( (string) $hook, self::SLUG ) ) {
			return;
		}
		wp_enqueue_style(
			'neximan-invoice',
			NEXIMAN_BUILDER_URL . 'assets/css/invoice.css',
			array(),
			NEXIMAN_BUILDER_VERSION
		);
	}

	/**
	 * Registers the settings + sanitizer.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::SLUG,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitizes the submitted settings.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize( $input ) {
		$defaults = self::defaults();
		$clean    = array();
		$switches = array( 'fa_digits', 'show_images', 'show_checkout', 'replace_cart' );
		$colors   = array( 'accent', 'accent2', 'dark', 'text', 'card', 'bg', 'border' );

		foreach ( $defaults as $key => $default ) {
			if ( ! isset( $input[ $key ] ) ) {
				// Unchecked switches submit nothing.
				$clean[ $key ] = in_array( $key, $switches, true ) ? 'no' : $default;
				continue;
			}

			$value = $input[ $key ];

			if ( in_array( $key, $colors, true ) ) {
				$clean[ $key ] = sanitize_hex_color( $value ) ? $value : $default;
			} elseif ( 'radius' === $key ) {
				$clean[ $key ] = max( 0, min( 60, (int) $value ) );
			} elseif ( in_array( $key, $switches, true ) ) {
				$clean[ $key ] = 'yes';
			} else {
				$clean[ $key ] = sanitize_text_field( $value );
			}
		}

		return $clean;
	}

	/**
	 * Renders the settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		$s = self::get();

		$text_fields = array(
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

		$color_fields = array(
			'accent'  => __( 'Accent Color', 'neximan-builder' ),
			'accent2' => __( 'Accent Color 2', 'neximan-builder' ),
			'dark'    => __( 'Heading Color', 'neximan-builder' ),
			'text'    => __( 'Text Color', 'neximan-builder' ),
			'card'    => __( 'Card Background', 'neximan-builder' ),
			'bg'      => __( 'Panel Background', 'neximan-builder' ),
			'border'  => __( 'Border Color', 'neximan-builder' ),
		);

		$switches = array(
			'show_images'   => __( 'Show product images', 'neximan-builder' ),
			'show_checkout' => __( 'Show checkout button', 'neximan-builder' ),
			'fa_digits'     => __( 'Persian digits for quantities', 'neximan-builder' ),
			'replace_cart'  => __( 'Replace the WooCommerce Cart page with this invoice', 'neximan-builder' ),
		);
		?>
		<div class="wrap neximan-settings">
			<h1><?php esc_html_e( 'Neximan Invoice Settings', 'neximan-builder' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Customize the invoice / checkout summary globally. Place it with the [neximan_invoice] shortcode or the "Neximan Invoice" Elementor widget, or enable "Replace the WooCommerce Cart page" below.', 'neximan-builder' ); ?>
			</p>

			<form method="post" action="options.php">
				<?php settings_fields( self::SLUG ); ?>

				<h2 class="title"><?php esc_html_e( 'Texts', 'neximan-builder' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php foreach ( $text_fields as $key => $label ) : ?>
						<tr>
							<th scope="row"><label for="nx-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
							<td>
								<input name="<?php echo esc_attr( self::OPTION ); ?>[<?php echo esc_attr( $key ); ?>]"
									id="nx-<?php echo esc_attr( $key ); ?>"
									type="text"
									class="regular-text"
									value="<?php echo esc_attr( $s[ $key ] ); ?>" />
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<h2 class="title"><?php esc_html_e( 'Colors & Style', 'neximan-builder' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php foreach ( $color_fields as $key => $label ) : ?>
						<tr>
							<th scope="row"><label for="nx-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
							<td>
								<input name="<?php echo esc_attr( self::OPTION ); ?>[<?php echo esc_attr( $key ); ?>]"
									id="nx-<?php echo esc_attr( $key ); ?>"
									type="color"
									value="<?php echo esc_attr( $s[ $key ] ); ?>" />
							</td>
						</tr>
					<?php endforeach; ?>
					<tr>
						<th scope="row"><label for="nx-radius"><?php esc_html_e( 'Corner Radius (px)', 'neximan-builder' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( self::OPTION ); ?>[radius]" id="nx-radius" type="number" min="0" max="60" value="<?php echo esc_attr( $s['radius'] ); ?>" />
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Behaviour', 'neximan-builder' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php foreach ( $switches as $key => $label ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $label ); ?></th>
							<td>
								<label>
									<input name="<?php echo esc_attr( self::OPTION ); ?>[<?php echo esc_attr( $key ); ?>]" type="checkbox" value="yes" <?php checked( 'yes', $s[ $key ] ); ?> />
									<?php esc_html_e( 'Enable', 'neximan-builder' ); ?>
								</label>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<?php submit_button(); ?>
			</form>

			<div class="neximan-settings-preview">
				<h2><?php esc_html_e( 'Live preview', 'neximan-builder' ); ?></h2>
				<p class="description"><?php esc_html_e( 'A sample of how the invoice will look (uses your colors and texts).', 'neximan-builder' ); ?></p>
				<?php echo self::preview_html( $s ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- internal markup, values escaped. ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Builds a small static preview using the chosen colors/labels.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function preview_html( $s ) {
		$style = sprintf(
			'--nx-inv-accent:%s;--nx-inv-accent-2:%s;--nx-inv-dark:%s;--nx-inv-text:%s;--nx-inv-card:%s;--nx-inv-bg:%s;--nx-inv-border:%s;--nx-inv-radius:%dpx;',
			esc_attr( $s['accent'] ),
			esc_attr( $s['accent2'] ),
			esc_attr( $s['dark'] ),
			esc_attr( $s['text'] ),
			esc_attr( $s['card'] ),
			esc_attr( $s['bg'] ),
			esc_attr( $s['border'] ),
			(int) $s['radius']
		);

		ob_start();
		?>
		<div class="neximan-invoice" dir="rtl" style="<?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- composed of escaped values. ?>">
			<div class="neximan-invoice-head">
				<div>
					<h2 class="neximan-invoice-title"><?php echo esc_html( $s['title'] ); ?></h2>
					<p class="neximan-invoice-subtitle"><?php echo esc_html( $s['subtitle'] ); ?></p>
				</div>
				<div class="neximan-invoice-logo"><?php bloginfo( 'name' ); ?></div>
			</div>
			<div class="neximan-invoice-list">
				<div class="neximan-invoice-item">
					<div class="neximan-invoice-body">
						<div class="neximan-invoice-name">Noah <span class="neximan-invoice-badge"><?php echo esc_html( $s['badge'] ); ?></span></div>
						<div class="neximan-invoice-specs">
							<div class="neximan-invoice-spec"><span class="neximan-invoice-spec-k"><?php echo esc_html( $s['layout'] ); ?></span><span class="neximan-invoice-spec-v">3-seater</span></div>
							<div class="neximan-invoice-spec"><span class="neximan-invoice-spec-k"><?php echo esc_html( $s['color'] ); ?></span><span class="neximan-invoice-spec-v">Honey</span></div>
						</div>
					</div>
					<div class="neximan-invoice-meta">
						<div class="neximan-invoice-line">۹٬۶۰۰٬۰۰۰</div>
					</div>
				</div>
			</div>
			<div class="neximan-invoice-totals">
				<div class="neximan-invoice-row neximan-invoice-row--grand">
					<span><?php echo esc_html( $s['grand_total'] ); ?></span>
					<span class="neximan-invoice-amount">۹٬۶۰۰٬۰۰۰</span>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
