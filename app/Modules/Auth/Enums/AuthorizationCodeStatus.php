<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Auth\Enums;

enum AuthorizationCodeStatus: string
{
    case ACTIVE = 'active';
    case CONSUMED = 'consumed';
    case REVOKED = 'revoked';
    case EXPIRED = 'expired';
}
