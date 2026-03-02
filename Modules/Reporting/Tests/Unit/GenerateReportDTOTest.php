<?php

declare(strict_types=1);

namespace Modules\Reporting\Tests\Unit;

use Modules\Reporting\Application\DTOs\GenerateReportDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for GenerateReportDTO.
 *
 * Pure PHP — no database or Laravel bootstrap required.
 */
class GenerateReportDTOTest extends TestCase
{
    public function test_from_array_hydrates_all_fields(): void
    {
        $dto = GenerateReportDTO::fromArray([
            'report_definition_id' => 5,
            'export_format'        => 'pdf',
            'filters'              => ['date_from' => '2026-01-01', 'date_to' => '2026-01-31'],
        ]);

        $this->assertSame(5, $dto->reportDefinitionId);
        $this->assertSame('pdf', $dto->exportFormat);
        $this->assertSame(['date_from' => '2026-01-01', 'date_to' => '2026-01-31'], $dto->filters);
    }

    public function test_from_array_defaults_export_format_to_csv(): void
    {
        $dto = GenerateReportDTO::fromArray([
            'report_definition_id' => 1,
        ]);

        $this->assertSame('csv', $dto->exportFormat);
    }

    public function test_from_array_defaults_filters_to_empty_array(): void
    {
        $dto = GenerateReportDTO::fromArray([
            'report_definition_id' => 1,
        ]);

        $this->assertSame([], $dto->filters);
    }

    public function test_from_array_defaults_definition_id_to_zero(): void
    {
        $dto = GenerateReportDTO::fromArray([]);

        $this->assertSame(0, $dto->reportDefinitionId);
    }

    public function test_report_definition_id_cast_to_int(): void
    {
        $dto = GenerateReportDTO::fromArray([
            'report_definition_id' => '8',
        ]);

        $this->assertIsInt($dto->reportDefinitionId);
        $this->assertSame(8, $dto->reportDefinitionId);
    }
}
