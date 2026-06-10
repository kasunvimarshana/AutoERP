<?php

declare(strict_types=1);

namespace Modules\Reporting\Exporters;

use Illuminate\Http\Response;
use Modules\Reporting\DTOs\ReportData;
use Modules\Reporting\Services\ReportFilename;
use Modules\Reporting\Services\ReportValueFormatter;
use RuntimeException;
use ZipArchive;

final class ExcelExporter
{
    public function __construct(
        private readonly ReportFilename $filenames,
        private readonly ReportValueFormatter $formatter,
    ) {}

    public function export(ReportData $report): Response
    {
        if (! class_exists(ZipArchive::class)) {
            return response($this->legacyWorkbook($report), 200, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$this->filenames->make($report->definition, 'xls').'"',
            ]);
        }

        $path = tempnam(sys_get_temp_dir(), 'autoerp-report-');
        if ($path === false) {
            throw new RuntimeException('Unable to create a temporary Excel report.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create the Excel report archive.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheet($report));
        $zip->close();

        $content = file_get_contents($path);
        @unlink($path);
        if ($content === false) {
            throw new RuntimeException('Unable to read the generated Excel report.');
        }

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$this->filenames->make($report->definition, 'xlsx').'"',
        ]);
    }

    private function legacyWorkbook(ReportData $report): string
    {
        $rows = [$this->legacyRow(array_map(
            fn ($column): array => [$column->label, 'text'],
            $report->definition->columns,
        ))];

        foreach ($report->rows as $reportRow) {
            $rows[] = $this->legacyRow(array_map(
                fn ($column): array => [$reportRow[$column->key] ?? null, $column->format],
                $report->definition->columns,
            ));
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<?mso-application progid="Excel.Sheet"?>'
            .'<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" '
            .'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
            .'<Worksheet ss:Name="Report"><Table>'.implode('', $rows).'</Table></Worksheet></Workbook>';
    }

    /**
     * @param  array<int, array{0: mixed, 1: string}>  $values
     */
    private function legacyRow(array $values): string
    {
        $cells = '';

        foreach ($values as [$value, $format]) {
            $numeric = in_array($format, ['money', 'currency', 'decimal', 'integer'], true)
                && preg_match('/^-?\d+(?:\.\d+)?$/', (string) $value);
            $type = $numeric ? 'Number' : 'String';
            $content = $numeric ? (string) $value : $this->formatter->format($value);
            $cells .= '<Cell><Data ss:Type="'.$type.'">'
                .htmlspecialchars($content, ENT_XML1).'</Data></Cell>';
        }

        return '<Row>'.$cells.'</Row>';
    }

    private function worksheet(ReportData $report): string
    {
        $rows = [$this->row(1, array_map(fn ($column): array => [$column->label, 'text'], $report->definition->columns))];
        $index = 2;

        foreach ($report->rows as $reportRow) {
            $values = array_map(
                fn ($column): array => [$reportRow[$column->key] ?? null, $column->format],
                $report->definition->columns,
            );
            $rows[] = $this->row($index++, $values);
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'.implode('', $rows).'</sheetData></worksheet>';
    }

    /**
     * @param  array<int, array{0: mixed, 1: string}>  $values
     */
    private function row(int $index, array $values): string
    {
        $cells = '';

        foreach (array_values($values) as $offset => [$value, $format]) {
            $reference = $this->columnName($offset + 1).$index;
            if (in_array($format, ['money', 'currency', 'decimal', 'integer'], true)
                && preg_match('/^-?\d+(?:\.\d+)?$/', (string) $value)) {
                $cells .= '<c r="'.$reference.'"><v>'.htmlspecialchars((string) $value, ENT_XML1).'</v></c>';

                continue;
            }

            $text = $this->formatter->format($value);
            $cells .= '<c r="'.$reference.'" t="inlineStr"><is><t xml:space="preserve">'
                .htmlspecialchars($text, ENT_XML1).'</t></is></c>';
        }

        return '<row r="'.$index.'">'.$cells.'</row>';
    }

    private function columnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Report" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>';
    }
}
