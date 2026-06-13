<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Sales\Http\Controllers\Concerns\FiltersSalesQueries;
use Modules\Sales\Http\Controllers\Concerns\ScopesSalesRequests;
use Modules\Sales\Http\Requests\ListSalesRequest;
use Modules\Sales\Http\Requests\StoreSalesCreditNoteRequest;
use Modules\Sales\Http\Resources\SalesCreditNoteResource;
use Modules\Sales\Models\SalesCreditNote;
use Modules\Sales\Services\SalesCreditNoteService;

final class SalesCreditNoteController
{
    use ScopesSalesRequests;
    use FiltersSalesQueries;

    public function index(ListSalesRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(SalesCreditNote::query(), $request)->with(['customer', 'salesReturn']);
        $this->applySalesFilters(
            $query,
            $request,
            'credit_note_number',
            'credit_note_date',
        );

        return SalesCreditNoteResource::collection($query->latest('credit_note_date')->paginate($request->perPage()));
    }

    public function store(StoreSalesCreditNoteRequest $request, SalesCreditNoteService $service): JsonResponse
    {
        return (new SalesCreditNoteResource($service->create($request->toData())->load(['customer', 'salesReturn'])))->response()->setStatusCode(201);
    }

    public function show(ListSalesRequest $request, int $creditNote): SalesCreditNoteResource
    {
        return new SalesCreditNoteResource($this->scope(SalesCreditNote::query(), $request)->with(['customer', 'salesReturn'])->findOrFail($creditNote));
    }
}
