<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleRental\Enums\RentalUsageEventType;
use Modules\VehicleRental\Enums\RentalUsageLogStatus;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\RentalActionRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalUsageEventRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalUsageLogRequest;
use Modules\VehicleRental\Http\Resources\RentalUsageEventResource;
use Modules\VehicleRental\Http\Resources\RentalUsageLogResource;
use Modules\VehicleRental\Services\RentalUsageEventService;
use Modules\VehicleRental\Services\RentalUsageLogService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class RentalUsageController extends RentalController
{
    public function index(ListRentalRequest $request, int $agreement): AnonymousResourceCollection
    {
        return RentalUsageLogResource::collection(
            $this->agreement($request, $agreement)->operationalUsageLogs()
                ->with([
                    'vehicle.make',
                    'vehicle.model',
                    'driver',
                    'events',
                    'contexts.agreement.customer',
                    'contexts.agreement.supplier',
                    'contexts.rateSnapshot',
                ])
                ->get(),
        );
    }

    public function store(
        StoreRentalUsageLogRequest $request,
        int $agreement,
        RentalUsageLogService $service,
        VehicleRentalAuthorizationService $authorization,
    ): JsonResponse {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::RECORD_USAGE,
        );

        return (new RentalUsageLogResource($service->create(
            $this->agreement($request, $agreement),
            $request->toData(),
        )))->response()->setStatusCode(201);
    }

    public function storeEvent(
        StoreRentalUsageEventRequest $request,
        int $agreement,
        int $usageLog,
        RentalUsageEventService $service,
        VehicleRentalAuthorizationService $authorization,
    ): JsonResponse {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::RECORD_USAGE,
        );
        $model = $this->agreement($request, $agreement);
        $data = $request->toData();
        if ($data->eventType === RentalUsageEventType::Holiday) {
            $authorization->assert(
                $request->currentUserId(),
                $request->tenantId(),
                VehicleRentalAuthorizationService::CLASSIFY_HOLIDAY,
            );
            if (trim((string) $data->remarks) === '') {
                abort(422, 'Holiday usage classification requires a documented reason or calendar reference.');
            }
        }

        return (new RentalUsageEventResource($service->create(
            $this->usageLog($model, $usageLog),
            $data,
        )))->response()->setStatusCode(201);
    }

    public function submit(
        RentalActionRequest $request,
        int $agreement,
        int $usageLog,
        RentalUsageLogService $service,
        VehicleRentalAuthorizationService $authorization,
    ): RentalUsageLogResource {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::RECORD_USAGE,
        );
        $model = $this->agreement($request, $agreement);

        return new RentalUsageLogResource($service->changeStatus(
            $this->usageLog($model, $usageLog),
            RentalUsageLogStatus::Submitted,
            $request->currentUserId(),
            $request->input('reason'),
        ));
    }

    public function approve(
        RentalActionRequest $request,
        int $agreement,
        int $usageLog,
        RentalUsageLogService $service,
        VehicleRentalAuthorizationService $authorization,
    ): RentalUsageLogResource {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::APPROVE_USAGE,
        );
        $mileageOverride = $request->boolean('mileage_override');
        if ($mileageOverride) {
            $authorization->assert(
                $request->currentUserId(),
                $request->tenantId(),
                VehicleRentalAuthorizationService::OVERRIDE_MILEAGE,
            );
            if (trim((string) $request->input('reason')) === '') {
                abort(422, 'A reason is required for a mileage-chain override.');
            }
        }
        $model = $this->agreement($request, $agreement);

        return new RentalUsageLogResource($service->changeStatus(
            $this->usageLog($model, $usageLog),
            RentalUsageLogStatus::Approved,
            $request->currentUserId(),
            $request->input('reason'),
            $mileageOverride,
        ));
    }

    public function reject(
        RentalActionRequest $request,
        int $agreement,
        int $usageLog,
        RentalUsageLogService $service,
        VehicleRentalAuthorizationService $authorization,
    ): RentalUsageLogResource {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::APPROVE_USAGE,
        );
        $model = $this->agreement($request, $agreement);

        return new RentalUsageLogResource($service->changeStatus(
            $this->usageLog($model, $usageLog),
            RentalUsageLogStatus::Rejected,
            $request->currentUserId(),
            $request->input('reason'),
        ));
    }
}
