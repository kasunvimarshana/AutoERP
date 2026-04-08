<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

use Modules\Product\Application\DTOs\CategoryData;

interface CategoryServiceInterface
{
    public function create(CategoryData $dto): mixed;
    public function getTree(int $tenantId): array;
    public function move(int $id, ?int $parentId): mixed;
}
