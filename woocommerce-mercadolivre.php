<?php
/**
 * Plugin Name: WooCommerce MercadoLivre
 * Plugin URI: http://www.woocommercemercadolivre.com.br/
 * Description: WooCommerce integration with MercadoLivre.
 * Version: 0.0.2
 * Author: agenciamagma, Carlos Cardoso Dias
 * Author URI: http://magmastore.com.br/
 * Text Domain: woocommerce-mercadolivre
 * Domain Path: /languages/
 * License: GPLv2
 *
 * @author Carlos Cardoso Dias
 */

/**
 * Anti cheating code
 */
defined( 'ABSPATH' ) or die( 'A Ag&ecirc;ncia Magma n&atilde;o deixa voc&ecirc; trapacear ;)' );

if ( ! class_exists( 'WooCommerce_MercadoLivre' ) ) :

require_once( 'includes/core/class-mgm-main-plugin.php' );

final class WooCommerce_MercadoLivre extends MGM_Main_Plugin {

	/**
	 * ML_Communication instance
	 */
	public $ml_communication = null;

	public static function get_admin_settings_page_url() {
		return get_admin_url( null , 'admin.php?page=wc-settings&tab=integration&section=ag-magma-ml-integration' );
	}

	/**
	 * Get plugin directory
	 */
	public static function get_plugin_url( $context = '' ) {
		return plugins_url( $context , __FILE__ );
	}
	
	/**
	 * Activation routine
	 */
	public static function activate() {
		add_rewrite_endpoint( 'ml-notifications' , EP_ROOT );
		flush_rewrite_rules();
	}

	/**
	 * Deactivation routine
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
	
	/**
	 * Initializes the plugin
	 */
	public function after_init() {
		$this->options_slug = 'woocommerce_ag-magma-ml-integration_settings';

		require_once( 'includes/class-ml-communication.php' );
		$this->ml_communication = ML_Communication::get_instance( 'ML_Communication' );

		require_once( 'includes/class-ml-product.php' );
		require_once( 'includes/class-ml-category.php' );
		
		if ( defined( 'DOING_AJAX' ) ) {
			// Include Ajax and dependencies
			require_once( 'includes/class-ml-questions.php' );
			require_once( 'includes/class-ml-ajax.php' );
			ML_Ajax::get_instance( 'ML_Ajax' );
		} else if ( $this->user_has_privileges() ) {
			// Check if the user just logged out
			$this->check_logout();
			// Verify if the user just logged in
			$this->check_login();

			// Include Notices
			require_once( 'includes/class-ml-notices.php' );
			ML_Notices::get_instance( 'ML_Notices' );

			if ( $this->ml_is_logged() ) {
				// Include Metaboxes
				require_once( 'includes/class-ml-metaboxes.php' );
				ML_Metaboxes::get_instance( 'ML_Metaboxes' );
			}
		}

		parent::after_init();
	}

	/**
	 * Add the ML notifications handler endpoint
	 *
	 * @action( hook: "init" , must_add: "ml_is_logged" )
	 */
	public function add_notifications_endpoint() {
		add_rewrite_endpoint( 'ml-notifications' , EP_ROOT );
	}

	/**
	 * Redirect to the notifications handler template
	 *
	 * @filter( hook: "template_include" , must_add: "ml_is_logged" )
	 */
	public function notifications_handler( $template ) {
		global $wp_query;
		
		if ( isset( $wp_query->query_vars['ml-notifications'] ) ) {
			return plugin_dir_path( __FILE__ ) . '/includes/class-ml-notifications.php';
		}

		return $template;
	}

	/**
	 * Add MercadoLivre options under WooCommerce Integrations Tab
	 *
	 * @filter( hook: "woocommerce_integrations" , must_add: "user_has_privileges" )
	 */
	public function add_woocommerce_mercadolivre_integration( $integrations ) {
		require_once( 'includes/class-ml-integration.php' );
		$integrations[] = 'WC_Mercadolivre_Integration';
        return $integrations;
	}

	/**
	 * Check if the user can manage the plugin
	 */
	public function user_has_privileges() {
		return ( is_admin()/* && current_user_can( 'manage_options' ) */);
	}

	/**
	 * Add ML product column
	 *
	 * @filter( hook: "manage_edit-product_columns" , must_add: "should_display_ml_contents" )
	 */
	public function add_ml_product_column_title( $columns ) {
		$columns['ml_actions'] = __( 'ML Actions' , $this->textdomain );
		return $columns;
	}

	/**
	 * Print product column content
	 *
	 * @action( hook: "manage_product_posts_custom_column" , must_add: "should_display_ml_contents" )
	 */
	public function add_ml_product_column_content( $column , $post_id ) {
		if ( $column != 'ml_actions' ) {
			return;
		}

		include( 'includes/views/html-product-column-content.php' );
	}

	/**
	 * Check if have to display custom columns and other ML contents
	 */
	public function should_display_ml_contents() {
		return ( $this->user_has_privileges() && $this->ml_is_logged() );
	}

	/**
	 * Update the product quantity at ML
	 *
	 * @action( hook: "woocommerce_product_set_stock" , must_add: "ml_is_logged" )
	 */
	public function update_product_quantity( $product ) {
		$ml_product = new ML_Product( $product );
		
		if ( ! $ml_product->is_published() ) {
			return;
		}

		try {
			$ml_product->update_stock();
		} catch ( ML_Exception $e ) {
			ML()->ml_error_message = sprintf( __( 'An error ocurred while trying to update the stock of the product %s on MercadoLivre: %s' , ML()->textdomain ) , $product->get_formatted_name() , $e->getMessage() );
		}
	}

	/**
	 * Check if ML is already configured e logged-in
	 */
	public function ml_is_logged() {
		if ( empty( $this->ml_communication ) ) {
			return false;
		}

		return $this->ml_communication->is_logged();
	}

	/**
	 * Include the tab integration scripts
	 *
	 * @script( include_in: "admin" )
	 */
	public function refresh_when_logged() {
		if ( ( isset( $_GET['page'] ) ) && ( $_GET['page'] == 'wc-settings' ) && isset( $_GET['tab'] ) && ( $_GET['tab'] == 'integration' ) ) {
			if ( isset( $_GET['section'] ) && ( $_GET['section'] != 'ag-magma-ml-integration' ) ) {
				return;
			}

			if ( ! $this->ml_is_logged() ) {
				wp_enqueue_script( 'refresh-on-close' , self::get_plugin_url( 'assets/js/ml.login.refresh.page.js' ) , array( 'thickbox' ) );
				wp_localize_script( 'refresh-on-close' , 'obj' , array( 'url' => self::get_admin_settings_page_url() ) );
			}
		}
	}

	/**
	 * Check if the user just logged in
	 */
	private function check_login() {
		if ( isset( $_GET['code'] ) && is_admin() && $this->ml_is_logged() ) {
			// Login routines
			$this->ml_nickname        = $this->ml_communication->get_user()->nickname;
			$this->ml_sites           = wp_list_pluck( $this->ml_communication->get_sites() , 'name' , 'id' );
			$store                    = $this->ml_communication->get_official_store();
			if ( ! empty( $store->brands ) ) {
				$this->ml_official_stores = wp_list_pluck( $store->brands , 'fantasy_name' , 'official_store_id' );
			}
			
			
			// It's inside the thickbox!
			include( 'includes/views/html-login-success-page.php' );
			die();
		} else if ( isset( $_GET['error_description'] , $_GET['section'] ) && ( strpos( $_GET['section'] , 'error' ) !== false ) ) {
			// Authorizing error
			include( 'includes/views/html-login-fail-page.php' );
			die();
		}
	}

	/**
	 * Check if the user logged out
	 */
	private function check_logout() {
		if ( isset( $_GET['ml-logout'] ) && $_GET['ml-logout'] == 'logout' ) {
			// Logout routines
			ML_Communication::reset_token_data();
			unset( $this->ml_nickname );
			unset( $this->ml_sites );
			unset( $this->ml_official_stores );

			wp_safe_redirect( add_query_arg( array( 'ml-logout' => '1' ) , wp_get_referer() ) );
		}
	}
}

/**
 * Initialize the plugin
 */
WooCommerce_MercadoLivre::register( array( 'called_class' => 'WooCommerce_MercadoLivre' ) );

/**
 * Instance shortcut
 */
function ML() {
	return WooCommerce_MercadoLivre::get_instance( 'WooCommerce_MercadoLivre' );
}

endif;