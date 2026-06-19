<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Sales\Http\Controllers\Concerns\FiltersSalesQueries;
use Modules\Sales\Http\Controllers\Concerns\ScopesSalesRequests;
use Modules\Sales\Http\Requests\ConvertSalesQuotationRequest;
use Modules\Sales\Http\Requests\ListSalesRequest;
use Modules\Sales\Http\Requests\SalesActionRequest;
use Modules\Sales\Http\Requests\StoreSalesQuotationRequest;
use Modules\Sales\Http\Requests\UpdateSalesQuotationRequest;
use Modules\Sales\Http\Resources\SalesOrderResource;
use Modules\Sales\Http\Resources\SalesQuotationResource;
use Modules\Sales\Models\SalesQuotation;
use Modules\Sales\Services\SalesAuthorizationService;
use Modules\Sales\Services\SalesQuotationService;

final class SalesQuotationController
{
    use FiltersSalesQueries;
    use ScopesSalesRequests;

    public function __construct(private readonly SalesAuthorizationService $authorization) {}

    public function index(ListSalesRequest $request): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::QUOTATIONS_VIEW);

        $query = $this->scope(SalesQuotation::query(), $request)->with($this->relations());
        $this->applySalesFilters(
            $query,
            $request,
            'quotation_number',
            'quotation_date',
        );

        return SalesQuotationResource::collection($query->latest('quotation_date')->paginate($request->perPage()));
    }

    public function store(StoreSalesQuotationRequest $request, SalesQuotationService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::QUOTATIONS_CREATE);

        return (new SalesQuotationResource($service->create($request->toData())))->response()->setStatusCode(201);
    }

    public function show(ListSalesRequest $request, int $quotation): SalesQuotationResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::QUOTATIONS_VIEW);

        return new SalesQuotationResource($this->scope(SalesQuotation::query(), $request)->with($this->relations())->findOrFail($quotation));
    }

    public function update(UpdateSalesQuotationRequest $request, int $quotation, SalesQuotationService $service): SalesQuotationResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::QUOTATIONS_UPDATE);

        return new SalesQuotationResource($service->update($this->scope(SalesQuotation::query(), $request)->findOrFail($quotation), $request->toData()));
    }

    public function destroy(SalesActionRequest $request, int $quotation, SalesQuotationService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::QUOTATIONS_DELETE);

        $service->delete($this->scope(SalesQuotation::query(), $request)->findOrFail($quotation));

        return response()->json(status: 204);
    }

    public function send(SalesActionRequest $request, int $quotation, SalesQuotationService $service): SalesQuotationResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::QUOTATIONS_SEND);

        return new SalesQuotationResource($service->send($this->scope(SalesQuotation::query(), $request)->findOrFail($quotation), $request->currentUserId()));
    }

    public function accept(SalesActionRequest $request, int $quotation, SalesQuotationService $service): SalesQuotationResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::QUOTATIONS_ACCEPT);

        return new SalesQuotationResource($service->accept($this->scope(SalesQuotation::query(), $request)->findOrFail($quotation), $request->currentUserId()));
    }

    public function reject(SalesActionRequest $request, int $quotation, SalesQuotationService $service): SalesQuotationResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::QUOTATIONS_REJECT);

        return new SalesQuotationResource($service->reject(
            $this->scope(SalesQuotation::query(), $request)->findOrFail($quotation),
            $request->currentUserId(),
            $request->filled('reason') ? (string) $request->input('reason') : null,
        ));
    }

    public function convert(ConvertSalesQuotationRequest $request, int $quotation, SalesQuotationService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::QUOTATIONS_CONVERT);

        $order = $service->convertToOrder(
            $this->scope(SalesQuotation::query(), $request)->findOrFail($quotation),
            $request->filled('sales_order_date') ? (string) $request->input('sales_order_date') : null,
            $request->filled('warehouse_id') ? (int) $request->input('warehouse_id') : null,
            $request->filled('warehouse_location_id') ? (int) $request->input('warehouse_location_id') : null,
            $request->currentUserId(),
        );

        return (new SalesOrderResource($order))->response()->setStatusCode(201);
    }

    private function relations(): array
    {
        return ['customer.creditProfile', 'currency', 'lines.item', 'lines.variant', 'lines.uom', 'adjustments'];
    }
}
