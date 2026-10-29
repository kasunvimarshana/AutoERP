<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryStatus;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Validators\InventoryValidationService;
use Modules\Item\Enums\ItemBaseUomRevisionStatus;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemBaseUomRevision;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

class StockCardService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryValidationService $validation,
        private readonly InventorySourceDocumentResolver $documents,
    ) {}

    public function build(array $filters, int $tenantId, ?int $organizationUnitId): array
    {
        $item = Item::query()->with('baseUom')->findOrFail((int) $filters['item_id']);
        $warehouse = WarehouseModel::query()->findOrFail((int) $filters['warehouse_id']);
        $this->validation->assertScope($tenantId, $organizationUnitId, (int) $item->tenant_id, $item->organization_unit_id);
        $this->validation->assertScope($tenantId, $organizationUnitId, (int) $warehouse->tenant_id, $warehouse->organization_unit_id);

        $location = null;
        if (! empty($filters['warehouse_location_id'])) {
            $location = WarehouseLocationModel::query()->findOrFail((int) $filters['warehouse_location_id']);
            $this->validation->assertScope($tenantId, $organizationUnitId, (int) $location->tenant_id, $location->organization_unit_id);
            if ((int) $location->warehouse_id !== (int) $warehouse->id) {
                throw ValidationException::withMessages([
                    'warehouse_location_id' => 'The selected location does not belong to the warehouse.',
                ]);
            }
        }

        $timezone = (string) ($filters['timezone'] ?? config('app.timezone'));
        $from = CarbonImmutable::parse($filters['date_from'], $timezone)->startOfDay()->utc();
        $to = CarbonImmutable::parse($filters['date_to'], $timezone)->addDay()->startOfDay()->utc();
        $snapshot = CarbonImmutable::now('UTC');
        $revisions = ItemBaseUomRevision::query()
            ->where('tenant_id', $tenantId)
            ->where('item_id', $item->id)
            ->where('status', ItemBaseUomRevisionStatus::Applied->value)
            ->where('applied_at', '<=', $snapshot)
            ->orderBy('applied_at')
            ->orderBy('id')
            ->get();

        $query = InventoryMovement::query()
            ->where('tenant_id', $tenantId)
            ->where('item_id', $item->id)
            ->where('warehouse_id', $warehouse->id)
            ->whereIn('status', [InventoryStatus::Posted->value, InventoryStatus::Reversed->value])
            ->whereNotNull('posted_at')
            ->where('posted_at', '<=', $snapshot);

        if ($organizationUnitId !== null) {
            $query->where('organization_unit_id', $organizationUnitId);
        }
        if ($location !== null) {
            $query->where('warehouse_location_id', $location->id);
        }

        return DB::transaction(function () use ($query, $from, $to, $snapshot, $tenantId, $organizationUnitId, $item, $warehouse, $location, $filters, $timezone, $revisions): array {
            $opening = $this->sumSigned(clone $query, $from, $revisions->all(), (int) $item->base_uom_id);
            $movements = (clone $query)
                ->where('posted_at', '>=', $from)
                ->where('posted_at', '<', $to)
                ->orderBy('posted_at')
                ->orderBy('id')
                ->get();
            $documents = $this->documents->resolve($movements->all(), $tenantId, $organizationUnitId);
            $balance = $opening;
            $totalIn = $this->math->normalize(0);
            $totalOut = $this->math->normalize(0);
            $rows = [];

            foreach ($movements as $movement) {
                $direction = $movement->direction->value;
                $quantity = $this->currentQuantity($movement, $revisions->all(), (int) $item->base_uom_id);
                $in = $direction === InventoryDirection::In->value ? $quantity : $this->math->normalize(0);
                $out = $direction === InventoryDirection::Out->value ? $quantity : $this->math->normalize(0);
                $totalIn = $this->math->add($totalIn, $in);
                $totalOut = $this->math->add($totalOut, $out);
                $balance = $this->math->sub($this->math->add($balance, $in), $out);
                $document = $documents[(string) $movement->source_type][(int) $movement->source_id] ?? null;

                $rows[] = [
                    'posted_at' => $movement->posted_at->copy()->setTimezone($timezone)->toIso8601String(),
                    'document_date' => $movement->movement_date?->format('Y-m-d'),
                    'movement_number' => $movement->movement_number,
                    'description' => $movement->description,
                    'type' => $movement->movement_type->value,
                    'document' => $document,
                    'quantity_in' => $in,
                    'quantity_out' => $out,
                    'balance' => $balance,
                ];
            }

            return [
                'item' => ['name' => $item->name, 'code' => $item->code, 'uom' => $item->baseUom?->name],
                'warehouse' => ['name' => $warehouse->name, 'code' => $warehouse->code],
                'location' => $location === null ? null : ['name' => $location->name, 'code' => $location->code],
                'date_from' => $filters['date_from'],
                'date_to' => $filters['date_to'],
                'timezone' => $timezone,
                'snapshot_at' => $snapshot->toIso8601String(),
                'opening' => $opening,
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'closing' => $balance,
                'rows' => $rows,
            ];
        });
    }

    private function sumSigned(Builder $query, CarbonImmutable $before, array $revisions, int $currentUomId): string
    {
        $balance = $this->math->normalize(0);
        foreach ($query->where('posted_at', '<', $before)->cursor() as $movement) {
            $quantity = $this->currentQuantity($movement, $revisions, $currentUomId);
            if ($movement->direction === InventoryDirection::In) {
                $balance = $this->math->add($balance, $quantity);
            } elseif ($movement->direction === InventoryDirection::Out) {
                $balance = $this->math->sub($balance, $quantity);
            }
        }

        return $balance;
    }

    private function currentQuantity(InventoryMovement $movement, array $revisions, int $currentUomId): string
    {
        $quantity = $this->math->normalize((string) $movement->quantity);
        $uomId = (int) $movement->base_uom_id;

        foreach ($revisions as $revision) {
            if ($revision->applied_at < $movement->posted_at || (int) $revision->old_base_uom_id !== $uomId) {
                continue;
            }

            $quantity = $this->math->mul($quantity, (string) $revision->conversion_factor);
            $uomId = (int) $revision->new_base_uom_id;
        }

        if ($uomId !== $currentUomId) {
            throw new \LogicException('A historical inventory movement cannot be expressed in the current item base unit.');
        }

        return $quantity;
    }
}
