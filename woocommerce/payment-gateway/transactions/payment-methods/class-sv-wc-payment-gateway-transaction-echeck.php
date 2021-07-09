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

if ( ! class_exists( 'SV_WC_Payment_Gateway_Transaction_eCheck' ) ) :

/**
 * The transaction eCheck class.
 *
 * @since 4.7.0-dev
 */
class SV_WC_Payment_Gateway_Transaction_eCheck extends SV_WC_Payment_Gateway_Transaction_Payment_Method {


	/** @var array extra data specific to echecks */
	protected $extra_data = array(
		'id_number'      => '',
		'id_type'        => '',
		'check_number'   => '',
		'account_type'   => '',
	);

	/** @var array properties that should never be stored or filtered */
	protected $sensitive_data = array(
		'account_number' => '',
		'id_number'      => '',
	);


	/* Getter methods *********************************************************/


	/**
	 * Gets the ID number.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return string
	 */
	public function get_id_number() {

		return $this->get_prop( 'id_number', 'edit' );
	}


	/**
	 * Gets the ID type.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return string
	 */
	public function get_id_type( $context = 'view' ) {

		return $this->get_prop( 'id_type', $context );
	}


	/**
	 * Gets the check number.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return string
	 */
	public function get_check_number( $context = 'view' ) {

		return $this->get_prop( 'check_number', $context );
	}


	/**
	 * Gets the account type name.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return string
	 */
	public function get_account_type_name( $context = 'view' ) {

		$value = SV_WC_Payment_Gateway_Helper::payment_type_to_name( $this->get_account_type() );

		if ( 'view' === $context ) {

			/**
			 * Filters a transaction's eCheck account type name.
			 *
			 * @since 4.7.0-dev
			 *
			 * @param string $name account type name
			 * @param \SV_WC_Payment_Gateway_Transaction_eCheck $payment_method payment method object
			 * @param \SV_WC_Payment_Gateway_Transaction $transaction transaction object
			 */
			$value = apply_filters( $this->get_hook_prefix() . 'account_type_name', $value, $this, $this->get_transaction() );

			/**
			 * Filters a transaction's eCheck account type name.
			 *
			 * @since 4.7.0-dev
			 *
			 * @param string $exp_date expiration date
			 * @param \SV_WC_Payment_Gateway_Transaction_eCheck $payment_method payment method object
			 * @param \SV_WC_Payment_Gateway_Transaction $transaction transaction object
			 */
			$value = apply_filters( $this->get_gateway_hook_prefix() . 'account_type_name', $value, $this, $this->get_transaction() );
		}

		return $value;
	}


	/**
	 * Gets the account type.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $context output context - 'view' will be filtered
	 *
	 * @return string
	 */
	public function get_account_type( $context = 'view' ) {

		return $this->get_prop( 'account_type', $context );
	}


	/**
	 * Gets the payment method type.
	 *
	 * @since 4.7.0-dev
	 *
	 * @return string
	 */
	public function get_type() {

		return 'echeck';
	}


	/* Setter methods *********************************************************/


	/**
	 * Sets the ID number.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value ID number
	 */
	public function set_id_number( $value ) {

		$this->set_prop( 'id_number', $value );
	}


	/**
	 * Sets the ID type.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value ID type
	 */
	public function set_id_type( $value ) {

		$this->set_prop( 'id_type', $value );
	}


	/**
	 * Sets the check number.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value check number
	 */
	public function set_check_number( $value ) {

		$this->set_prop( 'check_number', $value );
	}


	/**
	 * Sets the account type.
	 *
	 * @since 4.7.0-dev
	 *
	 * @param string $value account type
	 */
	public function set_account_type( $value ) {

		$this->set_prop( 'account_type', $value );
	}


}

endif;
