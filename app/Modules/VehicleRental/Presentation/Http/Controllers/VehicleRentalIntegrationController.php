<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Results\Result;
use Modules\VehicleRental\Application\Contracts\Services\VehicleRentalIntegrationServiceInterface;

final class VehicleRentalIntegrationController extends Controller
{
    public function __construct(private readonly VehicleRentalIntegrationServiceInterface $service)
    {
    }

    public function createRentalInvoice(Request $request, int $agreementId): JsonResponse
    {
        return $this->respond($this->service->createRentalInvoice($agreementId, $request->all()));
    }

    public function allocateRentalPayment(Request $request, int $agreementId): JsonResponse
    {
        return $this->respond($this->service->allocateRentalPayment($agreementId, $request->all()));
    }

    public function createRentalProviderPayable(Request $request, int $agreementId): JsonResponse
    {
        return $this->respond($this->service->createRentalProviderPayable($agreementId, $request->all()));
    }

    public function allocateProviderPayablePayment(Request $request, int $providerPayableId): JsonResponse
    {
        return $this->respond($this->service->allocateProviderPayablePayment($providerPayableId, $request->all()));
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
