woocommerce-gateway-pagosonline
===============================

Extends WooCommerce. Provides a PagosOnline (www.pagosonline.com) payment gateway for WooCommerce.

License: GNU General Public License v3.0

Requires at least: Wordpress 3.5.0 WooCommerce 2.0.0

Tested up to: Wordpress 3.5.2 WooCommerce 2.0.12

Installation
============

1. Upload `woocommerce-gateway-pagosonline` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Activate gateway and set options in Woocommerce config

Setup
=====

* A PagosOnline account is needed to obtain the user id and encryption key
  * When using test mode, a different encryption key is used. You can get this from PagosOnline support
* PagosOnline is mainly used with Colombian Pesos. This currency is not included by default in Woocommerce. It can be added as described here: http://docs.woothemes.com/document/add-a-custom-currency-symbol/
  * The correct currency code for colombian pesos is COP
