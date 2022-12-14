<?php

/**
 * All the functions related to Woocommerce hooks
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Block_Woo_Orders
 * @subpackage Block_Woo_Orders/admin
 * @author     Jenny Chan <jenny@jennychan.dev>
 */
class Block_Woo_Orders_Woocommerce_Hooks {

	/**
	 * @param int $order_id
	 * @param array $posted_data
	 * @param WC_Order $order
	 *
	 * @return void
	 * @throws Exception
	 */
	public function scan_orders_for_fraud( $order_id, $posted_data, $order ) {

		if ( empty( get_option( 'bwo_scan_enabled' ) ) ) {
			return;
		}

		global $wpdb;

		// fetch data
		$app_user_id_table = $wpdb->prefix . 'bwo_app_user_ids';
		$email_table       = $wpdb->prefix . 'bwo_emails';

		$app_user_id = $order->get_meta( 'app_user_id' );
		$email       = $order->get_billing_email();

		$app_user_id_query = $wpdb->prepare( "SELECT * FROM $app_user_id_table WHERE app_user_id = %s", $app_user_id );
		$app_user_id_row   = $wpdb->get_row( $app_user_id_query, ARRAY_A );

		$email_query = $wpdb->prepare( "SELECT * FROM $email_table WHERE email = %s", $email );
		$email_row   = $wpdb->get_row( $email_query, ARRAY_A );

		$is_verified     = false;
		$is_blocked      = false;
		$review_required = false;

		$rows_to_check = array(
			'app_user_id' => $app_user_id_row,
			'email'       => $email_row
		);

		$scan_result = [];

		foreach ( $rows_to_check as $key => $row ) {
			if ( ! empty( $row ) ) {
				if ( $row['flag'] === "verified" ) {
					$is_verified = true;
				} else if ( $row['flag'] === "review" ) {
					$review_required = true;
				} else if ( $row['flag'] === "blocked" ) {
					$is_blocked = true;
				}

				$scan_result[ $key ] = [
					'flag'  => $row['flag'],
					'notes' => $row['notes']
				];
			}
		}

		if ( ! empty( $scan_result ) ) {
			update_post_meta( $order_id, 'scan_result', json_encode( $scan_result ) );
		}

		if ( $is_blocked ) {
			$order->update_status( 'blocked' );
			throw new Exception( __( 'Your email has been blacklisted from ordering. Please reach out to <a href="mailto:support@example.com">support@example.com</a> if this was an error.', 'block-woo-orders' ) );
		} else if ( $review_required && ! $is_verified ) {
			$order->update_status( 'review-required' );
		} else if ( $is_verified ) {
			$order->update_status( 'verified' );
		}
	}

	/**
	 * Add custom field app_user_id to checkout page
	 * @param $checkout
	 *
	 * @return void
	 */
	public function add_app_user_id_field( $checkout ) {
		woocommerce_form_field( 'app_user_id', array(
			'type'        => 'text',
			'class'       => array( 'app-user-id form-row-wide' ),
			'label'       => __( 'Your App User ID' ),
			'placeholder' => __( 'App User ID' ),
			'required'    => true,
		), $checkout->get_value( 'app_user_id' ) );
	}

	/**
	 * Verify app_user_id field is filled upon checkout
	 *
	 * @return void
	 */
	public function verify_app_user_id_field() {
		if ( ! $_POST['app_user_id'] || empty( $_POST['app_user_id'] ) ) {
			wc_add_notice( __( 'Please enter an App User ID.' ), 'error' );
		}
	}

	/**
	 * Update order meta app_user_id if it's not empty
	 *
	 * @param $order_id
	 *
	 * @return void
	 */
	public function update_order_meta_app_user_id( $order_id ) {
		if ( ! empty( $_POST['app_user_id'] ) ) {
			update_post_meta( $order_id, 'app_user_id', sanitize_text_field( $_POST['app_user_id'] ) );
		}
	}

	/**
	 * Show app_user_id in Edit Order
	 * @param $order
	 *
	 * @return void
	 */
	public function display_admin_order_meta_app_user_id( $order ) {
		echo '<p><strong>' . __( 'App User ID', 'woocommerce' ) . ':</strong> ' . $order->get_meta( 'app_user_id' ) . '</p>';
	}
}