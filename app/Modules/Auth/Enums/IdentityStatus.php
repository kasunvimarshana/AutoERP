<?php

declare(strict_types=1);

namespace Modules\Auth\Enums;

enum IdentityStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case REVOKED = 'revoked';
}
