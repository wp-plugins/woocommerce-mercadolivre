<?php
/**
 * MercadoLivre Metaboxes
 * 
 * @author Carlos Cardoso Dias
 *
 **/

/**
 * Anti cheating code
 */
defined( 'ABSPATH' ) or die( 'A Ag&ecirc;ncia Magma n&atilde;o deixa voc&ecirc; trapacear ;)' );

if ( ! class_exists( 'ML_Metaboxes' ) ) :

final class ML_Metaboxes extends MGM_Plugin {

	/**
	 * Add the ML Last Comments widget
	 *
	 * @action( hook: "wp_dashboard_setup" )
	 */
	public function add_dashboard_widget() {
		wp_add_dashboard_widget( 'ml_dashboard_widget' , __( 'MercadoLivre - Last Comments' , ML()->textdomain ), array( &$this , 'print_comments_widget' ) );
	}

	/**
	 * Render the ML Last Comments widget
	 */
	public function print_comments_widget( $post , $callback_args ) {
		echo '<div id="ml-comments"></div>';
	}

	/**
	 * Include script and style on the dashboard
	 *
	 * @script( include_in: "admin" )
	 */
	public function add_dashboard_scripts() {
		$screen = get_current_screen();

		if ( $screen->id != 'dashboard' ) {
			return;
		}

		$textdomain = ML()->textdomain;

		wp_enqueue_style( 'jqcomments-style' , WooCommerce_MercadoLivre::get_plugin_url( '/assets/css/jquery.comment.min.css' ) );
		wp_enqueue_script( 'jqcomments-script' , WooCommerce_MercadoLivre::get_plugin_url( '/assets/js/jquery.comment.js' ) , array( 'jquery' ) , '1.0.0' );
		wp_enqueue_script( 'ml-comments-script' , WooCommerce_MercadoLivre::get_plugin_url( '/assets/js/ml.comments.js' ) , array( 'jqcomments-script' ) , '2.1.0' );
		
		$nonce = wp_create_nonce( 'ml-comments-action' );
		$ajax_url = admin_url( 'admin-ajax.php' );
		
		wp_localize_script( 'ml-comments-script' , 'obj' , array(
			'get_url'             => add_query_arg( array( 'action' => 'get_ml_comments' , 'security' => $nonce ) , $ajax_url ),
			'post_url'            => add_query_arg( array( 'action' => 'post_ml_comment' , 'security' => $nonce ) , $ajax_url ),
			'delete_url'          => add_query_arg( array( 'action' => 'delete_ml_comment' , 'security' => $nonce ) , $ajax_url ),
			'comment_placeholder' => __( 'Aswer this question' , $textdomain ),
			'send_text'           => __( 'Send' , $textdomain ),
			'reply_text'          => __( 'Reply' , $textdomain ),
			'delete_text'         => __( 'Delete' , $textdomain )
		));
	}

	/**
	 * Include script on the post/edit product pages
	 *
	 * @script( include_in: "admin" )
	 */
	public function add_product_scripts() {
		$screen = get_current_screen();
		
		if ( $screen->id != 'product' ) {
			return;
		}

		$ml_product = null;

		if ( ! empty( $_GET['post'] ) ) {
			$ml_product = new ML_Product( intval( $_GET['post'] ) );

			if ( $ml_product->is_published() ) {
				return;
			}
		}

		$textdomain = ML()->textdomain;

		wp_enqueue_script( 'jquery-option-tree-script' , WooCommerce_MercadoLivre::get_plugin_url( '/assets/js/jquery.option.tree.js' ) , array( 'jquery' ) );
		wp_enqueue_script( 'ml-categories-script' , WooCommerce_MercadoLivre::get_plugin_url( '/assets/js/ml.categories.tree.js' ) , array( 'jquery-option-tree-script' ) , '1.4.1' );
		
		$array_fields = array(
			'url'                     => add_query_arg( array( 'action' => 'get_ml_subcategories' , 'security' => wp_create_nonce( 'ml-product-action' ) ) , admin_url( 'admin-ajax.php' ) ),
			'check_variation_url'     => add_query_arg( array( 'action' => 'check_variation' , 'security' => wp_create_nonce( 'ml-product-action' ) ) , admin_url( 'admin-ajax.php' ) ),
			'get_shipping_modes_url'  => add_query_arg( array( 'action' => 'get_ml_shipping_modes' , 'security' => wp_create_nonce( 'ml-shipping-action' ) ) , admin_url( 'admin-ajax.php' ) ),
			'image_url'               => WooCommerce_MercadoLivre::get_plugin_url( '/assets/img/ajax-loader.gif' ),
			'first_level_label'       => __( 'Category' , $textdomain ),
			'level_label'             => __( 'Subcategory' , $textdomain ),
			'pre_selected'            => isset( $ml_product->category_id ) ? wp_list_pluck( ML_Category::get_category_path( $ml_product->category_id ) , 'id' ) : null
		);

		wp_localize_script( 'ml-categories-script' , 'obj' , $array_fields );
	}

	/**
	 * Add ML tab
	 *
	 * @filter( hook: "woocommerce_product_data_tabs" )
	 */
	public function add_product_tab( $tabs ) {
		$tabs['ml_product'] = array(
			'label'  => __( 'MercadoLivre', ML()->textdomain ),
			'target' => 'ml_product_data',
			'class'  => array( 'hide_if_external', 'hide_if_downloadable', 'hide_if_virtual' , 'hide_if_grouped' )
		);

		return $tabs;
	}

	/**
	 * Render main ML product metabox
	 *
	 * @action( hook: "woocommerce_product_data_panels" )
	 */
	public function print_product_fields() {
		include_once( 'views/html-product-tab.php' );
	}

	/**
	 * Link to view product at ML
	 *
	 * @filter( hook: "get_sample_permalink_html" )
	 */
	public function add_link_to_product( $return , $id , $new_title , $new_slug ) {
		global $post;

		if ( $post->post_type == 'product' ) {
			$ml_product = new ML_Product( $post );

			if ( $ml_product->is_published() ) {
				$text = __( 'View at ML' , ML()->textdomain );
				$return .= "<span id=\"view-ml-post-btn\"><a href=\"{$ml_product->permalink}\" target=\"_blank\" class=\"button button-small\">{$text}</a></span>";
			}
		}
		
		return $return;
	}

	/**
	 * Save ML product data
	 *
	 * @action( hook: "woocommerce_process_product_meta_variable" )
	 * @action( hook: "woocommerce_process_product_meta_simple" )
	 */
	public function save_product_metaboxes_data( $post_id ) {
		$ml_product = new ML_Product( $post_id );

		$ml_product->video_id = $_POST['ml_video_id'];

		if ( ! $ml_product->is_published() ) {
			// Fields for a new product
			$ml_product->category_id     = $_POST['ml_category_id'];
			$ml_product->listing_type_id = $_POST['ml_listing_type_id'];

			if ( ! empty( $_POST['ml_official_store_id'] ) ) {
				$ml_product->official_store_id = $_POST['ml_official_store_id'];
			}
		}

		if ( $ml_product->can_update_special_fields() ) {
			// Fields that can only be updated when the product has no sales
			$ml_product->title        = sanitize_text_field( $_POST['ml_title'] );
			//$ml_product->buying_mode  = $_POST['ml_buying_mode'];
			//$ml_product->condition    = $_POST['ml_condition'];
			$ml_product->warranty     = $_POST['ml_warranty'];

			if ( isset( $_POST['ml_shipping_mode'] ) && ( $_POST['ml_shipping_mode'] == 'custom' ) ) {
				// Custom Shipping
				$costs = array();
				for ( $i = 0 ; $i < 10 ; $i++ ) {
					if ( empty( $_POST['ml_shipment_data'][ $i ]['description'] ) || empty( $_POST['ml_shipment_data'][ $i ]['cost'] ) ) {
						continue;
					}

					$costs[] = array(
						'description'  => sanitize_text_field( $_POST['ml_shipment_data'][ $i ]['description'] ),
						'cost'         => floatval( $_POST['ml_shipment_data'][ $i ]['cost'] )
					);
				}

				$ml_product->shipping_mode           = 'custom';
				$ml_product->shipment_costs          = $costs;
				$ml_product->shipment_local_pickup   = ( $_POST['ml_shipment_local_pickup'] == 'yes' );
				if ( isset( $_POST['ml_shipment_free_shipping'] ) ) {
					$ml_product->shipment_free_shipping   = ( $_POST['ml_shipment_free_shipping'] == 'yes' );
				} else {
					$ml_product->shipment_free_shipping  = false;
				}
			}
		}

		if ( ! $ml_product->is_published() || ! $ml_product->is_variable() ) {
			$ml_product->price = sanitize_text_field( $_POST['ml_price'] );
		}

		if ( $ml_product->is_variable() ) {
			// Set variations
			$number_of_variations = count( $_POST['ml_variations']['child'] );
			$attributes = array_diff( array_keys( $_POST['ml_variations'] ) , array( 'child' , 'price' ) );

			for ( $position = 0 ; $position < $number_of_variations ; $position++ ) {
				$child_product = new ML_Product( intval( $_POST['ml_variations']['child'][ $position ] ) );
				
				if ( ! $child_product->is_published() ) {
					$attribute_combinations = array();

					foreach ( $attributes as $attribute ) {
						if ( ! empty( $_POST['ml_variations'][ $attribute ][ $position ] ) ) {
							$attribute_combinations[] = array(
								'id'       => strval( $attribute ),
								'value_id' => strval( $_POST['ml_variations'][ $attribute ][ $position ] )
							);
						}
					}

					$child_product->attribute_combinations = $attribute_combinations;
				}
				
				$child_product->price = sanitize_text_field( $_POST['ml_variations']['price'][ $position ] );
			}
		}

		if ( $ml_product->is_published() && ( ( ML()->ml_auto_update == 'yes' ) || ( isset( $_POST['ml_publish'] ) && ( $_POST['ml_publish'] == 'yes' ) ) ) ) {
			// Verify aditional changes and update
			try {
				// Update the product
				$new_ml_product = $ml_product->update();

				if ( $_POST['ml_listing_type_id'] != $new_ml_product->listing_type_id ) {
					$ml_product->update_listing_type( $_POST['ml_listing_type_id'] );
				}

				if ( ! empty( $ml_product->get_wc_product()->get_post_data()->post_content ) ) {
					$ml_product->update_description();
				}

				// Enqueue a message for the user
				ML()->add_notice( sprintf( '%s: <a href="%s" target="_blank">%s</a>' , __( 'The product has been updated successfully on MercadoLivre' , ML()->textdomain ) , $new_ml_product->permalink , $new_ml_product->permalink ) , 'success' );
			} catch ( ML_Exception $e ) {
				ML()->add_notice( sprintf( '%s: %s' , __( 'The product could not be updated on MercadoLivre' , ML()->textdomain ) , $e->getMessage() ) , 'error' );
			}
		} else if ( ! $ml_product->is_published() && ( ( ML()->ml_auto_export == 'yes' ) || ( isset( $_POST['ml_publish'] ) && ( $_POST['ml_publish'] == 'yes' ) ) ) ) {
			// Post
			try {
				// Post the product
				$new_ml_product = $ml_product->post();
				// Enqueue a message for the user
				ML()->add_notice( sprintf( '%s: <a href="%s" target="_blank">%s</a>' , __( 'The product was successfully published on MercadoLivre' , ML()->textdomain ) , $new_ml_product->permalink , $new_ml_product->permalink ) , 'success' );
			} catch ( ML_Exception $e ) {
				ML()->add_notice( sprintf( '%s: %s' , __( 'The product could not be posted on MercadoLivre' , ML()->textdomain ) , $e->getMessage() ) , 'error' );
			}
		}
	}
}

endif;