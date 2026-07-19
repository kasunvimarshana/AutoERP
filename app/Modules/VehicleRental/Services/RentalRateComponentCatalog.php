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
            $this->definition(RentalRateComponentCode::BaseRental, self::GROUP_CORE, true),
            $this->definition(RentalRateComponentCode::ExcessKm, self::GROUP_CORE),
            $this->definition(RentalRateComponentCode::DriverSalary, self::GROUP_CORE),
            $this->definition(RentalRateComponentCode::NormalOvertime, self::GROUP_CORE),
            $this->definition(RentalRateComponentCode::DoubleOvertime, self::GROUP_CORE),
            $this->definition(RentalRateComponentCode::TripleOvertime, self::GROUP_CORE),
            $this->definition(RentalRateComponentCode::NightOut, self::GROUP_CORE),
            $this->definition(RentalRateComponentCode::Parking, self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::Toll, self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::Waiting, self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::Outstation, self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::Pass, self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::Fuel, self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::Damage, self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::Repair, self::GROUP_EVENT),
            $this->definition(RentalRateComponentCode::OtherRecovery, self::GROUP_EVENT),
        ];
    }

    /** @return list<RentalRateUnit> */
    public static function supportedUnits(RentalRateComponentCode $code): array
    {
        return match ($code) {
            RentalRateComponentCode::BaseRental => [
                RentalRateUnit::Fixed,
                RentalRateUnit::Month,
                RentalRateUnit::Week,
                RentalRateUnit::Day,
                RentalRateUnit::Hour,
                RentalRateUnit::Trip,
                RentalRateUnit::Count,
            ],
            RentalRateComponentCode::DriverSalary => [
                RentalRateUnit::Fixed,
                RentalRateUnit::Month,
                RentalRateUnit::Week,
                RentalRateUnit::Day,
                RentalRateUnit::Hour,
                RentalRateUnit::Minute,
                RentalRateUnit::Trip,
                RentalRateUnit::Count,
            ],
            RentalRateComponentCode::NormalOvertime,
            RentalRateComponentCode::DoubleOvertime,
            RentalRateComponentCode::TripleOvertime => [
                RentalRateUnit::Hour,
                RentalRateUnit::Minute,
            ],
            RentalRateComponentCode::ExcessKm => [RentalRateUnit::Kilometre],
            RentalRateComponentCode::NightOut,
            RentalRateComponentCode::Parking,
            RentalRateComponentCode::Toll,
            RentalRateComponentCode::Pass => [RentalRateUnit::Count],
            RentalRateComponentCode::Waiting => [RentalRateUnit::Hour],
            RentalRateComponentCode::Outstation => [RentalRateUnit::Trip],
            RentalRateComponentCode::Fuel => [RentalRateUnit::Litre],
            RentalRateComponentCode::Damage,
            RentalRateComponentCode::Repair,
            RentalRateComponentCode::OtherRecovery => [RentalRateUnit::Fixed],
            RentalRateComponentCode::WithholdingTax => [],
        };
    }

    public static function defaultUnit(RentalRateComponentCode $code): RentalRateUnit
    {
        return match ($code) {
            RentalRateComponentCode::BaseRental,
            RentalRateComponentCode::DriverSalary => RentalRateUnit::Month,
            RentalRateComponentCode::ExcessKm => RentalRateUnit::Kilometre,
            RentalRateComponentCode::NormalOvertime,
            RentalRateComponentCode::DoubleOvertime,
            RentalRateComponentCode::TripleOvertime,
            RentalRateComponentCode::Waiting => RentalRateUnit::Hour,
            RentalRateComponentCode::NightOut,
            RentalRateComponentCode::Parking,
            RentalRateComponentCode::Toll,
            RentalRateComponentCode::Pass => RentalRateUnit::Count,
            RentalRateComponentCode::Outstation => RentalRateUnit::Trip,
            RentalRateComponentCode::Fuel => RentalRateUnit::Litre,
            RentalRateComponentCode::Damage,
            RentalRateComponentCode::Repair,
            RentalRateComponentCode::OtherRecovery,
            RentalRateComponentCode::WithholdingTax => RentalRateUnit::Fixed,
        };
    }

    /** @return array{code: string, unit: string, supported_units: list<string>, group: string, required: bool} */
    private function definition(
        RentalRateComponentCode $code,
        string $group,
        bool $required = false,
    ): array {
        return [
            'code' => $code->value,
            'unit' => self::defaultUnit($code)->value,
            'supported_units' => array_map(
                static fn (RentalRateUnit $supportedUnit): string => $supportedUnit->value,
                self::supportedUnits($code),
            ),
            'group' => $group,
            'required' => $required,
        ];
    }
}
