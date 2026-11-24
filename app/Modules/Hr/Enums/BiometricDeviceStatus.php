<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Enums;

enum BiometricDeviceStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Offline = 'offline';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active / Online',
            self::Inactive => 'Inactive / Disabled',
            self::Offline => 'Offline / Unreachable',
            self::Error => 'Error / Communication Failure',
        };
    }
}
