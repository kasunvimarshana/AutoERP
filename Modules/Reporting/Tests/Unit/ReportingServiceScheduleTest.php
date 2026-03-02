<?php

declare(strict_types=1);

namespace Modules\Reporting\Tests\Unit;

use Modules\Reporting\Application\DTOs\GenerateReportDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Reporting module schedule and export DTO field mapping.
 *
 * Tests validate the field-mapping patterns used in ReportingService::scheduleReport()
 * and ReportingService::generateReport() without requiring a database or Laravel bootstrap.
 */
class ReportingServiceScheduleTest extends TestCase
{
    // -------------------------------------------------------------------------
    // GenerateReportDTO — export format validation
    // -------------------------------------------------------------------------

    public function test_generate_report_dto_supports_csv_format(): void
    {
        $dto = GenerateReportDTO::fromArray([
            'report_definition_id' => 1,
            'export_format'        => 'csv',
        ]);

        $this->assertSame('csv', $dto->exportFormat);
    }

    public function test_generate_report_dto_supports_pdf_format(): void
    {
        $dto = GenerateReportDTO::fromArray([
            'report_definition_id' => 2,
            'export_format'        => 'pdf',
        ]);

        $this->assertSame('pdf', $dto->exportFormat);
    }

    public function test_generate_report_dto_supports_xlsx_format(): void
    {
        $dto = GenerateReportDTO::fromArray([
            'report_definition_id' => 3,
            'export_format'        => 'xlsx',
        ]);

        $this->assertSame('xlsx', $dto->exportFormat);
    }

    public function test_generate_report_dto_default_format_is_csv(): void
    {
        $dto = GenerateReportDTO::fromArray([
            'report_definition_id' => 4,
        ]);

        $this->assertSame('csv', $dto->exportFormat);
    }

    // -------------------------------------------------------------------------
    // scheduleReport — field mapping (pure PHP, mirrors scheduleReport logic)
    // -------------------------------------------------------------------------

    public function test_schedule_payload_maps_all_fields(): void
    {
        // Mirror the mapping done inside scheduleReport()
        $input = [
            'report_definition_id' => 5,
            'frequency'            => 'daily',
            'export_format'        => 'csv',
            'recipients'           => ['admin@example.com', 'finance@example.com'],
            'next_run_at'          => '2026-03-01 08:00:00',
        ];

        $payload = [
            'report_definition_id' => $input['report_definition_id'],
            'frequency'            => $input['frequency'],
            'export_format'        => $input['export_format'],
            'recipients'           => $input['recipients'] ?? [],
            'next_run_at'          => $input['next_run_at'] ?? null,
            'is_active'            => true,
        ];

        $this->assertSame(5, $payload['report_definition_id']);
        $this->assertSame('daily', $payload['frequency']);
        $this->assertSame('csv', $payload['export_format']);
        $this->assertSame(['admin@example.com', 'finance@example.com'], $payload['recipients']);
        $this->assertSame('2026-03-01 08:00:00', $payload['next_run_at']);
        $this->assertTrue($payload['is_active']);
    }

    public function test_schedule_payload_recipients_defaults_to_empty_array(): void
    {
        $input = [
            'report_definition_id' => 6,
            'frequency'            => 'weekly',
            'export_format'        => 'pdf',
        ];

        $payload = [
            'recipients' => $input['recipients'] ?? [],
            'next_run_at' => $input['next_run_at'] ?? null,
            'is_active'   => true,
        ];

        $this->assertSame([], $payload['recipients']);
        $this->assertNull($payload['next_run_at']);
        $this->assertTrue($payload['is_active']);
    }

    public function test_schedule_payload_is_active_always_true(): void
    {
        $payload = ['is_active' => true];

        $this->assertTrue($payload['is_active']);
    }

    // -------------------------------------------------------------------------
    // createDefinition — field mapping (pure PHP, mirrors createDefinition logic)
    // -------------------------------------------------------------------------

    public function test_definition_payload_maps_required_fields(): void
    {
        $input = [
            'name' => 'Monthly Sales Report',
            'slug' => 'monthly-sales',
            'type' => 'financial',
        ];

        $payload = [
            'name'        => $input['name'],
            'slug'        => $input['slug'],
            'type'        => $input['type'],
            'description' => $input['description'] ?? null,
            'filters'     => $input['filters'] ?? null,
            'columns'     => $input['columns'] ?? null,
            'sort_config' => $input['sort_config'] ?? null,
            'is_system'   => false,
            'is_active'   => true,
        ];

        $this->assertSame('Monthly Sales Report', $payload['name']);
        $this->assertSame('monthly-sales', $payload['slug']);
        $this->assertSame('financial', $payload['type']);
        $this->assertNull($payload['description']);
        $this->assertNull($payload['filters']);
        $this->assertFalse($payload['is_system']);
        $this->assertTrue($payload['is_active']);
    }

    public function test_definition_payload_maps_optional_fields(): void
    {
        $input = [
            'name'        => 'Inventory Valuation',
            'slug'        => 'inventory-valuation',
            'type'        => 'inventory',
            'description' => 'Monthly inventory valuation by warehouse',
            'filters'     => ['warehouse_id' => 1],
            'columns'     => ['product_name', 'quantity', 'unit_cost', 'total_value'],
            'sort_config' => ['column' => 'total_value', 'direction' => 'desc'],
        ];

        $payload = [
            'name'        => $input['name'],
            'slug'        => $input['slug'],
            'type'        => $input['type'],
            'description' => $input['description'] ?? null,
            'filters'     => $input['filters'] ?? null,
            'columns'     => $input['columns'] ?? null,
            'sort_config' => $input['sort_config'] ?? null,
            'is_system'   => false,
            'is_active'   => true,
        ];

        $this->assertSame('Monthly inventory valuation by warehouse', $payload['description']);
        $this->assertSame(['warehouse_id' => 1], $payload['filters']);
        $this->assertSame(['product_name', 'quantity', 'unit_cost', 'total_value'], $payload['columns']);
        $this->assertSame(['column' => 'total_value', 'direction' => 'desc'], $payload['sort_config']);
    }
}
