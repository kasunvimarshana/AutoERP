<?php

declare(strict_types=1);

namespace Modules\Workflow\Exceptions;

use Exception;

class WorkflowException extends Exception
{
    public static function notFound(int $id): self
    {
        return new self("Workflow with ID {$id} not found");
    }

    public static function cannotExecute(string $reason): self
    {
        return new self("Workflow cannot be executed: {$reason}");
    }
}
