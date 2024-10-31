<?php
/**
 * Plugin Name: PayFlexi Installment Payment Plans for LifterLMS
 * Plugin URI: https://developers.payflexi.co
 * Description: PayFlexi allows your customer to pay in installment for courses. PayFlexi supports your existing payment processor such as Stripe, PayStack, Flutterwave and more
 * Version: 1.0.5
 * Author: PayFlexi
 * Author URI: https://payflexi.co
 * Text Domain: lifterlms-payflexi-gateway
 * Domain Path: /i18n
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 5.1
 * Tested up to: 6.0.2
 * LMS requires at least: 3.29
 * LLMS tested up to: 6.10.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'LLMS_PAYFLEXI_GATEWAY_PLUGIN_FILE' ) ) {
	define( 'LLMS_PAYFLEXI_GATEWAY_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'LLMS_PAYFLEXI_GATEWAY_PLUGIN_DIR' ) ) {
	define( 'LLMS_PAYFLEXI_GATEWAY_PLUGIN_DIR', dirname( LLMS_PAYFLEXI_GATEWAY_PLUGIN_FILE ) . '/' );
}

if ( ! class_exists( 'LifterLMS_Payflexi_Gateway' ) ) {
	require_once LLMS_PAYFLEXI_GATEWAY_PLUGIN_DIR . 'class-lifterlms-payflexi-gateway.php';
}

/**
 * Main gateway instance
 *
 * @since 2021-02-18
 *
 * @return LifterLMS_Payflexi_Gateway
 */
function llms_payflexi_gateway() {
	return LifterLMS_Payflexi_Gateway::instance();
}
return llms_payflexi_gateway();
