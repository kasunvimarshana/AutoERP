<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Modules\VehicleRental\Enums\RentalUsageFactStatus;
use Modules\VehicleRental\Http\Controllers\Concerns\ScopesVehicleRentalRequests;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\RentalUsageFactTransitionRequest;
use Modules\VehicleRental\Http\Requests\UpdateRentalUsageFactRequest;
use Modules\VehicleRental\Http\Resources\RentalUsageFactResource;
use Modules\VehicleRental\Models\RentalUsageFact;
use Modules\VehicleRental\Services\RentalUsageFactService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class RentalUsageFactController
{
    use ScopesVehicleRentalRequests;

    public function __construct(private readonly VehicleRentalAuthorizationService $authorization) {}

    public function show(ListRentalRequest $request, int $fact): RentalUsageFactResource
    {
        $this->authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::VIEW,
        );

        return new RentalUsageFactResource(
            $this->scope(RentalUsageFact::query(), $request)
                ->with([
                    'context.agreement.customer',
                    'context.agreement.supplier',
                    'context.rateVersion',
                    'usageLog',
                ])
                ->findOrFail($fact),
        );
    }

    public function update(
        UpdateRentalUsageFactRequest $request,
        int $fact,
        RentalUsageFactService $service,
    ): RentalUsageFactResource {
        $this->authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::RECORD_USAGE,
        );
        $model = $this->scope(RentalUsageFact::query(), $request)->findOrFail($fact);

        return new RentalUsageFactResource($service->update(
            $model,
            $request->validated(),
            (int) $request->input('expected_version'),
            $request->currentUserId(),
        ));
    }

    public function transition(
        RentalUsageFactTransitionRequest $request,
        int $fact,
        RentalUsageFactService $service,
    ): RentalUsageFactResource {
        $status = RentalUsageFactStatus::from((string) $request->input('status'));
        $permission = in_array(
            $status,
            [RentalUsageFactStatus::Approved, RentalUsageFactStatus::Rejected, RentalUsageFactStatus::Reversed],
            true,
        )
            ? VehicleRentalAuthorizationService::APPROVE_USAGE
            : VehicleRentalAuthorizationService::RECORD_USAGE;
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), $permission);
        $model = $this->scope(RentalUsageFact::query(), $request)->findOrFail($fact);

        return new RentalUsageFactResource($service->transition(
            $model,
            $status,
            (int) $request->input('expected_version'),
            $request->currentUserId(),
            $request->input('reason'),
        ));
    }
}
