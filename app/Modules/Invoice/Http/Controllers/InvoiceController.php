<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
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
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\Invoice\Services\ManualInvoiceService;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Modules\Core\Contracts\TenantExecutionContextInterface;

final class InvoiceController
{
    public function index(ListInvoiceRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(Invoice::query(), $request);
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
        return new InvoiceResource($this->scope(Invoice::query(), $request)
            ->with('lines')
            ->findOrFail($invoice));
    }

    public function preview(StoreInvoiceRequest $request, ManualInvoiceService $service): JsonResponse
    {
        return response()->json(['data' => get_object_vars($service->preview($request->toData()))]);
    }

    public function store(StoreInvoiceRequest $request, ManualInvoiceService $service): InvoiceResource
    {
        return new InvoiceResource(
            $service->create($request->toData(), $request->idempotencyKey()),
        );
    }

    public function approve(InvoiceActionRequest $request, int $invoice, InvoiceStatusService $service): InvoiceResource
    {
        return new InvoiceResource($service->transitionIfVersion(
            $this->find($request, $invoice),
            InvoiceStatus::Approved,
            $request->expectedVersion(),
            $request->currentUserId(),
        ));
    }

    public function post(InvoiceActionRequest $request, int $invoice, InvoiceStatusService $service): InvoiceResource
    {
        return new InvoiceResource($service->transitionIfVersion(
            $this->find($request, $invoice),
            InvoiceStatus::Posted,
            $request->expectedVersion(),
            $request->currentUserId(),
        ));
    }

    public function cancel(
        InvoiceActionRequest $request,
        int $invoice,
        InvoiceStatusService $statuses,
        InvoiceBalanceService $balances,
    ): InvoiceResource {
        $model = DB::transaction(function () use (
            $request,
            $invoice,
            $statuses,
            $balances,
        ): Invoice {
            $model = $statuses->transitionIfVersion(
                $this->find($request, $invoice),
                InvoiceStatus::Cancelled,
                $request->expectedVersion(),
                $request->currentUserId(),
                $request->reason(),
            );
            $balances->cancel($model);

            return $model;
        });

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

    public function printView(int $invoice): View
    {
        $model = Invoice::with('lines')->find($invoice);

        if ($model === null) {
            return view('invoice.notfound', ['id' => $invoice]);
        }

        return view('invoice.print', ['invoice' => $model]);
    }

    // API: generate a temporary signed public print URL (called from SPA)
    public function signedPrintLink(Request $request, int $invoice): JsonResponse
    {
        $tenantId = $request->attributes->get(config('core.current_tenant.id_attribute', 'current_tenant_id'));
        $printUrl = URL::temporarySignedRoute(
            'invoices.public.print',
            now()->addMinutes(15),
            ['invoice' => $invoice, 'tenant' => $tenantId],
        );
        $pdfUrl = URL::temporarySignedRoute(
            'invoices.public.pdf',
            now()->addMinutes(15),
            ['invoice' => $invoice, 'tenant' => $tenantId],
        );

        return response()->json(['data' => ['print_url' => $printUrl, 'pdf_url' => $pdfUrl]]);
    }

    // Public signed route: render print view when valid signed URL is provided
    public function publicPrint(Request $request, int $invoice, int $tenant, TenantExecutionContextInterface $executionContext): View
    {
        $model = $executionContext->runForTenant(
            $tenant,
            fn (): ?Invoice => Invoice::query()->with('lines')->find($invoice),
        );
        if ($model === null) {
            return view('invoice.notfound', ['id' => $invoice]);
        }

        return view('invoice.print', ['invoice' => $model]);
    }
}
