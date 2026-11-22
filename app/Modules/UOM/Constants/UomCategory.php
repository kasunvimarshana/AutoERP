<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\UOM\Constants;

final class UomCategory
{
    public const QUANTITY = 'quantity';

    public const WEIGHT = 'weight';

    public const VOLUME = 'volume';

    public const LENGTH = 'length';

    public const AREA = 'area';

    public const TIME = 'time';

    public const SERVICE = 'service';

    public const OTHER = 'other';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::QUANTITY,
            self::WEIGHT,
            self::VOLUME,
            self::LENGTH,
            self::AREA,
            self::TIME,
            self::SERVICE,
            self::OTHER,
        ];
    }
}
