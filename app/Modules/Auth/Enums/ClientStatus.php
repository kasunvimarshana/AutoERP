<?php

declare(strict_types=1);

namespace Modules\Auth\Enums;

enum ClientStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
