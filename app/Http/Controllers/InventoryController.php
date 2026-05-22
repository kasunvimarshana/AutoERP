<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventoryAllocationRequest;
use App\Http\Requests\StoreInventoryValuationRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Modules\Inventory\Application\DTOs\AllocationRequest;
use Modules\Inventory\Application\DTOs\ValuationRequest;
use Modules\Inventory\Application\Services\InventoryAllocationService;
use Modules\Inventory\Application\Services\InventoryValuationService;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryValuationService $valuationService,
        private readonly InventoryAllocationService $allocationService,
    ) {
    }

    public function valuate(StoreInventoryValuationRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $dto = new ValuationRequest(
            tenantId: (int) ($payload['tenant_id'] ?? env('DEFAULT_TENANT_ID')),
            itemId: (int) $payload['item_id'],
            locationId: (int) $payload['location_id'],
            uomId: (int) $payload['uom_id'],
            direction: (string) $payload['direction'],
            quantity: (float) $payload['quantity'],
            warehouseId: isset($payload['warehouse_id']) ? (int) $payload['warehouse_id'] : null,
            organizationUnitId: isset($payload['organization_unit_id'])
                ? (int) $payload['organization_unit_id']
                : (int) env('DEFAULT_OU_ID'),
            variantId: isset($payload['variant_id']) ? (int) $payload['variant_id'] : null,
            batchId: isset($payload['batch_id']) ? (int) $payload['batch_id'] : null,
            serialId: isset($payload['serial_id']) ? (int) $payload['serial_id'] : null,
            unitCost: isset($payload['unit_cost']) ? (float) $payload['unit_cost'] : null,
            txnType: $payload['txn_type'] ?? null,
            performedBy: isset($payload['performed_by']) ? (int) $payload['performed_by'] : null,
            performedAt: isset($payload['performed_at']) ? CarbonImmutable::parse($payload['performed_at']) : null,
            notes: $payload['notes'] ?? null,
            referenceType: $payload['reference_type'] ?? null,
            referenceId: isset($payload['reference_id']) ? (int) $payload['reference_id'] : null,
            metadata: $payload['metadata'] ?? [],
            valuationMethod: $payload['valuation_method'] ?? null,
            layerDate: isset($payload['layer_date']) ? CarbonImmutable::parse($payload['layer_date']) : null,
        );

        $result = $this->valuationService->process($dto);

        return response()->json([
            'valuation_method' => $result->valuationMethod,
            'direction' => $result->direction,
            'quantity' => $result->quantity,
            'unit_cost' => $result->unitCost,
            'total_cost' => $result->totalCost,
            'balance_quantity' => $result->balanceQuantity,
            'balance_value' => $result->balanceValue,
            'consumptions' => array_map(static fn ($c) => [
                'layer_id' => $c->layerId,
                'consumed_quantity' => $c->consumedQuantity,
                'unit_cost' => $c->unitCost,
            ], $result->consumptions),
        ], HttpResponse::HTTP_CREATED);
    }

    public function allocate(StoreInventoryAllocationRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $dto = new AllocationRequest(
            tenantId: (int) ($payload['tenant_id'] ?? env('DEFAULT_TENANT_ID')),
            itemId: (int) $payload['item_id'],
            requiredQuantity: (float) $payload['required_quantity'],
            locationId: isset($payload['location_id']) ? (int) $payload['location_id'] : null,
            warehouseId: isset($payload['warehouse_id']) ? (int) $payload['warehouse_id'] : null,
            variantId: isset($payload['variant_id']) ? (int) $payload['variant_id'] : null,
            allocationMethod: $payload['allocation_method'] ?? null,
            preferredBatchIds: array_map(static fn ($id) => (int) $id, $payload['preferred_batch_ids'] ?? []),
            preferredLotNumbers: array_map(static fn ($lot) => (string) $lot, $payload['preferred_lot_numbers'] ?? []),
            referenceType: $payload['reference_type'] ?? null,
            referenceId: isset($payload['reference_id']) ? (int) $payload['reference_id'] : null,
            persistReservation: (bool) ($payload['persist_reservation'] ?? true),
            expiresAt: isset($payload['expires_at']) ? CarbonImmutable::parse($payload['expires_at']) : null,
            metadata: $payload['metadata'] ?? [],
            ruleContext: $payload['rule_context'] ?? [],
            ruleKeys: array_map(static fn ($key) => (string) $key, $payload['rule_keys'] ?? []),
            organizationUnitId: isset($payload['organization_unit_id'])
                ? (int) $payload['organization_unit_id']
                : (int) env('DEFAULT_OU_ID'),
        );

        $result = $this->allocationService->allocate($dto);

        return response()->json([
            'allocation_method' => $result->allocationMethod,
            'requested_quantity' => $result->requestedQuantity,
            'allocated_quantity' => $result->allocatedQuantity,
            'fully_allocated' => $result->isFullyAllocated(),
            'lines' => array_map(static fn ($line) => [
                'stock_level_id' => $line->stockLevelId,
                'location_id' => $line->locationId,
                'batch_id' => $line->batchId,
                'serial_id' => $line->serialId,
                'quantity' => $line->quantity,
                'unit_cost' => $line->unitCost,
                'batch_number' => $line->batchNumber,
                'lot_number' => $line->lotNumber,
            ], $result->lines),
        ], HttpResponse::HTTP_OK);
    }
}
