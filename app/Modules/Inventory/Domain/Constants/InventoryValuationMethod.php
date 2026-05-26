<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Constants;

final class InventoryValuationMethod
{
    public const FIFO = 'fifo';
    public const LIFO = 'lifo';
    public const WEIGHTED_AVERAGE = 'weighted_average';
    public const STANDARD = 'standard';
    public const SPECIFIC = 'specific';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::FIFO,
            self::LIFO,
            self::WEIGHTED_AVERAGE,
            self::STANDARD,
            self::SPECIFIC,
        ];
    }
}
