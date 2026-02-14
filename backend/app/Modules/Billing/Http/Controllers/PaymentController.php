<?php

namespace App\Modules\Billing\Http\Controllers;

use App\Core\BaseController;
use App\Modules\Billing\Services\PaymentService;
use App\Modules\Billing\Requests\CreatePaymentRequest;
use App\Modules\Billing\Requests\UpdatePaymentRequest;
use App\Modules\Billing\Resources\PaymentResource;
use Illuminate\Http\JsonResponse;

class PaymentController extends BaseController
{
    protected PaymentService $service;

    public function __construct(PaymentService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $payments = $this->service->getAllPayments();
        return $this->collection(PaymentResource::collection($payments));
    }

    public function store(CreatePaymentRequest $request): JsonResponse
    {
        $payment = $this->service->createPayment($request->validated());
        return $this->created(new PaymentResource($payment));
    }

    public function show(int $id): JsonResponse
    {
        $payment = $this->service->getPayment($id);
        if (!$payment) {
            return $this->notFound();
        }
        return $this->resource(new PaymentResource($payment));
    }

    public function update(UpdatePaymentRequest $request, int $id): JsonResponse
    {
        $result = $this->service->updatePayment($id, $request->validated());
        if (!$result) {
            return $this->notFound();
        }
        return $this->success(null, 'Payment updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deletePayment($id);
        if (!$result) {
            return $this->notFound();
        }
        return $this->success(null, 'Payment deleted successfully');
    }
}
