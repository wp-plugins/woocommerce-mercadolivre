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

if ( ! class_exists( 'MGM_Annotations' ) ) :

require_once( 'class-mgm-singleton.php' );

class MGM_Annotations extends MGM_Singleton {
	
	// action|filter|ajax|script|style
	public function process_annotations( $object ) {
		$reflection = new ReflectionClass( $object );

		foreach ( $reflection->getMethods() as $method ) {
			$annotations = $method->getDocComment();
			
			if ( ! empty( $annotations ) ) {

				preg_match_all( "/@(action|filter|ajax|script|style)\s*\((.*?)\)/" , $annotations , $output );

				foreach ( $output[0] as $position => $match ) {

					call_user_func( array( $this , 'perform_' . $output[1][$position] ) , $this->process_parameters( $output[2][$position] , $output[1][$position] ) , $method , $object );

				}
				
			}
		}
	}

	public function process_parameters( $string_parameter , $action ) {
		if ( empty( $string_parameter ) ) {
			return array();
		}

		preg_match_all( "/(?:(\S+)\s*(?:=>?|:))?\s*(('|\")(.+?)\\3|\d+(?:.\d+)?|(?:true|false))/" , $string_parameter , $output );
		
		$values_array = array_map( array( $this , 'get_primary_value' ) , $output[4] , $output[2] );

		$not_used_keys = array_diff( call_user_func( array( $this , 'get_' . $action . '_parameters' ) ) , array_filter( $output[1] ) );

		$keys_array = array();
		foreach ($output[1] as $value) {
			$keys_array[] = empty( $value ) ? array_shift( $not_used_keys ) : $value;
		}

		return array_combine( $keys_array , array_values( $values_array ) );
	}

	public function get_primary_value( $primary_value , $secondary_value ) {
		if ( empty( $primary_value ) ) {
			return $secondary_value;
		}

		return $primary_value;
	}

	public function convert_boolean_value( $string_value , $object ) {
		if ( ! is_string( $string_value ) ) {
			return $string_value;
		}

		switch ( $string_value ) {
			case 'true':
				return true;
				break;

			case 'false':
				return false;
				break;
			default:
				return call_user_func( array( $object , $string_value ) );
				break;
		}
	}

	public function get_action_parameters() {
		return array( 'hook' , 'priority' , 'arguments' , 'must_add' );
	}

	public function perform_action( $parameters , $method , $object ) {
		$default = array_combine( $this->get_action_parameters() , array_values( array( $method->getName() , 10 , $method->getNumberOfRequiredParameters() , true ) ) );
		
		$values = wp_parse_args( $parameters , $default );

		if ( ! $this->convert_boolean_value( $values['must_add'] , $object ) ) {
			return;
		}

		add_action( $values['hook'] , array( $object , $method->getName() ) , $values['priority'] , $values['arguments'] );
	}

	public function get_filter_parameters() {
		return array( 'hook' , 'priority' , 'arguments' , 'must_add' );
	}

	public function perform_filter( $parameters , $method , $object ) {
		$default = array_combine( $this->get_filter_parameters() , array_values( array( $method->getName() , 10 , $method->getNumberOfRequiredParameters() , true ) ) );
		
		$values = wp_parse_args( $parameters , $default );
		
		if ( ! $this->convert_boolean_value( $values['must_add'] , $object ) ) {
			return;
		}

		add_action( $values['hook'] , array( $object , $method->getName() ) , $values['priority'] , $values['arguments'] );
	}

	public function get_ajax_parameters() {
		return array( 'action' , 'only_logged' , 'priority' , 'arguments' , 'must_add' );
	}

	public function perform_ajax( $parameters , $method , $object ) {
		$default = array_combine( $this->get_ajax_parameters() , array_values( array( $method->getName() , true , 10 , $method->getNumberOfRequiredParameters() , defined( 'DOING_AJAX' ) ) ) );

		$values = wp_parse_args( $parameters , $default );

		if ( ! $this->convert_boolean_value( $values['must_add'] , $object ) ) {
			return;
		}

		if ( ! $this->convert_boolean_value( $values['only_logged'] , $object ) ) {
			add_action( 'wp_ajax_nopriv_' . $values['action'] , array( $object , $method->getName() ) , $values['priority'] , $values['arguments'] );
		}

		add_action( 'wp_ajax_' . $values['action'] , array( $object , $method->getName() ) , $values['priority'] , $values['arguments'] );
	}

	public function get_script_parameters() {
		return array( 'include_in' , 'priority' , 'arguments' , 'must_add' );
	}

	public function perform_script( $parameters , $method , $object ) {
		$default = array_combine( $this->get_script_parameters() , array_values( array( 'admin' , 10 , $method->getNumberOfRequiredParameters() , true ) ) );

		$values = wp_parse_args( $parameters , $default );

		if ( ! $this->convert_boolean_value( $values['must_add'] , $object ) ) {
			return;
		}

		$hooks = array_map( 'trim' , explode( ',' , $values['include_in'] ) );
		foreach ( $hooks as $hook ) {
			if ( in_array( $hook , array( 'login' , 'admin' , 'wp' ) ) ) {
				add_action( $hook . '_enqueue_scripts' , array( $object , $method->getName() ) , $values['priority'] , $values['arguments'] );
			}
		}
	}

	public function get_style_parameters() {
		return array( 'include_in' , 'priority' , 'arguments' , 'must_add' );
	}

	public function perform_style( $parameters , $method , $object ) {
		$this->perform_script( $parameters , $method , $object );
	}
}
endif;
