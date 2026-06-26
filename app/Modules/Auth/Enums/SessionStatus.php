<?php

declare(strict_types=1);

namespace Modules\Auth\Enums;

enum SessionStatus: string
{
    case ACTIVE = 'active';
    case REVOKED = 'revoked';
    case EXPIRED = 'expired';
}
