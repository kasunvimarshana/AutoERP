<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAgreementVehicle;
use Modules\VehicleRental\Models\RentalCharge;
use Modules\VehicleRental\Models\RentalExpense;
use Modules\VehicleRental\Models\RentalReservation;
use Modules\VehicleRental\Models\RentalUsageLog;

abstract class RentalController
{
    protected function agreement(TenantScopedRequest $request, int $id): RentalAgreement
    {
        return RentalAgreement::query()
            ->forContext($request->tenantId(), $request->organizationUnitId())
            ->findOrFail($id);
    }

    protected function reservation(TenantScopedRequest $request, int $id): RentalReservation
    {
        return RentalReservation::query()
            ->forContext($request->tenantId(), $request->organizationUnitId())
            ->findOrFail($id);
    }

    protected function allocation(RentalAgreement $agreement, int $id): RentalAgreementVehicle
    {
        return $agreement->vehicles()->with(['vehicle', 'pickupInspection', 'returnInspection'])->findOrFail($id);
    }

    protected function usageLog(RentalAgreement $agreement, int $id): RentalUsageLog
    {
        return RentalUsageLog::query()
            ->whereKey($id)
            ->whereHas('contexts', fn ($query) => $query->where('agreement_id', $agreement->getKey()))
            ->firstOrFail();
    }

    protected function expense(RentalAgreement $agreement, int $id): RentalExpense
    {
        return $agreement->expenses()->findOrFail($id);
    }

    protected function charge(RentalAgreement $agreement, int $id): RentalCharge
    {
        return $agreement->charges()->findOrFail($id);
    }
}
