<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts\UseCases\PriceListItems;

use Modules\Core\Application\Results\Result;

interface DeletePriceListItemServiceInterface
{
    public function execute(int|string $id): Result;
}