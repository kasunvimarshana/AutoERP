<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class JournalEntryAlreadyPostedException extends DomainException
{
    public function __construct(int $id)
    {
        parent::__construct("Journal entry [{$id}] is already posted and cannot be modified.");
    }
}
