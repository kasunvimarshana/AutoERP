<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Http\Requests\InvoiceActionRequest;
use Modules\Invoice\Http\Requests\ListInvoiceRequest;
use Modules\Invoice\Http\Requests\StoreInvoiceRequest;
use Modules\Invoice\Http\Resources\InvoiceAdjustmentResource;
use Modules\Invoice\Http\Resources\InvoiceResource;
use Modules\Invoice\Http\Resources\InvoiceSourceLineResource;
use Modules\Invoice\Http\Resources\InvoiceSourceResource;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceBalanceService;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoiceStatusService;

final class InvoiceController
{
    public function index(ListInvoiceRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(Invoice::query(), $request)->with([
            'balance',
            'currency',
            'customer',
            'supplier',
        ]);
        if ($request->filled('search')) {
            $query->where('invoice_number', 'like', '%'.trim((string) $request->input('search')).'%');
        }
        foreach (['invoice_type', 'direction', 'status', 'party_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        if ($request->filled('balance_status')) {
            $query->whereHas('balance', fn (Builder $scope): Builder => $scope->where('status', $request->input('balance_status')));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->input('date_to'));
        }

        return InvoiceResource::collection($query->latest('invoice_date')->paginate($request->perPage()));
    }

    public function show(ListInvoiceRequest $request, int $invoice): InvoiceResource
    {
        return new InvoiceResource($this->scope(Invoice::query(), $request)->with([
            'currency', 'customer', 'supplier', 'lines.item', 'lines.uom', 'sources',
            'sourceLines', 'adjustments.allocations',
            'adjustmentAllocations', 'balance', 'creditAllocations',
        ])->findOrFail($invoice));
    }

    public function preview(StoreInvoiceRequest $request, InvoiceCreationService $service): JsonResponse
    {
        return response()->json(['data' => get_object_vars($service->preview($request->toData()))]);
    }

    public function store(StoreInvoiceRequest $request, InvoiceCreationService $service): InvoiceResource
    {
        return new InvoiceResource(
            $service->create($request->toData())->loadMissing(['currency', 'customer', 'supplier']),
        );
    }

    public function approve(InvoiceActionRequest $request, int $invoice, InvoiceStatusService $service): InvoiceResource
    {
        return new InvoiceResource($service->transition($this->find($request, $invoice), InvoiceStatus::Approved));
    }

    public function post(InvoiceActionRequest $request, int $invoice, InvoiceStatusService $service): InvoiceResource
    {
        return new InvoiceResource($service->transition($this->find($request, $invoice), InvoiceStatus::Posted));
    }

    public function cancel(
        InvoiceActionRequest $request,
        int $invoice,
        InvoiceStatusService $statuses,
        InvoiceBalanceService $balances,
    ): InvoiceResource {
        $model = $statuses->transition($this->find($request, $invoice), InvoiceStatus::Cancelled);
        $balances->cancel($model);

        return new InvoiceResource($model->refresh()->load('balance'));
    }

    public function balance(InvoiceActionRequest $request, int $invoice, InvoiceBalanceService $service): JsonResponse
    {
        $balance = $this->find($request, $invoice)->balance()->firstOrFail();

        return response()->json(['data' => get_object_vars($service->result($balance))]);
    }

    public function sources(InvoiceActionRequest $request, int $invoice): JsonResponse
    {
        $model = $this->find($request, $invoice);

        return response()->json([
            'data' => [
                'sources' => InvoiceSourceResource::collection($model->sources()->get())
                    ->resolve($request),
                'source_lines' => InvoiceSourceLineResource::collection(
                    $model->sourceLines()->get(),
                )->resolve($request),
            ],
        ]);
    }

    public function adjustments(InvoiceActionRequest $request, int $invoice): JsonResponse
    {
        $adjustments = $this->find($request, $invoice)
            ->adjustments()
            ->with('allocations')
            ->get();

        return response()->json([
            'data' => InvoiceAdjustmentResource::collection($adjustments)->resolve($request),
        ]);
    }

    private function find(InvoiceActionRequest $request, int $invoice): Invoice
    {
        return $this->scope(Invoice::query(), $request)->findOrFail($invoice);
    }

    private function scope(Builder $query, ListInvoiceRequest|InvoiceActionRequest $request): Builder
    {
        $query->where('tenant_id', $request->tenantId());

        return $request->organizationUnitId() === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $request->organizationUnitId());
    }
}
