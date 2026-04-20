<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class CustomerAddressNotFoundException extends NotFoundException
{
    public function __construct(int $id)
    {
        parent::__construct('CustomerAddress', $id);
    }
}
