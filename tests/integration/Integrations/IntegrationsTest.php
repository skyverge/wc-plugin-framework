<?php

use SkyVerge\WooCommerce\PluginFramework\v5_7_1\Integrations\Disable_Admin_Notices;
use SkyVerge\WooCommerce\PluginFramework\v5_7_1\Integrations\Integrations;
use SkyVerge\WooCommerce\PluginFramework\v5_7_1\SV_WC_Plugin;

class IntegrationsTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;

	/** @var Integrations */
	protected $integrations;


	protected function _before() {

		// include plugins with integrations as active
		// wp-browser also adds a handler for pre_option_active_plugins to return the plugins in Codeception's configuration
		// See wp_tests_options() in vendor/lucatume/wp-browser/src/includes/bootstrap.php
		add_filter( 'pre_option_active_plugins', function( $plugins ) {

			if ( ! in_array( 'disable-admin-notices/disable-admin-notices.php', $plugins, true ) ) {
				$plugins[] = 'disable-admin-notices/disable-admin-notices.php';
			}

			return $plugins;
		} );

		// remove active plugins cache
		$property = new ReflectionProperty( SV_WC_Plugin::class, 'active_plugins' );
		$property->setAccessible( true );
		$property->setValue( sv_wc_test_plugin(), [] );

		$this->integrations = new Integrations( sv_wc_test_plugin() );
	}


	/** Tests *********************************************************************************************************/


	/** @see Integrations::get_integrations() */
	public function test_get_integrations() {

		$integrations = $this->integrations->get_integrations();

		$this->assertArrayHasKey( Integrations::INTEGRATION_DISABLE_ADMIN_NOTICES, $integrations );
	}


	/** @see Integrations\Integrations::get_integration() */
	public function test_get_integration() {

		$integration = $this->integrations->get_integration( Integrations::INTEGRATION_DISABLE_ADMIN_NOTICES );

		$this->assertInstanceOf( Disable_Admin_Notices::class, $integration );
	}


}
