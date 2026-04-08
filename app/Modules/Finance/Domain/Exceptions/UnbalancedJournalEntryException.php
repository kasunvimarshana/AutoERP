<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

final class UnbalancedJournalEntryException extends DomainException
{
    public function __construct(float $totalDebit, float $totalCredit)
    {
        parent::__construct(
            sprintf(
                'Journal entry is unbalanced: total debit (%.6f) does not equal total credit (%.6f).',
                $totalDebit,
                $totalCredit
            ),
            422
        );
    }
}
