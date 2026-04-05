<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Product\Application\Contracts\ProductCategoryServiceInterface;
use Modules\Product\Domain\Entities\ProductCategory;
use Modules\Product\Domain\RepositoryInterfaces\ProductCategoryRepositoryInterface;

class ProductCategoryService implements ProductCategoryServiceInterface
{
    public function __construct(private readonly ProductCategoryRepositoryInterface $repo) {}

    public function findById(int $id): ProductCategory
    {
        $c = $this->repo->findById($id);
        if (!$c) throw new NotFoundException("ProductCategory", $id);
        return $c;
    }

    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->repo->findByTenant($tenantId, $perPage, $page);
    }

    public function getTree(int $tenantId): array
    {
        return $this->repo->buildTree($tenantId);
    }

    public function create(array $data): ProductCategory
    {
        return $this->repo->create($data);
    }

    public function update(int $id, array $data): ProductCategory
    {
        $c = $this->repo->update($id, $data);
        if (!$c) throw new NotFoundException("ProductCategory", $id);
        return $c;
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }
}
