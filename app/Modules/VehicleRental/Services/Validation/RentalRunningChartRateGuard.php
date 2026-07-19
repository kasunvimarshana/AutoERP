<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services\Validation;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\VehicleRental\Enums\RentalBillingBasis;
use Modules\VehicleRental\Enums\RentalRateVersionStatus;
use Modules\VehicleRental\Models\RentalAssignment;
use Modules\VehicleRental\Models\RentalRateLine;
use Modules\VehicleRental\Models\RentalRateVersion;
use Modules\VehicleRental\Models\RentalRunningChart;

final class RentalRunningChartRateGuard
{
    public function assertCommercialMode(RentalAssignment $assignment, RentalRunningChart $chart): void
    {
        $assignment->loadMissing('agreement');
        if ($assignment->agreement->billing_basis !== RentalBillingBasis::Daily) {
            return;
        }

        $versions = $assignment->agreement->rateVersions()
            ->where('status', RentalRateVersionStatus::Active->value)
            ->whereDate('effective_from', '<=', $chart->operational_date?->toDateString())
            ->where(function (Builder $query) use ($chart): void {
                $date = $chart->operational_date?->toDateString();
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            })
            ->with('lines')
            ->lockForUpdate()
            ->get();
        if ($versions->count() !== 1) {
            throw new InvalidArgumentException('Running chart date must be covered by exactly one active rental rate version.');
        }

        /** @var RentalRateVersion $version */
        $version = $versions->first();
        $acRates = $version->lines->filter(fn (RentalRateLine $line): bool => $line->rate_code->isAcModeRate());
        if ($acRates->isEmpty()) {
            return;
        }
        if ($chart->ac_mode === null) {
            throw new InvalidArgumentException('Select the actual AC mode before finalizing this daily running chart.');
        }
        if (! $acRates->contains(fn (RentalRateLine $line): bool => $line->rate_code === $chart->ac_mode->rateCode())) {
            throw new InvalidArgumentException('The selected AC mode does not have an effective agreement rate.');
        }
    }
}
