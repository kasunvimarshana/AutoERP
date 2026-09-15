<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum UsageChargeComponent: string
{
    case NormalOvertime = 'normal_ot';
    case DoubleOvertime = 'double_ot';
    case TripleOvertime = 'triple_ot';
    case NightOut = 'night_out';

    public const STORAGE_LENGTH = 40;

    public const MINUTES_PER_HOUR = 60;

    public const SINGLE_NIGHT = 1;

    public function observation(): string
    {
        return $this === self::NightOut ? 'night_outs' : $this->value.'_minutes';
    }

    public function rate(): string
    {
        return $this->value.'_rate';
    }

    public function denominator(): int
    {
        return $this === self::NightOut ? self::SINGLE_NIGHT : self::MINUTES_PER_HOUR;
    }

    public function label(): string
    {
        return match ($this) {
            self::NormalOvertime => 'Normal OT', self::DoubleOvertime => 'Double OT',
            self::TripleOvertime => 'Triple OT', self::NightOut => 'Night-outs',
        };
    }
}
