<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class JournalEntryNotFoundException extends NotFoundException
{
    public function __construct(int|string $id)
    {
        parent::__construct("Journal entry [{$id}] not found.");
    }
}
