#!/usr/bin/env bash
# GENERATED_BY_CODEX_YOLO_DOCKER_TESTS_V3
set -euo pipefail

WORKDIR="/work"
WP_TESTS_DIR="${WORKDIR}/.wp-tests"
WP_CORE_DIR="${WORKDIR}/.wp-core"

DB_HOST="${WP_TESTS_DB_HOST:-db}"
DB_NAME="${WP_TESTS_DB_NAME:-wordpress_test}"
DB_USER="${WP_TESTS_DB_USER:-root}"
DB_PASS="${WP_TESTS_DB_PASS:-root}"
WP_VERSION="${WP_VERSION:-latest}"
PHPUNIT_VERSION="${PHPUNIT_VERSION:-9.6.20}"
WP_TESTS_DOMAIN="${WP_TESTS_DOMAIN:-example.com}"
WP_TESTS_EMAIL="${WP_TESTS_EMAIL:-test@example.com}"
WP_TESTS_TITLE="${WP_TESTS_TITLE:-Summaraize Tests Suite}"
WP_PHP_BINARY="${WP_PHP_BINARY:-$(command -v php)}"

echo "==> Installing system deps"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq --no-install-recommends git unzip mariadb-client curl rsync ca-certificates
if command -v docker-php-ext-install >/dev/null 2>&1; then
	docker-php-ext-install mysqli pdo_mysql
fi

echo "==> Installing PHPUnit ${PHPUNIT_VERSION}"
curl -Ls -o /usr/local/bin/phpunit "https://phar.phpunit.de/phpunit-${PHPUNIT_VERSION}.phar"
chmod +x /usr/local/bin/phpunit
phpunit --version

echo "==> Installing WP core for tests"
mkdir -p "${WP_TESTS_DIR}" "${WP_CORE_DIR}"

if [ ! -f "${WP_CORE_DIR}/wp-load.php" ]; then
	curl -Ls -o /tmp/wp.tar.gz "https://wordpress.org/latest.tar.gz"
	tar -xzf /tmp/wp.tar.gz -C /tmp
	rsync -a /tmp/wordpress/ "${WP_CORE_DIR}/"
fi

echo "==> Installing WP test suite"
if [ ! -d "${WP_TESTS_DIR}/includes" ]; then
	git clone --depth=1 https://github.com/WordPress/wordpress-develop.git /tmp/wp-develop
	rsync -a /tmp/wp-develop/tests/phpunit/ "${WP_TESTS_DIR}/"
fi

echo "==> Creating wp-tests-config.php"
cat > "${WP_TESTS_DIR}/wp-tests-config.php" <<CFG
<?php
define( 'DB_NAME', '${DB_NAME}' );
define( 'DB_USER', '${DB_USER}' );
define( 'DB_PASSWORD', '${DB_PASS}' );
define( 'DB_HOST', '${DB_HOST}' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

define( 'ABSPATH', '${WP_CORE_DIR}/' );
define( 'WP_DEBUG', true );
define( 'WP_TESTS_DOMAIN', '${WP_TESTS_DOMAIN}' );
define( 'WP_TESTS_EMAIL', '${WP_TESTS_EMAIL}' );
define( 'WP_TESTS_TITLE', '${WP_TESTS_TITLE}' );
define( 'WP_PHP_BINARY', '${WP_PHP_BINARY}' );

\$table_prefix = 'wptests_';

require_once '${WP_TESTS_DIR}/includes/functions.php';
CFG

echo "==> Creating test database if needed"
MYSQL_SSL_FLAG=""
if mysql --help 2>/dev/null | grep -q "ssl-mode"; then
    MYSQL_SSL_FLAG="--ssl-mode=DISABLED"
elif mysql --help 2>/dev/null | grep -q "skip-ssl"; then
    MYSQL_SSL_FLAG="--skip-ssl"
elif mysql --help 2>/dev/null | grep -q "ssl"; then
    MYSQL_SSL_FLAG="--ssl=0"
fi
mysql ${MYSQL_SSL_FLAG} -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`;" >/dev/null

echo "==> Running PHPUnit"
cd "${WORKDIR}"
phpunit
