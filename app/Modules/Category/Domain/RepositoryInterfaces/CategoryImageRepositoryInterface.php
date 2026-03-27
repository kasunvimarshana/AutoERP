<?php

declare(strict_types=1);

namespace Modules\Category\Domain\RepositoryInterfaces;

use Modules\Category\Domain\Entities\CategoryImage;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface CategoryImageRepositoryInterface extends RepositoryInterface
{
    public function findByUuid(string $uuid): ?CategoryImage;

    public function findByCategory(int $categoryId): ?CategoryImage;

    public function save(CategoryImage $image): CategoryImage;

    public function deleteByCategory(int $categoryId): bool;
}
