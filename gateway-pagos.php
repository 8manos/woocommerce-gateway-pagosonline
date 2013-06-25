<?php
/*
Plugin Name: WooCommerce PagosOnline Gateway
Plugin URI: https://github.com/8manos/woocommerce-gateway-pagosonline
Description: Extends WooCommerce. Provides a PagosOnline (www.pagosonline.com) payment gateway for WooCommerce.
Version: 0.9
Author: 8manos S.A.S
Author URI: http://8manos.com/

	Copyright: © 2002-2013 8manos S.A.S (email: plugins@8manos.com)
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

add_action('plugins_loaded', 'woocommerce_gateway_pagosonline_init', 0);

function woocommerce_gateway_pagosonline_init() {

	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

	/**
	 * Localization
	 */
	load_plugin_textdomain('wc-gateway-pagosonline', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');

	/**
	 * Gateway class
	 */
	class WC_Gateway_Pagosonline extends WC_Payment_Gateway {

		function __construct() {
			$this->id                 = 'pagosonline';
			$this->icon               = plugins_url( 'images/pagos_logo.jpg', dirname(__FILE__) );
			$this->has_fields         = false;
			$this->method_title       = 'PagosOnline';
			$this->method_description = 'PagosOnline works by sending the user to <a href="http://www.pagosonline.com">pagosonline</a> to enter their payment information.';

			// Load the form fields.
			$this->init_form_fields();
			// Load the settings.
			$this->init_settings();

			$this->enabled     = $this->get_option('enabled');
			$this->title       = $this->get_option('title');
			$this->description = $this->get_option('description');
			$this->usuarioId   = $this->get_option('usuarioId');
			$this->llave       = $this->get_option('llave');
			$this->testmode    = $this->get_option('testmode');

			add_action( 'woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options') );
		}

		/**
		 * Initialize Gateway Settings Form Fields
		 */
		function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title' => __( 'Enable/Disable', 'woocommerce' ),
					'type' => 'checkbox',
					'label' => __( 'Enable PagosOnline', 'woocommerce' ),
					'default' => 'yes'
				),
				'title' => array(
					'title' => __( 'Title', 'woocommerce' ),
					'type' => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
					'default' => __( 'PagosOnline', 'woocommerce' ),
					'desc_tip' => true,
				),
				'description' => array(
					'title' => __( 'Description', 'woocommerce' ),
					'type' => 'textarea',
					'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
					'default' => 'PagosOnline, paga con tarjetas de credito y PSE en Colombia'
				),
				'usuarioId' => array(
					'title' => 'ID usuario',
					'type' => 'text',
					'description' => 'Número de usuario en el sistema de Pagosonline',
					'default' => ''
				),
				'llave' => array(
					'title' => 'Llave de encripción',
					'type' => 'text',
					'description' => 'Esta llave se puede consultar a través del módulo  administrativo  del  sistema  dado  por  Pagosonline',
					'default' => ''
				),
				'testmode' => array(
					'title' => __( 'Test mode', 'woocommerce' ),
					'type' => 'checkbox',
					'label' => __( 'Enable PagosOnline test mode', 'woocommerce' ),
					'default' => 'yes',
					'description' => 'Módulo que permite realizar pruebas con tarjetas de crédito ficticias y pagos simulados sobre el sistema PSE, en tiempo real.',
				),
			);
		}

		/**
		 * Check if this gateway is enabled and available in the user's country
		 *
		 * @access public
		 * @return bool
		 */
		function is_valid_for_use() {
			if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_paypal_supported_currencies', array('COP', 'MXN', 'USD', 'EUR', 'GBP', 'VEB') ) ) ) return false;

			return true;
		}

		/**
		 * Admin Panel Options
		 * - Options for bits like 'title' and availability on a country-by-country basis
		 *
		 * @since 1.0.0
		 */
		function admin_options() {

			?>
			<h3>PagosOnline</h3>
			<p><?php _e( 'PagosOnline works by sending the user to <a href="http://www.pagosonline.com">pagosonline</a> to enter their payment information.', 'woocommerce' ); ?></p>

			<?php if ( $this->is_valid_for_use() ) : ?>

				<table class="form-table">
				<?php
					// Generate the HTML For the settings form.
					$this->generate_settings_html();
				?>
				</table>

			<?php else : ?>
				<div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( 'PagosOnline does not support your store currency.', 'woocommerce' ); ?></p></div>
			<?php
			endif;
		}
	}

	/**
	* Add the Gateway to WooCommerce
	**/
	function woocommerce_add_gateway_pagosonline($methods) {
		$methods[] = 'WC_Gateway_Pagosonline';
		return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_pagosonline' );
}
