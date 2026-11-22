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

enum CredentialStatus: string
{
    case ACTIVE = 'active';
    case REVOKED = 'revoked';
}
