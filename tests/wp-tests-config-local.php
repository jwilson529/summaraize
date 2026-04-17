<?php
/**
 * Local PHPUnit config for plugin integration tests.
 *
 * @package Summaraize
 */

$plugin_root = dirname( __DIR__ );
$tests_root  = $plugin_root . '/.wp-tests';
$wp_core_dir = getenv( 'WP_CORE_DIR' );

if ( ! $wp_core_dir ) {
	$wp_core_dir = $plugin_root . '/.wp-core';
}

$db_host = getenv( 'WP_TESTS_DB_HOST' );

if ( ! $db_host ) {
	$db_host = 'db' !== gethostbyname( 'db' ) ? 'db' : '127.0.0.1';
}

$db_name = getenv( 'WP_TESTS_DB_NAME' );
if ( false === $db_name || '' === $db_name ) {
	$db_name = 'wordpress_test';
}

$db_user = getenv( 'WP_TESTS_DB_USER' );
if ( false === $db_user || '' === $db_user ) {
	$db_user = 'root';
}

$db_password = getenv( 'WP_TESTS_DB_PASS' );
if ( false === $db_password || '' === $db_password ) {
	$db_password = 'root';
}

$tests_domain = getenv( 'WP_TESTS_DOMAIN' );
if ( false === $tests_domain || '' === $tests_domain ) {
	$tests_domain = 'example.com';
}

$tests_email = getenv( 'WP_TESTS_EMAIL' );
if ( false === $tests_email || '' === $tests_email ) {
	$tests_email = 'test@example.com';
}

$tests_title = getenv( 'WP_TESTS_TITLE' );
if ( false === $tests_title || '' === $tests_title ) {
	$tests_title = 'Summaraize Tests Suite';
}

$php_binary = getenv( 'WP_PHP_BINARY' );
if ( false === $php_binary || '' === $php_binary ) {
	$php_binary = 'php';
}

define( 'DB_NAME', $db_name );
define( 'DB_USER', $db_user );
define( 'DB_PASSWORD', $db_password );
define( 'DB_HOST', $db_host );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

define( 'ABSPATH', rtrim( $wp_core_dir, '/\\' ) . '/' );
define( 'WP_DEBUG', true );
define( 'WP_TESTS_DOMAIN', $tests_domain );
define( 'WP_TESTS_EMAIL', $tests_email );
define( 'WP_TESTS_TITLE', $tests_title );
define( 'WP_PHP_BINARY', $php_binary );

// WordPress integration tests expect a mutable global table prefix here.
// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$table_prefix = 'wptests_';

require_once $tests_root . '/includes/functions.php';
