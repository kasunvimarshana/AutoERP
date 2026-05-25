<?php

declare(strict_types=1);

namespace Modules\Sequence\Domain\Constants;

final class SequencePeriodType
{
    public const YEARLY = 'yearly';
    public const MONTHLY = 'monthly';
    public const INFINITE = 'infinite';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::YEARLY,
            self::MONTHLY,
            self::INFINITE,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }

    private function __construct()
    {
    }
}
