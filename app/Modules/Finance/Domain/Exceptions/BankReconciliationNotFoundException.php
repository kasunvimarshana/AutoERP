<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class BankReconciliationNotFoundException extends NotFoundException
{
    public function __construct(int|string $id)
    {
        parent::__construct('BankReconciliation', $id);
    }
}
