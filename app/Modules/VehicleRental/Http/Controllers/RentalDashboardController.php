<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\VehicleRental\Http\Requests\RentalDashboardRequest;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RentalDashboardService;

final class RentalDashboardController extends Controller
{
    public function __construct(
        private readonly RentalDashboardService $service,
        private readonly RentalAuthorization $auth,
    ) {}

    public function show(RentalDashboardRequest $request): JsonResponse
    {
        $context = $request->context();
        $this->auth->assertDashboard($context);

        $data = $this->service->getSummary($context->tenantId, $context->organizationUnitId);

        return response()->json(['data' => $data]);
    }
}
