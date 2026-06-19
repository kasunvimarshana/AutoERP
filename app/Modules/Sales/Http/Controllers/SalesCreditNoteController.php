<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Invoice\Models\Invoice;
use Modules\Sales\Http\Controllers\Concerns\FiltersSalesQueries;
use Modules\Sales\Http\Controllers\Concerns\ScopesSalesRequests;
use Modules\Sales\Http\Requests\AllocateSalesCreditNoteRequest;
use Modules\Sales\Http\Requests\ListSalesRequest;
use Modules\Sales\Http\Requests\SalesActionRequest;
use Modules\Sales\Http\Requests\StoreSalesCreditNoteRequest;
use Modules\Sales\Http\Resources\SalesCreditNoteResource;
use Modules\Sales\Models\SalesCreditNote;
use Modules\Sales\Services\SalesAuthorizationService;
use Modules\Sales\Services\SalesCreditNoteService;

final class SalesCreditNoteController
{
    use FiltersSalesQueries;
    use ScopesSalesRequests;

    public function __construct(private readonly SalesAuthorizationService $authorization) {}

    public function index(ListSalesRequest $request): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::CREDIT_NOTES_VIEW);

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
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::CREDIT_NOTES_CREATE);

        return (new SalesCreditNoteResource($service->create($request->toData())->load(['customer', 'salesReturn'])))->response()->setStatusCode(201);
    }

    public function show(ListSalesRequest $request, int $creditNote): SalesCreditNoteResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::CREDIT_NOTES_VIEW);

        return new SalesCreditNoteResource($this->scope(SalesCreditNote::query(), $request)->with(['customer', 'salesReturn'])->findOrFail($creditNote));
    }

    public function approve(
        SalesActionRequest $request,
        int $creditNote,
        SalesCreditNoteService $service,
    ): SalesCreditNoteResource {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::CREDIT_NOTES_APPROVE);

        return new SalesCreditNoteResource(
            $service->approve($this->find($request, $creditNote))->load(['customer', 'salesReturn']),
        );
    }

    public function post(
        SalesActionRequest $request,
        int $creditNote,
        SalesCreditNoteService $service,
    ): SalesCreditNoteResource {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::CREDIT_NOTES_POST);

        return new SalesCreditNoteResource(
            $service->post($this->find($request, $creditNote))->load(['customer', 'salesReturn']),
        );
    }

    public function allocate(
        AllocateSalesCreditNoteRequest $request,
        int $creditNote,
        SalesCreditNoteService $service,
    ): SalesCreditNoteResource {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::CREDIT_NOTES_ALLOCATE);

        $invoice = $this->scope(Invoice::query(), $request)
            ->findOrFail($request->invoiceId());

        return new SalesCreditNoteResource(
            $service->allocate(
                $this->find($request, $creditNote),
                $invoice,
                $request->amount(),
            )->load(['customer', 'salesReturn']),
        );
    }

    private function find(
        SalesActionRequest|AllocateSalesCreditNoteRequest $request,
        int $creditNote,
    ): SalesCreditNote {
        return $this->scope(SalesCreditNote::query(), $request)->findOrFail($creditNote);
    }
}
