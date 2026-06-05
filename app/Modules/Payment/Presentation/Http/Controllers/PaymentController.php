<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Modules\Payment\Application\Services\PaymentService;
use Modules\Payment\Presentation\Http\Requests\AllocatePaymentRequest;
use Modules\Payment\Presentation\Http\Requests\ListPaymentRequest;
use Modules\Payment\Presentation\Http\Requests\StorePaymentRequest;
use Modules\Payment\Presentation\Http\Resources\PaymentResource;

final class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function index(ListPaymentRequest $request): AnonymousResourceCollection
    {
        return PaymentResource::collection($this->payments->paginate($request->validated()));
    }

    public function show(int $payment): PaymentResource
    {
        return new PaymentResource($this->payments->find($payment));
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        return (new PaymentResource($this->payments->create($request->validated())))->response()->setStatusCode(201);
    }

    public function allocate(AllocatePaymentRequest $request, int $payment): PaymentResource
    {
        return new PaymentResource($this->payments->allocate($payment, $request->validated()['allocations']));
    }

    public function allocateAdvance(AllocatePaymentRequest $request, int $advance): JsonResponse
    {
        return response()->json(['data' => $this->payments->allocateAdvance($advance, $request->validated()['allocations'])]);
    }

    public function destroy(int $payment): JsonResponse
    {
        $this->payments->delete($payment);

        return response()->json(null, 204);
    }
}
