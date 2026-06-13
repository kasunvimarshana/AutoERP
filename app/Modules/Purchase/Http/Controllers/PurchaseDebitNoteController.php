<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Invoice\Models\Invoice;
use Modules\Purchase\Http\Controllers\Concerns\ScopesPurchaseRequests;
use Modules\Purchase\Http\Requests\AllocatePurchaseDebitNoteRequest;
use Modules\Purchase\Http\Requests\ListPurchaseDocumentRequest;
use Modules\Purchase\Http\Requests\PurchaseActionRequest;
use Modules\Purchase\Http\Requests\StorePurchaseDebitNoteRequest;
use Modules\Purchase\Http\Resources\PurchaseDebitNoteResource;
use Modules\Purchase\Models\PurchaseDebitNote;
use Modules\Purchase\Services\PurchaseDebitNoteService;

final class PurchaseDebitNoteController
{
    use ScopesPurchaseRequests;

    public function index(ListPurchaseDocumentRequest $request): AnonymousResourceCollection
    {
        return PurchaseDebitNoteResource::collection($this->scope(PurchaseDebitNote::query(), $request)
            ->with(['supplier', 'purchaseReturn'])
            ->latest('debit_note_date')
            ->paginate($request->perPage()));
    }

    public function store(StorePurchaseDebitNoteRequest $request, PurchaseDebitNoteService $service): JsonResponse
    {
        return (new PurchaseDebitNoteResource($service->create($request->toData())->load(['supplier', 'purchaseReturn'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ListPurchaseDocumentRequest $request, int $debitNote): PurchaseDebitNoteResource
    {
        return new PurchaseDebitNoteResource($this->scope(PurchaseDebitNote::query(), $request)
            ->with(['supplier', 'purchaseReturn'])
            ->findOrFail($debitNote));
    }

    public function approve(
        PurchaseActionRequest $request,
        int $debitNote,
        PurchaseDebitNoteService $service,
    ): PurchaseDebitNoteResource {
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
