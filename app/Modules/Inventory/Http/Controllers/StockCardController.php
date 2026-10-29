<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Inventory\Enums\StockCardExportFormat;
use Modules\Inventory\Http\Requests\StockCardRequest;
use Modules\Inventory\Services\StockCardService;
use Spatie\LaravelPdf\Facades\Pdf;

class StockCardController
{
    private const PDF_PAPER_SIZE = 'a4';

    private const PDF_ORIENTATION = 'landscape';

    public function show(StockCardRequest $request, StockCardService $service): JsonResponse
    {
        return response()->json(['data' => $service->build($request->validated(), $request->tenantId(), $request->organizationUnitId())]);
    }

    public function export(StockCardRequest $request, StockCardService $service): Response
    {
        $data = $service->build($request->validated(), $request->tenantId(), $request->organizationUnitId());
        $format = StockCardExportFormat::from((string) $request->validated('format'));
        $filename = 'stock-card-'.$data['date_from'].'-'.$data['date_to'].'.'.$format->value;

        if ($format === StockCardExportFormat::Pdf) {
            return Pdf::view('inventory::stock-card', ['card' => $data])
                ->format(self::PDF_PAPER_SIZE)
                ->orientation(self::PDF_ORIENTATION)
                ->name($filename)
                ->download()
                ->toResponse($request);
        }

        $rows = $this->rows($data);
        if ($format === StockCardExportFormat::Excel) {
            $xmlRows = [];
            foreach ($rows as $row) {
                $cells = array_map(static fn (string $value): string => '<Cell><Data ss:Type="String">'.htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</Data></Cell>', $row);
                $xmlRows[] = '<Row>'.implode('', $cells).'</Row>';
            }
            $content = '<?xml version="1.0" encoding="UTF-8"?>'
                .'<?mso-application progid="Excel.Sheet"?>'
                .'<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
                .'<Worksheet ss:Name="Stock Card"><Table>'.implode('', $xmlRows).'</Table></Worksheet></Workbook>';

            return response($content, 200, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");
        foreach ($rows as $row) {
            fputcsv($stream, array_map(static fn (string $value): string => preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value, $row));
        }
        rewind($stream);
        $content = (string) stream_get_contents($stream);
        fclose($stream);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function rows(array $card): array
    {
        $rows = [
            ['Item', $card['item']['code'].' - '.$card['item']['name']],
            ['Warehouse', $card['warehouse']['name']],
            ['Location', $card['location']['name'] ?? 'All locations'],
            ['Period', $card['date_from'].' to '.$card['date_to']],
            ['UOM', (string) $card['item']['uom']],
            ['Posted at', 'Document date', 'Document', 'Description', 'IN', 'OUT', 'Balance'],
            ['', '', '', 'Opening balance', '', '', $card['opening']],
        ];

        foreach ($card['rows'] as $entry) {
            $rows[] = [
                $entry['posted_at'],
                $entry['document_date'] ?? '',
                $entry['document']['label'] ?? $entry['movement_number'],
                $entry['description'] ?? '',
                $entry['quantity_in'],
                $entry['quantity_out'],
                $entry['balance'],
            ];
        }

        $rows[] = ['', '', '', 'Period totals', $card['total_in'], $card['total_out'], $card['closing']];

        return $rows;
    }
}
