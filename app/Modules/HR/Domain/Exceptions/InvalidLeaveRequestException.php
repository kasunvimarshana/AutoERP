<?php
declare(strict_types=1);
namespace Modules\HR\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class InvalidLeaveRequestException extends DomainException
{
    public function __construct(string $message = 'Invalid leave request operation.')
    {
        parent::__construct($message);
    }
}
