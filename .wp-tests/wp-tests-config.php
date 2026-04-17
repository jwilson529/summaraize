<?php
define( 'DB_NAME', 'wordpress_test' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', 'root' );
define( 'DB_HOST', 'db' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

define( 'ABSPATH', '/work/.wp-core/' );
define( 'WP_DEBUG', true );
define( 'WP_TESTS_DOMAIN', 'example.com' );
define( 'WP_TESTS_EMAIL', 'test@example.com' );
define( 'WP_TESTS_TITLE', 'Summaraize Tests Suite' );
define( 'WP_PHP_BINARY', '/usr/local/bin/php' );

$table_prefix = 'wptests_';

require_once '/work/.wp-tests/includes/functions.php';
