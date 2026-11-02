<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Http\Response;
use Modules\Reporting\DTOs\ReportData;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\Reporting\Exporters\CsvExporter;
use Modules\Reporting\Exporters\ExcelExporter;
use Modules\Reporting\Exporters\HtmlExporter;
use Modules\Reporting\Exporters\PdfExporter;

final class ReportExport
{
    public function __construct(
        private readonly ReportDataFactory $documents,
        private readonly HtmlExporter $html,
        private readonly PdfExporter $pdf,
        private readonly CsvExporter $csv,
        private readonly ExcelExporter $excel,
    ) {}

    public function response(string $format, ReportData $report): Response
    {
        return match ($format) {
            'html' => $this->html->preview($report),
            'print' => $this->html->print($report),
            'pdf' => $this->pdf->export($report),
            'csv' => $this->csv->export($report),
            'xlsx' => $this->excel->export($report),
            default => response('Unsupported export format.', 422),
        };
    }

    /**
     * Compatibility entry point for specialized report services.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $input
     */
    public function export(
        string $format,
        ReportDefinition $definition,
        array $rows,
        int $tenantId,
        ?int $organizationUnitId,
        array $input = [],
    ): Response {
        return $this->response(
            $format,
            $this->documents->make($definition, $rows, $tenantId, $organizationUnitId, $input, $format),
        );
    }
}
