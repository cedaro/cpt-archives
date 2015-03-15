<?php
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	if ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
		$_tests_dir = getenv( 'WP_DEVELOP_DIR' ) . 'tests/phpunit';
	}

	elseif ( file_exists( '../../../../../tests/phpunit/includes/bootstrap.php' ) ) {
		$_tests_dir = '../../../../../tests/phpunit';
	}

	elseif ( file_exists( '/tmp/wordpress-tests-lib/includes/bootstrap.php' ) ) {
		$_tests_dir = '/tmp/wordpress-tests-lib';
	}
}

$GLOBALS['wp_tests_options'] = array(
	'active_plugins'  => array(
		'cpt-archives/cpt-archives.php',
	),
	'timezone_string' => 'America/Los_Angeles',
);

// @link https://core.trac.wordpress.org/browser/trunk/tests/phpunit/includes/functions.php
require_once $_tests_dir . '/includes/functions.php';

tests_add_filter( 'muplugins_loaded', function() {
	require( dirname( dirname( __DIR__ ) ) . '/cpt-archives.php' );
} );

require $_tests_dir . '/includes/bootstrap.php';
