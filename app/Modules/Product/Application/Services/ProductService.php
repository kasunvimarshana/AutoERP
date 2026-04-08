<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\ProductServiceInterface;
use Modules\Product\Domain\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Product\Domain\Events\ProductCreated;
use Modules\Product\Domain\Events\ProductUpdated;
use Modules\Product\Domain\Exceptions\DuplicateSkuException;
use Modules\Product\Domain\Exceptions\ProductNotFoundException;

class ProductService extends BaseService implements ProductServiceInterface
{
    public function __construct(ProductRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Default execute handler — delegates to createProduct.
     */
    protected function handle(array $data): mixed
    {
        return $this->createProduct($data);
    }

    /**
     * Create a new product, enforcing SKU uniqueness.
     */
    public function createProduct(array $data): mixed
    {
        return DB::transaction(function () use ($data) {
            $sku = $data['sku'] ?? '';

            /** @var \Modules\Product\Domain\Contracts\Repositories\ProductRepositoryInterface $repo */
            $repo = $this->repository;
            if ($repo->findBySku($sku) !== null) {
                throw new DuplicateSkuException($sku);
            }

            $product = $this->repository->create($data);
            $this->addEvent(new ProductCreated((int) ($product->tenant_id ?? 0), $product->id));
            $this->dispatchEvents();

            return $product;
        });
    }

    /**
     * Update an existing product.
     */
    public function updateProduct(string $id, array $data): mixed
    {
        return DB::transaction(function () use ($id, $data) {
            $product = $this->repository->find($id);
            if (! $product) {
                throw new ProductNotFoundException($id);
            }

            $updated = $this->repository->update($id, $data);
            $this->addEvent(new ProductUpdated((int) ($product->tenant_id ?? 0), $id));
            $this->dispatchEvents();

            return $updated;
        });
    }
}
