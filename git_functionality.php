<?php

/*
 * The code below was taken from the Git Branches Info plugin by Konrad Karpieszuk, http://muzungu.pl.
 */

namespace TclStatus\Git;


function get_git_head_file_content( $repository_path ) {

	$head_file_path = construct_head_path( $repository_path );

	if ( is_file( $head_file_path ) && is_readable( $head_file_path ) ) {
		$file = file_get_contents( $head_file_path );
	}

	return isset( $file ) ? $file : null;

}


function construct_head_path( $repository_path ) {
	$git_dir_name = git_directory_path( $repository_path );

	$head_file_path = $git_dir_name . DIRECTORY_SEPARATOR . 'HEAD';

	return $head_file_path;
}


function git_directory_path( $repository_path ) {
	$repository_path = untrailingslashit( $repository_path );

	if ( PHP_OS == "Windows" || PHP_OS == "WINNT" ) {
		$repository_path = str_replace( "/", DIRECTORY_SEPARATOR, $repository_path );
	}

	$git_dir_name = $repository_path . DIRECTORY_SEPARATOR . ".git";

	return $git_dir_name;
}


function get_branch_name($file_content) {
	$lines = explode( "\n", $file_content );
	$branch_name = false;
	foreach ( $lines as $line ) {
		if ( strpos( $line, 'ref:' ) === 0 ) {
			$in_line = explode( "/", $line );
			$branch_name = $in_line[ count( $in_line ) - 1 ];
			break;
		}
	}

	return $branch_name;
}