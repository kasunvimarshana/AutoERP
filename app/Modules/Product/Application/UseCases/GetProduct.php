<?php

declare(strict_types=1);

namespace Modules\Product\Application\UseCases;

use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class GetProduct
{
    public function __construct(private readonly ProductRepositoryInterface $productRepo) {}

    public function execute(int $id): ?Product
    {
        return $this->productRepo->find($id);
    }

    public function findBySku(int $tenantId, string $sku): ?Product
    {
        return $this->productRepo->findBySku($tenantId, $sku);
    }
}
