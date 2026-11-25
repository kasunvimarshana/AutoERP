<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Enums;

enum CredentialStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Lost = 'lost';
    case Revoked = 'revoked';
}
