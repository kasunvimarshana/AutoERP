<?php

declare(strict_types=1);

namespace Modules\Workflow\Enums;

enum StepType: string
{
    case START = 'start';
    case ACTION = 'action';
    case APPROVAL = 'approval';
    case CONDITION = 'condition';
    case PARALLEL = 'parallel';
    case END = 'end';

    public function label(): string
    {
        return match ($this) {
            self::START => 'Start',
            self::ACTION => 'Action',
            self::APPROVAL => 'Approval',
            self::CONDITION => 'Condition',
            self::PARALLEL => 'Parallel',
            self::END => 'End',
        };
    }

    public function requiresInput(): bool
    {
        return in_array($this, [self::APPROVAL, self::ACTION]);
    }

    public function allowsMultipleOutputs(): bool
    {
        return in_array($this, [self::CONDITION, self::PARALLEL]);
    }
}
