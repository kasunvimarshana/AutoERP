<?php

declare(strict_types=1);

namespace Modules\HR\Domain\ValueObjects;

enum BiometricDeviceStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case MAINTENANCE = 'maintenance';
    case OFFLINE = 'offline';
}
