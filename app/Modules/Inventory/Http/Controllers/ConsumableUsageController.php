<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Enums\InventoryStatus;
use Modules\Inventory\Http\Requests\InventoryLookupRequest;
use Modules\Inventory\Http\Requests\ListConsumableUsageRequest;
use Modules\Inventory\Http\Requests\ReverseConsumableUsageRequest;
use Modules\Inventory\Http\Requests\StoreConsumableUsageRequest;
use Modules\Inventory\Http\Resources\ConsumableLookupResource;
use Modules\Inventory\Http\Resources\ConsumableUsageResource;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Services\ConsumableUsageService;
use Modules\Item\Services\ItemQueryService;

final class ConsumableUsageController
{
    public function items(InventoryLookupRequest $request, ItemQueryService $items): AnonymousResourceCollection
    {
        return ConsumableLookupResource::collection($items->lookup(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->perPage(),
            'consumable',
        ));
    }

    public function index(ListConsumableUsageRequest $request): AnonymousResourceCollection
    {
        $from = (string) ($request->input('from') ?: now()->startOfMonth()->toDateString());
        $to = (string) ($request->input('to') ?: now()->toDateString());
        $exclusiveTo = CarbonImmutable::parse($to)->addDay()->toDateString();
        $query = InventoryMovement::query()
            ->where('tenant_id', $request->tenantId())
            ->where('movement_type', InventoryMovementType::InternalConsumption->value)
            ->where('movement_date', '>=', $from)
            ->where('movement_date', '<', $exclusiveTo);
        $request->organizationUnitId() === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $request->organizationUnitId());
        if ($request->filled('item_id')) {
            $query->where('item_id', (int) $request->input('item_id'));
        }

        $totals = (clone $query)
            ->where('status', InventoryStatus::Posted->value)
            ->selectRaw('item_id, SUM(quantity) AS total_quantity, SUM(total_cost) AS total_cost')
            ->groupBy('item_id')
            ->with('item.baseUom')
            ->orderBy('item_id')
            ->get()
            ->map(fn (InventoryMovement $row): array => [
                'item' => ['id' => $row->item->getKey(), 'name' => $row->item->name, 'code' => $row->item->code],
                'quantity' => (string) $row->total_quantity,
                'uom' => $row->item->baseUom?->name,
                'cost' => (string) $row->total_cost,
            ]);

        return ConsumableUsageResource::collection(
            $query->with(['item', 'warehouse', 'warehouseLocation', 'variant', 'batch', 'baseUom'])
                ->orderByDesc('movement_date')
                ->orderByDesc('id')
                ->paginate((int) ($request->input('per_page') ?: 25)),
        )->additional(['summary' => ['from' => $from, 'to' => $to, 'items' => $totals]]);
    }

    public function store(StoreConsumableUsageRequest $request, ConsumableUsageService $service): JsonResponse
    {
        if (trim((string) $request->header('Idempotency-Key')) === '') {
            throw ValidationException::withMessages(['idempotency_key' => ['Idempotency-Key header is required.']]);
        }

        $movement = $service->post(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->currentUserId(),
            (string) $request->header('Idempotency-Key'),
        );

        return (new ConsumableUsageResource($movement->load(['item', 'warehouse', 'warehouseLocation', 'variant', 'batch', 'baseUom'])))
            ->response()->setStatusCode(201);
    }

    public function reverse(ReverseConsumableUsageRequest $request, int $usage, ConsumableUsageService $service): ConsumableUsageResource
    {
        $movement = $service->reverse(
            $request->tenantId(),
            $request->organizationUnitId(),
            $usage,
            $request->currentUserId(),
            (string) $request->input('reason'),
        );

        return new ConsumableUsageResource($movement->load(['item', 'warehouse', 'warehouseLocation', 'variant', 'batch', 'baseUom']));
    }
}
