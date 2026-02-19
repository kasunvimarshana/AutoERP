<?php

declare(strict_types=1);

namespace Modules\Workflow\Enums;

enum ConditionType: string
{
    case EQUALS = 'equals';
    case NOT_EQUALS = 'not_equals';
    case GREATER_THAN = 'greater_than';
    case LESS_THAN = 'less_than';
    case CONTAINS = 'contains';
    case IN_ARRAY = 'in_array';
    case REGEX = 'regex';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::EQUALS => 'Equals',
            self::NOT_EQUALS => 'Not Equals',
            self::GREATER_THAN => 'Greater Than',
            self::LESS_THAN => 'Less Than',
            self::CONTAINS => 'Contains',
            self::IN_ARRAY => 'In Array',
            self::REGEX => 'Regex',
            self::CUSTOM => 'Custom',
        };
    }

    public function evaluate(mixed $value, mixed $expected): bool
    {
        return match ($this) {
            self::EQUALS => $value == $expected,
            self::NOT_EQUALS => $value != $expected,
            self::GREATER_THAN => $value > $expected,
            self::LESS_THAN => $value < $expected,
            self::CONTAINS => is_string($value) && str_contains($value, (string) $expected),
            self::IN_ARRAY => is_array($expected) && in_array($value, $expected),
            self::REGEX => is_string($value) && preg_match($expected, $value),
            self::CUSTOM => false,
        };
    }
}
