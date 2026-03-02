<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Domain\Contracts\InventoryRepositoryInterface;
use Modules\Inventory\Domain\Entities\StockLedgerEntry as EntryEntity;
use Modules\Inventory\Domain\Enums\LedgerEntryType;
use Modules\Inventory\Infrastructure\Models\StockLedgerEntry as EntryModel;

class InventoryRepository implements InventoryRepositoryInterface
{
    public function getStockLevel(int $productId, int $warehouseId, int $tenantId): string
    {
        $inbound = (string) EntryModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->whereIn('type', array_map(
                fn (LedgerEntryType $t): string => $t->value,
                array_filter(LedgerEntryType::cases(), fn (LedgerEntryType $t): bool => $t->isInbound())
            ))
            ->sum('quantity');

        $outbound = (string) EntryModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->whereIn('type', array_map(
                fn (LedgerEntryType $t): string => $t->value,
                array_filter(LedgerEntryType::cases(), fn (LedgerEntryType $t): bool => $t->isOutbound())
            ))
            ->sum('quantity');

        return bcsub($inbound ?: '0', $outbound ?: '0', 4);
    }

    public function getStockLevelForUpdate(int $productId, int $warehouseId, int $tenantId): string
    {
        $inboundTypes = array_map(
            fn (LedgerEntryType $t): string => $t->value,
            array_values(array_filter(LedgerEntryType::cases(), fn (LedgerEntryType $t): bool => $t->isInbound()))
        );

        // Build placeholders for the IN clause (one ? per value)
        $placeholders = implode(',', array_fill(0, count($inboundTypes), '?'));

        $result = DB::selectOne(
            "SELECT COALESCE(SUM(CASE WHEN type IN ({$placeholders}) THEN quantity ELSE -quantity END), 0) AS balance
             FROM stock_ledger_entries
             WHERE tenant_id = ? AND product_id = ? AND warehouse_id = ?
             FOR UPDATE",
            array_merge($inboundTypes, [$tenantId, $productId, $warehouseId])
        );

        return bcadd((string) ($result->balance ?? '0'), '0', 4);
    }

    public function recordEntry(EntryEntity $entry): EntryEntity
    {
        $model = EntryModel::create([
            'tenant_id'    => $entry->getTenantId(),
            'product_id'   => $entry->getProductId(),
            'variant_id'   => $entry->getVariantId(),
            'warehouse_id' => $entry->getWarehouseId(),
            'type'         => $entry->getType()->value,
            'quantity'     => $entry->getQuantity(),
            'unit_cost'    => $entry->getUnitCost(),
            'reference_type' => $entry->getReferenceType(),
            'reference_id' => $entry->getReferenceId(),
            'notes'        => $entry->getNotes(),
        ]);

        return $this->toDomain($model);
    }

    public function getHistory(int $tenantId, int $productId, int $warehouseId, int $page, int $perPage): array
    {
        return EntryModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->orderByDesc('created_at')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn (EntryModel $m): EntryEntity => $this->toDomain($m))
            ->all();
    }

    private function toDomain(EntryModel $model): EntryEntity
    {
        return new EntryEntity(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            productId: (int) $model->product_id,
            variantId: $model->variant_id ? (int) $model->variant_id : null,
            warehouseId: (int) $model->warehouse_id,
            type: $model->type instanceof LedgerEntryType
                ? $model->type
                : LedgerEntryType::from((string) $model->type),
            quantity: (string) $model->quantity,
            unitCost: (string) $model->unit_cost,
            referenceType: $model->reference_type,
            referenceId: $model->reference_id ? (int) $model->reference_id : null,
            notes: $model->notes,
            createdAt: new DateTimeImmutable((string) $model->created_at),
        );
    }
}
