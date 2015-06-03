<?php
/**
 * MercadoLivre Notices
 * 
 * @author Carlos Cardoso Dias
 *
 **/

/**
 * Anti cheating code
 */
defined( 'ABSPATH' ) or die( 'A Ag&ecirc;ncia Magma n&atilde;o deixa voc&ecirc; trapacear ;)' );

if ( ! class_exists( 'ML_Notices' ) ) :

final class ML_Notices extends MGM_Plugin {

	/**
	 * Print an admin notice if WooCommerce is missing
	 *
	 * @action( hook: "admin_notices" , must_add: "check_woocommerce_missing" )
	 */
	public function woocommerce_missing_notice() {
		MGM_Main_Plugin::print_error_notice( __( 'Please make sure that WooCommerce is installed and active for this plugin to work properly' , ML()->textdomain ) );
	}

	/**
	 * Check if WooCommerce isn't active
	 */
	public function check_woocommerce_missing() {
		return ( ! MGM_Main_Plugin::check_active_plugin( 'woocommerce/woocommerce.php' ) );
	}

	/**
	 * Print an admin notice to advert the user to configure the plugin
	 *
	 * @action( hook: "admin_notices" , must_add: "check_ml_isnt_logged" )
	 */
	public function configure_plugin_notice() {
		WooCommerce_MercadoLivre::print_warning_notice( sprintf( __( 'The MercadoLivre is not already set in your store, click %shere%s to configure the plugin' , ML()->textdomain ) , '<a href="' . WooCommerce_MercadoLivre::get_admin_settings_page_url() . '">' , '</a>' ) );
	}

	/**
	 * Check if the plugin isn't cofigured yet
	 */
	public function check_ml_isnt_logged() {
		return ( ! ML()->ml_is_logged() );
	}

	/**
	 * Print admin notices related with API operations
	 *
	 * @action( hook: "admin_notices" , must_add: "has_messages" )
	 */
	public function ml_operations_notices() {
		if ( ! empty( ML()->ml_error_message ) ) {
			MGM_Main_Plugin::print_error_notice( ML()->ml_error_message );
			unset( ML()->ml_error_message );
		}

		if ( ! empty( ML()->ml_success_message ) ) {
			MGM_Main_Plugin::print_success_notice( ML()->ml_success_message );
			unset( ML()->ml_success_message );
		}

		if ( ! empty( ML()->ml_warning_message ) ) {
			MGM_Main_Plugin::print_warning_notice( ML()->ml_warning_message );
			unset( ML()->ml_warning_message );
		}
	}

	/**
	 * Check if the API has messages to display
	 */
	public function has_messages() {
		return ( ML()->ml_is_logged() && ( ! empty( ML()->ml_error_message ) || ! empty( ML()->ml_success_message ) || ! empty( ML()->ml_warning_message ) ) );
	}

	/**
	 * Display the log-out message
	 *
	 * @action( hook: "admin_notices" , must_add: "check_logout" )
	 */
	public function logout_notice() {
		MGM_Main_Plugin::print_success_notice( __( 'The MercadoLivre is no longer integrated with your store' , ML()->textdomain ) );
	}

	/**
	 * Check if the user logged out
	 */
	public function check_logout() {
		return ( isset( $_GET['ml-logout'] ) && ( $_GET['ml-logout'] == 1 ) );
	}
}

endif;