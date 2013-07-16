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

		var $notify_url;

		var $pol_codes = array(
			1 => 'Transacción aprobada',
			2 => 'Pago cancelado por el usuario',
			3 => 'Pago cancelado por el usuario durante validación',
			4 => 'Transacción rechazada por la entidad',
			5 => 'Transacción declinada por la entidad',
			6 => 'Fondos insuficientes',
			7 => 'Tarjeta invalida',
			8 => 'Acuda a su entidad',
			9 => 'Tarjeta vencida',
			10 => 'Tarjeta restringida',
			11 => 'Discrecional POL',
			12 => 'Fecha de expiracióno campo seg. Inválidos',
			13 => 'Repita transacción',
			14 => 'Transacción inválida',
			15 => 'Transacción en proceso de validación',
			16 => 'Combinación usuario-contraseña inválidos',
			17 => 'Monto excede máximo permitido por entidad',
			18 => 'Documento de identificación inválido',
			19 => 'Transacción abandonada capturando datos TC',
			20 => 'Transacción abandonada',
			21 => 'Imposible reversar transacción',
			22 => 'Tarjeta no autorizada para realizar compras por internet.',
			23 => 'Transacción rechazada',
			24 => 'Transacción parcial aprobada',
			25 => 'Rechazada por no confirmación',
			26 => 'Comprobante generado, esperando pago en banco',
			9994 => 'Transacción pendiente por confirmar',
			9995 => 'Certificado digital no encontrado',
			9996 => 'Entidad no responde',
			9997 => 'Error de mensajería con la entidad financiera',
			9998 => 'Error en la entidad financiera',
			9999 => 'Error no especificado'
		);

		function __construct() {
			global $woocommerce;

			$this->id                 = 'pagosonline';
			$this->icon               = plugins_url('images/pol_trans.png', __FILE__);
			$this->has_fields         = false;
			$this->method_title       = 'PagosOnline';
			$this->method_description = 'PagosOnline works by sending the user to <a href="http://www.pagosonline.com">pagosonline</a> to enter their payment information.';

			$this->liveurl    = 'https://gateway.pagosonline.net/apps/gateway/index.html';
			$this->testurl    = 'https://gateway2.pagosonline.net/apps/gateway/index.html';
			$this->notify_url = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Gateway_Pagosonline', home_url( '/' ) ) );

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
			$this->debug       = $this->get_option( 'debug' );

			//send using POST or GET
			$this->form_submission_method = $this->get_option( 'form_submission_method' ) == 'yes' ? true : false;

			// Logs
			if ( $this->debug == 'yes' )
				$this->log = $woocommerce->logger();

			add_action( 'woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options') );

			//to generate PagosOnline form (submit it automatically using javascript)
			add_action( 'woocommerce_receipt_pagosonline', array( $this, 'receipt_page' ) );

			// Payment listener/API hook
			add_action( 'woocommerce_api_wc_gateway_pagosonline', array( $this, 'check_pagos_response' ) );
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
				'debug' => array(
					'title' => __( 'Debug Log', 'woocommerce' ),
					'type' => 'checkbox',
					'label' => __( 'Enable logging', 'woocommerce' ),
					'default' => 'no',
					'description' => sprintf( __( 'Log PagosOnline events, such as IPN requests, inside <code>woocommerce/logs/pagosonline-%s.txt</code>', 'woocommerce' ), sanitize_file_name( wp_hash( 'pagosonline' ) ) ),
				),
				'form_submission_method' => array(
					'title' => __( 'Submission method', 'woocommerce' ),
					'type' => 'checkbox',
					'label' => __( 'Use form submission method.', 'woocommerce' ),
					'description' => 'Activando esta opción, se utilizará un formulario para enviar los datos a PagosOnline, ocultando la información del pedido de la URL.',
					'default' => 'no'
				)
			);
		}

		/**
		 * Check if this gateway is enabled and available in the user's country
		 *
		 * @access public
		 * @return bool
		 */
		function is_valid_for_use() {
			if ( ! in_array( get_woocommerce_currency(), array('COP', 'MXN', 'USD', 'EUR', 'GBP', 'VEB') ) ) {
				return false;
			}

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

		/**
		 * Get args for passing to PagosOnline
		 *
		 * @access public
		 * @param mixed $order
		 * @return array
		 */
		function get_pagosonline_args( $order ) {
			global $woocommerce;

			$moneda = get_woocommerce_currency();
			$firma = md5("$this->llave~$this->usuarioId~$order->id~$order->order_total~$moneda");

			//base only from items that have tax
			$base_iva = 0;

			$line_items = $order->get_items();//line items can have more than one of the same product
			foreach ($line_items as $item) {
				$item_tax = $order->get_line_tax($item);
				if ($item_tax > 0) {
					$base_iva += $order->get_line_total($item);
				}
			}
			//shipping with tax
			if ($order->get_shipping_tax() > 0) {
				$base_iva += $order->get_shipping();
			}

			if ( $this->debug == 'yes' ) {
				$this->log->add( 'pagosonline', 'Generating payment form for order ' . $order->get_order_number() . '. Notify URL: ' . $this->notify_url );
			}

			// PagosOnline Args
			$args = array(
				'usuarioId'         => $this->usuarioId,
				'refVenta'          => $order->id,
				'descripcion'       => 'orden no. '.$order->id.' - valor: '.$order->order_total,
				'valor'             => $order->order_total,
				'iva'               => $order->get_total_tax(),
				'baseDevolucionIva' => (string) $base_iva,
				'firma'             => $firma,
				'emailComprador'    => $order->billing_email,
				'moneda'            => $moneda,
				'nombreComprador'   => $order->billing_first_name.' '.$order->billing_last_name,
				'telefonoMovil'     => $order->billing_phone,
				'url_respuesta'     => $this->get_return_url( $order ),
				'url_confirmacion'  => $this->notify_url
			);

			$args['prueba'] = ($this->testmode == 'yes') ? 1 : 0;

			return $args;
		}


		/**
		 * Generate the pagosonline button link
		 *
		 * @access public
		 * @param mixed $order_id
		 * @return string
		 */
		function generate_pagosonline_form( $order_id ) {
			global $woocommerce;

			$order = new WC_Order( $order_id );

			$pagosonline_adr = ($this->testmode == 'yes') ? $this->testurl : $this->liveurl;

			$pagosonline_args = $this->get_pagosonline_args( $order );

			$form_inputs = array();

			foreach ($pagosonline_args as $key => $value) {
				$form_inputs[] = '<input type="hidden" name="'.esc_attr( $key ).'" value="'.esc_attr( $value ).'" />';
			}

			$woocommerce->add_inline_js( '
				jQuery("body").block({
					message: "' . esc_js( __( 'Thank you for your order. We are now redirecting you to PagosOnline to make payment.', 'woocommerce' ) ) . '",
					baseZ: 99999,
					overlayCSS:
					{
						background: "#fff",
						opacity: 0.6
					},
					css: {
						padding:        "20px",
						zindex:         "9999999",
						textAlign:      "center",
						color:          "#555",
						border:         "3px solid #aaa",
						backgroundColor:"#fff",
						cursor:         "wait",
						lineHeight:		"24px",
					}
				});
				jQuery("#submit_pagosonline_payment_form").click();
			' );

			return '<form action="'.esc_url( $pagosonline_adr ).'" method="post" id="pagosonline_payment_form" target="_top">
				' . implode( '', $form_inputs) . '
				<input type="submit" class="button alt" id="submit_pagosonline_payment_form" value="' . __( 'Pay via PagosOnline', 'woocommerce' ) . '" /> <a class="button cancel" href="'.esc_url( $order->get_cancel_order_url() ).'">'.__( 'Cancel order &amp; restore cart', 'woocommerce' ).'</a>
			</form>';
		}

		function pre_process_order($order) {
			global $woocommerce;

			$order->update_status('on-hold', 'Esperando respuesta PagosOnline.');
			//$order->reduce_order_stock();

			$woocommerce->cart->empty_cart();
		}

		/**
		 * Process the payment and return the result
		 *
		 * @access public
		 * @param int $order_id
		 * @return array
		 */
		function process_payment( $order_id ) {

			$order = new WC_Order( $order_id );

			if ( ! $this->form_submission_method ) {
				$redirect_args = $this->get_pagosonline_args( $order );
				$redirect_args = http_build_query( $redirect_args, '', '&' );

				$redirect = ($this->testmode == 'yes') ? $this->testurl : $this->liveurl;

				$this->pre_process_order($order);

				return array(
					'result'   => 'success',
					'redirect'  => $redirect . '?' . $redirect_args
				);
			} else {

				return array(
					'result'   => 'success',
					'redirect' => add_query_arg( 'order', $order->id, add_query_arg( 'key', $order->order_key, get_permalink( woocommerce_get_page_id('pay') ) ) )
				);
			}
		}

		/**
		 * Output for the order received page.
		 *
		 * @access public
		 * @return void
		 */
		function receipt_page( $order_id ) {

			$order = new WC_Order( $order_id );

			$this->pre_process_order($order);

			echo '<p>'.__( 'Thank you for your order, please click the button below to pay with PagosOnline.', 'woocommerce' ).'</p>';
			echo $this->generate_pagosonline_form( $order_id );

		}

		/**
		 * Check for PagosOnline IPN Response
		 *
		 * @access public
		 * @return void
		 */
		function check_pagos_response() {

			@ob_clean();

			if ( ! empty($_POST) ) {

				$usuario_id           = $_POST['usuario_id'];
				$estado_pol           = $_POST['estado_pol'];
				$codigo_respuesta_pol = $_POST['codigo_respuesta_pol'];
				$ref_venta            = $_POST['ref_venta'];
				$ref_pol              = $_POST['ref_pol'];
				$firma                = $_POST['firma'];
				$valor                = $_POST['valor'];
				$moneda               = $_POST['moneda'];

				$firma_generada = strtoupper(md5("$this->llave~$usuario_id~$ref_venta~$valor~$moneda~$estado_pol"));

				if ( $this->usuarioId != $usuario_id || $firma != $firma_generada ) {
					if ( $this->debug == 'yes' ) {
						$this->log->add( 'pagosonline', 'Error: User Id or key are wrong.' );
						$this->log->add( 'pagosonline', 'config_user: '.$this->usuarioId.'. post_user: '.$usuario_id );
						$this->log->add( 'pagosonline', 'sent_key: '.$firma.'. gen_key: '.$firma_generada );
					}
					exit;
				}

				$order = new WC_Order( $ref_venta );

				if ( ! isset( $order->id ) ) {
					if ( $this->debug == 'yes' )
						$this->log->add( 'pagosonline', 'Error: Order Id does not match invoice.' );
					exit;
				}

				if ( $this->debug == 'yes' ) {
					$this->log->add( 'pagosonline', 'Found order #' . $order->id );
					$this->log->add( 'pagosonline', 'Payment status: ' . $estado_pol );
					$this->log->add( 'pagosonline', 'Payment code: ' . $codigo_respuesta_pol );
				}

				//número de transacción PagosOnline
				$order->add_order_note('ref_pol: '.$ref_pol);

				$respuesta_pol = $this->pol_codes[$codigo_respuesta_pol];

				// We are here so lets check status and do actions
				switch ( $estado_pol ) {
					case 4 :
						// Payment completed
						$order->add_order_note('respuesta_pol: '.$respuesta_pol);
						$order->payment_complete();

						if ( $this->debug == 'yes' )
							$this->log->add( 'pagosonline', 'Payment complete.' );

						break;
					case 5 :
						$order->update_status('cancelled', 'respuesta_pol: '.$respuesta_pol);
						break;
					case 6 :
						$order->update_status('failed', 'respuesta_pol: '.$respuesta_pol);
						break;
					case 7 :
						$order->update_status('processing', 'respuesta_pol: '.$respuesta_pol);
						break;
					case 8 :
					case 9 :
						$order->update_status('refunded', 'Orden reversada. respuesta_pol: '.$respuesta_pol);
						break;
					default:
						$order->add_order_note('estado_pol: '.$estado_pol.' - respuesta_pol: '.$respuesta_pol);
				}
				exit;

			} else {

				wp_die( "PagosOnline IPN Request Failure" );

			}

		}
	}

	/**
	 * Overrides woocommerce core templates. We use it for thank you page
	 */
	function pagosonline_locate_template($template, $template_name, $template_path) {
		global $woocommerce;
		$default_template = $template;

		if ( ! $template_path ) $template_path = $woocommerce->template_url;
		$plugin_path  = untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/woocommerce/';

		// Look within passed path within the theme - this is priority
		$template = locate_template(
			array(
				trailingslashit( $template_path ) . $template_name,
				$template_name
			)
		);

		// Get the template from this plugin, if it exists
		if ( ! $template && file_exists( $plugin_path . $template_name ) ) {
		    $template = $plugin_path . $template_name;
		}

		// Use default template
		if ( ! $template ) {
		    $template = $default_template;
		}

		// Return what we found
		return $template;
	}
	add_filter('woocommerce_locate_template', 'pagosonline_locate_template', 10, 3);

	/**
	* Add the Gateway to WooCommerce
	**/
	function woocommerce_add_gateway_pagosonline($methods) {
		$methods[] = 'WC_Gateway_Pagosonline';
		return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_pagosonline' );
}
