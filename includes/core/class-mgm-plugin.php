<?php
/**
 * 
 * @package  MGM Framework
 * @category Core
 * @author   Carlos Cardoso Dias
 *
 * License: -
 **/

/**
 * Anti cheating code
 **/
defined( 'ABSPATH' ) or die( 'A Ag&ecirc;ncia Magma n&atilde;o deixa voc&ecirc; trapacear ;)' );

if ( ! class_exists( 'MGM_Plugin' ) ) :

require_once( 'class-mgm-singleton.php' );
require_once( 'class-mgm-annotations.php' );

abstract class MGM_Plugin extends MGM_Singleton {

	public function after_init() {
		MGM_Annotations::get_instance( 'MGM_Annotations' )->process_annotations( $this );
	}
}
endif;
