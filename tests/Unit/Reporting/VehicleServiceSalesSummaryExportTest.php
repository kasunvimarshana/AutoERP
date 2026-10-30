<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use DateTimeImmutable;
use Illuminate\Validation\ValidationException;
use Modules\Reporting\DTOs\ReportData;
use Modules\Reporting\Services\ReportExport;
use Modules\Reporting\Services\VehicleServiceSalesSummaryExport;
use Tests\TestCase;

class VehicleServiceSalesSummaryExportTest extends TestCase
{
    public function test_exports_summary_rankings_jobs_and_components_without_exposing_ids_or_recalculating_amounts(): void
    {
        $component = ['item' => ['id' => 88, 'code' => 'OIL', 'name' => 'Engine oil'], 'kind' => 'stock', 'quantity' => '2.5', 'cost' => '30.125'];
        $combo = [
            'item' => ['id' => 77, 'code' => 'SVC', 'name' => 'Oil service'],
            'quantity' => '1', 'job_count' => 1, 'sales_amount' => '100.555',
            'cost' => '40.125', 'component_cost' => '40.125', 'stock_cost' => '30.125',
            'profit' => '60.43', 'components' => [$component],
        ];
        $totals = ['job_count' => 1, 'revenue' => '100.555', 'stock_cost' => '30.125', 'direct_cost' => '40.125', 'commission' => '5', 'profit' => '55.43', 'collected' => '70', 'margin' => '55.12'];
        $result = [
            'summary' => [...$totals, 'stock' => ['items' => []], 'combo' => ['items' => [$combo]]],
            'jobs' => [[
                ...$totals, 'id' => 99, 'job_number' => 'JOB-001', 'date' => '2026-10-31',
                'customer' => ['name' => 'Nimal'], 'vehicle' => ['name' => 'ABC-1234'],
                'used_stock_cost' => '30.125', 'stock_items' => [], 'combos' => [$combo], 'other_items' => [],
            ]],
        ];

        $document = new VehicleServiceSalesSummaryExport;
        $rows = $document->rows($result);

        self::assertCount(6, $rows);
        self::assertSame('100.555', $rows[0]['revenue']);
        self::assertSame('55.43', $rows[0]['profit']);
        self::assertSame('SVC — Oil service', $rows[1]['reference']);
        self::assertArrayNotHasKey('revenue', $rows[2]);
        self::assertSame('30.125', $rows[2]['stock_cost']);
        self::assertSame('JOB-001 | 2026-10-31 | Nimal | ABC-1234', $rows[3]['reference']);
        self::assertSame('70', $rows[3]['collected']);
        self::assertSame('40.125', $rows[4]['direct_cost']);
        self::assertStringContainsString('OIL — Engine oil', $rows[5]['reference']);
        self::assertSame('landscape', $document->definition()->orientation);
        foreach ($document->definition()->columns as $column) {
            self::assertFalse($column->summarize, 'Overlapping sections must not be summed together.');
        }

        $report = new ReportData(
            definition: $document->definition(),
            rows: $rows,
            summary: [],
            branding: ['tenant_name' => 'Test tenant', 'company_name' => 'Test company'],
            filters: [],
            generatedAt: new DateTimeImmutable('2026-10-31'),
            template: 'reports.vehicle-service.report',
        );
        foreach (['html', 'print', 'csv', 'xlsx', 'pdf'] as $format) {
            $response = app(ReportExport::class)->response($format, $report);
            self::assertSame(200, $response->getStatusCode(), "{$format} export failed");
            self::assertNotEmpty($response->getContent());
            if (in_array($format, ['html', 'print', 'csv'], true)) {
                self::assertStringContainsString('Oil service', $response->getContent());
                self::assertStringContainsString('JOB-001', $response->getContent());
            }
        }

        config(['reporting.export_row_limit' => 5]);
        $this->expectException(ValidationException::class);
        $document->rows($result);
    }
}
