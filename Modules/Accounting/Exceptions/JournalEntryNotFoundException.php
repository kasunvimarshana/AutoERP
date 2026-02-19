<?php

declare(strict_types=1);

namespace Modules\Accounting\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

class JournalEntryNotFoundException extends NotFoundException
{
    protected $message = 'Journal entry not found';

    protected $errorCode = 'JOURNAL_ENTRY_NOT_FOUND';
}
