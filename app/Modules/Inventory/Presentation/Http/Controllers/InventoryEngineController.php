<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockLevelRepositoryInterface;
use Modules\Inventory\Application\Contracts\UseCases\InventoryEngines\AllocateInventoryStockServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\InventoryEngines\CalculateInventoryValuationServiceInterface;
use Modules\Inventory\Presentation\Http\Requests\AllocateInventoryStockRequest;
use Modules\Inventory\Presentation\Http\Requests\CalculateInventoryValuationRequest;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;

final class InventoryEngineController extends Controller
{
    public function __construct(
        private readonly CalculateInventoryValuationServiceInterface $calculateInventoryValuationService,
        private readonly AllocateInventoryStockServiceInterface $allocateInventoryStockService,
        private readonly StockLevelRepositoryInterface $stockLevelRepository,
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly UomConversionServiceInterface $uomConversionService,
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
        $payload = $request->validated();
        $criteria = [
            'tenant_id' => (int) $payload['tenant_id'],
            'item_id' => (int) $payload['item_id'],
        ];

        foreach (['organization_unit_id', 'warehouse_id', 'location_id', 'variant_id', 'batch_id', 'serial_id'] as $field) {
            if (array_key_exists($field, $payload) && $payload[$field] !== null) {
                $criteria[$field] = (int) $payload[$field];
            }
        }

        $requestedQuantity = round((float) $payload['quantity'], 4);
        $baseRequestedQuantity = $requestedQuantity;
        $warnings = [];

        if (isset($payload['uom_id'])) {
            $item = $this->itemRepository->findByIdInTenant((int) $payload['item_id'], (int) $payload['tenant_id']);
            $baseUomId = $item instanceof DataRecord ? (int) $item->get('base_uom_id', 0) : 0;
            $requestUomId = (int) $payload['uom_id'];

            if ($baseUomId > 0 && $requestUomId > 0 && $baseUomId !== $requestUomId) {
                $conversion = $this->uomConversionService->convert(
                    $requestedQuantity,
                    $requestUomId,
                    $baseUomId,
                    (int) $payload['tenant_id'],
                    (int) $payload['item_id'],
                );

                if ($conversion->isFailure()) {
                    $error = $conversion->errorOrFail();

                    return response()->json([
                        'message' => $error->message,
                        'code' => $error->code,
                        'context' => $error->context,
                        'errors' => [
                            'uom_id' => ['The selected UOM cannot be converted to the item base UOM.'],
                        ],
                    ], 422);
                }

                $baseRequestedQuantity = round((float) $conversion->valueOrFail(), 4);
                $warnings[] = 'Requested quantity was converted to the item base UOM before checking availability.';
            }
        }

        $quantityOnHand = 0.0;
        $reservedQuantity = 0.0;
        $blockedQuantity = 0.0;
        $damagedQuantity = 0.0;

        foreach ($this->stockLevelRepository->list($criteria) as $stockLevel) {
            if (! $stockLevel instanceof DataRecord) {
                continue;
            }

            $quantityOnHand += (float) $stockLevel->get('quantity_on_hand', 0);
            $reservedQuantity += (float) $stockLevel->get('quantity_reserved', 0);
            $blockedQuantity += (float) $stockLevel->get('quantity_blocked', 0);
            $damagedQuantity += (float) $stockLevel->get('quantity_damaged', 0);
        }

        $availableQuantity = round(max(0, $quantityOnHand - $reservedQuantity - $blockedQuantity - $damagedQuantity), 4);
        $decision = $availableQuantity >= $baseRequestedQuantity ? 'available' : 'insufficient';

        return response()->json([
            'breakdown' => [
                ['label' => 'Requested in base UOM', 'value' => number_format($baseRequestedQuantity, 4, '.', '')],
                ['label' => 'On hand', 'value' => number_format($quantityOnHand, 4, '.', '')],
                ['label' => 'Reserved', 'value' => number_format($reservedQuantity, 4, '.', '')],
                ['label' => 'Blocked', 'value' => number_format($blockedQuantity, 4, '.', '')],
                ['label' => 'Damaged', 'value' => number_format($damagedQuantity, 4, '.', '')],
            ],
            'calculated' => [
                'requestedQuantity' => number_format($requestedQuantity, 4, '.', ''),
                'baseRequestedQuantity' => number_format($baseRequestedQuantity, 4, '.', ''),
                'availableQuantity' => number_format($availableQuantity, 4, '.', ''),
                'reservedQuantity' => number_format($reservedQuantity, 4, '.', ''),
                'requested_quantity' => number_format($requestedQuantity, 4, '.', ''),
                'base_requested_quantity' => number_format($baseRequestedQuantity, 4, '.', ''),
                'available_quantity' => number_format($availableQuantity, 4, '.', ''),
                'reserved_quantity' => number_format($reservedQuantity, 4, '.', ''),
                'decision' => $decision,
                'status' => $decision,
            ],
            'errors' => $decision === 'available' ? [] : ['Requested quantity exceeds available stock for the selected dimensions.'],
            'input' => $payload,
            'warnings' => $quantityOnHand <= 0 ? [...$warnings, 'No stock levels matched the selected dimensions.'] : $warnings,
        ]);
    }
}
