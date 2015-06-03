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

if ( ! class_exists( 'MGM_Main_Plugin' ) ) :

require_once( 'class-mgm-plugin.php' );

abstract class MGM_Main_Plugin extends MGM_Plugin {

	public $textdomain = '';

	protected $options_slug = '';
	protected $options      = null;

	public static function register( $args = array() ) {
		$default = array( 'called_class' => '' , 'start_plugin_action' => 'plugins_loaded' );
		$args = wp_parse_args( $args , $default );

		if ( empty( $args['called_class'] ) ) {
			$args['called_class'] = get_called_class();
		}

		$reflection = new ReflectionClass( $args['called_class'] );
		
		register_activation_hook( $reflection->getFileName() , array( $args['called_class'] , 'activate' ) );
		register_deactivation_hook( $reflection->getFileName() , array( $args['called_class'] , 'deactivate' ) );

		add_action( $args['start_plugin_action'] , array( $args['called_class'] , 'get_instance' ) );
	}

	public static function activate() {

	}

	public static function deactivate() {

	}

	public static function check_active_plugin( $plugin_file = 'woocommerce/woocommerce.php' ) {
		return in_array( $plugin_file , apply_filters( 'active_plugins' , get_option( 'active_plugins' ) ) );
	}

	protected static function print_admin_notice( $message , $class ) {
		echo sprintf('<div class="%s"><p>%s</p></div>', $class , $message );
	}

	public static function print_success_notice( $message ) {
		self::print_admin_notice( $message , 'updated' );
	}

	public static function print_error_notice( $message ) {
		self::print_admin_notice( $message , 'error' );
	}

	public static function print_warning_notice( $message ) {
		self::print_admin_notice( $message , 'update-nag' );
	}

	protected function __construct() {
		// Set textdomain
		$this->textdomain = $this->make_slug();

		// Load plugin textdomain
		add_action( 'init' , array( $this , 'load_textdomain' ) );

		parent::__construct();
	}

	public function load_textdomain() {
		$reflection = new ReflectionClass( $this );
		$locale = apply_filters( 'plugin_locale' , get_locale() , $this->textdomain );
		load_textdomain( $this->textdomain , sprintf( '%s%s/%s-%s.mo' , trailingslashit( WP_LANG_DIR ) , $this->textdomain , $this->textdomain , $locale ) );
		load_plugin_textdomain( $this->textdomain , false , dirname( plugin_basename( $reflection->getFileName() ) ) . '/languages/' );
	}

	public function make_slug( $string = null ) {
		if ( empty( $string ) ) {
			$string = get_class( $this );
		}

		return str_replace( '_' , '-' , strtolower( $string ) );		
	}

	public function __get( $name ) {
		// Read options
		if ( empty( $this->options_slug ) ) {
			return null;
		}

		if ( empty( $this->options ) ) {
			$this->options = get_option( $this->options_slug , array() );
		}

		if ( isset( $this->options[ $name ] ) ) {
			return $this->options[ $name ];
		}

		return null;
	}

	public function __set( $name , $value ) {
		// Write options
		if ( empty( $this->options_slug ) ) {
			return;
		}

		if ( empty( $this->options ) ) {
			$this->options = get_option( $this->options_slug , array() );
		}

		$this->options[ $name ] = $value;

		update_option( $this->options_slug , $this->options );
	}

	public function __isset( $name ) {
		// Verify if option is set
		if ( empty( $this->options_slug ) ) {
			return false;
		}

		if ( empty( $this->options ) ) {
			$this->options = get_option( $this->options_slug , array() );
		}

		return isset( $this->options[ $name ] );
	}

	public function __unset( $name ) {
		if ( empty( $this->options_slug ) ) {
			return;
		}

		if ( empty( $this->options ) ) {
			$this->options = get_option( $this->options_slug , array() );
		}

		unset( $this->options[ $name ] );

		update_option( $this->options_slug , $this->options );
	}
}
endif;