<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts\UseCases\PriceLists;

use Modules\Core\Application\Results\Result;

interface DeletePriceListServiceInterface
{
    public function execute(int|string $id): Result;
}