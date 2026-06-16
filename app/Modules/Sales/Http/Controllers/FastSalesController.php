<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use Modules\Sales\Http\Requests\FastSalesRequest;
use Modules\Sales\Http\Resources\FastSalesResource;
use Modules\Sales\Services\FastSalesService;

final class FastSalesController
{
    public function context(FastSalesRequest $request, FastSalesService $service): FastSalesResource
    {
        return new FastSalesResource($service->context($request->payload()));
    }

    public function preview(FastSalesRequest $request, FastSalesService $service): FastSalesResource
    {
        return new FastSalesResource($service->preview($request->payload()));
    }

    public function store(FastSalesRequest $request, FastSalesService $service): FastSalesResource
    {
        return new FastSalesResource($service->create($request->payload()));
    }
}
