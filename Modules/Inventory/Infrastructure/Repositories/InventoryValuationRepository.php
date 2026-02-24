<?php

namespace Modules\Inventory\Infrastructure\Repositories;

use Modules\Inventory\Domain\Contracts\InventoryValuationRepositoryInterface;
use Modules\Inventory\Infrastructure\Models\InventoryValuationEntryModel;

class InventoryValuationRepository implements InventoryValuationRepositoryInterface
{
    public function findLastByProduct(string $tenantId, string $productId): ?object
    {
        return InventoryValuationEntryModel::where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }

    public function create(array $data): object
    {
        return InventoryValuationEntryModel::create($data);
    }

    public function paginate(string $tenantId, array $filters = [], int $perPage = 20): object
    {
        $query = InventoryValuationEntryModel::where('tenant_id', $tenantId)
            ->orderByDesc('created_at');

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }

        if (! empty($filters['valuation_method'])) {
            $query->where('valuation_method', $filters['valuation_method']);
        }

        return $query->paginate($perPage);
    }

    public function valuationReport(string $tenantId): iterable
    {
        // Collect distinct product IDs then fetch the latest entry per product
        $productIds = InventoryValuationEntryModel::where('tenant_id', $tenantId)
            ->distinct()
            ->pluck('product_id');

        $results = [];
        foreach ($productIds as $productId) {
            $last = $this->findLastByProduct($tenantId, $productId);
            if ($last) {
                $results[] = (object) [
                    'product_id'       => $productId,
                    'total_qty'        => $last->running_balance_qty,
                    'total_value'      => $last->running_balance_value,
                    'valuation_method' => $last->valuation_method,
                    'as_of'            => $last->created_at,
                ];
            }
        }
        return $results;
    }
}
