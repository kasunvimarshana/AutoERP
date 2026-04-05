<?php
declare(strict_types=1);
namespace Modules\User\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class InvalidCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Invalid credentials.');
    }
}
