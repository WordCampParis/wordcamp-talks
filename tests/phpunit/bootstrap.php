<?php

require_once getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit/includes/functions.php';

function _bootstrap_wct() {
	if ( defined( 'WP_ADMIN' ) && WP_ADMIN ) {
		$PHP_SELF = $GLOBALS['PHP_SELF'] = $_SERVER['PHP_SELF'] = '/wp-admin/index.php';
	}

	// load WordCamp Talk Proposals
	require dirname( __FILE__ ) . '/../../wordcamp-talks.php';
}
tests_add_filter( 'muplugins_loaded', '_bootstrap_wct' );


function _wct_assets_lang_dir( $mofile_path = '', $mofile = '' ) {
	$assets_dir = dirname( __FILE__ ) . '/assets';
	return $assets_dir . '/' . $mofile;

}
tests_add_filter( 'wordcamp_talks_lang_dir', '_wct_assets_lang_dir', 10, 2 );

function _wct_assets_available_languages( $languages = array() ) {
	return array_merge( $languages, array( 'fr_FR' ) );

}
tests_add_filter( 'get_available_languages', '_wct_assets_available_languages', 10, 1 );

function _wct_assets_register_wordcamp_post_type() {
	global $argv;

	if ( isset( $argv[1] ) && '--group' === $argv[1] && isset( $argv[2] ) && 'wordcamp' === $argv[2] ) {
		require_once dirname( __FILE__ ) . '/assets/class-wordcamp-post-types-plugin.php';
	}
}
tests_add_filter( 'plugins_loaded', '_wct_assets_register_wordcamp_post_type', 1 );

require getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit/includes/bootstrap.php';

// include our testcase
require( 'testcase.php' );
