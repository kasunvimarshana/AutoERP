<?php

declare(strict_types=1);

namespace Modules\Transaction\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class JournalEntryNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('JournalEntry', $id);
    }
}
