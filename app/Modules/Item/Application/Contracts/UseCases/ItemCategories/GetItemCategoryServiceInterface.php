<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ItemCategories;

use Modules\Core\Application\Results\Result;

interface GetItemCategoryServiceInterface
{
    public function execute(int|string $id): Result;
}
