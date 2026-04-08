<?php

declare(strict_types=1);

namespace Modules\Financial\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

/**
 * Thrown when journal entry debit total does not equal credit total.
 */
class InvalidJournalEntryException extends DomainException
{
    public function __construct(float $totalDebit, float $totalCredit)
    {
        parent::__construct(
            sprintf(
                'Journal entry is not balanced: total debit %.4f does not equal total credit %.4f.',
                $totalDebit,
                $totalCredit,
            ),
            422,
        );
    }
}
