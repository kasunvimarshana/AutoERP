<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Purchase\Http\Requests\PurchaseContextRequest;
use Modules\Purchase\Services\PurchaseAdjustmentCatalogueService;
use Modules\Purchase\Services\PurchaseAuthorizationService;
use Modules\Purchase\Services\PurchaseDocumentContextService;

final class PurchaseContextController
{
    public function __construct(private readonly PurchaseAuthorizationService $authorization) {}

    public function orderCreateContext(PurchaseContextRequest $request, PurchaseDocumentContextService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_CREATE);

        return response()->json([
            'data' => $service->purchaseOrderCreateContext($request->tenantId(), $request->organizationUnitId()),
        ]);
    }

    public function supplierContext(PurchaseContextRequest $request, int $supplier, PurchaseDocumentContextService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_VIEW);

        return response()->json([
            'data' => $service->supplierContext($request->tenantId(), $request->organizationUnitId(), $supplier),
        ]);
    }

    public function itemContext(PurchaseContextRequest $request, int $item, PurchaseDocumentContextService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_VIEW);

        return response()->json([
            'data' => $service->itemPurchaseContext(
                $request->tenantId(),
                $request->organizationUnitId(),
                $item,
                $request->filled('supplier_id') ? (int) $request->input('supplier_id') : null,
                $request->filled('item_variant_id') ? (int) $request->input('item_variant_id') : null,
                $request->filled('currency_id') ? (int) $request->input('currency_id') : null,
            ),
        ]);
    }

    public function adjustmentCatalogue(PurchaseContextRequest $request, PurchaseAdjustmentCatalogueService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_VIEW);

        return response()->json(['data' => $service->catalogue()]);
    }

    public function warehouses(PurchaseContextRequest $request, PurchaseDocumentContextService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_VIEW);

        return response()->json([
            'data' => $service->warehouses(
                $request->tenantId(),
                $request->organizationUnitId(),
                trim((string) $request->input('search', '')),
                $request->perPage(),
            ),
        ]);
    }

    public function warehouseLocations(PurchaseContextRequest $request, int $warehouse, PurchaseDocumentContextService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_VIEW);

        return response()->json([
            'data' => $service->warehouseLocations(
                $request->tenantId(),
                $request->organizationUnitId(),
                $warehouse,
                trim((string) $request->input('search', '')),
                $request->perPage(),
            ),
        ]);
    }
}
