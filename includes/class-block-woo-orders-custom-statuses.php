<?php
/**
 * Add custom statuses to Woocommerce orders
 *
 * @package    Block_Woo_Orders
 * @subpackage Block_Woo_Orders/includes
 * @author     Jenny Chan <jenny@jennychan.dev>
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Block_Woo_Orders_Custom_Statuses {

	/**
	 * Register custom statuses
	 * @return void
	 */
	public function register_custom_statuses() {
		register_post_status( 'wc-verified', array(
			'label'                     => 'Verified',
			'public'                    => true,
			'show_in_admin_status_list' => true,
			'show_in_admin_all_list'    => true,
			'exclude_from_search'       => false,
			'label_count'               => _n_noop( 'Verified <span class="count">(%s)</span>', 'Verified <span class="count">(%s)</span>' )
		) );

		register_post_status( 'wc-review-required', array(
			'label'                     => 'Review Required',
			'public'                    => true,
			'show_in_admin_status_list' => true,
			'show_in_admin_all_list'    => true,
			'exclude_from_search'       => false,
			'label_count'               => _n_noop( 'Review Required <span class="count">(%s)</span>', 'Review Required <span class="count">(%s)</span>' )
		) );

		register_post_status( 'wc-blocked', array(
			'label'                     => 'Blocked',
			'public'                    => true,
			'show_in_admin_status_list' => true,
			'show_in_admin_all_list'    => true,
			'exclude_from_search'       => false,
			'label_count'               => _n_noop( 'Blocked <span class="count">(%s)</span>', 'Blocked <span class="count">(%s)</span>' )
		) );
	}

	/**
	 * Add custom statuses to orders
	 * @param $order_statuses
	 *
	 * @return array
	 */
	public function add_custom_statuses_to_order( $order_statuses ) {

		$new_order_statuses = array();
		foreach ( $order_statuses as $key => $status ) {
			$new_order_statuses[ $key ] = $status;
			if ( 'wc-processing' === $key ) {
				$new_order_statuses['wc-verified']        = 'Verified';
				$new_order_statuses['wc-review-required'] = 'Review Required';
				$new_order_statuses['wc-blocked']         = 'Blocked';
			}
		}

		return $new_order_statuses;
	}

	/**
	 * Add order statues to Order Post Type
	 *
	 * @param array $statuses
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public function add_new_order_statuses( $statuses, $order ) {
		// Registering the custom status as valid for payment
		$statuses[] = 'review-required';
		$statuses[] = 'verified';

		return $statuses;
	}
}