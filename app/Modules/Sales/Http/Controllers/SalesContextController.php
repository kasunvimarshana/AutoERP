<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Sales\Http\Requests\SalesContextRequest;
use Modules\Sales\Services\SalesAdjustmentCatalogueService;
use Modules\Sales\Services\SalesAuthorizationService;
use Modules\Sales\Services\SalesDocumentContextService;

final class SalesContextController
{
    public function __construct(private readonly SalesAuthorizationService $authorization) {}

    public function orderCreateContext(SalesContextRequest $request, SalesDocumentContextService $contexts): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::ORDERS_CREATE);

        return response()->json(['data' => $contexts->salesOrderCreateContext($request->tenantId(), $request->organizationUnitId())]);
    }

    public function itemContext(SalesContextRequest $request, int $item, SalesDocumentContextService $contexts): JsonResponse
    {
        $this->assertLookupAccess($request);

        return response()->json(['data' => $contexts->itemSalesContext(
            $request->tenantId(),
            $request->organizationUnitId(),
            $item,
            $request->filled('item_variant_id') ? (int) $request->input('item_variant_id') : null,
            $request->filled('currency_id') ? (int) $request->input('currency_id') : null,
            $request->filled('sales_date') ? (string) $request->input('sales_date') : null,
            $request->filled('uom_id') ? (int) $request->input('uom_id') : null,
        )]);
    }

    public function adjustmentCatalogue(SalesContextRequest $request, SalesAdjustmentCatalogueService $catalogue): JsonResponse
    {
        $this->assertLookupAccess($request);

        return response()->json(['data' => $catalogue->catalogue()]);
    }

    public function warehouses(SalesContextRequest $request, SalesDocumentContextService $contexts): JsonResponse
    {
        $this->authorization->assertAny($request->currentUserId(), $request->tenantId(), [
            SalesAuthorizationService::ORDERS_CREATE,
            SalesAuthorizationService::DELIVERIES_CREATE,
            SalesAuthorizationService::ALLOCATIONS_CREATE,
            SalesAuthorizationService::FAST_SALES_LOOKUPS,
        ]);

        return response()->json(['data' => $contexts->warehouses(
            $request->tenantId(),
            $request->organizationUnitId(),
            trim((string) $request->input('search', '')),
            $request->perPage(),
        )]);
    }

    public function warehouseLocations(SalesContextRequest $request, int $warehouse, SalesDocumentContextService $contexts): JsonResponse
    {
        $this->authorization->assertAny($request->currentUserId(), $request->tenantId(), [
            SalesAuthorizationService::ORDERS_CREATE,
            SalesAuthorizationService::DELIVERIES_CREATE,
            SalesAuthorizationService::ALLOCATIONS_CREATE,
            SalesAuthorizationService::FAST_SALES_LOOKUPS,
        ]);

        return response()->json(['data' => $contexts->warehouseLocations(
            $request->tenantId(),
            $request->organizationUnitId(),
            $warehouse,
            trim((string) $request->input('search', '')),
            $request->perPage(),
        )]);
    }

    public function taxGroups(SalesContextRequest $request, SalesDocumentContextService $contexts): JsonResponse
    {
        $this->authorization->assertAny($request->currentUserId(), $request->tenantId(), [
            SalesAuthorizationService::ORDERS_CREATE,
            SalesAuthorizationService::CUSTOMER_INVOICES_CREATE,
            SalesAuthorizationService::FAST_SALES_LOOKUPS,
        ]);

        return response()->json(['data' => $contexts->taxGroups(
            $request->tenantId(),
            $request->organizationUnitId(),
            trim((string) $request->input('search', '')),
            $request->perPage(),
        )]);
    }

    private function assertLookupAccess(SalesContextRequest $request): void
    {
        $this->authorization->assertAny($request->currentUserId(), $request->tenantId(), [
            SalesAuthorizationService::ORDERS_VIEW,
            SalesAuthorizationService::QUOTATIONS_VIEW,
            SalesAuthorizationService::FAST_SALES_LOOKUPS,
            SalesAuthorizationService::FAST_SALES_VIEW,
            SalesAuthorizationService::FAST_SALES_EXECUTE,
        ]);
    }
}
