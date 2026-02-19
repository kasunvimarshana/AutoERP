<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Support\Facades\Storage;
use Modules\Reporting\Enums\ExportFormat;

/**
 * ReportExportService
 *
 * Handles exporting reports to various formats
 */
class ReportExportService
{
    /**
     * Export report data to specified format
     */
    public function export(array $data, ExportFormat $format, ?string $filename = null): string
    {
        $filename = $filename ?? 'report_'.now()->format('YmdHis');

        return match ($format) {
            ExportFormat::CSV => $this->exportCsv($data, $filename),
            ExportFormat::JSON => $this->exportJson($data, $filename),
            ExportFormat::PDF => $this->exportPdf($data, $filename),
        };
    }

    /**
     * Export to CSV format
     */
    public function exportCsv(array $data, string $filename): string
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('No data to export');
        }

        $filename = $filename.'.csv';
        $path = 'exports/'.$filename;

        // Create CSV content
        $output = fopen('php://temp', 'r+');

        // Write headers
        $firstRow = is_array($data[0]) ? $data[0] : (array) $data[0];
        fputcsv($output, array_keys($firstRow));

        // Write data rows
        foreach ($data as $row) {
            $rowData = is_array($row) ? $row : (array) $row;
            fputcsv($output, $rowData);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        // Store file
        Storage::put($path, $content);

        return $path;
    }

    /**
     * Export to JSON format
     */
    public function exportJson(array $data, string $filename): string
    {
        $filename = $filename.'.json';
        $path = 'exports/'.$filename;

        $content = json_encode([
            'data' => $data,
            'count' => count($data),
            'exported_at' => now()->toISOString(),
        ], JSON_PRETTY_PRINT);

        Storage::put($path, $content);

        return $path;
    }

    /**
     * Export to PDF format (HTML-based)
     *
     * Generates a printable HTML document that can be converted to PDF
     * using browser print-to-PDF or a headless browser (e.g., Chrome, wkhtmltopdf).
     * This avoids adding runtime PHP dependencies while providing PDF capability.
     * 
     * Note: Returns an HTML file path. The HTML is optimized for PDF conversion
     * and includes print-specific styling. API consumers should either:
     * 1. Convert to PDF server-side using headless browser, or
     * 2. Serve the HTML with appropriate Content-Type headers for browser rendering
     * 
     * @return string Path to the generated HTML file (with .html extension)
     */
    public function exportPdf(array $data, string $filename): string
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('No data to export');
        }

        $filename = $filename.'.html';
        $path = 'exports/'.$filename;

        // Generate HTML content optimized for PDF printing
        $html = $this->generatePdfHtml($data, $filename);

        // Store HTML file
        Storage::put($path, $html);

        return $path;
    }

    /**
     * Generate HTML content optimized for PDF printing
     */
    private function generatePdfHtml(array $data, string $filename): string
    {
        $firstRow = is_array($data[0]) ? $data[0] : (array) $data[0];
        $headers = array_keys($firstRow);

        $headerRow = implode('', array_map(fn($h) => "<th>".htmlspecialchars((string)$h)."</th>", $headers));
        
        $dataRows = '';
        foreach ($data as $row) {
            $rowData = is_array($row) ? $row : (array) $row;
            $cells = implode('', array_map(fn($v) => "<td>".htmlspecialchars((string)$v)."</td>", $rowData));
            $dataRows .= "<tr>{$cells}</tr>";
        }

        $exportedAt = now()->format('Y-m-d H:i:s');
        $recordCount = count($data);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$filename}</title>
    <style>
        @media print {
            @page { margin: 1cm; }
            body { margin: 0; }
            .no-print { display: none; }
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .meta {
            font-size: 10px;
            color: #666;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            color: #666;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        .no-print {
            background-color: #ffffcc;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ffcc00;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <strong>Note:</strong> To convert to PDF, use your browser's Print function (Ctrl+P / Cmd+P) and select "Save as PDF".
    </div>
    <div class="header">
        <h1>Report Export</h1>
        <div>{$filename}</div>
    </div>
    <div class="meta">
        <strong>Exported:</strong> {$exportedAt} | <strong>Records:</strong> {$recordCount}
    </div>
    <table>
        <thead>
            <tr>{$headerRow}</tr>
        </thead>
        <tbody>
            {$dataRows}
        </tbody>
    </table>
    <div class="footer">
        Generated by Enterprise ERP/CRM SaaS Platform
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get download URL for exported file
     */
    public function getDownloadUrl(string $path): string
    {
        return Storage::url($path);
    }

    /**
     * Stream CSV download
     */
    public function streamCsv(array $data, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ];

        return response()->stream(function () use ($data) {
            $output = fopen('php://output', 'w');

            if (! empty($data)) {
                // Write headers
                $firstRow = is_array($data[0]) ? $data[0] : (array) $data[0];
                fputcsv($output, array_keys($firstRow));

                // Write data
                foreach ($data as $row) {
                    $rowData = is_array($row) ? $row : (array) $row;
                    fputcsv($output, $rowData);
                }
            }

            fclose($output);
        }, 200, $headers);
    }

    /**
     * Stream JSON download
     */
    public function streamJson(array $data, string $filename): \Illuminate\Http\JsonResponse
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}.json\"",
        ];

        return response()->json([
            'data' => $data,
            'count' => count($data),
            'exported_at' => now()->toISOString(),
        ])->withHeaders($headers);
    }

    /**
     * Delete exported file
     */
    public function deleteExport(string $path): bool
    {
        return Storage::delete($path);
    }

    /**
     * Clean up old exports
     */
    public function cleanupOldExports(int $daysOld = 7): int
    {
        $files = Storage::files('exports');
        $deletedCount = 0;
        $cutoffTime = now()->subDays($daysOld)->timestamp;

        foreach ($files as $file) {
            if (Storage::lastModified($file) < $cutoffTime) {
                Storage::delete($file);
                $deletedCount++;
            }
        }

        return $deletedCount;
    }
}
