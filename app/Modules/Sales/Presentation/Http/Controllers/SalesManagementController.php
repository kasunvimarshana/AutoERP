<?php

declare(strict_types=1);

namespace Modules\Sales\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sales\Application\Contracts\Services\SalesManagementServiceInterface;
use Modules\Sales\Presentation\Http\Controllers\Concerns\RespondsWithSalesResult;

final class SalesManagementController extends Controller
{
    use RespondsWithSalesResult;

    public function __construct(private readonly SalesManagementServiceInterface $service) {}

    public function upsertSalesOrderWithLines(Request $request): JsonResponse
    {
        return $this->respond($this->service->upsertSalesOrderWithLines(null, $request->all()));
    }

    public function updateSalesOrderWithLines(Request $request, int $id): JsonResponse
    {
        return $this->respond($this->service->upsertSalesOrderWithLines($id, $request->all()));
    }

    public function syncSalesOrderLines(Request $request, int $id): JsonResponse
    {
        return $this->respond($this->service->syncSalesOrderLines($id, $request->all()));
    }

    public function upsertGdnWithLines(Request $request): JsonResponse
    {
        return $this->respond($this->service->upsertGdnWithLines(null, $request->all()));
    }

    public function updateGdnWithLines(Request $request, int $id): JsonResponse
    {
        return $this->respond($this->service->upsertGdnWithLines($id, $request->all()));
    }

    public function syncGdnLines(Request $request, int $id): JsonResponse
    {
        return $this->respond($this->service->syncGdnLines($id, $request->all()));
    }

    public function upsertSalesReturnWithLines(Request $request): JsonResponse
    {
        return $this->respond($this->service->upsertSalesReturnWithLines(null, $request->all()));
    }

    public function updateSalesReturnWithLines(Request $request, int $id): JsonResponse
    {
        return $this->respond($this->service->upsertSalesReturnWithLines($id, $request->all()));
    }

    public function syncSalesReturnLines(Request $request, int $id): JsonResponse
    {
        return $this->respond($this->service->syncSalesReturnLines($id, $request->all()));
    }

    public function statusHistory(Request $request, string $entityType, int $id): JsonResponse
    {
        $tenantId = (int) ($request->input('tenant_id', 0));

        return $this->respond($this->service->getStatusHistory($entityType, $id, $tenantId));
    }

    public function showSettings(Request $request): JsonResponse
    {
        $tenantId = (int) ($request->input('tenant_id', 0));
        $organizationUnitId = $request->has('organization_unit_id')
            ? (int) $request->input('organization_unit_id')
            : null;

        return $this->respond($this->service->getSalesSettings($tenantId, $organizationUnitId));
    }

    public function upsertSettings(Request $request): JsonResponse
    {
        return $this->respond($this->service->upsertSalesSettings($request->all()));
    }

    public function initializeSettings(Request $request): JsonResponse
    {
        return $this->respond($this->service->initializeSalesSettings($request->all()));
    }

    public function availableSalesOrderLinesForGdn(Request $request, int $salesOrderId): JsonResponse
    {
        $tenantId = (int) ($request->input('tenant_id', 0));

        return $this->respond($this->service->getAvailableSalesOrderLinesForGdn($tenantId, $salesOrderId));
    }

    public function availableGdnLinesForDocument(Request $request, int $gdnHeaderId): JsonResponse
    {
        $tenantId = (int) ($request->input('tenant_id', 0));

        return $this->respond($this->service->getAvailableGdnLinesForDocument($tenantId, $gdnHeaderId));
    }

    public function returnableLines(Request $request): JsonResponse
    {
        $tenantId = (int) ($request->input('tenant_id', 0));
        $sourceType = (string) $request->input('source_type', '');
        $sourceId = (int) $request->input('source_id', 0);

        return $this->respond($this->service->getReturnableLines($sourceType, $sourceId, $tenantId));
    }

    public function receivableDocuments(Request $request): JsonResponse
    {
        $tenantId = (int) ($request->input('tenant_id', 0));
        $customerId = $request->has('customer_id') ? (int) $request->input('customer_id') : null;

        return $this->respond($this->service->getReceivableDocuments($tenantId, $customerId));
    }

    public function stockAvailability(Request $request): JsonResponse
    {
        $tenantId = (int) ($request->input('tenant_id', 0));
        $itemId = (int) ($request->input('item_id', 0));
        $warehouseId = $request->has('warehouse_id') ? (int) $request->input('warehouse_id') : null;
        $locationId = $request->has('location_id') ? (int) $request->input('location_id') : null;

        return $this->respond($this->service->getStockAvailability($tenantId, $itemId, $warehouseId, $locationId));
    }
}
