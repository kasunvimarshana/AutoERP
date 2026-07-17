<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Modules\VehicleRental\Enums\RentalRateComponentCode;
use Modules\VehicleRental\Enums\RentalRateUnit;

final class RentalRateComponentCatalog
{
    private const GROUP_CORE = 'core';

    private const GROUP_EVENT = 'event';

    /**
     * @return list<array{
     *     code: string,
     *     unit: string,
     *     supported_units: list<string>,
     *     group: string,
     *     required: bool
     * }>
     */
    public function definitions(): array
    {
        return [
            $this->definition(
                RentalRateComponentCode::BaseRental,
                RentalRateUnit::Month,
                [
                    RentalRateUnit::Fixed,
                    RentalRateUnit::Month,
                    RentalRateUnit::Week,
                    RentalRateUnit::Day,
                    RentalRateUnit::Hour,
                    RentalRateUnit::Trip,
                    RentalRateUnit::Count,
                ],
                self::GROUP_CORE,
                true,
            ),
            $this->definition(RentalRateComponentCode::ExcessKm, RentalRateUnit::Kilometre, [RentalRateUnit::Kilometre], self::GROUP_CORE),
            $this->definition(
                RentalRateComponentCode::DriverSalary,
                RentalRateUnit::Month,
                [
                    RentalRateUnit::Fixed,
                    RentalRateUnit::Month,
                    RentalRateUnit::Week,
                    RentalRateUnit::Day,
                    RentalRateUnit::Hour,
                    RentalRateUnit::Minute,
                    RentalRateUnit::Trip,
                    RentalRateUnit::Count,
                ],
                self::GROUP_CORE,
            ),
            $this->definition(RentalRateComponentCode::NormalOvertime, RentalRateUnit::Hour, [RentalRateUnit::Hour, RentalRateUnit::Minute], self::GROUP_CORE),
            $this->definition(RentalRateComponentCode::DoubleOvertime, RentalRateUnit::Hour, [RentalRateUnit::Hour, RentalRateUnit::Minute], self::GROUP_CORE),
            $this->definition(RentalRateComponentCode::TripleOvertime, RentalRateUnit::Hour, [RentalRateUnit::Hour, RentalRateUnit::Minute], self::GROUP_CORE),
            $this->definition(RentalRateComponentCode::NightOut, RentalRateUnit::Count, [RentalRateUnit::Count], self::GROUP_CORE),
            $this->definition(RentalRateComponentCode::Parking, RentalRateUnit::Count, [RentalRateUnit::Count], self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::Toll, RentalRateUnit::Count, [RentalRateUnit::Count], self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::Waiting, RentalRateUnit::Hour, [RentalRateUnit::Hour], self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::Outstation, RentalRateUnit::Trip, [RentalRateUnit::Trip], self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::Pass, RentalRateUnit::Count, [RentalRateUnit::Count], self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::Fuel, RentalRateUnit::Litre, [RentalRateUnit::Litre], self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::Damage, RentalRateUnit::Fixed, [RentalRateUnit::Fixed], self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::Repair, RentalRateUnit::Fixed, [RentalRateUnit::Fixed], self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::OtherRecovery, RentalRateUnit::Fixed, [RentalRateUnit::Fixed], self::GROUP_EVENT),
        ];
    }

    /**
     * @param list<RentalRateUnit> $supportedUnits
     * @return array{code: string, unit: string, supported_units: list<string>, group: string, required: bool}
     */
    private function definition(
        RentalRateComponentCode $code,
        RentalRateUnit $unit,
        array $supportedUnits,
        string $group,
        bool $required = false,
    ): array {
        return [
            'code' => $code->value,
            'unit' => $unit->value,
            'supported_units' => array_map(
                static fn (RentalRateUnit $supportedUnit): string => $supportedUnit->value,
                $supportedUnits,
            ),
            'group' => $group,
            'required' => $required,
        ];
    }
}
