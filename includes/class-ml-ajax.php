<?php
/**
 * MercadoLivre Ajax
 * 
 * @author Carlos Cardoso Dias
 *
 **/

/**
 * Anti cheating code
 */
defined( 'ABSPATH' ) or die( 'A Ag&ecirc;ncia Magma n&atilde;o deixa voc&ecirc; trapacear ;)' );

if ( ! class_exists( 'ML_Ajax' ) ) :

final class ML_Ajax extends MGM_Plugin {

	/**
	 * Get last comments at ML
	 *
	 * @ajax( action: "get_ml_comments" , only_logged: true )
	 */
	public function get_last_comments() {
		check_ajax_referer( 'ml-comments-action' , 'security' );

		if ( is_null( ML()->ml_communication ) ) {
			die();
		}

		$comments = array();

		foreach ( array_reverse( ML_Questions::get_last_questions() ) as $question ) {
			$author = ML()->ml_communication->get_user( $question->from->id );

			// Get the product image thumbnail at the site if it has any
			$image = ML_Product::get_main_image( $question->item_id );
			
			// Check if the post has answer
			$active_question = ( $question->status == 'UNANSWERED' );

			// Creating the comment
			$comment = array(
				'Id'         => $question->id,
				'Author'     => sprintf( '<a href="%s" target="_blank">%s</a>' , $author->permalink , $author->nickname ),
				'Comment'    => $question->text,
				'ParentId'   => null,
				'UserAvatar' => $image,
				'CanDelete'  => $active_question,
				'CanReply'   => $active_question,
				'Date'       => date( 'H:m d/m/Y' , strtotime( $question->date_created ) )
			);

			$comments[] = $comment;

			if ( ! is_null( $question->answer ) ) {
				// Creating the aswer
				$answer = array(
					'Id'         => 0,
					'Author'     => ML()->ml_nickname,
					'Comment'    => $question->answer->text,
					'ParentId'   => $question->id,
					'UserAvatar' => $image,
					'CanDelete'  => false,
					'CanReply'   => false,
					'Date'       => date( 'H:m d/m/Y' , strtotime( $question->answer->date_created ) )
				);

				$comments[] = $answer;
			}
		}

		wp_send_json( $comments );
	}

	/**
	 * Post answer to question at ML
	 *
	 * @ajax( action: "post_ml_comment" , only_logged: true )
	 */
	public function post_aswer() {
		check_ajax_referer( 'ml-comments-action' , 'security' );

		if ( is_null( ML()->ml_communication ) || ! isset( $_POST['parentId'] ) || ! isset( $_POST['comment'] ) ) {
			die();
		}

		$answered_question = ML_Questions::answer_question( $_POST['parentId'] , $_POST['comment'] );

		if ( is_null( $answered_question ) ) {
			die();
		}

		$answer = array(
			'Id'         => 0,
			'Author'     => ML()->ml_nickname,
			'Comment'    => $answered_question->answer->text,
			'ParentId'   => $answered_question->id,
			'UserAvatar' => ML_Product::get_main_image( $answered_question->item_id ),
			'CanDelete'  => false,
			'CanReply'   => false,
			'Date'       => date( 'H:m d/m/Y' , strtotime( $answered_question->answer->date_created ) )
		);

		wp_send_json( $answer );
	}

	/**
	 * Delete question at ML
	 *
	 * @ajax( action: "delete_ml_comment" , only_logged: true )
	 */
	public function delete_answer() {
		check_ajax_referer( 'ml-comments-action' , 'security' );

		if ( isset( $_POST['commentId'] ) && ! is_null( ML()->ml_communication ) && ML_Questions::delete_question( $_POST['commentId'] ) ) {
			wp_send_json( $_POST['commentId'] );
		}
		
		die( -1 );
	}

	/**
	 * Get subcategories at ML
	 *
	 * @ajax( action: "get_ml_subcategories" , only_logged: true )
	 */
	public function get_subcategories() {
		check_ajax_referer( 'ml-product-action' , 'security' );
		
		if ( empty( $_GET['id'] ) ) {
			wp_send_json( wp_list_pluck( ML_Category::get_categories( ML()->ml_site ) , 'name' , 'id' ) );
		}
		
		if ( $_GET['id'] == 'null' ) {
			die();
		}

		wp_send_json( wp_list_pluck( ML_Category::get_subcategories( $_GET['id'] ) , 'name' , 'id' ) );
	}

	/**
	 * Checks whether the selected category has variations at ML
	 *
	 * @ajax( action: "check_variation" , only_logged: true )
	 */
	public function check_variations() {
		check_ajax_referer( 'ml-product-action' , 'security' );

		if ( ! isset( $_GET['category'] , $_GET['product_id'] ) ) {
			die();
		}

		$variations = ML_Category::get_category_variations( $_GET['category'] );
		$ml_product = new ML_Product( intval( $_GET['product_id'] ) );
		
		ob_start();
		
		if ( ! empty( $variations ) ) {
			include( 'views/html-variations.php' );
		}
		
		wp_send_json( ob_get_clean() );
	}

	/**
	 * Add an html line variation 
	 *
	 * @ajax( action: "add_variation_line" , only_logged: true )
	 */
	public function add_variation() {
		check_ajax_referer( 'ml-variation-line-action' , 'security' );

		if ( ! isset( $_GET['product_id'] , $_GET['category'] ) ) {
			die();
		}

		ob_start();
		
		$variations = ML_Category::get_category_variations( $_GET['category'] );
		$ml_product = new ML_Product( intval( $_GET['product_id'] ) );
		
		include( 'views/html-variation-line.php' );
		
		wp_send_json( ob_get_clean() );
	}

	/**
	 * Relist a closed product at ML
	 *
	 * @ajax( action: "relist_ml_product" , only_logged: true )
	 */
	public function relist_product() {
		check_ajax_referer( 'ml-relist-product-action' , 'security' );

		if ( ! isset( $_GET['post_id'] ) ) {
			die();
		}

		try {
			$ml_product = new ML_Product( intval( $_GET['post_id'] ) );
			$ml_product->relist();
			ML()->add_notice( __( 'The product was successfully relisted on MercadoLivre' , ML()->textdomain ) , 'success' );
		} catch ( ML_Exception $e ) {
			ML()->add_notice( sprintf( '%s: %s' , __( 'The product could not be relisted on MercadoLivre' , ML()->textdomain ) , $e->getMessage() ) , 'error' );
		}
		
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=product' ) );
		die();
	}

	/**
	 * Update a product at ML
	 *
	 * @ajax( action: "change_status_ml_product" , only_logged: true )
	 */
	public function change_product_status() {
		check_ajax_referer( 'ml-change-status-action' , 'security' );

		if ( ! isset( $_GET['post_id'] , $_GET['status'] ) ) {
			die();
		}
		try {
			$ml_product = new ML_Product( intval( $_GET['post_id'] ) );
			$ml_product->update_status( $_GET['status'] );
		} catch (ML_Exception $e) {
			ML()->add_notice( sprintf( '%s: %s' , __( 'Error while trying to update product status on MercadoLivre' , ML()->textdomain ) , $e->getMessage() ) , 'error' );
		}
		
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=product' ) );
		die();
	}

	/**
	 * Get shipment content
	 *
	 * @ajax( action: "get_ml_shipment" , only_logged: true )
	 */
	public function get_shipment() {
		check_ajax_referer( 'ml-shipment-action' , 'security' );

		if ( empty( $_GET['product_id'] ) || empty( $_GET['shipping_mode'] ) || ! in_array( $_GET['shipping_mode'] , array( 'custom' ) ) ) {
			die();
		}

		$ml_product  = new ML_Product( intval( $_GET['product_id'] ) );
		$category_id = empty( $ml_product->category_id ) ? $_GET['category_id'] : $ml_product->category_id;
		
		ob_start();
		
		include( "views/html-{$_GET['shipping_mode']}-shipment.php" );
		
		wp_send_json( ob_get_clean() );
	}

	/**
	 * Get shipping modes
	 *
	 * @ajax( action: "get_ml_shipping_modes" , only_logged: true )
	 */
	public function get_shipping_modes() {
		check_ajax_referer( 'ml-shipping-action' , 'security' );

		if ( empty( $_GET['category'] ) ) {
			die();
		}

		$return = array();
		foreach ( ML()->ml_communication->get_shipping_modes( $_GET['category'] ) as $key => $value ) {
			$return[] = array( 'id' => $key , 'name' => $value );
		}

		wp_send_json( $return );
	}
}
endif;