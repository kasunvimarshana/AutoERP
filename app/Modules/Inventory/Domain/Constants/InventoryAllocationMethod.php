<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Constants;

final class InventoryAllocationMethod
{
    public const FIFO = 'fifo';
    public const FEFO = 'fefo';
    public const PROPORTIONAL = 'proportional';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::FIFO,
            self::FEFO,
            self::PROPORTIONAL,
        ];
    }
}
