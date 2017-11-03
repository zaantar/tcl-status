<?php
/*
Plugin Name: Toolset Common Status
Plugin URI: https://github.com/zaantar/tcl-status
Description: WordPress plugins that shows information about Toolset plugin versions, branch names and the loaded Toolset Common Library instance in the admin bar.
Version: 0.1-dev
Author: hkirat, zaantar
Author URI: http://zaantar.eu
Text Domain: tcl-status
Domain Path: /languages
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
GitHub Plugin URI: zaantar/tcl-status
*/

namespace TclStatus;

final class Main {

	const PARENT_NOTE_ID = 'tcl-status';

	private $supported_plugins = array();

	public static function initialize() {
		new self();
	}


	private function __construct() {

		add_action( 'after_setup_theme', array( $this, 'save_tcl_force_setting' ) );

		// Because TCL uses: add_action( 'plugins_loaded', 'toolset_common_plugins_loaded', -1 );
		add_action( 'plugins_loaded', array( $this, 'force_tcl_setting' ), -2 );

		add_action( 'admin_bar_menu', array( $this, 'modify_admin_bar' ), 90 );

		$this->supported_plugins = array(
			'types' => array(
				'is_active' => 'TYPES_VERSION',
				'version_constant' => 'TYPES_VERSION',
				'abspath' => 'TYPES_ABSPATH'
			),
			'cred' => array(
				'is_active' => 'CRED_FE_VERSION',
				'version_constant' => 'CRED_FE_VERSION',
				'abspath' => function() { return defined( 'CRED_ABSPATH' ) ? CRED_ABSPATH : CRED_ROOT_PLUGIN_PATH; },
			),
			'views' => array(
				'is_active' => 'WPV_VERSION',
				'version_constant' => 'WPV_VERSION',
				'abspath' => 'WPV_PATH'
			),
			'layouts' => array(
				'is_active' => 'WPDDL_VERSION',
				'version_constant' => 'WPDDL_VERSION',
				'abspath' => 'WPDDL_ABSPATH'
			),
			'access' => array(
				'is_active' => 'TACCESS_VERSION',
				'version_constant' => 'TACCESS_VERSION',
				'abspath' => 'TACCESS_PLUGIN_PATH'
			)
		);
	}


	/**
	 * @param \WP_Admin_Bar $wp_admin_bar
	 */
	public function modify_admin_bar( $wp_admin_bar ) {
		$wp_admin_bar->add_node(
			array(
				'parent' => false,
				'id' => self::PARENT_NOTE_ID,
				'title' => $this->get_tcl_string()
			)
		);

		foreach( $this->supported_plugins as $plugin_slug => $plugin_config ) {
			$this->add_plugin_node( $plugin_slug, $wp_admin_bar, self::PARENT_NOTE_ID );
		}

		$this->add_m2m_node( $wp_admin_bar, self::PARENT_NOTE_ID );
		$this->add_tcl_force_menu( $wp_admin_bar, self::PARENT_NOTE_ID );

		do_action( 'tcl_status_add_nodes', $wp_admin_bar, self::PARENT_NOTE_ID );

	}


	private function get_tcl_string() {
		if( $this->is_tcl_loaded() ) {
			return sprintf( 'tcl: %s (%s%s)', $this->get_tcl_plugin_location(), $this->get_tcl_version(), $this->get_tcl_branch_name() );
		} else {
			return 'tcl: ----';
		}
	}


	private function get_tcl_plugin_location() {
		if( ! defined( 'TOOLSET_COMMON_PATH' ) ) {
			return '----';
		}

		return $this->get_basename( TOOLSET_COMMON_PATH );
	}


	private function get_basename( $abspath ) {
		// path to the common library, relative to WP plugin directory
		$basename = plugin_basename( $abspath );

		// get only the first directory name
		// handle slashes (always) and a directory separator in case it's different (windows machines)
		$path_parts = explode( '/', $basename );
		$path_parts = explode( DIRECTORY_SEPARATOR, $path_parts[0] );
		return $path_parts[0];
	}


	private function is_tcl_loaded() {
		return (bool) apply_filters( 'toolset_is_toolset_common_available', false );
	}


	private function get_tcl_version() {
		if( defined( 'TOOLSET_COMMON_VERSION_NUMBER' ) ) {
			return TOOLSET_COMMON_VERSION_NUMBER;
		} else {
			return ' ??';
		}
	}


	private function get_branch_info( $repository_path ) {

		require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'git_functionality.php';

		$git_head_file = Git\get_git_head_file_content( $repository_path );
		if ( ! $git_head_file ) {
			return '';
		}

		$branch_name = Git\get_branch_name( $git_head_file );

		if ( ! $branch_name ) {
			return '';
		}

		$git_fetch_head_file_time = Git\get_git_fetch_head_file_time( $repository_path );
		$last_pulled = $this->file_time_string( $git_fetch_head_file_time );

		$result = sprintf( ' @ %s%s',  $branch_name, $last_pulled );

		return $result;

	}


	private function get_tcl_branch_name() {

		if ( ! defined( 'TOOLSET_COMMON_PATH' ) ) {
			return '';
		}

		return $this->get_branch_info( TOOLSET_COMMON_PATH );

	}


	/**
	 * @param string $plugin_slug
	 * @param \WP_Admin_Bar $wp_admin_bar
	 * @param string $parent
	 */
	private function add_plugin_node( $plugin_slug, $wp_admin_bar, $parent ) {

		$plugin_config = $this->supported_plugins[ $plugin_slug ];

		$is_plugin_active = defined( $plugin_config['is_active'] );

		if( ! $is_plugin_active ) {
			return;
		}

		$abspath = $plugin_config['abspath'];
		if( is_callable( $abspath ) ) {
			$abspath = call_user_func( $abspath );
		} else {
			$abspath = constant( $abspath );
		}

		$plugin_string = sprintf(
			'%s: %s%s',
			$plugin_slug,
			constant( $plugin_config['version_constant'] ),
			$this->get_branch_info( $abspath )
		);

		$wp_admin_bar->add_node(
			array(
				'parent' => $parent,
				'id' => "{$parent}_{$plugin_slug}",
				'title' => $plugin_string
			)
		);
	}


	function file_time_string( $git_fetch_head_file_time ) {
		if( ! $git_fetch_head_file_time ) {
			return ' &#x292B;';
		}
		$time_elapsed = human_time_diff( $git_fetch_head_file_time );

		return sprintf( ' &#x2798; %s ago', $time_elapsed );
	}


	/**
	 * @param \WP_Admin_Bar $wp_admin_bar
	 * @param string $parent_slug
	 */
	public function add_m2m_node( $wp_admin_bar, $parent_slug ) {

		$is_m2m_ready = apply_filters( 'toolset_is_m2m_ready', false );
		$is_m2m_enabled = apply_filters( 'toolset_is_m2m_enabled', false );
		if( $is_m2m_enabled ) {
			$state = 'enabled';
		} elseif( $is_m2m_ready ) {
			$state = 'ready';
		} else {
			$state = 'missing';
		}

		$tags = array();

		if( 'missing' != $state ) {

			$m2m_controller = \Toolset_Relationship_Controller::get_instance();
			if( ! method_exists( $m2m_controller, 'is_fully_initialized' ) ) {

				$tags[] = 'init-unknown';

			} else {

				$is_fully_initialized = $m2m_controller->is_fully_initialized();

				if ( $is_fully_initialized ) {
					$tags[] = 'full';

					/* $wpml_interop = \Toolset_Relationship_WPML_Interoperability::get_instance();
					if( $wpml_interop->is_interop_active() ) {
						$tags[] = 'wpml-interop';
					}

					if ( ! $wpml_interop->is_full_refresh_needed() ) {
						$tags[] = 'refresh-needed';
					} */

				} elseif( 'ready' != $state ) {

					$tags[] = 'core';

					/* if( $is_fully_initialized ) {
						$multilingual_mode = \Toolset_Relationship_Multilingual_Mode::get();
						if ( 'off' !== $multilingual_mode ) {
							$tags[] = "(wpml-interop: $multilingual_mode)";
						}
					} */
				}
			}

		}

		$output = sprintf(
			'm2m: %s%s',
			$state,
			empty( $tags ) ? '' : ' | ' . implode( ' ', $tags )
		);

		$wp_admin_bar->add_node(
			array(
				'parent' => $parent_slug,
				'id' => "{$parent_slug}_m2m",
				'title' => $output
			)
		);
	}


	/**
	 * WordPress option that, if not empty, holds an array of TCL path and URL which should be forcibly loaded.
	 *
	 * array(
	 *     'path' => $tcl_abspath,
	 *     'url' => $tcl_base_url
	 * )
	 */
	const FORCED_TCL_PATH_OPTION = 'tcl_status_forced_tcl_path';


	/**
	 * Add an admin menu item with a list of detected TCL instances.
	 *
	 * The user can choose one, which will reload the page (twice) and update the FORCED_TCL_PATH_OPTION.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar
	 * @param string $parent_id
	 */
	private function add_tcl_force_menu( $wp_admin_bar, $parent_id ) {

		$tcl_force_id = "{$parent_id}_tcl_force";

		$wp_admin_bar->add_node(
			array(
				'parent' => $parent_id,
				'id' => $tcl_force_id,
				'title' => "Force TCL location"
			)
		);

		// Add a menu item for each path
		$toolset_common_paths = $this->get_possible_common_paths();

		foreach( $toolset_common_paths as $version => $paths ) {

			$basename = $this->get_basename( $paths['path'] );

			$wp_admin_bar->add_node(
				array(
					'parent' => $tcl_force_id,
					'id' => "{$tcl_force_id}_$basename",
					'title' => sprintf( '%s (%d)', $basename, $version ),

					// these args will be intercepted and used for updating the option
					'href' => esc_url(
						add_query_arg(
							array(
								'tcl-force' => '1',
								'tcl-path' => esc_attr( $paths['path'] ),
								'tcl-url' => esc_attr( isset( $paths['url'] ) ? $paths['url'] : '' )
							),
							$_SERVER['REQUEST_URI']
						)
					)
				)
			);
		}

		// Finally, an item for clearing the option.
		$wp_admin_bar->add_node(
			array(
				'parent' => $tcl_force_id,
				'id' => "{$tcl_force_id}_restore",
				'title' => 'Restore normal operation',
				'href' => esc_url(
					add_query_arg(
						array(
							'tcl-force' => '0'
						),
						$_SERVER['REQUEST_URI']
					)
				)
			)
		);
	}


	/**
	 * Get a list of detected TCL instances.
	 *
	 * It is structured in the same way as the $toolset_common_paths global.
	 * Because of that, TCL instances are indexed by versions and the ones with the same version will get overwitten.
	 *
	 * In order to mitigate this, we're adding a list of known paths that will be checked and additional
	 * entries will be added with the lowest version numbers possible.
	 *
	 * @return array
	 */
	private function get_possible_common_paths() {
		global $toolset_common_paths;

		$results = $toolset_common_paths;

		$paths_to_check = array(
			'types' => array(
				'path' => 'types/library/toolset/toolset-common'
			)
		);

		// Manually add predefined paths if their plugin basenames are missing in the results
		$lowest_index = 1;
		foreach( $paths_to_check as $basename => $paths ) {
			if( ! $this->common_paths_contain_basename( $results, $basename ) ) {
				$results[ $lowest_index ] = array(
					'path' => trailingslashit( WP_PLUGIN_DIR ) . $paths['path'],
					'url' => plugins_url() . '/' . $paths['path']
				);
				++$lowest_index;
			}
		}


		return $results;
	}


	private function common_paths_contain_basename( $toolset_common_paths, $basename ) {
		if ( !empty( $toolset_common_paths ) ) {
			foreach( $toolset_common_paths as $paths ) {
				$current_basename = $this->get_basename( $paths['path'] );
				if( $current_basename == $basename ) {
					return true;
				}
			}
		}

		return false;
	}


	/**
	 * Check for URL parameters and if tcl-force one is detected, update the option.
	 *
	 * After updating, these parameters are removed from the URI and the page is reloaded.
	 */
	public function save_tcl_force_setting() {

		if( ! isset( $_GET['tcl-force'] ) ) {
			return;
		}

		if( '1' == $_GET['tcl-force'] ) {
			$option = array(
				'path' => $_GET['tcl-path'],
				'url' => $_GET['tcl-url']
			);
		} else {
			$option = '';
		}

		\update_option( self::FORCED_TCL_PATH_OPTION, $option, true );

		$redirect_to = \esc_url( \remove_query_arg( array( 'tcl-force', 'tcl-path', 'tcl-url' ), $_SERVER['REQUEST_URI'] ) );
		\wp_redirect( $redirect_to );
		exit;
	}


	/**
	 * Forcibly load the TCL instance, if it's defined in the option.
	 *
	 * We'll just add another entry for it, with a very high priority. The TCL loading mechanism will handle the rest.
	 */
	public function force_tcl_setting() {
		$forced_tcl = get_option( self::FORCED_TCL_PATH_OPTION, '' );
		if( !is_array( $forced_tcl ) ) {
			return;
		}

		// Primitive safety measure
		$loader_path = untrailingslashit( $forced_tcl['path'] ) . DIRECTORY_SEPARATOR . 'loader.php';
		if( ! file_exists( $loader_path ) ) {
			return;
		}

		global $toolset_common_paths;

		$toolset_common_paths[ 999999 ] = $forced_tcl;
	}


}


Main::initialize();
