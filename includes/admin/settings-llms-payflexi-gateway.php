<?php
/**
 * LifterLMS Payflexi Gateway Settings
 *
 * @package LifterLMS_Payflexi_Gateway/Admin/Settings
 *
 * @since 1.0.0
 * @version 1.0.2
 */

defined( 'ABSPATH' ) || exit;

$gateway = llms_payflexi_gateway()->get_gateway();

$fields = array();

/**
 * Allow users to enter an API Key to use the gateway.
 */
$fields[] = array(
	'id' 			=> $gateway->get_option_name( 'disable_payment_plans' ),
	'title' 		=> __( 'Payment Plans', 'lifterlms-payflexi-gateway' ),
	'desc'         	=> __( 'Disable Installment Payment Plans', 'lifterlms-payflexi-gateway' ),
	'desc_tooltip' 	=> __( 'When checked, only full one-time payment would be available to your users.', 'lifterlms-payflexi-gateway' ),
	'default'      	=> 'no',
	'type'  		=> 'checkbox',
	'secure_option' => 'LLMS_PAYFLEXI_GATEWAY_DISABLE_PAYMENT_PLANS',
);

$fields[] = array(
	'id' 			=> $gateway->get_option_name( 'live_public_api_key' ),
	'title' 		=> __( 'PayFlexi Live Public API Key', 'lifterlms-payflexi-gateway' ),
	'type'  		=> 'text',
	'secure_option' => 'LLMS_PAYFLEXI_GATEWAY_LIVE_PUBLIC_API_KEY',
);

$fields[] = array(
	'id' 			=> $gateway->get_option_name( 'live_secret_api_key' ),
	'title' 		=> __( 'PayFlexi Live Secret API Key', 'lifterlms-payflexi-gateway' ),
	'type'  		=> 'text',
	'secure_option' => 'LLMS_PAYFLEXI_GATEWAY_LIVE_SECRET_API_KEY',
);

$fields[] = array(
	'id'            => $gateway->get_option_name( 'test_public_api_key' ),
	'title'         => __( 'PayFlexi Test Public API Key', 'lifterlms-payflexi-gateway' ),
	'type'          => 'text',
	'secure_option' => 'LLMS_PAYFLEXI_GATEWAY_TEST_PUBLIC_API_KEY',
);

$fields[] = array(
	'id'            => $gateway->get_option_name( 'test_secret_api_key' ),
	'title'         => __( 'PayFlexi Test Secret API Key', 'lifterlms-payflexi-gateway' ),
	'type'          => 'text',
	'secure_option' => 'LLMS_PAYFLEXI_GATEWAY_TEST_SECRET_API_KEY',
);

$fields[] = array(
	'id'            => $gateway->get_option_name( 'enabled_payment_gateway' ),
	'desc'    		=> '<br>' . __( 'This should correspond with the payment gateway enabled on your PayFlexi merchant dashboard.', 'lifterlms-payflexi-gateway' ),
	'title'         => __( 'Payment Gateway', 'lifterlms-payflexi-gateway' ),
	'default'  		=> 'stripe',
	'type'     		=> 'select',
	'desc_tip' 		=> false,
	'options'  		=> array(
		'stripe' => 'Stripe',
		'paystack' => 'Paystack',
		'payflexi' => 'PayFlexi (For Nigeria Merchants Only)'
	),
);

$fields[] = array(
	'id'    		=> $gateway->get_option_name( 'payment_instructions' ),
	'desc'  		=> '<br>' . __( 'Displayed to the user when this gateway is selected during checkout.', 'lifterlms-payflexi-gateway' ),
	'title' 		=> __( 'Checkout Info', 'lifterlms-payflexi-gateway' ),
	'default' 		=> 'You would automatically be enrolled to the course once your payment is completed',
	'type'  		=> 'textarea',
);

$fields[] = array(
	'id'    		=> $gateway->get_option_name( 'webhook_url' ),
	'desc'  		=> '<br>' . __( 'Please copy the webhook URL above and add it to your PayFlexi merchant dashboard in the Developer settings page.', 'lifterlms-payflexi-gateway' ),
	'title' 		=> __( 'Webhook URL', 'lifterlms-payflexi-gateway' ),
	'default' 		=>  rest_url( '/llms-payflexi/events' ),
	'type'  		=> 'text',
);


return $fields;
