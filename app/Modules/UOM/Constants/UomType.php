<?php

declare(strict_types=1);

namespace Modules\UOM\Constants;

final class UomType
{
    public const UNIT = 'UNIT';

    public const MASS = 'MASS';

    public const VOLUME = 'VOLUME';

    public const LENGTH = 'LENGTH';

    public const AREA = 'AREA';

    public const TIME = 'TIME';

    public const DISTANCE = 'DISTANCE';

    public const OTHER = 'OTHER';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::UNIT,
            self::MASS,
            self::VOLUME,
            self::LENGTH,
            self::AREA,
            self::TIME,
            self::DISTANCE,
            self::OTHER,
        ];
    }
}
