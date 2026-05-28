<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Repositories\StockLevelRepositoryInterface;
use Modules\VehicleService\Application\Contracts\Services\VehicleServiceManagementServiceInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobCardLineRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobCardRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobCustomerSuppliedItemRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobExternalServiceRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobPaymentLinkRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobStatusHistoryRepositoryInterface;
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
        private readonly VehicleServiceNonInventoryItemRepositoryInterface $nonInventoryItemRepository,
        private readonly VehicleServiceJobExternalServiceRepositoryInterface $externalServiceRepository,
        private readonly VehicleServiceJobCustomerSuppliedItemRepositoryInterface $customerSuppliedItemRepository,
        private readonly VehicleServiceJobStatusHistoryRepositoryInterface $statusHistoryRepository,
        private readonly VehicleServiceSettingRepositoryInterface $settingRepository,
        private readonly VehicleServiceJobPaymentLinkRepositoryInterface $paymentLinkRepository,
        private readonly StockLevelRepositoryInterface $stockLevelRepository,
    ) {
    }

    public function upsertJobCardAggregate(?int $id, array $payload): Result
    {
        try {
            return $this->jobCardRepository->transaction(function () use ($id, $payload): Result {
                $headerPayload = $this->extractHeaderPayload($payload);
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

                if (is_array($payload['labor_items'] ?? null)) {
                    $syncLabor = $this->syncLaborItems($jobCardId, [
                        'tenant_id' => $tenantId,
                        'organization_unit_id' => $organizationUnitId,
                        'labor_items' => $payload['labor_items'],
                    ]);
                    if ($syncLabor->isFailure()) {
                        return $syncLabor;
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
                    $this->jobCardLineRepository->delete($lineId);
                    continue;
                }

                $upsert = $this->withDefaultRowVersion([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'job_card_id' => $jobCardId,
                    ...$linePayload,
                ]);

                if ($lineId === null) {
                    $this->jobCardLineRepository->create($upsert);
                    continue;
                }

                $this->jobCardLineRepository->update($lineId, $upsert);
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

            foreach ($laborItems as $linePayload) {
                if (! is_array($linePayload)) {
                    continue;
                }

                $lineId = isset($linePayload['id']) ? (int) $linePayload['id'] : null;
                if ((bool) ($linePayload['_delete'] ?? false) && $lineId !== null) {
                    $this->laborItemRepository->delete($lineId);
                    continue;
                }

                $upsert = $this->withDefaultRowVersion([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'job_card_id' => $jobCardId,
                    ...$linePayload,
                ]);

                if ($lineId === null) {
                    $this->laborItemRepository->create($upsert);
                    continue;
                }

                $this->laborItemRepository->update($lineId, $upsert);
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

                $upsert = $this->withDefaultRowVersion([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'job_card_id' => $jobCardId,
                    ...$linePayload,
                ]);

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

    public function getInvoiceableJobCards(int $tenantId, ?int $customerId): Result
    {
        try {
            $filters = [
                'tenant_id' => $tenantId,
                'status' => 'completed',
            ];
            if ($customerId !== null) {
                $filters['customer_id'] = $customerId;
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
                $filters['customer_id'] = $customerId;
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

        $discountTotal = $partsDiscount + $laborDiscount + $nonInventoryDiscount;
        $taxTotal = $partsTax + $laborTax + $nonInventoryTax;
        $grandTotal = $partsSubtotal + $laborSubtotal + $nonInventorySubtotal - $discountTotal + $taxTotal;

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
            $payload['external_services'],
            $payload['customer_supplied_items'],
        );

        return $payload;
    }

    private function withDefaultRowVersion(array $payload): array
    {
        if (! array_key_exists('row_version', $payload)) {
            $payload['row_version'] = 1;
        }

        return $payload;
    }
}
