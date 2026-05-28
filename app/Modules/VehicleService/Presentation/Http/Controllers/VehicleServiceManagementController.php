<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Results\Result;
use Modules\VehicleService\Application\Contracts\Services\VehicleServiceManagementServiceInterface;

final class VehicleServiceManagementController extends Controller
{
    public function __construct(private readonly VehicleServiceManagementServiceInterface $service)
    {
    }

    public function upsertJobCardAggregate(Request $request): JsonResponse
    {
        return $this->respond($this->service->upsertJobCardAggregate(null, $request->all()));
    }

    public function updateJobCardAggregate(Request $request, int $jobCardId): JsonResponse
    {
        return $this->respond($this->service->upsertJobCardAggregate($jobCardId, $request->all()));
    }

    public function syncJobCardLines(Request $request, int $jobCardId): JsonResponse
    {
        return $this->respond($this->service->syncJobCardLines($jobCardId, $request->all()));
    }

    public function syncLaborItems(Request $request, int $jobCardId): JsonResponse
    {
        return $this->respond($this->service->syncLaborItems($jobCardId, $request->all()));
    }

    public function syncExternalServices(Request $request, int $jobCardId): JsonResponse
    {
        return $this->respond($this->service->syncExternalServices($jobCardId, $request->all()));
    }

    public function syncCustomerSuppliedItems(Request $request, int $jobCardId): JsonResponse
    {
        return $this->respond($this->service->syncCustomerSuppliedItems($jobCardId, $request->all()));
    }

    public function statusHistory(Request $request, string $entityType, int $entityId): JsonResponse
    {
        $tenantId = (int) ($request->input('tenant_id', 0));

        return $this->respond($this->service->getStatusHistory($entityType, $entityId, $tenantId));
    }

    public function showSettings(Request $request): JsonResponse
    {
        $tenantId = (int) ($request->input('tenant_id', 0));
        $organizationUnitId = $request->has('organization_unit_id')
            ? (int) $request->input('organization_unit_id')
            : null;

        return $this->respond($this->service->getSettings($tenantId, $organizationUnitId));
    }

    public function upsertSettings(Request $request): JsonResponse
    {
        return $this->respond($this->service->upsertSettings($request->all()));
    }

    public function initializeSettings(Request $request): JsonResponse
    {
        return $this->respond($this->service->initializeSettings($request->all()));
    }

    public function stockAvailability(Request $request): JsonResponse
    {
        $tenantId = (int) ($request->input('tenant_id', 0));
        $itemId = (int) ($request->input('item_id', 0));
        $warehouseId = $request->has('warehouse_id') ? (int) $request->input('warehouse_id') : null;
        $locationId = $request->has('location_id') ? (int) $request->input('location_id') : null;

        return $this->respond($this->service->getStockAvailability($tenantId, $itemId, $warehouseId, $locationId));
    }

    public function invoiceableJobCards(Request $request): JsonResponse
    {
        $tenantId = (int) ($request->input('tenant_id', 0));
        $customerId = $request->has('customer_id') ? (int) $request->input('customer_id') : null;

        return $this->respond($this->service->getInvoiceableJobCards($tenantId, $customerId));
    }

    public function receivableJobCards(Request $request): JsonResponse
    {
        $tenantId = (int) ($request->input('tenant_id', 0));
        $customerId = $request->has('customer_id') ? (int) $request->input('customer_id') : null;

        return $this->respond($this->service->getReceivableJobCards($tenantId, $customerId));
    }

    private function respond(Result $result): JsonResponse
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $statusCode = $error->code === 'VEHICLESERVICE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $statusCode);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }
}
