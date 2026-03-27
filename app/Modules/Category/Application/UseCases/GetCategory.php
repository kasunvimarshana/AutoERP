<?php

declare(strict_types=1);

namespace Modules\Category\Application\UseCases;

use Modules\Category\Domain\Entities\Category;
use Modules\Category\Domain\RepositoryInterfaces\CategoryRepositoryInterface;

class GetCategory
{
    public function __construct(private readonly CategoryRepositoryInterface $categoryRepo) {}

    public function execute(int $id): ?Category
    {
        return $this->categoryRepo->find($id);
    }
}
