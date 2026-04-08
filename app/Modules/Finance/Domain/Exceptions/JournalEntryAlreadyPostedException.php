<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

final class JournalEntryAlreadyPostedException extends DomainException
{
    public function __construct(mixed $id = null)
    {
        $message = $id !== null
            ? "Journal entry '{$id}' has already been posted and cannot be modified."
            : 'Journal entry has already been posted and cannot be modified.';

        parent::__construct($message, 422);
    }
}
