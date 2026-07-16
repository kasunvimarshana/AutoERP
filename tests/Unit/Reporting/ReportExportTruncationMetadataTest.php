<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use DateTimeImmutable;
use Modules\Customer\Models\Customer;
use Modules\Reporting\Constants\ReportExportHeader;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportData;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\Reporting\Services\ReportExport;
use Tests\TestCase;

final class ReportExportTruncationMetadataTest extends TestCase
{
    public function test_bounded_export_exposes_row_limit_and_truncation_metadata(): void
    {
        $report = new ReportData(
            definition: new ReportDefinition(
                key: 'customer-test',
                title: 'Customer Test',
                group: 'Test',
                model: Customer::class,
                columns: [new ReportColumn('name', 'Name')],
            ),
            rows: [['name' => 'Customer One']],
            summary: [],
            branding: [],
            filters: [],
            generatedAt: new DateTimeImmutable('2026-07-16T00:00:00+00:00'),
            template: 'reporting::reports.default',
            mode: 'csv',
            rowLimit: 1,
            truncated: true,
        );

        $response = app(ReportExport::class)->response('csv', $report);

        self::assertSame('1', $response->headers->get(ReportExportHeader::ROW_LIMIT));
        self::assertSame('true', $response->headers->get(ReportExportHeader::TRUNCATED));
        self::assertSame(1, $report->viewData()['rowLimit']);
        self::assertTrue($report->viewData()['truncated']);
    }

    public function test_specialized_unbounded_export_does_not_claim_a_row_limit(): void
    {
        $report = new ReportData(
            definition: new ReportDefinition(
                key: 'customer-test',
                title: 'Customer Test',
                group: 'Test',
                model: Customer::class,
                columns: [new ReportColumn('name', 'Name')],
            ),
            rows: [['name' => 'Customer One']],
            summary: [],
            branding: [],
            filters: [],
            generatedAt: new DateTimeImmutable('2026-07-16T00:00:00+00:00'),
            template: 'reporting::reports.default',
            mode: 'csv',
        );

        $response = app(ReportExport::class)->response('csv', $report);

        self::assertFalse($response->headers->has(ReportExportHeader::ROW_LIMIT));
        self::assertFalse($response->headers->has(ReportExportHeader::TRUNCATED));
    }
}
