<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts\UseCases\Currencies;

use Modules\Core\Application\Results\Result;

interface GetCurrencyServiceInterface
{
    public function execute(int|string $id): Result;
}
