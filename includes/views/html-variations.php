<?php
/**
 * Variations View
 *
 * @author Carlos Cardoso Dias
 *
 */

/**
 * Anti cheating code
 */
defined( 'ABSPATH' ) or die( 'A Ag&ecirc;ncia Magma n&atilde;o deixa voc&ecirc; trapacear ;)' );

/**
 * receive $ml_product
 * receive $variations
 */
?>
<div id="ml_variations_content">
	<p><span><?php _e( 'Variations' , ML()->textdomain ); ?></span></p>
	<table id="ml_variations_table" class="widefat">
		<thead>
			<?php foreach ( $variations as $variation ) : ?>
				<?php if ( isset( $variation->tags->allow_variations ) && ( $variation->tags->allow_variations == 1 ) ) : ?>
					<th><?php echo $variation->name . ( ( isset( $variation->tags->required ) && ( $variation->tags->required == 1 ) ) ? '*' : '' ); ?></th>
				<?php endif; ?>
			<?php endforeach; ?>
			<th><?php _e( 'Child Product*' , ML()->textdomain ); ?></th>
			<th><?php _e( 'Price' , ML()->textdomain ); ?></th>
			<th></th>
		</thead>
		<tbody>
			<?php 
			if ( $ml_product->is_published() && $ml_product->is_variable() ) {
				foreach ( $ml_product->get_wc_product()->get_children() as $child_id ) {
					$child_product = new ML_Product( $child_id );
					
					if ( empty( $child_product->attribute_combinations ) ) {
						continue;
					}

					$saved_variation = wp_list_pluck( $child_product->attribute_combinations , 'value_id' , 'id' );

					include( 'html-variation-line.php' );
				}
			} else {
				include( 'html-variation-line.php' );
			}
			?>
		</tbody>
	</table>
	<p class="button-add-variation">
		<a id="add_variation" href="#" class="button-secondary alignright"><?php _e( 'Add variation' , $textdomain ); ?></a>
	</p>
	<script type="text/javascript">
		jQuery(document).ready(function($){
			$('select[name^="ml_variations"]').select2({placeholder: "<?php _e( 'Not Required' , ML()->textdomain ); ?>"});
			
			/*
			$('.tip_variation').tipTip({
				'attribute' : 'data-tip',
				'fadeIn' : 50,
				'fadeOut' : 50,
				'delay' : 200
			});*/

			$('a.delete_ml_variation_button').click(function(){
				$(this).parent().parent().remove();
				return false;
			});
			
			$('#add_variation').click(function(){
				$('#ml_variations_table').block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});

				//$('#ml_category_id').parent().append('<img src="' + obj.image_url + '" class="check_variation">');
				$.getJSON("<?php echo add_query_arg( array( 'action' => 'add_variation_line' , 'security' => wp_create_nonce( 'ml-variation-line-action' ) , 'product_id' => isset( $_GET['product_id'] ) ? $_GET['product_id'] : $ml_product->get_wc_product()->id , 'category' => isset( $_GET['category'] ) ? $_GET['category'] : $ml_product->category_id ) , admin_url( 'admin-ajax.php' ) ); ?>", function(variation_line){
					$('#ml_variations_table tbody').append(variation_line);
					$('#ml_variations_table tbody tr:last').find('select[name^="ml_variations"]').select2({placeholder: "<?php _e( 'Not Required' , ML()->textdomain ); ?>"});
					$('a.delete_ml_variation_button:last').click(function(){
						$(this).parent().parent().remove();
						return false;
					});
					$('#ml_variations_table').unblock();
				});
				return false;
			});
		});
	</script>
</div>
<!--img class="tip_variation help_tip" data-tip="<?php //echo ( ( isset( $variation->tags->required ) && ( $variation->tags->required == 1 ) )? __( 'Required' , ML()->textdomain ) : __( 'Not Required' , ML()->textdomain ) ); ?>" src="<?php //echo esc_url( WC()->plugin_url() ); ?>/assets/images/help.png" height="16" width="16" /-->