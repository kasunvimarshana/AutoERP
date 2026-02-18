<?php

namespace App\Modules\Reporting\Services;

use App\Modules\Reporting\DTOs\ExportReportDTO;
use App\Modules\Reporting\Enums\ExportFormat;
use App\Services\BaseService;
use Illuminate\Support\Facades\Response;

/**
 * Export Service
 *
 * Handles report export to various formats.
 */
class ExportService extends BaseService
{
    /**
     * Export report data
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(ExportReportDTO $dto, array $data)
    {
        return match ($dto->format) {
            ExportFormat::CSV => $this->exportToCsv($data, $dto->filename),
            ExportFormat::PDF => $this->exportToPdf($data, $dto->filename),
            ExportFormat::EXCEL => $this->exportToExcel($data, $dto->filename),
            ExportFormat::JSON => $this->exportToJson($data, $dto->filename),
        };
    }

    /**
     * Export to CSV
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportToCsv(array $data, ?string $filename = null)
    {
        $filename = $filename ?? 'report_'.date('Y-m-d_His').'.csv';

        return Response::streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');

            // Write headers if data is not empty
            if (! empty($data)) {
                $headers = array_keys($data[0]);
                fputcsv($handle, $headers);

                // Write data rows
                foreach ($data as $row) {
                    fputcsv($handle, $row);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Export to PDF
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportToPdf(array $data, ?string $filename = null)
    {
        $filename = $filename ?? 'report_'.date('Y-m-d_His').'.pdf';

        // Note: This is a placeholder. In production, use a library like DomPDF or mPDF
        $html = $this->generateHtmlTable($data);

        return Response::streamDownload(function () use ($html) {
            echo $html;
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Export to Excel
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportToExcel(array $data, ?string $filename = null)
    {
        $filename = $filename ?? 'report_'.date('Y-m-d_His').'.xlsx';

        // Note: This is a placeholder. In production, use PhpSpreadsheet or similar
        return $this->exportToCsv($data, str_replace('.xlsx', '.csv', $filename));
    }

    /**
     * Export to JSON
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportToJson(array $data, ?string $filename = null)
    {
        $filename = $filename ?? 'report_'.date('Y-m-d_His').'.json';

        return Response::streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT);
        }, $filename, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Generate HTML table for PDF export
     */
    protected function generateHtmlTable(array $data): string
    {
        if (empty($data)) {
            return '<html><body><p>No data to export</p></body></html>';
        }

        $headers = array_keys($data[0]);

        $html = '<html><head><style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #4CAF50; color: white; }
        </style></head><body>';

        $html .= '<table>';
        $html .= '<thead><tr>';

        foreach ($headers as $header) {
            $html .= '<th>'.htmlspecialchars($header).'</th>';
        }

        $html .= '</tr></thead><tbody>';

        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>'.htmlspecialchars($cell ?? '').'</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '</body></html>';

        return $html;
    }
}
