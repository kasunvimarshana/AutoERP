<?php

declare(strict_types=1);

namespace Modules\Selling\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Selling\Http\Requests\StoreSaleRequest;
use Modules\Selling\Http\Requests\StoreSaleReturnRequest;
use Modules\Selling\Http\Resources\SaleResource;
use Modules\Selling\Models\Sale;
use Modules\Selling\Services\SalePostingService;

final class SaleController
{
    public function index(TenantScopedRequest $request): AnonymousResourceCollection
    {
        $query = Sale::query()
            ->with(['customer', 'warehouse', 'lines.item', 'lines.uom', 'lines.variant', 'lines.batch', 'lines.serialNumber', 'invoice.balance', 'returns.lines'])
            ->where('tenant_id', $request->tenantId())
            ->where('organization_unit_id', $request->organizationUnitId())
            ->latest('sale_date')
            ->latest('id');

        return SaleResource::collection($query->paginate(25));
    }

    public function show(TenantScopedRequest $request, int $sale): SaleResource
    {
        $model = Sale::query()
            ->with(['customer', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'lines.batch', 'lines.serialNumber', 'invoice.balance', 'returns.lines'])
            ->where('tenant_id', $request->tenantId())
            ->where('organization_unit_id', $request->organizationUnitId())
            ->findOrFail($sale);

        return new SaleResource($model);
    }

    public function store(StoreSaleRequest $request, SalePostingService $service): SaleResource
    {
        $sale = $service->create($request->payload(), (string) $request->header('Idempotency-Key'));

        return new SaleResource($sale);
    }

    public function storeReturn(StoreSaleReturnRequest $request, SaleReturnPostingService $service): SaleResource
    {
        $sale = $service->create($request->payload(), (string) $request->header('Idempotency-Key'));

        return new SaleResource($sale);
    }
}
