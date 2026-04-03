<?php

declare(strict_types=1);

namespace Modules\Taxation\Domain\ValueObjects;

class TaxCalculationMethod
{
    public const INCLUSIVE = 'inclusive';
    public const EXCLUSIVE = 'exclusive';
    public const COMPOUND = 'compound';

    public static function values(): array
    {
        return [
            self::INCLUSIVE,
            self::EXCLUSIVE,
            self::COMPOUND,
        ];
    }
}
