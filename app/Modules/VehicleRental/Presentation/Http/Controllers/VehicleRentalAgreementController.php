<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Results\Result;
use Modules\VehicleRental\Application\Contracts\Services\VehicleRentalManagementServiceInterface;
use Modules\VehicleRental\Presentation\Http\Requests\ListVehicleRentalAgreementRequest;
use Modules\VehicleRental\Presentation\Http\Requests\UpsertVehicleRentalAggregateRequest;
use Modules\VehicleRental\Presentation\Http\Resources\VehicleRentalRecordResource;

final class VehicleRentalAgreementController extends Controller
{
    public function __construct(private readonly VehicleRentalManagementServiceInterface $service) {}

    public function index(ListVehicleRentalAgreementRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $tenantId = (int) $validated['tenant_id'];
        $agreementRole = $validated['agreement_role'] ?? null;

        return $this->respond($this->service->listAgreements(
            $tenantId,
            is_string($agreementRole) ? $agreementRole : null,
        ));
    }

    public function store(UpsertVehicleRentalAggregateRequest $request): JsonResponse
    {
        return $this->respond($this->service->upsertAgreementAggregate(null, $request->all()));
    }

    public function show(int $agreement): JsonResponse
    {
        return $this->respond($this->service->getAgreement($agreement));
    }

    public function update(UpsertVehicleRentalAggregateRequest $request, int $agreement): JsonResponse
    {
        return $this->respond($this->service->upsertAgreementAggregate($agreement, $request->all()));
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
