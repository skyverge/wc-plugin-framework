<?php
/**
 * WooCommerce Plugin Framework
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
 * @package   SkyVerge/WooCommerce/Plugin/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_7_1\Integrations;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_7_1\\Integrations\\Disable_Admin_Notices' ) ) :


/**
 * Disable Admin Notices Integration
 *
 * @link https://wordpress.org/plugins/disable-admin-notices/
 *
 * @since 5.7.2-dev.1
 */
class Disable_Admin_Notices {


	/**
	 * Constructor.
	 *
	 * @since 5.7.2-dev.1
	 */
	public function __construct() {

		$this->add_hooks();
	}


	/**
	 * Setup integration hooks.
	 *
	 * @since 5.7.2-dev.1
	 */
	protected function add_hooks() {

		// priority must be strictly less than 20 - see callback description
		add_action( 'admin_footer',  [ $this, 'enqueue_conflict_fix_script' ], 10 );
	}


	/**
	 * Enqueues a JavaScript snippet used to prevent an Uncaught DOMException when Disable Admin Notices is active.
	 *
	 * The snippet must be rendered in all pages to prevent the error when other SkyVerge plugins using previous versions of the framework render notices.
	 * The snippet must be rendered on callback for the admin_footer action with a priority less than 20 to make sure it runs before the JS code that triggers the error.
	 * The conflict was detected on Disable Admin Notices 1.1.1 and we don't know whether there is a solution on the roadmap.
	 *
	 * @internal
	 *
	 * @since 5.7.2-dev.1
	 */
	public function enqueue_conflict_fix_script() {

		ob_start();

		?>

		// prevent Uncaught DOMException: Failed to execute 'insertBefore' on 'Node': The new child element contains the parent.
		// Webcraftic's Disable Admin Notices can cause the placeholder to be included inside one of the notices
		// here we make sure that the placeholder and other visible notices are siblings
		$( '[class*="admin-notice-placeholder"]' ).each( function() {

			$placeholder = $( this );
			$container   = $placeholder.closest( '.js-wc-plugin-framework-admin-notice' );

			if ( $container.length ) {

				try {
					$container.find( '.wbcr-dan-hide-notice-link' ).insertAfter( $container.find( '.wbcr-dan-hide-notices' ) );
					$placeholder.insertAfter( $container );
				} catch ( e ) {
					// we tried...
				}
			}
		} );

		<?php

		wc_enqueue_js( ob_get_clean() );
	}


}


endif;
