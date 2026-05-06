<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Exceptions;

class LeaveRequestConflictException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Leave request conflicts with an existing approved or pending request.');
    }
}
