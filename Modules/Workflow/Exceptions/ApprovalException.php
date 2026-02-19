<?php

declare(strict_types=1);

namespace Modules\Workflow\Exceptions;

use Exception;

class ApprovalException extends Exception
{
    public static function notAuthorized(): self
    {
        return new self('User not authorized for this approval');
    }

    public static function alreadyFinalized(): self
    {
        return new self('Approval already finalized');
    }
}
