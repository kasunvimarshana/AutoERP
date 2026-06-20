<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class ModuleMigrationBaselineTest extends TestCase
{
    public function test_module_migrations_form_a_one_table_per_file_baseline(): void
    {
        $root = dirname(__DIR__, 3);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root.'/app/Modules', FilesystemIterator::SKIP_DOTS),
        );
        $migrations = [];

        foreach ($iterator as $file) {
            $path = str_replace('\\', '/', $file->getPathname());
            if ($file->isFile() && $file->getExtension() === 'php' && str_contains($path, '/Database/Migrations/')) {
                $migrations[] = $path;
            }
        }

        sort($migrations);
        self::assertCount(216, $migrations);

        $timestamps = [];
        $tables = [];
        $violations = [];

        foreach ($migrations as $migration) {
            $source = (string) file_get_contents($migration);
            preg_match_all("/Schema::create\\('([^']+)'/", $source, $creates);
            preg_match_all("/Schema::dropIfExists\\('([^']+)'\\)/", $source, $drops);
            preg_match('/^(\\d{4}_\\d{2}_\\d{2}_\\d{6})_/', basename($migration), $timestamp);

            if (count($creates[1]) !== 1 || count($drops[1]) !== 1 || $creates[1] !== $drops[1]) {
                $violations[] = basename($migration).': expected one matching create/drop pair';
            }
            if (str_contains($source, 'Schema::table(')) {
                $violations[] = basename($migration).': contains Schema::table';
            }
            if (str_contains($source, 'DB::table(')) {
                $violations[] = basename($migration).': contains migration data access';
            }
            if ($timestamp === []) {
                $violations[] = basename($migration).': missing canonical timestamp';
            } elseif (isset($timestamps[$timestamp[1]])) {
                $violations[] = basename($migration).': duplicates timestamp '.$timestamp[1];
            } else {
                $timestamps[$timestamp[1]] = true;
            }
            if ($creates[1] !== [] && isset($tables[$creates[1][0]])) {
                $violations[] = basename($migration).': duplicates table '.$creates[1][0];
            } elseif ($creates[1] !== []) {
                $tables[$creates[1][0]] = true;
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
        self::assertCount(216, $tables);
    }
}
