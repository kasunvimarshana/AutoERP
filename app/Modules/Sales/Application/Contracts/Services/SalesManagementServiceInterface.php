<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface SalesManagementServiceInterface
{
    public function upsertSalesOrderWithLines(?int $id, array $payload): Result;

    public function syncSalesOrderLines(int $purchaseOrderId, array $payload): Result;

    public function upsertGdnWithLines(?int $id, array $payload): Result;

    public function syncGdnLines(int $gdnHeaderId, array $payload): Result;

    public function upsertSalesReturnWithLines(?int $id, array $payload): Result;

    public function syncSalesReturnLines(int $purchaseReturnId, array $payload): Result;

    public function getStatusHistory(string $entityType, int $entityId, int $tenantId): Result;

    public function getSalesSettings(int $tenantId, ?int $organizationUnitId): Result;

    public function upsertSalesSettings(array $payload): Result;

    public function initializeSalesSettings(array $payload): Result;

    public function getAvailableSalesOrderLinesForGdn(int $tenantId, int $purchaseOrderId): Result;

    public function getAvailableGdnLinesForDocument(int $tenantId, int $gdnHeaderId): Result;

    public function getReturnableLines(string $sourceType, int $sourceId, int $tenantId): Result;

    public function getReceivableDocuments(int $tenantId, ?int $customerId): Result;
}
