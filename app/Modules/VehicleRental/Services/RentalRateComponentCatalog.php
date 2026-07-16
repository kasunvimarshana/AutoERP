<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Modules\VehicleRental\Enums\RentalRateComponentCode;
use Modules\VehicleRental\Enums\RentalRateUnit;

final class RentalRateComponentCatalog
{
    private const GROUP_CORE = 'core';

    private const GROUP_EVENT = 'event';

    /** @return list<array{code: string, unit: string, group: string, required: bool}> */
    public function definitions(): array
    {
        return [
            $this->definition(RentalRateComponentCode::BaseRental, RentalRateUnit::Month, self::GROUP_CORE, true),
            $this->definition(RentalRateComponentCode::ExcessKm, RentalRateUnit::Km, self::GROUP_CORE),
            $this->definition(RentalRateComponentCode::DriverSalary, RentalRateUnit::Month, self::GROUP_CORE),
            $this->definition(RentalRateComponentCode::NormalOvertime, RentalRateUnit::Hour, self::GROUP_CORE),
            $this->definition(RentalRateComponentCode::DoubleOvertime, RentalRateUnit::Hour, self::GROUP_CORE),
            $this->definition(RentalRateComponentCode::TripleOvertime, RentalRateUnit::Hour, self::GROUP_CORE),
            $this->definition(RentalRateComponentCode::NightOut, RentalRateUnit::Count, self::GROUP_CORE),
            $this->definition(RentalRateComponentCode::Parking, RentalRateUnit::Count, self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::Toll, RentalRateUnit::Count, self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::Waiting, RentalRateUnit::Hour, self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::Outstation, RentalRateUnit::Trip, self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::Pass, RentalRateUnit::Count, self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::Fuel, RentalRateUnit::Litre, self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::Damage, RentalRateUnit::Fixed, self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::Repair, RentalRateUnit::Fixed, self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::OtherRecovery, RentalRateUnit::Fixed, self::GROUP_EVENT),
        ];
    }

    /** @return array{code: string, unit: string, group: string, required: bool} */
    private function definition(
        RentalRateComponentCode $code,
        RentalRateUnit $unit,
        string $group,
        bool $required = false,
    ): array {
        return [
            'code' => $code->value,
            'unit' => $unit->value,
            'group' => $group,
            'required' => $required,
        ];
    }
}
