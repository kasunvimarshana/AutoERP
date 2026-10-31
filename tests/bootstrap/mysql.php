<?php

declare(strict_types=1);

const AUTOERP_SUPPORTED_MYSQL_TEST_CONNECTIONS = ['mysql', 'mariadb'];
const AUTOERP_TEST_DATABASE_SUFFIX = '_test';

$connection = strtolower(trim((string) getenv('DB_CONNECTION')));
$database = trim((string) getenv('DB_DATABASE'));
$databaseUrl = trim((string) getenv('DB_URL'));
$host = trim((string) getenv('DB_HOST'));

if (! in_array($connection, AUTOERP_SUPPORTED_MYSQL_TEST_CONNECTIONS, true)) {
    throw new RuntimeException('MySQL verification requires DB_CONNECTION=mysql or DB_CONNECTION=mariadb.');
}

if ($databaseUrl !== '') {
    throw new RuntimeException('MySQL verification requires DB_URL to be empty so the guarded DB_DATABASE target is authoritative.');
}

if ($database === '' || ! str_ends_with(strtolower($database), AUTOERP_TEST_DATABASE_SUFFIX)) {
    throw new RuntimeException('MySQL verification requires a disposable DB_DATABASE ending with _test.');
}

if ($host === '') {
    throw new RuntimeException('MySQL verification requires an explicitly selected DB_HOST; do not inherit a host from the application .env file.');
}

// The PHPUnit bootstrap executes before Laravel loads .env. Pin the validated
// connection, database, host and empty URL in each environment store so a
// production DB_URL/DB_HOST in .env cannot override this disposable target.
foreach (['DB_CONNECTION' => $connection, 'DB_DATABASE' => $database, 'DB_HOST' => $host, 'DB_URL' => ''] as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}

require dirname(__DIR__, 2).'/vendor/autoload.php';
