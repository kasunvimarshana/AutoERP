<?php

declare(strict_types=1);

namespace Modules\Category\Application\Services;

use Modules\Category\Application\Contracts\DeleteCategoryServiceInterface;
use Modules\Category\Domain\Events\CategoryDeleted;
use Modules\Category\Domain\Exceptions\CategoryNotFoundException;
use Modules\Category\Domain\RepositoryInterfaces\CategoryRepositoryInterface;
use Modules\Core\Application\Services\BaseService;

class DeleteCategoryService extends BaseService implements DeleteCategoryServiceInterface
{
    public function __construct(private readonly CategoryRepositoryInterface $categoryRepository)
    {
        parent::__construct($categoryRepository);
    }

    protected function handle(array $data): bool
    {
        $id = $data['id'];
        $category = $this->categoryRepository->find($id);

        if (! $category) {
            throw new CategoryNotFoundException($id);
        }

        $tenantId = $category->getTenantId();
        $deleted = $this->categoryRepository->delete($id);

        if ($deleted) {
            $this->addEvent(new CategoryDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
