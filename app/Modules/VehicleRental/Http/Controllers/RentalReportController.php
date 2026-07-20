<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Services\RentalReportService;

final class RentalReportController
{
    public function summary(ListRentalRequest $request, RentalReportService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->summary(
                $request->tenantId(),
                $request->organizationUnitId(),
                $request->validated('date_from'),
                $request->validated('date_to'),
            ),
        ]);
    }
}
