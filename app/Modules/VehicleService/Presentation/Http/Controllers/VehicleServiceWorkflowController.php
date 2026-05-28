<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Results\Result;
use Modules\VehicleService\Application\Contracts\Services\VehicleServiceWorkflowServiceInterface;

final class VehicleServiceWorkflowController extends Controller
{
    public function __construct(private readonly VehicleServiceWorkflowServiceInterface $service)
    {
    }

    public function transition(Request $request, int $jobCardId): JsonResponse
    {
        return $this->respond($this->service->transition($jobCardId, $request->all()));
    }

    public function createInvoice(Request $request, int $jobCardId): JsonResponse
    {
        return $this->respond($this->service->createInvoice($jobCardId, $request->all()));
    }

    public function allocatePayment(Request $request, int $jobCardId): JsonResponse
    {
        return $this->respond($this->service->allocatePayment($jobCardId, $request->all()));
    }

    public function postInventory(Request $request, int $jobCardId): JsonResponse
    {
        return $this->respond($this->service->postInventory($jobCardId, $request->all()));
    }

    public function postFinance(Request $request, int $jobCardId): JsonResponse
    {
        return $this->respond($this->service->postFinance($jobCardId, $request->all()));
    }

    public function reverseFinance(Request $request, int $jobCardId): JsonResponse
    {
        return $this->respond($this->service->reverseFinance($jobCardId, $request->all()));
    }

    private function respond(Result $result): JsonResponse
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $statusCode = $error->code === 'VEHICLESERVICE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $statusCode);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }
}
