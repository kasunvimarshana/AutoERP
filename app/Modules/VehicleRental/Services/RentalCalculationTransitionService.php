<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\VehicleRental\Enums\RentalCalculationStatus;
use Modules\VehicleRental\Models\RentalCalculationRun;

final class RentalCalculationTransitionService
{
    public function __construct(
        private readonly RentalCalculationService $workflow,
    ) {}

    public function transition(
        RentalCalculationRun $run,
        RentalCalculationStatus $to,
        int $expectedVersion,
        ?int $userId = null,
        ?string $reason = null,
    ): RentalCalculationRun {
        return DB::transaction(function () use (
            $run,
            $to,
            $expectedVersion,
            $userId,
            $reason,
        ): RentalCalculationRun {
            $locked = RentalCalculationRun::query()
                ->lockForUpdate()
                ->findOrFail($run->getKey());

            if ((int) $locked->row_version !== $expectedVersion) {
                throw new InvalidArgumentException(
                    'Rental calculation changed since it was loaded. Reload and try again.',
                );
            }

            if ($locked->calculation_status === $to) {
                return $locked->load($this->workflow->relations());
            }

            $locked->forceFill([
                'row_version' => $expectedVersion + 1,
                'updated_by' => $userId,
            ])->save();

            return $this->workflow->transition($locked, $to, $userId, $reason);
        });
    }
}
