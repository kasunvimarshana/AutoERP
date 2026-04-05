<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class JournalEntryImbalancedException extends DomainException
{
    public function __construct(float $totalDebit, float $totalCredit)
    {
        parent::__construct(
            "Journal entry is imbalanced: total debits ({$totalDebit}) do not equal total credits ({$totalCredit})."
        );
    }
}
