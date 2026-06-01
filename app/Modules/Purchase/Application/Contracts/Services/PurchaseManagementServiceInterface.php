<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface PurchaseManagementServiceInterface
{
    public function upsertPurchaseOrderWithLines(?int $id, array $payload): Result;

    public function syncPurchaseOrderLines(int $purchaseOrderId, array $payload): Result;

    public function upsertGrnWithLines(?int $id, array $payload): Result;

    public function syncGrnLines(int $grnHeaderId, array $payload): Result;

    public function upsertPurchaseReturnWithLines(?int $id, array $payload): Result;

    public function syncPurchaseReturnLines(int $purchaseReturnId, array $payload): Result;

    public function getStatusHistory(string $entityType, int $entityId, int $tenantId): Result;

    public function getDashboardSummary(int $tenantId, ?int $organizationUnitId): Result;

    public function getPurchaseSettings(int $tenantId, ?int $organizationUnitId): Result;

    public function upsertPurchaseSettings(array $payload): Result;

    public function initializePurchaseSettings(array $payload): Result;

    public function getAvailablePurchaseOrderLinesForGrn(int $tenantId, int $purchaseOrderId): Result;

    public function getAvailableGrnLinesForDocument(int $tenantId, int $grnHeaderId): Result;

    public function getReturnableLines(string $sourceType, int $sourceId, int $tenantId): Result;

    public function getPayableDocuments(int $tenantId, ?int $supplierId): Result;

    public function calculateInvoicePreview(array $payload): Result;
}
