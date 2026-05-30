<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface VehicleServiceManagementServiceInterface
{
    public function upsertJobCardAggregate(?int $id, array $payload): Result;

    public function syncJobCardLines(int $jobCardId, array $payload): Result;

    public function syncLaborItems(int $jobCardId, array $payload): Result;

    public function syncNonInventoryItems(int $jobCardId, array $payload): Result;

    public function syncExternalServices(int $jobCardId, array $payload): Result;

    public function syncCustomerSuppliedItems(int $jobCardId, array $payload): Result;

    public function getStatusHistory(string $entityType, int $entityId, int $tenantId): Result;

    public function getSettings(int $tenantId, ?int $organizationUnitId): Result;

    public function upsertSettings(array $payload): Result;

    public function initializeSettings(array $payload): Result;

    public function getStockAvailability(int $tenantId, int $itemId, ?int $warehouseId, ?int $locationId): Result;

    public function getVehicleOwnerSummary(int $tenantId, int $vehicleId): Result;

    public function validatePartyContext(array $payload): Result;

    public function getInvoiceableJobCards(int $tenantId, ?int $customerId): Result;

    public function getReceivableJobCards(int $tenantId, ?int $customerId): Result;

    public function calculateInvoicePreview(array $payload): Result;
}
