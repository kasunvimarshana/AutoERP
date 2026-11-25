<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Tenant\Constants;

final class TenantSubscriptionOperation
{
    public const ASSIGN = 'assign';

    public const RENEW = 'renew';

    public const EXTEND = 'extend';

    public const CORRECT = 'correct';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::ASSIGN, self::RENEW, self::EXTEND, self::CORRECT];
    }

    private function __construct() {}
}
