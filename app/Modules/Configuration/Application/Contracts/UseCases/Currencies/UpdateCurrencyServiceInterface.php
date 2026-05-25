<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts\UseCases\Currencies;

use Modules\Core\Application\Results\Result;

interface UpdateCurrencyServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}
