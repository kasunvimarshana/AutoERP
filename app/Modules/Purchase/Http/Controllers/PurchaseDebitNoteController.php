<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Database\Eloquent\Builder;
use Modules\Invoice\Models\Invoice;
use Modules\Purchase\Enums\PurchaseDebitNoteStatus;
use Modules\Purchase\Http\Controllers\Concerns\ScopesPurchaseRequests;
use Modules\Purchase\Http\Requests\AllocatePurchaseDebitNoteRequest;
use Modules\Purchase\Http\Requests\ListPurchaseDocumentRequest;
use Modules\Purchase\Http\Requests\PurchaseActionRequest;
use Modules\Purchase\Http\Requests\StorePurchaseDebitNoteRequest;
use Modules\Purchase\Http\Resources\PurchaseDebitNoteResource;
use Modules\Purchase\Models\PurchaseDebitNote;
use Modules\Purchase\Services\PurchaseAuthorizationService;
use Modules\Purchase\Services\PurchaseDebitNoteService;

final class PurchaseDebitNoteController
{
    use ScopesPurchaseRequests;

    public function __construct(private readonly PurchaseAuthorizationService $authorization) {}

    public function index(ListPurchaseDocumentRequest $request): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::DEBIT_NOTES_VIEW);
        $this->assertAllowedStatus($request, PurchaseDebitNoteStatus::cases());

        $query = $this->scope(PurchaseDebitNote::query(), $request)->with(['supplier', 'purchaseReturn']);
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('debit_note_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function (Builder $supplier) use ($search): void {
                        $supplier->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('supplier_number', 'like', "%{$search}%");
                    });
            });
        }
        foreach (['status', 'supplier_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate('debit_note_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('debit_note_date', '<=', $request->input('date_to'));
        }

        return PurchaseDebitNoteResource::collection($query->latest('debit_note_date')->paginate($request->perPage()));
    }

    public function store(StorePurchaseDebitNoteRequest $request, PurchaseDebitNoteService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::DEBIT_NOTES_CREATE);

        return (new PurchaseDebitNoteResource($service->create($request->toData())->load(['supplier', 'purchaseReturn'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ListPurchaseDocumentRequest $request, int $debitNote): PurchaseDebitNoteResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::DEBIT_NOTES_VIEW);

        return new PurchaseDebitNoteResource($this->scope(PurchaseDebitNote::query(), $request)
            ->with(['supplier', 'purchaseReturn'])
            ->findOrFail($debitNote));
    }

    public function approve(
        PurchaseActionRequest $request,
        int $debitNote,
        PurchaseDebitNoteService $service,
    ): PurchaseDebitNoteResource {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::DEBIT_NOTES_APPROVE);

        return new PurchaseDebitNoteResource(
            $service->approve(
                $this->find($request, $debitNote),
                $request->currentUserId(),
            )->load(['supplier', 'purchaseReturn']),
        );
    }

    public function post(
        PurchaseActionRequest $request,
        int $debitNote,
        PurchaseDebitNoteService $service,
    ): PurchaseDebitNoteResource {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::DEBIT_NOTES_POST);

        return new PurchaseDebitNoteResource(
            $service->post($this->find($request, $debitNote))
                ->load(['supplier', 'purchaseReturn']),
        );
    }

    public function allocate(
        AllocatePurchaseDebitNoteRequest $request,
        int $debitNote,
        PurchaseDebitNoteService $service,
    ): PurchaseDebitNoteResource {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::DEBIT_NOTES_ALLOCATE);

        $invoice = $this->scope(Invoice::query(), $request)
            ->findOrFail($request->invoiceId());

        return new PurchaseDebitNoteResource(
            $service->allocate(
                $this->find($request, $debitNote),
                $invoice,
                $request->amount(),
            )->load(['supplier', 'purchaseReturn']),
        );
    }

    private function find(
        PurchaseActionRequest|AllocatePurchaseDebitNoteRequest $request,
        int $debitNote,
    ): PurchaseDebitNote {
        return $this->scope(PurchaseDebitNote::query(), $request)->findOrFail($debitNote);
    }
}
