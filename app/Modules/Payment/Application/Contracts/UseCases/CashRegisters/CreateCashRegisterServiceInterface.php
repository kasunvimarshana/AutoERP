<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\CashRegisters;

use Modules\Core\Application\Results\Result;

interface CreateCashRegisterServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}