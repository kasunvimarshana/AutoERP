<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class HistoricalRetentionMigrationTest extends TestCase
{
    private const STATUS_HISTORY_MIGRATION_SUFFIX = '_status_histories_table.php';

    private const DESTRUCTIVE_DELETE_ACTION = '->cascadeOnDelete()';

    private const RETENTIVE_DELETE_ACTION = '->restrictOnDelete()';

    public function test_status_history_migrations_retain_history_when_parent_records_are_deleted(): void
    {
        $migrations = $this->statusHistoryMigrations();
        self::assertNotEmpty($migrations, 'No status-history migrations were discovered.');

        foreach ($migrations as $migration) {
            $source = $this->source($migration->getPathname());
            self::assertStringNotContainsString(
                self::DESTRUCTIVE_DELETE_ACTION,
                $source,
                $migration->getPathname().' must not cascade-delete historical evidence.',
            );
            self::assertStringContainsString(
                self::RETENTIVE_DELETE_ACTION,
                $source,
                $migration->getPathname().' must retain parent-linked historical evidence.',
            );
        }
    }

    /** @return list<SplFileInfo> */
    private function statusHistoryMigrations(): array
    {
        $migrations = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->modulesPath('')));

        foreach ($iterator as $file) {
            if (
                $file instanceof SplFileInfo
                && $file->isFile()
                && str_ends_with($file->getFilename(), self::STATUS_HISTORY_MIGRATION_SUFFIX)
            ) {
                $migrations[] = $file;
            }
        }

        return $migrations;
    }

    private function source(string $path): string
    {
        $source = file_get_contents($path);
        self::assertNotFalse($source, $path);

        return $source;
    }

    private function modulesPath(string $relativePath): string
    {
        $base = dirname(__DIR__, 3).'/app/Modules';

        return $relativePath === '' ? $base.DIRECTORY_SEPARATOR : $base.'/'.$relativePath;
    }
}