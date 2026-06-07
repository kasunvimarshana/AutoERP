<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Purchase\DTOs\CreatePurchaseReturnData;
use Modules\Purchase\Enums\PurchaseReturnType;
use Modules\Purchase\Http\Controllers\Concerns\ScopesPurchaseRequests;
use Modules\Purchase\Http\Requests\ListPurchaseOrderRequest;
use Modules\Purchase\Http\Requests\PurchaseActionRequest;
use Modules\Purchase\Http\Requests\StorePurchaseReturnRequest;
use Modules\Purchase\Http\Resources\PurchaseReturnResource;
use Modules\Purchase\Models\PurchaseReturn;
use Modules\Purchase\Services\PurchaseReturnService;

final class PurchaseReturnController
{
    use ScopesPurchaseRequests;

    public function index(ListPurchaseOrderRequest $request): AnonymousResourceCollection
    {
        return PurchaseReturnResource::collection($this->scope(PurchaseReturn::query(), $request)
            ->with($this->relations())
            ->latest('return_date')
            ->paginate($request->perPage()));
    }

    public function store(StorePurchaseReturnRequest $request, PurchaseReturnService $service): PurchaseReturnResource
    {
        return new PurchaseReturnResource($service->create($request->toData())->load($this->relations()));
    }

    public function show(ListPurchaseOrderRequest $request, int $return): PurchaseReturnResource
    {
        return new PurchaseReturnResource($this->scope(PurchaseReturn::query(), $request)
            ->with($this->relations())
            ->findOrFail($return));
    }

    public function approve(PurchaseActionRequest $request, int $return, PurchaseReturnService $service): PurchaseReturnResource
    {
        return new PurchaseReturnResource($service->approve($this->scope(PurchaseReturn::query(), $request)->findOrFail($return), $request->currentUserId())
            ->load($this->relations()));
    }

    public function post(PurchaseActionRequest $request, int $return, PurchaseReturnService $service): JsonResponse
    {
        $model = $this->scope(PurchaseReturn::query(), $request)->with('lines')->findOrFail($return);

        return response()->json(['data' => get_object_vars($service->post($model, $request->currentUserId()))]);
    }

    public function cancel(PurchaseActionRequest $request, int $return, PurchaseReturnService $service): PurchaseReturnResource
    {
        return new PurchaseReturnResource($service->cancel($this->scope(PurchaseReturn::query(), $request)->findOrFail($return))
            ->load($this->relations()));
    }

    public function manualSupplierReturn(StorePurchaseReturnRequest $request, PurchaseReturnService $service): PurchaseReturnResource
    {
        $data = $request->toData();

        return new PurchaseReturnResource($service->create(new CreatePurchaseReturnData(
            tenantId: $data->tenantId,
            returnDate: $data->returnDate,
            warehouseId: $data->warehouseId,
            organizationUnitId: $data->organizationUnitId,
            returnNumber: $data->returnNumber,
            warehouseLocationId: $data->warehouseLocationId,
            supplierType: $data->supplierType,
            supplierId: $data->supplierId,
            reason: $data->reason,
            returnType: PurchaseReturnType::ManualSupplierReturn,
            sourceType: 'manual_supplier_return',
            sourceId: $data->sourceId,
            approvalRequired: true,
            affectsSupplierBalance: $data->affectsSupplierBalance,
            costBasis: $data->costBasis,
            auditMetadata: $data->auditMetadata,
            createdBy: $data->createdBy,
            lines: $data->lines,
        ))->load($this->relations()));
    }

    private function relations(): array
    {
        return [
            'supplier',
            'warehouse',
            'warehouseLocation',
            'sourceGoodsReceipt',
            'debitNote',
            'lines.item',
            'lines.variant',
            'lines.uom',
            'adjustmentAllocations',
        ];
    }
}
