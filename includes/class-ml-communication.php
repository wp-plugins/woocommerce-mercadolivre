<?php
/**
 * MercadoLivre Communication
 * 
 * @author Carlos Cardoso Dias
 *
 **/

/**
 * Anti cheating code
 */
defined( 'ABSPATH' ) or die( 'A Ag&ecirc;ncia Magma n&atilde;o deixa voc&ecirc; trapacear ;)' );

if ( ! class_exists( 'ML_Communication' ) ) :

require_once( 'core/class-mgm-singleton.php' );
require_once( 'class-ml-exception.php' );
require_once( 'class-ml.php' );

final class ML_Communication extends MGM_Singleton {

	private $meli = null;

	/**
	 * Get all shipping modes disponible at ML
	 *
	 * @return array shipping modes
	 */
	public static function get_all_shipping_modes() {
		$textdomain = ML()->textdomain;
		
		return array(
			'not_specified'  => __( 'Not Specified' , $textdomain ),
			'custom'         => __( 'Custom' , $textdomain )
		);
	}
	
	/**
	 * Store the new token data and returns true if everything went ok.
	 *
	 * @return boolean
	 */
	public static function update_token_data( $new_token ) {
		if ( $new_token['httpCode'] != 200 ) {
			return false;
		}
		
		ML()->ml_access_token  = $new_token['body']->access_token;
		ML()->ml_expires_in    = time() + $new_token['body']->expires_in;
		ML()->ml_refresh_token = $new_token['body']->refresh_token;
		
		return true;
	}

	/**
	 * Erases the token data.
	 *
	 * @return void
	 */
	public static function reset_token_data() {
		unset( ML()->ml_access_token );
		unset( ML()->ml_expires_in );
		unset( ML()->ml_refresh_token );
	}

	/**
	 * ML_Communication initialization.
	 *
	 * @return void
	 */
	public function init() {
		if ( isset( ML()->ml_app_id , ML()->ml_secret_key , ML()->ml_access_token , ML()->ml_refresh_token ) ) {
			$this->meli = new Meli( ML()->ml_app_id , ML()->ml_secret_key , ML()->ml_access_token , ML()->ml_refresh_token );
		} else if ( isset( ML()->ml_app_id , ML()->ml_secret_key ) ) {
			$this->meli = new Meli( ML()->ml_app_id , ML()->ml_secret_key );
		}
	}

	/**
	 * This function checks whether the user is logged in and if he is not, it tries to log the user.
	 * Returns true if the user is logged and false otherwise.
	 *
	 * @return boolean
	 **/
	public function is_logged() {
		if ( is_null( $this->meli ) ) {
			return false;
		}

		if ( isset( ML()->ml_access_token ) ) {
			// We can check if the access token is invalid checking the time
			if( ML()->ml_expires_in < time() ) {
				// Make the refresh process and update de app variables with the new parameters
				return self::update_token_data( $this->meli->refreshAccessToken() );
			}

			// Everything is OK and now the user is logged
			return true;
		}

		if( isset( $_GET['code'] ) ) {
			// Now we create the app variables with the authenticated user
			$new_token = $this->meli->authorize( $_GET['code'] , WooCommerce_MercadoLivre::get_admin_settings_page_url() );
			return self::update_token_data( $new_token );
		}

		// It seems that the URL don't have the necessary code
		return false;
	}

	/**
	 * This function gets the URL to the user authenticate the application in ML.
	 *
	 * @return string
	 **/
	public function get_login_url() {
		if ( is_null( $this->meli ) ) {
			return '';
		}

		return $this->meli->getAuthUrl( WooCommerce_MercadoLivre::get_admin_settings_page_url() );
	}

	/**
	 * Get a generic resource from ML
	 *
	 * @param  string        Resource name
	 * @param  array         Params for the get call
	 * @throws ML_Exception  In case of bad response
	 * @return Object        Data body
	 */
	public function get_resource( $resource , $params = array() ) {
		return $this->execute( 'get' , $resource , $params );
	}

	/**
	 * Delete a generic resource from ML
	 *
	 * @param  string        Resource name
	 * @param  array         Params for the delete call
	 * @throws ML_Exception  In case of bad response
	 * @return Object        Data body
	 */
	public function delete_resource( $resource , $params = array() ) {
		return $this->execute( 'delete' , $resource , $params );
	}

	/**
	 * Post a generic resource to ML
	 *
	 * @param  string        Resource name
	 * @param  Object        Resource body
	 * @param  array         Params for the post call
	 * @throws ML_Exception  In case of bad response
	 * @return Object        Data body
	 */
	public function post_resource( $resource_name , $resource_body , $params = array() ) {
		return $this->execute( 'post' , $resource_name , $params , $resource_body );
	}

	/**
	 * Update a generic resource to ML
	 *
	 * @param  string        Resource name
	 * @param  Object        Resource body
	 * @param  array         Params for the post call
	 * @throws ML_Exception  In case of bad response
	 * @return Object        Data body
	 */
	public function put_resource( $resource_name , $resource_body , $params = array() ) {
		return $this->execute( 'put' , $resource_name , $params , $resource_body );
	}

	/**
	 * Upload a picture to ML
	 *
	 * @param  int           Picture id
	 * @param  array         Params for the upload call
	 * @throws ML_Exception  In case of bad response
	 * @return array         Image metadata
	 */
	public function upload_picture( $picture_id , $params = array() ) {
		$image_meta   = wp_get_attachment_metadata( $picture_id );
		$upload_dir   = wp_upload_dir();
		$picture_path = $upload_dir['basedir'] . '/' . $image_meta['file'];
		
		if ( isset( $image_meta['image_meta']['ml_id'] ) || empty( $image_meta ) ) {
			return $image_meta;
		}

		try {
			$ml_picture = $this->execute( 'upload' , '/pictures' , $params , $picture_path );
			$image_meta['image_meta']['ml_id'] = $ml_picture->id;
			wp_update_attachment_metadata( $picture_id , $image_meta );
			ML()->add_notice( sprintf( __( 'Image %s succesfully posted' , ML()->textdomain ) , $image_meta['file'] ) , 'success' );
		} catch (ML_Exception $e) {
			ML()->add_notice( sprintf( __( 'An error ocurred while trying to upload the picture %s to MercadoLivre: %s' , ML()->textdomain ) , $picture_path , $e->getMessage() ) , 'error' );
		}

		return $image_meta;
	}

	/**
	 * Get a user object from ML
	 *
	 * @param  string user_id
	 * @return Object user
	 */
	public function get_user( $user_id = 'me' ) {
		try {
			return $this->get_resource( '/users/' . $user_id );
		} catch (ML_Exception $e) {
			ML()->add_notice( __( 'An error ocurred while trying to communicate with MercadoLivre. Try to reload the page.' , ML()->textdomain ) , 'error' );
		}

		return null;
	}

	/**
	 * Get disponible ML sites
	 *
	 * @return Object sites
	 */
	public function get_sites() {
		try {
			return $this->get_resource( '/sites' );
		} catch (ML_Exception $e) {
			ML()->add_notice( __( 'An error ocurred while trying to communicate with MercadoLivre. Try to reload the page.' , ML()->textdomain ) , 'error' );
		}

		return null;
	}

	/**
	 * Get listing types from ML
	 *
	 * @return Object listing types
	 */
	public function get_listing_types() {
		try {
			$site = 'MLB';
			
			if ( ! empty( ML()->ml_site ) ) {
				$site = ML()->ml_site;
			}
			
			return $this->get_resource( "/sites/{$site}/listing_types" );
		} catch (ML_Exception $e) {
			ML()->add_notice( __( 'An error ocurred while trying to communicate with MercadoLivre. Try to reload the page.' , ML()->textdomain ) , 'error' );
		}
		
		return null;
	}

	/**
	 * Get default currency from ML
	 *
	 * @return string default currency
	 */
	public function get_default_currency() {
		try {
			$currency = $this->get_resource( '/sites/' . ML()->ml_site , array( 'attributes' => 'default_currency_id' ) );
			return $currency->default_currency_id;
		} catch (ML_Exception $e) {
			ML()->add_notice( __( 'An error ocurred while trying to communicate with MercadoLivre. Try to reload the page.' , ML()->textdomain ) , 'error' );
		}

		return 'BRL';
	}

	/**
	 * Get disponible shipping modes for the authenticated user from ML
	 *
	 * @return array shipping modes
	 */
	public function get_shipping_modes( $category_id = null ) {
		$all_shipping_modes = self::get_all_shipping_modes();

		try {
			if ( ! empty( $category_id ) ) {
				$params = array( 'category_id' => $category_id );
				$user = $this->get_user();
				$disponible_modes = wp_list_pluck( $this->get_resource( "users/{$user->id}/shipping_modes" , $params ) , 'mode' );
				return array_intersect_key( $all_shipping_modes , array_flip( $disponible_modes ) );
			}
		} catch (ML_Exception $e) {
			ML()->add_notice( __( 'An error ocurred while trying to communicate with MercadoLivre. Try to reload the page.' , ML()->textdomain ) , 'error' );
		}

		return array( 'not_specified' => $all_shipping_modes['not_specified'] );
	}

	/**
	 * Return a shipping configuration for a specific category and shipping mode
	 *
	 * @return stdClass shipping
	 */
	public function get_shipping_mode_configuration( $category_id , $shipping_mode ) {
		try {
			$user = $this->get_user();
			$params = array( 'category_id' => $category_id );

			foreach ( $this->get_resource( "users/{$user->id}/shipping_modes" , $params ) as $shipping ) {
				if ( $shipping->mode == $shipping_mode ) {
					return $shipping;
				}
			}

		} catch (ML_Exception $e) {
			ML()->add_notice( __( 'An error ocurred while trying to communicate with MercadoLivre. Try to reload the page.' , ML()->textdomain ) , 'error' );
		}
		
		return null;
	}

	/**
	 * Return the official store information associated with the user
	 *
	 * @return stdClass Official store
	 */
	public function get_official_store() {
		try {
			$user = $this->get_user();
			return $this->get_resource( "users/{$user->id}/brands" );
		} catch (ML_Exception $e) {
		}

		return null;
	}

	/**
	 * Executes a generic request to ML
	 * 
	 * @param  string        Method name
	 * @param  string        Resource name
	 * @param  Object        Resource body
	 * @param  array         Params for the request
	 * @throws ML_Exception  In case of bad response
	 * @return Object        Data body
	 */
	private function execute( $method , $resource_name , &$params = array() , &$resource_body = null ) {
		if ( ! isset( $params['access_token'] ) && isset( ML()->ml_access_token ) ) {
			$params['access_token'] = ML()->ml_access_token;
		}

		$method_params = array( $resource_name );
		
		if ( ! is_null( $resource_body ) ) {
			$method_params[] = $resource_body;
		}

		$method_params[] = $params;

		$data = call_user_func_array( array( $this->meli , $method ) , $method_params );

		if ( ! isset( $data['httpCode'] , $data['body'] ) || ( $data === false ) ) {
			throw new ML_Exception( __( 'Empty response from ML' , ML()->textdomain ) );
		}

		if ( ! in_array( $data['httpCode'] , array( 200 , 201 , 204 ) ) ) {
			throw new ML_Exception( $data['body'] );
		}

		return $data['body'];
	}
}

endif;
