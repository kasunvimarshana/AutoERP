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

enum PunchSource: string
{
    case Web = 'web';
    case Mobile = 'mobile';
    case BiometricDevice = 'biometric_device';
    case ManualEntry = 'manual_entry';
    case SystemResolved = 'system_resolved';
}
