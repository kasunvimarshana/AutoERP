<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Contracts;

use Modules\Product\Domain\Entities\Product;

interface ProductRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?Product;

    public function findBySku(string $sku, int $tenantId): ?Product;

    public function findByBarcode(string $barcode, int $tenantId): ?Product;

    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array;

    public function save(Product $product): Product;

    public function delete(int $id, int $tenantId): void;
}
