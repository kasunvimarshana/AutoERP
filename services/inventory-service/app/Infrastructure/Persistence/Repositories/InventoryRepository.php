<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Infrastructure\Persistence\Models\InventoryItem;

/**
 * InventoryRepository
 */
class InventoryRepository extends BaseRepository
{
    public function __construct(InventoryItem $model)
    {
        parent::__construct($model);
    }

    public function findByProductAndTenant(string $productId, string $tenantId): ?InventoryItem
    {
        /** @var InventoryItem|null */
        return $this->findBy(['product_id' => $productId, 'tenant_id' => $tenantId]);
    }
}
