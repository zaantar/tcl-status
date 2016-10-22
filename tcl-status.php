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

		/*$wp_admin_bar->add_node(
			array(
				'parent' => self::PARENT_NOTE_ID,
				'id' => 'xxxxxx',
				'title' => $this->get_tcl_string()
			)
		);*/
	}


	private function get_tcl_string() {
		if( $this->is_tcl_loaded() ) {
			return sprintf( 'tcl: %s (%d)', $this->get_tcl_plugin_location(), $this->get_tcl_version() );
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
}


Main::initialize();