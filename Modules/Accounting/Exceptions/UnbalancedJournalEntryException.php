<?php

declare(strict_types=1);

namespace Modules\Accounting\Exceptions;

use Modules\Core\Exceptions\BusinessLogicException;

class UnbalancedJournalEntryException extends BusinessLogicException
{
    protected $message = 'Journal entry debits and credits must be equal';

    protected $errorCode = 'UNBALANCED_JOURNAL_ENTRY';
}
