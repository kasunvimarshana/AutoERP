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

enum BiometricPunchState: string
{
    case CheckIn = 'check_in';
    case CheckOut = 'check_out';
    case BreakOut = 'break_out';
    case BreakIn = 'break_in';
    case OvertimeIn = 'overtime_in';
    case OvertimeOut = 'overtime_out';
    case Auto = 'auto';

    public function toPunchType(): ?PunchType
    {
        return match ($this) {
            self::CheckIn, self::OvertimeIn => PunchType::In,
            self::CheckOut, self::OvertimeOut => PunchType::Out,
            self::BreakOut => PunchType::BreakOut,
            self::BreakIn => PunchType::BreakIn,
            self::Auto => null,
        };
    }
}
