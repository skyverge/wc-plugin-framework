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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Transactions\Payment-Methods
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2017, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'SV_WC_Payment_Gateway_Transaction_Credit_Card' ) ) :

/**
 * The transaction credit card class.
 *
 * @since 4.7.0-dev
 */
class SV_WC_Payment_Gateway_Transaction_Credit_Card extends SV_WC_Payment_Gateway_Transaction_Payment_Method {


	/** @var array extra data specific to credit cards */
	protected $extra_data = array(
		'exp_month' => '',
		'exp_year'  => '',
		'csc'       => '',
		'card_type' => '',
	);

	/** @var array properties that should never be stored or filtered */
	protected $sensitive_data = array(
		'account_number' => '',
		'csc'            => '',
	);


	/* Getter methods *********************************************************/


	/**
	 * Gets the card number.
	 *
	 * @since 4.7.0-dev
	 *
	 * @return string
	 */
	public function get_card_number() {

		return $this->get_account_number();
	}


	/**
	 * Gets the formatted expiration date.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return string
	 */
	public function get_exp_date( $context = 'view' ) {

		$value =  $this->get_exp_month() . '/' . substr( $this->get_exp_year(), -2 );

		if ( 'view' === $context ) {

			/**
			 * Filters a transaction's credit card expiration date.
			 *
			 * @since 4.7.0-dev
			 *
			 * @param string $exp_date expiration date
			 * @param \SV_WC_Payment_Gateway_Transaction_Credit_Card $payment_method payment method object
			 * @param \SV_WC_Payment_Gateway_Transaction $transaction transaction object
			 */
			$value = apply_filters( $this->get_hook_prefix() . 'exp_date', $value, $this, $this->get_transaction() );

			/**
			 * Filters a transaction's credit card expiration date.
			 *
			 * @since 4.7.0-dev
			 *
			 * @param string $exp_date expiration date
			 * @param \SV_WC_Payment_Gateway_Transaction_Credit_Card $payment_method payment method object
			 * @param \SV_WC_Payment_Gateway_Transaction $transaction transaction object
			 */
			$value = apply_filters( $this->get_gateway_hook_prefix() . 'exp_date', $value, $this, $this->get_transaction() );
		}

		return $value;
	}


	/**
	 * Gets the expiration month.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return string
	 */
	public function get_exp_month( $context = 'view' ) {

		return $this->get_prop( 'exp_month', $context );
	}


	/**
	 * Gets the expiration year.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return string
	 */
	public function get_exp_year( $context = 'view' ) {

		return $this->get_prop( 'exp_year', $context );
	}


	/**
	 * Gets the card CSC.
	 *
	 * @since 4.7.0-dev
	 *
	 * @return string
	 */
	public function get_csc() {

		return $this->get_prop( 'csc', 'edit' );
	}


	/**
	 * Gets the card type name.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return string
	 */
	public function get_card_type_name( $context = 'view' ) {

		$value = SV_WC_Payment_Gateway_Helper::payment_type_to_name( $this->get_card_type() );

		if ( 'view' === $context ) {

			/**
			 * Filters a transaction's credit card type name.
			 *
			 * @since 4.7.0-dev
			 *
			 * @param string $name card type name
			 * @param \SV_WC_Payment_Gateway_Transaction_Credit_Card $payment_method payment method object
			 * @param \SV_WC_Payment_Gateway_Transaction $transaction transaction object
			 */
			$value = apply_filters( $this->get_hook_prefix() . 'card_type_name', $value, $this, $this->get_transaction() );

			/**
			 * Filters a gateway-specific transaction's credit card type name.
			 *
			 * @since 4.7.0-dev
			 *
			 * @param string $name card type name
			 * @param \SV_WC_Payment_Gateway_Transaction_Credit_Card $payment_method payment method object
			 * @param \SV_WC_Payment_Gateway_Transaction $transaction transaction object
			 */
			$value = apply_filters( $this->get_gateway_hook_prefix() . 'card_type_name', $value, $this, $this->get_transaction() );
		}

		return $value;
	}


	/**
	 * Gets the card type.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return string
	 */
	public function get_card_type( $context = 'view' ) {

		return $this->get_prop( 'card_type', $context );
	}


	/**
	 * Gets the payment method type.
	 *
	 * @since 4.7.0-dev
	 *
	 * @return string
	 */
	public function get_type() {

		return 'credit_card'; // TODO: underscore?
	}


	/* Setter methods *********************************************************/


	/**
	 * Sets the card number.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value card number
	 */
	public function set_card_number( $value ) {

		$this->set_account_number( $value );
	}


	/**
	 * Sets the expiration month.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value expiration month
	 */
	public function set_exp_month( $value ) {

		$this->set_prop( 'exp_year', $value );
	}


	/**
	 * Sets the expiration year.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value expiration year
	 */
	public function set_exp_year( $value ) {

		$this->set_prop( 'exp_year', $value );
	}


	/**
	 * Sets the card CSC.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value card CSC
	 */
	public function set_csc( $value ) {

		$this->set_prop( 'csc', $value );
	}


	/**
	 * Sets the card type.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value card type
	 */
	public function set_card_type( $value ) {

		$this->set_prop( 'card_type', $value );
	}


}

endif;
