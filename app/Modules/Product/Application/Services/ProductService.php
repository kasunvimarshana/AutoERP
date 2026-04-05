<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Application\Contracts\ProductServiceInterface;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\Events\ProductCreated;
use Modules\Product\Domain\Exceptions\ProductNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class ProductService implements ProductServiceInterface
{
    public function __construct(private readonly ProductRepositoryInterface $repo) {}

    public function findById(int $id): Product
    {
        $p = $this->repo->findById($id);
        if (!$p) throw new ProductNotFoundException($id);
        return $p;
    }

    public function findByTenant(int $tenantId, array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->repo->findByTenant($tenantId, $filters, $perPage, $page);
    }

    public function create(array $data): Product
    {
        $p = $this->repo->create($data);
        event(new ProductCreated($p->getTenantId(), $p->getId()));
        return $p;
    }

    public function update(int $id, array $data): Product
    {
        $p = $this->repo->update($id, $data);
        if (!$p) throw new ProductNotFoundException($id);
        return $p;
    }

    public function delete(int $id): bool
    {
        $p = $this->repo->findById($id);
        if (!$p) throw new ProductNotFoundException($id);
        return $this->repo->delete($id);
    }
}
