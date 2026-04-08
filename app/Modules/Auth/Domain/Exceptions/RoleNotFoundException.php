<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

final class RoleNotFoundException extends DomainException
{
    public function __construct(string $identifier)
    {
        parent::__construct("Role not found: {$identifier}");
    }
}
