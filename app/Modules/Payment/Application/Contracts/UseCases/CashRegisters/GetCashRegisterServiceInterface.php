<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\CashRegisters;

use Modules\Core\Application\Results\Result;

interface GetCashRegisterServiceInterface
{
    public function execute(int|string $id): Result;
}