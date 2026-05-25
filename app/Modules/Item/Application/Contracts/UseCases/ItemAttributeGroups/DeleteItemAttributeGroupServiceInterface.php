<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ItemAttributeGroups;

use Modules\Core\Application\Results\Result;

interface DeleteItemAttributeGroupServiceInterface
{
    public function execute(int|string $id): Result;
}
