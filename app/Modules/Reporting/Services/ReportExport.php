<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Http\Response;
use ZipArchive;
use Modules\Reporting\DTOs\ReportDefinition;

final class ReportExport
{
    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function csv(ReportDefinition $definition, array $rows): Response
    {
        $content = $this->delimited($definition, $rows, ',');

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$this->filename($definition, 'csv').'"',
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function xlsx(ReportDefinition $definition, array $rows): Response
    {
        if (! class_exists(ZipArchive::class)) {
            return response($this->delimited($definition, $rows, "\t"), 200, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$this->filename($definition, 'xls').'"',
            ]);
        }

        $path = tempnam(sys_get_temp_dir(), 'autoerp-report-');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheet($definition, $rows));
        $zip->close();

        $content = (string) file_get_contents($path);
        @unlink($path);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$this->filename($definition, 'xlsx').'"',
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function pdf(ReportDefinition $definition, array $rows): Response
    {
        $lines = [$definition->title, str_repeat('-', min(90, max(10, strlen($definition->title))))];
        $headers = array_map(fn ($column): string => $column->label, $definition->columns);
        $lines[] = implode(' | ', $headers);

        foreach (array_slice($rows, 0, 400) as $row) {
            $lines[] = implode(' | ', array_map(fn ($column): string => $this->plain($row[$column->key] ?? ''), $definition->columns));
        }

        return response($this->simplePdf($lines), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename($definition, 'pdf').'"',
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function print(ReportDefinition $definition, array $rows): Response
    {
        $header = implode('', array_map(fn ($column): string => '<th>'.e($column->label).'</th>', $definition->columns));
        $body = '';

        foreach ($rows as $row) {
            $body .= '<tr>'.implode('', array_map(fn ($column): string => '<td>'.e($this->plain($row[$column->key] ?? '')).'</td>', $definition->columns)).'</tr>';
        }

        $html = '<!doctype html><html><head><meta charset="utf-8"><title>'.e($definition->title).'</title><style>body{font-family:Arial,sans-serif;margin:24px;color:#111827}table{width:100%;border-collapse:collapse;font-size:12px}th,td{border:1px solid #d1d5db;padding:6px;text-align:left}th{background:#f3f4f6}h1{font-size:20px}</style></head><body><h1>'.e($definition->title).'</h1><table><thead><tr>'.$header.'</tr></thead><tbody>'.$body.'</tbody></table><script>window.print()</script></body></html>';

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function delimited(ReportDefinition $definition, array $rows, string $delimiter): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, array_map(fn ($column): string => $column->label, $definition->columns), $delimiter);

        foreach ($rows as $row) {
            fputcsv($stream, array_map(fn ($column): string => $this->plain($row[$column->key] ?? ''), $definition->columns), $delimiter);
        }

        rewind($stream);

        return (string) stream_get_contents($stream);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function worksheet(ReportDefinition $definition, array $rows): string
    {
        $xmlRows = [$this->xlsxRow(1, array_map(fn ($column): string => $column->label, $definition->columns))];
        $index = 2;

        foreach ($rows as $row) {
            $xmlRows[] = $this->xlsxRow($index++, array_map(fn ($column): string => $this->plain($row[$column->key] ?? ''), $definition->columns));
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.implode('', $xmlRows).'</sheetData></worksheet>';
    }

    /**
     * @param array<int, string> $values
     */
    private function xlsxRow(int $index, array $values): string
    {
        $cells = '';
        foreach (array_values($values) as $offset => $value) {
            $cells .= '<c r="'.$this->columnName($offset + 1).$index.'" t="inlineStr"><is><t>'.htmlspecialchars($value, ENT_XML1).'</t></is></c>';
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

    /**
     * @param array<int, string> $lines
     */
    private function simplePdf(array $lines): string
    {
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>',
        ];
        $pages = [];
        $lineChunks = array_chunk($lines, 42);

        foreach ($lineChunks as $chunk) {
            $content = "BT\n/F1 9 Tf\n50 790 Td\n";
            foreach ($chunk as $line) {
                $content .= '('.$this->pdfText(substr($line, 0, 130)).") Tj\n0 -16 Td\n";
            }
            $content .= "ET\n";
            $contentId = count($objects) + 1;
            $objects[] = "<< /Length ".strlen($content)." >>\nstream\n{$content}endstream";
            $pageId = count($objects) + 1;
            $pages[] = $pageId;
            $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents {$contentId} 0 R >>";
        }

        $objects[1] = '<< /Type /Pages /Kids ['.implode(' ', array_map(fn (int $id): string => "{$id} 0 R", $pages)).'] /Count '.count($pages).' >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id + 1] = strlen($pdf);
            $pdf .= ($id + 1)." 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    private function pdfText(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }

    private function plain(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value) ?: '';
        }

        return (string) $value;
    }

    private function filename(ReportDefinition $definition, string $extension): string
    {
        return (string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $definition->key).'.'.$extension;
    }
}
