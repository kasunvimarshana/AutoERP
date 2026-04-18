<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class UnbalancedJournalEntryException extends DomainException
{
    public function __construct(float $debits, float $credits)
    {
        parent::__construct(sprintf(
            'Journal entry is not balanced. Debit total %.4f does not match credit total %.4f.',
            $debits,
            $credits
        ));
    }
}
