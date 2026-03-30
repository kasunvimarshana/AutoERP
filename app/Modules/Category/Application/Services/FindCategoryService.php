<?php

declare(strict_types=1);

namespace Modules\Category\Application\Services;

use Illuminate\Support\Collection;
use Modules\Category\Application\Contracts\FindCategoryServiceInterface;
use Modules\Category\Domain\Entities\Category;
use Modules\Category\Domain\RepositoryInterfaces\CategoryRepositoryInterface;

/**
 * Delegates category read queries (flat find, tree, roots) to the repository.
 *
 * Keeping these queries here rather than in the controller upholds DIP:
 * controllers depend on this service interface, not on the repository.
 */
class FindCategoryService implements FindCategoryServiceInterface
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {}

    public function find(int $id): ?Category
    {
        return $this->categoryRepository->find($id);
    }

    public function getTree(int $tenantId, ?int $rootId = null): Collection
    {
        return $this->categoryRepository->getTree($tenantId, $rootId);
    }

    public function findRoots(int $tenantId): Collection
    {
        return $this->categoryRepository->findRoots($tenantId);
    }
}
