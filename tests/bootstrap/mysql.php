<?php

declare(strict_types=1);

const AUTOERP_SUPPORTED_MYSQL_TEST_CONNECTIONS = ['mysql', 'mariadb'];
const AUTOERP_TEST_DATABASE_SUFFIX = '_test';

$connection = strtolower(trim((string) getenv('DB_CONNECTION')));
$database = strtolower(trim((string) getenv('DB_DATABASE')));
$databaseUrl = trim((string) getenv('DB_URL'));

if (! in_array($connection, AUTOERP_SUPPORTED_MYSQL_TEST_CONNECTIONS, true)) {
    throw new RuntimeException('MySQL verification requires DB_CONNECTION=mysql or DB_CONNECTION=mariadb.');
}

if ($databaseUrl !== '') {
    throw new RuntimeException('MySQL verification requires DB_URL to be empty so the guarded DB_DATABASE target is authoritative.');
}

if ($database === '' || ! str_ends_with($database, AUTOERP_TEST_DATABASE_SUFFIX)) {
    throw new RuntimeException('MySQL verification requires a disposable DB_DATABASE ending with _test.');
}

require dirname(__DIR__, 2).'/vendor/autoload.php';
