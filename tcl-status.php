<?php
/*
Plugin Name: Toolset Common Status
Plugin URI: https://github.com/zaantar/tcl-status
Description:
Version: 0.1-dev
Author: hkirat, zaantar
Author URI: http://zaantar.eu
Text Domain: tcl-status
Domain Path: /languages
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

namespace TclStatus;

final class Main {

	const PARENT_NOTE_ID = 'tcl-status';

	public static function initialize() {
		new self();
	}


	public function __construct() {
		add_action( 'admin_bar_menu', array( $this, 'modify_admin_bar' ), 999 );

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

	}


	private function get_tcl_string() {
		if( $this->is_tcl_loaded() ) {
			return sprintf( 'tcl: %s (%d%s)', $this->get_tcl_plugin_location(), $this->get_tcl_version(), $this->get_tcl_branch_name() );
		} else {
			return 'tcl: ----';
		}
	}


	private function get_tcl_plugin_location() {
		if( ! defined( 'TOOLSET_COMMON_PATH' ) ) {
			return '----';
		}

		// path to the common library, relative to WP plugin directory
		$basename = plugin_basename( TOOLSET_COMMON_PATH );

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
			return 0;
		}
	}


	private function get_branch_name( $repository_path ) {

		require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'git_functionality.php';

		$git_head_file = Git\get_git_head_file_content( $repository_path );
		if ( ! $git_head_file ) {
			return '';
		}

		$branch_name = Git\get_branch_name( $git_head_file );

		if ( ! $branch_name ) {
			return '';
		}

		$result = sprintf( ' @ %s',  $branch_name );

		return $result;

	}


	private function get_tcl_branch_name() {

		if ( ! defined( 'TOOLSET_COMMON_PATH' ) ) {
			return '';
		}

		return $this->get_branch_name( TOOLSET_COMMON_PATH );

	}


	private $supported_plugins = array();


	/**
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
			$this->get_branch_name( $abspath )
		);

		$wp_admin_bar->add_node(
			array(
				'parent' => $parent,
				'id' => "{$parent}_{$plugin_slug}",
				'title' => $plugin_string
			)
		);
	}

}


Main::initialize();