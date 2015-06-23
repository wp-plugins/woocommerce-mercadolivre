<?php 
/**
 * Product Tab View
 *
 * @author Carlos Cardoso Dias
 *
 */

/**
 * Anti cheating code
 */
defined( 'ABSPATH' ) or die( 'A Ag&ecirc;ncia Magma n&atilde;o deixa voc&ecirc; trapacear ;)' );

global $thepostid;
$ml_product = new ML_Product( intval( $thepostid ) );
$textdomain = ML()->textdomain;

?>

<div id="ml_product_data" class="panel woocommerce_options_panel">
	<?php if ( ML()->ml_auto_export == 'no' ) : ?>
		<div class="options_group">
			<?php woocommerce_wp_checkbox( array(
				'id'           => 'ml_publish',
				'label'        => $ml_product->is_published() ? __( 'Update at ML' , $textdomain ) : __( 'Post at ML' , $textdomain ),
				'description'  => __( 'Check if this product should be published at ML' , $textdomain ),
				'desc_tip'     => true,
				'value'        => ( isset( $_POST['ml_publish'] ) || $ml_product->is_published() ) ? 'yes' : 'no'
			)); ?>
		</div>
	<?php endif; ?>
	<div class="options_group ml_required_fields">
		<p class="form-field ml_category_id_field">
			<label for="ml_category_id"><?php _e( 'Category' , $textdomain ); ?></label>
			<?php
			if ( $ml_product->is_published() ) {
				printf( '<a href="%s" target="_blank">%s</a>' , $ml_product->permalink , implode( ' > ' , wp_list_pluck( ML_Category::get_category_path( $ml_product->category_id ) , 'name' ) ) );
				$variations = ML_Category::get_category_variations( $ml_product->category_id );

				if ( ! empty( $variations ) ) {
					include_once( 'html-variations.php' );
				}
			} else {
				woocommerce_wp_hidden_input( array(
					'id'    => 'ml_category_id',
					'value' => $ml_product->category_id
				) );
			}
			?>
		</p>
	</div>
	<div class="options_group">
		<?php
		if (! empty( ML()->ml_official_stores ) ) {
			woocommerce_wp_select( array(
				'id'                => 'ml_official_store_id',
				'label'             => __( 'Official Store' , $textdomain ),
				'description'       => __( 'Determines which official store the product will be posted' , $textdomain ),
				'desc_tip'          => true,
				'options'           => ML()->ml_official_stores,
				'value'             => isset( $_POST['ml_official_store_id'] ) ? $_POST['ml_official_store_id'] : $ml_product->official_store_id,
				'custom_attributes' => $ml_product->is_published() ? array( 'disabled' => 'disabled' ) : array()
			) );
		}
		//Modifiable with conditions
		woocommerce_wp_select( array(
			'id'            => 'ml_listing_type_id',
			'label'         => __( 'Listing Type' , $textdomain ),
			'description'   => __( 'Determines how your product will be listed on MercadoLivre' , $textdomain ),
			'desc_tip'      => true,
			'options'       => wp_list_pluck( ML()->ml_communication->get_listing_types() , 'name' , 'id' ),
			'value'         => isset( $_POST['ml_listing_type_id'] ) ? $_POST['ml_listing_type_id'] : $ml_product->listing_type_id
		) );
		?>
	</div>
	<div class="options_group">
		<?php
		// Modifiable
		woocommerce_wp_text_input( array(
			'id'            => 'ml_video_id',
			'label'         => __( 'Video ID or URL' , $textdomain ),
			'description'   => __( 'Use the URL or the ID of the video on youtube' , $textdomain ),
			'desc_tip'      => true,
			'value'         => isset( $_POST['ml_video_id'] ) ? $_POST['ml_video_id'] : $ml_product->video_id
		));

		//depending on sold_quantity == 0 to modify
		woocommerce_wp_textarea_input( array(
			'id'                => 'ml_warranty',
			'label'             => __( 'Warranty' , $textdomain ),
			'description'       => __( 'Describe the product warranty for buyers on MercadoLivre' , $textdomain ),
			'desc_tip'          => true,
			'value'             => isset( $_POST['ml_warranty'] ) ? $_POST['ml_warranty'] : $ml_product->warranty,
			'custom_attributes' => $ml_product->can_update_special_fields() ? array() : array( 'disabled' => 'disabled' )
		) );
		?>
	</div>
	<div class="options_group shipment_fields">
		<?php
		woocommerce_wp_select( array(
			'id'                => 'ml_shipping_mode',
			'label'             => __( 'Shipping' , $textdomain ),
			'description'       => __( 'Determine how your product will be delivered for buyers on MercadoLivre' , $textdomain ),
			'desc_tip'          => true,
			'options'           => ML()->ml_communication->get_shipping_modes( empty( $ml_product->category_id ) ? null : $ml_product->category_id ),
			'value'             => isset( $_POST['ml_shipping_mode'] ) ? $_POST['ml_shipping_mode'] : $ml_product->shipping_mode,
			'custom_attributes' => $ml_product->can_update_special_fields() ? array() : array( 'disabled' => 'disabled' )
		) );

		if ( ! empty( $ml_product->shipping_mode ) && ( $ml_product->shipping_mode != 'not_specified' ) ) {
			$category_id = $ml_product->category_id;
			include_once( "html-{$ml_product->shipping_mode}-shipment.php" );
		}

		?>
	</div>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('#ml_shipping_mode').change(function() {
				// Remove previous shipping mode content
				$('#ml_shipment_content').remove();
				// append image
				$('.shipment_fields').append('<img src="' + "<?php echo WooCommerce_MercadoLivre::get_plugin_url( '/assets/img/ajax-loader.gif' ); ?>" + '" class="loading_shipment">');
				$.getJSON( "<?php echo add_query_arg( array( 'action' => 'get_ml_shipment' , 'security' => wp_create_nonce( 'ml-shipment-action' ) , 'product_id' => $thepostid ) , admin_url( 'admin-ajax.php' ) ); ?>" + '&shipping_mode=' + $(this).val() + '&category_id=' + $('#ml_category_id').val() , function( data ) {
					// remove image and insert content
					$('.loading_shipment').remove();
					$('.shipment_fields').append(data);
				});
			});
		});
	</script>
	<div class="options_group">
		<?php
		//depending on sold_quantity == 0 to modify
		woocommerce_wp_text_input( array(
			'id'                => 'ml_title',
			'label'             => __( 'Title' , $textdomain ),
			'description'       => __( 'Product title on MercadoLivre' , $textdomain ),
			'desc_tip'          => true,
			'value'             => isset( $_POST['ml_title'] ) ? $_POST['ml_title'] : $ml_product->title,
			'custom_attributes' => $ml_product->can_update_special_fields() ? array() : array( 'disabled' => 'disabled' )
		) );

		// Modifiable
		if ( ! $ml_product->is_variable() ) {
			woocommerce_wp_text_input( array(
				'id'            => 'ml_price',
				'label'         => __( 'Price' , $textdomain ),
				'wrapper_class' => 'show_if_simple',
				'description'   => __( 'Leave blank to use the same value of the site' , $textdomain ),
				'desc_tip'      => true,
				'data_type'     => 'price',
				'value'         => isset( $_POST['ml_price'] ) ? $_POST['ml_price'] : $ml_product->price
			) );
		}
		?>
	</div>
</div>