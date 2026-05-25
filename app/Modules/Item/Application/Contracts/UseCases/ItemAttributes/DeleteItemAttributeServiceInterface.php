<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ItemAttributes;

use Modules\Core\Application\Results\Result;

interface DeleteItemAttributeServiceInterface
{
    public function execute(int|string $id): Result;
}
