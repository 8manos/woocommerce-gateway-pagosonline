<?php
/*
Plugin Name: WooCommerce Pagos en Linea Gateway
Plugin URI: https://github.com/8manos/woocommerce-gateway-pagosonline
Description: Extends WooCommerce. Provides a Pagos en Linea (www.pagosonline.com) payment gateway for WooCommerce.
Version: 0.8
Author: 8manos S.A.S
*/

/*  Copyright 2012  8manos S.A.S  (email : plugins@8manos.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    
*/

// Init Pagosonline Gateway after WooCommerce has loaded
add_action('plugins_loaded', 'init_pagos_gateway', 0);

function init_pagos_gateway() {
	// If the WooCommerce payment gateway class is not available, do nothing
	if ( !class_exists( 'woocommerce_payment_gateway' ) ) return;
	class woocommerce_pagos extends woocommerce_payment_gateway { 
		public function __construct() { 
			global $woocommerce; 
			$this->id			= 'pagosonline';
		   	$this->icon 		= plugins_url(basename(dirname(__FILE__))."/images/pagos_logo.jpg");
		   	$this->has_fields 	= false;
		   	$this->purchaseurl = "https://gateway.pagosonline.net/apps/gateway/index.html";
			//$this->purchaseurl = "https://gateway.pagosonline/apps/gateway/index.html";
	    
	    	// Load the form fields.
			$this->init_form_fields();
			// Load the settings.
			$this->init_settings();	
			//print_r($this->settings);exit;

			$this->enabled			= $this->settings['enabled'];
			$this->title 			= $this->settings['title'];
			$this->descripcion  	= $this->settings['Description'];
			$this->usuarioId				= $this->settings['usuarioId'];
			$this->firma		=$this->settings['firma'];
			$this->iva = $this->settings['iva'];
			$this->debugmode		= $this->settings['debugmode'];
			$this->debugmode_email	= $this->settings['debugmode_email'];
			$this->testmode			= $this->settings['testmode'];
				
			add_action( 'init', array(&$this, 'check_pagos_response') );		
			add_action( 'valid-pagosonline-request', array(&$this, 'successful_request') );
			add_action( 'woocommerce_receipt_pagosonline', array(&$this, 'receipt_page') );
			add_action( 'woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options') );	
		}
		/**
    	 * Initialise Gateway Settings Form Fields
    	 */
    	function init_form_fields() {
    		$this->form_fields = array(
				'enabled' => array(
								'title' => __( 'Enable/Disable', 'woothemes' ), 
								'type' => 'checkbox', 
								'label' => __( 'Enable Pagos', 'woothemes' ), 
								'default' => 'yes'
							), 
				'title' => array(
								'title' => __( 'Title', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' ), 
								'default' => __( 'Pagos', 'woothemes' )
							),
				'Description'=>array(
				             'title' => __( 'description', 'woothemes' ), 
				             'type' => 'text', 
										 'description' => __( 'Please enter pagos information;', 'woothemes' ), 
										 'default' => 'Pruebas'
										),
				'usuarioId'=>array(
				             'title' => __( 'Pagos user account number', 'woothemes' ), 
				             'type' => 'text', 
										 'description' => __( 'Please enter your pagos account number; this is needed in order to take payment!', 'woothemes' ), 
										 'default' => ''
										),
				'testmode'=>array(
				             'title' => __( 'Mode', 'woothemes' ), 
				             'type' => 'select', 
										 'options' => array('1'=>'Pruebas', '2'=>'Payment'),
								'description' => __( '<br/>Standard Routine allows the customer to pay with credit card & via Paypal. The Single Page Checkout only allows credit cards. More information about purchase routines can be found in the readme.txt', 'woothemes' ), 
								'default' => '1'
										),						
																													
				'firma' => array(
								'title' => __( 'firma', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Please enter your pagos secret word; this is needed in order to take payment!', 'woothemes' ), 
								'default' => ''
							), 
				'iva' => array(
								'title' => __( 'iva', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Please enter your pagos VAT;', 'woothemes' ), 
								'default' => ''
							),			
				'debugmode_email' => array(
								'title' => __( 'Who gets the Debug emails', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'If Send Debug Emails is active, where do we send the emails?', 'woothemes' ), 
								'default' => get_bloginfo('admin_email')
							)
				);
    
		} // End init_form_fields()
		
		
		/**
	 	* Admin Panel Options 
	 	* - Options for bits like 'title' and availability on a country-by-country basis
	 	*
	 	* @since 1.0.0
	 	*/
		public function admin_options() {
	
	    	?>
	    	<h3><?php _e('Pagos', 'woothemes'); ?></h3>
	    	
	    	<?php _e('<p>Pagos works by sending the user to <a href="http://www.pagosonline.com">pagosonline</a> to enter their payment information. Instructions on how to set up the pagos account settings can be found in the readme.txt.</p>', 'woothemes'); ?>
	    	
	    	<div class="updated inline">
	    		<p><?php _e('Please note that the WooCommerce currency must match the currency that has been specified in your pagosonline account, otherwise the customer will be charged with the wrong amount.', 'woothemes'); ?></p>
	    		
	    		<p><?php _e('Also note, if you offer free shipping for tangible (i.e. physical) products you need to add a New Shipping Method in your Pagos account (see the shipping section in your pagos account). Set the pricing to Free and then choose which countries this method should apply to.', 'woothemes'); ?></p>
	    	</div>
	    	
    		<table class="form-table">
    		<?php
    			// Generate the HTML For the settings form.
    			$this->generate_settings_html();
    		?>
			</table><!--/.form-table-->
    		<?php
    	} // End admin_options()
    
    /**
    * There are no payment fields for bacs, but we want to show the description if set.
    **/
    function payment_fields() {
      if ($this->description) echo wpautop(wptexturize($this->description));
    }
    
    /**
	 	* Generate the pagos button link
	 	**/
		public function generate_pagos_form( $order_id ) {
			global $woocommerce;
			$order = &new woocommerce_order( $order_id );
			//print_r($order);
			$oitems = unserialize($order->order_custom_fields['_order_items'][0]);
			$_region = isset($_COOKIE['ms-region']) ? $_COOKIE['ms-region'] : mSalazar_Shop::get_data('region'); 
			if($_region == "co"){
		     $moneda = "COP";
			}else{
			$moneda = "USD";
		}
			$order_currency = $order->order_custom_fields[0];
			$shipping_name = explode(' ', $order->shipping_method);
			//echo "$this->firma~$this->usuarioId~$order_id~$order->order_total~$moneda";echo "<BR>";
			//echo md5("$this->firma~$this->usuarioId~$order_id~$order->order_total~$moneda");	
			$llave_encripcion = "$this->firma";
			$refVenta = "$order_id"; //referencia que debe ser única para cada transacción
			$baseDevolucionIva= $oitems[0]['line_total'];//"10000.00";//$order->order_total - $order->order_tax; //el precio sin iva de los productos que tienen iva
			//$iva=$this->iva; //impuestos calculados de la transacción
			$iva= $oitems[0]['line_subtotal_tax'];//"1600.00";
			$valor= $order->order_total; //el valor total
      $firma_cadena = "$llave_encripcion~$this->usuarioId~$refVenta~$valor~$moneda";
			//$firma_cadena = "$llave_encripcion~$this->usuarioId~$order_id~$order->order_total~$moneda";
			$pagos_args = array_merge(
				array(
					'usuarioId' 					=> $this->usuarioId,
					'prueba'					=> 1,
					//'x_receipt_link_url' 	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id')))),
					//'return_url'			=> $order->get_cancel_order_url(),
					//'id_type'				=> 1,
					
					// Order key
					'refVenta'			=> $refVenta,
					'descripcion' => $this->descripcion,
					'iva' => $iva,//$this->iva,
					'baseDevolucionIva' => $baseDevolucionIva,
					'emailComprador' => $this->debugmode_email,
					'valor'					=> $valor,//$order->order_total,
					'moneda' => $moneda,
					'firma' => md5("$firma_cadena"),
					'url_respuesta' => add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id')))),
					'url_confirmacion' => get_site_url()."/confirm-pagos/",
					'nombreComprador' => $order->billing_first_name ." ".$order->billing_last_name,
					'telefonoMovil' => $order->billing_phone,
					
					// Address info
					/*'first_name'			=> $order->billing_first_name,
					'last_name'				=> $order->billing_last_name,
					'street_address'		=> $order->billing_address_1,
					'street_address2'		=> $order->billing_address_2,
					'city'					=> $order->billing_city,
					'state'					=> $order->billing_state,
					'zip'					=> $order->billing_postcode,
					'country'				=> $order->billing_country,
					'email'					=> $order->billing_email,
					'phone'					=> $order->billing_phone,
					
					// Shipping info
					'ship_name'				=> $order->shipping_first_name . ' ' . $order->shipping_last_name,
					'ship_street_address'	=> $order->shipping_address_1,
					'ship_street_address2'	=> $order->shipping_address_2,
					'ship_city'				=> $order->shipping_city,
					'ship_state'			=> $order->shipping_state,
					'ship_zip'				=> $order->shipping_postcode,
					'ship_country'			=> $order->shipping_country,*/
		
					// Payment Info
				)
			);
			//print_r($pagos_args);exit;
			// Cart Contents
			$item_loop = 0;
			if (sizeof($order->get_items())>0) : foreach ($order->get_items() as $item) :
				$_product = $order->get_product_from_item( $item );
				if ($_product->exists() && $item['qty']) :
					
					// Check if product is downloadable or virtual. If so then set the product as intangible (N).
					// Otherwise set the product as tangible (Y).
					if ( $_product->is_virtual() ) :
						$tangible = "N";
					else :
						$tangible = "Y";
					endif;
							
					//$pagos_args['li_'.$item_loop.'_type'] = 'product';
					///$pagos_args['li_'.$item_loop.'_name'] = $item['name'];
					//$pagos_args['li_'.$item_loop.'_product_id'] = $item['id'];
					//$pagos_args['li_'.$item_loop.'_quantity'] = $item['qty'];
					
					if ($order->prices_include_tax) :
					//	$pagos_args['li_'.$item_loop.'_price'] = number_format($order->get_item_total( $item, true ), 2, '.', '');
					else :
						//$pagos_args['li_'.$item_loop.'_price'] = number_format($order->get_item_total( $item, false ), 2, '.', '');
					endif;
					
					
					//$pagos_args['li_'.$item_loop.'_tangible'] = $tangible;
					
					$item_loop++;
									
				endif;
			endforeach; endif;

			
			// Shipping Cost
			if ($order->get_shipping()>0) :
			
				//$pagos_args['li_'.$item_loop.'_type'] = 'shipping';
				//$pagos_args['li_'.$item_loop.'_name'] = __('Shipping cost', 'woothemes');
				//$pagos_args['li_'.$item_loop.'_quantity'] = 1;
				
				if ($order->prices_include_tax) :
					//$pagos_args['li_'.$item_loop.'_price'] = number_format(($order->get_shipping() + $order->order_shipping_tax), 2, '.', '');
				else :
					//$pagos_args['li_'.$item_loop.'_price'] = number_format($order->get_shipping(), 2, '.', '');
				endif;
				
				//$pagos_args['li_'.$item_loop.'_tangible'] = 'Y';
				//$item_loop++;
				
			endif;
			
			// Tax
			if (!$order->prices_include_tax && $order->get_total_tax()>0) :
			
				/*$pagos_args['li_'.$item_loop.'_type'] = 'tax';
				$pagos_args['li_'.$item_loop.'_name'] = __('Tax', 'woothemes');
				$pagos_args['li_'.$item_loop.'_quantity'] = 1;
				$pagos_args['li_'.$item_loop.'_price'] = $order->get_total_tax();
				$pagos_args['li_'.$item_loop.'_tangible'] = 'N';
				$item_loop++;*/
			endif;
			
			// Discount
			if ($order->get_order_discount()>0) :
			
				/*$pagos_args['li_'.$item_loop.'_type'] = 'coupon';
				$pagos_args['li_'.$item_loop.'_name'] = __('Discount', 'woothemes');
				$pagos_args['li_'.$item_loop.'_quantity'] = 1;
				$pagos_args['li_'.$item_loop.'_price'] = $order->get_order_discount();
				$pagos_args['li_'.$item_loop.'_tangible'] = 'N';*/
				
			endif;
			
			
			// SEND THE DEBUG EMAIL
			if ( $this->debugmode == 'yes' && isset($this->debugmode_email) ) :
								
				foreach ( $pagos_args as $key => $value ) {
					$message .= $key . '=' . $value . "\r\n";
				}
					
				$message = 'Order ID: ' . $order_id . "\r\n" . "\r\n" . $message;
				pagos_debug_email( $this->debugmode_email, 'Pagos Debug. Sent Values Order ID: ' . $order_id, $message );
				
			endif;
			
			// Set the form action address depending on the selected Purchase Routine 
			/*if ( $this->purchase_routine == 'spurchase' ):
				$twocheckout_adr = $this->spurchaseurl;
			else :
				$twocheckout_adr = $this->purchaseurl;
			endif;*/
			$pagos_adr = $this->purchaseurl;
			// Prepare the form	
			//print_r($pagos_args);exit;
			$pagos_args_array = array();
			foreach ($pagos_args as $key => $value) {
				$pagos_args_array[] = '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
			}
			//print_r($pagos_args_array);exit;
			// The form
			/*echo '<form action="'.$pagos_adr.'" method="post" id="pagos_payment_form">
					' . implode('', $pagos_args_array) . '
					<input type="submit" class="button-alt" id="submit_pagos_payment_form" value="'.__('Pay via Pagosonline', 'woothemes').'" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'woothemes').'</a>
					<script type="text/javascript">
						jQuery(function(){
							jQuery("body").block(
								{ 
									message: "<img src=\"'.$woocommerce->plugin_url().'/assets/images/ajax-loader.gif\" alt=\"Redirecting...\" />'.__('Thank you for your order. We are now redirecting you to pagos to make payment.', 'woothemes').'", 
									overlayCSS: 
									{ 
										background: "#fff", 
										opacity: 1 
									},
									css: { 
                                   		padding:        20, 
                                   		textAlign:      "center", 
                                   		color:          "#555", 
                                   		border:         "3px solid #aaa", 
                                   		backgroundColor:"#fff", 
                                   		cursor:         "wait",
                                   		lineHeight:        "32px"
                               		} 
								});
							jQuery("#submit_pagos_payment_form").click();
						});
					</script>
				</form>';
				exit;*/
			return '<form action="'.$pagos_adr.'" method="post" id="pagos_payment_form">
					' . implode('', $pagos_args_array) . '
					<input type="submit" class="button-alt" id="submit_pagos_payment_form" value="'.__('Pay via Pagosonline', 'woothemes').'" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'woothemes').'</a>
					<script type="text/javascript">
						jQuery(function(){
							jQuery("body").block(
								{ 
									message: "<img src=\"'.$woocommerce->plugin_url().'/assets/images/ajax-loader.gif\" alt=\"Redirecting...\" />'.__('Thank you for your order. We are now redirecting you to pagos to make payment.', 'woothemes').'", 
									overlayCSS: 
									{ 
										background: "#fff", 
										opacity: 1 
									},
									css: { 
                                   		padding:        20, 
                                   		textAlign:      "center", 
                                   		color:          "#555", 
                                   		border:         "3px solid #aaa", 
                                   		backgroundColor:"#fff", 
                                   		cursor:         "wait",
                                   		lineHeight:        "32px"
                               		} 
								});
							jQuery("#submit_pagos_payment_form").click();
						});
					</script>
				</form>';
			
		}
		
		/**
		 * Process the payment and return the result
		 **/
		function process_payment( $order_id ) {
			
			$order = &new woocommerce_order( $order_id );
			return array(
				'result' 	=> 'success',
				'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
			);
			
		}
		
		/**
		 * receipt_page
		 **/
		function receipt_page( $order ) {
			echo '<p>'.__('Thank you for your order, please click the button below to pay with pagosonline.', 'woothemes').'</p>';
			
			echo $this->generate_pagos_form( $order );
			
		}
		
		/**
		 * Check for PAGOS Response
		 **/
		function check_pagos_response() {
			
			//print_r($_REQUEST);
			$llave = $llave_encripcion;
			$usuario_id=$_REQUEST['usuario_id'];
			$ref_venta=$_REQUEST['ref_venta'];
			$ref_pol = $_REQUEST['ref_pol'];
			$valor=$_REQUEST['valor'];
			$moneda=$_REQUEST['moneda'];
			$estado_pol=$_REQUEST['estado_pol'];
			$firma_cadena= "$llave~$usuario_id~$ref_venta~$valor~$moneda~$estado_pol";$firmacreada = md5($firma_cadena);//firma que generaron ustedes
			$firma =$_REQUEST['firma'];//firma que envía nuestro sistema
			$ref_venta=$_REQUEST['ref_venta'];
			$fecha_procesamiento=$_REQUEST['fecha_procesamiento'];
			$ref_pol=$_REQUEST['ref_pol'];
			$cus=$_REQUEST['cus'];
			$extra1=$_REQUEST['extra1'];
			$banco_pse=$_REQUEST['banco_pse'];
			if($_REQUEST['estado_pol'] == 6 && $_REQUEST['codigo_respuesta_pol'] == 5)
			{$estadoTx = "Transacci&oacute;n fallida";}
			else if($_REQUEST['estado_pol'] == 6 && $_REQUEST['codigo_respuesta_pol'] == 4)
			{$estadoTx = "Transacci&oacute;n rechazada";}
			else if($_REQUEST['estado_pol'] == 12 && $_REQUEST['codigo_respuesta_pol'] == 9994)
			{$estadoTx = "Pendiente, Por favor revisar si el d&eacute;bito fue realizado en el Banco";}
			else if($_REQUEST['estado_pol'] == 4 && $_REQUEST['codigo_respuesta_pol'] == 1)
			{
				$firma = "$llave_encripcion~$usuario_id~$ref_venta~$valor~$moneda~$estado_pol";
				$estadoTx = "Transacci&oacute;n aprobada";}
			else
			{$estadoTx=$_REQUEST['mensaje'];}
			//if(strtoupper($firma)==strtoupper($firmacreada)){// comparacion de las firmas para comprobar que los datos si vienen de Pagosonline
			
			//echo $_REQUEST['ref_pol'];
			// Check for return to thank you page or pagos INS-response
			if ( isset($_REQUEST['ref_pol']) ) :
			
				// Get values for calculating the MD5-hash
				$firma = $firma_cadena;
				$usuarioId = $usuario_id;
				
				// Call made from "Click here to finalize the order"-button
				if ( isset($ref_pol) ) :
				
					// GET THE RETURN FROM pagos
					$RefNr = $ref_pol;
					$order_number = $_REQUEST["order"];
					$total = $valor;
					$pagosMD5 = $_REQUEST["key"];
				
					// Calculate our specific MD5Hash so we can validate it with the one sent from 2Checkout
					// If this is a test purchase we need to change the order number to 1
					if ( $this->testmode == 'yes' ):
						$string_to_hash = $usuarioId . $sid . "1" . $valor;		
					else :
						$string_to_hash = $usuarioId . $sid . $_REQUEST["order"] . $valor;		
					endif;
					
					$check_key = strtoupper(md5($string_to_hash));

					// Put the variables returned from twocheckout in an array so we can pass them on 
					// to the successful_request function.
					$pagos_return_values = array(
						"check_key" 		=> 	$check_key,
						"RefNr" 			=> $RefNr,
						"sale_id" 			=> $ref_venta,
						"total" 			=> $total,
						"twocheckoutMD5" 	=> $pagosMD5
					);
					
			
				// 2Checkout INS-response
				elseif ( isset($ref_pol) ) :
			
					// GET THE RETURN FROM 2Checkout
					$RefNr = $ref_pol;
					$sale_id = $refVenta;
					$invoice_id = $_REQUEST["invoice_id"];
					$pagoMD5 = $_REQUEST["md5_hash"];
					$vendor_id = $ref_pol;
					
					// Calculate our specific MD5Hash so we can validate it with the one sent from 2Checkout
					$string_to_hash = $sale_id . $sid . $invoice_id . $secret_word;		
					$check_key = strtoupper(md5($string_to_hash));
					
	
					// Put the variables returned from twocheckout in an array so we can pass them on 
					// to the successful_request function.
					$pagos_return_values = array(
						"check_key" 		=> $check_key,
						"RefNr" 			=> $RefNr,
						"sale_id" 			=> $sale_id,
						"vendor_id" 		=> $vendor_id,
						"twocheckoutMD5" 	=> $pagoMD5
					);
					
				endif;
				
				// SEND THE DEBUG EMAIL
				if ( $this->debugmode == 'yes' && isset($this->debugmode_email) ) :
				
					$order_id 	  	= $RefNr;
									
					foreach ( $twocheckout_return_values as $key => $value ) {
						$message .= $key . '=' . $value . "\r\n";
					}
						
					$message = 'Order ID: ' . $order_id . "\r\n" . "\r\n" . $message;
					pagos_debug_email( $this->debugmode_email, 'Pagos Debug. Return Values Order ID: ' . $order_id, $message );
				
				endif;
					
				// COMPARE MD5-HASH. IF IT'S OK THEN THE TRANSACTION IS VALID.
				if ( isset($pagos_return_values['check_key']) && $check_key == $twocheckoutMD5 ) :
					do_action("valid-twocheckout-request", $pagos_return_values);
				endif;
				
				
			endif;								
		}
		
	}
}//init

/**
 * Add the gateway to WooCommerce
 **/
function add_pagosonline_gateway( $methods ) {
	$methods[] = 'woocommerce_pagos'; return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_pagosonline_gateway' );