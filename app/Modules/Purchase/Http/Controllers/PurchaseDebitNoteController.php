<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Purchase\Http\Controllers\Concerns\ScopesPurchaseRequests;
use Modules\Purchase\Http\Requests\ListPurchaseOrderRequest;
use Modules\Purchase\Http\Requests\StorePurchaseDebitNoteRequest;
use Modules\Purchase\Http\Resources\PurchaseDebitNoteResource;
use Modules\Purchase\Models\PurchaseDebitNote;
use Modules\Purchase\Services\PurchaseDebitNoteService;

final class PurchaseDebitNoteController
{
    use ScopesPurchaseRequests;

    public function index(ListPurchaseOrderRequest $request): AnonymousResourceCollection
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

    public function show(ListPurchaseOrderRequest $request, int $debitNote): PurchaseDebitNoteResource
    {
        return new PurchaseDebitNoteResource($this->scope(PurchaseDebitNote::query(), $request)
            ->with(['supplier', 'purchaseReturn'])
            ->findOrFail($debitNote));
    }
}
