<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class LeaveTypeNotFoundException extends NotFoundException
{
    public function __construct(int $id)
    {
        parent::__construct('LeaveType', $id);
    }
}
