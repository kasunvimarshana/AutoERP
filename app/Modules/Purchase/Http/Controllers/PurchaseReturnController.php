<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Database\Eloquent\Builder;
use Modules\Purchase\Constants\PurchaseAuditEvent;
use Modules\Purchase\Enums\PurchaseReturnStatus;
use Modules\Purchase\Http\Controllers\Concerns\ScopesPurchaseRequests;
use Modules\Purchase\Http\Requests\ListPurchaseDocumentRequest;
use Modules\Purchase\Http\Requests\PurchaseActionRequest;
use Modules\Purchase\Http\Requests\StoreManualSupplierReturnRequest;
use Modules\Purchase\Http\Requests\StorePurchaseReturnRequest;
use Modules\Purchase\Http\Resources\PurchaseReturnResource;
use Modules\Purchase\Models\PurchaseReturn;
use Modules\Purchase\Services\PurchaseAuthorizationService;
use Modules\Purchase\Services\PurchaseAuditService;
use Modules\Purchase\Services\PurchaseDocumentPresentationService;
use Modules\Purchase\Services\PurchaseReturnService;

final class PurchaseReturnController
{
    use ScopesPurchaseRequests;

    public function __construct(
        private readonly PurchaseAuthorizationService $authorization,
        private readonly PurchaseDocumentPresentationService $presentation,
        private readonly PurchaseAuditService $audit,
    ) {}

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

        $returns = $query->latest('return_date')->paginate($request->perPage());
        $this->presentation->preparePurchaseReturns($returns->getCollection());

        return PurchaseReturnResource::collection($returns);
    }

    public function store(StorePurchaseReturnRequest $request, PurchaseReturnService $service): PurchaseReturnResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::RETURNS_CREATE);

        $return = $service->create($request->toData())->load($this->relations());
        $this->audit->recordDocumentEvent(PurchaseAuditEvent::PURCHASE_RETURN_CREATED, 'purchase_return', $return);

        return new PurchaseReturnResource($this->presentation->preparePurchaseReturn($return));
    }

    public function show(ListPurchaseDocumentRequest $request, int $return): PurchaseReturnResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::RETURNS_VIEW);

        $return = $this->scope(PurchaseReturn::query(), $request)
            ->with($this->relations())
            ->findOrFail($return);

        return new PurchaseReturnResource($this->presentation->preparePurchaseReturn($return));
    }

    public function approve(PurchaseActionRequest $request, int $return, PurchaseReturnService $service): PurchaseReturnResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::RETURNS_APPROVE);

        $model = $this->scope(PurchaseReturn::query(), $request)->findOrFail($return);
        $before = $model->attributesToArray();
        $updated = $service->approve(
            $model,
            $request->currentUserId(),
            $request->expectedVersion(),
        )
            ->load($this->relations());
        $this->audit->recordDocumentEvent(PurchaseAuditEvent::PURCHASE_RETURN_APPROVED, 'purchase_return', $updated, $before);

        return new PurchaseReturnResource($this->presentation->preparePurchaseReturn($updated));
    }

    public function post(PurchaseActionRequest $request, int $return, PurchaseReturnService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::RETURNS_POST);

        $model = $this->scope(PurchaseReturn::query(), $request)->with('lines')->findOrFail($return);

        $before = $model->attributesToArray();
        $result = $service->post(
            $model,
            $request->currentUserId(),
            $request->expectedVersion(),
        );
        $posted = $this->scope(PurchaseReturn::query(), $request)->findOrFail($return);
        $this->audit->recordDocumentEvent(PurchaseAuditEvent::PURCHASE_RETURN_POSTED, 'purchase_return', $posted, $before, [
            'debit_note_id' => $result->debitNoteId,
            'inventory_movement_ids' => $result->inventoryMovementIds,
        ]);

        return response()->json(['data' => [
            'purchase_return_id' => $result->documentId,
            'purchase_return_number' => $result->documentNumber,
            'status' => $result->status,
            'inventory_movement_ids' => $result->inventoryMovementIds,
            'debit_note_id' => $result->debitNoteId,
        ]]);
    }

    public function cancel(PurchaseActionRequest $request, int $return, PurchaseReturnService $service): PurchaseReturnResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::RETURNS_CANCEL);

        $model = $this->scope(PurchaseReturn::query(), $request)->findOrFail($return);
        $before = $model->attributesToArray();
        $updated = $service->cancel(
            $model,
            $request->expectedVersion(),
        )
            ->load($this->relations());
        $this->audit->recordDocumentEvent(PurchaseAuditEvent::PURCHASE_RETURN_CANCELLED, 'purchase_return', $updated, $before);

        return new PurchaseReturnResource($this->presentation->preparePurchaseReturn($updated));
    }

    public function manualSupplierReturn(StoreManualSupplierReturnRequest $request, PurchaseReturnService $service): PurchaseReturnResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::RETURNS_CREATE_MANUAL);

        $return = $service->create($request->toData())->load($this->relations());
        $this->audit->recordDocumentEvent(PurchaseAuditEvent::PURCHASE_RETURN_CREATED, 'purchase_return', $return);

        return new PurchaseReturnResource($this->presentation->preparePurchaseReturn($return));
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
