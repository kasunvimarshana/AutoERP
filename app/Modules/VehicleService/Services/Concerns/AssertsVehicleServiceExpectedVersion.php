<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services\Concerns;

use Illuminate\Validation\ValidationException;
use Modules\VehicleService\Models\VehicleServiceJob;

trait AssertsVehicleServiceExpectedVersion
{
    private function assertExpectedVersion(VehicleServiceJob $job, ?int $expectedVersion): void
    {
        if ($expectedVersion === null) {
            return;
        }

        if ((int) $job->row_version === $expectedVersion) {
            return;
        }

        throw ValidationException::withMessages([
            'expected_version' => ['Vehicle service job was changed by another request. Reload it before continuing.'],
        ]);
    }

    private function bumpJobVersion(VehicleServiceJob $job): VehicleServiceJob
    {
        $job->forceFill(['row_version' => (int) $job->row_version + 1])->save();

        return $job->refresh();
    }
}
