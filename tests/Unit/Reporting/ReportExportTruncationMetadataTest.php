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
        $report = $this->report(rowLimit: 1, truncated: true);

        $response = app(ReportExport::class)->response('csv', $report);

        self::assertSame('1', $response->headers->get(ReportExportHeader::ROW_LIMIT));
        self::assertSame('true', $response->headers->get(ReportExportHeader::TRUNCATED));
        self::assertSame(1, $report->viewData()['rowLimit']);
        self::assertTrue($report->viewData()['truncated']);
    }

    public function test_specialized_unbounded_export_does_not_claim_a_row_limit(): void
    {
        $report = $this->report();

        $response = app(ReportExport::class)->response('csv', $report);

        self::assertFalse($response->headers->has(ReportExportHeader::ROW_LIMIT));
        self::assertFalse($response->headers->has(ReportExportHeader::TRUNCATED));
    }

    public function test_truncated_rendered_report_warns_the_user_that_the_output_is_incomplete(): void
    {
        $report = $this->report(rowLimit: 1, truncated: true, mode: 'html');

        $html = view($report->template, $report->viewData())->render();

        self::assertStringContainsString('role="alert"', $html);
        self::assertStringContainsString(
            'This report is limited to 1 rows. Refine the filters before using it as a complete operational or financial record.',
            $html,
        );
    }

    private function report(
        ?int $rowLimit = null,
        bool $truncated = false,
        string $mode = 'csv',
    ): ReportData {
        return new ReportData(
            definition: new ReportDefinition(
                key: 'customer-test',
                title: 'Customer Test',
                group: 'Test',
                model: Customer::class,
                columns: [new ReportColumn('name', 'Name')],
            ),
            rows: [['name' => 'Customer One']],
            summary: [],
            branding: [
                'tenant_name' => 'Test Tenant',
                'company_name' => 'Test Company',
            ],
            filters: [],
            generatedAt: new DateTimeImmutable('2026-07-16T00:00:00+00:00'),
            template: 'reports.shared.report',
            mode: $mode,
            rowLimit: $rowLimit,
            truncated: $truncated,
        );
    }
}
