<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class AccountNotFoundException extends NotFoundException
{
    public function __construct(int|string $id)
    {
        parent::__construct("Account [{$id}] not found.");
    }
}
