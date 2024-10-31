<?php
/**
 * Manage incoming webhooks from PayFlexi.
 *
 * @package  LifterLMS_Payflexi/Classes
 *
 * @since 1.0.0
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_PayfFlexi_Events_Controller class.
 *
 * @since 1.0.0
 */
class LLMS_Payflexi_Events_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'llms-payflexi';

	/**
	 * Base Resource.
	 *
	 * @var string
	 */
	protected $rest_base = 'events';

	/**
	 * Register routes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'receive_event' ),
				),
			)
		);

	}

    /**
	 * Handle incoming webhook events.
	 *
	 * @since 1.0.0
	 *
	 * @param obj $request PayFlexi event object.
	 * @return WP_REST_Response
	 */
	public function receive_event( $request ) {

        $gateway = llms_payflexi_gateway()->get_gateway();

        if ((strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') || ! array_key_exists('HTTP_X_PAYFLEXI_SIGNATURE', $_SERVER)) {
            exit;
        }

		$body  = @file_get_contents( 'php://input' );

        if ($gateway->is_test_mode_enabled()) {
            $secret_key = trim($gateway->get_option('test_secret_api_key'));
        } else {
            $secret_key = trim($gateway->get_option('live_secret_api_key'));
        }

        if ($_SERVER['HTTP_X_PAYFLEXI_SIGNATURE'] !== hash_hmac('sha512', $body, $secret_key)) {
            exit;
        }

        $event = json_decode( $body );

		if ('transaction.approved' == $event->event && 'approved' == $event->data->status) {
			http_response_code( 200 );

            $reference = $event->data->reference;
			$initial_reference = $event->data->initial_reference;

			$order_info = explode( '-', $initial_reference );

            $order_id = $order_info[1];

			$order = new LLMS_Order( $order_id );

			$user_id = $order->get( 'user_id' );

			$product_id = $order->get( 'product_id' );

			$currency = $order->get( 'currency' );

            $order_amount = get_post_meta($order_id, '_llms_payflexi_order_amount', true);

			$saved_txn_ref = get_post_meta($order_id, '_llms_payflexi_transaction_id', true);

            $order_amount  = $order_amount ? $order_amount : $event->data->amount;

            $amount_paid  = $event->data->txn_amount ? $event->data->txn_amount : 0;

            $payflexi_txn_ref = $event->data->reference;

			if ( $amount_paid < $order_amount ) {

				if($reference === $initial_reference && (!$saved_txn_ref || empty($saved_txn_ref))){
                    add_post_meta( $order_id, '_llms_payflexi_transaction_id', $initial_reference, true );
                    update_post_meta( $order_id, '_llms_payflexi_order_amount', $order_amount);
                    update_post_meta( $order_id, '_llms_payflexi_installment_amount_paid', $amount_paid);
                    
					$txn_data = array();
					$txn_data['amount'] = $amount_paid;
					$txn_data['transaction_id'] = $payflexi_txn_ref;
					$txn_data['status'] = 'llms-txn-succeeded';
					$txn_data['payment_type'] = 'single';
					$txn_data['source_description'] = __( 'PayFlexi Flexible Checkout Payment', 'lifterlms-payflexi' );

					$order->record_transaction( $txn_data );
					
					$note = 'This order is currently was partially paid with ' . $order->get_price($amount_paid, array('currency' => $currency), 'float')  . ' PayFlexi Transaction Reference: ' . $payflexi_txn_ref;
					$order->add_note( $note );
					$order->set_status('pending');

					llms_unenroll_student($user_id, $product_id, 'cancelled');
                }

				if($reference !== $initial_reference && (!$saved_txn_ref || !empty($saved_txn_ref))){

                    $installment_amount_paid = get_post_meta($order_id, '_llms_payflexi_installment_amount_paid', true);
                    $total_installment_amount_paid = $installment_amount_paid + $amount_paid;
                    
					if($total_installment_amount_paid >= $order_amount){

                        update_post_meta($order_id, '_llms_payflexi_installment_amount_paid', $total_installment_amount_paid);

						$txn_data = array();
						$txn_data['amount'] = $amount_paid;
						$txn_data['transaction_id'] = $payflexi_txn_ref;
						$txn_data['status'] = 'llms-txn-succeeded';
						$txn_data['payment_type'] = 'single';
						$txn_data['source_description'] = __( 'PayFlexi (Pay in Installment)', 'lifterlms-payflexi' );
	
						$order->record_transaction( $txn_data );

                        $note = 'The last partial payment of ' . $order->get_price($amount_paid, array('currency' => $currency), 'float') . ' PayFlexi Transaction Reference: ' . $payflexi_txn_ref;
						$order->add_note( $note );
						$order->set_status( 'completed' );

						llms_enroll_student($user_id, $product_id);

                    }else{

                        update_post_meta( $order_id, '_llms_payflexi_installment_amount_paid', $total_installment_amount_paid);

						$txn_data = array();
						$txn_data['amount'] = $amount_paid;
						$txn_data['transaction_id'] = $payflexi_txn_ref;
						$txn_data['status'] = 'llms-txn-succeeded';
						$txn_data['payment_type'] = 'single';
						$txn_data['source_description'] = __( 'PayFlexi (Pay in Installment)', 'lifterlms-payflexi' );
	
						$txn = $order->record_transaction( $txn_data );

                        $note = 'This order is currently was partially paid with ' . $txn->get_price($amount_paid) . ' PayFlexi Transaction Reference: ' . $payflexi_txn_ref;
						$order->add_note( $note );
						$order->set_status( 'pending' );

						llms_unenroll_student($user_id, $product_id, 'cancelled');

                    }

                }

			}else{
				
				$txn_data = array();
				$txn_data['amount'] = $amount_paid;
				$txn_data['transaction_id'] = $payflexi_txn_ref;
				$txn_data['status'] = 'llms-txn-succeeded';
				$txn_data['payment_type'] = 'single';
				$txn_data['source_description'] = __( 'PayFlexi (Pay in Installment)', 'lifterlms-payflexi' );

				$txn = $order->record_transaction( $txn_data );

				$note = 'This order was completely paid with ' . $txn->get_price($amount_paid) . ' PayFlexi Transaction Reference: ' . $payflexi_txn_ref;
				$order->add_note( $note );
				$order->set_status('completed');

				llms_enroll_student($user_id, $product_id);

				$gateway->complete_transaction( $order );
			}
		}

		exit;
	}

}
