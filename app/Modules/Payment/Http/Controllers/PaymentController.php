<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Http\Requests\AllocatePaymentRequest;
use Modules\Payment\Http\Requests\ListPaymentRequest;
use Modules\Payment\Http\Requests\PaymentActionRequest;
use Modules\Payment\Http\Requests\RefundPaymentRequest;
use Modules\Payment\Http\Requests\ReversePaymentRequest;
use Modules\Payment\Http\Requests\StorePaymentRequest;
use Modules\Payment\Http\Resources\PaymentResource;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentAllocationService;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Payment\Services\PaymentRefundService;
use Modules\Payment\Services\PaymentReversalService;
use Modules\Payment\Services\PaymentStatusService;

final class PaymentController
{
    public function index(ListPaymentRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(Payment::query(), $request);
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(fn (Builder $scope): Builder => $scope
                ->where('payment_number', 'like', "%{$search}%")
                ->orWhere('reference_number', 'like', "%{$search}%"));
        }
        foreach (['payment_type', 'direction', 'status', 'party_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->input('date_to'));
        }

        return PaymentResource::collection($query->latest('payment_date')->paginate($request->perPage()));
    }

    public function store(StorePaymentRequest $request, PaymentCreationService $service): PaymentResource
    {
        return new PaymentResource($service->create($request->toData()));
    }

    public function show(ListPaymentRequest $request, int $payment): PaymentResource
    {
        return new PaymentResource($this->scope(Payment::query(), $request)
            ->with(['lines', 'allocations', 'unappliedBalance', 'refunds', 'reversals'])->findOrFail($payment));
    }

    public function approve(PaymentActionRequest $request, int $payment, PaymentStatusService $service): PaymentResource
    {
        return new PaymentResource($service->transition($this->find($request, $payment), PaymentStatus::Approved, $request->currentUserId()));
    }

    public function post(PaymentActionRequest $request, int $payment, PaymentStatusService $service): PaymentResource
    {
        return new PaymentResource($service->transition($this->find($request, $payment), PaymentStatus::Posted, $request->currentUserId()));
    }

    public function void(PaymentActionRequest $request, int $payment, PaymentStatusService $service): PaymentResource
    {
        return new PaymentResource($service->void(
            $this->find($request, $payment),
            $request->currentUserId(),
            $request->filled('reason') ? (string) $request->input('reason') : null,
        ));
    }

    public function reverse(ReversePaymentRequest $request, int $payment, PaymentReversalService $service): JsonResponse
    {
        $this->find($request, $payment);

        return response()->json(['data' => $service->reverse($request->toData($payment))]);
    }

    public function allocate(AllocatePaymentRequest $request, int $payment, PaymentAllocationService $service): PaymentResource
    {
        return new PaymentResource($service->allocate($this->find($request, $payment), $request->toData()));
    }

    public function allocations(PaymentActionRequest $request, int $payment): JsonResponse
    {
        return response()->json(['data' => $this->find($request, $payment)->allocations()->get()]);
    }

    public function unappliedBalance(PaymentActionRequest $request, int $payment): JsonResponse
    {
        return response()->json(['data' => $this->find($request, $payment)->unappliedBalance()->first()]);
    }

    public function refund(RefundPaymentRequest $request, int $payment, PaymentRefundService $service): JsonResponse
    {
        $this->find($request, $payment);

        return response()->json(['data' => $service->refund($request->toData($payment))], 201);
    }

    private function find(PaymentActionRequest|AllocatePaymentRequest|RefundPaymentRequest|ReversePaymentRequest $request, int $payment): Payment
    {
        return $this->scope(Payment::query(), $request)->findOrFail($payment);
    }

    private function scope(Builder $query, ListPaymentRequest|PaymentActionRequest|AllocatePaymentRequest|RefundPaymentRequest|ReversePaymentRequest $request): Builder
    {
        $query->where('tenant_id', $request->tenantId());

        return $request->organizationUnitId() === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $request->organizationUnitId());
    }
}
