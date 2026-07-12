<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Controllers;

use Dompdf\Dompdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Modules\Core\Contracts\TenantExecutionContextInterface;
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
use Modules\Invoice\Services\InvoicePrintService;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\Invoice\Services\ManualInvoiceService;

final class InvoiceController
{
    public function __construct(private readonly InvoicePrintService $prints) {}

    public function index(
        ListInvoiceRequest $request,
        InvoiceStatusService $statuses,
    ): AnonymousResourceCollection {
        $query = $this->scope(Invoice::query(), $request);
        if ($request->filled('search')) {
            $query->where('invoice_number', 'like', '%'.trim((string) $request->input('search')).'%');
        }
        foreach (['invoice_type', 'direction', 'status', 'party_id', 'currency_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        if ($request->filled('balance_status')) {
            $query->whereHas('balance', fn (Builder $scope): Builder => $scope->where('status', $request->input('balance_status')));
        }
        if ($request->boolean('settlement_eligible')) {
            $query
                ->whereIn('status', $statuses->settlementStatuses())
                ->whereHas(
                    'balance',
                    fn (Builder $scope): Builder => $scope->where('remaining_amount', '>', 0),
                );
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
            ->with(['lines', 'sources'])
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

    public function printView(Request $request, int $invoice): View|Response
    {
        $model = $this->prints->findScoped(
            $invoice,
            $this->currentTenantId($request),
            $this->currentOrganizationUnitId($request),
        );

        if ($model === null) {
            return $this->notFound($invoice);
        }

        return view('invoice.print', $this->prints->viewData(
            $model,
            $this->scopedPdfUrl($request, $model),
        ));
    }

    public function pdf(Request $request, int $invoice): Response
    {
        $model = $this->prints->findScoped(
            $invoice,
            $this->currentTenantId($request),
            $this->currentOrganizationUnitId($request),
        );

        if ($model === null) {
            return $this->notFound($invoice);
        }

        return $this->pdfResponse($model);
    }

    public function signedPrintLink(Request $request, int $invoice): JsonResponse
    {
        $tenantId = $this->currentTenantId($request);
        $organizationUnitId = $this->currentOrganizationUnitId($request);
        $model = $this->prints->findScoped($invoice, $tenantId, $organizationUnitId);

        if ($model === null) {
            abort(404);
        }

        $routeParameters = $this->publicRouteParameters($model);
        $printUrl = URL::temporarySignedRoute(
            'invoices.public.print',
            now()->addMinutes(InvoicePrintService::SIGNED_URL_TTL_MINUTES),
            $routeParameters,
        );
        $pdfUrl = URL::temporarySignedRoute(
            'invoices.public.pdf',
            now()->addMinutes(InvoicePrintService::SIGNED_URL_TTL_MINUTES),
            $routeParameters,
        );

        return response()->json(['data' => ['print_url' => $printUrl, 'pdf_url' => $pdfUrl]]);
    }

    public function publicPrint(Request $request, int $invoice, int $tenant, TenantExecutionContextInterface $executionContext): View|Response
    {
        $organizationUnitId = $this->signedOrganizationUnitId($request);
        $model = $executionContext->runForTenant(
            $tenant,
            fn (): ?Invoice => $this->prints->findScoped($invoice, $tenant, $organizationUnitId),
        );
        if ($model === null) {
            return $this->notFound($invoice);
        }

        return view('invoice.print', $this->prints->viewData(
            $model,
            URL::temporarySignedRoute(
                'invoices.public.pdf',
                now()->addMinutes(InvoicePrintService::SIGNED_URL_TTL_MINUTES),
                $this->publicRouteParameters($model),
            ),
        ));
    }

    public function publicPdf(Request $request, int $invoice, int $tenant, TenantExecutionContextInterface $executionContext): Response
    {
        $organizationUnitId = $this->signedOrganizationUnitId($request);
        $model = $executionContext->runForTenant(
            $tenant,
            fn (): ?Invoice => $this->prints->findScoped($invoice, $tenant, $organizationUnitId),
        );
        if ($model === null) {
            return $this->notFound($invoice);
        }

        return $this->pdfResponse($model);
    }

    private function pdfResponse(Invoice $invoice): Response
    {
        $html = view('invoice.print', $this->prints->viewData($invoice, mode: 'pdf'))->render();

        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper(InvoicePrintService::PDF_PAPER_SIZE, InvoicePrintService::PDF_ORIENTATION);
        $dompdf->render();

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$this->prints->filename($invoice).'"');
    }

    private function scopedPdfUrl(Request $request, Invoice $invoice): string
    {
        $route = $request->routeIs('invoice.print') ? 'invoice.pdf' : 'invoices.pdf';

        return route($route, ['invoice' => (int) $invoice->getKey()]);
    }

    /**
     * @return array<string, int>
     */
    private function publicRouteParameters(Invoice $invoice): array
    {
        $parameters = [
            'invoice' => (int) $invoice->getKey(),
            'tenant' => (int) $invoice->tenant_id,
        ];

        if ($invoice->organization_unit_id !== null) {
            $parameters['organization_unit'] = (int) $invoice->organization_unit_id;
        }

        return $parameters;
    }

    private function currentTenantId(Request $request): int
    {
        $value = $request->attributes->get((string) config('core.current_tenant.id_attribute', 'current_tenant_id'));
        if (! is_numeric($value) || (int) $value < 1) {
            abort(404);
        }

        return (int) $value;
    }

    private function currentOrganizationUnitId(Request $request): ?int
    {
        $value = $request->attributes->get((string) config('core.current_organization_unit.id_attribute', 'current_organization_unit_id'));

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function signedOrganizationUnitId(Request $request): ?int
    {
        $value = $request->query('organization_unit');

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function notFound(int $invoice): Response
    {
        return response(view('invoice.notfound', ['id' => $invoice]), 404);
    }
}
