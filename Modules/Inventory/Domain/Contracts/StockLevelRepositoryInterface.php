<?php
namespace Modules\Inventory\Domain\Contracts;
interface StockLevelRepositoryInterface
{
    public function getStockLevel(string $productId, string $locationId, ?string $variantId = null): ?object;
    public function paginate(array $filters, int $perPage = 15): object;
    public function adjustStock(string $productId, string $locationId, string $qty, string $type, string $tenantId, ?string $variantId = null): void;
}
