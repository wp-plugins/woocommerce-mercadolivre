<?php
/**
 * 
 * @package  WP Plugins MGM Framework
 * @category Core
 * @author   Carlos Cardoso Dias
 *
 * License: -
 **/

/**
 * Anti cheating code
 **/
defined( 'ABSPATH' ) or die( 'A Ag&ecirc;ncia Magma n&atilde;o deixa voc&ecirc; trapacear ;)' );

if ( ! function_exists( 'get_called_class' ) ):

function get_called_class() {
	foreach ( debug_backtrace() as $execution ) {
		if ( isset( $execution['function'] ) && ( ( $execution['function'] == 'call_user_func_array' ) || ( $execution['function'] == 'call_user_func' ) ) ) {
			return $execution['args'][0][0];
		}
	}

	return false;
}

endif;

if ( ! class_exists( 'MGM_Singleton' ) ) :
abstract class MGM_Singleton {

	private static $instances = array();

	final public static function get_instance( $called_class = '' ) {
		if ( empty( $called_class ) ) {
			$called_class = get_called_class();
		}

		if ( ! isset( self::$instances[ $called_class ] ) ) {
			self::$instances[ $called_class ] = new $called_class();
			self::$instances[ $called_class ]->after_init();
		}

		return self::$instances[ $called_class ];
	}

	public function init() {
		//Init object when instantiated
	}

	public function after_init() {
		// Execute after construct the object
	}

	protected function __construct() {
		//Thou shalt not construct
		$this->init();
	}

	protected function __clone() {
		//Thou shalt not clone
	}

	final public function __wakeup() {
		throw new Exception( 'Cannot unserialize singleton' );
	}
}
endif;
