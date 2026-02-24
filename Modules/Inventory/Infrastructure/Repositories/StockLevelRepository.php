<?php
namespace Modules\Inventory\Infrastructure\Repositories;
use Modules\Inventory\Domain\Contracts\StockLevelRepositoryInterface;
use Modules\Inventory\Infrastructure\Models\StockLevelModel;
use Modules\Inventory\Application\Services\StockLevelService;
class StockLevelRepository implements StockLevelRepositoryInterface
{
    public function __construct(private StockLevelService $stockLevelService) {}
    public function getStockLevel(string $productId, string $locationId, ?string $variantId = null): ?object
    {
        $query = StockLevelModel::where('product_id', $productId)
            ->where('location_id', $locationId);
        if ($variantId !== null) {
            $query->where('variant_id', $variantId);
        } else {
            $query->whereNull('variant_id');
        }
        return $query->first();
    }
    public function paginate(array $filters, int $perPage = 15): object
    {
        $query = StockLevelModel::query();
        if (!empty($filters['product_id'])) $query->where('product_id', $filters['product_id']);
        if (!empty($filters['location_id'])) $query->where('location_id', $filters['location_id']);
        if (!empty($filters['warehouse_id'])) {
            $query->whereHas('location', fn($q) => $q->where('warehouse_id', $filters['warehouse_id']));
        }
        return $query->paginate($perPage);
    }
    public function adjustStock(string $productId, string $locationId, string $qty, string $type, string $tenantId, ?string $variantId = null): void
    {
        if ($type === 'increase') {
            $this->stockLevelService->increase($productId, $locationId, $qty, $tenantId, $variantId);
        } else {
            $this->stockLevelService->decrease($productId, $locationId, $qty, $tenantId, $variantId);
        }
    }
}
