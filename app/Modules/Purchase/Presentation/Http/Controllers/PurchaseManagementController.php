<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Results\Result;
use Modules\Purchase\Application\Contracts\Services\PurchaseManagementServiceInterface;

final class PurchaseManagementController extends Controller
{
    public function __construct(private readonly PurchaseManagementServiceInterface $service)
    {
    }

    public function upsertPurchaseOrderWithLines(Request $request): JsonResponse
    {
        return $this->respond($this->service->upsertPurchaseOrderWithLines(null, $request->all()));
    }

    public function updatePurchaseOrderWithLines(Request $request, int $id): JsonResponse
    {
        return $this->respond($this->service->upsertPurchaseOrderWithLines($id, $request->all()));
    }

    public function syncPurchaseOrderLines(Request $request, int $id): JsonResponse
    {
        return $this->respond($this->service->syncPurchaseOrderLines($id, $request->all()));
    }

    public function upsertGrnWithLines(Request $request): JsonResponse
    {
        return $this->respond($this->service->upsertGrnWithLines(null, $request->all()));
    }

    public function updateGrnWithLines(Request $request, int $id): JsonResponse
    {
        return $this->respond($this->service->upsertGrnWithLines($id, $request->all()));
    }

    public function syncGrnLines(Request $request, int $id): JsonResponse
    {
        return $this->respond($this->service->syncGrnLines($id, $request->all()));
    }

    public function upsertPurchaseReturnWithLines(Request $request): JsonResponse
    {
        return $this->respond($this->service->upsertPurchaseReturnWithLines(null, $request->all()));
    }

    public function updatePurchaseReturnWithLines(Request $request, int $id): JsonResponse
    {
        return $this->respond($this->service->upsertPurchaseReturnWithLines($id, $request->all()));
    }

    public function syncPurchaseReturnLines(Request $request, int $id): JsonResponse
    {
        return $this->respond($this->service->syncPurchaseReturnLines($id, $request->all()));
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

        return $this->respond($this->service->getPurchaseSettings($tenantId, $organizationUnitId));
    }

    public function upsertSettings(Request $request): JsonResponse
    {
        return $this->respond($this->service->upsertPurchaseSettings($request->all()));
    }

    public function initializeSettings(Request $request): JsonResponse
    {
        return $this->respond($this->service->initializePurchaseSettings($request->all()));
    }

    public function availablePurchaseOrderLinesForGrn(Request $request, int $purchaseOrderId): JsonResponse
    {
        $tenantId = (int) ($request->input('tenant_id', 0));

        return $this->respond($this->service->getAvailablePurchaseOrderLinesForGrn($tenantId, $purchaseOrderId));
    }

    public function availableGrnLinesForDocument(Request $request, int $grnHeaderId): JsonResponse
    {
        $tenantId = (int) ($request->input('tenant_id', 0));

        return $this->respond($this->service->getAvailableGrnLinesForDocument($tenantId, $grnHeaderId));
    }

    public function returnableLines(Request $request): JsonResponse
    {
        $tenantId = (int) ($request->input('tenant_id', 0));
        $sourceType = (string) $request->input('source_type', '');
        $sourceId = (int) $request->input('source_id', 0);

        return $this->respond($this->service->getReturnableLines($sourceType, $sourceId, $tenantId));
    }

    public function payableDocuments(Request $request): JsonResponse
    {
        $tenantId = (int) ($request->input('tenant_id', 0));
        $supplierId = $request->has('supplier_id') ? (int) $request->input('supplier_id') : null;

        return $this->respond($this->service->getPayableDocuments($tenantId, $supplierId));
    }

    private function respond(Result $result): JsonResponse
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $statusCode = $error->code === 'PURCHASE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $statusCode);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }
}
