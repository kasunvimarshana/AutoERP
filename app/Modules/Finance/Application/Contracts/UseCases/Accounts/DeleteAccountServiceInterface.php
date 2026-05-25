<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\Accounts;

use Modules\Core\Application\Results\Result;

interface DeleteAccountServiceInterface
{
    public function execute(int|string $id): Result;
}
