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

if ( ! class_exists( 'SV_WC_Payment_Gateway_Authorization_Transaction' ) ) :


/**
 * The payment authorization transaction class.
 *
 * @since 4.7.0-dev
 */
class SV_WC_Payment_Gateway_Authorization_Transaction extends SV_WC_Payment_Gateway_Payment_Transaction {


	/**
	 * Gets the transaction type.
	 *
	 * @since 4.7.0-dev
	 *
	 * @return string
	 */
	public function get_type() {

		return SV_WC_Payment_Gateway_Helper::TRANSACTION_TYPE_AUTHORIZATION;
	}


}

endif;
