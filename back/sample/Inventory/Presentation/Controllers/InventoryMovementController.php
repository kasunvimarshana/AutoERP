<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Inventory\Application\DTOs\MovementLineDTO;
use Modules\Inventory\Application\DTOs\PostInventoryMovementDTO;
use Modules\Inventory\Application\Orchestrators\InventoryMovementOrchestrator;
use Modules\Inventory\Domain\Enums\StockMovementDirection;
use Modules\Inventory\Presentation\Requests\PostInventoryMovementRequest;

class InventoryMovementController extends Controller
{
    public function __construct(
        private readonly InventoryMovementOrchestrator $orchestrator,
    ) {
    }

    public function store(PostInventoryMovementRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $line = new MovementLineDTO(
            tenantId: (int) $payload['tenant_id'],
            organizationUnitId: isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null,
            itemId: (int) $payload['item_id'],
            variantId: isset($payload['variant_id']) ? (int) $payload['variant_id'] : null,
            warehouseId: isset($payload['warehouse_id']) ? (int) $payload['warehouse_id'] : null,
            locationId: isset($payload['location_id']) ? (int) $payload['location_id'] : null,
            batchId: isset($payload['batch_id']) ? (int) $payload['batch_id'] : null,
            serialId: isset($payload['serial_id']) ? (int) $payload['serial_id'] : null,
            uomId: (int) $payload['uom_id'],
            quantity: (float) $payload['quantity'],
            direction: StockMovementDirection::from($payload['direction']),
            txnType: (string) $payload['txn_type'],
            providedUnitCost: isset($payload['provided_unit_cost']) ? (float) $payload['provided_unit_cost'] : null,
            performedBy: isset($payload['performed_by']) ? (int) $payload['performed_by'] : null,
            referenceType: $payload['reference_type'] ?? null,
            referenceId: isset($payload['reference_id']) ? (int) $payload['reference_id'] : null,
            notes: $payload['notes'] ?? null,
            currencyId: isset($payload['currency_id']) ? (int) $payload['currency_id'] : null,
            exchangeRate: isset($payload['exchange_rate']) ? (float) $payload['exchange_rate'] : null,
            metadata: (array) ($payload['metadata'] ?? []),
        );

        $movement = $this->orchestrator->post(new PostInventoryMovementDTO($line));

        return response()->json([
            'success' => true,
            'movement_id' => $movement->id,
            'total_cost' => $movement->total_cost,
            'direction' => $movement->direction,
        ], 201);
    }
}
