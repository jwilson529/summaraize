<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Summaraize
 */

if ( 'cli' !== php_sapi_name() ) {
	defined( 'ABSPATH' ) || exit;
}

$_plugin_root = dirname( __DIR__ );
$_tests_root  = $_plugin_root . '/.wp-tests';

if ( ! defined( 'ABSPATH' ) && ! getenv( 'WP_TESTS_DIR' ) && ! is_dir( $_tests_root . '/includes' ) ) {
	// Keep a clear failure mode when local tests are run without the WP test suite.
	echo "WP test suite is not available. Set WP_TESTS_DIR to a valid suite path or run `npm test` to bootstrap it.\n";
	exit( 1 );
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = $_tests_root;
}

if ( ! defined( 'WP_TESTS_CONFIG_FILE_PATH' ) ) {
	define( 'WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config-local.php' );
}

if ( ! defined( 'WP_TESTS_DIR' ) ) {
	define( 'WP_TESTS_DIR', $_tests_dir );
}

if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	$possible_polyfills_path = dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills';
	if ( is_dir( $possible_polyfills_path ) ) {
		define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $possible_polyfills_path );
	}
}

require_once WP_TESTS_DIR . '/includes/functions.php';

/**
 * Load the plugin for integration tests.
 */
function plugin_load_for_tests() {
	require dirname( __DIR__ ) . '/summaraize.php';
}

tests_add_filter( 'muplugins_loaded', 'plugin_load_for_tests' );

require_once WP_TESTS_DIR . '/includes/bootstrap.php';
