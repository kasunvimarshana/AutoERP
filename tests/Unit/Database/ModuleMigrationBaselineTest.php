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
        $metadata = [];

        foreach ($migrations as $migration) {
            $source = (string) file_get_contents($migration);
            preg_match_all("/Schema::create\\('([^']+)'/", $source, $creates);
            preg_match_all("/Schema::dropIfExists\\('([^']+)'\\)/", $source, $drops);
            preg_match('/^(\\d{4}_\\d{2}_\\d{2}_\\d{6})_/', basename($migration), $timestamp);
            $name = basename($migration);

            if (count($creates[1]) !== 1 || count($drops[1]) !== 1 || $creates[1] !== $drops[1]) {
                $violations[] = $name.': expected one matching create/drop pair';
            }
            if (str_contains($source, 'Schema::table(')) {
                $violations[] = $name.': contains Schema::table';
            }
            if (str_contains($source, 'DB::table(')) {
                $violations[] = $name.': contains migration data access';
            }
            foreach (['getDriverName(', 'DB::statement(', 'DB::unprepared(', 'DB::raw('] as $forbidden) {
                if (str_contains($source, $forbidden)) {
                    $violations[] = $name.": contains forbidden database-specific operation {$forbidden}";
                }
            }
            if (preg_match('/^\\d{4}_\\d{2}_\\d{2}_\\d{6}_create_[a-z0-9_]+_table\\.php$/', $name) !== 1) {
                $violations[] = $name.': is not a fresh-baseline create migration';
            }
            if ($timestamp === []) {
                $violations[] = $name.': missing canonical timestamp';
            } elseif (isset($timestamps[$timestamp[1]])) {
                $violations[] = $name.': duplicates timestamp '.$timestamp[1];
            } else {
                $timestamps[$timestamp[1]] = true;
            }
            if ($creates[1] !== [] && isset($tables[$creates[1][0]])) {
                $violations[] = $name.': duplicates table '.$creates[1][0];
            } elseif ($creates[1] !== []) {
                $tables[$creates[1][0]] = $timestamp[1] ?? null;
            }

            $indexes = $this->indexColumnExpressions($source, 'index');
            $uniqueIndexes = $this->indexColumnExpressions($source, 'unique');
            foreach (array_count_values($indexes) as $columns => $count) {
                if ($count > 1) {
                    $violations[] = $name.": duplicates index columns {$columns}";
                }
            }
            foreach (array_count_values($uniqueIndexes) as $columns => $count) {
                if ($count > 1) {
                    $violations[] = $name.": duplicates unique index columns {$columns}";
                }
            }
            foreach (array_intersect($indexes, $uniqueIndexes) as $columns) {
                $violations[] = $name.": index {$columns} is redundant with an identical unique index";
            }

            $metadata[] = [
                'name' => $name,
                'source' => $source,
                'timestamp' => $timestamp[1] ?? null,
                'table' => $creates[1][0] ?? null,
            ];
        }

        foreach ($metadata as $migration) {
            preg_match_all("/(?:constrained|->on)\\('([^']+)'/", $migration['source'], $references);
            foreach (array_unique($references[1]) as $reference) {
                $referencedTimestamp = $tables[$reference] ?? null;
                if ($referencedTimestamp !== null
                    && $migration['timestamp'] !== null
                    && $referencedTimestamp > $migration['timestamp']
                ) {
                    $violations[] = $migration['name'].": references {$reference} before it is created";
                }
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
        self::assertCount(216, $tables);
    }

    /**
     * @return list<string>
     */
    private function indexColumnExpressions(string $source, string $method): array
    {
        preg_match_all(
            '/\\$table->'.preg_quote($method, '/').'\\(\\s*(\\[[^\\]]+\\]|[\'\"][^\'\"]+[\'\"])/s',
            $source,
            $matches,
        );

        return array_map(
            static fn (string $columns): string => (string) preg_replace('/\\s+/', '', str_replace('"', "'", $columns)),
            $matches[1],
        );
    }
}
