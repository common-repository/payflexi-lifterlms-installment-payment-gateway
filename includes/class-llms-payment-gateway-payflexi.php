<?php
/**
 * Main Payment Gateway class file
 *
 * This class is the main payment gateway class that extends the core LLMS_Payment_Gateway abstract
 *
 * @package LifterLMS_Payflexi_Gateway/Classes
 *
 * @since 2021-02-18
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_Payment_Gateway_Payflexi
 *
 * @since 1.0.0
 */
class LLMS_Payment_Gateway_Payflexi extends LLMS_Payment_Gateway {

	protected $checkbox_option = '';

	protected $select_option = '';

	protected $live_public_api_key = '';

	protected $live_secret_api_key = '';

	protected $test_public_api_key = '';

	protected $test_secret_api_key = '';

	protected $enabled_payment_gateway = '';

	/**
	 * Constructor
	 *
	 * This method will configure class variables and attach all necessary hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function __construct() {

		$this->configure_variables();

		add_filter('llms_get_gateway_settings_fields', array( $this, 'add_settings_fields' ), 10, 2 );
		add_action( 'lifterlms_before_view_order_table', array( $this, 'before_view_order_table' ) );

		add_filter( 'lifterlms_register_order_post_statuses', array( $this, 'add_new_llms_order_status' ));
	}

	/**
	 * Output custom settings fields on the LifterLMS Gateways Screen
	 * @since 1.0.0
	 */
	public function add_settings_fields( $default_fields, $gateway_id ) {
		if ( $this->id === $gateway_id ) {
			$fields = include LLMS_PAYFLEXI_GATEWAY_PLUGIN_DIR . 'includes/admin/settings-llms-payflexi-gateway.php';

			$default_fields = array_merge( $default_fields, $fields );

		}

		return $default_fields;
	}

	/**
	 * Define class variables
	 * 
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function configure_variables() {

		$this->id = 'payflexi';
		$this->title = _x( 'Pay in Installment', 'Gateway title', 'lifterlms-payflexi-gateway' );
		$this->description = __( 'Pay in Flexible Installment using PayFlexi', 'lifterlms-payflexi-gateway' );
		$this->admin_title = _x( 'PayFlexi', 'Gateway admin title', 'lifterlms-payflexi-gateway' );
		$this->admin_description = __( 'Allow customers to purchase courses and memberships in flexible Installment or full using PayFlexi.', 'lifterlms-payflexi-gateway' );
		$this->icon = '<img src="' . plugins_url( 'assets/img/icon.png', LLMS_PAYFLEXI_GATEWAY_PLUGIN_FILE ) . '" alt="' . __( 'PayFlexi Flexible Checkout', 'lifterlms-payflexi-gateway' ) . '">';
		$this->test_mode_title = __( 'Test Mode', 'lifterlms-payflexi-gateway' );
		$this->test_mode_description = sprintf(
			__( 'Test Mode can be used to process test transactions. %1$sLearn More.%2$s', 'lifterlms-payflexi-gateway' ),
			'<a href="https://developers.payflexi.co">', 
			'</a>'
		);

		$this->supports = array(
			'checkout_fields'    => false,
			'single_payments'    => true,
			'test_mode'          => true,
		);

		$this->admin_order_fields = wp_parse_args(
			array(
				'customer'     => true,
				'source'       => true,
				'subscription' => false,
			),
			$this->admin_order_fields
		);
	}
	/**
	 * Output payment instructions if the order is pending
	 *
	 * @return   void
	 * @since    1.0.0
	 * @version  1.0.0
	 */
	public function before_view_order_table() {

		global $wp;

		if ( ! empty( $wp->query_vars['orders'] ) ) {

			$order = new LLMS_Order( intval( $wp->query_vars['orders'] ) );

			if ( 'payflexi' === $order->get( 'payment_gateway' ) && in_array( $order->get( 'status' ), array( 'llms-pending', 'llms-on-hold' ) ) ) {

				echo $this->get_payment_instructions();

			}
		}

	}

	/**
	 * Get fields displayed on the checkout form
	 *
	 * @return   string
	 * @since    1.0.0
	 * @version  1.0.0
	 */
	public function get_payment_instructions() {
		$opt = $this->get_option( 'payment_instructions' );
		if ( $opt ) {
			$fields = '<div class="llms-notice llms-info"><h3>' . esc_html__( 'Payment Instructions', 'lifterlms' ) . '</h3>' . wpautop( wptexturize( wp_kses_post( $opt ) ) ) . '</div>';
		} else {
			$fields = '';
		}
		return apply_filters( 'llms_get_payment_instructions', $fields, $this->id );
	}

	/**
	 * Handle the redirect callback from PayFlexi Gateway
	 *
	 * @since    1.0.0
	 * @version  1.0.0
	 */
	public function handle_gateway_requests(){

		if ( ! is_llms_checkout() ) {
			return;
		}
		
		// It's technically possible for a user to clear this, they shouldn't, but if they do you'll throw errors below if we don't check to ensure it exists.
		$confirmation_slug = get_option( 'lifterlms_myaccount_confirm_payment_endpoint' );
		if ( ! $confirmation_slug ) {
			return;
		}

		global $wp_query;
		// If the endpoint slug isn't found in the query vars then we're on the regular checkout page, not the checkout confirmation page.
		if ( ! isset( $wp_query->query[ $confirmation_slug ] ) ) {
			return;
		}

		// Make sure we can verify the order. This assumes you've created a redirect link which contains the order key in the url's query string.
		$order_key = llms_filter_input( INPUT_GET, 'order', FILTER_SANITIZE_STRING );
		
		// Bail if we don't have an order.
		if ( ! $order_key ) {
			return;
		}

		$order = llms_get_order_by_key( $order_key );

		// No order found for the given key, bail.
		if ( ! $order ) {
			return;
		}

		// Verify the order uses your payment gateway.
		if ( 'payflexi' !== $order->get( 'payment_gateway' ) ) {
			return;
		}

		if (isset( $_GET['order'] ) && isset($_GET['pf_cancelled'])) {
			llms_add_notice('Transaction cancelled', 'error' );
			wp_redirect(llms_get_page_url('checkout', ['plan' => $order->plan_id]));
			die();
		}

		if (isset( $_GET['order'] ) && isset($_GET['pf_declined'])) {
			llms_add_notice('Transaction declined', 'error' );
			wp_redirect(llms_get_page_url('checkout', ['plan' => $order->plan_id]));
			die();
		}

		if (isset( $_GET['order'] ) && isset($_GET['pf_approved'])) {

			$payment_reference = sanitize_text_field($_GET['pf_approved']);

			$currency = $order->get( 'currency' );

			$order_id = $order->get( 'id' );

			$user_id = $order->get( 'user_id' );

			$product_id = $order->get( 'product_id' );

			$transaction = $this->payflexi_verify_transaction($payment_reference);

			if (!$transaction->errors && 'approved' == $transaction->data->status) {

				$order_amount = $transaction->data->amount;

                $amount_paid = $transaction->data->txn_amount;

				if ( $amount_paid < $order_amount ) {
                    add_post_meta( $order_id, '_llms_payflexi_transaction_id', $payment_reference, true );
                    update_post_meta( $order_id, '_llms_payflexi_order_amount', $order_amount);
                    update_post_meta( $order_id, '_llms_payflexi_installment_amount_paid', $amount_paid);
                    
					$txn_data = array();
					$txn_data['amount'] = $amount_paid;
					$txn_data['transaction_id'] = $payment_reference;
					$txn_data['status'] = 'llms-txn-succeeded';
					$txn_data['payment_type'] = 'single';
					$txn_data['source_description'] = __( 'PayFlexi Flexible Checkout Payment', 'lifterlms-payflexi' );

					$txn = $order->record_transaction( $txn_data );
					
					$note = 'This course is partially paid with ' . $txn->get_price($amount_paid) . ' PayFlexi Transaction Reference: ' . $payment_reference;
                    $order->add_note( $note );
					$order->set_status('pending');

					llms_unenroll_student($user_id, $product_id, 'cancelled');

					llms_add_notice('Partial Payment Successful', 'success' );
					llms_redirect_and_exit(get_permalink( llms_get_page_id( 'myaccount' ) ));
                
				} else {

                    $txn_data = array();
					$txn_data['amount'] = $amount_paid;
					$txn_data['transaction_id'] = $payment_reference;
					$txn_data['status'] = 'llms-txn-succeeded';
					$txn_data['payment_type'] = 'single';
					$txn_data['source_description'] = __('One-time payment via PayFlexi', 'lifterlms-payflexi' );

					$order->record_transaction( $txn_data );

                    $note = 'Payment transaction was successful. PayFlexi Transaction Reference: ' . $payment_reference;
                    $order->add_note( $note );
					$order->set_status('completed');

					llms_enroll_student($user_id, $product_id);

					$this->complete_transaction( $order );

                }
			}else {
				llms_add_notice('Payment failed', 'error' );
				wp_redirect(llms_get_page_url('checkout', ['plan' => $order->plan_id]));
            }
		}
	}


	public function payflexi_gateway_response($payflexi_data){

		$payflexi_url = 'https://api.payflexi.co/merchants/transactions';

        if (llms_payflexi_gateway()->get_gateway()->is_test_mode_enabled()) {
            $secret_key = trim($this->get_option('test_secret_api_key'));
        } else {
            $secret_key = trim($this->get_option('live_secret_api_key'));
        }

		$enabled_payment_gateway = trim($this->get_option('enabled_payment_gateway'));

		$plans_disabled = trim($this->get_option('disable_payment_plans'));

        $headers = array(
            'Authorization' => 'Bearer ' . $secret_key,
            'Content-Type' =>  'application/json',
            'Accept' =>  'application/json'
        );

        $body = array(
            'name'         => $payflexi_data['name'],
            'amount'       => $payflexi_data['amount'],
            'email'        => $payflexi_data['email'],
            'reference'    => $payflexi_data['reference'],
            'currency'     => $payflexi_data['currency'],
            'callback_url' => $payflexi_data['callback_url'],
            'domain'       => 'global',
			'gateway'	   => $enabled_payment_gateway,
			'plans_enabled' => $plans_disabled ? false: true,
            'meta' 		   => $payflexi_data['meta']
        );

        $args = array(
            'body'    => json_encode($body),
            'headers' => $headers,
            'sslverify' => false,
            'timeout' => 60,
        );

        $request = wp_remote_post( $payflexi_url, $args );

        if ( ! is_wp_error( $request ) && 200 === wp_remote_retrieve_response_code( $request ) ) {
            $payflexi_response = json_decode( wp_remote_retrieve_body( $request ) );
        } else {
            $payflexi_response = json_decode( wp_remote_retrieve_body( $request ) );
        }
        return $payflexi_response;
	}

	/**
	 * Handle a "pending" order
	 *
	 * @since 1.0.0
	 *
	 * @param LLMS_Order        $order   Order object.
	 * @param LLMS_Access_Plan  $plan    Access plan object.
	 * @param LLMS_Student      $student Student object.
	 * @param LLMS_Coupon|false $coupon  Coupon object when a coupon has been applied, otherwise `false.
	 * @return null|void
	 */
	public function handle_pending_order( $order, $plan, $student, $coupon = false ) {
		$this->log( 'PayFlexi Gateway `handle_pending_order()` started', $order, $plan, $student, $coupon );

		$first_name = $order->get( 'billing_first_name' );
		$last_name = $order->get( 'billing_last_name' );
		$full_name = $first_name . ' ' . $last_name;
		$order_id = $order->get( 'id' );
		$order_key = $order->get( 'order_key' );
		$product_title = sprintf( '%s: %s', ucwords( $order->get( 'product_type' ) ), $order->get( 'product_title' ) );

		$payflexi_data = array();

		$payflexi_data['name']      	= $full_name ? $full_name : $student->get( 'display_name' );
		$payflexi_data['amount']    	= $order->get_price( 'total', array(), 'float' );
		$payflexi_data['currency']		= $order->get( 'currency' );
		$payflexi_data['email']     	= $student->get( 'email' ) ? $student->get( 'email' ) : $order->get( 'billing_email' );
		$payflexi_data['reference'] 	= 'LLMS-' . $order_id . '-' . uniqid();
		$payflexi_data['callback_url'] 	= llms_confirm_payment_url( $order->get( 'order_key' ) );
		$payflexi_data['meta']   		= array(
											'title' => $product_title,
											'order_id' => $order->get( 'id' ), 
											'order_key' => $order->get( 'order_key' ),
											'course_plan' => $order->get( 'plan_title' ) 
										);

		$get_payment_response = $this->payflexi_gateway_response( $payflexi_data );

		if (!$get_payment_response->errors) {
			do_action( 'lifterlms_handle_pending_order_complete', $order );
			wp_redirect($get_payment_response->checkout_url);
			exit();
		} else {
			$this->log( 'PayFlexi Gateway `handle_pending_order()` ended with api request errors', $get_payment_response->message);
			llms_add_notice( $get_payment_response->message, 'error' );
			wp_redirect(llms_get_page_url('checkout', ['plan' => $plan->get( 'id' )]));
			exit();
		}
	}

	/**
	 * Verify approved transaction from PayFlexi API
	 *
	 * @since 1.0.0
	 * @return void
	 */
    public function payflexi_verify_transaction($payment_reference) {

        $payflexi_url = 'https://api.payflexi.co/merchants/transactions/' . $payment_reference;

        if (llms_payflexi_gateway()->get_gateway()->is_test_mode_enabled()) {
            $secret_key = trim($this->get_option('test_secret_api_key'));
        } else {
            $secret_key = trim($this->get_option('live_secret_api_key'));
        }

        $headers = array(
            'Authorization' => 'Bearer ' . $secret_key,
        );

        $args = array(
            'sslverify' => false, //Set to true on production
            'headers' => $headers,
            'timeout' => 60,
        );

        $request = wp_remote_get( $payflexi_url, $args );

        if ( ! is_wp_error( $request ) && 200 === wp_remote_retrieve_response_code( $request ) ) {
            $payflexi_response = json_decode( wp_remote_retrieve_body( $request ) );
        } else {
            $payflexi_response = json_decode( wp_remote_retrieve_body( $request ) );

        }

        return $payflexi_response;
    }

	 /**
	 * Register a new Order status
	 *
	 * @since 1.0.0
	 * @return array
	 */
    public function add_new_llms_order_status( $order_statuses ) {

		$order_statuses['llms-partial'] = array(
			'label'       => _x( 'Partially Paid', 'Order status', 'lifterlms' ),
			'label_count' => _n_noop( 'Partially Paid <span class="count">(%s)</span>', 'Partially Paid <span class="count">(%s)</span>', 'lifterlms' ),
		);

        return $order_statuses;   
    }

}
