<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Inventory\Http\Requests\OpeningStockImportRequest;
use Modules\Inventory\Http\Resources\InventoryAdjustmentResource;
use Modules\Inventory\Services\OpeningStockCsvImportService;
use Modules\Inventory\Services\OpeningStockCsvReader;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class OpeningStockImportController
{
    private const TEMPLATE_FILE_NAME = 'opening-stock-template.csv';

    public function template(): StreamedResponse
    {
        return response()->streamDownload(static function (): void {
            $stream = fopen('php://output', 'wb');
            if ($stream === false) {
                return;
            }

            fputcsv($stream, OpeningStockCsvReader::HEADERS, ',', '"', '');
            fclose($stream);
        }, self::TEMPLATE_FILE_NAME, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function preview(
        OpeningStockImportRequest $request,
        OpeningStockCsvImportService $service,
    ): JsonResponse {
        return response()->json(['data' => $service->preview(
            $request->csvFile(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->warehouseId(),
            $request->warehouseLocationId(),
        )->toArray()]);
    }

    public function store(
        OpeningStockImportRequest $request,
        OpeningStockCsvImportService $service,
    ): InventoryAdjustmentResource {
        return new InventoryAdjustmentResource($service->createDraft(
            $request->csvFile(),
            $request->tenantId(),
            $request->organizationUnitId(),
            (string) $request->input('adjustment_date'),
            $request->warehouseId(),
            $request->warehouseLocationId(),
            $request->currentUserId(),
        ));
    }
}
