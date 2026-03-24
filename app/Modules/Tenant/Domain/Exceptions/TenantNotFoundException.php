<?php

namespace Modules\Tenant\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class TenantNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('Tenant', $id);
    }
}
