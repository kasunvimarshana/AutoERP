<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ItemVariantAttributes;

use Modules\Core\Application\Results\Result;

interface GetItemVariantAttributeServiceInterface
{
    public function execute(int|string $id): Result;
}
