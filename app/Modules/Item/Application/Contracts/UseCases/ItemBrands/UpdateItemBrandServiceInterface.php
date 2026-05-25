<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ItemBrands;

use Modules\Core\Application\Results\Result;

interface UpdateItemBrandServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}
