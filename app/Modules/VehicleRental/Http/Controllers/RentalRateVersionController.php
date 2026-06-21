<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\VehicleRental\Http\Controllers\Concerns\ScopesVehicleRentalRequests;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalRateVersionRequest;
use Modules\VehicleRental\Http\Resources\RentalRateVersionResource;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAgreementRateVersion;
use Modules\VehicleRental\Services\RentalRateVersionService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class RentalRateVersionController
{
    use ScopesVehicleRentalRequests;

    public function __construct(private readonly VehicleRentalAuthorizationService $authorization) {}

    public function store(StoreRentalRateVersionRequest $request, int $agreement, RentalRateVersionService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_RATES);
        $agreementModel = $this->scope(RentalAgreement::query(), $request)->findOrFail($agreement);
        return (new RentalRateVersionResource($service->createDraft($agreementModel, $request->validated(), $request->currentUserId())))->response()->setStatusCode(201);
    }

    public function activate(ListRentalRequest $request, int $version, RentalRateVersionService $service): RentalRateVersionResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_RATES);
        $model = $this->scope(RentalAgreementRateVersion::query(), $request)->findOrFail($version);
        return new RentalRateVersionResource($service->activate($model, $request->currentUserId()));
    }
}
