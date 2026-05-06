<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class LeaveRequestConflictException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Leave request conflicts with an existing approved or pending request.', 409);
    }
}
