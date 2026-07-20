<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\VehicleRental\Http\Requests\CreateRentalFinancialDocumentRequest;
use Modules\VehicleRental\Http\Resources\RentalFinancialDocumentResource;
use Modules\VehicleRental\Services\RentalFinancialDocumentService;

final class RentalFinancialDocumentController extends RentalController
{
    public function store(
        CreateRentalFinancialDocumentRequest $request,
        int $calculation,
        RentalFinancialDocumentService $service,
    ): JsonResponse {
        return (new RentalFinancialDocumentResource($service->create(
            $this->calculation($request, $calculation),
            $request->toData(),
        )))->response()->setStatusCode(201);
    }
}
