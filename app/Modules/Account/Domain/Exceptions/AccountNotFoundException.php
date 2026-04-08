<?php

declare(strict_types=1);

namespace Modules\Account\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class AccountNotFoundException extends NotFoundException
{
    public function __construct(mixed $id)
    {
        parent::__construct('Account', $id);
    }
}
