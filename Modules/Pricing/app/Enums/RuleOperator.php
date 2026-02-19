<?php

declare(strict_types=1);

namespace Modules\Pricing\Enums;

/**
 * Rule Operator Enum
 *
 * Defines comparison operators for rule conditions
 */
enum RuleOperator: string
{
    case EQUALS = 'equals';
    case NOT_EQUALS = 'not_equals';
    case GREATER_THAN = 'greater_than';
    case GREATER_THAN_OR_EQUAL = 'greater_than_or_equal';
    case LESS_THAN = 'less_than';
    case LESS_THAN_OR_EQUAL = 'less_than_or_equal';
    case IN = 'in';
    case NOT_IN = 'not_in';
    case BETWEEN = 'between';
    case CONTAINS = 'contains';

    /**
     * Get all enum values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::EQUALS => 'Equals',
            self::NOT_EQUALS => 'Not Equals',
            self::GREATER_THAN => 'Greater Than',
            self::GREATER_THAN_OR_EQUAL => 'Greater Than or Equal',
            self::LESS_THAN => 'Less Than',
            self::LESS_THAN_OR_EQUAL => 'Less Than or Equal',
            self::IN => 'In',
            self::NOT_IN => 'Not In',
            self::BETWEEN => 'Between',
            self::CONTAINS => 'Contains',
        };
    }

    /**
     * Evaluate the operator
     *
     * @param  mixed  $left
     * @param  mixed  $right
     */
    public function evaluate($left, $right): bool
    {
        return match ($this) {
            self::EQUALS => $left == $right,
            self::NOT_EQUALS => $left != $right,
            self::GREATER_THAN => $left > $right,
            self::GREATER_THAN_OR_EQUAL => $left >= $right,
            self::LESS_THAN => $left < $right,
            self::LESS_THAN_OR_EQUAL => $left <= $right,
            self::IN => is_array($right) && in_array($left, $right),
            self::NOT_IN => is_array($right) && ! in_array($left, $right),
            self::BETWEEN => is_array($right) && count($right) >= 2 && $left >= $right[0] && $left <= $right[1],
            self::CONTAINS => is_string($left) && is_string($right) && str_contains($left, $right),
        };
    }
}
