<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
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
    ) {
    }

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

    public function syncSalesOrderLines(int $purchaseOrderId, array $payload): Result
    {
        try {
            return $this->purchaseOrderRepository->transaction(function () use ($purchaseOrderId, $payload): Result {
                $header = $this->purchaseOrderRepository->findById($purchaseOrderId);
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
                        $purchaseOrderId,
                    );

                    if ($lineId === null) {
                        $this->purchaseOrderLineRepository->create($this->withDefaultRowVersion($upsert));
                    } else {
                        $this->purchaseOrderLineRepository->update($lineId, $upsert);
                    }
                }

                $this->recalculateSalesOrderTotals($purchaseOrderId, $tenantId);

                return Result::success([
                    'sales_order_id' => $purchaseOrderId,
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
                    return Result::failure(new Error(SalesErrorCode::NOT_FOUND, 'GRN not found.'));
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
                    return Result::failure(new Error(SalesErrorCode::NOT_FOUND, 'GRN not found.'));
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

    public function syncSalesReturnLines(int $purchaseReturnId, array $payload): Result
    {
        try {
            return $this->purchaseReturnRepository->transaction(function () use ($purchaseReturnId, $payload): Result {
                $header = $this->purchaseReturnRepository->findById($purchaseReturnId);
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
                        $purchaseReturnId,
                    );

                    if ($lineId === null) {
                        $this->purchaseReturnLineRepository->create($this->withDefaultRowVersion($upsert));
                    } else {
                        $this->purchaseReturnLineRepository->update($lineId, $upsert);
                    }
                }

                $this->recalculateSalesReturnTotals($purchaseReturnId, $tenantId);

                return Result::success([
                    'sales_return_id' => $purchaseReturnId,
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
            'require_sales_order_before_gdn' => false,
            'require_gdn_before_invoice' => false,
        ], $payload));
    }

    public function getAvailableSalesOrderLinesForGdn(int $tenantId, int $purchaseOrderId): Result
    {
        try {
            $lines = $this->purchaseOrderLineRepository->list([
                'tenant_id' => $tenantId,
                'sales_order_id' => $purchaseOrderId,
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

    /** @return array<string, mixed> */
    private function extractHeaderPayload(array $payload): array
    {
        unset($payload['lines']);

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
        int $purchaseOrderId,
    ): array {
        $orderedQty = round((float) ($line['ordered_qty'] ?? 0), 4);
        $unitPrice = round((float) ($line['unit_price'] ?? 0), 4);

        $line = array_merge($line, [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $line['organization_unit_id'] ?? $organizationUnitId,
            'sales_order_id' => $purchaseOrderId,
            'ordered_qty' => $orderedQty,
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
        $receivedQty = round((float) ($line['received_qty'] ?? 0), 4);
        $unitPrice = round((float) ($line['unit_price'] ?? 0), 4);
        $acceptedQty = array_key_exists('accepted_qty', $line)
            ? round((float) ($line['accepted_qty'] ?? 0), 4)
            : max(0.0, $receivedQty - round((float) ($line['rejected_qty'] ?? 0), 4));

        $line = array_merge($line, [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $line['organization_unit_id'] ?? $organizationUnitId,
            'gdn_header_id' => $gdnHeaderId,
            'received_qty' => $receivedQty,
            'accepted_qty' => $acceptedQty,
            'unit_price' => $unitPrice,
        ]);

        return $this->hydrateLineTotals($line, $receivedQty, $unitPrice);
    }

    /** @return array<string, mixed> */
    private function normalizeSalesReturnLinePayload(
        array $line,
        int $tenantId,
        mixed $organizationUnitId,
        int $purchaseReturnId,
    ): array {
        $returnQty = round((float) ($line['return_qty'] ?? 0), 4);
        $unitPrice = round((float) ($line['unit_price'] ?? 0), 4);

        $line = array_merge($line, [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $line['organization_unit_id'] ?? $organizationUnitId,
            'sales_return_id' => $purchaseReturnId,
            'return_qty' => $returnQty,
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
        $taxAmount = round((float) ($line['tax_amount'] ?? 0), 4);

        $line['gross_amount'] = $grossAmount;
        $line['discount_amount'] = $discountAmount;
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

    private function recalculateSalesOrderTotals(int $purchaseOrderId, int $tenantId): void
    {
        $lines = $this->purchaseOrderLineRepository->list([
            'tenant_id' => $tenantId,
            'sales_order_id' => $purchaseOrderId,
        ]);

        $this->applyHeaderTotals($lines, function (array $totals) use ($purchaseOrderId): void {
            $header = $this->purchaseOrderRepository->findById($purchaseOrderId);
            if (! $header instanceof DataRecord) {
                return;
            }

            $headerDiscountAmount = round((float) $header->get('header_discount_amount', 0), 4);
            $headerTaxAmount = round((float) $header->get('header_tax_amount', 0), 4);
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

            $this->purchaseOrderRepository->update($purchaseOrderId, [
                'subtotal' => $totals['subtotal'],
                'line_tax_total' => $totals['line_tax_total'],
                'line_discount_total' => $totals['line_discount_total'],
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'balance' => $balance,
            ]);
        });
    }

    private function recalculateGdnTotals(int $gdnHeaderId, int $tenantId): void
    {
        $lines = $this->gdnLineRepository->list([
            'tenant_id' => $tenantId,
            'gdn_header_id' => $gdnHeaderId,
        ]);

        $this->applyHeaderTotals($lines, function (array $totals) use ($gdnHeaderId): void {
            $header = $this->gdnHeaderRepository->findById($gdnHeaderId);
            if (! $header instanceof DataRecord) {
                return;
            }

            $headerDiscountAmount = round((float) $header->get('header_discount_amount', 0), 4);
            $headerTaxAmount = round((float) $header->get('header_tax_amount', 0), 4);
            $debitNoteTotal = round((float) $header->get('debit_note_total', 0), 4);
            $creditNoteTotal = round((float) $header->get('credit_note_total', 0), 4);

            $discountTotal = round($totals['line_discount_total'] + $headerDiscountAmount, 4);
            $taxTotal = round($totals['line_tax_total'] + $headerTaxAmount, 4);
            $grandTotal = round(
                $totals['subtotal'] - $discountTotal + $taxTotal + $debitNoteTotal - $creditNoteTotal,
                4,
            );

            $this->gdnHeaderRepository->update($gdnHeaderId, [
                'subtotal' => $totals['subtotal'],
                'line_tax_total' => $totals['line_tax_total'],
                'line_discount_total' => $totals['line_discount_total'],
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
            ]);
        });
    }

    private function recalculateSalesReturnTotals(int $purchaseReturnId, int $tenantId): void
    {
        $lines = $this->purchaseReturnLineRepository->list([
            'tenant_id' => $tenantId,
            'sales_return_id' => $purchaseReturnId,
        ]);

        $this->applyHeaderTotals($lines, function (array $totals) use ($purchaseReturnId): void {
            $header = $this->purchaseReturnRepository->findById($purchaseReturnId);
            if (! $header instanceof DataRecord) {
                return;
            }

            $headerDiscountAmount = round((float) $header->get('header_discount_amount', 0), 4);
            $headerTaxAmount = round((float) $header->get('header_tax_amount', 0), 4);
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

            $this->purchaseReturnRepository->update($purchaseReturnId, [
                'subtotal' => $totals['subtotal'],
                'line_tax_total' => $totals['line_tax_total'],
                'line_discount_total' => $totals['line_discount_total'],
                'line_restocking_total' => $lineRestockingTotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
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
