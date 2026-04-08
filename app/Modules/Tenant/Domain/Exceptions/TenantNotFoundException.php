<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

final class TenantNotFoundException extends NotFoundException
{
    public function __construct(mixed $identifier = null)
    {
        parent::__construct('Tenant', $identifier);
    }
}
