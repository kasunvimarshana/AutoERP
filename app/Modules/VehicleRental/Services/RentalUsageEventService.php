<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Facades\DB;
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
        if ($this->math->compare($data->quantity, '0.000000') <= 0) {
            throw new InvalidArgumentException('Usage event quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($log, $data): RentalUsageEvent {
            $log = RentalUsageLog::query()->lockForUpdate()->findOrFail($log->getKey());
            if ($log->status !== RentalUsageLogStatus::Draft) {
                throw new InvalidArgumentException('Usage events can only be changed while the running chart is draft.');
            }
            if ($data->eventType === RentalUsageEventType::Weekend && ! $log->usage_date->isWeekend()) {
                throw new InvalidArgumentException('Weekend usage can only be classified on a weekend usage date.');
            }

            return RentalUsageEvent::query()->create([
                'tenant_id' => $log->tenant_id,
                'organization_unit_id' => $log->organization_unit_id,
                'usage_log_id' => $log->getKey(),
                'event_type' => $data->eventType->value,
                'quantity' => $this->math->normalize($data->quantity),
                'remarks' => $data->remarks,
                'created_by' => $data->createdBy,
            ]);
        });
    }
}
