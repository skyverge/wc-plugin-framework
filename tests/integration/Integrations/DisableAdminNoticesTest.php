<?php

use SkyVerge\WooCommerce\PluginFramework\v5_7_1\Integrations\Disable_Admin_Notices;

class DisableAdminNoticesTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/** Tests *********************************************************************************************************/


	/** @see Integrations::get_integrations() */
	public function test_constructor() {

		$integration = new Disable_Admin_Notices();

		$this->assertEquals( 10, has_action( 'admin_footer', [ $integration, 'enqueue_conflict_fix_script' ] ) );
	}


}
