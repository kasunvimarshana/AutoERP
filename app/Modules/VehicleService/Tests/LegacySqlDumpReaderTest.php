<?php

declare(strict_types=1);

namespace Modules\VehicleService\Tests;

use Modules\VehicleService\Services\Import\LegacySqlDumpReader;
use PHPUnit\Framework\TestCase;

final class LegacySqlDumpReaderTest extends TestCase
{
    public function test_it_reads_only_allowlisted_insert_rows_without_executing_sql(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'legacy-sql-reader-');
        self::assertIsString($path);
        file_put_contents($path, <<<'SQL'
-- This text and every non-INSERT statement must remain inert.
DROP TABLE vehicles;
INSERT INTO `vehicle_service_jobs` (`id`, `notes`, `nullable_value`) VALUES
(1, 'Oil, filter and wash', NULL),
(2, 'Customer\'s request', 'kept');
INSERT INTO `ignored_table` (`id`) VALUES (99);
SQL);

        try {
            $rows = (new LegacySqlDumpReader)->read($path, ['vehicle_service_jobs']);
        } finally {
            @unlink($path);
        }

        self::assertSame([
            ['id' => '1', 'notes' => 'Oil, filter and wash', 'nullable_value' => null],
            ['id' => '2', 'notes' => "Customer's request", 'nullable_value' => 'kept'],
        ], $rows['vehicle_service_jobs']);
        self::assertArrayNotHasKey('ignored_table', $rows);
    }
}
