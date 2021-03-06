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


function get_git_fetch_head_file_time( $repository_path ) {
	$head_fetch_file_path = construct_fetch_head_path( $repository_path );
	if (!is_file($head_fetch_file_path)) {
		$time = null;
	} else {
		$time = filemtime($head_fetch_file_path);
	}

	return  $time;
}


function construct_fetch_head_path( $repository_path ) {
	$git_dir_name = git_directory_path( $repository_path );

	$file_path = $git_dir_name . DIRECTORY_SEPARATOR . 'FETCH_HEAD';

	return $file_path;
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

			// Handle special case with feature/issue-000 branch names
			if( 4 == count( $in_line ) && 'heads' == $in_line[1] ) {
				$branch_name = $in_line[2] . '/' . $in_line[3];
			} else {
				$branch_name = $in_line[ count( $in_line ) - 1 ];
			}
			break;
		}
	}

	return $branch_name;
}