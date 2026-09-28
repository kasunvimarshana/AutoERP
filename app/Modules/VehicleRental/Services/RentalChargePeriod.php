<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Models\RentalCharge;

final class RentalChargePeriod
{
    public function __construct(private readonly RentalCalendar $calendar) {}

    /** @return array{string, string} */
    public function fromChart(AgreementContext $context, string $startsAt, string $endsAt): array
    {
        $timezone = $this->calendar->timezone($context);
        $start = OperationalTime::parse($startsAt, 'starts_at')->setTimezone($timezone);
        $end = OperationalTime::parse($endsAt, 'ends_at')->setTimezone($timezone);

        return [$start->toDateString(), $end->subMicrosecond()->toDateString()];
    }

    /** @return array{string, string} */
    public function resolve(RentalCharge $charge, AgreementContext $context): array
    {
        $calculation = $charge->calculation;
        if (isset($calculation['supply_from'], $calculation['supply_until'])) {
            return [(string) $calculation['supply_from'], (string) $calculation['supply_until']];
        }
        if (isset($calculation['from'], $calculation['until'])) {
            return [(string) $calculation['from'], (string) $calculation['until']];
        }
        if (isset($calculation['chart']['starts_at'], $calculation['chart']['ends_at'])) {
            return $this->fromChart(
                $context,
                (string) $calculation['chart']['starts_at'],
                (string) $calculation['chart']['ends_at'],
            );
        }

        return [(string) $charge->period_from, (string) $charge->period_until];
    }
}
