<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface ProductRepositoryInterface extends RepositoryInterface
{
    public function findBySku(string $sku, int $tenantId): mixed;

    public function findByBarcode(string $barcode, int $tenantId): mixed;

    public function findByCategory(int $categoryId): Collection;

    public function searchByName(string $query, int $tenantId): Collection;

    public function findByTenant(int $tenantId, array $filters = []): mixed;
}
