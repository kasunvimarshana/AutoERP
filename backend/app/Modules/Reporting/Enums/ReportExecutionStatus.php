<?php

namespace App\Modules\Reporting\Enums;

enum ReportExecutionStatus: string
{
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::RUNNING => 'Running',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
        };
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED]);
    }
}
