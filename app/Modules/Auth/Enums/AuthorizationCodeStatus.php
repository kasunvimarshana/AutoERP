<?php

declare(strict_types=1);

namespace Modules\Auth\Enums;

enum AuthorizationCodeStatus: string
{
    case ACTIVE = 'active';
    case CONSUMED = 'consumed';
    case REVOKED = 'revoked';
    case EXPIRED = 'expired';
}
