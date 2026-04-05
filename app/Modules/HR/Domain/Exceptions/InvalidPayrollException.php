<?php
declare(strict_types=1);
namespace Modules\HR\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class InvalidPayrollException extends DomainException
{
    public function __construct(string $message = 'Invalid payroll operation.')
    {
        parent::__construct($message);
    }
}
