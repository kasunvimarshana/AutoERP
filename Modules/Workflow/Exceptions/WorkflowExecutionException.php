<?php

declare(strict_types=1);

namespace Modules\Workflow\Exceptions;

use Exception;

class WorkflowExecutionException extends Exception
{
    public static function stepFailed(string $stepName, string $reason): self
    {
        return new self("Step '{$stepName}' failed: {$reason}");
    }
}
