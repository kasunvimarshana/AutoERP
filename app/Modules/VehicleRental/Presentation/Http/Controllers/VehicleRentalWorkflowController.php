<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Results\Result;
use Modules\VehicleRental\Application\Contracts\Services\VehicleRentalWorkflowServiceInterface;

final class VehicleRentalWorkflowController extends Controller
{
    public function __construct(private readonly VehicleRentalWorkflowServiceInterface $service) {}

    public function transitionAgreement(Request $request, int $agreementId): JsonResponse
    {
        return $this->respond($this->service->transitionAgreement($agreementId, $request->all()));
    }

    public function transitionRunningChart(Request $request, int $runningChartId): JsonResponse
    {
        return $this->respond($this->service->transitionRunningChart($runningChartId, $request->all()));
    }

    public function createInvoice(Request $request, int $agreementId): JsonResponse
    {
        return $this->respond($this->service->createInvoice($agreementId, $request->all()));
    }

    public function allocateCustomerPayment(Request $request, int $agreementId): JsonResponse
    {
        return $this->respond($this->service->allocateCustomerPayment($agreementId, $request->all()));
    }

    public function createProviderPayable(Request $request, int $agreementId): JsonResponse
    {
        return $this->respond($this->service->createProviderPayable($agreementId, $request->all()));
    }

    public function allocateProviderPayment(Request $request, int $providerPayableId): JsonResponse
    {
        return $this->respond($this->service->allocateProviderPayment($providerPayableId, $request->all()));
    }

    public function postFinance(Request $request, string $entityType, int $entityId): JsonResponse
    {
        return $this->respond($this->service->postFinance($entityType, $entityId, $request->all()));
    }

    public function reverseFinance(Request $request, string $entityType, int $entityId): JsonResponse
    {
        return $this->respond($this->service->reverseFinance($entityType, $entityId, $request->all()));
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
