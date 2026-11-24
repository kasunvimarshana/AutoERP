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

enum BiometricVerifyMethod: string
{
    case Fingerprint = 'fingerprint';
    case Face = 'face';
    case Card = 'card';
    case Password = 'password';
    case Iris = 'iris';
    case Unknown = 'unknown';
}
