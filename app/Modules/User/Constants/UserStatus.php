<?php

declare(strict_types=1);

namespace Modules\User\Constants;

final class UserStatus
{
    public const INVITED = 'invited';

    public const ACTIVE = 'active';

    public const INACTIVE = 'inactive';

    public const SUSPENDED = 'suspended';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::ACTIVE,
            self::INACTIVE,
            self::SUSPENDED,
        ];
    }

    /** @return list<string> */
    public static function platformOperatorListValues(): array
    {
        return [self::INVITED, self::ACTIVE, self::INACTIVE];
    }

    /** @return list<string> */
    public static function platformOperatorMutableValues(): array
    {
        return [self::ACTIVE, self::INACTIVE];
    }

    private function __construct() {}
}
