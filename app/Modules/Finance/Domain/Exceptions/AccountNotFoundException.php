<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

final class AccountNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('Account', $id);
    }
}
