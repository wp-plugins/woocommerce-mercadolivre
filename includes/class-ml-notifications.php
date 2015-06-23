<?php
/**
 * MercadoLivre Notifications
 * 
 * @author Carlos Cardoso Dias
 *
 **/

/**
 * Anti cheating code
 */
defined( 'ABSPATH' ) or die( 'A Ag&ecirc;ncia Magma n&atilde;o deixa voc&ecirc; trapacear ;)' );

if ( ! class_exists( 'ML_Notifications' ) ) :

final class ML_Notifications extends MGM_Singleton {

	/**
	 * Notifications handler for ML
	 */
	public function process_notifications() {
		// Get the json post data
		global $HTTP_RAW_POST_DATA;
		
		if ( empty( $HTTP_RAW_POST_DATA ) ) {
			$HTTP_RAW_POST_DATA = file_get_contents( 'php://input' );
		}

		$notification = json_decode( $HTTP_RAW_POST_DATA );
		
		// End procedure if the json data or specific handler doesn't exist
		if ( empty( $notification ) || ! in_array( $notification->topic , array( 'payments' , 'items' , 'questions' , 'orders' ) ) ) {
			return;
		}
		
		// Get the resource data
		$resource = ML()->ml_communication->get_resource( $notification->resource );

		// End if resource is not a valid one
		if ( empty( $resource ) ) {
			return;
		}

		// Dispatch to proper handler with the resource data
		call_user_func( array( $this , $notification->topic . '_handler' ) , $resource );
	}

	/**
	 * Handler for ML payments
	 */
	public function payments_handler( $resource ) {
	}

	/**
	 * Handler for ML items
	 */
	public function items_handler( $resource ) {
		$ml_product = null;

		try {
			$ml_product = new ML_Product( $resource->id );
		} catch (ML_Exception $e) {
			$ml_product = null;
		}

		if ( isset( $ml_product ) && $ml_product->is_published() ) {
			$ml_product->save_data( $resource );
			$ml_product->get_wc_product()->set_stock( $resource->available_quantity );
		}
	}

	/**
	 * Handler for ML questions
	 */
	public function questions_handler( $resource ) {
	}

	/**
	 * Handler for ML orders
	 */
	public function orders_handler( $resource ) {
	}
}

endif;

remove_action( 'woocommerce_product_set_stock' , array( ML() , 'update_product_quantity' ) );

ML_Notifications::get_instance( 'ML_Notifications' )->process_notifications();

exit();