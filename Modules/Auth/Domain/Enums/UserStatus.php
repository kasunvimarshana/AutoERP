<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';
    case Banned = 'banned';
}
