<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\Accounts;

use Modules\Core\Application\Results\Result;

interface CreateAccountServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
