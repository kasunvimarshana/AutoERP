<?php

declare(strict_types=1);

namespace Modules\Reporting\Tests;

use DateTimeImmutable;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportData;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\Reporting\Exporters\ExcelExporter;
use Modules\Reporting\Exporters\HtmlExporter;
use Modules\Reporting\Services\ReportDefinitionRegistry;
use Modules\Reporting\Services\ReportSummaryBuilder;
use Tests\TestCase;
use ZipArchive;

final class ReportingFrameworkTest extends TestCase
{
    public function test_registry_contains_the_initial_enterprise_reports(): void
    {
        $reports = array_keys($this->app->make(ReportDefinitionRegistry::class)->all());

        foreach ([
            'inventory.stock-balance',
            'inventory.stock-movement',
            'purchase.orders',
            'purchase.grns',
            'purchase.returns',
            'masters.supplier',
            'masters.customer',
            'masters.vehicle',
            'masters.employee',
            'vehicle-service.jobs',
            'vehicle-service.employee-commissions',
            'vehicle-service.profitability',
            'invoice.register',
            'invoice.balance',
            'payment.register',
            'payment.allocation',
            'finance.ledger',
            'finance.trial-balance',
        ] as $key) {
            self::assertContains($key, $reports);
        }
    }

    public function test_decimal_summaries_are_exact(): void
    {
        $definition = $this->definition();
        $summary = $this->app->make(ReportSummaryBuilder::class)->build($definition, [
            ['reference' => 'A', 'amount' => '0.100000'],
            ['reference' => 'B', 'amount' => '0.200000'],
        ]);

        self::assertSame('0.300000', $summary[1]['value']);
    }

    public function test_large_html_report_uses_shared_multi_page_table_styles(): void
    {
        $report = $this->report(180);
        $html = $this->app->make(HtmlExporter::class)->render($report);

        self::assertStringContainsString('table-header-group', $html);
        self::assertStringContainsString('page-break-before: always', $html);
        self::assertStringContainsString('Tenant Brand', $html);
        self::assertStringContainsString('Reference 180', $html);
        self::assertSame(181, substr_count($html, '<tr>'));
    }

    public function test_excel_export_contains_every_large_dataset_row(): void
    {
        $response = $this->app->make(ExcelExporter::class)->export($this->report(180));
        $content = $response->getContent();

        self::assertIsString($content);

        if (! class_exists(ZipArchive::class)) {
            self::assertStringContainsString('application/vnd.ms-excel', (string) $response->headers->get('Content-Type'));
            self::assertSame(181, substr_count($content, '<Row>'));
            self::assertStringContainsString('<Data ss:Type="Number">180.100000</Data>', $content);

            return;
        }

        self::assertStringStartsWith('PK', $content);

        $path = tempnam(sys_get_temp_dir(), 'report-test-');
        file_put_contents($path, $content);
        $zip = new ZipArchive;
        self::assertTrue($zip->open($path) === true);
        $worksheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($path);

        self::assertIsString($worksheet);
        self::assertSame(181, substr_count($worksheet, '<row '));
        self::assertStringContainsString('<v>180.100000</v>', $worksheet);
    }

    private function definition(): ReportDefinition
    {
        return new ReportDefinition(
            key: 'test.large-report',
            title: 'Large Report',
            group: 'Inventory',
            model: InventoryStockBalance::class,
            columns: [
                new ReportColumn('reference', 'Reference'),
                new ReportColumn('amount', 'Amount', format: 'money', summarize: true),
            ],
        );
    }

    private function report(int $rowCount): ReportData
    {
        $definition = $this->definition();
        $rows = [];

        for ($index = 1; $index <= $rowCount; $index++) {
            $rows[] = [
                'reference' => 'Reference '.$index,
                'amount' => $index.'.100000',
            ];
        }

        return new ReportData(
            definition: $definition,
            rows: $rows,
            summary: $this->app->make(ReportSummaryBuilder::class)->build($definition, $rows),
            branding: [
                'company_name' => 'AutoERP',
                'tenant_name' => 'Tenant Brand',
                'organization_unit_name' => 'Main Branch',
                'currency_code' => 'USD',
                'logo_data_uri' => null,
            ],
            filters: [['label' => 'Status', 'value' => 'Active']],
            generatedAt: new DateTimeImmutable('2026-06-11 10:00:00'),
            template: 'reports.inventory.report',
        );
    }
}
