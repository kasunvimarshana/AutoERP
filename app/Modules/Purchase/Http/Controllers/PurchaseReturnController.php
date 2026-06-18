<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Database\Eloquent\Builder;
use Modules\Purchase\Enums\PurchaseReturnStatus;
use Modules\Purchase\Http\Controllers\Concerns\ScopesPurchaseRequests;
use Modules\Purchase\Http\Requests\ListPurchaseDocumentRequest;
use Modules\Purchase\Http\Requests\PurchaseActionRequest;
use Modules\Purchase\Http\Requests\StoreManualSupplierReturnRequest;
use Modules\Purchase\Http\Requests\StorePurchaseReturnRequest;
use Modules\Purchase\Http\Resources\PurchaseReturnResource;
use Modules\Purchase\Models\PurchaseReturn;
use Modules\Purchase\Services\PurchaseAuthorizationService;
use Modules\Purchase\Services\PurchaseReturnService;

final class PurchaseReturnController
{
    use ScopesPurchaseRequests;

    public function __construct(private readonly PurchaseAuthorizationService $authorization) {}

    public function index(ListPurchaseDocumentRequest $request): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::RETURNS_VIEW);
        $this->assertAllowedStatus($request, PurchaseReturnStatus::cases());

        $query = $this->scope(PurchaseReturn::query(), $request)->with($this->relations());
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('return_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function (Builder $supplier) use ($search): void {
                        $supplier->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('supplier_number', 'like', "%{$search}%");
                    });
            });
        }
        foreach (['status', 'supplier_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate('return_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('return_date', '<=', $request->input('date_to'));
        }

        return PurchaseReturnResource::collection($query->latest('return_date')->paginate($request->perPage()));
    }

    public function store(StorePurchaseReturnRequest $request, PurchaseReturnService $service): PurchaseReturnResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::RETURNS_CREATE);

        return new PurchaseReturnResource($service->create($request->toData())->load($this->relations()));
    }

    public function show(ListPurchaseDocumentRequest $request, int $return): PurchaseReturnResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::RETURNS_VIEW);

        return new PurchaseReturnResource($this->scope(PurchaseReturn::query(), $request)
            ->with($this->relations())
            ->findOrFail($return));
    }

    public function approve(PurchaseActionRequest $request, int $return, PurchaseReturnService $service): PurchaseReturnResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::RETURNS_APPROVE);

        return new PurchaseReturnResource($service->approve($this->scope(PurchaseReturn::query(), $request)->findOrFail($return), $request->currentUserId())
            ->load($this->relations()));
    }

    public function post(PurchaseActionRequest $request, int $return, PurchaseReturnService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::RETURNS_POST);

        $model = $this->scope(PurchaseReturn::query(), $request)->with('lines')->findOrFail($return);

        return response()->json(['data' => get_object_vars($service->post($model, $request->currentUserId()))]);
    }

    public function cancel(PurchaseActionRequest $request, int $return, PurchaseReturnService $service): PurchaseReturnResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::RETURNS_CANCEL);

        return new PurchaseReturnResource($service->cancel($this->scope(PurchaseReturn::query(), $request)->findOrFail($return))
            ->load($this->relations()));
    }

    public function manualSupplierReturn(StoreManualSupplierReturnRequest $request, PurchaseReturnService $service): PurchaseReturnResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::RETURNS_CREATE_MANUAL);

        return new PurchaseReturnResource($service->create($request->toData())->load($this->relations()));
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
