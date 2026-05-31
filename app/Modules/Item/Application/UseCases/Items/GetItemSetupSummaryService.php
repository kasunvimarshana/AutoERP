<?php

declare(strict_types=1);

namespace Modules\Item\Application\UseCases\Items;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Item\Application\Contracts\UseCases\Items\GetItemSetupSummaryServiceInterface;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\Item\Domain\Constants\ItemErrorCode;
use Throwable;

final class GetItemSetupSummaryService implements GetItemSetupSummaryServiceInterface
{
    public function __construct(
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
    ) {
    }

    public function capabilities(int|string $id): Result
    {
        return $this->forItem($id, fn (DataRecord $item, int $tenantId): array => $this->buildCapabilities($item, $tenantId));
    }

    public function inventorySummary(int|string $id): Result
    {
        return $this->forItem($id, function (DataRecord $item, int $tenantId): array {
            $itemId = (int) $item->id();
            $stockable = $this->bool($item->get('is_stockable'));
            $stock = $this->stockTotals($tenantId, $itemId);

            return [
                'item_id' => $itemId,
                'is_stockable' => $stockable,
                'quantity_on_hand' => $stock['quantity_on_hand'],
                'quantity_reserved' => $stock['quantity_reserved'],
                'quantity_available' => $stock['quantity_available'],
                'stock_level_count' => $stock['stock_level_count'],
                'minimum_stock' => $this->number($item->get('minimum_stock')),
                'reorder_point' => $this->number($item->get('reorder_point')),
                'reorder_quantity' => $this->nullableNumber($item->get('reorder_quantity')),
                'safety_stock' => $this->number($item->get('safety_stock')),
                'valuation_method' => $item->get('valuation_method'),
                'standard_cost' => $this->nullableNumber($item->get('standard_cost')),
                'inventory_account_id' => $item->get('inventory_account_id'),
                'cogs_account_id' => $item->get('cogs_account_id'),
            ];
        });
    }

    public function pricingReferences(int|string $id): Result
    {
        return $this->forItem($id, function (DataRecord $item, int $tenantId): array {
            if (! Schema::hasTable('price_list_items')) {
                return ['item_id' => (int) $item->id(), 'count' => 0, 'references' => []];
            }

            $rows = DB::table('price_list_items')
                ->leftJoin('price_lists', 'price_list_items.price_list_id', '=', 'price_lists.id')
                ->where('price_list_items.tenant_id', $tenantId)
                ->where('price_list_items.item_id', (int) $item->id())
                ->select([
                    'price_list_items.id',
                    'price_list_items.price_list_id',
                    'price_list_items.uom_id',
                    'price_list_items.currency_id',
                    'price_list_items.price',
                    'price_list_items.discount_type',
                    'price_list_items.discount_value',
                    'price_list_items.is_active',
                    'price_lists.code as price_list_code',
                    'price_lists.name as price_list_name',
                ])
                ->orderBy('price_list_items.id')
                ->limit(200)
                ->get()
                ->map(fn (object $row): array => [
                    'id' => (int) $row->id,
                    'price_list_id' => $row->price_list_id,
                    'price_list_code' => $row->price_list_code,
                    'price_list_name' => $row->price_list_name,
                    'uom_id' => $row->uom_id,
                    'currency_id' => $row->currency_id,
                    'price' => $this->number($row->price),
                    'discount_type' => $row->discount_type,
                    'discount_value' => $this->number($row->discount_value),
                    'is_active' => (bool) $row->is_active,
                ])
                ->all();

            return ['item_id' => (int) $item->id(), 'count' => count($rows), 'references' => $rows];
        });
    }

    public function uomSetup(int|string $id): Result
    {
        return $this->forItem($id, function (DataRecord $item): array {
            return [
                'item_id' => (int) $item->id(),
                'base_uom_id' => $item->get('base_uom_id'),
                'default_receipt_uom_id' => $item->get('default_receipt_uom_id'),
                'default_issue_uom_id' => $item->get('default_issue_uom_id'),
                'default_consumption_uom_id' => $item->get('default_consumption_uom_id'),
                'default_charge_uom_id' => $item->get('default_charge_uom_id'),
                'is_configured' => $item->get('base_uom_id') !== null,
            ];
        });
    }

    public function previewTypeSetup(array $payload): Result
    {
        try {
            $tenantId = $this->currentTenant->currentTenantId();
            if ($tenantId === null) {
                return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, 'Tenant context is required.'));
            }

            $record = new DataRecord(array_merge([
                'id' => 0,
                'type' => 'inventory_product',
                'is_stockable' => false,
                'is_purchasable' => false,
                'is_sellable' => false,
                'is_service' => false,
                'is_rentable' => false,
                'is_chargeable' => false,
                'is_batch_tracked' => false,
                'is_serial_tracked' => false,
                'base_uom_id' => null,
                'default_receipt_uom_id' => null,
                'default_issue_uom_id' => null,
                'default_consumption_uom_id' => null,
                'default_charge_uom_id' => null,
            ], $payload));

            $capabilities = $this->buildCapabilities($record, $tenantId);
            $warnings = [];

            if ($capabilities['stockable'] && $record->get('base_uom_id') === null) {
                $warnings[] = 'Stockable item setup requires a base UOM.';
            }

            if ($this->type($record) === 'combo' && ! $capabilities['has_combo_components']) {
                $warnings[] = 'Combo items should include at least one component before operational use.';
            }

            return Result::success([
                'capabilities' => $capabilities,
                'warnings' => $warnings,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param callable(DataRecord,int): array<string, mixed> $callback
     */
    private function forItem(int|string $id, callable $callback): Result
    {
        try {
            $tenantId = $this->currentTenant->currentTenantId();
            if ($tenantId === null) {
                return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, 'Tenant context is required.'));
            }

            $item = $this->itemRepository->findByIdInTenant($id, $tenantId);
            if ($item === null) {
                return Result::failure(new Error(ItemErrorCode::NOT_FOUND, 'Item not found.'));
            }

            return Result::success($callback($item, $tenantId));
        } catch (Throwable $exception) {
            return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCapabilities(DataRecord $item, int $tenantId): array
    {
        $itemId = (int) $item->id();

        return [
            'item_id' => $itemId,
            'item_type' => $this->type($item),
            'stockable' => $this->bool($item->get('is_stockable')),
            'affects_inventory' => $this->bool($item->get('is_stockable')),
            'purchasable' => $this->bool($item->get('is_purchasable')),
            'sellable' => $this->bool($item->get('is_sellable')),
            'service_usable' => $this->bool($item->get('is_service')),
            'rental_usable' => $this->bool($item->get('is_rentable')),
            'chargeable' => $this->bool($item->get('is_chargeable')),
            'batch_tracking' => $this->bool($item->get('is_batch_tracked')),
            'serial_tracking' => $this->bool($item->get('is_serial_tracked')),
            'has_variants' => $this->exists('item_variants', $tenantId, 'item_id', $itemId),
            'has_combo_components' => $this->exists('combo_items', $tenantId, 'combo_item_id', $itemId),
            'has_identifiers' => $this->exists('item_identifiers', $tenantId, 'item_id', $itemId),
            'uom_configured' => $item->get('base_uom_id') !== null,
            'pricing_references_count' => $this->count('price_list_items', $tenantId, 'item_id', $itemId),
            'inventory_references_count' => $this->count('stock_levels', $tenantId, 'item_id', $itemId),
        ];
    }

    /**
     * @return array{quantity_on_hand:float,quantity_reserved:float,quantity_available:float,stock_level_count:int}
     */
    private function stockTotals(int $tenantId, int $itemId): array
    {
        if (! Schema::hasTable('stock_levels')) {
            return ['quantity_on_hand' => 0.0, 'quantity_reserved' => 0.0, 'quantity_available' => 0.0, 'stock_level_count' => 0];
        }

        $row = DB::table('stock_levels')
            ->where('tenant_id', $tenantId)
            ->where('item_id', $itemId)
            ->selectRaw('COUNT(*) as stock_level_count, COALESCE(SUM(quantity_on_hand), 0) as quantity_on_hand, COALESCE(SUM(quantity_reserved), 0) as quantity_reserved')
            ->first();

        $onHand = $this->number($row?->quantity_on_hand);
        $reserved = $this->number($row?->quantity_reserved);

        return [
            'quantity_on_hand' => $onHand,
            'quantity_reserved' => $reserved,
            'quantity_available' => $onHand - $reserved,
            'stock_level_count' => (int) ($row?->stock_level_count ?? 0),
        ];
    }

    private function exists(string $table, int $tenantId, string $column, int $id): bool
    {
        return $this->count($table, $tenantId, $column, $id) > 0;
    }

    private function count(string $table, int $tenantId, string $column, int $id): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return DB::table($table)
            ->where('tenant_id', $tenantId)
            ->where($column, $id)
            ->count();
    }

    private function type(DataRecord $item): string
    {
        return strtolower(str_replace([' ', '-'], '_', (string) $item->get('type', 'inventory_product')));
    }

    private function bool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    private function number(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function nullableNumber(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
