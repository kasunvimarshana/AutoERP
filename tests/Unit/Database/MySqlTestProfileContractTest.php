<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use PHPUnit\Framework\TestCase;

final class MySqlTestProfileContractTest extends TestCase
{
    public function test_mysql_profile_requires_an_explicit_disposable_database_without_credentials_in_source(): void
    {
        $root = dirname(__DIR__, 3);
        $configuration = file_get_contents($root.'/phpunit.mysql.xml');
        $bootstrap = file_get_contents($root.'/tests/bootstrap/mysql.php');
        $composer = file_get_contents($root.'/composer.json');

        self::assertIsString($configuration);
        self::assertIsString($bootstrap);
        self::assertIsString($composer);
        self::assertStringContainsString('bootstrap="tests/bootstrap/mysql.php"', $configuration);
        self::assertStringNotContainsString('name="DB_DATABASE"', $configuration);
        self::assertStringNotContainsString('name="DB_USERNAME"', $configuration);
        self::assertStringNotContainsString('name="DB_PASSWORD"', $configuration);
        self::assertStringContainsString("AUTOERP_TEST_DATABASE_SUFFIX = '_test'", $bootstrap);
        self::assertStringContainsString('DB_URL to be empty', $bootstrap);
        self::assertStringContainsString('"test:mysql"', $composer);
    }
}
