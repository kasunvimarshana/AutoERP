<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\VehicleRental\Http\Requests\VehicleDriverReassignmentRequest;
use Modules\VehicleRental\Services\VehicleDriverReassignmentService;
use Symfony\Component\HttpFoundation\Response;

final class VehicleDriverReassignmentController
{
    public function __construct(private readonly VehicleDriverReassignmentService $service) {}

    public function index(VehicleDriverReassignmentRequest $request): JsonResponse
    {
        $agreementId = $request->query('customer_agreement_id') !== null
            ? (int) $request->query('customer_agreement_id')
            : null;

        $reassignments = $this->service->listReassignments($request->context(), $agreementId);

        return response()->json(['data' => $reassignments]);
    }

    public function reassignDriver(VehicleDriverReassignmentRequest $request, int $chart): JsonResponse
    {
        $driverId = (int) $request->input('driver_employee_id');
        $reason = (string) $request->input('reason');
        $expectedVersion = $request->expectedVersion();

        $reassignment = $this->service->reassignRunningChartDriver(
            $request->context(),
            $chart,
            $driverId,
            $reason,
            $expectedVersion
        );

        return response()->json(['data' => $reassignment], Response::HTTP_CREATED);
    }

    public function replaceVehicle(VehicleDriverReassignmentRequest $request, int $use): JsonResponse
    {
        $reassignment = $this->service->replaceVehicle(
            $request->context(),
            $use,
            $request->all(),
            $request->expectedVersion()
        );

        return response()->json(['data' => $reassignment], Response::HTTP_CREATED);
    }
}
