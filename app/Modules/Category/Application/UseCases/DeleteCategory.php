<?php

declare(strict_types=1);

namespace Modules\Category\Application\UseCases;

use Modules\Category\Domain\Events\CategoryDeleted;
use Modules\Category\Domain\Exceptions\CategoryNotFoundException;
use Modules\Category\Domain\RepositoryInterfaces\CategoryRepositoryInterface;

class DeleteCategory
{
    public function __construct(private readonly CategoryRepositoryInterface $categoryRepo) {}

    public function execute(int $id): bool
    {
        $category = $this->categoryRepo->find($id);
        if (! $category) {
            throw new CategoryNotFoundException($id);
        }

        $tenantId = $category->getTenantId();
        $deleted = $this->categoryRepo->delete($id);

        if ($deleted) {
            event(new CategoryDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
