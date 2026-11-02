<?php

declare(strict_types=1);

namespace Modules\Reporting\Tests;

use DateTimeImmutable;
use DOMDocument;
use DOMXPath;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportData;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\Reporting\Exporters\ExcelExporter;
use Modules\Reporting\Exporters\HtmlExporter;
use Modules\Reporting\Exporters\PdfExporter;
use Modules\Reporting\Services\ReportDefinitionRegistry;
use Modules\Reporting\Services\ReportSummaryBuilder;
use Modules\Reporting\Services\ReportTemplateResolver;
use RuntimeException;
use Spatie\LaravelPdf\Drivers\DomPdfDriver;
use Spatie\LaravelPdf\Drivers\PdfDriver;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Spatie\LaravelPdf\PdfOptions;
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
        $document = new DOMDocument;
        @$document->loadHTML($html);
        $rows = (new DOMXPath($document))->query(
            '//table[contains(concat(" ", normalize-space(@class), " "), " report-table ")]/tbody/tr',
        );

        self::assertStringContainsString('table-header-group', $html);
        self::assertStringContainsString('page-break-before: always', $html);
        self::assertStringContainsString('summary-table', $html);
        self::assertStringContainsString('report-footer', $html);
        self::assertStringNotContainsString('display: flex', $html);
        self::assertStringNotContainsString('display: grid', $html);
        self::assertStringContainsString('Tenant Brand', $html);
        self::assertStringContainsString('Reference 180', $html);
        self::assertNotFalse($rows);
        self::assertSame(180, $rows->length);
    }

    public function test_dompdf_is_the_default_pdf_driver(): void
    {
        self::assertSame('dompdf', config('laravel-pdf.driver'));
        self::assertSame('A4', config('reporting.pdf.paper_size'));
        self::assertSame('portrait', config('reporting.pdf.orientation'));
        self::assertInstanceOf(DomPdfDriver::class, $this->app->make(PdfDriver::class));
    }

    public function test_pdf_exporter_uses_the_shared_report_view_and_download_settings(): void
    {
        Pdf::fake();

        $response = $this->app->make(PdfExporter::class)->export($this->report(2));

        self::assertSame(200, $response->getStatusCode());
        Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf): bool {
            return $pdf->viewName === 'reports.inventory.report'
                && $pdf->viewData['mode'] === 'pdf'
                && $pdf->format === 'A4'
                && $pdf->orientation === 'portrait'
                && $pdf->margins === [
                    'top' => 12.0,
                    'right' => 10.0,
                    'bottom' => 12.0,
                    'left' => 10.0,
                    'unit' => 'mm',
                ]
                && $pdf->downloadName === 'test.large-report.pdf'
                && $pdf->isDownload();
        });
    }

    public function test_dompdf_generates_a_real_pdf_without_browser_dependencies(): void
    {
        $response = $this->app->make(PdfExporter::class)->export($this->report(2));
        $content = $response->getContent();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/pdf', $response->headers->get('Content-Type'));
        self::assertSame('attachment; filename="test.large-report.pdf"', $response->headers->get('Content-Disposition'));
        self::assertIsString($content);
        self::assertStringStartsWith('%PDF-', $content);
    }

    public function test_pdf_export_failures_return_a_clean_api_error(): void
    {
        $this->app->instance(PdfDriver::class, new class implements PdfDriver
        {
            public function generatePdf(
                string $html,
                ?string $headerHtml,
                ?string $footerHtml,
                PdfOptions $options,
            ): string {
                throw new RuntimeException('PDF renderer unavailable.');
            }

            public function savePdf(
                string $html,
                ?string $headerHtml,
                ?string $footerHtml,
                PdfOptions $options,
                string $path,
            ): void {
                throw new RuntimeException('PDF renderer unavailable.');
            }
        });

        $response = $this->app->make(PdfExporter::class)->export($this->report(1));
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(500, $response->getStatusCode());
        self::assertSame('PDF_EXPORT_FAILED', $payload['error']['code']);
        self::assertSame('The PDF export could not be generated.', $payload['message']);
    }

    public function test_requested_report_families_share_the_existing_pdf_export_path(): void
    {
        Pdf::fake();

        $registry = $this->app->make(ReportDefinitionRegistry::class);
        $templates = $this->app->make(ReportTemplateResolver::class);
        $exporter = $this->app->make(PdfExporter::class);
        $reports = [
            'invoice.register' => ['reports.finance.report', 'portrait'],
            'payment.register' => ['reports.finance.report', 'landscape'],
            'finance.ledger' => ['reports.finance.report', 'landscape'],
            'inventory.stock-balance' => ['reports.inventory.report', 'landscape'],
            'purchase.orders' => ['reports.purchase.report', 'portrait'],
            'vehicle-service.jobs' => ['reports.vehicle-service.report', 'portrait'],
            'vehicle-service.employee-commissions' => ['reports.vehicle-service.report', 'portrait'],
            'vehicle-service.profitability' => ['reports.vehicle-service.report', 'landscape'],
        ];

        foreach ($reports as $key => [$expectedTemplate, $expectedOrientation]) {
            $definition = $registry->get($key);
            $template = $templates->resolve($definition);

            self::assertSame($expectedTemplate, $template);
            self::assertSame(200, $exporter->export($this->reportFor($definition, $template))->getStatusCode());

            Pdf::assertRespondedWithPdf(
                fn (PdfBuilder $pdf): bool => $pdf->viewName === $expectedTemplate
                    && $pdf->downloadName === $key.'.pdf'
                    && $pdf->orientation === $expectedOrientation,
            );
        }
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

    private function reportFor(ReportDefinition $definition, string $template): ReportData
    {
        return new ReportData(
            definition: $definition,
            rows: [],
            summary: [],
            branding: [
                'company_name' => 'AutoERP',
                'tenant_name' => 'Tenant Brand',
                'organization_unit_name' => 'Main Branch',
                'currency_code' => 'USD',
                'logo_data_uri' => null,
            ],
            filters: [],
            generatedAt: new DateTimeImmutable('2026-06-11 10:00:00'),
            template: $template,
        );
    }
}
