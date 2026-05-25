<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ItemVariantAttributeValues;

use Modules\Core\Application\Results\Result;

interface ListItemVariantAttributeValuesServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}
