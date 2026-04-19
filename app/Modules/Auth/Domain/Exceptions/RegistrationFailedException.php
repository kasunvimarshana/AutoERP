<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class RegistrationFailedException extends DomainException
{
    public function __construct(string $message = 'User registration failed', int $code = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
