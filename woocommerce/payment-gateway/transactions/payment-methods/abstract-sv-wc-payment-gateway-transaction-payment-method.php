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
 * @copyright Copyright (c) 2013-2017, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'SV_WC_Payment_Gateway_Transaction_Payment_Method' ) ) :

/**
 * The base gateway transaction payment method class.
 *
 * @since 4.7.0-dev
 */
abstract class SV_WC_Payment_Gateway_Transaction_Payment_Method {


	/** @var array payment method properties */
	protected $data = array(
		'account_number' => '',
		'last_four'      => '',
		'nonce'          => '',
	);

	/** @var array extra data specific to this payment method type */
	protected $extra_data = array();

	/** @var array properties that should never be stored or filtered */
	protected $sensitive_data = array(
		'account_number' => '',
	);

	/** @var \SV_WC_Payment_Gateway_Payment_Token the token associated with this method */
	protected $token;

	/** @var string \SV_WC_Payment_Gateway_Transaction transaction object */
	protected $transaction;


	/**
	 * Constructs the class.
	 *
	 * @since 4.7.0-dev
	 */
	public function __construct() {

		$this->data = array_merge( $this->data, $this->extra_data );
	}


	/* Getter methods *********************************************************/


	/**
	 * Gets the token associated with this method.
	 *
	 * @since 4.7.0-dev
	 *
	 * @return \SV_WC_Payment_Gateway_Payment_Token|null
	 */
	public function get_token() {

		return $this->token;
	}


	/**
	 * Gets the method's account number.
	 *
	 * @since 4.7.0-dev
	 *
	 * @return string
	 */
	public function get_account_number() {

		return $this->get_prop( 'account_number', 'edit' );
	}


	/**
	 * Gets the last four characters of the account number.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return string
	 */
	public function get_last_four( $context = 'view' ) {

		$last_four = $this->get_prop( 'last_four', $context );

		if ( ! $last_four && $account_number = $this->get_account_number() ) {

			$last_four = substr( $account_number, -4 );

			$this->set_last_four( $last_four );
		}

		return $last_four;
	}


	/**
	 * Gets the nonce.
	 *
	 * This can be a token set by gateways that implement client-side JS
	 * tokenization to keep the method's sensitive details off the server.
	 *
	 * @since 4.7.0-dev
	 *
	 * @return string
	 */
	public function get_nonce() {

		return $this->get_prop( 'nonce', 'edit' );
	}


	/**
	 * Gets a property.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $prop property to get
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return mixed
	 */
	protected function get_prop( $prop, $context = 'view' ) {

		$value = null;

		$token = $this->get_token();

		if ( $token && is_callable( array( $token, "get_{$prop}" ) ) ) {
			$value = $token->{"get_{$prop}"}();
		} elseif ( isset( $this->data[ $prop ] ) ) {
			$value = $this->data[ $prop ];
		}

		if ( $value && 'view' === $context && ! in_array( $prop, $this->sensitive_data, true ) ) {

			/**
			 * Filters the payment method property.
			 *
			 * @since 4.7.0-dev
			 *
			 * @param mixed $value property value
			 * @param \SV_WC_Payment_Gateway_Transaction_Payment_Method $payment_method payment method object
			 * @param \SV_WC_Payment_Gateway_Transaction $transaction transaction object
			 */
			$value = apply_filters( $this->get_hook_prefix() . $prop, $value, $this, $this->get_transaction() );

			/**
			 * Filters the payment method property.
			 *
			 * @since 4.7.0-dev
			 *
			 * @param mixed $value property value
			 * @param \SV_WC_Payment_Gateway_Transaction_Payment_Method $payment_method payment method object
			 * @param \SV_WC_Payment_Gateway_Transaction $transaction transaction object
			 */
			$value = apply_filters( $this->get_gateway_hook_prefix() . $prop, $value, $this, $this->get_transaction() );
		}

		return $value;
	}


	/**
	 * Gets the hook prefix for filtering property values.
	 *
	 * @since 4.7.0-dev
	 *
	 * @return string
	 */
	protected function get_hook_prefix() {

		return 'woocommerce_transaction_' . $this->get_type() . '_get_';
	}


	/**
	 * Gets the gateway-specific hook prefix for filtering property values.
	 *
	 * @since 4.7.0-dev
	 *
	 * @return string
	 */
	protected function get_gateway_hook_prefix() {

		return 'wc_' . $this->get_transaction()->get_gateway_id() . '_transaction_' . $this->get_type() . '_get_';
	}


	/**
	 * Gets the transaction associated with this payment method.
	 *
	 * @since 4.7.0-dev
	 *
	 * @return \SV_WC_Payment_Gateway_Transaction
	 */
	protected function get_transaction() {

		return $this->transaction;
	}


	/**
	 * Gets the payment method type.
	 *
	 * @since 4.7.0-dev
	 *
	 * @return string
	 */
	abstract public function get_type();


	/* Setter methods *********************************************************/


	/**
	 * Sets the token associated with this method.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param \SV_WC_Payment_Gateway_Payment_Token $token payment method token object
	 */
	public function set_token( SV_WC_Payment_Gateway_Payment_Token $token ) {

		$this->token = $token;
	}


	/**
	 * Sets the account number.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value account number
	 */
	public function set_account_number( $value ) {

		$this->set_prop( 'account_number', $value );
	}


	/**
	 * Sets the nonce.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value nonce
	 */
	public function set_nonce( $value ) {

		$this->set_prop( 'nonce', $value );
	}


	/**
	 * Sets the last four characters of the account number.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value last four characters of the account number
	 */
	public function set_last_four( $value ) {

		$this->set_prop( 'last_four', $value );
	}


	/**
	 * Sets a property.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $prop the property to set
	 * @param string|array $value the property value
	 */
	protected function set_prop( $prop, $value ) {

		$token = $this->get_token();

		if ( $token && is_callable( array( $token, "set_{$prop}" ) ) ) {
			$token->{"set_{$prop}"}( $value );
		} elseif ( array_key_exists( $prop, $this->data ) ) {
			$this->data[ $prop ] = $value;
		}
	}


	/**
	 * Sets the transaction object.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param \SV_WC_Payment_Gateway_Transaction $transaction transaction object
	 */
	public function set_transaction( $transaction ) {

		$this->transaction = $transaction;
	}


	/* CRUD methods ***********************************************************/


	public function save( $order_id ) {

		foreach ( $this->data as $key => $value ) {

			if ( ! in_array( $key, $this->sensitive_data, true ) ) {
				update_post_meta( $order_id, '_wc_' . $this->get_transaction()->get_gateway_id() . '_' . $key, $value );
			}
		}
	}


}

endif;
