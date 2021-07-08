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
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_6_0;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_6_0\\Language_Packs' ) ) :

/**
 * Language packs handler.
 *
 * @since x.y.z
 */
class Language_Packs {


	/** @var SV_WC_Plugin main plugin instance */
	private $plugin;

	/** @var array translations configuration */
	private $config;

	/** @var array memoized supported languages */
	private $supported_languages = [];


	/**
	 * Language packs constructor.
	 *
	 * @since x.y.z
	 *
	 * @param SV_WC_Plugin $plugin main plugin instance
	 * @param array $config the plugin's translations configuration
	 */
	public function __construct( SV_WC_Plugin $plugin, array $config ) {

		$this->plugin = $plugin;
		$this->config = $config;

		// if no translations configuration is specified, we can assume this plugin does not support them or relies on the WordPress plugins directory
		if ( ! empty( $this->config ) ) {

			// adds the plugin to the list of plugins in the translations transient
			add_filter( 'site_transient_update_plugins', [ $this, 'add_translations' ], 1, 1 );

			// intercepts the translations API to update a plugin that is not listed in the WordPress plugins directory
			add_filter( 'translations_api', [ $this, 'update_translations' ], 1, 3 );

			// handle internal translations cache cleanup by synchronizing it to the site transients cache for plugin updates
			add_action( 'set_site_transient_update_plugins',    [ $this, 'clean_translations_cache' ] );
			add_action( 'delete_site_transient_update_plugins', [ $this, 'clean_translations_cache' ] );
		}
	}


	/**
	 * Cleans internal translations caches.
	 *
	 * This happens when WordPress sets or deletes the corresponding cache for updating plugins.
	 * @see Language_Packs::process_translations_update_request()
	 *
	 * @internal
	 *
	 * @since x.y.z
	 */
	public function clean_translations_cache() {

		$transient_key = $this->get_cache_transient_key();
		$translations  = get_site_transient( $transient_key );

		if ( ! is_array( $translations ) || empty( $translations ) ) {
			return;
		}

		// considers a time margin of 15 seconds in case of frequent writes
		$timestamp = key( $translations );
		$expired   = time() - $timestamp >= 15;

		if ( $expired ) {
			delete_site_transient( $transient_key );
		}
	}


	/**
	 * Gets the transient key for the current plugin.
	 *
	 * @since x.y.z
	 *
	 * @return string
	 */
	protected function get_cache_transient_key() {

		return sprintf( '%s_languages', $this->get_plugin()->get_id() );
	}


	/**
	 * Adds translations data to the plugins update transient.
	 *
	 * @internal
	 *
	 * @since x.y.z
	 *
	 * @param \stdClass $data transient data
	 * @return \stdClass
	 */
	public function add_translations( $data ) {

		if ( is_object( $data ) ) {

			if ( ! isset( $data->translations ) ) {
				$data->translations = [];
			}

			if ( is_array( $data->translations ) ) {

				$language_packs = $this->get_language_packs();

				if ( ! empty( $language_packs ) ) {

					/**
					 * This contains an associative array of plugins translation data as follows:
					 *
					 * [ 'plugin-slug' =>
					 *   [ '<lang_code, e.g "it_IT">' =>
					 *     'POT_Creation-Date'  => "<datetime>", // optional
					 *     'PO-Revision-Date'   => "<datetime>", // important, this is the time used by WordPress to issue update requests
					 *     'Project-Id-Version' => "<string>",   // should be optional, this matches the same information as in the pot file
					 *     'X-Generator'        => "<string>",   // for example GlotPress/x.y.z, optional
					 *   ], ...
					 * ], ...
					 *
					 * We must insert the same kind of data in the array for our plugin, so that WordPress is tricked into issuing an update request for the given plugin.
					 */
					$installed_translations = wp_get_installed_translations( 'plugins' );

					foreach ( $language_packs as $language_pack ) {

						// skip unsupported languages
						if ( ! $this->is_supported_language( $language_pack->get_language() ) ) {
							continue;
						}

						// if an existing translation is found, check that if it's in need of an updated, otherwise bail
						if ( isset( $installed_translations[ $this->get_plugin()->get_id() ][ $language_pack->get_language() ]['PO-Revision-Date'] ) ) {

							try {
								$local_last_updated_at  = new \DateTime( $installed_translations[ $this->get_plugin()->get_id() ][ $language_pack->get_language() ]['PO-Revision-Date'] );
								$remote_last_updated_at = new \DateTime( $language_pack->get_po_revision_date() );
							} catch ( \Exception $e ) {
								continue;
							}

							if ( $local_last_updated_at >= $remote_last_updated_at ) {
								continue;
							}
						}

						// append WordPress plugin translation updates transient data
						$data->translations[] = $language_pack->get_transient_data();
					}
				}
			}
		}

		return $data;
	}


	/**
	 * Intercepts the translations API requests to inject plugin translation updates.
	 *
	 * @see \translations_api()
	 *
	 * @internal
	 *
	 * @since x.y.z
	 *
	 * @param false|array|\WP_Error $response request response
	 * @param string $request_type request type
	 * @param array $args request arguments
	 * @return false|array|\WP_Error
	 */
	public function update_translations( $response, $request_type, $args ) {

		if ( 'plugins' === $request_type
		     && is_array( $args )
		     && isset( $args['slug'], $args['version'] )
		     && $this->get_plugin()->get_id() === $args['slug'] ) {

			return $this->process_translations_update_request( $args );
		}

		return $response;
	}


	/**
	 * Processes a translations update request.
	 *
	 * This should return a 200 HTTP response with message "OK" along with the following body containing an array of language data:
	 *
	 * {
	 *   "translations": [
	 *     {
	 *       "language":"<language code, e.g. it_IT>",
	 *       "version": "x.y.z should match the version of the request",
	 *       "updated": "<datetime>",
	 *       "english_name": "<name>"
	 *       "native_name": "<name>"
	 *       "package": "<path_to_file.zip file with po/mo assets, may include blocks>",
	 *       "iso":
	 *         {
	 *           "1": "<language code ISO first part, e.g it>",
	 *           "2": "<language code ISO second part, e.g IT>"
	 *         }
	 *     },
	 *    ...
	 *   ]
	 * }
	 *
	 * @see Language_Packs::update_translations()
	 * @see Language_Packs::clean_translations_cache()
	 * @see \translations_api()
	 *
	 * @since x.y.z
	 *
	 * @param array $args update request arguments
	 * @return array|\WP_Error
	 */
	private function process_translations_update_request( array $args ) {

		$transient_key = $this->get_cache_transient_key();
		$translations  = get_site_transient( $transient_key );
		$args          = wp_parse_args( [
			'version' => $this->get_plugin()->get_version(),
		], $args );

		if ( ! is_array( $translations ) ) {

			$translations = [];
			$timestamp    = time();

			// TODO this should parse the language packs object array into a normalized response object as expected by WordPress {FN 2020-03-05}

			foreach ( $this->get_language_packs( $args['version'] ) as $language_pack ) {
				$translations[ $timestamp ] = $language_pack->get_response_data();
			}

			if ( ! empty( $translations ) ) {
				set_site_transient( $transient_key, $translations, DAY_IN_SECONDS );
			}
		}

		return $translations;
	}


	/**
	 * Gets remote translations data.
	 *
	 * @since x.y.z
	 *
	 * @param string $version version of the translations to retrieve (default to current)
	 * @return array
	 */
	protected function get_language_packs( $version = '' ) {

		if ( empty( $version ) ) {
			$version = $this->get_plugin()->get_version();
		}

		// TODO should use $this->config prop to learn where to look for translations {FN 2020-03-05}

		// TODO this should build a request to a remote server and output a normalized object, e.g. a Language_Pack object {FN 2020-03-05}
		return [];
	}


	/**
	 * Determines if a given language is supported in WordPress.
	 *
	 * Lets the handler only process languages that are present in the current installation.
	 *
	 * @since x.y.z
	 *
	 * @param string $language language code
	 * @return bool
	 */
	private function is_supported_language( $language ) {

		if ( ! array_key_exists( $language, $this->supported_languages ) ) {
			$this->supported_languages[ $language ] = in_array( $language, get_available_languages(), false );
		}

		return $this->supported_languages[ $language ];
	}


	/**
	 * Gets the main plugin instance.
	 *
	 * @since x.y.z
	 *
	 * @return SV_WC_Plugin
	 */
	protected function get_plugin() {

		return $this->plugin;
	}


}

endif;
