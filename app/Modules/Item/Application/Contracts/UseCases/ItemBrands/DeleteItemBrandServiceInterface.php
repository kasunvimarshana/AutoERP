<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ItemBrands;

use Modules\Core\Application\Results\Result;

interface DeleteItemBrandServiceInterface
{
    public function execute(int|string $id): Result;
}
