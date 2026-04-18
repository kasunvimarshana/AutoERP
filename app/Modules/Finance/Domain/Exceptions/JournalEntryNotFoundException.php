<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class JournalEntryNotFoundException extends NotFoundException
{
    public function __construct(int $id)
    {
        parent::__construct('Journal entry', $id);
    }
}
