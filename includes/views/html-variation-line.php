<?php
/**
 * Variation Line View
 *
 * @author Carlos Cardoso Dias
 *
 */

/**
 * Anti cheating code
 */
defined( 'ABSPATH' ) or die( 'A Ag&ecirc;ncia Magma n&atilde;o deixa voc&ecirc; trapacear ;)' );

/**
 * receive $saved_variation, $variations, $ml_product
 */
?>
<tr>
	<?php foreach ( $variations as $variation ) : ?>
		<?php if ( isset( $variation->tags->allow_variations ) && ( $variation->tags->allow_variations == 1 ) ) : ?>
			<td>
				<select name="ml_variations[<?php echo $variation->id; ?>][]" class="select" style="width: 90%;" <?php echo ( isset( $saved_variation ) && $child_product->is_published() ) ? 'disabled' : ''; ?>>
					<?php if ( ! isset( $variation->tags->required ) || ( $variation->tags->required != 1 ) ) : ?>
						<option value=""></option>
					<?php endif; ?>
				<?php foreach ( $variation->values as $value ) : ?>
					<option value="<?php echo $value->id; ?>" <?php echo ( isset( $saved_variation ) && ( $value->id == $saved_variation[ $variation->id ] ) ) ? 'selected' : ''; ?>><?php echo $value->name; ?></option>
				<?php endforeach; ?>
				</select>
			</td>
		<?php endif; ?>
	<?php endforeach; ?>
	<td>
		<?php if ( isset( $saved_variation ) && $child_product->is_published() ) : ?>
			<input name="ml_variations[child][]" type="hidden" value="<?php echo $child_product->get_wc_product()->variation_id; ?>" />
		<?php endif; ?>
		<select name="ml_variations[child][]" class="child_select" style="width: 90%;" <?php echo ( isset( $saved_variation ) && $child_product->is_published() ) ? 'disabled' : ''; ?>>
			<?php
			$args = array(
				'post_parent' => $_GET['product_id'],
				'post_type'   => 'product_variation',
				'orderby'     => 'menu_order',
				'order'       => 'ASC',
				'fields'      => 'ids',
				'post_status' => 'publish',
				'numberposts' => -1
			);
			foreach ( get_posts( $args ) as $variation_id ) : ?>
				<option value="<?php echo $variation_id; ?>" <?php echo ( isset( $saved_variation ) && ( $variation_id == $child_product->get_wc_product()->variation_id ) ) ? 'selected' : ''; ?>><?php echo '#' . $variation_id; ?></option>
			<?php endforeach; ?>
		</select>
	</td>
	<td>
		<input type="text" class="wc_input_price" name="ml_variations[price][]" style="float:none; width: 90px;" value="<?php echo ( isset( $saved_variation ) && $child_product->is_published() ) ? $child_product->price : '' ; ?>">
	</td>
	<td>
		<?php if ( ! ( isset( $saved_variation ) && $child_product->is_published() ) ) : ?>
			<a href="#" class="button-secondary delete_ml_variation_button"><?php _e( 'Delete' , ML()->textdomain ); ?></a>
		<?php endif; ?>
	</td>
</tr>