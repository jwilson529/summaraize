<?php
define( 'DB_NAME', 'wordpress_test' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', 'root' );
define( 'DB_HOST', 'db' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

define( 'ABSPATH', '/work/.wp-core/' );
define( 'WP_DEBUG', true );

$table_prefix = 'wptests_';

require_once '/work/.wp-tests/includes/functions.php';
