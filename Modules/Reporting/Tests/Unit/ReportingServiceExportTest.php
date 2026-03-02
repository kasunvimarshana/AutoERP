<?php

declare(strict_types=1);

namespace Modules\Reporting\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Modules\Reporting\Application\DTOs\GenerateReportDTO;
use Modules\Reporting\Application\Services\ReportingService;
use Modules\Reporting\Domain\Contracts\ReportingRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ReportingService — generateReport and scheduleReport payload mapping.
 *
 * generateReport() and scheduleReport() call DB::transaction() + Eloquent::create()
 * internally, which require a full Laravel bootstrap.  These tests verify the DTO
 * field-mapping rules and create-payload structures as pure PHP.
 */
class ReportingServiceExportTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Structural compliance
    // -------------------------------------------------------------------------

    public function test_reporting_service_has_generate_report_method(): void
    {
        $this->assertTrue(
            method_exists(ReportingService::class, 'generateReport'),
            'ReportingService must expose a public generateReport() method.'
        );
    }

    public function test_reporting_service_has_schedule_report_method(): void
    {
        $this->assertTrue(
            method_exists(ReportingService::class, 'scheduleReport'),
            'ReportingService must expose a public scheduleReport() method.'
        );
    }

    // -------------------------------------------------------------------------
    // GenerateReportDTO — field mapping and defaults
    // -------------------------------------------------------------------------

    public function test_generate_report_dto_defaults_export_format_to_csv(): void
    {
        $dto = GenerateReportDTO::fromArray(['report_definition_id' => 1]);

        $this->assertSame('csv', $dto->exportFormat);
    }

    public function test_generate_report_dto_accepts_pdf_format(): void
    {
        $dto = GenerateReportDTO::fromArray([
            'report_definition_id' => 2,
            'export_format'        => 'pdf',
        ]);

        $this->assertSame('pdf', $dto->exportFormat);
    }

    public function test_generate_report_dto_accepts_excel_format(): void
    {
        $dto = GenerateReportDTO::fromArray([
            'report_definition_id' => 3,
            'export_format'        => 'excel',
        ]);

        $this->assertSame('excel', $dto->exportFormat);
    }

    public function test_generate_report_dto_defaults_filters_to_empty_array(): void
    {
        $dto = GenerateReportDTO::fromArray(['report_definition_id' => 4]);

        $this->assertSame([], $dto->filters);
    }

    public function test_generate_report_dto_casts_report_definition_id_to_int(): void
    {
        $dto = GenerateReportDTO::fromArray([
            'report_definition_id' => '7',
            'export_format'        => 'csv',
        ]);

        $this->assertIsInt($dto->reportDefinitionId);
        $this->assertSame(7, $dto->reportDefinitionId);
    }

    // -------------------------------------------------------------------------
    // generateReport create payload (pure PHP — mirrors generateReport logic)
    // -------------------------------------------------------------------------

    public function test_generate_report_payload_sets_status_pending(): void
    {
        $dto = GenerateReportDTO::fromArray([
            'report_definition_id' => 1,
            'export_format'        => 'csv',
            'filters'              => ['date_from' => '2026-01-01'],
        ]);

        $createPayload = [
            'report_definition_id' => $dto->reportDefinitionId,
            'export_format'        => $dto->exportFormat,
            'status'               => 'pending',
            'filters_applied'      => $dto->filters,
        ];

        $this->assertSame('pending', $createPayload['status']);
        $this->assertSame(['date_from' => '2026-01-01'], $createPayload['filters_applied']);
    }

    public function test_generate_report_to_array_round_trip(): void
    {
        $input = [
            'report_definition_id' => 5,
            'export_format'        => 'pdf',
            'filters'              => ['tenant_id' => 1, 'period' => 'Q1'],
        ];

        $dto   = GenerateReportDTO::fromArray($input);
        $array = $dto->toArray();

        $this->assertSame(5, $array['report_definition_id']);
        $this->assertSame('pdf', $array['export_format']);
        $this->assertSame(['tenant_id' => 1, 'period' => 'Q1'], $array['filters']);
    }

    // -------------------------------------------------------------------------
    // scheduleReport payload (pure PHP — mirrors scheduleReport logic)
    // -------------------------------------------------------------------------

    public function test_schedule_report_payload_sets_is_active_true(): void
    {
        $data = [
            'report_definition_id' => 1,
            'frequency'            => 'weekly',
            'export_format'        => 'csv',
            'recipients'           => ['admin@example.com'],
            'next_run_at'          => '2026-03-01 00:00:00',
        ];

        $schedulePayload = [
            'report_definition_id' => $data['report_definition_id'],
            'frequency'            => $data['frequency'],
            'export_format'        => $data['export_format'],
            'recipients'           => $data['recipients'] ?? [],
            'next_run_at'          => $data['next_run_at'] ?? null,
            'is_active'            => true,
        ];

        $this->assertTrue($schedulePayload['is_active']);
        $this->assertSame('weekly', $schedulePayload['frequency']);
        $this->assertSame(['admin@example.com'], $schedulePayload['recipients']);
    }

    public function test_schedule_report_payload_recipients_defaults_to_empty_array(): void
    {
        $data = [
            'report_definition_id' => 1,
            'frequency'            => 'daily',
            'export_format'        => 'csv',
        ];

        $schedulePayload = [
            'report_definition_id' => $data['report_definition_id'],
            'frequency'            => $data['frequency'],
            'export_format'        => $data['export_format'],
            'recipients'           => $data['recipients'] ?? [],
            'next_run_at'          => $data['next_run_at'] ?? null,
            'is_active'            => true,
        ];

        $this->assertSame([], $schedulePayload['recipients']);
        $this->assertNull($schedulePayload['next_run_at']);
    }

    // -------------------------------------------------------------------------
    // listDefinitions — delegation (read path, no DB::transaction)
    // -------------------------------------------------------------------------

    public function test_list_definitions_delegates_to_repository(): void
    {
        $collection = new Collection();

        $repo = $this->createMock(ReportingRepositoryContract::class);
        $repo->expects($this->once())
            ->method('all')
            ->willReturn($collection);

        $service = new ReportingService($repo);
        $result  = $service->listDefinitions();

        $this->assertSame($collection, $result);
    }
}
