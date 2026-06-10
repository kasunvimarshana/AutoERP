<?php

declare(strict_types=1);

namespace Modules\Reporting\Exporters;

use Illuminate\Http\Response;
use Modules\Reporting\DTOs\ReportData;
use Modules\Reporting\Services\ReportFilename;
use Modules\Reporting\Services\ReportValueFormatter;

final class CsvExporter
{
    public function __construct(
        private readonly ReportFilename $filenames,
        private readonly ReportValueFormatter $formatter,
    ) {}

    public function export(ReportData $report): Response
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, array_map(fn ($column): string => $column->label, $report->definition->columns));

        foreach ($report->rows as $row) {
            fputcsv($stream, array_map(
                fn ($column): string => $this->formatter->format($row[$column->key] ?? null),
                $report->definition->columns,
            ));
        }

        rewind($stream);
        $content = (string) stream_get_contents($stream);
        fclose($stream);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$this->filenames->make($report->definition, 'csv').'"',
        ]);
    }
}
