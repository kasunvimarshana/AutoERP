<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\DTOs\RentalUsageEventData;
use Modules\VehicleRental\Enums\RentalUsageEventType;
use Modules\VehicleRental\Enums\RentalUsageLogStatus;
use Modules\VehicleRental\Models\RentalUsageEvent;
use Modules\VehicleRental\Models\RentalUsageLog;

final class RentalUsageEventService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function create(RentalUsageLog $log, RentalUsageEventData $data): RentalUsageEvent
    {
        if ($log->status === RentalUsageLogStatus::Rejected) {
            throw new InvalidArgumentException('Usage events cannot be added to a rejected usage log.');
        }
        if ($this->math->compare($data->quantity, '0.000000') <= 0) {
            throw new InvalidArgumentException('Usage event quantity must be greater than zero.');
        }
        $log->loadMissing('agreement.rateSnapshot');
        $rate = $data->rate ?? $this->rate($log, $data->eventType);
        if ($this->math->isNegative($rate)) {
            throw new InvalidArgumentException('Usage event rate cannot be negative.');
        }

        return RentalUsageEvent::query()->create([
            'tenant_id' => $log->tenant_id,
            'organization_unit_id' => $log->organization_unit_id,
            'usage_log_id' => $log->getKey(),
            'agreement_id' => $log->agreement_id,
            'event_type' => $data->eventType->value,
            'quantity' => $this->math->normalize($data->quantity),
            'rate_snapshot' => $this->math->normalize($rate),
            'amount' => $this->math->mul($data->quantity, $rate),
            'remarks' => $data->remarks,
        ]);
    }

    private function rate(RentalUsageLog $log, RentalUsageEventType $type): string
    {
        $snapshot = $log->agreement?->rateSnapshot
            ?? throw new InvalidArgumentException('Agreement rate snapshot is missing.');

        return (string) match ($type) {
            RentalUsageEventType::ExtraHour => $snapshot->extra_hour_rate,
            RentalUsageEventType::ExtraKm => $snapshot->extra_km_rate,
            RentalUsageEventType::Overtime => $snapshot->overtime_rate,
            RentalUsageEventType::DoubleOvertime => $snapshot->double_overtime_rate,
            RentalUsageEventType::NightShift => $snapshot->night_shift_rate,
            RentalUsageEventType::Weekend => $snapshot->weekend_rate,
            RentalUsageEventType::Holiday => $snapshot->holiday_rate,
            RentalUsageEventType::DayOut => $snapshot->day_out_rate,
            RentalUsageEventType::NightOut => $snapshot->night_out_rate,
            RentalUsageEventType::Driver => $snapshot->driver_rate,
            RentalUsageEventType::Outstation => $snapshot->outstation_rate,
            RentalUsageEventType::Waiting => $snapshot->waiting_hour_rate,
            RentalUsageEventType::Pass, RentalUsageEventType::Other => '0.000000',
        };
    }
}
