<?php
/**
 * MercadoLivre Exception
 * 
 * @author Carlos Cardoso Dias
 *
 **/

/**
 * Anti cheating code
 */
defined( 'ABSPATH' ) or die( 'A Ag&ecirc;ncia Magma n&atilde;o deixa voc&ecirc; trapacear ;)' );

if ( ! class_exists( 'ML_Exception' ) ) :

final class ML_Exception extends Exception {

	public function __construct( $message , $code = 0 ) {
		parent::__construct( is_object( $message ) ? $this->get_translated_message( $message ) : $message , $code );
	}

	private function get_translated_message( $obj ) {
		if ( empty( $obj->cause[0]->code ) ) {
			return print_r( $obj , true );
		}

		switch ( $obj->cause[0]->code ) {
			case 'item.start_time.invalid'                           : return __( 'Invalid start time.' , ML()->textdomain );
			case 'item.category_id.invalid'                          : return __( 'Invalid category.' , ML()->textdomain );
			case 'item.buying_mode.invalid'                          : return __( 'Invalid buying mode.' , ML()->textdomain );
			case 'item.available_quantity.invalid'                   : return __( 'Invalid item quantity.' , ML()->textdomain );
			case 'item.attributes.invalid'                           : return __( 'Invalid item attributes.' , ML()->textdomain );
			case 'item.variations.attribute_combinations.invalid'    : return __( 'Invalid attribute combinations.' , ML()->textdomain );
			case 'item.attributes.missing_required'                  : return __( 'Missing required attributes.' , ML()->textdomain );
			case 'item.listing_type_id.invalid'                      : return __( 'Invalid listing type.' , ML()->textdomain );
			case 'item.listing_type_id.requiresPictures'             : return __( 'Pictures is required.' , ML()->textdomain );
			case 'item.site_id.invalid'                              : return __( 'Invalid site.' , ML()->textdomain );
			case 'item.shipping.mode.invalid'                        : return __( 'Invalid shipping mode.' , ML()->textdomain );
			case 'item.description.max'                              : return __( 'The number of characters in description must be less then 50000 characters.' , ML()->textdomain );
			case 'item.pictures.max'                                 : return __( 'Number of images exceeded.' , ML()->textdomain );
			case 'item.pictures.variation.quantity'                  : return __( 'Every variation must have between 1 and 6 pictures.' , ML()->textdomain );
			case 'item.attributes.invalid_length'                    : return __( 'Invalid value length for an attribute.' , ML()->textdomain );
			case 'item.title.not_modifiable'                         : return __( 'Title is not modifiable.' , ML()->textdomain );
			case 'item.condition.not_modifiable'                     : return __( 'Condition is not modifiable.' , ML()->textdomain );
			case 'item.buying_mode.not_modifiable'                   : return __( 'Buying Mode is not modifiable.' , ML()->textdomain );
			case 'item.warranty.not_modifiable'                      : return __( 'Warranty is not modifiable.' , ML()->textdomain );
			case 'body.invalid'                                      : return __( 'Invalid item body.' , ML()->textdomain );
			case 'item.status.invalid'                               : return __( 'Item in status closed is not possible to change to status closed.' , ML()->textdomain );
			case 'item.variations.attribute_combinations.duplicated' : return __( 'Variation attribute is duplicated. Allowed unique atrributes combinations.' , ML()->textdomain );
			case 'file.not_found'                                    : return __( 'There is no file in request.' , ML()->textdomain );
		}

		return print_r( $obj , true );
	}
}

endif;