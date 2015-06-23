<?php
/**
 * Product Column View
 *
 * @author Carlos Cardoso Dias
 *
 */

/**
 * Anti cheating code
 */
defined( 'ABSPATH' ) or die( 'A Ag&ecirc;ncia Magma n&atilde;o deixa voc&ecirc; trapacear ;)' );

$ml_product = new ML_Product( $post_id );
?>
<?php if ( $ml_product->is_published() ) : ?>
	<a href="<?php echo add_query_arg( array( 'action' => 'change_status_ml_product' , 'post_id' => $post_id , 'status' => 'closed' , 'security' => wp_create_nonce( 'ml-change-status-action' ) ) , admin_url( 'admin-ajax.php' ) ); ?>" class="button tips ml_delete ml_button" data-tip="<?php _e( 'Delete' , ML()->textdomain ); ?>"></a>
<?php endif; ?>
<?php if ( isset( $ml_product->status ) ) : ?>
	<?php if ( $ml_product->status == 'closed' ) : ?>
		<a href="<?php echo add_query_arg( array( 'action' => 'relist_ml_product' , 'post_id' => $post_id , 'security' => wp_create_nonce( 'ml-relist-product-action' ) ) , admin_url( 'admin-ajax.php' ) ); ?>" class="button tips ml_relist ml_button" data-tip="<?php _e( 'Relist' , ML()->textdomain ); ?>"></a>
	<?php elseif ( $ml_product->status == 'active' ) : ?>
		<a href="<?php echo add_query_arg( array( 'action' => 'change_status_ml_product' , 'post_id' => $post_id , 'status' => 'paused' , 'security' => wp_create_nonce( 'ml-change-status-action' ) ) , admin_url( 'admin-ajax.php' ) ); ?>" class="button tips ml_pause ml_button" data-tip="<?php _e( 'Pause' , ML()->textdomain ); ?>"></a>
	<?php elseif ( $ml_product->status == 'paused' ) : ?>
		<a href="<?php echo add_query_arg( array( 'action' => 'change_status_ml_product' , 'post_id' => $post_id , 'status' => 'active' , 'security' => wp_create_nonce( 'ml-change-status-action' ) ) , admin_url( 'admin-ajax.php' ) ); ?>" class="button tips ml_activate ml_button" data-tip="<?php _e( 'Activate' , ML()->textdomain ); ?>"></a>
	<?php endif; ?>
<?php endif; ?>
<?php if ( isset( $ml_product->permalink ) ) : ?>
	<a href="<?php echo $ml_product->permalink; ?>" target="_blank" class="button tips ml_view ml_button" data-tip="<?php _e( 'View at ML' , ML()->textdomain ); ?>"></a>
<?php endif; ?>