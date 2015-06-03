<?php
/**
 * MercadoLivre Integration
 * 
 * @author Carlos Cardoso Dias
 *
 **/

/**
 * Anti cheating code
 */
defined( 'ABSPATH' ) or die( 'A Ag&ecirc;ncia Magma n&atilde;o deixa voc&ecirc; trapacear ;)' );

if( ! class_exists( 'WC_Mercadolivre_Integration' ) ) :

final class WC_Mercadolivre_Integration extends WC_Integration {
	
	public function __construct() {
		global $woocommerce;

		$this->id                 = 'ag-magma-ml-integration';
		$this->method_title       = __( 'MercadoLivre' , ML()->textdomain );
		$this->method_description = __( 'Integration with the MercadoLivre API' , ML()->textdomain );
		
		$this->init_settings();
		$this->init_form_fields();

		$this->ml_app_id          = $this->get_option( 'ml_app_id' );
		$this->ml_secret_key      = $this->get_option( 'ml_secret_key' );
		$this->ml_site            = $this->get_option( 'ml_site' );
		$this->ml_auto_export     = $this->get_option( 'ml_auto_export' );
		$this->ml_auto_update     = $this->get_option( 'ml_auto_update' );

		add_action( 'woocommerce_update_options_integration_' . $this->id , array( $this , 'process_admin_options' ) );
	}

	public function init_form_fields() {
		$textdomain = ML()->textdomain;

		if ( ML()->ml_is_logged() ) {
			$this->form_fields = array(
				'ml_logout_button'  => array(
					'title'             => __( 'Logout' , $textdomain ),
					'type'              => 'link',
					'href'              => add_query_arg( array( 'ml-logout' => 'logout' ) , wp_get_referer() ),
					'description'       => sprintf( '%s: <code>%s</code>' , __( 'Click to log out the current user' , $textdomain ) , ML()->ml_nickname )
				),
				'ml_first_section'  => array(
					'title'             => __( 'General Settings' , $textdomain ),
					'type'              => 'title',
					'description'       => __( 'Settings that affect the entire plugin behavior' , $textdomain )
				),
				'ml_site'           => array(
					'title'             => __( 'Country' , $textdomain ),
					'type'              => 'select',
					'description'       => __( 'Country where you want to act on MercadoLivre' , $textdomain ),
					'default'           => 'MLB',
					'options'           => ML()->ml_sites
				),
				'ml_auto_export'    => array(
					'title'             => __( 'Export Automatically' , $textdomain ),
					'type'              => 'checkbox',
					'description'       => __( 'Determines whether to create a new product in the store he also will be created on MercadoLivre' , $textdomain ),
					'default'           => 'yes'
				),
				'ml_auto_update'    => array(
					'title'             => __( 'Update Automatically' , $textdomain ),
					'type'              => 'checkbox',
					'description'       => __( 'Determines whether to change a product in the store he also will be changed on MercadoLivre' , $textdomain ),
					'default'           => 'yes'
				)
			);
		} else {
			$tutorial = sprintf( __( ' Create your application %shere%s. Your \'Redirect URI\' is %s and \'Notifications Callback URL\' is %s' , $textdomain ) , '<a href="http://applications.mercadolibre.com/" target="_blank">' , '</a>' , admin_url( 'admin.php' ) , get_site_url( get_current_blog_id() , 'ml-notifications' ) );
			$this->form_fields = array(
				'ml_app_id'         => array(
					'title'             => __( 'APP ID' , $textdomain ),
					'type'              => 'decimal',
					'description'       => __( 'Application ID on MercadoLivre.' , $textdomain ) . $tutorial,
					'default'           => ''
				),
				'ml_secret_key'     => array(
					'title'             => __( 'Secret Key' , $textdomain ),
					'type'              => 'text',
					'description'       => __( 'Application secret key on MercadoLivre.' , $textdomain ) . $tutorial,
					'default'           => ''
				)
			);

			if ( ! empty( ML()->ml_app_id ) && ! empty( ML()->ml_secret_key ) ) {
				$login_button = array(
					'ml_login_button' => array(
						'title'             => __( 'Login' , $textdomain ),
						'type'              => 'link',
						'href'              => add_query_arg( array( 'TB_iframe' => 'true' , 'width' => '800' , 'height' => '550' ) , ML()->ml_communication->get_login_url() ),
						'class'             => 'button-secondary thickbox',
						'custom_attributes' => array( 'title' => __( 'MercadoLivre Login' , $textdomain ) ),
						'description'       => __( 'Log in to perform operations' , $textdomain )
					)
				);
				$this->form_fields = array_merge( $this->form_fields , $login_button );
			}
		}
	}

	/**
     * Generate Button HTML.
     */
	public function generate_link_html( $key , $data ) {
		$field    = $this->plugin_id . $this->id . '_' . $key;
		$defaults = array(
			'href'              => '',
			'class'             => 'button-secondary',
			'css'               => '',
			'custom_attributes' => array(),
			'desc_tip'          => false,
			'description'       => '',
			'title'             => '',
		);

		$data = wp_parse_args( $data, $defaults );

		if ( strpos( $data['class'] , 'thickbox' ) !== false ) {
			add_thickbox();
		}

		ob_start();
		include( 'views/html-button.php' );
		return ob_get_clean();
	}

	/**
	 * - Saves the options to the DB
	 */
	public function validate_settings_fields( $form_fields = array() ) {
		parent::validate_settings_fields( $form_fields );

		if ( empty( $_POST[ $this->plugin_id . $this->id . '_ml_app_id' ] ) && empty( $_POST[ $this->plugin_id . $this->id . '_ml_secret_key' ] ) ) {
			$fields = array(
				'ml_app_id',
				'ml_secret_key',
				'ml_access_token',
				'ml_expires_in',
				'ml_refresh_token',
				'ml_nickname',
				'ml_sites',
				'ml_official_stores'
			);

			foreach ( $fields as $field ) {
				$this->sanitized_fields[ $field ] = ML()->{ $field };
			}
		}
	}
}

endif;