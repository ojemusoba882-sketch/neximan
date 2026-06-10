<?php
/**
 * Custom post type "Neximan Builder" (a series / parent product) and its admin UI.
 *
 * @package Neximan_Furniture_Builder
 */

namespace Neximan\Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CPT
 */
class CPT {

	/**
	 * Nonce action/name used by the meta box.
	 *
	 * @var string
	 */
	const NONCE = 'neximan_save_config';

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . Config::POST_TYPE, array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
	}

	/**
	 * Registers the series post type.
	 *
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Furniture Builders', 'neximan-builder' ),
			'singular_name'      => __( 'Furniture Builder', 'neximan-builder' ),
			'add_new'            => __( 'Add New', 'neximan-builder' ),
			'add_new_item'       => __( 'Add New Builder', 'neximan-builder' ),
			'edit_item'          => __( 'Edit Builder', 'neximan-builder' ),
			'new_item'           => __( 'New Builder', 'neximan-builder' ),
			'view_item'          => __( 'View Builder', 'neximan-builder' ),
			'search_items'       => __( 'Search Builders', 'neximan-builder' ),
			'not_found'          => __( 'No builders found', 'neximan-builder' ),
			'menu_name'          => __( 'Neximan Builders', 'neximan-builder' ),
		);

		register_post_type(
			Config::POST_TYPE,
			array(
				'labels'       => $labels,
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => true,
				'menu_icon'    => 'dashicons-layout',
				'supports'     => array( 'title' ),
				'capability_type' => 'post',
			)
		);
	}

	/**
	 * Registers the configuration meta box.
	 *
	 * @return void
	 */
	public function add_meta_box() {
		add_meta_box(
			'neximan_config',
			__( 'Builder Configuration', 'neximan-builder' ),
			array( $this, 'render_meta_box' ),
			Config::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Enqueues admin assets on the CPT edit screen.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function admin_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || Config::POST_TYPE !== $screen->post_type ) {
			return;
		}
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'neximan-admin',
			NEXIMAN_BUILDER_URL . 'assets/css/admin.css',
			array(),
			NEXIMAN_BUILDER_VERSION
		);

		wp_enqueue_script(
			'neximan-admin',
			NEXIMAN_BUILDER_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			NEXIMAN_BUILDER_VERSION,
			true
		);

		wp_enqueue_style( 'wp-color-picker' );

		wp_localize_script(
			'neximan-admin',
			'NeximanAdmin',
			array(
				'i18n' => array(
					'confirmDelete'   => __( 'Remove this item?', 'neximan-builder' ),
					'selectImage'     => __( 'Select Image', 'neximan-builder' ),
					'useImage'        => __( 'Use this image', 'neximan-builder' ),
					'model'           => __( 'Model', 'neximan-builder' ),
					'layout'          => __( 'Layout', 'neximan-builder' ),
					'color'           => __( 'Color', 'neximan-builder' ),
				),
				'standardLayouts' => array(
					__( '2-seater', 'neximan-builder' ),
					__( '3-seater', 'neximan-builder' ),
					__( '4-seater', 'neximan-builder' ),
					__( '5-seater', 'neximan-builder' ),
					__( '6-seater', 'neximan-builder' ),
					__( '7-seater', 'neximan-builder' ),
					__( 'L-shape', 'neximan-builder' ),
				),
			)
		);
	}

	/**
	 * Renders the meta box markup. The actual UI is built by admin.js from the
	 * JSON config; this provides the mount point and serialized data.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( self::NONCE, self::NONCE );

		$config = Config::get_raw( $post->ID );
		$json   = Config::json( $config );
		?>
		<div class="neximan-admin" id="neximan-admin">
			<p class="description">
				<?php esc_html_e( 'A "Builder" represents one furniture series / parent product (e.g. Noah, Melorin). Define its models, each model\'s layouts (unlimited, with their own image and price) and fabric colors. Create another Builder for another series.', 'neximan-builder' ); ?>
			</p>

			<div class="neximan-admin-row">
				<label>
					<strong><?php esc_html_e( 'Pricing Mode', 'neximan-builder' ); ?></strong>
					<select id="neximan-pricing-mode">
						<option value="modular" <?php selected( isset( $config['pricingMode'] ) ? $config['pricingMode'] : 'modular', 'modular' ); ?>><?php esc_html_e( 'Modular (sum of modules)', 'neximan-builder' ); ?></option>
						<option value="simple" <?php selected( isset( $config['pricingMode'] ) ? $config['pricingMode'] : 'modular', 'simple' ); ?>><?php esc_html_e( 'Simple (flat price per layout)', 'neximan-builder' ); ?></option>
					</select>
				</label>
				<label>
					<strong><?php esc_html_e( 'Parent WooCommerce Product ID', 'neximan-builder' ); ?></strong>
					<input type="number" min="0" step="1" id="neximan-woo-id" value="<?php echo esc_attr( isset( $config['wooProductId'] ) ? $config['wooProductId'] : 0 ); ?>" />
				</label>
				<label>
					<strong><?php esc_html_e( 'Checkout Price Mode', 'neximan-builder' ); ?></strong>
					<select id="neximan-price-mode">
						<option value="dynamic" <?php selected( isset( $config['priceMode'] ) ? $config['priceMode'] : 'dynamic', 'dynamic' ); ?>><?php esc_html_e( 'Dynamic (builder price)', 'neximan-builder' ); ?></option>
						<option value="product" <?php selected( isset( $config['priceMode'] ) ? $config['priceMode'] : 'dynamic', 'product' ); ?>><?php esc_html_e( 'WooCommerce product price', 'neximan-builder' ); ?></option>
						<option value="variation" <?php selected( isset( $config['priceMode'] ) ? $config['priceMode'] : 'dynamic', 'variation' ); ?>><?php esc_html_e( 'WooCommerce variation price', 'neximan-builder' ); ?></option>
					</select>
				</label>
			</div>

			<div class="neximan-admin-row">
				<label>
					<strong><?php esc_html_e( 'Variation Attribute: Layout', 'neximan-builder' ); ?></strong>
					<input type="text" id="neximan-var-layout" placeholder="pa_chideman" value="<?php echo esc_attr( isset( $config['varAttrs']['layout'] ) ? $config['varAttrs']['layout'] : '' ); ?>" />
				</label>
				<label>
					<strong><?php esc_html_e( 'Variation Attribute: Color', 'neximan-builder' ); ?></strong>
					<input type="text" id="neximan-var-color" placeholder="pa_color" value="<?php echo esc_attr( isset( $config['varAttrs']['color'] ) ? $config['varAttrs']['color'] : '' ); ?>" />
				</label>
			</div>

			<div class="neximan-modules-section">
				<h3><?php esc_html_e( 'Modules (building blocks)', 'neximan-builder' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Define the reusable parts with their price (e.g. Seat 60, Seat 85, Corner, Pouf, Table). Layout prices are calculated by summing the modules used in each layout.', 'neximan-builder' ); ?></p>
				<div id="neximan-modules" class="neximan-list"></div>
				<button type="button" class="button" id="neximan-add-module"><?php esc_html_e( '+ Add Module', 'neximan-builder' ); ?></button>
			</div>

			<h3><?php esc_html_e( 'Fabric Colors', 'neximan-builder' ); ?></h3>
			<div id="neximan-colors" class="neximan-list"></div>
			<button type="button" class="button" id="neximan-add-color"><?php esc_html_e( '+ Add Color', 'neximan-builder' ); ?></button>

			<h3><?php esc_html_e( 'Option Groups (Size, etc.)', 'neximan-builder' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Add as many option groups as you need (e.g. Size 60/85 cm, leg type, ...). Each choice can change the price and map to a WooCommerce variation value.', 'neximan-builder' ); ?></p>
			<div id="neximan-options" class="neximan-list"></div>
			<button type="button" class="button" id="neximan-add-option"><?php esc_html_e( '+ Add Option Group', 'neximan-builder' ); ?></button>

			<h3><?php esc_html_e( 'Models', 'neximan-builder' ); ?></h3>
			<div id="neximan-models" class="neximan-list"></div>
			<button type="button" class="button button-primary" id="neximan-add-model"><?php esc_html_e( '+ Add Model', 'neximan-builder' ); ?></button>

			<input type="hidden" name="neximan_config" id="neximan-config-json" value="<?php echo esc_attr( $json ); ?>" />
		</div>
		<?php
	}

	/**
	 * Saves the configuration meta from the posted JSON.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function save( $post_id, $post ) {
		unset( $post );

		if ( ! isset( $_POST[ self::NONCE ] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$json = isset( $_POST['neximan_config'] ) ? wp_unslash( $_POST['neximan_config'] ) : '';
		$raw  = json_decode( $json, true );

		$clean = Config::sanitize( is_array( $raw ) ? $raw : array() );

		update_post_meta( $post_id, Config::META_KEY, Config::json( $clean ) );
	}
}
