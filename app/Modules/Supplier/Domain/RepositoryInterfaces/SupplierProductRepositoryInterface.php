<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Supplier\Domain\Entities\SupplierProduct;

interface SupplierProductRepositoryInterface extends RepositoryInterface
{
    public function save(SupplierProduct $supplierProduct): SupplierProduct;

    public function clearPreferredByProductVariant(int $tenantId, int $productId, ?int $variantId, ?int $excludeId = null): void;

    public function find(int|string $id, array $columns = ['*']): ?SupplierProduct;
}
