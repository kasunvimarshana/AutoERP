<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use Modules\Sales\Http\Requests\FastSalesRequest;
use Modules\Sales\Http\Resources\FastSalesResource;
use Modules\Sales\Services\FastSalesService;
use Modules\Sales\Services\SalesAuthorizationService;

final class FastSalesController
{
    public function __construct(private readonly SalesAuthorizationService $authorization) {}

    public function context(FastSalesRequest $request, FastSalesService $service): FastSalesResource
    {
        $this->authorization->assertAny($request->currentUserId(), $request->tenantId(), [
            SalesAuthorizationService::FAST_SALES_VIEW,
            SalesAuthorizationService::FAST_SALES_LOOKUPS,
            SalesAuthorizationService::FAST_SALES_EXECUTE,
        ]);

        return new FastSalesResource($service->context($request->payload()));
    }

    public function preview(FastSalesRequest $request, FastSalesService $service): FastSalesResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::FAST_SALES_VIEW);

        return new FastSalesResource($service->preview($request->payload()));
    }

    public function store(FastSalesRequest $request, FastSalesService $service): FastSalesResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::FAST_SALES_EXECUTE);

        return new FastSalesResource($service->create($request->payload()));
    }
}
