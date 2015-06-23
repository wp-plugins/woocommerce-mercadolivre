<?php
/**
 * MercadoLivre Product
 *
 * This class handles the communication with ML for what concerns about
 * products and provides integration helper functions.
 *
 * @author Carlos Cardoso Dias
 *
 */

/**
 * Anti cheating code
 */
defined( 'ABSPATH' ) or die( 'A Ag&ecirc;ncia Magma n&atilde;o deixa voc&ecirc; trapacear ;)' );

if ( ! class_exists( 'ML_Product' ) ) :

final class ML_Product {

	/**
	 * Prefix for attributes
	 */
	const ML_PREFIX = '_ml_';

	/**
	 * WC_Product instance
	 */
	private $product = null;

	/**
	 * Get ML product
	 *
	 * @param  string     ML id
	 * @return WC_Product product
	 */
	public static function get_product( $ml_id ) {
		$args = array(
			'meta_key'     => self::ML_PREFIX . 'id',
			'meta_value'   => $ml_id,
			'post_type'    => array( 'product' , 'product_variation' ),
			'post_status'  => 'any'
		);

		$posts = get_posts( $args );

		if ( count( $posts ) == 1 ) {
			return wc_get_product( $posts[0] );
		}

		return null;
	}

	/**
	 * Get the product main image url
	 *
	 * @param  string ML id
	 * @return string image url
	 */
	public static function get_main_image( $ml_id ) {
		$product = self::get_product( $ml_id );

		if ( ! empty( $product ) ) {
			$image = wp_get_attachment_url( $product->get_image_id() );

			if ( ! empty( $image ) ) {
				return $image;
			}
		}

		return null;
	}

	/**
	 * Get the id of a video on youtube based on its url
	 *
	 * @param  string video url
	 * @return string video id
	 */
	public static function get_video_id( $video_url ) {
		if ( ! is_string( $video_url ) ) {
			return '';
		}

		$matches = array();
		preg_match( '/v=(.{11})/' , $video_url , $matches );

		return isset( $matches[1] ) ? $matches[1] : $video_url;
	}

	/**
	 * Get the ML product fields
	 *
	 * @return array fields
	 */
	public static function get_fields() {
		return array(
			'id',
			'site_id',
			'title',
			'category_id',
			'price',
			'currency_id',
			'sold_quantity',
			'buying_mode',
			'listing_type_id',
			'start_time',
			'stop_time',
			'end_time',
			'condition',
			'permalink',
			'thumbnail',
			'video_id',
			'status',
			'warranty',
			'parent_item_id',
			'date_created',
			'last_updated',
			'shipping_mode',
			'shipment_local_pickup',
			'shipment_costs',
			'shipment_free_methods',
			'shipment_free_shipping',
			'seller_custom_field',
			'attribute_combinations'
		);
	}

	/**
	 *
	 * Class constructor
	 *
	 */
	public function __construct( $product ) {
		if ( is_string( $product ) ) {
			$this->product = self::get_product( $product );
		} else {
			$this->product = wc_get_product( $product );
		}

		if ( ! ( $this->product instanceof WC_Product ) ) {
			throw new ML_Exception( __( 'Error loading product' , ML()->textdomain ) );
		}
	}

	/**
	 * Magic method to read attributes
	 */
	public function __get( $name ) {
		return get_post_meta( $this->product->is_type( 'variation' ) ? $this->product->variation_id : $this->product->id , self::ML_PREFIX . $name , true );
	}

	/**
	 * Magic method to write attributes
	 */
	public function __set( $name , $value ) {
		update_post_meta( $this->product->is_type( 'variation' ) ? $this->product->variation_id : $this->product->id , self::ML_PREFIX . $name , $value );
	}

	/**
	 * Magic method to check attributes
	 */
	public function __isset( $name ) {
		$attribute = get_post_meta( $this->product->is_type( 'variation' ) ? $this->product->variation_id : $this->product->id , self::ML_PREFIX . $name , true );
		return ( ! empty( $attribute ) );
	}

	/**
	 * Magic method to delete attributes
	 */
	public function __unset( $name ) {
		delete_post_meta( $this->product->is_type( 'variation' ) ? $this->product->variation_id : $this->product->id , self::ML_PREFIX . $name );
	}

	/**
	 * Check if the product is posted at ML
	 */
	public function is_published() {
		return isset( $this->id );
	}

	/**
	 * Check if the product is variable
	 */
	public function is_variable() {
		return $this->product->is_type( 'variable' );
	}

	/**
	 * Check if the product can update special fields at ML
	 */
	public function can_update_special_fields() {
		return ( ! $this->is_published() || ! isset( $this->sold_quantity ) || ( isset( $this->sold_quantity ) && ( $this->sold_quantity == 0 ) ) );
	}

	/**
	 * Get WC_Product for this ML_Product instante
	 */
	public function get_wc_product() {
		return $this->product;
	}

	/**
	 * Get ML string format for product dimensions
	 */
	public function get_dimensions() {
		return implode( 'x' , array( $this->product->length , $this->product->width , $this->product->height ) ) . ',' . strval( floatval( $this->product->weight ) * 1000 );
	}

	/**
	 * Dimensions validation according to ML
	 */
	public function has_dimensions() {
		$variables = array( $this->product->width , $this->product->length , $this->product->height , $this->product->weight );

		foreach ( $variables as $variable ) {
			if ( empty( $variable ) || ! is_numeric( $variable ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * This function post the product at ML, throws an exception in 
	 * case of error, with the error cause in the message.
	 *
	 * @param  boolean                 Whether to replace the already stored product information or not
	 * @return Object                  Product data at ML
	 * @throws ML_Exception
	 **/
	public function post() {
		if ( $this->is_published() ) {
			throw new ML_Exception( __( 'This product is already posted at ML' , ML()->textdomain ) );
		}
		
		$body = apply_filters( 'post_ml_product' , $this->get_body() , $this->product );
		
		$ml_product = ML()->ml_communication->post_resource( '/items' , $body );
		
		$this->save_data( $ml_product );

		return $ml_product;
	}

	/**
	 * This function validates the product structure to post in ML and return true 
	 * if everything is OK and this product can be posted, false otherwise.
	 *
	 * @return boolean
	 */
	public function validate() {
		try {
			$data = ML()->ml_communication->post_resource( '/items/validate' , $this->get_body() );
		} catch ( ML_Exception $e ) {
			return false;
		}

		return true;
	}

	/**
	 * Update a product at ML
	 *
	 * @param  WC_product  Product
	 * @return Object      New product data at ML
	 */
	public function update() {
		$body = apply_filters( 'update_ml_product' , $this->get_body() , $this->product );

		$ml_product = ML()->ml_communication->put_resource( "/items/{$this->id}" , $body );
		
		$this->save_data( $ml_product );

		return $ml_product;
	}

	/**
	 * This function updates a product stock at ML, throws an exception in 
	 * case of error, with the error cause in the message.
	 *
	 * @param  int|WP_Post|WC_Product  Product ID, post object or WC_Product itself
	 * @return stdClass                Response from ML
	 * @throws ML_Exception
	 */
	public function update_stock() {
		$stock_data = array();

		if ( $this->is_variable() ) {
			foreach ( $this->product->get_children() as $child_id ) {
				$child_product = new ML_Product( $child_id );
				$stock_data[] = array(
					'id'                 => $child_product->id,
					'available_quantity' => $child_product->get_wc_product()->get_stock_quantity()
				);
			}
		} else {
			$stock_data['available_quantity'] = $this->product->get_stock_quantity();
		}

		$ml_product = ML()->ml_communication->put_resource( "/items/{$this->id}" , $stock_data );

		$this->save_data( $ml_product );

		return $ml_product;
	}

	/**
	 * This function updates a product listing type at ML, throws an exception in 
	 * case of error, with the error cause in the message.
	 *
	 * @param  string                  New listing type id
	 * @return stdClass                Response from ML
	 * @throws ML_Exception
	 */
	public function update_listing_type( $new_listing_type ) {
		if ( ! in_array( $new_listing_type , wp_list_pluck( ML()->ml_communication->get_listing_types() , 'id' ) ) ) {
			throw new ML_Exception( __( 'Invalid listing type' , ML()->textdomain ) );
		}

		$body = apply_filters( 'update_ml_product_listing_type' , array( 'id' => $new_listing_type ) , $this->product );

		$ml_product = ML()->ml_communication->post_resource( "/items/{$this->id}/listing_type" , $body );

		$this->save_data( $ml_product );

		return $ml_product;
	}

	/**
	 * This function updates a product status at ML, throws an exception in 
	 * case of error, with the error cause in the message.
	 *
	 * @param  string                  New status
	 * @return stdClass                Response from ML
	 * @throws ML_Exception
	 */
	public function update_status( $new_status ) {
		$new_status = strtolower( $new_status );

		if ( ! in_array( $new_status , array( 'closed' , 'paused' , 'active' ) ) ) {
			throw new ML_Exception( __( 'Invalid status' , ML()->textdomain ) );
		}

		if ( ( $new_status == 'closed' ) && ( $this->status == 'closed' ) ) {
			$this->delete_data();
			return null;
		}

		$body = apply_filters( 'update_ml_product_status' , array( 'status' => $new_status ) , $this->product );

		$ml_product = ML()->ml_communication->put_resource( "/items/{$this->id}" , $body );

		if ( $new_status == 'closed' ) {
			$this->delete_data();
		} else {
			$this->save_data( $ml_product );
		}

		return $ml_product;
	}

	/**
	 * This function updates a product description at ML, throws an exception in 
	 * case of error, with the error cause in the message.
	 *
	 * @param  string                  New description
	 * @return stdClass                Response from ML
	 * @throws ML_Exception
	 */
	public function update_description( $new_description = null ) {
		if ( empty( $new_description ) ) {
			$new_description = $this->product->get_post_data()->post_content;
		}

		$body = apply_filters( 'update_ml_product_description' , array( 'text' => $new_description ) , $this->product );
		
		return ML()->ml_communication->put_resource( "/items/{$this->id}/description" , $body );
	}

	/**
	 * This function relist a product at ML, throws an exception in 
	 * case of error, with the error cause in the message.
	 *
	 * @param  int|WP_Post|WC_Product  Product ID, post object or WC_Product itself
	 * @param  string                  New description
	 * @return stdClass                Response from ML
	 * @throws ML_Exception
	 */
	public function relist() {
		if ( $this->status != 'closed' ) {
			throw new ML_Exception( __( 'Only items with a closed status admit relisting' , ML()->textdomain ) );
		}

		if ( ! in_array( $this->listing_type_id , array( 'gold_premium' , 'gold' , 'silver' ) ) ) {
			$this->delete_data( 'id' );
			return $this->post();
		}

		$body['listing_type_id'] = $this->listing_type_id;

		if ( $this->is_variable() ) {
			foreach ( $this->product->get_children() as $child_id ) {
				$child_product = new ML_Product( $child_id );

				if ( ! $child_product->is_published() ) {
					continue;
				}

				$body['variations'][] = array(
					'id'       => $child_product->id,
					'price'    => empty( $child_product->price ) ? $child_product->get_wc_product()->get_price() : $child_product->price,
					'quantity' => $child_product->get_wc_product()->get_stock_quantity()
				);
			}
		} else {
			$body['price'] = empty( $this->price ) ? $this->product->get_price() : $this->price;
			$body['quantity'] = $this->product->get_stock_quantity();
		}

		$body = apply_filters( 'relist_ml_product' , $body , $this->product );

		$ml_product = ML()->ml_communication->post_resource( "/items/{$this->id}/relist" , $body );

		$this->save_data( $ml_product );
		
		return $ml_product;
	}

	/**
	 * Save ml product data
	 */
	public function save_data( &$ml_product ) {
		$excluded_fields = array(
			'title',
			'price',
			'seller_custom_field',
			'attribute_combinations',
			'shipment_local_pickup',
			'shipment_costs',
			'shipment_free_methods',
			'shipment_free_shipping'
		);

		foreach ( self::get_fields() as $field ) {
			if ( in_array( $field , $excluded_fields ) ) {
				continue;
			}

			if ( $field == 'shipping_mode' ) {
				// Save shipping information
				$this->shipping_mode = $ml_product->shipping->mode;

				switch ( $ml_product->shipping->mode ) {
					case 'custom':
						$this->shipment_local_pickup = ( $ml_product->shipping->local_pick_up == 1 );
						if ( isset( $ml_product->shipping->free_shipping ) ) {
							$this->shipment_free_shipping = ( $ml_product->shipping->free_shipping == 1 );
						}
						break;
					case 'me1':
						break;
					case 'me2':
						break;
				}

			} else {
				$this->{ $field } = $ml_product->{ $field };
			}
		}
		
		// Save variable items
		if ( ! empty( $ml_product->variations ) ) {
			foreach ( $ml_product->variations as $variation ) {
				$child_id      = empty( $variation->seller_custom_field ) ? strval( $variation->id ) : intval( $variation->seller_custom_field );
				$child_product = new ML_Product( $child_id  );

				if ( isset( $child_product->price ) ) {
					$child_product->price = $variation->price;
				}

				$child_product->id                  = $variation->id;
				$child_product->sold_quantity       = $variation->sold_quantity;
				$child_product->seller_custom_field = $variation->seller_custom_field;

				$attribute_combinations = array();
				foreach ( $variation->attribute_combinations as $attribute_combination ) {
					$attribute_combinations[] = array( 'id' => $attribute_combination->id , 'value_id' => $attribute_combination->value_id );
				}

				$child_product->attribute_combinations = $attribute_combinations;
			}
		}
	}

	/**
	 * Delete ML product data
	 */
	public function delete_data( $ml_fields = null ) {
		if ( empty( $ml_fields ) ) {
			$ml_fields = self::get_fields();
		} else if ( is_string( $ml_fields ) ) {
			$ml_fields = array( $ml_fields );
		}

		$ids = $this->is_variable() ? array_merge( array( $this->product->id ) , $this->product->get_children() ) : array( $this->product->id );
		
		foreach ( $ids as $id ) {
			foreach ( $ml_fields as $ml_field ) {
				delete_post_meta( $id , self::ML_PREFIX . $ml_field );
			}
		}
	}

	/**
	 * Get product ML format
	 */
	private function get_body() {
		// Set default fields
		$ml_data = array( 'video_id' => ML_Product::get_video_id( $this->video_id ) );

		if ( ! $this->is_published() ) {
			// Fields for a new product
			$new_fields = array(
				'category_id'      => $this->category_id,
				'currency_id'      => ML()->ml_communication->get_default_currency(),
				'listing_type_id'  => empty( $this->listing_type_id ) ? 'bronze' : $this->listing_type_id,
				'description'      => $this->product->get_post_data()->post_content
			);

			if ( ! empty( $this->official_store_id ) ) {
				$new_fields['official_store_id'] = $this->official_store_id;
			}

			$ml_data = array_merge( $ml_data , $new_fields );
		}

		if ( $this->can_update_special_fields() ) {
			// Fields that can only be updated when the product has no sales
			$new_fields = array(
				'title'            => empty( $this->title ) ? $this->product->get_title() : $this->title,
				'buying_mode'      => empty( $this->buying_mode ) ? 'buy_it_now' : $this->buying_mode,
				'condition'        => empty( $this->condition ) ? 'new' : $this->condition,
				'warranty'         => $this->warranty
			);

			$this->set_shipping_fields( $ml_data );

			$ml_data = array_merge( $ml_data , $new_fields );
		}

		if ( ! $this->is_published() || ! $this->is_variable() ) {
			$ml_data = array_merge( $ml_data , array( 'price' => empty( $this->price ) ? $this->product->get_price() : $this->price ) );
		}

		if ( $this->is_variable() ) {
			return $this->get_variable_body( $ml_data );
		}

		return $this->get_simple_body( $ml_data );
	}

	/**
	 * Get ML json format for simple products 
	 */
	private function get_simple_body( &$ml_data ) {
		$this->set_pictures_to_body( $this->product , $ml_data );
		$ml_data['available_quantity'] = $this->product->get_stock_quantity();
		return $ml_data;
	}

	/**
	 * Get ML json format for variable products 
	 */
	private function get_variable_body( &$ml_data ) {
		$variation_counter = 0;

		foreach ( $this->product->get_children() as $child_id ) {
			$child_product = new ML_Product( $child_id );

			// Check if this variation is linked with ML
			if ( empty( $child_product->id ) && empty( $child_product->attribute_combinations ) ) {
				continue;
			}

			$ml_data['variations'][ $variation_counter ] = array(
				'available_quantity'     => $child_product->get_wc_product()->get_stock_quantity(),
				'price'                  => empty( $child_product->price ) ? $child_product->get_wc_product()->get_price() : $child_product->price,
				'seller_custom_field'    => strval( $child_id )
			);

			$this->set_pictures_to_body( $child_product->get_wc_product() , $ml_data , $variation_counter );
			$this->set_pictures_to_body( $child_product->get_wc_product() , $ml_data );

			if ( $child_product->is_published() ) {
				$ml_data['variations'][ $variation_counter ]['id'] = intval( $child_product->id );
			} else {
				$ml_data['variations'][ $variation_counter ]['attribute_combinations'] = $child_product->attribute_combinations;
			}


			$variation_counter++;
		}

		return $ml_data;
	}

	/**
	 * Set ML JSON structure for shipment fields
	 */
	private function set_shipping_fields( &$ml_data ) {
		if ( ! in_array( $this->shipping_mode , array( 'not_specified' , 'custom' ) ) ) {
			return;
		}

		$ml_data['shipping']['mode'] = $this->shipping_mode;

		if ( $this->has_dimensions() ) {
			$ml_data['shipping']['dimensions'] = $this->get_dimensions();
		}

		switch ( $this->shipping_mode ) {
			case 'custom':
				$ml_data['shipping']['local_pick_up'] = $this->shipment_local_pickup;
				$ml_data['shipping']['free_shipping'] = $this->shipment_free_shipping;
				$ml_data['shipping']['methods']       = array();
				$ml_data['shipping']['costs']         = $this->shipment_costs;
				break;
			case 'me1':
				break;
			case 'me2':
				break;
		}
	}

	/**
     * Atach the ML pictures structure to products body
     *
     * @param  WC_Product   Product to get pictures
     * @param  array        ML product body
     * @param  int          Position of the variation, or -1 for non-variable products
     * @return void
     */
	private function set_pictures_to_body( &$product , &$ml_data , $for_variation = -1 ) {
		$array_id = array();
		
		$main_image = $product->get_image_id();
		
		if ( ! empty( $main_image ) ) {
			$array_id[] = $product->get_image_id();
		}

		$attachments = $product->get_gallery_attachment_ids();
		
		if ( empty( $attachments ) ) {
			$attachments = explode( ',', wc_clean( $_POST['product_image_gallery'] ) );
		}

		$array_id = array_filter( array_merge( $array_id , $attachments ) );

		foreach ( $array_id as $id ) {
			$image_meta     = ML()->ml_communication->upload_picture( $id );
			$img_properties = wp_get_attachment_image_src( $id , 'full' );
			
			if ( empty( $image_meta['image_meta']['ml_id'] ) && empty( $img_properties ) ) {
				continue;
			}

			if ( $for_variation >= 0 ) {
				$ml_data['variations'][ $for_variation ][ 'picture_ids' ][] = isset( $image_meta['image_meta']['ml_id'] ) ? $image_meta['image_meta']['ml_id'] : $img_properties[0];
			} else {
				$ml_data['pictures'][] = isset( $image_meta['image_meta']['ml_id'] ) ? array( 'id' => $image_meta['image_meta']['ml_id'] ) : array( 'source' => $img_properties[0] );
			}
		}
	}
}

endif;