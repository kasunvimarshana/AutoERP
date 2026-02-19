<?php

declare(strict_types=1);

namespace Modules\Workflow\Exceptions;

use Exception;

class WorkflowBuilderException extends Exception
{
    public static function validationFailed(array $errors): self
    {
        return new self('Workflow validation failed: '.implode(', ', $errors));
    }
}
