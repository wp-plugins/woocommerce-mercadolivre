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
		add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id , array( $this , 'fields_to_save' ) );
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
		
		?><tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
				<?php echo $this->get_tooltip_html( $data ); ?>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<a href="<?php echo esc_attr( $data['href'] ); ?>" class="<?php echo esc_attr( $data['class'] ); ?>" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo $this->get_custom_attribute_html( $data ); ?>><?php echo wp_kses_post( $data['title'] ); ?></a>
					<?php echo $this->get_description_html( $data ); ?>
				</fieldset>
			</td>
		</tr><?php

		return ob_get_clean();
	}

	/**
	 * Fields to save in the database
	 */
	public function fields_to_save( $sanitized_fields ) {
		$old_options = get_option( 'woocommerce_ag-magma-ml-integration_settings' , array() );

		$fields = array(
			'ml_app_id'          => '',
			'ml_secret_key'      => '',
			'ml_access_token'    => '',
			'ml_expires_in'      => '',
			'ml_refresh_token'   => '',
			'ml_nickname'        => '',
			'ml_sites'           => array(),
			'ml_official_stores' => array(),
			'ml_site'            => 'MLB',
			'ml_auto_export'     => 'yes',
			'ml_auto_update'     => 'yes',
			'ml_messages'        => array()
		);

		$fields_to_save = wp_parse_args( $sanitized_fields , wp_parse_args( $old_options , $fields ) );

		foreach ( $fields_to_save as $key => $value ) {
			if ( empty( $fields_to_save[ $key ] ) ) {
				unset( $fields_to_save[ $key ] );
			}
		}

		return $fields_to_save;
	}
}

endif;