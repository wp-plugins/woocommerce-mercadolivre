<?php
/**
 * Custom Shipment View
 *
 * @author Carlos Cardoso Dias
 *
 */

/**
 * Anti cheating code
 */
defined( 'ABSPATH' ) or die( 'A Ag&ecirc;ncia Magma n&atilde;o deixa voc&ecirc; trapacear ;)' );

/**
 * $ml_product
 *
 */
?>
<div id="ml_shipment_content">
	<p><span><?php _e( 'Shipment Details' , ML()->textdomain ); ?></span></p>
	<table id="ml_custom_shipment_table" class="widefat">
		<thead>
			<th><?php _e( 'Description' , ML()->textdomain ); ?></th>
			<th><?php _e( 'Cost' , ML()->textdomain ); ?></th>
		</thead>
		<tbody>
			<?php $costs = $ml_product->shipment_costs; ?>
			<?php for ( $i = 0 ; $i < 10 ; $i++ ) : ?>
				<tr>
					<td>
						<input name="ml_shipment_data[<?php echo $i; ?>][description]" type="text" value="<?php echo isset( $costs[ $i ]['description'] ) ? $costs[ $i ]['description'] : '' ; ?>" <?php echo $ml_product->can_update_special_fields() ? '' : 'disabled="disabled"'; ?>>
					</td>
					<td>
						<input name="ml_shipment_data[<?php echo $i; ?>][cost]" type="text" class="wc_input_price" value="<?php echo isset( $costs[ $i ]['cost'] ) ? $costs[ $i ]['cost'] : '' ; ?>" <?php echo $ml_product->can_update_special_fields() ? '' : 'disabled="disabled"'; ?>>
					</td>
				</tr>
			<?php endfor; ?>
		</tbody>
	</table>
	<?php
	woocommerce_wp_checkbox( array(
		'id'                => 'ml_shipment_local_pickup',
		'label'             => __( 'Local pick up' , ML()->textdomain ),
		'description'       => __( 'Check if this product can be taken on site' , ML()->textdomain ),
		'desc_tip'          => true,
		'value'             => ( isset( $ml_product->shipment_local_pickup ) && $ml_product->shipment_local_pickup ) ? 'yes' : 'no',
		'custom_attributes' => $ml_product->can_update_special_fields() ? array() : array( 'disabled' => 'disabled' )
	) );

	if ( ! in_array( ML()->ml_site , array( 'MLA' , 'MLB' ) ) ) {
		woocommerce_wp_checkbox( array(
			'id'                => 'ml_shipment_free_shipping',
			'label'             => __( 'Free Shipping' , ML()->textdomain ),
			'description'       => __( 'Check if this product can be delivered for free' , ML()->textdomain ),
			'desc_tip'          => true,
			'value'             => ( isset( $ml_product->shipment_free_shipping ) && $ml_product->shipment_free_shipping ) ? 'yes' : 'no',
			'custom_attributes' => $ml_product->can_update_special_fields() ? array() : array( 'disabled' => 'disabled' )
		) );
	}
	?>
</div>