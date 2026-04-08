<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

final class InvalidCredentialsException extends DomainException
{
    public function __construct(string $message = 'Invalid credentials provided.')
    {
        parent::__construct($message);
    }
}
