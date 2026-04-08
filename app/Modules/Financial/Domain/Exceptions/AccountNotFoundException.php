<?php

declare(strict_types=1);

namespace Modules\Financial\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class AccountNotFoundException extends NotFoundException
{
    public function __construct(string $id)
    {
        parent::__construct('Account', $id);
    }
}
