<?php

declare(strict_types=1);

namespace Modules\Auth\Enums;

enum TokenStatus: string
{
    case ACTIVE = 'active';
    case ROTATED = 'rotated';
    case REVOKED = 'revoked';
    case EXPIRED = 'expired';
}
