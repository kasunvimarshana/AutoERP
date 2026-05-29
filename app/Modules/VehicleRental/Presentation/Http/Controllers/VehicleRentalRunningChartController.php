<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Results\Result;
use Modules\VehicleRental\Application\Contracts\Services\VehicleRentalManagementServiceInterface;
use Modules\VehicleRental\Presentation\Http\Requests\ListVehicleRentalRunningChartRequest;
use Modules\VehicleRental\Presentation\Http\Requests\UpsertVehicleRentalAggregateRequest;
use Modules\VehicleRental\Presentation\Http\Resources\VehicleRentalRecordResource;

final class VehicleRentalRunningChartController extends Controller
{
    public function __construct(private readonly VehicleRentalManagementServiceInterface $service) {}

    public function index(ListVehicleRentalRunningChartRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $tenantId = (int) $validated['tenant_id'];
        $agreementId = isset($validated['agreement_id']) ? (int) $validated['agreement_id'] : null;

        return $this->respond($this->service->listRunningCharts($tenantId, $agreementId));
    }

    public function store(UpsertVehicleRentalAggregateRequest $request): JsonResponse
    {
        return $this->respond($this->service->upsertRunningChartAggregate(null, $request->all()));
    }

    public function show(int $runningChart): JsonResponse
    {
        return $this->respond($this->service->getRunningChart($runningChart));
    }

    public function update(UpsertVehicleRentalAggregateRequest $request, int $runningChart): JsonResponse
    {
        return $this->respond($this->service->upsertRunningChartAggregate($runningChart, $request->all()));
    }

    private function respond(Result $result): JsonResponse
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $statusCode = $error->code === 'VEHICLERENTAL_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $statusCode);
        }

        return response()->json(['data' => (new VehicleRentalRecordResource($result->valueOrFail()))->resolve()]);
    }
}
