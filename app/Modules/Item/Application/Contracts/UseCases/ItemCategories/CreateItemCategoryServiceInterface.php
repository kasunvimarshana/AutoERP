<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ItemCategories;

use Modules\Core\Application\Results\Result;

interface CreateItemCategoryServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
