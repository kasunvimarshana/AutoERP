<?php

declare(strict_types=1);

namespace Modules\Reporting\Exporters;

use Illuminate\Http\Response;
use Modules\Reporting\DTOs\ReportData;
use Modules\Reporting\Services\ReportFilename;
use Spatie\LaravelPdf\Facades\Pdf;
use Throwable;

final class PdfExporter
{
    public function __construct(private readonly ReportFilename $filenames) {}

    public function export(ReportData $report): Response
    {
        try {
            $pdfReport = $report->withMode('pdf');

            return Pdf::view($pdfReport->template, $pdfReport->viewData())
                ->format((string) config('reporting.pdf.paper_size', 'A4'))
                ->orientation($pdfReport->orientation())
                ->margins(
                    (float) config('reporting.pdf.margins.top', 12),
                    (float) config('reporting.pdf.margins.right', 10),
                    (float) config('reporting.pdf.margins.bottom', 12),
                    (float) config('reporting.pdf.margins.left', 10),
                )
                ->name($this->filenames->make($report->definition, 'pdf'))
                ->download()
                ->toResponse(request());
        } catch (Throwable $exception) {
            report($exception);

            $payload = [
                'success' => false,
                'message' => 'The PDF export could not be generated.',
                'error' => [
                    'code' => 'PDF_EXPORT_FAILED',
                    'type' => 'infrastructure',
                    'message' => 'The PDF export could not be generated.',
                    'details' => (object) [],
                ],
            ];

            return response(
                json_encode($payload, JSON_THROW_ON_ERROR),
                500,
                ['Content-Type' => 'application/json'],
            );
        }
    }
}
