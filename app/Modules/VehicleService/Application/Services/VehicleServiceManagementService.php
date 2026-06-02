<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Finance\Application\Contracts\Services\TaxCalculationServiceInterface;
use Modules\Inventory\Application\Repositories\StockLevelRepositoryInterface;
use Modules\VehicleService\Application\Contracts\Services\VehicleServiceManagementServiceInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobCardLineRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobCardRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobCustomerSuppliedItemRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobExternalServiceRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobPaymentLinkRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobStatusHistoryRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceLaborAssignmentRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceLaborItemRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceNonInventoryItemRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceSettingRepositoryInterface;
use Modules\VehicleService\Domain\Constants\VehicleServiceErrorCode;
use Throwable;

final class VehicleServiceManagementService implements VehicleServiceManagementServiceInterface
{
    public function __construct(
        private readonly VehicleServiceJobCardRepositoryInterface $jobCardRepository,
        private readonly VehicleServiceJobCardLineRepositoryInterface $jobCardLineRepository,
        private readonly VehicleServiceLaborItemRepositoryInterface $laborItemRepository,
        private readonly VehicleServiceLaborAssignmentRepositoryInterface $laborAssignmentRepository,
        private readonly VehicleServiceNonInventoryItemRepositoryInterface $nonInventoryItemRepository,
        private readonly VehicleServiceJobExternalServiceRepositoryInterface $externalServiceRepository,
        private readonly VehicleServiceJobCustomerSuppliedItemRepositoryInterface $customerSuppliedItemRepository,
        private readonly VehicleServiceJobStatusHistoryRepositoryInterface $statusHistoryRepository,
        private readonly VehicleServiceSettingRepositoryInterface $settingRepository,
        private readonly VehicleServiceJobPaymentLinkRepositoryInterface $paymentLinkRepository,
        private readonly StockLevelRepositoryInterface $stockLevelRepository,
        private readonly TaxCalculationServiceInterface $taxCalculationService,
    ) {}

    public function upsertJobCardAggregate(?int $id, array $payload): Result
    {
        try {
            return $this->jobCardRepository->transaction(function () use ($id, $payload): Result {
                $headerPayload = $this->applyPartyContextDefaults($this->extractHeaderPayload($payload));
                if ($id === null && trim((string) ($headerPayload['job_card_number'] ?? '')) === '') {
                    $headerPayload['job_card_number'] = $this->nextJobCardNumber(
                        (int) ($headerPayload['tenant_id'] ?? 0),
                        isset($headerPayload['organization_unit_id']) ? (int) $headerPayload['organization_unit_id'] : null,
                    );
                }
                $partyValidation = $this->validatePartyContext($headerPayload);
                if ($partyValidation->isFailure()) {
                    return $partyValidation;
                }

                $jobCard = $id === null
                    ? $this->jobCardRepository->create($this->withDefaultRowVersion($headerPayload))
                    : $this->jobCardRepository->update($id, $headerPayload);

                $jobCardId = (int) $jobCard->id();
                $tenantId = (int) $jobCard->get('tenant_id', 0);
                $organizationUnitId = $jobCard->get('organization_unit_id');

                if (is_array($payload['lines'] ?? null)) {
                    $syncLines = $this->syncJobCardLines($jobCardId, [
                        'tenant_id' => $tenantId,
                        'organization_unit_id' => $organizationUnitId,
                        'lines' => $payload['lines'],
                    ]);
                    if ($syncLines->isFailure()) {
                        return $syncLines;
                    }
                }

                $laborItemClientKeys = [];
                if (is_array($payload['labor_items'] ?? null)) {
                    $syncLabor = $this->syncLaborItems($jobCardId, [
                        'tenant_id' => $tenantId,
                        'organization_unit_id' => $organizationUnitId,
                        'labor_items' => $payload['labor_items'],
                    ]);
                    if ($syncLabor->isFailure()) {
                        return $syncLabor;
                    }
                    $syncValue = $syncLabor->valueOrFail();
                    if (is_array($syncValue['labor_item_client_keys'] ?? null)) {
                        $laborItemClientKeys = $syncValue['labor_item_client_keys'];
                    }
                }

                if (is_array($payload['external_services'] ?? null)) {
                    $syncExternal = $this->syncExternalServices($jobCardId, [
                        'tenant_id' => $tenantId,
                        'organization_unit_id' => $organizationUnitId,
                        'external_services' => $payload['external_services'],
                    ]);
                    if ($syncExternal->isFailure()) {
                        return $syncExternal;
                    }
                }

                if (is_array($payload['non_inventory_items'] ?? null)) {
                    $syncNonInventory = $this->syncNonInventoryItems($jobCardId, [
                        'tenant_id' => $tenantId,
                        'organization_unit_id' => $organizationUnitId,
                        'non_inventory_items' => $payload['non_inventory_items'],
                    ]);
                    if ($syncNonInventory->isFailure()) {
                        return $syncNonInventory;
                    }
                }

                if (is_array($payload['customer_supplied_items'] ?? null)) {
                    $syncCustomerItems = $this->syncCustomerSuppliedItems($jobCardId, [
                        'tenant_id' => $tenantId,
                        'organization_unit_id' => $organizationUnitId,
                        'customer_supplied_items' => $payload['customer_supplied_items'],
                    ]);
                    if ($syncCustomerItems->isFailure()) {
                        return $syncCustomerItems;
                    }
                }

                if (is_array($payload['labor_assignments'] ?? null)) {
                    $syncAssignments = $this->syncLaborAssignments($jobCardId, [
                        'tenant_id' => $tenantId,
                        'organization_unit_id' => $organizationUnitId,
                        'labor_assignments' => $payload['labor_assignments'],
                        'labor_item_client_keys' => $laborItemClientKeys,
                    ]);
                    if ($syncAssignments->isFailure()) {
                        return $syncAssignments;
                    }
                }

                $this->recalculateJobCardTotals($jobCardId, $tenantId);

                $reloaded = $this->jobCardRepository->findById($jobCardId);
                if (! $reloaded instanceof DataRecord) {
                    return Result::failure(new Error(VehicleServiceErrorCode::NOT_FOUND, 'Job card not found.'));
                }

                return Result::success($reloaded);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function syncJobCardLines(int $jobCardId, array $payload): Result
    {
        try {
            $jobCard = $this->jobCardRepository->findById($jobCardId);
            if (! $jobCard instanceof DataRecord) {
                return Result::failure(new Error(VehicleServiceErrorCode::NOT_FOUND, 'Job card not found.'));
            }

            $tenantId = (int) ($payload['tenant_id'] ?? $jobCard->get('tenant_id', 0));
            $organizationUnitId = $payload['organization_unit_id'] ?? $jobCard->get('organization_unit_id');
            $lines = is_array($payload['lines'] ?? null) ? $payload['lines'] : [];

            foreach ($lines as $linePayload) {
                if (! is_array($linePayload)) {
                    continue;
                }

                $lineId = isset($linePayload['id']) ? (int) $linePayload['id'] : null;
                if ((bool) ($linePayload['_delete'] ?? false) && $lineId !== null) {
                    $this->deleteComboExpansion($tenantId, $jobCardId, $lineId);
                    $this->jobCardLineRepository->delete($lineId);

                    continue;
                }

                if ((int) ($linePayload['item_id'] ?? 0) < 1 || (int) ($linePayload['uom_id'] ?? 0) < 1) {
                    return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, 'Each service line requires a valid item and UOM.'));
                }

                $upsert = $this->withDefaultRowVersion($this->hydrateLineTotals([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'job_card_id' => $jobCardId,
                    ...$linePayload,
                ]));

                if ($lineId === null) {
                    $created = $this->jobCardLineRepository->create($upsert);
                    $this->expandComboComponents($created, $upsert);

                    continue;
                }

                $this->deleteComboExpansion($tenantId, $jobCardId, $lineId);
                $updated = $this->jobCardLineRepository->update($lineId, $upsert);
                $this->expandComboComponents($updated, $upsert);
            }

            $this->recalculateJobCardTotals($jobCardId, $tenantId);

            return Result::success([
                'job_card_id' => $jobCardId,
                'synced' => true,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function syncLaborItems(int $jobCardId, array $payload): Result
    {
        try {
            $jobCard = $this->jobCardRepository->findById($jobCardId);
            if (! $jobCard instanceof DataRecord) {
                return Result::failure(new Error(VehicleServiceErrorCode::NOT_FOUND, 'Job card not found.'));
            }

            $tenantId = (int) ($payload['tenant_id'] ?? $jobCard->get('tenant_id', 0));
            $organizationUnitId = $payload['organization_unit_id'] ?? $jobCard->get('organization_unit_id');
            $laborItems = is_array($payload['labor_items'] ?? null) ? $payload['labor_items'] : [];
            $clientKeys = [];

            foreach ($laborItems as $linePayload) {
                if (! is_array($linePayload)) {
                    continue;
                }

                $lineId = isset($linePayload['id']) ? (int) $linePayload['id'] : null;
                if ((bool) ($linePayload['_delete'] ?? false) && $lineId !== null) {
                    $this->laborItemRepository->delete($lineId);

                    continue;
                }

                if ((int) ($linePayload['item_id'] ?? 0) < 1 || (int) ($linePayload['uom_id'] ?? 0) < 1) {
                    return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, 'Each labour line requires a valid item and UOM.'));
                }

                $clientKey = $this->extractClientKey($linePayload);
                $lineData = [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'job_card_id' => $jobCardId,
                    ...$linePayload,
                ];
                unset($lineData['line_type'], $lineData['requires_stock_movement'], $lineData['warehouse_id']);

                $upsert = $this->withDefaultRowVersion($this->hydrateLineTotals($lineData, true));

                if ($lineId === null) {
                    $created = $this->laborItemRepository->create($upsert);
                    if ($clientKey !== null) {
                        $clientKeys[$clientKey] = (int) $created->id();
                    }

                    continue;
                }

                $updated = $this->laborItemRepository->update($lineId, $upsert);
                if ($clientKey !== null) {
                    $clientKeys[$clientKey] = (int) $updated->id();
                }
            }

            $this->recalculateJobCardTotals($jobCardId, $tenantId);

            return Result::success([
                'job_card_id' => $jobCardId,
                'labor_item_client_keys' => $clientKeys,
                'synced' => true,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function syncNonInventoryItems(int $jobCardId, array $payload): Result
    {
        try {
            $jobCard = $this->jobCardRepository->findById($jobCardId);
            if (! $jobCard instanceof DataRecord) {
                return Result::failure(new Error(VehicleServiceErrorCode::NOT_FOUND, 'Job card not found.'));
            }

            $tenantId = (int) ($payload['tenant_id'] ?? $jobCard->get('tenant_id', 0));
            $organizationUnitId = $payload['organization_unit_id'] ?? $jobCard->get('organization_unit_id');
            $items = is_array($payload['non_inventory_items'] ?? null) ? $payload['non_inventory_items'] : [];

            foreach ($items as $linePayload) {
                if (! is_array($linePayload)) {
                    continue;
                }

                $lineId = isset($linePayload['id']) ? (int) $linePayload['id'] : null;
                if ((bool) ($linePayload['_delete'] ?? false) && $lineId !== null) {
                    $this->nonInventoryItemRepository->delete($lineId);

                    continue;
                }

                if ((int) ($linePayload['item_id'] ?? 0) < 1 || (int) ($linePayload['uom_id'] ?? 0) < 1) {
                    return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, 'Each non-inventory line requires a valid item and UOM.'));
                }

                $lineData = [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'job_card_id' => $jobCardId,
                    'name' => $this->resolveItemName($tenantId, isset($linePayload['item_id']) ? (int) $linePayload['item_id'] : null)
                        ?? (string) ($linePayload['name'] ?? $linePayload['description'] ?? 'Non-inventory charge'),
                    ...$linePayload,
                ];
                unset($lineData['item_id'], $lineData['line_type'], $lineData['requires_stock_movement'], $lineData['warehouse_id']);

                $upsert = $this->withDefaultRowVersion($this->hydrateLineTotals($lineData));

                if ($lineId === null) {
                    $this->nonInventoryItemRepository->create($upsert);

                    continue;
                }

                $this->nonInventoryItemRepository->update($lineId, $upsert);
            }

            $this->recalculateJobCardTotals($jobCardId, $tenantId);

            return Result::success([
                'job_card_id' => $jobCardId,
                'synced' => true,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function syncLaborAssignments(int $jobCardId, array $payload): Result
    {
        try {
            $jobCard = $this->jobCardRepository->findById($jobCardId);
            if (! $jobCard instanceof DataRecord) {
                return Result::failure(new Error(VehicleServiceErrorCode::NOT_FOUND, 'Job card not found.'));
            }

            $tenantId = (int) ($payload['tenant_id'] ?? $jobCard->get('tenant_id', 0));
            $organizationUnitId = $payload['organization_unit_id'] ?? $jobCard->get('organization_unit_id');
            $assignments = is_array($payload['labor_assignments'] ?? null) ? $payload['labor_assignments'] : [];
            $clientKeyMap = is_array($payload['labor_item_client_keys'] ?? null) ? $payload['labor_item_client_keys'] : [];

            foreach ($assignments as $assignmentPayload) {
                if (! is_array($assignmentPayload)) {
                    continue;
                }

                $assignmentId = isset($assignmentPayload['id']) ? (int) $assignmentPayload['id'] : null;
                if ((bool) ($assignmentPayload['_delete'] ?? false) && $assignmentId !== null) {
                    $this->laborAssignmentRepository->delete($assignmentId);

                    continue;
                }

                $laborItemId = isset($assignmentPayload['labor_item_id']) ? (int) $assignmentPayload['labor_item_id'] : 0;
                $clientKey = isset($assignmentPayload['labor_item_client_key']) ? (string) $assignmentPayload['labor_item_client_key'] : '';
                if ($laborItemId < 1 && $clientKey !== '' && isset($clientKeyMap[$clientKey])) {
                    $laborItemId = (int) $clientKeyMap[$clientKey];
                }

                $employeeId = isset($assignmentPayload['employee_id']) ? (int) $assignmentPayload['employee_id'] : 0;
                if ($laborItemId < 1 || $employeeId < 1) {
                    continue;
                }

                if (! $this->laborItemBelongsToJob($tenantId, $jobCardId, $laborItemId)) {
                    return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, 'Technician assignment must reference a labour item from this job card.'));
                }

                if (! $this->activeEmployeeExists($tenantId, $employeeId)) {
                    return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, 'Technician assignment must reference an active employee in this tenant.'));
                }

                $upsert = $this->withDefaultRowVersion([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'job_card_id' => $jobCardId,
                    ...$assignmentPayload,
                    'labor_item_id' => $laborItemId,
                    'employee_id' => $employeeId,
                ]);
                unset($upsert['labor_item_client_key'], $upsert['quantity']);

                if ($assignmentId === null) {
                    $existing = $this->laborAssignmentRepository->list([
                        'tenant_id' => $tenantId,
                        'labor_item_id' => $laborItemId,
                        'employee_id' => $employeeId,
                    ]);

                    if ($existing !== []) {
                        $this->laborAssignmentRepository->update((int) $existing[0]->id(), $upsert);

                        continue;
                    }

                    $this->laborAssignmentRepository->create($upsert);

                    continue;
                }

                $this->laborAssignmentRepository->update($assignmentId, $upsert);
            }

            return Result::success([
                'job_card_id' => $jobCardId,
                'synced' => true,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function syncExternalServices(int $jobCardId, array $payload): Result
    {
        try {
            $jobCard = $this->jobCardRepository->findById($jobCardId);
            if (! $jobCard instanceof DataRecord) {
                return Result::failure(new Error(VehicleServiceErrorCode::NOT_FOUND, 'Job card not found.'));
            }

            $tenantId = (int) ($payload['tenant_id'] ?? $jobCard->get('tenant_id', 0));
            $organizationUnitId = $payload['organization_unit_id'] ?? $jobCard->get('organization_unit_id');
            $items = is_array($payload['external_services'] ?? null) ? $payload['external_services'] : [];

            foreach ($items as $linePayload) {
                if (! is_array($linePayload)) {
                    continue;
                }

                $lineId = isset($linePayload['id']) ? (int) $linePayload['id'] : null;
                if ((bool) ($linePayload['_delete'] ?? false) && $lineId !== null) {
                    $this->externalServiceRepository->delete($lineId);

                    continue;
                }

                $upsert = $this->withDefaultRowVersion($this->hydrateExternalServiceTotals([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'job_card_id' => $jobCardId,
                    ...$linePayload,
                ]));

                if ($lineId === null) {
                    $this->externalServiceRepository->create($upsert);

                    continue;
                }

                $this->externalServiceRepository->update($lineId, $upsert);
            }

            $this->recalculateJobCardTotals($jobCardId, $tenantId);

            return Result::success([
                'job_card_id' => $jobCardId,
                'synced' => true,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function syncCustomerSuppliedItems(int $jobCardId, array $payload): Result
    {
        try {
            $jobCard = $this->jobCardRepository->findById($jobCardId);
            if (! $jobCard instanceof DataRecord) {
                return Result::failure(new Error(VehicleServiceErrorCode::NOT_FOUND, 'Job card not found.'));
            }

            $tenantId = (int) ($payload['tenant_id'] ?? $jobCard->get('tenant_id', 0));
            $organizationUnitId = $payload['organization_unit_id'] ?? $jobCard->get('organization_unit_id');
            $items = is_array($payload['customer_supplied_items'] ?? null)
                ? $payload['customer_supplied_items']
                : [];

            foreach ($items as $linePayload) {
                if (! is_array($linePayload)) {
                    continue;
                }

                $lineId = isset($linePayload['id']) ? (int) $linePayload['id'] : null;
                if ((bool) ($linePayload['_delete'] ?? false) && $lineId !== null) {
                    $this->customerSuppliedItemRepository->delete($lineId);

                    continue;
                }

                $upsert = $this->withDefaultRowVersion([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'job_card_id' => $jobCardId,
                    ...$linePayload,
                ]);

                if ($lineId === null) {
                    $this->customerSuppliedItemRepository->create($upsert);

                    continue;
                }

                $this->customerSuppliedItemRepository->update($lineId, $upsert);
            }

            return Result::success([
                'job_card_id' => $jobCardId,
                'synced' => true,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function getStatusHistory(string $entityType, int $entityId, int $tenantId): Result
    {
        try {
            return Result::success($this->statusHistoryRepository->list([
                'tenant_id' => $tenantId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ]));
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function getSettings(int $tenantId, ?int $organizationUnitId): Result
    {
        try {
            $settings = $organizationUnitId !== null
                ? $this->settingRepository->list([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'is_active' => true,
                ])
                : [];

            if ($settings === []) {
                $settings = $this->settingRepository->list([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => null,
                    'is_active' => true,
                ]);
            }

            return Result::success($settings[0] ?? null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function upsertSettings(array $payload): Result
    {
        try {
            $tenantId = (int) ($payload['tenant_id'] ?? 0);
            if ($tenantId < 1) {
                return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, 'tenant_id is required.'));
            }

            $organizationUnitId = isset($payload['organization_unit_id'])
                ? (int) $payload['organization_unit_id']
                : null;

            $existing = $this->settingRepository->list([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
            ]);

            if ($existing !== []) {
                $updated = $this->settingRepository->update(
                    (int) $existing[0]->id(),
                    array_merge($payload, ['tenant_id' => $tenantId]),
                );

                return Result::success($updated);
            }

            return Result::success($this->settingRepository->create($this->withDefaultRowVersion($payload)));
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function initializeSettings(array $payload): Result
    {
        return $this->upsertSettings([
            'tenant_id' => (int) ($payload['tenant_id'] ?? 0),
            'organization_unit_id' => $payload['organization_unit_id'] ?? null,
            'is_active' => true,
            'enable_inventory_reservation' => true,
            'enable_invoice_generation' => true,
            'enable_payment_allocation' => true,
            'enable_finance_posting' => true,
            'allow_negative_stock_for_service' => false,
            'default_service_due_days' => 0,
            'default_priority' => 'medium',
            'auto_invoice_trigger_status' => 'completed',
            'inventory_posting_trigger_status' => 'completed',
        ]);
    }

    public function getStockAvailability(int $tenantId, int $itemId, ?int $warehouseId, ?int $locationId): Result
    {
        try {
            $levels = $this->stockLevelRepository->list([
                'tenant_id' => $tenantId,
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'warehouse_location_id' => $locationId,
            ]);

            return Result::success([
                'tenant_id' => $tenantId,
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
                'stock_levels' => $levels,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function getVehicleOwnerSummary(int $tenantId, int $vehicleId): Result
    {
        try {
            if ($tenantId < 1 || $vehicleId < 1) {
                return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, 'tenant_id and vehicle_id are required.'));
            }

            $ownership = $this->currentVehicleOwnership($tenantId, $vehicleId);

            return Result::success([
                'tenant_id' => $tenantId,
                'vehicle_id' => $vehicleId,
                'ownership' => $ownership,
                'warnings' => $ownership === null
                    ? ['No current vehicle ownership record found. Service customer and billing party must be selected explicitly.']
                    : [],
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function validatePartyContext(array $payload): Result
    {
        try {
            $tenantId = (int) ($payload['tenant_id'] ?? 0);
            if ($tenantId < 1) {
                return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, 'tenant_id is required.'));
            }

            $errors = [];
            $warnings = [];

            foreach ([
                'service_customer' => ['type' => 'service_customer_type', 'id' => 'service_customer_id', 'name' => 'service_customer_name'],
                'billing_customer' => ['type' => 'billing_customer_type', 'id' => 'billing_customer_id', 'name' => 'billing_customer_name'],
                'payer' => ['type' => 'payer_type', 'id' => 'payer_id', 'name' => 'payer_name'],
            ] as $label => $keys) {
                $type = isset($payload[$keys['type']]) ? (string) $payload[$keys['type']] : null;
                $id = isset($payload[$keys['id']]) ? (int) $payload[$keys['id']] : null;
                $name = isset($payload[$keys['name']]) ? trim((string) $payload[$keys['name']]) : '';

                $validation = $this->validatePartyReference($tenantId, $label, $type, $id, $name);
                $errors = [...$errors, ...$validation['errors']];
                $warnings = [...$warnings, ...$validation['warnings']];
            }

            if (isset($payload['linked_customer_id']) && (int) $payload['linked_customer_id'] > 0) {
                if (! $this->tenantRecordExists('customers', (int) $payload['linked_customer_id'], $tenantId)) {
                    $errors[] = 'linked_customer_id must reference an active customer in the same tenant.';
                }
            }

            if (
                ($payload['vehicle_owner_type'] ?? null) !== null
                && ($payload['billing_customer_type'] ?? null) !== null
                && (string) $payload['vehicle_owner_type'] !== (string) $payload['billing_customer_type']
            ) {
                $warnings[] = 'Vehicle owner differs from billing customer. Backend will keep them separate for document/payment context.';
            }

            if ($errors !== []) {
                return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, implode(' ', $errors)));
            }

            return Result::success([
                'valid' => true,
                'warnings' => array_values(array_unique($warnings)),
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function getInvoiceableJobCards(int $tenantId, ?int $customerId): Result
    {
        try {
            $filters = [
                'tenant_id' => $tenantId,
                'status' => 'completed',
            ];
            if ($customerId !== null) {
                $filters['linked_customer_id'] = $customerId;
            }

            return Result::success($this->jobCardRepository->list($filters));
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function getReceivableJobCards(int $tenantId, ?int $customerId): Result
    {
        try {
            $filters = [
                'tenant_id' => $tenantId,
            ];
            if ($customerId !== null) {
                $filters['linked_customer_id'] = $customerId;
            }

            $jobCards = $this->jobCardRepository->list($filters);
            $paymentLinks = $this->paymentLinkRepository->list([
                'tenant_id' => $tenantId,
                'status' => 'active',
            ]);

            return Result::success([
                'job_cards' => $jobCards,
                'payment_links' => $paymentLinks,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function calculateInvoicePreview(array $payload): Result
    {
        try {
            $tenantId = (int) ($payload['tenant_id'] ?? 0);
            $groups = [
                'lines' => is_array($payload['lines'] ?? null) ? $payload['lines'] : [],
                'labor_items' => is_array($payload['labor_items'] ?? null) ? $payload['labor_items'] : [],
                'non_inventory_items' => is_array($payload['non_inventory_items'] ?? null)
                    ? $payload['non_inventory_items']
                    : [],
                'external_services' => is_array($payload['external_services'] ?? null)
                    ? $payload['external_services']
                    : [],
            ];

            $response = ['lines' => [], 'labor_items' => [], 'non_inventory_items' => [], 'external_services' => []];
            $subtotal = 0.0;
            $discountTotal = 0.0;
            $taxTotal = 0.0;

            foreach (['lines', 'labor_items', 'non_inventory_items'] as $group) {
                foreach ($groups[$group] as $line) {
                    if (! is_array($line)) {
                        continue;
                    }
                    $preview = $this->hydrateLineTotals(array_merge($line, ['tenant_id' => $tenantId]));
                    $response[$group][] = $preview;
                    $subtotal += (float) $preview['gross_amount'];
                    $discountTotal += (float) $preview['discount_amount'];
                    $taxTotal += (float) $preview['tax_amount'];
                }
            }

            foreach ($groups['external_services'] as $line) {
                if (! is_array($line)) {
                    continue;
                }
                $preview = $this->hydrateExternalServiceTotals(array_merge($line, ['tenant_id' => $tenantId]));
                $response['external_services'][] = $preview;
                $subtotal += (float) $preview['line_total'];
                $discountTotal += (float) $preview['discount_amount'];
                $taxTotal += (float) $preview['tax_amount'];
            }

            $headerDiscountAmount = $this->resolveDiscountAmount(
                max(0.0, $subtotal - $discountTotal),
                (string) ($payload['header_discount_type'] ?? ''),
                round((float) ($payload['header_discount_value'] ?? 0), 4),
            );
            $headerTaxAmount = $this->resolveTaxAmount(
                $tenantId,
                isset($payload['header_tax_group_id']) ? (int) $payload['header_tax_group_id'] : null,
                max(0.0, $subtotal - $discountTotal - $headerDiscountAmount),
                $payload['posting_date'] ?? null,
            );

            $discountTotal = round($discountTotal + $headerDiscountAmount, 4);
            $taxTotal = round($taxTotal + $headerTaxAmount, 4);
            $grandTotal = round(max(0.0, $subtotal - $discountTotal + $taxTotal), 4);

            return Result::success([
                'subtotal' => round($subtotal, 4),
                'header_discount_amount' => $headerDiscountAmount,
                'header_tax_amount' => $headerTaxAmount,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                ...$response,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    private function recalculateJobCardTotals(int $jobCardId, int $tenantId): void
    {
        $lineItems = $this->jobCardLineRepository->list([
            'tenant_id' => $tenantId,
            'job_card_id' => $jobCardId,
        ]);
        $laborItems = $this->laborItemRepository->list([
            'tenant_id' => $tenantId,
            'job_card_id' => $jobCardId,
        ]);
        $nonInventoryItems = $this->nonInventoryItemRepository->list([
            'tenant_id' => $tenantId,
            'job_card_id' => $jobCardId,
        ]);
        $externalServices = $this->externalServiceRepository->list([
            'tenant_id' => $tenantId,
            'job_card_id' => $jobCardId,
        ]);

        $partsSubtotal = $this->sumDecimal($lineItems, 'gross_amount');
        $partsTax = $this->sumDecimal($lineItems, 'tax_amount');
        $partsDiscount = $this->sumDecimal($lineItems, 'discount_amount');

        $laborSubtotal = $this->sumDecimal($laborItems, 'gross_amount');
        $laborTax = $this->sumDecimal($laborItems, 'tax_amount');
        $laborDiscount = $this->sumDecimal($laborItems, 'discount_amount');

        $nonInventorySubtotal = $this->sumDecimal($nonInventoryItems, 'gross_amount')
            + $this->sumDecimal($externalServices, 'line_total');
        $nonInventoryTax = $this->sumDecimal($nonInventoryItems, 'tax_amount')
            + $this->sumDecimal($externalServices, 'tax_amount');
        $nonInventoryDiscount = $this->sumDecimal($nonInventoryItems, 'discount_amount')
            + $this->sumDecimal($externalServices, 'discount_amount');

        $jobCard = $this->jobCardRepository->findById($jobCardId);
        if (! $jobCard instanceof DataRecord) {
            return;
        }

        $lineDiscountTotal = $partsDiscount + $laborDiscount + $nonInventoryDiscount;
        $lineTaxTotal = $partsTax + $laborTax + $nonInventoryTax;
        $subtotal = $partsSubtotal + $laborSubtotal + $nonInventorySubtotal;
        $headerDiscountAmount = $this->resolveDiscountAmount(
            max(0.0, $subtotal - $lineDiscountTotal),
            (string) $jobCard->get('header_discount_type', ''),
            round((float) $jobCard->get('header_discount_value', 0), 4),
        );
        $headerTaxAmount = $this->resolveTaxAmount(
            (int) $jobCard->get('tenant_id', 0),
            $jobCard->get('header_tax_group_id') !== null ? (int) $jobCard->get('header_tax_group_id') : null,
            max(0.0, $subtotal - $lineDiscountTotal - $headerDiscountAmount),
            null,
        );
        $discountTotal = $lineDiscountTotal + $headerDiscountAmount;
        $taxTotal = $lineTaxTotal + $headerTaxAmount;
        $grandTotal = $subtotal - $discountTotal + $taxTotal;

        $this->jobCardRepository->update($jobCardId, [
            'subtotal' => $partsSubtotal,
            'line_tax_total' => $partsTax,
            'line_discount_total' => $partsDiscount,
            'labor_item_subtotal' => $laborSubtotal,
            'labor_item_tax_total' => $laborTax,
            'labor_item_discount_total' => $laborDiscount,
            'non_inventory_item_subtotal' => $nonInventorySubtotal,
            'non_inventory_item_tax_total' => $nonInventoryTax,
            'non_inventory_item_discount_total' => $nonInventoryDiscount,
            'header_discount_amount' => $headerDiscountAmount,
            'header_tax_amount' => $headerTaxAmount,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'balance' => $grandTotal,
        ]);
    }

    /** @param array<int, DataRecord> $records */
    private function sumDecimal(array $records, string $field): float
    {
        $total = 0.0;
        foreach ($records as $record) {
            $total += (float) $record->get($field, 0);
        }

        return round($total, 4);
    }

    private function extractHeaderPayload(array $payload): array
    {
        unset(
            $payload['id'],
            $payload['lines'],
            $payload['labor_items'],
            $payload['labor_assignments'],
            $payload['non_inventory_items'],
            $payload['external_services'],
            $payload['customer_supplied_items'],
            $payload['subtotal'],
            $payload['line_tax_total'],
            $payload['line_discount_total'],
            $payload['non_inventory_item_subtotal'],
            $payload['non_inventory_item_tax_total'],
            $payload['non_inventory_item_discount_total'],
            $payload['labor_item_subtotal'],
            $payload['labor_item_tax_total'],
            $payload['labor_item_discount_total'],
            $payload['header_discount_amount'],
            $payload['header_tax_amount'],
            $payload['discount_total'],
            $payload['tax_total'],
            $payload['grand_total'],
            $payload['balance'],
        );

        return $payload;
    }

    private function applyPartyContextDefaults(array $payload): array
    {
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        $vehicleId = isset($payload['vehicle_id']) ? (int) $payload['vehicle_id'] : 0;

        if ($tenantId > 0 && $vehicleId > 0 && ! isset($payload['vehicle_ownership_id'])) {
            $ownership = $this->currentVehicleOwnership($tenantId, $vehicleId);
            if ($ownership !== null) {
                $payload['vehicle_ownership_id'] = $ownership['id'];
                $payload['vehicle_owner_type'] = $ownership['owner_type'];
                $payload['vehicle_owner_id'] = $ownership['owner_id'];
                $payload['vehicle_owner_name'] = $ownership['owner_display'];
                $payload['party_context'] = [
                    'vehicle_owner' => $ownership,
                    'warnings' => [],
                ];
            }
        }

        if (isset($payload['customer_id']) && ! isset($payload['linked_customer_id'])) {
            $payload['linked_customer_id'] = (int) $payload['customer_id'];
            unset($payload['customer_id']);
        }

        if (! isset($payload['service_customer_type']) && isset($payload['linked_customer_id'])) {
            $payload['service_customer_type'] = 'customer';
            $payload['service_customer_id'] = (int) $payload['linked_customer_id'];
        }

        if (! isset($payload['billing_customer_type']) && isset($payload['linked_customer_id'])) {
            $payload['billing_customer_type'] = 'customer';
            $payload['billing_customer_id'] = (int) $payload['linked_customer_id'];
        }

        if (! isset($payload['payer_type']) && isset($payload['billing_customer_type'])) {
            $payload['payer_type'] = $payload['billing_customer_type'];
            $payload['payer_id'] = $payload['billing_customer_id'] ?? null;
            $payload['payer_name'] = $payload['billing_customer_name'] ?? null;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function currentVehicleOwnership(int $tenantId, int $vehicleId): ?array
    {
        $record = DB::table('vehicle_ownerships')
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->where('is_current', true)
            ->whereNull('deleted_at')
            ->orderByRaw("case when ownership_role = 'legal_owner' then 0 when ownership_role = 'provider' then 1 else 2 end")
            ->orderByDesc('start_date')
            ->first();

        if ($record === null) {
            return null;
        }

        $ownerType = (string) $record->owner_type;
        $ownerId = $record->owner_id !== null ? (int) $record->owner_id : null;

        return [
            'id' => (int) $record->id,
            'ownership_type' => (string) $record->ownership_type,
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'owner_name' => $record->owner_name,
            'owner_display' => $this->resolvePartyDisplayName($tenantId, $ownerType, $ownerId, $record->owner_name),
            'ownership_role' => (string) $record->ownership_role,
            'start_date' => $record->start_date,
            'end_date' => $record->end_date,
            'is_current' => (bool) $record->is_current,
        ];
    }

    /**
     * @return array{errors: array<int, string>, warnings: array<int, string>}
     */
    private function validatePartyReference(int $tenantId, string $label, ?string $type, ?int $id, string $name): array
    {
        if ($type === null || trim($type) === '') {
            return ['errors' => [], 'warnings' => ["{$label} not selected. Backend will require it before invoice/payment posting."]];
        }

        $type = trim($type);
        if (in_array($type, ['external_party', 'internal_company', 'company'], true)) {
            if ($type === 'external_party' && $id === null && $name === '') {
                return ['errors' => ["{$label}_name is required for external_party."], 'warnings' => []];
            }

            return ['errors' => [], 'warnings' => []];
        }

        $table = match ($type) {
            'customer', 'billing_customer' => 'customers',
            'supplier', 'supplier_as_customer', 'provider' => 'suppliers',
            'employee' => 'employees',
            default => null,
        };

        if ($table === null) {
            return ['errors' => [], 'warnings' => ["{$label} uses {$type}; generic party mapping will be validated by the owning module when available."]];
        }

        if ($id === null || $id < 1) {
            return ['errors' => ["{$label}_id is required for {$type}."], 'warnings' => []];
        }

        if (! $this->tenantRecordExists($table, $id, $tenantId)) {
            return ['errors' => ["{$label}_id must reference an active {$type} in the same tenant."], 'warnings' => []];
        }

        if ($type === 'supplier_as_customer') {
            return ['errors' => [], 'warnings' => ['Supplier-as-customer requires a linked customer role before final invoicing if no Party module is active.']];
        }

        return ['errors' => [], 'warnings' => []];
    }

    private function tenantRecordExists(string $table, int $id, int $tenantId): bool
    {
        $query = DB::table($table)
            ->where('id', $id)
            ->where('tenant_id', $tenantId);

        if (Schema::hasColumn($table, 'is_active')) {
            $query->where(function ($query): void {
                $query->where('is_active', true)->orWhereNull('is_active');
            });
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->exists();
    }

    private function resolvePartyDisplayName(int $tenantId, string $ownerType, ?int $ownerId, ?string $fallback): ?string
    {
        if ($ownerId === null) {
            return $fallback;
        }

        $table = match ($ownerType) {
            'customer' => 'customers',
            'supplier', 'provider' => 'suppliers',
            'employee' => 'employees',
            default => null,
        };

        if ($table === null) {
            return $fallback;
        }

        $record = DB::table($table)->where('tenant_id', $tenantId)->where('id', $ownerId)->first();
        $data = $record !== null ? (array) $record : [];

        return match ($table) {
            'customers' => $data['display_name'] ?? $data['customer_name'] ?? $fallback,
            'suppliers' => $data['display_name'] ?? $data['supplier_name'] ?? $fallback,
            'employees' => $data['display_name'] ?? $data['full_name'] ?? $fallback,
            default => $fallback,
        };
    }

    private function hydrateLineTotals(array $line, bool $includeIncentive = false): array
    {
        $quantity = round((float) ($line['quantity'] ?? 0), 4);
        $unitPrice = round((float) ($line['unit_price'] ?? 0), 4);
        $grossAmount = round($quantity * $unitPrice, 4);
        $discountAmount = $this->resolveDiscountAmount(
            $grossAmount,
            (string) ($line['discount_type'] ?? ''),
            round((float) ($line['discount_value'] ?? 0), 4),
        );
        $lineTotal = round(max(0.0, $grossAmount - $discountAmount), 4);
        $taxAmount = $this->resolveTaxAmount(
            (int) ($line['tenant_id'] ?? 0),
            isset($line['tax_group_id']) ? (int) $line['tax_group_id'] : null,
            $lineTotal,
            $line['posting_date'] ?? null,
        );

        $line['quantity'] = $quantity;
        $line['unit_price'] = $unitPrice;
        $line['gross_amount'] = $grossAmount;
        $line['discount_amount'] = $discountAmount;
        $line['tax_amount'] = $taxAmount;
        $line['line_total'] = $lineTotal;
        $line['line_total_with_tax'] = round($lineTotal + $taxAmount, 4);

        if ($includeIncentive) {
            $line['incentive_amount'] = $this->resolveDiscountAmount(
                $lineTotal,
                (string) ($line['incentive_type'] ?? ''),
                round((float) ($line['incentive_value'] ?? 0), 4),
            );
        }

        return $line;
    }

    private function hydrateExternalServiceTotals(array $line): array
    {
        $quantity = round((float) ($line['quantity'] ?? 1), 4);
        $unitPrice = round((float) ($line['unit_price'] ?? 0), 4);
        $grossAmount = round($quantity * $unitPrice, 4);
        $discountAmount = $this->resolveDiscountAmount(
            $grossAmount,
            (string) ($line['discount_type'] ?? ''),
            round((float) ($line['discount_value'] ?? 0), 4),
        );

        $line['quantity'] = $quantity;
        $line['unit_price'] = $unitPrice;
        $line['discount_amount'] = $discountAmount;
        $line['tax_amount'] = 0.0;
        $line['line_total'] = $grossAmount;

        return $line;
    }

    private function resolveDiscountAmount(float $grossAmount, string $discountType, float $discountValue): float
    {
        if ($discountValue <= 0) {
            return 0.0;
        }

        $type = strtolower(trim($discountType));
        if ($type === 'percentage') {
            return round(min($grossAmount, ($grossAmount * $discountValue) / 100), 4);
        }

        return round(min($grossAmount, $discountValue), 4);
    }

    private function resolveTaxAmount(int $tenantId, ?int $taxGroupId, float $taxableAmount, mixed $postingDate = null): float
    {
        if ($tenantId < 1 || $taxGroupId === null || $taxGroupId < 1 || $taxableAmount <= 0) {
            return 0.0;
        }

        $result = $this->taxCalculationService->calculate(
            $tenantId,
            $taxGroupId,
            $taxableAmount,
            $postingDate !== null ? (string) $postingDate : null,
        );

        if ($result->isFailure()) {
            return 0.0;
        }

        $tax = $result->valueOrFail();

        return round((float) ($tax['tax_amount'] ?? 0), 4);
    }

    private function nextJobCardNumber(int $tenantId, ?int $organizationUnitId): string
    {
        if ($tenantId < 1) {
            return 'VSJC-' . now()->format('YmdHis');
        }

        $periodValue = now()->format('Y');
        $documentType = 'vehicle_service_job_card';
        $query = DB::table('sequences')
            ->where('tenant_id', $tenantId)
            ->where('document_type', $documentType)
            ->where('period_value', $periodValue)
            ->whereNull('deleted_at');

        $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);

        $sequence = $query->lockForUpdate()->first();
        if ($sequence === null) {
            $id = DB::table('sequences')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'document_type' => $documentType,
                'prefix' => 'VSJC-',
                'suffix' => '',
                'padding' => 5,
                'next_number' => 1,
                'period_type' => 'yearly',
                'period_value' => $periodValue,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $sequence = DB::table('sequences')->where('id', $id)->lockForUpdate()->first();
        }

        $nextNumber = (int) ($sequence->next_number ?? 1);
        DB::table('sequences')
            ->where('id', $sequence->id)
            ->update([
                'next_number' => $nextNumber + 1,
                'row_version' => ((int) ($sequence->row_version ?? 1)) + 1,
                'updated_at' => now(),
            ]);

        return (string) ($sequence->prefix ?? '')
            . str_pad((string) $nextNumber, (int) ($sequence->padding ?? 5), '0', STR_PAD_LEFT)
            . (string) ($sequence->suffix ?? '');
    }

    private function extractClientKey(array $payload): ?string
    {
        $metadata = $payload['metadata'] ?? null;
        if (! is_array($metadata)) {
            return null;
        }

        $clientKey = $metadata['client_key'] ?? null;

        return is_string($clientKey) && trim($clientKey) !== '' ? $clientKey : null;
    }

    private function resolveItemName(int $tenantId, ?int $itemId): ?string
    {
        if ($tenantId < 1 || $itemId === null || $itemId < 1) {
            return null;
        }

        $item = DB::table('items')
            ->where('tenant_id', $tenantId)
            ->where('id', $itemId)
            ->first();

        if ($item === null) {
            return null;
        }

        $sku = trim((string) ($item->sku ?? ''));
        $name = trim((string) ($item->name ?? ''));

        return trim($sku . ' - ' . $name, ' -');
    }

    private function laborItemBelongsToJob(int $tenantId, int $jobCardId, int $laborItemId): bool
    {
        return DB::table('vehicle_service_labor_items')
            ->where('tenant_id', $tenantId)
            ->where('job_card_id', $jobCardId)
            ->where('id', $laborItemId)
            ->exists();
    }

    private function activeEmployeeExists(int $tenantId, int $employeeId): bool
    {
        $query = DB::table('employees')
            ->where('tenant_id', $tenantId)
            ->where('id', $employeeId);

        if (Schema::hasColumn('employees', 'status')) {
            $query->where(function ($query): void {
                $query->where('status', 'active')->orWhere('status', 'ACTIVE')->orWhereNull('status');
            });
        }

        if (Schema::hasColumn('employees', 'is_active')) {
            $query->where(function ($query): void {
                $query->where('is_active', true)->orWhereNull('is_active');
            });
        }

        if (Schema::hasColumn('employees', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->exists();
    }

    private function expandComboComponents(DataRecord $parentLine, array $parentPayload): void
    {
        if ((string) ($parentPayload['line_type'] ?? '') !== 'combo') {
            return;
        }

        $tenantId = (int) $parentLine->get('tenant_id', 0);
        $jobCardId = (int) $parentLine->get('job_card_id', 0);
        $parentLineId = (int) $parentLine->id();
        $comboItemId = (int) $parentLine->get('item_id', 0);
        if ($tenantId < 1 || $jobCardId < 1 || $parentLineId < 1 || $comboItemId < 1) {
            return;
        }

        $parentQuantity = max(1.0, (float) $parentLine->get('quantity', 1));
        $components = DB::table('combo_items')
            ->join('items', 'items.id', '=', 'combo_items.component_item_id')
            ->where('combo_items.tenant_id', $tenantId)
            ->where('combo_items.combo_item_id', $comboItemId)
            ->whereNull('combo_items.deleted_at')
            ->orderBy('combo_items.sort_order')
            ->select([
                'combo_items.id',
                'combo_items.component_item_id',
                'combo_items.quantity',
                'combo_items.uom_id',
                'items.type',
                'items.is_stockable',
                'items.name',
                'items.sku',
            ])
            ->get();

        foreach ($components as $component) {
            $quantity = round($parentQuantity * (float) $component->quantity, 4);
            $type = (string) $component->type;
            $base = [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $parentLine->get('organization_unit_id'),
                'job_card_id' => $jobCardId,
                'combo_item_id' => (int) $component->id,
                'combo_group_key' => $parentLineId,
                'description' => trim((string) ($component->sku ?? '') . ' - ' . (string) ($component->name ?? ''), ' -'),
                'is_combo_component' => true,
                'item_id' => (int) $component->component_item_id,
                'quantity' => $quantity,
                'unit_price' => 0,
                'uom_id' => (int) $component->uom_id,
            ];

            if (in_array($type, ['labour', 'service'], true)) {
                $this->laborItemRepository->create($this->withDefaultRowVersion($this->hydrateLineTotals([
                    ...$base,
                    'requires_assignment' => true,
                    'status' => 'planned',
                ], true)));

                continue;
            }

            $this->jobCardLineRepository->create($this->withDefaultRowVersion($this->hydrateLineTotals([
                ...$base,
                'combo_parent_line_id' => $parentLineId,
                'line_type' => (bool) $component->is_stockable ? 'inventory' : $type,
                'requires_stock_movement' => (bool) $component->is_stockable,
            ])));
        }
    }

    private function deleteComboExpansion(int $tenantId, int $jobCardId, int $parentLineId): void
    {
        foreach ($this->jobCardLineRepository->list([
            'tenant_id' => $tenantId,
            'job_card_id' => $jobCardId,
            'combo_parent_line_id' => $parentLineId,
        ]) as $componentLine) {
            $this->jobCardLineRepository->delete((int) $componentLine->id());
        }

        foreach ($this->laborItemRepository->list([
            'tenant_id' => $tenantId,
            'job_card_id' => $jobCardId,
            'combo_group_key' => $parentLineId,
            'is_combo_component' => true,
        ]) as $componentLine) {
            $this->laborItemRepository->delete((int) $componentLine->id());
        }
    }

    private function withDefaultRowVersion(array $payload): array
    {
        if (! array_key_exists('row_version', $payload)) {
            $payload['row_version'] = 1;
        }

        return $payload;
    }
}
