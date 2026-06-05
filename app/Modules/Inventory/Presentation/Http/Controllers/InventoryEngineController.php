<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Inventory\Application\Contracts\UseCases\InventoryEngines\AllocateInventoryStockServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\InventoryEngines\CalculateInventoryValuationServiceInterface;
use Modules\Inventory\Application\Services\StockAvailabilityService;
use Modules\Inventory\Presentation\Http\Requests\AllocateInventoryStockRequest;
use Modules\Inventory\Presentation\Http\Requests\CalculateInventoryValuationRequest;

final class InventoryEngineController extends Controller
{
    public function __construct(
        private readonly CalculateInventoryValuationServiceInterface $calculateInventoryValuationService,
        private readonly AllocateInventoryStockServiceInterface $allocateInventoryStockService,
        private readonly StockAvailabilityService $stockAvailabilityService,
    ) {}

    public function calculateValuation(CalculateInventoryValuationRequest $request): JsonResponse
    {
        $result = $this->calculateInventoryValuationService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json([
                'message' => $result->errorOrFail()->message,
                'code' => $result->errorOrFail()->code,
                'context' => $result->errorOrFail()->context,
            ], 422);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function allocateStock(AllocateInventoryStockRequest $request): JsonResponse
    {
        $result = $this->allocateInventoryStockService->execute($request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();

            return response()->json([
                'message' => $error->message,
                'code' => $error->code,
                'context' => $error->context,
            ], 422);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function previewAvailability(AllocateInventoryStockRequest $request): JsonResponse
    {
        $result = $this->stockAvailabilityService->check($request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $response = [
                'message' => $error->message,
                'code' => $error->code,
                'context' => $error->context,
            ];

            if (str_contains(strtolower($error->message), 'uom')) {
                $response['errors'] = [
                    'uom_id' => ['The selected UOM cannot be converted to the item base UOM.'],
                ];
            }

            return response()->json($response, 422);
        }

        return response()->json($result->valueOrFail());
    }
}
