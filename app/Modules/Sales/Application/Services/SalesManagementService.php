<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Finance\Application\Contracts\Services\TaxCalculationServiceInterface;
use Modules\Inventory\Application\Repositories\StockLevelRepositoryInterface;
use Modules\Sales\Application\Contracts\Services\SalesManagementServiceInterface;
use Modules\Sales\Application\Repositories\GdnHeaderRepositoryInterface;
use Modules\Sales\Application\Repositories\GdnLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesDocumentLinkRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesOrderLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesOrderRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesPaymentAllocationRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesReturnLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesReturnRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesSettingRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesStatusHistoryRepositoryInterface;
use Modules\Sales\Domain\Constants\SalesErrorCode;
use Throwable;

final class SalesManagementService implements SalesManagementServiceInterface
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $purchaseOrderRepository,
        private readonly SalesOrderLineRepositoryInterface $purchaseOrderLineRepository,
        private readonly GdnHeaderRepositoryInterface $gdnHeaderRepository,
        private readonly GdnLineRepositoryInterface $gdnLineRepository,
        private readonly SalesReturnRepositoryInterface $purchaseReturnRepository,
        private readonly SalesReturnLineRepositoryInterface $purchaseReturnLineRepository,
        private readonly SalesSettingRepositoryInterface $purchaseSettingRepository,
        private readonly SalesStatusHistoryRepositoryInterface $purchaseStatusHistoryRepository,
        private readonly SalesDocumentLinkRepositoryInterface $purchaseDocumentLinkRepository,
        private readonly SalesPaymentAllocationRepositoryInterface $purchasePaymentAllocationRepository,
        private readonly StockLevelRepositoryInterface $stockLevelRepository,
        private readonly TaxCalculationServiceInterface $taxCalculationService,
    ) {}

    public function upsertSalesOrderWithLines(?int $id, array $payload): Result
    {
        try {
            return $this->purchaseOrderRepository->transaction(function () use ($id, $payload): Result {
                $headerPayload = $this->extractHeaderPayload($payload);
                $header = $id === null
                    ? $this->purchaseOrderRepository->create($this->withDefaultRowVersion($headerPayload))
                    : $this->purchaseOrderRepository->update($id, $headerPayload);

                if (is_array($payload['lines'] ?? null)) {
                    $sync = $this->syncSalesOrderLines((int) $header->id(), [
                        'tenant_id' => $header->get('tenant_id'),
                        'organization_unit_id' => $header->get('organization_unit_id'),
                        'lines' => $payload['lines'],
                    ]);
                    if ($sync->isFailure()) {
                        return $sync;
                    }
                } else {
                    $this->recalculateSalesOrderTotals((int) $header->id(), (int) $header->get('tenant_id'));
                }

                $reloaded = $this->purchaseOrderRepository->findById((int) $header->id());
                if (! $reloaded instanceof DataRecord) {
                    return Result::failure(new Error(SalesErrorCode::NOT_FOUND, 'Sales order not found.'));
                }

                return Result::success($reloaded);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function syncSalesOrderLines(int $salesOrderId, array $payload): Result
    {
        try {
            return $this->purchaseOrderRepository->transaction(function () use ($salesOrderId, $payload): Result {
                $header = $this->purchaseOrderRepository->findById($salesOrderId);
                if (! $header instanceof DataRecord) {
                    return Result::failure(new Error(SalesErrorCode::NOT_FOUND, 'Sales order not found.'));
                }

                $tenantId = (int) ($payload['tenant_id'] ?? $header->get('tenant_id', 0));
                $organizationUnitId = $payload['organization_unit_id'] ?? $header->get('organization_unit_id');
                $lines = is_array($payload['lines'] ?? null) ? $payload['lines'] : [];

                foreach ($lines as $linePayload) {
                    if (! is_array($linePayload)) {
                        continue;
                    }

                    $lineId = isset($linePayload['id']) ? (int) $linePayload['id'] : null;
                    $deleteRequested = (bool) ($linePayload['_delete'] ?? false);
                    if ($deleteRequested && $lineId !== null) {
                        $this->purchaseOrderLineRepository->delete($lineId);

                        continue;
                    }

                    $upsert = $this->normalizeSalesOrderLinePayload(
                        $linePayload,
                        $tenantId,
                        $organizationUnitId,
                        $salesOrderId,
                    );

                    if ($lineId === null) {
                        $this->purchaseOrderLineRepository->create($this->withDefaultRowVersion($upsert));
                    } else {
                        $this->purchaseOrderLineRepository->update($lineId, $upsert);
                    }
                }

                $this->recalculateSalesOrderTotals($salesOrderId, $tenantId);

                return Result::success([
                    'sales_order_id' => $salesOrderId,
                    'synced' => true,
                ]);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function upsertGdnWithLines(?int $id, array $payload): Result
    {
        try {
            return $this->gdnHeaderRepository->transaction(function () use ($id, $payload): Result {
                $headerPayload = $this->extractHeaderPayload($payload);
                $header = $id === null
                    ? $this->gdnHeaderRepository->create($this->withDefaultRowVersion($headerPayload))
                    : $this->gdnHeaderRepository->update($id, $headerPayload);

                if (is_array($payload['lines'] ?? null)) {
                    $sync = $this->syncGdnLines((int) $header->id(), [
                        'tenant_id' => $header->get('tenant_id'),
                        'organization_unit_id' => $header->get('organization_unit_id'),
                        'lines' => $payload['lines'],
                    ]);
                    if ($sync->isFailure()) {
                        return $sync;
                    }
                } else {
                    $this->recalculateGdnTotals((int) $header->id(), (int) $header->get('tenant_id'));
                }

                $reloaded = $this->gdnHeaderRepository->findById((int) $header->id());
                if (! $reloaded instanceof DataRecord) {
                    return Result::failure(new Error(SalesErrorCode::NOT_FOUND, 'GDN not found.'));
                }

                return Result::success($reloaded);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function syncGdnLines(int $gdnHeaderId, array $payload): Result
    {
        try {
            return $this->gdnHeaderRepository->transaction(function () use ($gdnHeaderId, $payload): Result {
                $header = $this->gdnHeaderRepository->findById($gdnHeaderId);
                if (! $header instanceof DataRecord) {
                    return Result::failure(new Error(SalesErrorCode::NOT_FOUND, 'GDN not found.'));
                }

                $tenantId = (int) ($payload['tenant_id'] ?? $header->get('tenant_id', 0));
                $organizationUnitId = $payload['organization_unit_id'] ?? $header->get('organization_unit_id');
                $lines = is_array($payload['lines'] ?? null) ? $payload['lines'] : [];

                foreach ($lines as $linePayload) {
                    if (! is_array($linePayload)) {
                        continue;
                    }

                    $lineId = isset($linePayload['id']) ? (int) $linePayload['id'] : null;
                    $deleteRequested = (bool) ($linePayload['_delete'] ?? false);
                    if ($deleteRequested && $lineId !== null) {
                        $this->gdnLineRepository->delete($lineId);

                        continue;
                    }

                    $upsert = $this->normalizeGdnLinePayload(
                        $linePayload,
                        $tenantId,
                        $organizationUnitId,
                        $gdnHeaderId,
                    );
                    if ($lineId === null) {
                        $this->gdnLineRepository->create($this->withDefaultRowVersion($upsert));
                    } else {
                        $this->gdnLineRepository->update($lineId, $upsert);
                    }
                }

                $this->recalculateGdnTotals($gdnHeaderId, $tenantId);

                return Result::success([
                    'gdn_header_id' => $gdnHeaderId,
                    'synced' => true,
                ]);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function upsertSalesReturnWithLines(?int $id, array $payload): Result
    {
        try {
            return $this->purchaseReturnRepository->transaction(function () use ($id, $payload): Result {
                $headerPayload = $this->extractHeaderPayload($payload);
                $header = $id === null
                    ? $this->purchaseReturnRepository->create($this->withDefaultRowVersion($headerPayload))
                    : $this->purchaseReturnRepository->update($id, $headerPayload);

                if (is_array($payload['lines'] ?? null)) {
                    $sync = $this->syncSalesReturnLines((int) $header->id(), [
                        'tenant_id' => $header->get('tenant_id'),
                        'organization_unit_id' => $header->get('organization_unit_id'),
                        'lines' => $payload['lines'],
                    ]);
                    if ($sync->isFailure()) {
                        return $sync;
                    }
                } else {
                    $this->recalculateSalesReturnTotals((int) $header->id(), (int) $header->get('tenant_id'));
                }

                $reloaded = $this->purchaseReturnRepository->findById((int) $header->id());
                if (! $reloaded instanceof DataRecord) {
                    return Result::failure(new Error(SalesErrorCode::NOT_FOUND, 'Sales return not found.'));
                }

                return Result::success($reloaded);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function syncSalesReturnLines(int $salesReturnId, array $payload): Result
    {
        try {
            return $this->purchaseReturnRepository->transaction(function () use ($salesReturnId, $payload): Result {
                $header = $this->purchaseReturnRepository->findById($salesReturnId);
                if (! $header instanceof DataRecord) {
                    return Result::failure(new Error(SalesErrorCode::NOT_FOUND, 'Sales return not found.'));
                }

                $tenantId = (int) ($payload['tenant_id'] ?? $header->get('tenant_id', 0));
                $organizationUnitId = $payload['organization_unit_id'] ?? $header->get('organization_unit_id');
                $lines = is_array($payload['lines'] ?? null) ? $payload['lines'] : [];

                foreach ($lines as $linePayload) {
                    if (! is_array($linePayload)) {
                        continue;
                    }

                    $lineId = isset($linePayload['id']) ? (int) $linePayload['id'] : null;
                    $deleteRequested = (bool) ($linePayload['_delete'] ?? false);
                    if ($deleteRequested && $lineId !== null) {
                        $this->purchaseReturnLineRepository->delete($lineId);

                        continue;
                    }

                    $upsert = $this->normalizeSalesReturnLinePayload(
                        $linePayload,
                        $tenantId,
                        $organizationUnitId,
                        $salesReturnId,
                    );

                    if ($lineId === null) {
                        $this->purchaseReturnLineRepository->create($this->withDefaultRowVersion($upsert));
                    } else {
                        $this->purchaseReturnLineRepository->update($lineId, $upsert);
                    }
                }

                $this->recalculateSalesReturnTotals($salesReturnId, $tenantId);

                return Result::success([
                    'sales_return_id' => $salesReturnId,
                    'synced' => true,
                ]);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function getStatusHistory(string $entityType, int $entityId, int $tenantId): Result
    {
        try {
            return Result::success($this->purchaseStatusHistoryRepository->list([
                'tenant_id' => $tenantId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ]));
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function getSalesSettings(int $tenantId, ?int $organizationUnitId): Result
    {
        try {
            $records = $organizationUnitId !== null
                ? $this->purchaseSettingRepository->list([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'is_active' => true,
                ])
                : [];

            if ($records === []) {
                $records = $this->purchaseSettingRepository->list([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => null,
                    'is_active' => true,
                ]);
            }

            return Result::success($records[0] ?? null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function upsertSalesSettings(array $payload): Result
    {
        try {
            $tenantId = (int) ($payload['tenant_id'] ?? 0);
            if ($tenantId < 1) {
                return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, 'tenant_id is required.'));
            }

            $organizationUnitId = isset($payload['organization_unit_id'])
                ? (int) $payload['organization_unit_id']
                : null;

            $existing = $this->purchaseSettingRepository->list([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
            ]);

            if ($existing !== []) {
                $updated = $this->purchaseSettingRepository->update((int) $existing[0]->id(), $payload);

                return Result::success($updated);
            }

            return Result::success($this->purchaseSettingRepository->create($this->withDefaultRowVersion($payload)));
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function initializeSalesSettings(array $payload): Result
    {
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        if ($tenantId < 1) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, 'tenant_id is required.'));
        }

        $organizationUnitId = isset($payload['organization_unit_id'])
            ? (int) $payload['organization_unit_id']
            : null;

        $existing = $this->purchaseSettingRepository->list([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
        ]);

        if ($existing !== []) {
            return Result::success($existing[0]);
        }

        return $this->upsertSalesSettings(array_merge([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'is_active' => true,
            'allow_direct_gdn' => true,
            'allow_direct_sales_invoice' => true,
            'allow_return_without_original' => true,
            'allow_header_discount' => true,
            'allow_line_discount' => true,
            'tax_calculation_level' => 'line',
            'header_discount_allocation_method' => 'proportional',
            'default_sales_order_status' => 'draft',
            'default_gdn_status' => 'draft',
            'default_sales_invoice_status' => 'draft',
            'default_sales_return_status' => 'draft',
            'default_customer_advance_account_id' => $payload['default_customer_advance_account_id'] ?? null,
            'default_refund_account_id' => $payload['default_refund_account_id'] ?? null,
            'require_sales_order_before_gdn' => false,
            'require_gdn_before_invoice' => false,
        ], $payload));
    }

    public function getAvailableSalesOrderLinesForGdn(int $tenantId, int $salesOrderId): Result
    {
        try {
            $lines = $this->purchaseOrderLineRepository->list([
                'tenant_id' => $tenantId,
                'sales_order_id' => $salesOrderId,
            ]);

            $result = [];
            foreach ($lines as $line) {
                $ordered = (float) $line->get('ordered_qty', 0);
                $delivered = (float) $line->get('delivered_qty', 0);
                $available = round($ordered - $delivered, 4);
                if ($available <= 0) {
                    continue;
                }

                $row = $line->toArray();
                $row['available_deliver_qty'] = $available;
                $result[] = $row;
            }

            return Result::success($result);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function getAvailableSalesOrderLinesForInvoice(int $tenantId, int $salesOrderId): Result
    {
        try {
            $lines = $this->purchaseOrderLineRepository->list([
                'tenant_id' => $tenantId,
                'sales_order_id' => $salesOrderId,
            ]);

            $result = [];
            foreach ($lines as $line) {
                $ordered = (float) $line->get('ordered_qty', 0);
                $invoiced = (float) $line->get('invoiced_qty', 0);
                $cancelled = (float) $line->get('cancelled_qty', 0);
                $available = round($ordered - $invoiced - $cancelled, 4);
                if ($available <= 0) {
                    continue;
                }

                $row = $line->toArray();
                $row['available_invoice_qty'] = $available;
                $result[] = $row;
            }

            return Result::success($result);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function getAvailableGdnLinesForDocument(int $tenantId, int $gdnHeaderId): Result
    {
        try {
            $lines = $this->gdnLineRepository->list([
                'tenant_id' => $tenantId,
                'gdn_header_id' => $gdnHeaderId,
            ]);

            $result = [];
            foreach ($lines as $line) {
                $delivered = (float) $line->get('delivered_qty', 0);
                $invoiced = (float) $line->get('invoiced_qty', 0);
                $available = round($delivered - $invoiced, 4);
                if ($available <= 0) {
                    continue;
                }

                $row = $line->toArray();
                $row['available_document_qty'] = $available;
                $result[] = $row;
            }

            return Result::success($result);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function getReturnableLines(string $sourceType, int $sourceId, int $tenantId): Result
    {
        try {
            $records = [];
            if ($sourceType === 'gdn_header') {
                $lines = $this->gdnLineRepository->list([
                    'tenant_id' => $tenantId,
                    'gdn_header_id' => $sourceId,
                ]);
                foreach ($lines as $line) {
                    $baseQty = (float) $line->get('delivered_qty', 0);
                    $returned = (float) $line->get('returned_qty', 0);
                    $available = round($baseQty - $returned, 4);
                    if ($available <= 0) {
                        continue;
                    }

                    $row = $line->toArray();
                    $row['available_return_qty'] = $available;
                    $row['source_type'] = 'gdn_line';
                    $records[] = $row;
                }
            }

            if ($sourceType === 'sales_order') {
                $lines = $this->purchaseOrderLineRepository->list([
                    'tenant_id' => $tenantId,
                    'sales_order_id' => $sourceId,
                ]);
                foreach ($lines as $line) {
                    $received = (float) $line->get('delivered_qty', 0);
                    $returned = (float) $line->get('returned_qty', 0);
                    $available = round($received - $returned, 4);
                    if ($available <= 0) {
                        continue;
                    }

                    $row = $line->toArray();
                    $row['available_return_qty'] = $available;
                    $row['source_type'] = 'sales_order_line';
                    $records[] = $row;
                }
            }

            return Result::success($records);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function getReceivableDocuments(int $tenantId, ?int $customerId): Result
    {
        try {
            $criteria = [
                'tenant_id' => $tenantId,
                'status' => 'active',
            ];
            if ($customerId !== null) {
                $criteria['source_type'] = 'sales_order';
            }

            $links = $this->purchaseDocumentLinkRepository->list($criteria);
            $allocations = $this->purchasePaymentAllocationRepository->list([
                'tenant_id' => $tenantId,
                'status' => 'active',
            ]);

            $linkedByDocument = [];
            foreach ($links as $link) {
                $documentId = (int) $link->get('document_id', 0);
                if ($documentId < 1) {
                    continue;
                }

                $linkedByDocument[$documentId] = ($linkedByDocument[$documentId] ?? 0.0)
                    + (float) $link->get('linked_amount', 0);
            }

            $allocatedByDocument = [];
            foreach ($allocations as $allocation) {
                $documentId = (int) $allocation->get('document_id', 0);
                if ($documentId < 1) {
                    continue;
                }

                $allocatedByDocument[$documentId] = ($allocatedByDocument[$documentId] ?? 0.0)
                    + (float) $allocation->get('allocated_amount', 0);
            }

            $result = [];
            foreach ($linkedByDocument as $documentId => $linkedTotal) {
                $allocatedTotal = $allocatedByDocument[$documentId] ?? 0.0;
                $outstanding = round(max(0.0, $linkedTotal - $allocatedTotal), 4);
                if ($outstanding <= 0.0) {
                    continue;
                }

                $result[] = [
                    'document_id' => $documentId,
                    'linked_amount' => round($linkedTotal, 4),
                    'allocated_amount' => round($allocatedTotal, 4),
                    'outstanding_amount' => $outstanding,
                ];
            }

            return Result::success($result);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function getStockAvailability(
        int $tenantId,
        int $itemId,
        ?int $warehouseId,
        ?int $locationId,
    ): Result {
        try {
            if ($tenantId < 1 || $itemId < 1) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'tenant_id and item_id are required.',
                ));
            }

            $criteria = [
                'tenant_id' => $tenantId,
                'item_id' => $itemId,
            ];

            if ($warehouseId !== null) {
                $criteria['warehouse_id'] = $warehouseId;
            }

            if ($locationId !== null) {
                $criteria['location_id'] = $locationId;
            }

            $levels = $this->stockLevelRepository->list($criteria);

            $rows = [];
            $onHandTotal = 0.0;
            $reservedTotal = 0.0;
            $availableTotal = 0.0;

            foreach ($levels as $level) {
                $onHand = round((float) $level->get('quantity_on_hand', 0), 4);
                $reserved = round((float) $level->get('quantity_reserved', 0), 4);
                $available = round($onHand - $reserved, 4);

                $rows[] = [
                    'stock_level_id' => (int) $level->id(),
                    'warehouse_id' => $level->get('warehouse_id'),
                    'location_id' => $level->get('location_id'),
                    'batch_id' => $level->get('batch_id'),
                    'serial_id' => $level->get('serial_id'),
                    'quantity_on_hand' => $onHand,
                    'quantity_reserved' => $reserved,
                    'quantity_available' => $available,
                ];

                $onHandTotal += $onHand;
                $reservedTotal += $reserved;
                $availableTotal += $available;
            }

            return Result::success([
                'tenant_id' => $tenantId,
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
                'quantity_on_hand_total' => round($onHandTotal, 4),
                'quantity_reserved_total' => round($reservedTotal, 4),
                'quantity_available_total' => round($availableTotal, 4),
                'levels' => $rows,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function calculateInvoicePreview(array $payload): Result
    {
        try {
            $lines = is_array($payload['lines'] ?? null) ? $payload['lines'] : [];

            $subtotal = 0.0;
            $taxTotal = 0.0;
            $discountTotal = 0.0;
            $linePreviews = [];

            foreach ($lines as $line) {
                if (! is_array($line)) {
                    continue;
                }

                $qty = round((float) ($line['quantity'] ?? $line['linked_quantity'] ?? 0), 4);
                $unitPrice = round((float) ($line['unit_price'] ?? 0), 4);
                $lineGross = round($qty * $unitPrice, 4);

                $discountAmount = $this->resolveDiscountAmount(
                    $lineGross,
                    (string) ($line['discount_type'] ?? ''),
                    round((float) ($line['discount_value'] ?? 0), 4),
                );
                $lineNet = round(max(0.0, $lineGross - $discountAmount), 4);
                $lineTax = $this->resolveTaxAmount(
                    (int) ($payload['tenant_id'] ?? $line['tenant_id'] ?? 0),
                    isset($line['tax_group_id']) ? (int) $line['tax_group_id'] : null,
                    $lineNet,
                    $payload['posting_date'] ?? null,
                );

                $subtotal += $lineGross;
                $discountTotal += $discountAmount;
                $taxTotal += $lineTax;

                $linePreviews[] = [
                    'line_id' => $line['id'] ?? null,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'gross_amount' => $lineGross,
                    'discount_amount' => $discountAmount,
                    'tax_amount' => $lineTax,
                    'line_total' => $lineNet,
                    'line_total_with_tax' => round($lineNet + $lineTax, 4),
                ];
            }

            $subtotal = round($subtotal, 4);
            $discountTotal = round($discountTotal, 4);
            $taxTotal = round($taxTotal, 4);
            $grandTotal = round(max(0.0, $subtotal - $discountTotal + $taxTotal), 4);

            return Result::success([
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'lines' => $linePreviews,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /** @return array<string, mixed> */
    private function extractHeaderPayload(array $payload): array
    {
        unset($payload['lines']);
        unset(
            $payload['subtotal'],
            $payload['line_tax_total'],
            $payload['line_discount_total'],
            $payload['header_discount_amount'],
            $payload['header_tax_amount'],
            $payload['discount_total'],
            $payload['tax_total'],
            $payload['grand_total'],
            $payload['balance'],
        );

        return $payload;
    }

    /** @return array<string, mixed> */
    private function withDefaultRowVersion(array $payload): array
    {
        if (! array_key_exists('row_version', $payload)) {
            $payload['row_version'] = 1;
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    private function normalizeSalesOrderLinePayload(
        array $line,
        int $tenantId,
        mixed $organizationUnitId,
        int $salesOrderId,
    ): array {
        $orderedQty = round((float) ($line['ordered_qty'] ?? 0), 4);
        $orderedBaseQty = round((float) ($line['ordered_base_qty'] ?? $orderedQty), 4);
        $reservedQty = round((float) ($line['reserved_qty'] ?? 0), 4);
        $pickedQty = round((float) ($line['picked_qty'] ?? 0), 4);
        $deliveredQty = round((float) ($line['delivered_qty'] ?? 0), 4);
        $invoicedQty = round((float) ($line['invoiced_qty'] ?? 0), 4);
        $returnedQty = round((float) ($line['returned_qty'] ?? 0), 4);
        $cancelledQty = round((float) ($line['cancelled_qty'] ?? 0), 4);
        $outstandingQty = round(
            max(0.0, $orderedQty - $deliveredQty - $cancelledQty),
            4,
        );
        $unitPrice = round((float) ($line['unit_price'] ?? 0), 4);

        $line = array_merge($line, [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $line['organization_unit_id'] ?? $organizationUnitId,
            'sales_order_id' => $salesOrderId,
            'ordered_qty' => $orderedQty,
            'ordered_base_qty' => $orderedBaseQty,
            'reserved_qty' => $reservedQty,
            'picked_qty' => $pickedQty,
            'delivered_qty' => $deliveredQty,
            'invoiced_qty' => $invoicedQty,
            'returned_qty' => $returnedQty,
            'cancelled_qty' => $cancelledQty,
            'outstanding_qty' => $outstandingQty,
            'unit_price' => $unitPrice,
        ]);

        return $this->hydrateLineTotals($line, $orderedQty, $unitPrice);
    }

    /** @return array<string, mixed> */
    private function normalizeGdnLinePayload(
        array $line,
        int $tenantId,
        mixed $organizationUnitId,
        int $gdnHeaderId,
    ): array {
        $expectedQty = round((float) ($line['expected_qty'] ?? 0), 4);
        $pickedQty = round((float) ($line['picked_qty'] ?? 0), 4);
        $deliveredQty = round((float) ($line['delivered_qty'] ?? 0), 4);
        $deliveredBaseQty = round((float) ($line['delivered_base_qty'] ?? $deliveredQty), 4);
        $shortQty = round((float) ($line['short_qty'] ?? 0), 4);
        $returnedQty = round((float) ($line['returned_qty'] ?? 0), 4);
        $unitPrice = round((float) ($line['unit_price'] ?? 0), 4);

        $line = array_merge($line, [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $line['organization_unit_id'] ?? $organizationUnitId,
            'gdn_header_id' => $gdnHeaderId,
            'expected_qty' => $expectedQty,
            'picked_qty' => $pickedQty,
            'delivered_qty' => $deliveredQty,
            'delivered_base_qty' => $deliveredBaseQty,
            'short_qty' => $shortQty,
            'returned_qty' => $returnedQty,
            'unit_price' => $unitPrice,
        ]);

        return $this->hydrateLineTotals($line, $deliveredQty, $unitPrice);
    }

    /** @return array<string, mixed> */
    private function normalizeSalesReturnLinePayload(
        array $line,
        int $tenantId,
        mixed $organizationUnitId,
        int $salesReturnId,
    ): array {
        $returnQty = round((float) ($line['return_qty'] ?? 0), 4);
        $returnBaseQty = round((float) ($line['return_base_qty'] ?? $returnQty), 4);
        $restockQty = round((float) ($line['restock_qty'] ?? 0), 4);
        $scrapQty = round((float) ($line['scrap_qty'] ?? 0), 4);
        $refundQty = round((float) ($line['refund_qty'] ?? 0), 4);
        $writeOffQty = round((float) ($line['write_off_qty'] ?? 0), 4);
        $unitPrice = round((float) ($line['unit_price'] ?? 0), 4);

        $line = array_merge($line, [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $line['organization_unit_id'] ?? $organizationUnitId,
            'sales_return_id' => $salesReturnId,
            'return_qty' => $returnQty,
            'return_base_qty' => $returnBaseQty,
            'restock_qty' => $restockQty,
            'scrap_qty' => $scrapQty,
            'refund_qty' => $refundQty,
            'write_off_qty' => $writeOffQty,
            'unit_price' => $unitPrice,
        ]);

        return $this->hydrateLineTotals($line, $returnQty, $unitPrice);
    }

    /** @param array<string, mixed> $line */
    private function hydrateLineTotals(array $line, float $quantity, float $unitPrice): array
    {
        $grossAmount = round($quantity * $unitPrice, 4);
        $discountType = (string) ($line['discount_type'] ?? '');
        $discountValue = round((float) ($line['discount_value'] ?? 0), 4);
        $discountAmount = $this->resolveDiscountAmount($grossAmount, $discountType, $discountValue);
        $lineTotal = round(max(0.0, $grossAmount - $discountAmount), 4);
        $taxAmount = $this->resolveTaxAmount(
            (int) ($line['tenant_id'] ?? 0),
            isset($line['tax_group_id']) ? (int) $line['tax_group_id'] : null,
            $lineTotal,
            $line['posting_date'] ?? null,
        );

        $line['gross_amount'] = $grossAmount;
        $line['discount_amount'] = $discountAmount;
        $line['tax_amount'] = $taxAmount;
        $line['line_total'] = $lineTotal;
        $line['line_total_with_tax'] = round($lineTotal + $taxAmount, 4);

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

    private function resolveHeaderDiscountAmount(DataRecord $header, float $discountableAmount): float
    {
        return $this->resolveDiscountAmount(
            max(0.0, $discountableAmount),
            (string) $header->get('header_discount_type', ''),
            round((float) $header->get('header_discount_value', 0), 4),
        );
    }

    private function recalculateSalesOrderTotals(int $purchaseOrderId, int $tenantId): void
    {
        $lines = $this->purchaseOrderLineRepository->list([
            'tenant_id' => $tenantId,
            'sales_order_id' => $purchaseOrderId,
        ]);

        $this->applyHeaderTotals($lines, function (array $totals) use ($purchaseOrderId, $lines): void {
            $header = $this->purchaseOrderRepository->findById($purchaseOrderId);
            if (! $header instanceof DataRecord) {
                return;
            }

            $headerDiscountAmount = $this->resolveHeaderDiscountAmount(
                $header,
                $totals['subtotal'] - $totals['line_discount_total'],
            );
            $headerTaxAmount = $this->resolveTaxAmount(
                (int) $header->get('tenant_id', 0),
                $header->get('header_tax_group_id') !== null ? (int) $header->get('header_tax_group_id') : null,
                max(0.0, $totals['subtotal'] - $totals['line_discount_total'] - $headerDiscountAmount),
                $header->get('order_date') ?? $header->get('document_date') ?? null,
            );
            $debitNoteTotal = round((float) $header->get('debit_note_total', 0), 4);
            $creditNoteTotal = round((float) $header->get('credit_note_total', 0), 4);
            $paidAmount = round((float) $header->get('paid_amount', 0), 4);

            $discountTotal = round($totals['line_discount_total'] + $headerDiscountAmount, 4);
            $taxTotal = round($totals['line_tax_total'] + $headerTaxAmount, 4);
            $grandTotal = round(
                $totals['subtotal'] - $discountTotal + $taxTotal + $debitNoteTotal - $creditNoteTotal,
                4,
            );
            $balance = round($grandTotal - $paidAmount, 4);

            $orderedQtyTotal = 0.0;
            $reservedQtyTotal = 0.0;
            $pickedQtyTotal = 0.0;
            $deliveredQtyTotal = 0.0;
            $invoicedQtyTotal = 0.0;
            $returnedQtyTotal = 0.0;
            $cancelledQtyTotal = 0.0;
            $outstandingQtyTotal = 0.0;

            foreach ($lines as $line) {
                $orderedQtyTotal += (float) $line->get('ordered_qty', 0);
                $reservedQtyTotal += (float) $line->get('reserved_qty', 0);
                $pickedQtyTotal += (float) $line->get('picked_qty', 0);
                $deliveredQtyTotal += (float) $line->get('delivered_qty', 0);
                $invoicedQtyTotal += (float) $line->get('invoiced_qty', 0);
                $returnedQtyTotal += (float) $line->get('returned_qty', 0);
                $cancelledQtyTotal += (float) $line->get('cancelled_qty', 0);
                $outstandingQtyTotal += (float) $line->get('outstanding_qty', 0);
            }

            $this->purchaseOrderRepository->update($purchaseOrderId, [
                'subtotal' => $totals['subtotal'],
                'line_tax_total' => $totals['line_tax_total'],
                'line_discount_total' => $totals['line_discount_total'],
                'header_discount_amount' => $headerDiscountAmount,
                'header_tax_amount' => $headerTaxAmount,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'balance' => $balance,
                'ordered_qty_total' => round($orderedQtyTotal, 4),
                'reserved_qty_total' => round($reservedQtyTotal, 4),
                'picked_qty_total' => round($pickedQtyTotal, 4),
                'delivered_qty_total' => round($deliveredQtyTotal, 4),
                'invoiced_qty_total' => round($invoicedQtyTotal, 4),
                'returned_qty_total' => round($returnedQtyTotal, 4),
                'cancelled_qty_total' => round($cancelledQtyTotal, 4),
                'outstanding_qty_total' => round($outstandingQtyTotal, 4),
            ]);
        });
    }

    private function recalculateGdnTotals(int $gdnHeaderId, int $tenantId): void
    {
        $lines = $this->gdnLineRepository->list([
            'tenant_id' => $tenantId,
            'gdn_header_id' => $gdnHeaderId,
        ]);

        $this->applyHeaderTotals($lines, function (array $totals) use ($gdnHeaderId, $lines): void {
            $header = $this->gdnHeaderRepository->findById($gdnHeaderId);
            if (! $header instanceof DataRecord) {
                return;
            }

            $headerDiscountAmount = $this->resolveHeaderDiscountAmount(
                $header,
                $totals['subtotal'] - $totals['line_discount_total'],
            );
            $headerTaxAmount = $this->resolveTaxAmount(
                (int) $header->get('tenant_id', 0),
                $header->get('header_tax_group_id') !== null ? (int) $header->get('header_tax_group_id') : null,
                max(0.0, $totals['subtotal'] - $totals['line_discount_total'] - $headerDiscountAmount),
                $header->get('gdn_date') ?? $header->get('document_date') ?? null,
            );
            $debitNoteTotal = round((float) $header->get('debit_note_total', 0), 4);
            $creditNoteTotal = round((float) $header->get('credit_note_total', 0), 4);

            $discountTotal = round($totals['line_discount_total'] + $headerDiscountAmount, 4);
            $taxTotal = round($totals['line_tax_total'] + $headerTaxAmount, 4);
            $grandTotal = round(
                $totals['subtotal'] - $discountTotal + $taxTotal + $debitNoteTotal - $creditNoteTotal,
                4,
            );

            $expectedQtyTotal = 0.0;
            $pickedQtyTotal = 0.0;
            $deliveredQtyTotal = 0.0;
            $shortQtyTotal = 0.0;
            $rejectedQtyTotal = 0.0;
            $returnedQtyTotal = 0.0;

            foreach ($lines as $line) {
                $expectedQtyTotal += (float) $line->get('expected_qty', 0);
                $pickedQtyTotal += (float) $line->get('picked_qty', 0);
                $deliveredQtyTotal += (float) $line->get('delivered_qty', 0);
                $shortQtyTotal += (float) $line->get('short_qty', 0);
                $rejectedQtyTotal += (float) $line->get('rejected_qty', 0);
                $returnedQtyTotal += (float) $line->get('returned_qty', 0);
            }

            $this->gdnHeaderRepository->update($gdnHeaderId, [
                'subtotal' => $totals['subtotal'],
                'line_tax_total' => $totals['line_tax_total'],
                'line_discount_total' => $totals['line_discount_total'],
                'header_discount_amount' => $headerDiscountAmount,
                'header_tax_amount' => $headerTaxAmount,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'expected_qty_total' => round($expectedQtyTotal, 4),
                'picked_qty_total' => round($pickedQtyTotal, 4),
                'delivered_qty_total' => round($deliveredQtyTotal, 4),
                'short_qty_total' => round($shortQtyTotal, 4),
                'rejected_qty_total' => round($rejectedQtyTotal, 4),
                'returned_qty_total' => round($returnedQtyTotal, 4),
            ]);
        });
    }

    private function recalculateSalesReturnTotals(int $purchaseReturnId, int $tenantId): void
    {
        $lines = $this->purchaseReturnLineRepository->list([
            'tenant_id' => $tenantId,
            'sales_return_id' => $purchaseReturnId,
        ]);

        $this->applyHeaderTotals($lines, function (array $totals) use ($purchaseReturnId, $lines): void {
            $header = $this->purchaseReturnRepository->findById($purchaseReturnId);
            if (! $header instanceof DataRecord) {
                return;
            }

            $headerDiscountAmount = $this->resolveHeaderDiscountAmount(
                $header,
                $totals['subtotal'] - $totals['line_discount_total'],
            );
            $headerTaxAmount = $this->resolveTaxAmount(
                (int) $header->get('tenant_id', 0),
                $header->get('header_tax_group_id') !== null ? (int) $header->get('header_tax_group_id') : null,
                max(0.0, $totals['subtotal'] - $totals['line_discount_total'] - $headerDiscountAmount),
                $header->get('return_date') ?? $header->get('document_date') ?? null,
            );
            $debitNoteTotal = round((float) $header->get('debit_note_total', 0), 4);
            $creditNoteTotal = round((float) $header->get('credit_note_total', 0), 4);
            $lineRestockingTotal = round((float) $totals['line_restocking_total'], 4);

            $discountTotal = round($totals['line_discount_total'] + $headerDiscountAmount, 4);
            $taxTotal = round($totals['line_tax_total'] + $headerTaxAmount, 4);
            $grandTotal = round(
                $totals['subtotal']
                - $discountTotal
                + $taxTotal
                + $debitNoteTotal
                - $creditNoteTotal
                - $lineRestockingTotal,
                4,
            );

            $returnQtyTotal = 0.0;
            $restockedQtyTotal = 0.0;
            $scrappedQtyTotal = 0.0;

            foreach ($lines as $line) {
                $returnQtyTotal += (float) $line->get('return_qty', 0);
                $restockedQtyTotal += (float) $line->get('restock_qty', 0);
                $scrappedQtyTotal += (float) $line->get('scrap_qty', 0);
            }

            $this->purchaseReturnRepository->update($purchaseReturnId, [
                'subtotal' => $totals['subtotal'],
                'line_tax_total' => $totals['line_tax_total'],
                'line_discount_total' => $totals['line_discount_total'],
                'line_restocking_total' => $lineRestockingTotal,
                'header_discount_amount' => $headerDiscountAmount,
                'header_tax_amount' => $headerTaxAmount,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'return_qty_total' => round($returnQtyTotal, 4),
                'restocked_qty_total' => round($restockedQtyTotal, 4),
                'scrapped_qty_total' => round($scrappedQtyTotal, 4),
            ]);
        });
    }

    /** @param list<DataRecord> $lines */
    private function applyHeaderTotals(array $lines, callable $applier): void
    {
        $totals = [
            'subtotal' => 0.0,
            'line_tax_total' => 0.0,
            'line_discount_total' => 0.0,
            'line_restocking_total' => 0.0,
        ];

        foreach ($lines as $line) {
            $totals['subtotal'] += (float) $line->get('gross_amount', 0);
            $totals['line_tax_total'] += (float) $line->get('tax_amount', 0);
            $totals['line_discount_total'] += (float) $line->get('discount_amount', 0);
            $totals['line_restocking_total'] += (float) $line->get('restocking_fee', 0);
        }

        $totals['subtotal'] = round($totals['subtotal'], 4);
        $totals['line_tax_total'] = round($totals['line_tax_total'], 4);
        $totals['line_discount_total'] = round($totals['line_discount_total'], 4);
        $totals['line_restocking_total'] = round($totals['line_restocking_total'], 4);

        $applier($totals);
    }
}
