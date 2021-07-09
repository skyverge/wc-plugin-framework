<?php
/**
 * WooCommerce Payment Gateway Framework
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the plugin to newer
 * versions in the future. If you wish to customize the plugin for your
 * needs please refer to http://www.skyverge.com
 *
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Transactions
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'SV_WC_Payment_Gateway_Transaction' ) ) :

/**
 * The base payment gateway transaction class.
 *
 * @since 4.7.0-dev
 *
 * @see \WC_Data
 */
abstract class SV_WC_Payment_Gateway_Transaction extends WC_Data {


	/** @var string the object type, for storage */
	protected $object_type = 'transaction'; // TODO: conflicts?

	/** @var array the core properties */
	protected $data = array(
		'external_id'        => '',
		'date_created'       => null,
		'status'             => '',
		'amount'             => '0.00',
		'currency'           => '',
		'authorization_code' => '',
		'retry_count'        => 0,
		'parent_id'          => 0,
		'gateway_id'         => '',
		'order_id'           => 0,
		'customer_id'        => '',
		'user_id'            => 0,
		'environment'        => '',
	);


	/* Getter methods *********************************************************/


	/**
	 * Gets the external transaction ID.
	 *
	 * This is usually defined by the gateway, and provides a way for our own
	 * separate database ID to be stored in the future.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return string
	 */
	public function get_external_id( $context = 'view' ) {

		return $this->get_prop( 'external_id', $context );
	}


	/**
	 * Gets the date the transaction was created.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return \WC_DateTime
	 */
	public function get_date_created( $context = 'view' ) {

		return $this->get_prop( 'date_created', $context );
	}


	/**
	 * Gets the status.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return string
	 */
	public function get_status( $context = 'view' ) {

		return $this->get_prop( 'status', $context );
	}


	/**
	 * Gets the amount.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return string
	 */
	public function get_amount( $context = 'view' ) {

		return $this->get_prop( 'amount', $context );
	}


	/**
	 * Gets the currency.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return string
	 */
	public function get_currency( $context = 'view' ) {

		return $this->get_prop( 'currency', $context );
	}


	/**
	 * Gets the authorization code.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return string
	 */
	public function get_authorization_code( $context = 'view' ) {

		return $this->get_prop( 'authorization_code', $context );
	}


	/**
	 * Gets the retry count.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return int
	 */
	public function get_retry_count( $context = 'view' ) {

		return $this->get_prop( 'retry_count', $context );
	}


	/**
	 * Gets the parent transaction ID.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return int
	 */
	public function get_parent_id( $context = 'view' ) {

		return $this->get_prop( 'parent_id', $context );
	}


	/**
	 * Gets the payment gateway ID.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return string
	 */
	public function get_gateway_id( $context = 'view' ) {

		return $this->get_prop( 'gateway_id', $context );
	}


	/**
	 * Gets the ID for the order that created this transaction.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return int
	 */
	public function get_order_id( $context = 'view' ) {

		return $this->get_prop( 'order_id', $context );
	}


	/**
	 * Gets the customer ID.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return string
	 */
	public function get_customer_id( $context = 'view' ) {

		return $this->get_prop( 'customer_id', $context );
	}


	/**
	 * Gets the user ID, if any.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return int
	 */
	public function get_user_id( $context = 'view' ) {

		return $this->get_prop( 'user_id', $context );
	}


	/**
	 * Gets the payment gateway environment.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return string
	 */
	public function get_environment( $context = 'view' ) {

		return $this->get_prop( 'environment', $context );
	}


	/**
	 * Gets a transaction property.
	 *
	 * @since 4.7.0-dev
	 *
	 * @see \WC_Data::get_prop()
	 *
	 * @param string $prop transaction property to get
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return mixed
	 */
	protected function get_prop( $prop, $context = 'view' ) {

		// Let WC core do its base filtering
		$value = parent::get_prop( $prop, $context );

		if ( 'view' === $context && $this->get_gateway_id() ) {

			/**
			 * Filters a transaction property in the 'view' context.
			 *
			 * @since 4.7.0-dev
			 *
			 * @param mixed $value transaction property value
			 * @param \SV_WC_Payment_Gateway_Transaction $transaction transaction object
			 */
			$value = apply_filters( $this->get_gateway_hook_prefix() . $prop, $value, $this );
		}

		return $value;
	}


	/**
	 * Gets the hook prefix specific to this transaction's gateway ID.
	 *
	 * i.e. 'wc_authorize_net_cim_transaction_get_status'
	 *
	 * @since 4.7.0-dev
	 *
	 * @return string
	 */
	protected function get_gateway_hook_prefix() {

		return 'woocommerce_' . $this->get_gateway_id() . '_' . $this->object_type . '_get_';
	}


	/**
	 * Gets the transaction type.
	 *
	 * @since 4.7.0-dev
	 *
	 * @return string
	 */
	abstract public function get_type();


	/**
	 * Gets the description.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return string
	 */
	abstract public function get_description( $context = 'view' );


	/* Setter methods *********************************************************/


	/**
	 * Sets the external ID.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value external ID
	 */
	public function set_external_id( $value ) {

		$this->set_prop( 'external_id', $value );
	}


	/**
	 * Sets the date the transaction was created.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value date created
	 */
	public function set_date_created( $value ) {

		$this->set_prop( 'date_created', $value );
	}


	/**
	 * Sets the status.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value status
	 */
	public function set_status( $value ) {

		$this->set_prop( 'status', $value );
	}


	/**
	 * Sets the amount.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string|float|int $value dollar amount
	 */
	public function set_amount( $value ) {

		$this->set_prop( 'amount', SV_WC_Helper::number_format( $value ) );
	}


	/**
	 * Sets the currency code.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value currency code
	 */
	public function set_currency( $value ) {

		if ( $value && ! in_array( $value, array_keys( get_woocommerce_currencies() ), true ) ) {
			$this->error( 'sv_wc_transaction_invalid_currency', __( 'Invalid currency code', 'woocommerce-plugin-framework' ) );
		}

		$this->set_prop( 'currency', $value );
	}


	/**
	 * Sets the authorization code.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value authorization code
	 */
	public function set_authorization_code( $value ) {

		$this->set_prop( 'authorization_code', $value );
	}


	/**
	 * Sets the retry count.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value retry count
	 */
	public function set_retry_count( $value ) {

		$this->set_prop( 'retry_count', (int) $value );
	}


	/**
	 * Sets the parent transaction ID.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value parent transaction ID
	 */
	public function set_parent_id( $value ) {

		// TODO: validate this ID when/if we end up storing transactions

		$this->set_prop( 'parent_id', (int) $value );
	}


	/**
	 * Sets the payment gateway ID.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value payment gateway ID
	 */
	public function set_gateway_id( $value ) {

		// TODO: validate?

		$this->set_prop( 'gateway_id', $value );
	}


	/**
	 * Sets the order ID.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param int $value WooCommerce order ID
	 */
	public function set_order_id( $value ) {

		if ( $value && ! wc_get_order( $value ) ) {
			$this->error( 'sv_wc_transaction_invalid_order_id', __( 'Invalid order ID', 'woocommerce-plugin-framework' ) );
		}

		$this->set_prop( 'order_id', (int) $value );
	}


	/**
	 * Sets the customer ID.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value customer ID
	 */
	public function set_customer_id( $value ) {

		$this->set_prop( 'customer_id', $value );
	}


	/**
	 * Sets the user ID.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param int $value WordPress user ID
	 */
	public function set_user_id( $value ) {

		if ( $value && ! get_userdata( $value ) ) {
			$this->error( 'sv_wc_transaction_invalid_user_id', __( 'Invalid user ID', 'woocommerce-plugin-framework' ) );
		}

		$this->set_prop( 'user_id', (int) $value );
	}


	/**
	 * Sets the gateway environment.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value gateway environment slug/ID
	 */
	public function set_environment( $value ) {

		$this->set_prop( 'environment', $value );
	}


	/* CRUD methods ***********************************************************/


	public function save() {

		if ( $order_id = $this->get_order_id() ) {

			foreach ( $thi->get_meta_data_to_save() as $key => $value ) {
				update_post_meta( $order_id, $this->get_meta_prefix() . $key, $value );
			}
		}
	}


	protected function get_meta_data_to_save() {

		return array(
			'trans_id'     => $this->get_external_id( 'edit' ),
			'trans_date'   => $this->get_date_created( 'edit' ),
			'trans_status' => $this->get_status( 'edit' ),
			'type'         => $this->get_type( 'edit' ),
			'amount'       => $this->get_amount( 'edit' ),
			'auth_code'    => $this->get_authorization_code( 'edit' ),
			'customer_id'  => $this->get_customer_id( 'edit' ),
			'retry_count'  => $this->get_retry_count( 'edit' ),
			'environment'  => $this->get_environment( 'edit' ),
		);
	}


	protected function get_meta_prefix() {

		return '_wc_' . $this->get_gateway_id() . '_' . $this->get_type() . '_';
	}


}

endif;
