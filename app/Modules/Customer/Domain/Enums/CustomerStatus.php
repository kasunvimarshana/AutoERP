<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\Enums;

enum CustomerStatus: string
{
    case Active = 'ACTIVE';
    case Inactive = 'INACTIVE';
    case Blocked = 'BLOCKED';
}
