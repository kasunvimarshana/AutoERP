<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Payment\Http\Requests\AllocatePaymentRequest;
use Modules\Payment\Http\Requests\ListPaymentRequest;
use Modules\Payment\Http\Requests\PaymentActionRequest;
use Modules\Payment\Http\Requests\RefundPaymentRequest;
use Modules\Payment\Http\Requests\ReversePaymentRequest;
use Modules\Payment\Http\Requests\SettlePaymentLineRequest;
use Modules\Payment\Http\Requests\StorePaymentRequest;
use Modules\Payment\Http\Resources\PaymentResource;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentAllocation;
use Modules\Payment\Services\PaymentAllocationService;
use Modules\Payment\Services\PaymentAuthorizationService;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Payment\Services\PaymentDocumentLifecycleService;
use Modules\Payment\Services\PaymentPostingService;
use Modules\Payment\Services\PaymentRefundService;
use Modules\Payment\Services\PaymentReversalService;
use Modules\Payment\Services\PaymentSettlementService;

final class PaymentController
{
    public function __construct(private readonly PaymentAuthorizationService $authorization) {}

    public function index(ListPaymentRequest $request): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentAuthorizationService::PAYMENTS_VIEW);
        $query = $this->scope(Payment::query(), $request)->with('currency');
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(fn (Builder $scope): Builder => $scope
                ->where('payment_number', 'like', "%{$search}%")
                ->orWhere('reference_number', 'like', "%{$search}%"));
        }
        foreach (['payment_type', 'direction', 'document_status', 'allocation_status', 'posting_status', 'instrument_status', 'party_id'] as $filter) {
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
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentAuthorizationService::PAYMENTS_CREATE);

        return new PaymentResource($service->create($request->toData()));
    }

    public function show(
        ListPaymentRequest $request,
        int $payment,
        InvoiceBalanceProviderInterface $invoices,
    ): PaymentResource {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentAuthorizationService::PAYMENTS_VIEW);
        $row = $this->scope(Payment::query(), $request)
            ->with([
                'currency', 'lines', 'allocations', 'unappliedBalance', 'refunds.refundPayment',
                'reversals', 'lifecycleEvents', 'originalPayment',
            ])
            ->findOrFail($payment);
        $this->attachInvoiceReferences($row->allocations, $invoices);

        return new PaymentResource($row);
    }

    public function approve(
        PaymentActionRequest $request,
        int $payment,
        PaymentDocumentLifecycleService $service,
    ): PaymentResource {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentAuthorizationService::PAYMENTS_APPROVE);

        return new PaymentResource($service->approve(
            $this->find($request, $payment),
            $request->expectedVersion(),
            $request->currentUserId(),
        ));
    }

    public function submitForApproval(
        PaymentActionRequest $request,
        int $payment,
        PaymentDocumentLifecycleService $service,
    ): PaymentResource {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentAuthorizationService::PAYMENTS_SUBMIT);

        return new PaymentResource($service->submit(
            $this->find($request, $payment),
            $request->expectedVersion(),
            $request->currentUserId(),
        ));
    }

    public function post(PaymentActionRequest $request, int $payment, PaymentPostingService $service): PaymentResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentAuthorizationService::PAYMENTS_POST);

        return new PaymentResource($service->post(
            $this->find($request, $payment),
            $request->expectedVersion(),
            $request->currentUserId(),
        ));
    }

    public function void(
        PaymentActionRequest $request,
        int $payment,
        PaymentDocumentLifecycleService $service,
    ): PaymentResource {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentAuthorizationService::PAYMENTS_VOID);

        return new PaymentResource($service->void(
            $this->find($request, $payment),
            $request->expectedVersion(),
            $request->currentUserId(),
            $request->reason(),
        ));
    }

    public function reverse(ReversePaymentRequest $request, int $payment, PaymentReversalService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentAuthorizationService::PAYMENTS_REVERSE);
        $this->find($request, $payment);

        return response()->json(['data' => $service->reverse($request->toData($payment))]);
    }

    public function allocate(AllocatePaymentRequest $request, int $payment, PaymentAllocationService $service): PaymentResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentAuthorizationService::PAYMENTS_ALLOCATE);

        return new PaymentResource($service->allocate(
            $this->find($request, $payment),
            $request->toData(),
            $request->expectedVersion(),
            $request->currentUserId(),
        ));
    }

    public function allocations(
        PaymentActionRequest $request,
        int $payment,
        InvoiceBalanceProviderInterface $invoices,
    ): JsonResponse {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentAuthorizationService::PAYMENTS_VIEW);
        $allocations = $this->find($request, $payment)->allocations()->get();
        $this->attachInvoiceReferences($allocations, $invoices);

        return response()->json(['data' => $allocations]);
    }

    public function unappliedBalance(PaymentActionRequest $request, int $payment): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentAuthorizationService::PAYMENTS_VIEW);

        return response()->json(['data' => $this->find($request, $payment)->unappliedBalance()->first()]);
    }

    public function settleLine(
        SettlePaymentLineRequest $request,
        int $payment,
        int $line,
        PaymentSettlementService $service,
    ): JsonResponse {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentAuthorizationService::PAYMENTS_SETTLE);

        return response()->json([
            'data' => $service->transitionLine(
                $this->find($request, $payment),
                $line,
                $request->settlementStatus(),
                $request->metadata(),
            ),
        ]);
    }

    public function refund(RefundPaymentRequest $request, int $payment, PaymentRefundService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentAuthorizationService::PAYMENTS_REFUND);
        $this->find($request, $payment);

        return response()->json(['data' => $service->refund($request->toData($payment))], 201);
    }

    private function find(
        PaymentActionRequest|AllocatePaymentRequest|RefundPaymentRequest|ReversePaymentRequest|SettlePaymentLineRequest $request,
        int $payment,
    ): Payment {
        return $this->scope(Payment::query(), $request)->findOrFail($payment);
    }

    private function scope(
        Builder $query,
        ListPaymentRequest|PaymentActionRequest|AllocatePaymentRequest|RefundPaymentRequest|ReversePaymentRequest|SettlePaymentLineRequest $request,
    ): Builder {
        $query->where('tenant_id', $request->tenantId());

        return $request->organizationUnitId() === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $request->organizationUnitId());
    }

    private function attachInvoiceReferences(Collection $allocations, InvoiceBalanceProviderInterface $invoices): void
    {
        $references = $invoices->getInvoiceReferences(
            $allocations->pluck('invoice_id')->map(fn (mixed $id): int => (int) $id)->all(),
        );
        foreach ($allocations as $allocation) {
            $allocation->setAttribute('invoice', $references[(int) $allocation->invoice_id] ?? null);
        }
    }
}
