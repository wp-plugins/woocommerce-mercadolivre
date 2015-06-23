<?php
/**
 * Plugin Name: WooCommerce MercadoLivre
 * Plugin URI: http://agenciamagma.com.br
 * Description: WooCommerce integration with MercadoLivre.
 * Version: 0.1.0
 * Author: agenciamagma, Carlos Cardoso Dias
 * Author URI: http://agenciamagma.com.br
 * Text Domain: woocommerce-mercadolivre
 * Domain Path: /languages/
 * License: -
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
		ML()->remove_plugin_data();
		flush_rewrite_rules();
	}
	
	/**
	 * Initializes the plugin
	 */
	public function after_init() {
		$this->options_slug = 'woocommerce_ag-magma-ml-integration_settings';

		if ( $this->user_has_privileges() ) {
			if ( isset( $_POST['woocommerce_ag-magma-ml-integration_ml_app_id'] ) ) {
				ML()->ml_app_id = $_POST['woocommerce_ag-magma-ml-integration_ml_app_id'];
			}

			if ( isset( $_POST['woocommerce_ag-magma-ml-integration_ml_secret_key'] ) ) {
				ML()->ml_secret_key = $_POST['woocommerce_ag-magma-ml-integration_ml_secret_key'];
			}
		}

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
			$this->add_notice( sprintf( __( 'An error ocurred while trying to update the stock of the product %s on MercadoLivre: %s' , $this->textdomain ) , $product->get_formatted_name() , $e->getMessage() ) , 'error' );
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
	 * Check if the plugin isn't cofigured yet
	 */
	public function check_ml_isnt_logged() {
		return ( ! $this->ml_is_logged() );
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
			} else {
				wp_enqueue_script( 'jquery-option-tree-script' , self::get_plugin_url( '/assets/js/jquery.option.tree.js' ) , array( 'jquery' ) );
				include_once( WC()->plugin_path() . '/includes/admin/class-wc-admin-assets.php' );
				wp_enqueue_script( 'wc-enhanced-select' );
				wp_enqueue_script( 'jquery-ui-progressbar' );
				wp_enqueue_script( 'jquery-ui-dialog' );
			}
			
		}
	}

	/**
	 * Include style for order and products listing
	 *
	 * @style( include_in: "admin" , must_add: "should_display_ml_contents" )
	 */
	public function add_listing_styles( $page ) {
		$screen = get_current_screen();

		if ( $screen->id == 'edit-product' ) {
			wp_enqueue_style( 'ml-columns-style' , self::get_plugin_url( '/assets/css/ml.columns.css' ) , array() , '1.0.8' );
		}
	}

	/**
	 * Remove saved fields and options
	 */
	public function remove_plugin_data() {
		foreach ( ML_Product::get_fields() as $field ) {
			delete_post_meta_by_key( ML_Product::ML_PREFIX . $field );
		}

		delete_option( $this->options_slug );
	}

	/**
	 * Print an admin notice if WooCommerce is missing
	 *
	 * @action( hook: "admin_notices" , must_add: "check_woocommerce_missing" )
	 */
	public function woocommerce_missing_notice() {
		self::print_error_notice( __( 'Please make sure that WooCommerce is installed and active for this plugin to work properly' , $this->textdomain ) );
	}

	/**
	 * Check if WooCommerce isn't active
	 */
	public function check_woocommerce_missing() {
		return ( ! self::check_active_plugin( 'woocommerce/woocommerce.php' ) );
	}

	/**
	 * Print an admin notice to advert the user to configure the plugin
	 *
	 * @action( hook: "admin_notices" , must_add: "check_ml_isnt_logged" )
	 */
	public function configure_plugin_notice() {
		self::print_warning_notice( sprintf( __( 'The MercadoLivre is not already set in your store, click %shere%s to configure the plugin' , $this->textdomain ) , '<a href="' . self::get_admin_settings_page_url() . '">' , '</a>' ) );
	}

	/**
	 * Print admin notices related with API operations
	 *
	 * @action( hook: "admin_notices" , must_add: "has_messages" )
	 */
	public function print_notices() {
		foreach ( $this->ml_messages as $message ) {
			switch ( $message['type'] ) {
				case 'success' : self::print_success_notice( $message['message'] ); break;
				case 'error'   : self::print_error_notice( $message['message'] ); break;
				case 'warning' : self::print_warning_notice( $message['message'] ); break;				
				default        : self::print_admin_notice( $message['message'] , $message['type'] ); break;
			}
		}

		unset( $this->ml_messages );
	}

	/**
	 * Add a notice to the array of notices
	 */
	public function add_notice( $message , $type = 'error' ) {
		$messages = $this->ml_messages;

		if ( empty( $messages ) || ! is_array( $messages ) ) {
			$messages = array();
		}

		$messages[] = array( 'message' => $message , 'type' => $type );
		$this->ml_messages = $messages;
	}

	/**
	 * Check if the API has messages to display
	 */
	public function has_messages() {
		return ( $this->ml_is_logged() && ( ! empty( $this->ml_messages ) ) );
	}

	/**
	 * Display the log-out message
	 *
	 * @action( hook: "admin_notices" , must_add: "check_logout_notice" )
	 */
	public function logout_notice() {
		self::print_success_notice( __( 'The MercadoLivre is no longer integrated with your store' , $this->textdomain ) );
	}

	/**
	 * Check if the user logged out
	 */
	public function check_logout_notice() {
		return ( isset( $_GET['ml-logout'] ) && ( $_GET['ml-logout'] == 1 ) );
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
			$this->remove_plugin_data();

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