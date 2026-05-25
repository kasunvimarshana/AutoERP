<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts\UseCases\PriceListItems;

use Modules\Core\Application\Results\Result;

interface CreatePriceListItemServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}