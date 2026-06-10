<?php
/**
 * Config model: stores, sanitizes and normalizes builder series data, builds
 * the signed pricing manifest and computes prices server-side.
 *
 * A "series" (e.g. Noah / Melorin) is stored as a CPT post with its full
 * configuration (models -> layouts, colors, option groups) kept as JSON post
 * meta. This gives unlimited nesting/flexibility.
 *
 * @package Neximan_Furniture_Builder
 */

namespace Neximan\Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Config
 */
class Config {

	/**
	 * Post meta key holding the JSON configuration.
	 *
	 * @var string
	 */
	const META_KEY = '_neximan_config';

	/**
	 * Custom post type slug.
	 *
	 * @var string
	 */
	const POST_TYPE = 'neximan_product';

	/**
	 * Returns the raw (decoded) config array for a series post.
	 *
	 * @param int $post_id Series post ID.
	 * @return array
	 */
	public static function get_raw( $post_id ) {
		$json = get_post_meta( $post_id, self::META_KEY, true );
		if ( empty( $json ) ) {
			return self::defaults();
		}

		$data = json_decode( $json, true );
		if ( ! is_array( $data ) ) {
			return self::defaults();
		}

		return wp_parse_args( $data, self::defaults() );
	}

	/**
	 * Default config skeleton.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'wooProductId' => 0,
			'priceMode'    => 'dynamic',
			'varAttrs'     => array(
				'layout' => '',
				'color'  => '',
			),
			'models'       => array(),
			'colors'       => array(),
			'options'      => array(),
		);
	}

	/**
	 * Sanitizes a raw config array coming from the admin form.
	 *
	 * @param array $raw Raw config.
	 * @return array Clean config.
	 */
	public static function sanitize( $raw ) {
		$clean = self::defaults();

		if ( ! is_array( $raw ) ) {
			return $clean;
		}

		$clean['wooProductId'] = isset( $raw['wooProductId'] ) ? absint( $raw['wooProductId'] ) : 0;
		$clean['priceMode']    = self::clean_price_mode( isset( $raw['priceMode'] ) ? $raw['priceMode'] : 'dynamic' );

		$clean['varAttrs'] = array(
			'layout' => isset( $raw['varAttrs']['layout'] ) ? self::clean_attr_key( $raw['varAttrs']['layout'] ) : '',
			'color'  => isset( $raw['varAttrs']['color'] ) ? self::clean_attr_key( $raw['varAttrs']['color'] ) : '',
		);

		// Colors.
		if ( ! empty( $raw['colors'] ) && is_array( $raw['colors'] ) ) {
			foreach ( $raw['colors'] as $i => $color ) {
				$clean['colors'][] = array(
					'id'       => self::clean_id( isset( $color['id'] ) ? $color['id'] : 'c' . $i ),
					'name'     => sanitize_text_field( isset( $color['name'] ) ? $color['name'] : '' ),
					'value'    => self::clean_color( isset( $color['value'] ) ? $color['value'] : '#cccccc' ),
					'price'    => isset( $color['price'] ) ? (float) $color['price'] : 0,
					'varValue' => isset( $color['varValue'] ) ? sanitize_text_field( $color['varValue'] ) : '',
				);
			}
		}

		// Option groups (e.g. Size).
		if ( ! empty( $raw['options'] ) && is_array( $raw['options'] ) ) {
			foreach ( $raw['options'] as $gi => $group ) {
				$clean_group = array(
					'id'      => self::clean_id( isset( $group['id'] ) ? $group['id'] : 'g' . $gi ),
					'label'   => sanitize_text_field( isset( $group['label'] ) ? $group['label'] : '' ),
					'varAttr' => isset( $group['varAttr'] ) ? self::clean_attr_key( $group['varAttr'] ) : '',
					'choices' => array(),
				);

				if ( ! empty( $group['choices'] ) && is_array( $group['choices'] ) ) {
					foreach ( $group['choices'] as $ci => $choice ) {
						$clean_group['choices'][] = array(
							'id'       => self::clean_id( isset( $choice['id'] ) ? $choice['id'] : 'o' . $ci ),
							'name'     => sanitize_text_field( isset( $choice['name'] ) ? $choice['name'] : '' ),
							'price'    => isset( $choice['price'] ) ? (float) $choice['price'] : 0,
							'varValue' => isset( $choice['varValue'] ) ? sanitize_text_field( $choice['varValue'] ) : '',
						);
					}
				}

				$clean['options'][] = $clean_group;
			}
		}

		// Models -> layouts.
		if ( ! empty( $raw['models'] ) && is_array( $raw['models'] ) ) {
			foreach ( $raw['models'] as $mi => $model ) {
				$clean_model = array(
					'id'        => self::clean_id( isset( $model['id'] ) ? $model['id'] : 'm' . $mi ),
					'name'      => sanitize_text_field( isset( $model['name'] ) ? $model['name'] : '' ),
					'type'      => self::clean_type( isset( $model['type'] ) ? $model['type'] : 'sofa' ),
					'basePrice' => isset( $model['basePrice'] ) ? (float) $model['basePrice'] : 0,
					'wooId'     => isset( $model['wooId'] ) ? absint( $model['wooId'] ) : 0,
					'layouts'   => array(),
				);

				if ( ! empty( $model['layouts'] ) && is_array( $model['layouts'] ) ) {
					foreach ( $model['layouts'] as $li => $layout ) {
						$clean_model['layouts'][] = array(
							'id'       => self::clean_id( isset( $layout['id'] ) ? $layout['id'] : 'l' . $li ),
							'label'    => sanitize_text_field( isset( $layout['label'] ) ? $layout['label'] : '' ),
							'image'    => isset( $layout['image'] ) ? esc_url_raw( $layout['image'] ) : '',
							'imageId'  => isset( $layout['imageId'] ) ? absint( $layout['imageId'] ) : 0,
							'price'    => isset( $layout['price'] ) ? (float) $layout['price'] : 0,
							'varValue' => isset( $layout['varValue'] ) ? sanitize_text_field( $layout['varValue'] ) : '',
						);
					}
				}

				$clean['models'][] = $clean_model;
			}
		}

		return $clean;
	}

	/**
	 * Returns a normalized "series" array (for front-end) from a post.
	 *
	 * @param int $post_id Series post ID.
	 * @return array|null
	 */
	public static function get_series( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return null;
		}

		$raw = self::get_raw( $post_id );

		return array(
			'id'        => 'post-' . $post_id,
			'postId'    => (int) $post_id,
			'name'      => get_the_title( $post_id ),
			'wooId'     => (int) $raw['wooProductId'],
			'priceMode' => $raw['priceMode'],
			'varAttrs'  => $raw['varAttrs'],
			'models'    => $raw['models'],
			'colors'    => $raw['colors'],
			'options'   => $raw['options'],
		);
	}

	/* --------------------------------------------------------------------- *
	 * Pricing manifest (signed) — the single source of truth for the cart.
	 * --------------------------------------------------------------------- */

	/**
	 * Builds a compact pricing manifest from a list of normalized series.
	 *
	 * @param array $series_list List of series arrays.
	 * @param array $woo         Widget WooCommerce meta (action, fallbackProduct).
	 * @return array
	 */
	public static function build_manifest( $series_list, $woo ) {
		$manifest = array(
			'woo'    => array(
				'fallbackProduct' => isset( $woo['fallbackProduct'] ) ? (int) $woo['fallbackProduct'] : 0,
				'forceProduct'    => isset( $woo['forceProduct'] ) ? (int) $woo['forceProduct'] : 0,
			),
			'series' => array(),
		);

		foreach ( $series_list as $series ) {
			$sid    = $series['id'];
			$models = array();

			foreach ( $series['models'] as $model ) {
				$layouts = array();
				foreach ( $model['layouts'] as $layout ) {
					$layouts[ $layout['id'] ] = array(
						'label'    => $layout['label'],
						'price'    => (float) $layout['price'],
						'varValue' => isset( $layout['varValue'] ) ? $layout['varValue'] : '',
					);
				}

				$models[ $model['id'] ] = array(
					'name'      => $model['name'],
					'type'      => isset( $model['type'] ) ? $model['type'] : 'custom',
					'basePrice' => (float) $model['basePrice'],
					'wooId'     => isset( $model['wooId'] ) ? (int) $model['wooId'] : 0,
					'layouts'   => $layouts,
				);
			}

			$colors = array();
			foreach ( $series['colors'] as $color ) {
				$colors[ $color['id'] ] = array(
					'name'     => $color['name'],
					'price'    => (float) $color['price'],
					'varValue' => isset( $color['varValue'] ) ? $color['varValue'] : '',
				);
			}

			$options = array();
			foreach ( ( isset( $series['options'] ) ? $series['options'] : array() ) as $group ) {
				$choices = array();
				foreach ( $group['choices'] as $choice ) {
					$choices[ $choice['id'] ] = array(
						'name'     => $choice['name'],
						'price'    => (float) $choice['price'],
						'varValue' => isset( $choice['varValue'] ) ? $choice['varValue'] : '',
					);
				}
				$options[ $group['id'] ] = array(
					'label'   => $group['label'],
					'varAttr' => isset( $group['varAttr'] ) ? $group['varAttr'] : '',
					'choices' => $choices,
				);
			}

			$manifest['series'][ $sid ] = array(
				'name'      => $series['name'],
				'wooId'     => (int) $series['wooId'],
				'priceMode' => isset( $series['priceMode'] ) ? $series['priceMode'] : 'dynamic',
				'varAttrs'  => isset( $series['varAttrs'] ) ? $series['varAttrs'] : array(),
				'models'    => $models,
				'colors'    => $colors,
				'options'   => $options,
			);
		}

		return $manifest;
	}

	/**
	 * Signs the exact manifest JSON string with a site-secret-derived hash.
	 *
	 * Signing the raw string (rather than a re-encoded array) guarantees the
	 * client can echo the manifest back byte-for-byte without serialization
	 * differences breaking the signature.
	 *
	 * @param string $manifest_json Manifest JSON string.
	 * @return string Signature.
	 */
	public static function sign_json( $manifest_json ) {
		return wp_hash( (string) $manifest_json );
	}

	/**
	 * Verifies a manifest JSON string against a signature.
	 *
	 * @param string $manifest_json Raw manifest JSON (as received).
	 * @param string $signature     Provided signature.
	 * @return array|null Decoded manifest on success, null on failure.
	 */
	public static function verify( $manifest_json, $signature ) {
		if ( ! is_string( $manifest_json ) || '' === $manifest_json ) {
			return null;
		}

		if ( ! hash_equals( self::sign_json( $manifest_json ), (string) $signature ) ) {
			return null;
		}

		$manifest = json_decode( $manifest_json, true );

		return is_array( $manifest ) ? $manifest : null;
	}

	/**
	 * Computes price, labels and variation attributes from a verified manifest.
	 *
	 * @param array  $manifest   Verified manifest.
	 * @param string $series_id  Selected series id.
	 * @param string $model_id   Selected model id.
	 * @param string $layout_id  Selected layout id.
	 * @param string $color_id   Selected color id.
	 * @param array  $option_sel Map of groupId => choiceId.
	 * @return array|null
	 */
	public static function compute_from_manifest( $manifest, $series_id, $model_id, $layout_id, $color_id, $option_sel ) {
		if ( empty( $manifest['series'][ $series_id ] ) ) {
			return null;
		}

		$series = $manifest['series'][ $series_id ];

		if ( empty( $series['models'][ $model_id ] ) ) {
			return null;
		}

		$model    = $series['models'][ $model_id ];
		$price    = (float) $model['basePrice'];
		$var_attr = array();

		// Layout.
		$layout_label = '';
		if ( ! empty( $series['models'][ $model_id ]['layouts'][ $layout_id ] ) ) {
			$layout        = $series['models'][ $model_id ]['layouts'][ $layout_id ];
			$price        += (float) $layout['price'];
			$layout_label  = $layout['label'];

			if ( ! empty( $series['varAttrs']['layout'] ) && '' !== $layout['varValue'] ) {
				$var_attr[ 'attribute_' . $series['varAttrs']['layout'] ] = $layout['varValue'];
			}
		}

		// Color.
		$color_name = '';
		if ( ! empty( $series['colors'][ $color_id ] ) ) {
			$color       = $series['colors'][ $color_id ];
			$price      += (float) $color['price'];
			$color_name  = $color['name'];

			if ( ! empty( $series['varAttrs']['color'] ) && '' !== $color['varValue'] ) {
				$var_attr[ 'attribute_' . $series['varAttrs']['color'] ] = $color['varValue'];
			}
		}

		// Option groups (e.g. size).
		$option_labels = array();
		if ( is_array( $option_sel ) ) {
			foreach ( $option_sel as $group_id => $choice_id ) {
				if ( empty( $series['options'][ $group_id ]['choices'][ $choice_id ] ) ) {
					continue;
				}
				$group  = $series['options'][ $group_id ];
				$choice = $group['choices'][ $choice_id ];
				$price += (float) $choice['price'];

				$option_labels[] = array(
					'label' => $group['label'],
					'value' => $choice['name'],
				);

				if ( ! empty( $group['varAttr'] ) && '' !== $choice['varValue'] ) {
					$var_attr[ 'attribute_' . $group['varAttr'] ] = $choice['varValue'];
				}
			}
		}

		$product_id = ! empty( $model['wooId'] ) ? (int) $model['wooId'] : (int) $series['wooId'];
		if ( ! $product_id ) {
			$product_id = (int) $manifest['woo']['fallbackProduct'];
		}

		// A forced product (single product page binding) always wins.
		if ( ! empty( $manifest['woo']['forceProduct'] ) ) {
			$product_id = (int) $manifest['woo']['forceProduct'];
		}

		return array(
			'price'          => $price,
			'priceMode'      => isset( $series['priceMode'] ) ? $series['priceMode'] : 'dynamic',
			'productId'      => $product_id,
			'series_name'    => $series['name'],
			'model_name'     => $model['name'],
			'model_type'     => $model['type'],
			'layout_label'   => $layout_label,
			'color_name'     => $color_name,
			'option_labels'  => $option_labels,
			'variation_attr' => $var_attr,
		);
	}

	/* --------------------------------------------------------------------- *
	 * Helpers.
	 * --------------------------------------------------------------------- */

	/**
	 * Finds an item by its 'id' key inside a list.
	 *
	 * @param array  $list List.
	 * @param string $id   ID.
	 * @return array|null
	 */
	public static function find_by_id( $list, $id ) {
		if ( ! is_array( $list ) ) {
			return null;
		}
		foreach ( $list as $item ) {
			if ( isset( $item['id'] ) && (string) $item['id'] === (string) $id ) {
				return $item;
			}
		}
		return null;
	}

	/**
	 * Sanitizes an identifier.
	 *
	 * @param string $id Raw id.
	 * @return string
	 */
	private static function clean_id( $id ) {
		$id = preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $id );
		return '' !== $id ? $id : uniqid( 'x' );
	}

	/**
	 * Validates a color string.
	 *
	 * @param string $color Raw color.
	 * @return string
	 */
	private static function clean_color( $color ) {
		$color = trim( (string) $color );
		if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color ) ) {
			return $color;
		}
		if ( preg_match( '/^rgba?\([0-9.,\s]+\)$/', $color ) ) {
			return $color;
		}
		return '#cccccc';
	}

	/**
	 * Validates a module type.
	 *
	 * @param string $type Raw type.
	 * @return string
	 */
	private static function clean_type( $type ) {
		$allowed = array( 'sofa', 'table', 'bed', 'chair', 'custom' );
		return in_array( $type, $allowed, true ) ? $type : 'custom';
	}

	/**
	 * Validates the price mode.
	 *
	 * @param string $mode Raw mode.
	 * @return string
	 */
	private static function clean_price_mode( $mode ) {
		$allowed = array( 'dynamic', 'product', 'variation' );
		return in_array( $mode, $allowed, true ) ? $mode : 'dynamic';
	}

	/**
	 * Cleans a WooCommerce attribute key (e.g. pa_size or size).
	 *
	 * @param string $key Raw key.
	 * @return string
	 */
	private static function clean_attr_key( $key ) {
		return sanitize_title( (string) $key );
	}
}
