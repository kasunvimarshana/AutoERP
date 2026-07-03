<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Database\Eloquent\Builder;
use Modules\Invoice\Models\Invoice;
use Modules\Purchase\Constants\PurchaseAuditEvent;
use Modules\Purchase\Enums\PurchaseDebitNoteStatus;
use Modules\Purchase\Http\Controllers\Concerns\ScopesPurchaseRequests;
use Modules\Purchase\Http\Requests\AllocatePurchaseDebitNoteRequest;
use Modules\Purchase\Http\Requests\ListPurchaseDocumentRequest;
use Modules\Purchase\Http\Requests\PurchaseActionRequest;
use Modules\Purchase\Http\Requests\StorePurchaseDebitNoteRequest;
use Modules\Purchase\Http\Resources\PurchaseDebitNoteResource;
use Modules\Purchase\Models\PurchaseDebitNote;
use Modules\Purchase\Services\PurchaseAuthorizationService;
use Modules\Purchase\Services\PurchaseAuditService;
use Modules\Purchase\Services\PurchaseDebitNoteService;
use Modules\Purchase\Services\PurchaseDocumentPresentationService;

final class PurchaseDebitNoteController
{
    use ScopesPurchaseRequests;

    public function __construct(
        private readonly PurchaseAuthorizationService $authorization,
        private readonly PurchaseDocumentPresentationService $presentation,
        private readonly PurchaseAuditService $audit,
    ) {}

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
        if ($request->filled('allocation_status')) {
            match ((string) $request->input('allocation_status')) {
                'unallocated' => $query->whereRaw('allocated_amount <= 0'),
                'partially_allocated' => $query->whereRaw('allocated_amount > 0')->whereRaw('remaining_amount > 0'),
                'allocated' => $query->whereRaw('allocated_amount > 0')->whereRaw('remaining_amount <= 0'),
                default => null,
            };
        }
        if ($request->filled('date_from')) {
            $query->whereDate('debit_note_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('debit_note_date', '<=', $request->input('date_to'));
        }

        $notes = $query->latest('debit_note_date')->paginate($request->perPage());
        $this->presentation->preparePurchaseDebitNotes($notes->getCollection());

        return PurchaseDebitNoteResource::collection($notes);
    }

    public function store(StorePurchaseDebitNoteRequest $request, PurchaseDebitNoteService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::DEBIT_NOTES_CREATE);

        $note = $service->create($request->toData())->load(['supplier', 'purchaseReturn']);
        $this->audit->recordDocumentEvent(PurchaseAuditEvent::PURCHASE_DEBIT_NOTE_CREATED, 'purchase_debit_note', $note);

        return (new PurchaseDebitNoteResource($this->presentation->preparePurchaseDebitNote($note)))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ListPurchaseDocumentRequest $request, int $debitNote): PurchaseDebitNoteResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::DEBIT_NOTES_VIEW);

        $note = $this->scope(PurchaseDebitNote::query(), $request)
            ->with(['supplier', 'purchaseReturn'])
            ->findOrFail($debitNote);

        return new PurchaseDebitNoteResource($this->presentation->preparePurchaseDebitNote($note));
    }

    public function approve(
        PurchaseActionRequest $request,
        int $debitNote,
        PurchaseDebitNoteService $service,
    ): PurchaseDebitNoteResource {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::DEBIT_NOTES_APPROVE);

        $model = $this->find($request, $debitNote);
        $before = $model->attributesToArray();
        $updated = $service->approve(
                $model,
                $request->currentUserId(),
                $request->expectedVersion(),
            )->load(['supplier', 'purchaseReturn']);
        $this->audit->recordDocumentEvent(PurchaseAuditEvent::PURCHASE_DEBIT_NOTE_APPROVED, 'purchase_debit_note', $updated, $before);

        return new PurchaseDebitNoteResource($this->presentation->preparePurchaseDebitNote($updated));
    }

    public function post(
        PurchaseActionRequest $request,
        int $debitNote,
        PurchaseDebitNoteService $service,
    ): PurchaseDebitNoteResource {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::DEBIT_NOTES_POST);

        $model = $this->find($request, $debitNote);
        $before = $model->attributesToArray();
        $updated = $service->post($model, $request->expectedVersion())
            ->load(['supplier', 'purchaseReturn']);
        $this->audit->recordDocumentEvent(PurchaseAuditEvent::PURCHASE_DEBIT_NOTE_POSTED, 'purchase_debit_note', $updated, $before);

        return new PurchaseDebitNoteResource($this->presentation->preparePurchaseDebitNote($updated));
    }

    public function allocate(
        AllocatePurchaseDebitNoteRequest $request,
        int $debitNote,
        PurchaseDebitNoteService $service,
    ): PurchaseDebitNoteResource {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::DEBIT_NOTES_ALLOCATE);

        $invoice = $this->scope(Invoice::query(), $request)
            ->findOrFail($request->invoiceId());

        $model = $this->find($request, $debitNote);
        $before = $model->attributesToArray();
        $updated = $service->allocate(
                $model,
                $invoice,
                $request->amount(),
                $request->expectedVersion(),
            )->load(['supplier', 'purchaseReturn']);
        $this->audit->recordDocumentEvent(PurchaseAuditEvent::PURCHASE_DEBIT_NOTE_ALLOCATED, 'purchase_debit_note', $updated, $before, [
            'invoice_id' => $invoice->getKey(),
            'amount' => $request->amount(),
        ]);

        return new PurchaseDebitNoteResource($this->presentation->preparePurchaseDebitNote($updated));
    }

    private function find(
        PurchaseActionRequest|AllocatePurchaseDebitNoteRequest $request,
        int $debitNote,
    ): PurchaseDebitNote {
        return $this->scope(PurchaseDebitNote::query(), $request)->findOrFail($debitNote);
    }
}
