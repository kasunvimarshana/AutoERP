<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class CustomerNotFoundException extends NotFoundException
{
    public function __construct(mixed $id)
    {
        parent::__construct('Customer', $id);
    }
}
