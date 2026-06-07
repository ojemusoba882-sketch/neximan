<?php
/**
 * Config model: stores, sanitizes and normalizes builder series data.
 *
 * A "series" (e.g. Noah / Melorin) is stored as a CPT post with its full
 * configuration (models -> layouts -> colors) kept as JSON post meta. This
 * gives unlimited nesting/flexibility that Elementor repeaters cannot provide.
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

		return $data;
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
			'models'       => array(),
			'colors'       => array(),
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
		$clean['priceMode']    = ( isset( $raw['priceMode'] ) && 'product' === $raw['priceMode'] ) ? 'product' : 'dynamic';

		// Colors.
		if ( ! empty( $raw['colors'] ) && is_array( $raw['colors'] ) ) {
			foreach ( $raw['colors'] as $i => $color ) {
				$clean['colors'][] = array(
					'id'    => self::clean_id( isset( $color['id'] ) ? $color['id'] : 'c' . $i ),
					'name'  => sanitize_text_field( isset( $color['name'] ) ? $color['name'] : '' ),
					'value' => self::clean_color( isset( $color['value'] ) ? $color['value'] : '#cccccc' ),
					'price' => isset( $color['price'] ) ? (float) $color['price'] : 0,
				);
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
							'id'      => self::clean_id( isset( $layout['id'] ) ? $layout['id'] : 'l' . $li ),
							'label'   => sanitize_text_field( isset( $layout['label'] ) ? $layout['label'] : '' ),
							'image'   => isset( $layout['image'] ) ? esc_url_raw( $layout['image'] ) : '',
							'imageId' => isset( $layout['imageId'] ) ? absint( $layout['imageId'] ) : 0,
							'price'   => isset( $layout['price'] ) ? (float) $layout['price'] : 0,
						);
					}
				}

				$clean['models'][] = $clean_model;
			}
		}

		return $clean;
	}

	/**
	 * Returns a normalized "series" array (for front-end + price calc) from a post.
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
			'id'     => 'post-' . $post_id,
			'postId' => (int) $post_id,
			'name'   => get_the_title( $post_id ),
			'wooId'  => isset( $raw['wooProductId'] ) ? (int) $raw['wooProductId'] : 0,
			'models' => isset( $raw['models'] ) ? $raw['models'] : array(),
			'colors' => isset( $raw['colors'] ) ? $raw['colors'] : array(),
		);
	}

	/**
	 * Computes the price and resolves selection labels for a post-based series.
	 *
	 * @param int    $post_id   Series post ID.
	 * @param string $model_id  Selected model id.
	 * @param string $layout_id Selected layout id.
	 * @param string $color_id  Selected color id.
	 * @return array|null Resolved selection details, or null when invalid.
	 */
	public static function resolve_selection( $post_id, $model_id, $layout_id, $color_id ) {
		$series = self::get_series( $post_id );
		if ( null === $series ) {
			return null;
		}

		$raw        = self::get_raw( $post_id );
		$price_mode = isset( $raw['priceMode'] ) ? $raw['priceMode'] : 'dynamic';

		return self::resolve_from_series( $series, $price_mode, $model_id, $layout_id, $color_id );
	}

	/**
	 * Computes the price and resolves labels for a normalized series array.
	 *
	 * Shared by both the post (CPT) and inline (Elementor) data sources.
	 *
	 * @param array  $series     Normalized series array.
	 * @param string $price_mode 'dynamic' or 'product'.
	 * @param string $model_id   Selected model id.
	 * @param string $layout_id  Selected layout id.
	 * @param string $color_id   Selected color id.
	 * @return array|null
	 */
	public static function resolve_from_series( $series, $price_mode, $model_id, $layout_id, $color_id ) {
		if ( empty( $series['models'] ) ) {
			return null;
		}

		$model = self::find_by_id( $series['models'], $model_id );
		if ( null === $model ) {
			return null;
		}

		$price        = (float) $model['basePrice'];
		$layout_label = '';
		$color_name   = '';

		$layout = self::find_by_id( isset( $model['layouts'] ) ? $model['layouts'] : array(), $layout_id );
		if ( null !== $layout ) {
			$price       += (float) $layout['price'];
			$layout_label = $layout['label'];
		}

		$color = self::find_by_id( isset( $series['colors'] ) ? $series['colors'] : array(), $color_id );
		if ( null !== $color ) {
			$price     += (float) $color['price'];
			$color_name = $color['name'];
		}

		$product_id = ! empty( $model['wooId'] ) ? (int) $model['wooId'] : (int) ( isset( $series['wooId'] ) ? $series['wooId'] : 0 );

		return array(
			'price'        => $price,
			'priceMode'    => $price_mode,
			'productId'    => $product_id,
			'model_name'   => $model['name'],
			'model_type'   => isset( $model['type'] ) ? $model['type'] : 'custom',
			'layout_label' => $layout_label,
			'color_name'   => $color_name,
			'series_name'  => isset( $series['name'] ) ? $series['name'] : '',
		);
	}

	/**
	 * Finds an item by its 'id' key inside a list.
	 *
	 * @param array  $list List of associative arrays.
	 * @param string $id   ID to find.
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
	 * Sanitizes an identifier (alphanumeric, dash, underscore).
	 *
	 * @param string $id Raw id.
	 * @return string
	 */
	private static function clean_id( $id ) {
		$id = preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $id );
		return '' !== $id ? $id : uniqid( 'x' );
	}

	/**
	 * Validates a hex/rgba color string, falling back to a default.
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
	 * Validates a module type against the allowed list.
	 *
	 * @param string $type Raw type.
	 * @return string
	 */
	private static function clean_type( $type ) {
		$allowed = array( 'sofa', 'table', 'bed', 'custom' );
		return in_array( $type, $allowed, true ) ? $type : 'custom';
	}
}
