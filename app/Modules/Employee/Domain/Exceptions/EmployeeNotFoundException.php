<?php

declare(strict_types=1);

namespace Modules\Employee\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class EmployeeNotFoundException extends NotFoundException
{
    public function __construct(int $id)
    {
        parent::__construct('Employee', $id);
    }
}
