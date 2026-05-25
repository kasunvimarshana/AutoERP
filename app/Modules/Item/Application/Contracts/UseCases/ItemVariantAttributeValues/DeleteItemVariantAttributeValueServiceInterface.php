<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ItemVariantAttributeValues;

use Modules\Core\Application\Results\Result;

interface DeleteItemVariantAttributeValueServiceInterface
{
    public function execute(int|string $id): Result;
}
