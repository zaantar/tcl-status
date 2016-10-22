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

		$this->add_types_node( $wp_admin_bar, self::PARENT_NOTE_ID );
		$this->add_cred_node( $wp_admin_bar, self::PARENT_NOTE_ID );
		$this->add_views_node( $wp_admin_bar, self::PARENT_NOTE_ID );
		$this->add_access_node( $wp_admin_bar, self::PARENT_NOTE_ID );
		$this->add_layouts_node( $wp_admin_bar, self::PARENT_NOTE_ID );

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


	/**
	 * @param \WP_Admin_Bar $wp_admin_bar
	 * @param string $parent
	 */
	private function add_types_node( $wp_admin_bar, $parent ) {

		$is_types_active = defined( 'TYPES_VERSION' );

		if( ! $is_types_active ) {
			return;
		}

		$types_string = sprintf(
			'types: %s%s',
			TYPES_VERSION,
			$this->get_branch_name( TYPES_ABSPATH )
		);

		$wp_admin_bar->add_node(
			array(
				'parent' => $parent,
				'id' => "{$parent}_types",
				'title' => $types_string
			)
		);
	}


	/**
	 * @param \WP_Admin_Bar $wp_admin_bar
	 * @param string $parent
	 */
	private function add_cred_node( $wp_admin_bar, $parent ) {

		$is_cred_active = defined( 'CRED_FE_VERSION' );

		if( ! $is_cred_active ) {
			return;
		}

		// Support before and after refactoring
		$cred_path = defined( 'CRED_ABSPATH' ) ? CRED_ABSPATH : CRED_ROOT_PLUGIN_PATH;

		$cred_string = sprintf(
			'cred: %s%s',
			CRED_FE_VERSION,
			$this->get_branch_name( $cred_path )
		);

		$wp_admin_bar->add_node(
			array(
				'parent' => $parent,
				'id' => "{$parent}_cred",
				'title' => $cred_string
			)
		);
	}


	/**
	 * @param \WP_Admin_Bar $wp_admin_bar
	 * @param string $parent
	 */
	private function add_views_node( $wp_admin_bar, $parent ) {

		$is_views_active = defined( 'WPV_VERSION' );

		if( ! $is_views_active ) {
			return;
		}

		$views_string = sprintf(
			'views: %s%s',
			WPV_VERSION,
			$this->get_branch_name( WPV_PATH )
		);

		$wp_admin_bar->add_node(
			array(
				'parent' => $parent,
				'id' => "{$parent}_views",
				'title' => $views_string
			)
		);
	}


	/**
	 * @param \WP_Admin_Bar $wp_admin_bar
	 * @param string $parent
	 */
	private function add_layouts_node( $wp_admin_bar, $parent ) {

		$is_layouts_active = defined( 'WPDDL_VERSION' );

		if( ! $is_layouts_active ) {
			return;
		}

		$layouts_string = sprintf(
			'layouts: %s%s',
			WPDDL_VERSION,
			$this->get_branch_name( WPDDL_ABSPATH )
		);

		$wp_admin_bar->add_node(
			array(
				'parent' => $parent,
				'id' => "{$parent}_layouts",
				'title' => $layouts_string
			)
		);
	}


	/**
	 * @param \WP_Admin_Bar $wp_admin_bar
	 * @param string $parent
	 */
	private function add_access_node( $wp_admin_bar, $parent ) {

		$is_access_loaded = defined( 'TACCESS_VERSION' );

		if( ! $is_access_loaded ) {
			return;
		}

		$access_string = sprintf(
			'access: %s%s',
			TACCESS_VERSION,
			$this->get_branch_name( TACCESS_PLUGIN_PATH )
		);

		$wp_admin_bar->add_node(
			array(
				'parent' => $parent,
				'id' => "{$parent}_access",
				'title' => $access_string
			)
		);
	}
}


Main::initialize();