<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Sales\Http\Controllers\Concerns\ScopesSalesRequests;
use Modules\Sales\Http\Requests\ListSalesRequest;
use Modules\Sales\Http\Requests\SalesActionRequest;
use Modules\Sales\Http\Requests\StoreSalesReturnRequest;
use Modules\Sales\Http\Resources\SalesReturnResource;
use Modules\Sales\Models\SalesReturn;
use Modules\Sales\Services\SalesReturnService;

final class SalesReturnController
{
    use ScopesSalesRequests;

    public function index(ListSalesRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(SalesReturn::query(), $request)->with($this->relations());
        foreach (['status', 'customer_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        return SalesReturnResource::collection($query->latest('return_date')->paginate($request->perPage()));
    }

    public function store(StoreSalesReturnRequest $request, SalesReturnService $service): JsonResponse
    {
        return (new SalesReturnResource($service->create($request->toData())))->response()->setStatusCode(201);
    }

    public function show(ListSalesRequest $request, int $return): SalesReturnResource
    {
        return new SalesReturnResource($this->scope(SalesReturn::query(), $request)->with($this->relations())->findOrFail($return));
    }

    public function approve(SalesActionRequest $request, int $return, SalesReturnService $service): SalesReturnResource
    {
        return new SalesReturnResource($service->approve($this->scope(SalesReturn::query(), $request)->findOrFail($return), $request->currentUserId()));
    }

    public function post(SalesActionRequest $request, int $return, SalesReturnService $service): JsonResponse
    {
        return response()->json(['data' => get_object_vars($service->post(
            $this->scope(SalesReturn::query(), $request)->findOrFail($return),
            $request->currentUserId(),
        ))]);
    }

    public function cancel(SalesActionRequest $request, int $return, SalesReturnService $service): SalesReturnResource
    {
        return new SalesReturnResource($service->cancel($this->scope(SalesReturn::query(), $request)->findOrFail($return), $request->currentUserId()));
    }

    private function relations(): array
    {
        return ['customer', 'warehouse', 'warehouseLocation', 'replacementSalesOrder', 'creditNote', 'lines.item', 'lines.variant', 'lines.uom', 'adjustmentAllocations'];
    }
}
