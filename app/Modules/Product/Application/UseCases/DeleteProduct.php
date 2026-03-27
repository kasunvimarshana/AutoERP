<?php

declare(strict_types=1);

namespace Modules\Product\Application\UseCases;

use Modules\Product\Domain\Events\ProductDeleted;
use Modules\Product\Domain\Exceptions\ProductNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class DeleteProduct
{
    public function __construct(private readonly ProductRepositoryInterface $productRepo) {}

    public function execute(int $id): bool
    {
        $product = $this->productRepo->find($id);

        if (! $product) {
            throw new ProductNotFoundException($id);
        }

        $tenantId = $product->getTenantId();
        $deleted = $this->productRepo->delete($id);

        if ($deleted) {
            event(new ProductDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
