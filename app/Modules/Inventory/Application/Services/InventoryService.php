<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use App\Support\Repositories\BaseRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Application\DTOs\InventoryRecordData;
use Modules\Inventory\Application\Repositories\CycleCountHeaderRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockAdjustmentRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockLevelRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockMovementRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockReservationRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockTransferRepositoryInterface;
use Modules\Inventory\Application\Repositories\TransferOrderRepositoryInterface;
use Modules\Inventory\Domain\Exceptions\InventoryIntegrityException;
use Modules\Inventory\Domain\Exceptions\InventoryRecordNotFoundException;
use Modules\Inventory\Domain\Services\InventoryDomainService;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;

class InventoryService
{
    public function __construct(
        private readonly Container $container,
        private readonly TenantRepositoryInterface $tenants,
        private readonly StockLevelRepositoryInterface $stockLevels,
        private readonly StockMovementRepositoryInterface $stockMovements,
        private readonly StockReservationRepositoryInterface $reservations,
        private readonly StockTransferRepositoryInterface $stockTransfers,
        private readonly StockAdjustmentRepositoryInterface $stockAdjustments,
        private readonly CycleCountHeaderRepositoryInterface $cycleCountHeaders,
        private readonly TransferOrderRepositoryInterface $transferOrders,
        private readonly InventoryDomainService $domain,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function definition(string $resource): array
    {
        $key = $this->domain->normalizeResourceKey($resource);
        $definition = config("inventory.resources.{$key}");

        if (! is_array($definition)) {
            throw InventoryRecordNotFoundException::for('Inventory resource', $resource);
        }

        return ['key' => $key, ...$definition];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(string $resource, int|string $tenantId, array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $this->ensureTenantExists($tenantId);
        $repository = $this->repository($resource);
        $criteria = ['tenant_id' => $tenantId, ...$filters];

        return $perPage === null
            ? $repository->getWhere($criteria)
            : $repository->paginateWhere($criteria, $perPage);
    }

    public function find(string $resource, int|string $tenantId, int|string $id): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = method_exists($repository, 'findForTenantById')
            ? $repository->findForTenantById($tenantId, $id)
            : $repository->getWhere(['tenant_id' => $tenantId, 'id' => $id])->first();

        if ($record === null) {
            throw InventoryRecordNotFoundException::for($definition['label'] ?? $resource, $id);
        }

        return $record;
    }

    public function create(string $resource, InventoryRecordData $data): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $this->ensureTenantExists($data->tenantId);

        return $repository->transaction(function () use ($definition, $repository, $data): Model {
            $record = $repository->create($this->prepareAttributes($definition['key'], $data->attributes, $data->tenantId));
            $this->applyPostCreateEffects($definition['key'], $record);

            return $this->find($definition['key'], $data->tenantId, $record->getKey());
        });
    }

    public function update(string $resource, int|string $tenantId, int|string $id, InventoryRecordData $data): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = $this->find($resource, $tenantId, $id);

        $this->domain->ensureMutable($definition['key'], $record, $definition, true);
        $this->domain->assertRowVersion($data->rowVersion, $record);

        return $repository->transaction(function () use ($definition, $repository, $record, $data, $tenantId): Model {
            $updated = $repository->update($record, [
                ...$this->prepareAttributes($definition['key'], $data->attributes, $tenantId),
                'row_version' => $this->domain->nextRowVersion($record),
            ]);
            $this->applyPostUpdateEffects($definition['key'], $updated);

            return $this->find($definition['key'], $tenantId, $updated->getKey());
        });
    }

    public function delete(string $resource, int|string $tenantId, int|string $id): bool
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = $this->find($resource, $tenantId, $id);

        $this->domain->ensureMutable($definition['key'], $record, $definition, true);

        return $repository->transaction(function () use ($definition, $repository, $record): bool {
            $this->reversePreDeleteEffects($definition['key'], $record);

            return $repository->delete($record);
        });
    }

    public function recalculateStockLevel(int|string $tenantId, int|string $stockLevelId): Model
    {
        $stockLevel = $this->find('stock_levels', $tenantId, $stockLevelId);
        $criteria = $this->domain->stockDimensionCriteria($stockLevel->getAttributes());

        return $this->stockLevels->transaction(function () use ($stockLevel, $criteria): Model {
            $movements = $this->stockMovements->getWhere(array_diff_key($criteria, ['condition' => true]));
            $reservations = $this->reservations->getWhere(array_diff_key($criteria, ['condition' => true]));

            $quantityOnHand = 0.0;
            foreach ($movements as $movement) {
                $quantityOnHand += (float) $movement->quantity_in - (float) $movement->quantity_out;
            }

            $quantityReserved = 0.0;
            foreach ($reservations as $reservation) {
                $quantityReserved += (float) $reservation->quantity;
            }

            return $this->stockLevels->update($stockLevel, [
                'quantity_on_hand' => $this->domain->normalizeDecimal($quantityOnHand),
                'quantity_reserved' => $this->domain->normalizeDecimal($quantityReserved),
                'row_version' => $this->domain->nextRowVersion($stockLevel),
            ]);
        });
    }

    private function ensureTenantExists(int|string $tenantId): void
    {
        if ($this->tenants->findById($tenantId) === null) {
            throw InventoryRecordNotFoundException::for('Tenant', $tenantId);
        }
    }

    private function repository(string $resource): BaseRepositoryInterface
    {
        $definition = $this->definition($resource);
        $repository = $this->container->make($definition['repository']);

        if (! $repository instanceof BaseRepositoryInterface) {
            throw InventoryIntegrityException::rule("Repository for [{$definition['key']}] must implement BaseRepositoryInterface.");
        }

        return $repository;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareAttributes(string $resource, array $attributes, int|string $tenantId): array
    {
        unset($attributes['row_version']);

        $attributes = [
            ...$this->normalizeScalars($attributes),
            'tenant_id' => $tenantId,
        ];
        $attributes['metadata'] = $this->domain->normalizeMetadata($attributes['metadata'] ?? null);

        return match ($resource) {
            'stock_movements' => $this->domain->prepareMovementAmounts($attributes),
            'stock_reservations' => $this->prepareReservationAttributes($attributes),
            'stock_levels' => $this->prepareStockLevelAttributes($attributes),
            'stock_adjustment_lines', 'cycle_count_lines' => $this->domain->prepareVarianceAmounts($attributes),
            'stock_transfer_lines' => $this->prepareStockTransferLineAttributes($attributes, $tenantId),
            'stock_adjustments' => $this->prepareStatusAttributes($attributes, 'document', 'DRAFT'),
            'stock_transfers' => $this->prepareStatusAttributes($attributes, 'document', 'DRAFT'),
            'transfer_orders' => $this->prepareStatusAttributes($attributes, 'document', 'DRAFT'),
            'cycle_count_headers' => $this->prepareStatusAttributes($attributes, 'cycle_count', 'draft', false),
            'serials' => $this->prepareStatusAttributes($attributes, 'serial', 'AVAILABLE'),
            'batches' => $this->prepareStatusAttributes($attributes, 'batch', 'active', false),
            'valuation_configs', 'inventory_cost_layers' => $this->prepareValuationAttributes($attributes),
            'receipt_inspections' => $this->prepareStatusAttributes($attributes, 'inspection', 'PENDING'),
            'put_away_tasks', 'picking_tasks' => $this->prepareStatusAttributes($attributes, 'task', 'PENDING'),
            'trace_logs' => $this->prepareTraceAttributes($attributes),
            default => $attributes,
        };
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalizeScalars(array $attributes): array
    {
        foreach ($attributes as $key => $value) {
            if (is_string($value)) {
                $attributes[$key] = $this->domain->normalizeText($value);
            }
        }

        foreach ([
            'unit_cost',
            'quantity_on_hand',
            'quantity_reserved',
            'quantity',
            'quantity_in',
            'quantity_out',
            'total_cost',
            'balance_quantity',
            'balance_value',
            'quantity_remaining',
            'system_qty',
            'counted_qty',
            'variance_qty',
            'variance_value',
            'requested_qty',
            'shipped_qty',
            'received_qty',
        ] as $column) {
            if (array_key_exists($column, $attributes) && $attributes[$column] !== null) {
                $attributes[$column] = $this->domain->normalizeDecimal($attributes[$column]);
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareStockLevelAttributes(array $attributes): array
    {
        $attributes['quantity_on_hand'] = $attributes['quantity_on_hand'] ?? $this->domain->normalizeDecimal(0);
        $attributes['quantity_reserved'] = $attributes['quantity_reserved'] ?? $this->domain->normalizeDecimal(0);
        $attributes['condition'] = $attributes['condition'] ?? config('inventory.defaults.condition', 'good');

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareReservationAttributes(array $attributes): array
    {
        $attributes['quantity'] = $this->domain->normalizeDecimal($attributes['quantity'] ?? 0);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareStockTransferLineAttributes(array $attributes, int|string $tenantId): array
    {
        $transfer = $this->stockTransfers->findForTenantById($tenantId, $attributes['stock_transfer_id'] ?? null);

        if ($transfer === null) {
            throw InventoryRecordNotFoundException::for('Stock transfer', $attributes['stock_transfer_id'] ?? null);
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareStatusAttributes(array $attributes, string $family, string $default, bool $uppercase = true): array
    {
        $attributes['status'] = $this->domain->normalizeEnum(
            'status',
            $attributes['status'] ?? null,
            config("inventory.statuses.{$family}", []),
            $default,
            $uppercase
        );

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareValuationAttributes(array $attributes): array
    {
        $attributes['valuation_method'] = $this->domain->normalizeEnum(
            'valuation_method',
            $attributes['valuation_method'] ?? null,
            config('inventory.valuation_methods', []),
            config('inventory.defaults.valuation_method', 'weighted_average'),
            false
        );

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareTraceAttributes(array $attributes): array
    {
        $attributes['action_type'] = $this->domain->normalizeEnum('action_type', $attributes['action_type'] ?? null, config('inventory.trace_actions', []), null, false);

        return $attributes;
    }

    private function applyPostCreateEffects(string $resource, Model $record): void
    {
        if ($resource === 'stock_movements') {
            $this->applyMovementToStockLevel($record, false);
        }

        if ($resource === 'stock_reservations') {
            $this->applyReservationToStockLevel($record, false);
        }
    }

    private function applyPostUpdateEffects(string $resource, Model $record): void
    {
        if ($resource === 'stock_reservations') {
            $this->recalculateStockLevelForRecord($record);
        }
    }

    private function reversePreDeleteEffects(string $resource, Model $record): void
    {
        if ($resource === 'stock_reservations') {
            $this->applyReservationToStockLevel($record, true);
        }
    }

    private function applyMovementToStockLevel(Model $movement, bool $reverse): void
    {
        $stockLevel = $this->stockLevelForRecord($movement);
        $delta = ((float) $movement->quantity_in - (float) $movement->quantity_out) * ($reverse ? -1 : 1);
        $onHand = (float) $stockLevel->quantity_on_hand + $delta;
        $reserved = (float) $stockLevel->quantity_reserved;

        if ($delta < 0) {
            $this->domain->assertEnoughAvailable((float) $stockLevel->quantity_on_hand, $reserved, abs($delta));
        }

        $this->stockLevels->update($stockLevel, [
            'quantity_on_hand' => $this->domain->normalizeDecimal($onHand),
            'unit_cost' => $movement->unit_cost ?? $stockLevel->unit_cost,
            'last_movement_at' => $movement->performed_at,
            'row_version' => $this->domain->nextRowVersion($stockLevel),
        ]);
    }

    private function applyReservationToStockLevel(Model $reservation, bool $reverse): void
    {
        $stockLevel = $this->stockLevelForRecord($reservation);
        $delta = (float) $reservation->quantity * ($reverse ? -1 : 1);

        $this->domain->assertEnoughAvailable(
            (float) $stockLevel->quantity_on_hand,
            (float) $stockLevel->quantity_reserved,
            $reverse ? 0.0 : $delta
        );

        $this->stockLevels->update($stockLevel, [
            'quantity_reserved' => $this->domain->normalizeDecimal((float) $stockLevel->quantity_reserved + $delta),
            'row_version' => $this->domain->nextRowVersion($stockLevel),
        ]);
    }

    private function recalculateStockLevelForRecord(Model $record): void
    {
        $stockLevel = $this->stockLevelForRecord($record);
        $this->recalculateStockLevel($stockLevel->tenant_id, $stockLevel->getKey());
    }

    private function stockLevelForRecord(Model $record): Model
    {
        $attributes = [
            ...$record->getAttributes(),
            'warehouse_id' => $record->warehouse_id,
            'condition' => config('inventory.defaults.condition', 'good'),
        ];
        $criteria = $this->domain->stockDimensionCriteria($attributes);
        $stockLevel = $this->stockLevels->getWhere($criteria)->first();

        if ($stockLevel !== null) {
            return $stockLevel;
        }

        if (($record->warehouse_id ?? null) === null || ($record->uom_id ?? null) === null) {
            throw InventoryIntegrityException::rule('A stock level can only be created from records with warehouse and unit of measure context.');
        }

        return $this->stockLevels->create([
            ...$criteria,
            'organization_unit_id' => $record->organization_unit_id,
            'uom_id' => $record->uom_id,
            'quantity_on_hand' => $this->domain->normalizeDecimal(0),
            'quantity_reserved' => $this->domain->normalizeDecimal(0),
            'unit_cost' => $record->unit_cost,
            'last_movement_at' => $record->performed_at ?? null,
            'metadata' => null,
        ]);
    }
}
