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

namespace SkyVerge\WooCommerce\PluginFramework\v5_7_1;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_7_1\\SV_WC_Plugin' ) ) :


/**
 * # WooCommerce Plugin Framework
 *
 * This framework class provides a base level of configurable and overrideable
 * functionality and features suitable for the implementation of a WooCommerce
 * plugin.  This class handles all the "non-feature" support tasks such
 * as verifying dependencies are met, loading the text domain, etc.
 *
 * @version 5.7.1
 */
abstract class SV_WC_Plugin {


	/** Plugin Framework Version */
	const VERSION = '5.7.1';

	/** @var object single instance of plugin */
	protected static $instance;

	/** @var string plugin id */
	private $id;

	/** @var string version number */
	private $version;

	/** @var string plugin path, without trailing slash */
	private $plugin_path;

	/** @var string plugin URL */
	private $plugin_url;

	/** @var string template path, without trailing slash */
	private $template_path;

	/** @var \WC_Logger instance */
	private $logger;

	/** @var  SV_WP_Admin_Message_Handler instance */
	private $message_handler;

	/** @var string the plugin text domain */
	private $text_domain;

	/** @var array memoized list of active plugins */
	private $active_plugins = [];

	/** @var int|float minimum supported WooCommerce versions before the latest (units for major releases, decimals for minor) */
	private $min_wc_semver;

	/** @var SV_WC_Plugin_Dependencies dependency handler instance */
	private $dependency_handler;

	/** @var SV_WC_Hook_Deprecator hook deprecator instance */
	private $hook_deprecator;

	/** @var Plugin\Lifecycle lifecycle handler instance */
	protected $lifecycle_handler;

	/** @var REST_API REST API handler instance */
	protected $rest_api_handler;

	/** @var Admin\Setup_Wizard handler instance */
	protected $setup_wizard_handler;

	/** @var SV_WC_Admin_Notice_Handler the admin notice handler class */
	private $admin_notice_handler;

	/** @var Integrations\Integrations */
	private $integrations;

	/**
	 * Initialize the plugin.
	 *
	 * Child plugin classes may add their own optional arguments.
	 *
	 * @since 2.0.0
	 *
	 * @param string $id plugin id
	 * @param string $version plugin version number
	 * @param array $args {
	 *     optional plugin arguments
	 *
	 *     @type int|float $latest_wc_versions the last supported versions of WooCommerce, as a major.minor float relative to the latest available version
	 *     @type string $text_domain the plugin textdomain, used to set up translations
	 *     @type array  $dependencies {
	 *         PHP extension, function, and settings dependencies
	 *
	 *         @type array $php_extensions PHP extension dependencies
	 *         @type array $php_functions  PHP function dependencies
	 *         @type array $php_settings   PHP settings dependencies
	 *     }
	 * }
	 */
	public function __construct( $id, $version, $args = [] ) {

		// required params
		$this->id      = $id;
		$this->version = $version;

		$args = wp_parse_args( $args, [
			'min_wc_semver' => 0.2, // by default, 2 minor versions behind the latest published are supported
			'text_domain'   => '',
			'dependencies'  => [],
		] );

		$this->min_wc_semver = is_numeric( $args['min_wc_semver'] ) ? abs( $args['min_wc_semver'] ) : null;
		$this->text_domain   = $args['text_domain'];

		// includes that are required to be available at all times
		$this->includes();

		// initialize the dependencies manager
		$this->init_dependencies( $args['dependencies'] );

		// build the admin message handler instance
		$this->init_admin_message_handler();

		// build the admin notice handler instance
		$this->init_admin_notice_handler();

		// build the hook deprecator instance
		$this->init_hook_deprecator();

		// build the lifecycle handler instance
		$this->init_lifecycle_handler();

		// build the REST API handler instance
		$this->init_rest_api_handler();

		// build the setup handler instance
		$this->init_setup_wizard_handler();

		$this->init_integrations();

		// add the action & filter hooks
		$this->add_hooks();
	}


	/** Init methods **********************************************************/


	/**
	 * Initializes the plugin dependency handler.
	 *
	 * @since 5.2.0
	 *
	 * @param array $dependencies {
	 *     PHP extension, function, and settings dependencies
	 *
	 *     @type array $php_extensions PHP extension dependencies
	 *     @type array $php_functions  PHP function dependencies
	 *     @type array $php_settings   PHP settings dependencies
	 * }
	 */
	protected function init_dependencies( $dependencies ) {

		$this->dependency_handler = new SV_WC_Plugin_Dependencies( $this, $dependencies );
	}


	/**
	 * Builds the admin message handler instance.
	 *
	 * Plugins can override this with their own handler.
	 *
	 * @since 5.2.0
	 */
	protected function init_admin_message_handler() {

		$this->message_handler = new SV_WP_Admin_Message_Handler( $this->get_id() );
	}


	/**
	 * Builds the admin notice handler instance.
	 *
	 * Plugins can override this with their own handler.
	 *
	 * @since 5.2.0
	 */
	protected function init_admin_notice_handler() {

		$this->admin_notice_handler = new SV_WC_Admin_Notice_Handler( $this );
	}


	/**
	 * Builds the hook deprecator instance.
	 *
	 * Plugins can override this with their own handler.
	 *
	 * @since 5.2.0
	 */
	protected function init_hook_deprecator() {

		$this->hook_deprecator = new SV_WC_Hook_Deprecator( $this->get_plugin_name(), $this->get_deprecated_hooks() );
	}


	/**
	 * Builds the lifecycle handler instance.
	 *
	 * Plugins can override this with their own handler to perform install and
	 * upgrade routines.
	 *
	 * @since 5.2.0
	 */
	protected function init_lifecycle_handler() {

		$this->lifecycle_handler = new Plugin\Lifecycle( $this );
	}


	/**
	 * Builds the REST API handler instance.
	 *
	 * Plugins can override this to add their own data and/or routes.
	 *
	 * @since 5.2.0
	 */
	protected function init_rest_api_handler() {

		$this->rest_api_handler = new REST_API( $this );
	}


	/**
	 * Builds the Setup Wizard handler instance.
	 *
	 * Plugins can override and extend this method to add their own setup wizard.
	 *
	 * @since 5.3.0
	 */
	protected function init_setup_wizard_handler() {

		require_once( $this->get_framework_path() . '/admin/abstract-sv-wc-plugin-admin-setup-wizard.php' );
	}


	/**
	 * Builds the Integrations instance.
	 *
	 * @return 5.7.2-dev.1
	 */
	protected function init_integrations() {

		$this->integrations = new Integrations\Integrations( $this );
	}


	/**
	 * Adds the action & filter hooks.
	 *
	 * @since 5.2.0
	 */
	private function add_hooks() {

		// initialize the plugin
		add_action( 'plugins_loaded', array( $this, 'init_plugin' ), 15 );

		// initialize the plugin admin
		add_action( 'admin_init', array( $this, 'init_admin' ), 0 );

		// hook for translations separately to ensure they're loaded
		add_action( 'init', array( $this, 'load_translations' ) );

		// add the admin notices
		add_action( 'admin_notices', array( $this, 'add_admin_notices' ) );
		add_action( 'admin_footer',  array( $this, 'add_delayed_admin_notices' ) );

		// add a 'Configure' link to the plugin action links
		add_filter( 'plugin_action_links_' . plugin_basename( $this->get_plugin_file() ), array( $this, 'plugin_action_links' ) );

		// automatically log HTTP requests from SV_WC_API_Base
		$this->add_api_request_logging();

		// add any PHP incompatibilities to the system status report
		add_filter( 'woocommerce_system_status_environment_rows', array( $this, 'add_system_status_php_information' ) );
	}


	/**
	 * Cloning instances is forbidden due to singleton pattern.
	 *
	 * @since 3.1.0
	 */
	public function __clone() {
		/* translators: Placeholders: %s - plugin name */
		_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'You cannot clone instances of %s.', 'woocommerce-plugin-framework' ), esc_html( $this->get_plugin_name() ) ), '3.1.0' );
	}


	/**
	 * Unserializing instances is forbidden due to singleton pattern.
	 *
	 * @since 3.1.0
	 */
	public function __wakeup() {
		/* translators: Placeholders: %s - plugin name */
		_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'You cannot unserialize instances of %s.', 'woocommerce-plugin-framework' ), esc_html( $this->get_plugin_name() ) ), '3.1.0' );
	}


	/**
	 * Load plugin & framework text domains.
	 *
	 * @internal
	 *
	 * @since 4.2.0
	 */
	public function load_translations() {

		$this->load_framework_textdomain();

		// if this plugin passes along its text domain, load its translation files
		if ( $this->text_domain ) {
			$this->load_plugin_textdomain();
		}
	}


	/**
	 * Loads the framework textdomain.
	 *
	 * @since 4.5.0
	 */
	protected function load_framework_textdomain() {
		$this->load_textdomain( 'woocommerce-plugin-framework', dirname( plugin_basename( $this->get_framework_file() ) ) );
	}


	/**
	 * Loads the plugin textdomain.
	 *
	 * @since 4.5.0
	 */
	protected function load_plugin_textdomain() {
		$this->load_textdomain( $this->text_domain, dirname( plugin_basename( $this->get_plugin_file() ) ) );
	}


	/**
	 * Loads the plugin textdomain.
	 *
	 * @since 4.5.0
	 * @param string $textdomain the plugin textdomain
	 * @param string $path the i18n path
	 */
	protected function load_textdomain( $textdomain, $path ) {

		// user's locale if in the admin for WP 4.7+, or the site locale otherwise
		$locale = is_admin() && is_callable( 'get_user_locale' ) ? get_user_locale() : get_locale();

		$locale = apply_filters( 'plugin_locale', $locale, $textdomain );

		load_textdomain( $textdomain, WP_LANG_DIR . '/' . $textdomain . '/' . $textdomain . '-' . $locale . '.mo' );

		load_plugin_textdomain( $textdomain, false, untrailingslashit( $path ) . '/i18n/languages' );
	}


	/**
	 * Initializes the plugin.
	 *
	 * Plugins can override this to set up any handlers after WordPress is ready.
	 *
	 * @since 5.2.0
	 */
	public function init_plugin() {

		// stub
	}


	/**
	 * Initializes the plugin admin.
	 *
	 * Plugins can override this to set up any handlers after the WordPress admin is ready.
	 *
	 * @since 5.2.0
	 */
	public function init_admin() {

		// stub
	}


	/**
	 * Include any critical files which must be available as early as possible,
	 *
	 * @since 2.0.0
	 */
	private function includes() {

		$framework_path = $this->get_framework_path();

		// common exception class
		require_once(  $framework_path . '/class-sv-wc-plugin-exception.php' );

		// addresses
		require_once(  $framework_path . '/Addresses/Address.php' );
		require_once(  $framework_path . '/Addresses/Customer_Address.php' );

		// Settings API
		require_once( $framework_path . '/Settings_API/Abstract_Settings.php' );
		require_once( $framework_path . '/Settings_API/Setting.php' );
		require_once( $framework_path . '/Settings_API/Control.php' );

		// common utility methods
		require_once( $framework_path . '/class-sv-wc-helper.php' );
		require_once( $framework_path . '/Country_Helper.php' );
		require_once( $framework_path . '/admin/Notes_Helper.php' );

		// backwards compatibility for older WC versions
		require_once( $framework_path . '/class-sv-wc-plugin-compatibility.php' );
		require_once( $framework_path . '/compatibility/abstract-sv-wc-data-compatibility.php' );
		require_once( $framework_path . '/compatibility/class-sv-wc-order-compatibility.php' );
		require_once( $framework_path . '/compatibility/class-sv-wc-product-compatibility.php' );

		// TODO: Remove this when WC 3.x can be required {CW 2017-03-16}
		require_once( $framework_path . '/compatibility/class-sv-wc-datetime.php' );

		// generic API base
		require_once( $framework_path . '/api/class-sv-wc-api-exception.php' );
		require_once( $framework_path . '/api/class-sv-wc-api-base.php' );
		require_once( $framework_path . '/api/interface-sv-wc-api-request.php' );
		require_once( $framework_path . '/api/interface-sv-wc-api-response.php' );

		// XML API base
		require_once( $framework_path . '/api/abstract-sv-wc-api-xml-request.php' );
		require_once( $framework_path . '/api/abstract-sv-wc-api-xml-response.php' );

		// JSON API base
		require_once( $framework_path . '/api/abstract-sv-wc-api-json-request.php' );
		require_once( $framework_path . '/api/abstract-sv-wc-api-json-response.php' );

		// REST API Controllers
		require_once( $framework_path . '/rest-api/Controllers/Settings.php' );

		// Handlers
		require_once( $framework_path . '/Handlers/Script_Handler.php' );
		require_once( $framework_path . '/class-sv-wc-plugin-dependencies.php' );
		require_once( $framework_path . '/class-sv-wc-hook-deprecator.php' );
		require_once( $framework_path . '/class-sv-wp-admin-message-handler.php' );
		require_once( $framework_path . '/class-sv-wc-admin-notice-handler.php' );
		require_once( $framework_path . '/Lifecycle.php' );
		require_once( $framework_path . '/rest-api/class-sv-wc-plugin-rest-api.php' );

		// Integrations
		require_once( $framework_path . '/Integrations/Integrations.php' );
		require_once( $framework_path . '/Integrations/Disable_Admin_Notices.php' );
	}


	/**
	 * Return deprecated/removed hooks. Implementing classes should override this
	 * and return an array of deprecated/removed hooks in the following format:
	 *
	 * $old_hook_name = array {
	 *   @type string $version version the hook was deprecated/removed in
	 *   @type bool $removed if present and true, the message will indicate the hook was removed instead of deprecated
	 *   @type string|bool $replacement if present and a string, the message will indicate the replacement hook to use,
	 *     otherwise (if bool and false) the message will indicate there is no replacement available.
	 * }
	 *
	 * @since 4.3.0
	 * @return array
	 */
	protected function get_deprecated_hooks() {

		// stub method
		return array();
	}


	/** Admin methods ******************************************************/


	/**
	 * Returns true if on the admin plugin settings page, if any
	 *
	 * @since 2.0.0
	 * @return boolean true if on the admin plugin settings page
	 */
	public function is_plugin_settings() {
		// optional method, not all plugins *have* a settings page
		return false;
	}


	/**
	 * Adds admin notices upon initialization.
	 *
	 * This may also produce notices if running an unsupported version of WooCommerce.
	 *
	 * @since 3.0.0
	 */
	public function add_admin_notices() {

		// bail if there's no defined versions to compare
		if ( empty( $this->min_wc_semver ) || ! is_numeric( $this->min_wc_semver ) ) {
			return;
		}

		$latest_wc_versions = SV_WC_Plugin_Compatibility::get_latest_wc_versions();
		$current_wc_version = SV_WC_Plugin_Compatibility::get_wc_version();

		// bail if the latest WooCommerce version or the current WooCommerce versions can't be determined
		if ( empty( $latest_wc_versions ) || empty( $current_wc_version ) ) {
			return;
		}

		// grab latest published version
		$supported_wc_version = $latest_wc_version = current( $latest_wc_versions );

		// grab semver parts
		$latest_semver        = explode( '.', $latest_wc_version );
		$supported_semver     = explode( '.', (string) $this->min_wc_semver );
		$supported_major      = max( 0,  (int) $latest_semver[0] - (int) $supported_semver[0] );
		$supported_minor      = isset( $supported_semver[1] ) ? (int) $supported_semver[1] : 0;
		$previous_minor       = null;

		// loop known WooCommerce versions from the most recent until we get the oldest supported one
		foreach ( $latest_wc_versions as $older_wc_version ) {

			// as we loop through versions, the latest one before we break the loop will be the minimum supported one
			$supported_wc_version = $older_wc_version;

			$older_semver = explode( '.', $older_wc_version );
			$older_major  = (int) $older_semver[0];
			$older_minor  = isset( $older_semver[1] ) ? (int) $older_semver[1] : 0;

			// if major is ignored, skip; if the minor hasn't changed (patch must be), skip
			if ( $older_major > $supported_major || $older_minor === $previous_minor ) {
				continue;
			}

			// we reached the maximum number of supported minor versions
			if ( $supported_minor <= 0 ) {
				break;
			}

			// store the previous minor while we loop patch versions, which we ignore
			$previous_minor = $older_minor;

			$supported_minor--;
		}

		// for strict comparison, we strip the patch version from the determined versions and compare only major, minor versions, ignoring patches (i.e. 1.2.3 becomes 1.2)
		$current_wc_version   = substr( $current_wc_version, 0, strpos( $current_wc_version, '.', strpos( $current_wc_version, '.' ) + 1 ) );
		$supported_wc_version = substr( $supported_wc_version, 0, strpos( $supported_wc_version, '.', strpos( $supported_wc_version, '.' ) + 1 ) );
		$compared_wc_version  = $current_wc_version && $supported_wc_version ? version_compare( $current_wc_version, $supported_wc_version ) : null;

		// installed version is at more than 2 minor versions ($min_wc_semver value) behind the last published version
		if ( -1 === $compared_wc_version ) {

			$this->get_admin_notice_handler()->add_admin_notice(
				sprintf(
					/* translators: Placeholders: %1$s - plugin name, %2$s - WooCommerce version number, %3$s - opening <a> HTML link tag, %4$s - closing </a> HTML link tag */
					__( 'Heads up! %1$s will soon discontinue support for WooCommerce %2$s. Please %3$supdate WooCommerce%4$s to take advantage of the latest updates and features.', 'woocommerce-plugin-framework' ),
					$this->get_plugin_name(),
					$current_wc_version,
					'<a href="' . esc_url( admin_url( 'update-core.php' ) ) .'">', '</a>'
				),
				$this->get_id_dasherized() . '-deprecated-wc-version-as-of-' . str_replace( '.', '-', $supported_wc_version ),
				[ 'notice_class' => 'notice-info' ]
			);
		}
	}


	/**
	 * Convenience method to add delayed admin notices, which may depend upon
	 * some setting being saved prior to determining whether to render
	 *
	 * @since 3.0.0
	 */
	public function add_delayed_admin_notices() {
		// stub method
	}


	/**
	 * Return the plugin action links.  This will only be called if the plugin
	 * is active.
	 *
	 * @since 2.0.0
	 * @param array $actions associative array of action names to anchor tags
	 * @return array associative array of plugin action links
	 */
	public function plugin_action_links( $actions ) {

		$custom_actions = array();

		// settings url(s)
		if ( $this->get_settings_link( $this->get_id() ) ) {
			$custom_actions['configure'] = $this->get_settings_link( $this->get_id() );
		}

		// documentation url if any
		if ( $this->get_documentation_url() ) {
			/* translators: Docs as in Documentation */
			$custom_actions['docs'] = sprintf( '<a href="%s" target="_blank">%s</a>', $this->get_documentation_url(), esc_html__( 'Docs', 'woocommerce-plugin-framework' ) );
		}

		// support url if any
		if ( $this->get_support_url() ) {
			$custom_actions['support'] = sprintf( '<a href="%s">%s</a>', $this->get_support_url(), esc_html_x( 'Support', 'noun', 'woocommerce-plugin-framework' ) );
		}

		// review url if any
		if ( $this->get_reviews_url() ) {
			$custom_actions['review'] = sprintf( '<a href="%s">%s</a>', $this->get_reviews_url(), esc_html_x( 'Review', 'verb', 'woocommerce-plugin-framework' ) );
		}

		// add the links to the front of the actions list
		return array_merge( $custom_actions, $actions );
	}


	/** Helper methods ******************************************************/


	/**
	 * Automatically log API requests/responses when using SV_WC_API_Base
	 *
	 * @since 2.2.0
	 * @see SV_WC_API_Base::broadcast_request()
	 */
	public function add_api_request_logging() {

		if ( ! has_action( 'wc_' . $this->get_id() . '_api_request_performed' ) ) {
			add_action( 'wc_' . $this->get_id() . '_api_request_performed', array( $this, 'log_api_request' ), 10, 2 );
		}
	}


	/**
	 * Log API requests/responses
	 *
	 * @since 2.2.0
	 * @param array $request request data, see SV_WC_API_Base::broadcast_request() for format
	 * @param array $response response data
	 * @param string|null $log_id log to write data to
	 */
	public function log_api_request( $request, $response, $log_id = null ) {

		$this->log( "Request\n" . $this->get_api_log_message( $request ), $log_id );

		if ( ! empty( $response ) ) {
			$this->log( "Response\n" . $this->get_api_log_message( $response ), $log_id );
		}
	}


	/**
	 * Transform the API request/response data into a string suitable for logging
	 *
	 * @since 2.2.0
	 * @param array $data
	 * @return string
	 */
	public function get_api_log_message( $data ) {

		$messages = array();

		$messages[] = isset( $data['uri'] ) && $data['uri'] ? 'Request' : 'Response';

		foreach ( (array) $data as $key => $value ) {
			$messages[] = trim( sprintf( '%s: %s', $key, is_array( $value ) || ( is_object( $value ) && 'stdClass' == get_class( $value ) ) ? print_r( (array) $value, true ) : $value ) );
		}

		return implode( "\n", $messages ) . "\n";
	}


	/**
	 * Adds any PHP incompatibilities to the system status report.
	 *
	 * @since 4.5.0
	 *
	 * @param array $rows WooCommerce system status rows
	 * @return array
	 */
	public function add_system_status_php_information( $rows ) {

		foreach ( $this->get_dependency_handler()->get_incompatible_php_settings() as $setting => $values ) {

			if ( isset( $values['type'] ) && 'min' === $values['type'] ) {

				// if this setting already has a higher minimum from another plugin, skip it
				if ( isset( $rows[ $setting ]['expected'] ) && $values['expected'] < $rows[ $setting ]['expected'] ) {
					continue;
				}

				$note = __( '%1$s - A minimum of %2$s is required.', 'woocommerce-plugin-framework' );

			} else {

				// if this requirement is already listed, skip it
				if ( isset( $rows[ $setting ] ) ) {
					continue;
				}

				$note = __( 'Set as %1$s - %2$s is required.', 'woocommerce-plugin-framework' );
			}

			$note = sprintf( $note, $values['actual'], $values['expected'] );

			$rows[ $setting ] = array(
				'name'     => $setting,
				'note'     => $note,
				'success'  => false,
				'expected' => $values['expected'], // WC doesn't use this, but it's useful for us
			);
		}

		return $rows;
	}


	/**
	 * Saves errors or messages to WooCommerce Log (woocommerce/logs/plugin-id-xxx.txt)
	 *
	 * @since 2.0.0
	 * @param string $message error or message to save to log
	 * @param string $log_id optional log id to segment the files by, defaults to plugin id
	 */
	public function log( $message, $log_id = null ) {

		if ( is_null( $log_id ) ) {
			$log_id = $this->get_id();
		}

		if ( ! is_object( $this->logger ) ) {
			$this->logger = new \WC_Logger();
		}

		$this->logger->add( $log_id, $message );
	}


	/**
	 * Require and instantiate a class
	 *
	 * @since 4.2.0
	 * @param string $local_path path to class file in plugin, e.g. '/includes/class-wc-foo.php'
	 * @param string $class_name class to instantiate
	 * @return object instantiated class instance
	 */
	public function load_class( $local_path, $class_name ) {

		require_once( $this->get_plugin_path() . $local_path );

		return new $class_name;
	}


	/**
	 * Determines if TLS v1.2 is required for API requests.
	 *
	 * Subclasses should override this to return true if TLS v1.2 is required.
	 *
	 * @since 5.5.2
	 *
	 * @return bool
	 */
	public function require_tls_1_2() {

		return false;
	}


	/**
	 * Determines if TLS 1.2 is available.
	 *
	 * @since 5.5.2
	 *
	 * @return bool
	 */
	public function is_tls_1_2_available() {

		// assume availability to avoid notices for unknown SSL types
		$is_available = true;

		// check the cURL version if installed
		if ( is_callable( 'curl_version' ) ) {

			$versions = curl_version();

			// cURL 7.34.0 is considered the minimum version that supports TLS 1.2
			if ( version_compare( $versions['version'], '7.34.0', '<' ) ) {
				$is_available = false;
			}
		}

		return $is_available;
	}


	/** Getter methods ******************************************************/


	/**
	 * Gets the main plugin file.
	 *
	 * @since 5.0.0
	 *
	 * @return string
	 */
	public function get_plugin_file() {

		$slug = dirname( plugin_basename( $this->get_file() ) );

		return trailingslashit( $slug ) . $slug . '.php';
	}


	/**
	 * The implementation for this abstract method should simply be:
	 *
	 * return __FILE__;
	 *
	 * @since 2.0.0
	 * @return string the full path and filename of the plugin file
	 */
	abstract protected function get_file();


	/**
	 * Returns the plugin id
	 *
	 * @since 2.0.0
	 * @return string plugin id
	 */
	public function get_id() {
		return $this->id;
	}


	/**
	 * Returns the plugin id with dashes in place of underscores, and
	 * appropriate for use in frontend element names, classes and ids
	 *
	 * @since 2.0.0
	 * @return string plugin id with dashes in place of underscores
	 */
	public function get_id_dasherized() {
		return str_replace( '_', '-', $this->get_id() );
	}


	/**
	 * Returns the plugin full name including "WooCommerce", ie
	 * "WooCommerce X".  This method is defined abstract for localization purposes
	 *
	 * @since 2.0.0
	 * @return string plugin name
	 */
	abstract public function get_plugin_name();


	/** Handler methods *******************************************************/


	/**
	 * Gets the dependency handler.
	 *
	 * @since 5.2.0.1
	 *
	 * @return SV_WC_Plugin_Dependencies
	 */
	public function get_dependency_handler() {

		return $this->dependency_handler;
	}


	/**
	 * Gets the lifecycle handler instance.
	 *
	 * @since 5.1.0
	 *
	 * @return Plugin\Lifecycle
	 */
	public function get_lifecycle_handler() {

		return $this->lifecycle_handler;
	}


	/**
	 * Gets the Setup Wizard handler instance.
	 *
	 * @since 5.3.0
	 *
	 * @return null|Admin\Setup_Wizard
	 */
	public function get_setup_wizard_handler() {

		return $this->setup_wizard_handler;
	}


	/**
	 * Gets the admin message handler.
	 *
	 * @since 2.0.0
	 *
	 * @return SV_WP_Admin_Message_Handler
	 */
	public function get_message_handler() {

		return $this->message_handler;
	}


	/**
	 * Gets the admin notice handler instance.
	 *
	 * @since 3.0.0
	 *
	 * @return SV_WC_Admin_Notice_Handler
	 */
	public function get_admin_notice_handler() {

		return $this->admin_notice_handler;
	}


	/**
	 * Gets the settings API handler instance.
	 *
	 * Plugins can use this to init the settings API handler.
	 *
	 * @since 5.7.0
	 *
	 * @return void|Settings_API\Abstract_Settings
	 */
	public function get_settings_handler() {

		return;
	}


	/**
	 * Returns the plugin version name.  Defaults to wc_{plugin id}_version
	 *
	 * @since 2.0.0
	 * @return string the plugin version name
	 */
	public function get_plugin_version_name() {

		return 'wc_' . $this->get_id() . '_version';
	}


	/**
	 * Returns the current version of the plugin
	 *
	 * @since 2.0.0
	 * @return string plugin version
	 */
	public function get_version() {
		return $this->version;
	}


	/**
	 * Returns the "Configure" plugin action link to go directly to the plugin
	 * settings page (if any)
	 *
	 * @since 2.0.0
	 * @see SV_WC_Plugin::get_settings_url()
	 * @param string $plugin_id optional plugin identifier.  Note that this can be a
	 *        sub-identifier for plugins with multiple parallel settings pages
	 *        (ie a gateway that supports both credit cards and echecks)
	 * @return string plugin configure link
	 */
	public function get_settings_link( $plugin_id = null ) {

		$settings_url = $this->get_settings_url( $plugin_id );

		if ( $settings_url ) {
			return sprintf( '<a href="%s">%s</a>', $settings_url, esc_html__( 'Configure', 'woocommerce-plugin-framework' ) );
		}

		// no settings
		return '';
	}


	/**
	 * Gets the plugin configuration URL
	 *
	 * @since 2.0.0
	 * @see SV_WC_Plugin::get_settings_link()
	 * @param string $plugin_id optional plugin identifier.  Note that this can be a
	 *        sub-identifier for plugins with multiple parallel settings pages
	 *        (ie a gateway that supports both credit cards and echecks)
	 * @return string plugin settings URL
	 */
	public function get_settings_url( $plugin_id = null ) {

		// stub method
		return '';
	}


	/**
	 * Returns true if the current page is the admin general configuration page
	 *
	 * @since 3.0.0
	 * @return boolean true if the current page is the admin general configuration page
	 */
	public function is_general_configuration_page() {

		return isset( $_GET['page'] ) && 'wc-settings' === $_GET['page'] && ( ! isset( $_GET['tab'] ) || 'general' === $_GET['tab'] );
	}


	/**
	 * Returns the admin configuration url for the admin general configuration page
	 *
	 * @since 3.0.0
	 * @return string admin configuration url for the admin general configuration page
	 */
	public function get_general_configuration_url() {

		return admin_url( 'admin.php?page=wc-settings&tab=general' );
	}


	/**
	 * Gets the plugin documentation url, used for the 'Docs' plugin action
	 *
	 * @since 2.0.0
	 * @return string documentation URL
	 */
	public function get_documentation_url() {

		return null;
	}


	/**
	 * Gets the support URL, used for the 'Support' plugin action link
	 *
	 * @since 4.0.0
	 * @return string support url
	 */
	public function get_support_url() {

		return null;
	}


	/**
	 * Gets the plugin sales page URL.
	 *
	 * @since 5.1.0
	 *
	 * @return string
	 */
	public function get_sales_page_url() {

		return '';
	}


	/**
	 * Gets the plugin reviews page URL.
	 *
	 * Used for the 'Reviews' plugin action and review prompts.
	 *
	 * @since 5.1.0
	 *
	 * @return string
	 */
	public function get_reviews_url() {

		return $this->get_sales_page_url() ? $this->get_sales_page_url() . '#comments' : '';
	}


	/**
	 * Gets the plugin's path without a trailing slash.
	 *
	 * e.g. /path/to/wp-content/plugins/plugin-directory
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_plugin_path() {

		if ( null === $this->plugin_path ) {
			$this->plugin_path = untrailingslashit( plugin_dir_path( $this->get_file() ) );
		}

		return $this->plugin_path;
	}


	/**
	 * Gets the plugin's URL without a trailing slash.
	 *
	 * E.g. http://skyverge.com/wp-content/plugins/plugin-directory
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_plugin_url() {

		if ( null === $this->plugin_url ) {
			$this->plugin_url = untrailingslashit( plugins_url( '/', $this->get_file() ) );
		}

		return $this->plugin_url;
	}


	/**
	 * Gets the woocommerce uploads path, without trailing slash.
	 *
	 * Oddly WooCommerce core does not provide a way to get this.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public static function get_woocommerce_uploads_path() {

		$upload_dir = wp_upload_dir();

		return $upload_dir['basedir'] . '/woocommerce_uploads';
	}


	/**
	 * Returns the loaded framework __FILE__
	 *
	 * @since 4.0.0
	 * @return string
	 */
	public function get_framework_file() {

		return __FILE__;
	}


	/**
	 * Gets the loaded framework path, without trailing slash.
	 *
	 * This matches the path to the highest version of the framework currently loaded.
	 *
	 * @since 4.0.0
	 * @return string
	 */
	public function get_framework_path() {

		return untrailingslashit( plugin_dir_path( $this->get_framework_file() ) );
	}


	/**
	 * Gets the absolute path to the loaded framework image directory, without a trailing slash.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function get_framework_assets_path() {

		return $this->get_framework_path() . '/assets';
	}


	/**
	 * Gets the loaded framework assets URL without a trailing slash.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function get_framework_assets_url() {

		return untrailingslashit( plugins_url( '/assets', $this->get_framework_file() ) );
	}


	/**
	 * Gets the plugin default template path, without a trailing slash.
	 *
	 * @since 5.5.0
	 *
	 * @return string
	 */
	public function get_template_path() {

		if ( null === $this->template_path ) {
			$this->template_path = $this->get_plugin_path() . '/templates';
		}

		return $this->template_path;
	}


	/**
	 * Loads and outputs a template file HTML.
	 *
	 * @see \wc_get_template() except we define automatically the default path
	 *
	 * @since 5.5.0
	 *
	 * @param string $template template name/part
	 * @param array $args associative array of optional template arguments
	 * @param string $path optional template path, can be empty, as themes can override this
	 * @param string $default_path optional default template path, will normally use the plugin's own template path unless overridden
	 */
	public function load_template( $template, array $args = [], $path = '', $default_path = '' ) {

		if ( '' === $default_path || ! is_string( $default_path ) ) {
			$default_path = trailingslashit( $this->get_template_path() );
		}

		wc_get_template( $template, $args, $path, $default_path );
	}


	/**
	 * Determines whether a plugin is active.
	 *
	 * @since 2.0.0
	 *
	 * @param string $plugin_name plugin name, as the plugin-filename.php
	 * @return boolean true if the named plugin is installed and active
	 */
	public function is_plugin_active( $plugin_name ) {

		$is_active = false;

		if ( is_string( $plugin_name ) ) {

			if ( ! array_key_exists( $plugin_name, $this->active_plugins ) ) {

				$active_plugins = (array) get_option( 'active_plugins', array() );

				if ( is_multisite() ) {
					$active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
				}

				$plugin_filenames = array();

				foreach ( $active_plugins as $plugin ) {

					if ( SV_WC_Helper::str_exists( $plugin, '/' ) ) {

						// normal plugin name (plugin-dir/plugin-filename.php)
						list( , $filename ) = explode( '/', $plugin );

					} else {

						// no directory, just plugin file
						$filename = $plugin;
					}

					$plugin_filenames[] = $filename;
				}

				$this->active_plugins[ $plugin_name ] = in_array( $plugin_name, $plugin_filenames, true );
			}

			$is_active = (bool) $this->active_plugins[ $plugin_name ];
		}

		return $is_active;
	}


	/** Deprecated methods ****************************************************/


	/**
	 * Handles version checking.
	 *
	 * @since 2.0.0
	 * @deprecated 5.2.0
	 */
	public function do_install() {

		wc_deprecated_function( __METHOD__, '5.2.0', get_class( $this->get_lifecycle_handler() ) . '::init()' );

		$this->get_lifecycle_handler()->init();
	}


	/**
	 * Helper method to install default settings for a plugin.
	 *
	 * @since 4.2.0
	 * @deprecated 5.2.0
	 *
	 * @param array $settings array of settings in format required by WC_Admin_Settings
	 */
	public function install_default_settings( array $settings ) {

		wc_deprecated_function( __METHOD__, '5.2.0', get_class( $this->get_lifecycle_handler() ) . '::install_default_settings()' );

		$this->get_lifecycle_handler()->install_default_settings( $settings );
	}


	/**
	 * Plugin activated method. Perform any activation tasks here.
	 * Note that this _does not_ run during upgrades.
	 *
	 * @since 4.2.0
	 * @deprecated 5.2.0
	 */
	public function activate() {

		wc_deprecated_function( __METHOD__, '5.2.0' );
	}


	/**
	 * Plugin deactivation method. Perform any deactivation tasks here.
	 *
	 * @since 4.2.0
	 * @deprecated 5.2.0
	 */
	public function deactivate() {

		wc_deprecated_function( __METHOD__, '5.2.0' );
	}


	/**
	 * Gets the string name of any required PHP extensions that are not loaded.
	 *
	 * @since 4.5.0
	 * @deprecated 5.2.0
	 *
	 * @return array
	 */
	public function get_missing_extension_dependencies() {

		wc_deprecated_function( __METHOD__, '5.2.0', get_class( $this->get_dependency_handler() ) . '::get_missing_php_extensions()' );

		return $this->get_dependency_handler()->get_missing_php_extensions();
	}


	/**
	 * Gets the string name of any required PHP functions that are not loaded.
	 *
	 * @since 2.1.0
	 * @deprecated 5.2.0
	 *
	 * @return array
	 */
	public function get_missing_function_dependencies() {

		wc_deprecated_function( __METHOD__, '5.2.0', get_class( $this->get_dependency_handler() ) . '::get_missing_php_functions()' );

		return $this->get_dependency_handler()->get_missing_php_functions();
	}


	/**
	 * Gets the string name of any required PHP extensions that are not loaded.
	 *
	 * @since 4.5.0
	 * @deprecated 5.2.0
	 *
	 * @return array
	 */
	public function get_incompatible_php_settings() {

		wc_deprecated_function( __METHOD__, '5.2.0', get_class( $this->get_dependency_handler() ) . '::get_incompatible_php_settings()' );

		return $this->get_dependency_handler()->get_incompatible_php_settings();
	}


	/**
	 * Gets the PHP dependencies.
	 *
	 * @since 2.0.0
	 * @deprecated 5.2.0
	 *
	 * @return array
	 */
	protected function get_dependencies() {

		wc_deprecated_function( __METHOD__, '5.2.0' );

		return array();
	}


	/**
	 * Gets the PHP extension dependencies.
	 *
	 * @since 4.5.0
	 * @deprecated 5.2.0
	 *
	 * @return array
	 */
	protected function get_extension_dependencies() {

		wc_deprecated_function( __METHOD__, '5.2.0', get_class( $this->get_dependency_handler() ) . '::get_php_extensions()' );

		return $this->get_dependency_handler()->get_php_extensions();
	}


	/**
	 * Gets the PHP function dependencies.
	 *
	 * @since 2.1.0
	 * @deprecated 5.2.0
	 *
	 * @return array
	 */
	protected function get_function_dependencies() {

		wc_deprecated_function( __METHOD__, '5.2.0', get_class( $this->get_dependency_handler() ) . '::get_php_functions()' );

		return $this->get_dependency_handler()->get_php_functions();
	}


	/**
	 * Gets the PHP settings dependencies.
	 *
	 * @since 4.5.0
	 * @deprecated 5.2.0
	 *
	 * @return array
	 */
	protected function get_php_settings_dependencies() {

		wc_deprecated_function( __METHOD__, '5.2.0', get_class( $this->get_dependency_handler() ) . '::get_php_settings()' );

		return $this->get_dependency_handler()->get_php_settings();
	}


	/**
	 * Sets the plugin dependencies.
	 *
	 * @since 4.5.0
	 * @deprecated 5.2.0
	 *
	 * @param array $dependencies the environment dependencies
	 */
	protected function set_dependencies( $dependencies = [] ) {

		wc_deprecated_function( __METHOD__, '5.2.0' );
	}


}


endif;
