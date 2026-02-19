<?php

declare(strict_types=1);

namespace Modules\Accounting\Exceptions;

use Modules\Core\Exceptions\BusinessLogicException;

class InvalidJournalEntryStatusException extends BusinessLogicException
{
    protected $message = 'Invalid journal entry status transition';

    protected $errorCode = 'INVALID_JOURNAL_ENTRY_STATUS';
}
