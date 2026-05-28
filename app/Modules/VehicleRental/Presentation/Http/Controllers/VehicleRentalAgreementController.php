<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Results\Result;
use Modules\VehicleRental\Application\Contracts\Services\VehicleRentalManagementServiceInterface;

final class VehicleRentalAgreementController extends Controller
{
    public function __construct(private readonly VehicleRentalManagementServiceInterface $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->input('tenant_id', 0);
        $agreementRole = $request->input('agreement_role');

        return $this->respond($this->service->listAgreements(
            $tenantId,
            is_string($agreementRole) ? $agreementRole : null,
        ));
    }

    public function store(Request $request): JsonResponse
    {
        return $this->respond($this->service->upsertAgreementAggregate(null, $request->all()));
    }

    public function show(int $agreement): JsonResponse
    {
        return $this->respond($this->service->getAgreement($agreement));
    }

    public function update(Request $request, int $agreement): JsonResponse
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

        return response()->json(['data' => $result->valueOrFail()]);
    }
}
