<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class CustomerContactNotFoundException extends NotFoundException
{
    public function __construct(int $id)
    {
        parent::__construct('CustomerContact', $id);
    }
}
